;
; Terezin: InSigHT TeNDencies TSQL -> CSV -> ZIP -> FTP
;
; schtasks /create /tn "Terezin Amusing" /tr "c:\terezin-amusing\terezin-amusing.exe" /sc HOURLY
;

#AutoIt3Wrapper_Icon=terezin.ico
#NoTrayIcon

;INCLUDE

#include<Date.au3>
#include<File.au3>
#include<Zip.au3>
#include<FTPEx.au3>
#include<_SQL.au3>

;VAR

$sensors = @scriptdir & '\sensor.txt'
$runtime = @YEAR & @MON & @MDAY & @HOUR & @MIN
$yesterday = StringRegExpReplace(_DateAdd('d', -1, _NowCalcDate()),'/','') & @HOUR & @MIN

$ftp_host = '[removed]'
$ftp_user = '[removed]'
$ftp_pass = '[removed]'

$sql_host = '[removed]'
$sql_user = '[removed]'
$sql_pass = '[removed]'
$sql_db = '[removed]'; InSigHT TeNDencies

;--------------------------------------------------

;CONTROL

;RUN
if ubound(ProcessList(@ScriptName), $UBOUND_ROWS) > 2 then exit; check if running or silent exit..

;DIRS
DirCreate(@scriptdir & '\archive')
DirCreate(@scriptdir & '\ftp')

;MAIN
$logfile = FileOpen(@scriptdir & '\terezin-amusing.log', 1); 1 = append
if @error then exit; silent exit..
logger(@CRLF & "Program start: " & $runtime)
sql(); Parse data from TSQL
main(); Pack and transport data over FTP
archive(); Archive logrotate
logger("Program end.")

FileClose($logfile)

;--------------------------------------------------

;FUNC
func main()
	;CSV + ZIP
	$csvlist = _FileListToArray(@ScriptDir, "*.csv")
	if ubound($csvlist) < 2 then
		logger("No new CSV files..")
	else
		$zip = _Zip_create(@scriptdir & '\ftp\terezin-' & @YEAR & @MON & @MDAY & 'T' & @HOUR & @MIN & @SEC & '.zip')
		if @error then
			logger("Cannot create ZIP archive..")
			return
		endif
		for $i=1 to UBound($csvlist) - 1
			_Zip_AddFile($zip, @ScriptDir & '\' & $csvlist[$i], 0); 0 = no progress box
			if not @error then FileDelete(@ScriptDir & '\' & $csvlist[$i]); clean the CSV
		next
		if ubound(_Zip_List($zip)) = 1 then FileDelete($zip); Remove empty ZIP archive
	endif
	;FTP + ARCHIVE
	$ziplist = _FileListToArray(@scriptdir & '\ftp', "*.zip")
	if ubound($ziplist) < 2 then
		logger("Nothin' to transport..")
		return
	endif
	$socket = _FTP_Open('Desigo FTP')
	$session = _FTP_Connect($socket, $ftp_host, $ftp_user, $ftp_pass, 1); 1 = passive FTP
	if @error then
		logger("Failed to create FTP session..")
		return
	endif
	for $i=1 to ubound($ziplist) - 1
		_FTP_FilePut($session, @ScriptDir & '\ftp\' & $ziplist[$i], $ziplist[$i])
		if @error then
			logger("File " & $ziplist[$i] & " transfer failed." )
			continueloop; skip archiving..
		endif
		;ARCHIVE
		FileMove(@scriptdir & '\ftp\' & $ziplist[$i], @scriptdir & '\archive')
	next
	_FTP_Close($session)
	_FTP_Close($socket)
endfunc

func logger($text)
	FileWriteLine($logfile, $text)
endfunc

func sql()
	local $sensor
	_FileReadToArray($sensors, $sensor, 0); zero based array
	if @error Then
		logger("Missing file sensor.txt.")
		return
	endif
	_SQL_RegisterErrorHandler()
	$adodb = _SQL_Startup()
	if $adodb = $SQL_ERROR then
		logger("SQL1: " & _SQL_GetErrMsg())
		return
	endif
	$sql = _sql_Connect($adodb, $sql_host, $sql_db, $sql_user, $sql_pass)
	if $sql = $SQL_ERROR then
		logger("SQL2: " & _SQL_GetErrMsg())
		return
	endif
	for $i=0 to ubound($sensor) - 1
		local $id_query = "SELECT TrendLogId FROM Designation WHERE Path LIKE '" & $sensor[$i] & "'"; Find device ID by name (Terezin1_B_HGrp0007_TR_PrVal).
		$id  = _SQL_Execute($adodb, $id_query)
		if $id = $SQL_ERROR then
			logger("SQL3: " & _SQL_GetErrMsg())
			_SQL_Close()
			return
		else
			local $data_query = "SELECT distinct top 10000 DateTimeStamp,Value,QualityTag FROM TrendRecord WHERE (DateTimeStamp > DATEADD (day, -1, GETDATE())) AND TrendLogId = '" & $id.Fields(0).Value & "' ORDER BY DateTimeStamp asc"; Return week data by ID.
			$data = _SQL_Execute($adodb, $data_query)
			if $data = $SQL_ERROR then
				logger("SQL4: " & _SQL_GetErrMsg())
				_SQL_Close()
				return
			else
				$csv = FileOpen(@ScriptDir & '\' & $runtime & '_' & $yesterday & '.csv', 1);  1 - append
				if @error Then
					logger("Failed to create CSV file.")
					_SQL_Close()
					return
				endif
				local $data_row
				while _SQL_FetchData($data, $data_row) = $SQL_OK
					if $data_row[2] = '192' Then
						;split date by space
						$timestamp = StringRegExpReplace($data_row[0],"^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$", "$3 $2 $1 $4 $5 $6")
						;remove leading zero from day and month
						$timestamp = StringRegExpReplace($timestamp, "^0([0-9])", "$1")
						$timestamp = StringRegExpReplace($timestamp, "^(\d+) 0([0-9])", "$1 $2")
						;YYYYmmddHHiiss -> 'jj.nn.YYYY HH:ii:ss'
						$timestamp = StringRegExpReplace($timestamp,"^(\d+) (\d+) (\d+) (\d+) (\d+) (\d+)$", "$1\.$2\.$3 $4:$5:$6")
						;write data
						FileWriteLine($csv, $sensor[$i] & ';' & $timestamp & ';' & $data_row[1]); 192 -> Alive
					endif
				wend
				FileClose($csv)
			endif
		endif
	next
	_SQL_Close()
EndFunc

func archive()
	$archlist = _FileListToArray(@scriptdir & '\archive', "*.zip")
	if ubound($archlist) < 2 then
		logger("Nothin' to cleanup..")
	else
		for $i=1 to UBound($archlist) - 1
			$ctime = FileGetTime(@ScriptDir & '\archive\' & $archlist[$i], 1); FT_CREATED -> array
			if _DateAdd('w', '-3', @YEAR & '/' & @MON & '/' & @MDAY) > $ctime[0] & '/' & $ctime[1] & '/' & $ctime[2] then; older than 3 weeks
				FileDelete(@ScriptDir & '\archive\' & $archlist[$i])
			endif
		next
	EndIf
endfunc
