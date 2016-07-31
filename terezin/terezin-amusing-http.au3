;
; Terezin: InSigHT TeNDencies TSQL -> CSV -> GZ -> HTTP
;
; schtasks /create /tn "Terezin Amusing HTTP" /tr "c:\terezin-amusing-http\terezin-amusing-http.exe" /sc HOURLY
;

#AutoIt3Wrapper_Icon=terezin.ico
#NoTrayIcon

;INCLUDE

#include<Date.au3>
#include<File.au3>
#include<_SQL.au3>
#include<ZLIB.au3>

;VAR

$location = 'terezin'
$sensors = @scriptdir & '\' & $location & '-sensor.txt'
$runtime = @YEAR & @MON & @MDAY & 'T' & @HOUR & @MIN & @SEC

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
DirCreate(@scriptdir & '\http')
;MAIN
$logfile = FileOpen(@scriptdir & '\' & $location & '-amusing-http.log', 1); 1 = append
if @error then exit; silent exit..
logger(@CRLF & "Program start: " & $runtime)
sql(); Parse data from TSQL
main(); Pack and transport data over HTTP
archive(); Archive logrotate
logger("Program end.")
FileClose($logfile)

;--------------------------------------------------

;FUNC
func main()
	;CSV + GZ
	$csvlist = _FileListToArray(@ScriptDir, "*.csv")
	if ubound($csvlist) < 2 then
		logger("No CSV file.")
	else
		for $i=1 to ubound($csvlist) - 1
			_ZLIB_GZFileCompress(@ScriptDir & '\' & $csvlist[$i], @ScriptDir & '\http\' & $csvlist[$i] & '.gz')
			if not @error = 1 then
				logger("Failed to GZIP file " & $csvlist[$i])
				continueloop; skip the broken one..
			else
				FileDelete(@ScriptDir & '\' & $csvlist[$i]); CSV clenup
			endIf
		Next
	endIf
	;HTTP + ARCHIVE
	$gzlist = _FileListToArray(@scriptdir & '\http', "*.gz")
	if ubound($gzlist) < 2 then
		logger("No GZIP to transport.")
		return
	endif
	$http_error_handler = ObjEvent("AutoIt.Error", "get_http_error"); register COM error handler
	$http = ObjCreate("winhttp.winhttprequest.5.1"); HTTP object instance
	if @error then
		logger("HTTP failed to create session.")
		return
	else
		for $i=1 to ubound($gzlist) - 1
			$gz_file = FileOpen(@ScriptDir & '\http\' & $gzlist[$i], 16)
			$gz_data = FileRead($gz_file)
			FileClose($gz_file)
			$http.open("POST","[removed]", False); No async HTTP..
			$http.SetRequestHeader("X-Location", StringRegExpReplace($gzlist[$i], "^(" & $location & "-\d+T\d+)(.*)","$1"))
			$http.Send($gz_data)
			if @error or $http.Status <> 200 then
				logger("File " & $gzlist[$i] & " HTTP transfer failed.")
				continueloop; skip archiving..
			endif
			;ARCHIVE
			FileMove(@scriptdir & '\http\' & $gzlist[$i], @scriptdir & '\archive')
		next
	endif
	$http_error_handler = ""; Unregister COM error handler
endfunc

func sql()
	local $sensor
	_FileReadToArray($sensors, $sensor, 0); zero based array
	if @error Then
		logger("Missing file: " & $sensors)
		return
	endif
	_SQL_RegisterErrorHandler(); register ADODB COM handler
	$adodb = _SQL_Startup(); ADODB object instance
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
		local $id_query = "SELECT TrendLogId FROM Designation WHERE Path LIKE '" & StringRegExpReplace($sensor[$i],"^(.*);(.*)$","$1") & "'"; Find device ID by name (Terezin1_B_HGrp0007_TR_PrVal).
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
				$csv = FileOpen(@ScriptDir & '\' & $location & '-' & $runtime & '.csv', 1);  1 - append
				if @error Then
					logger("Failed to create CSV file.")
					_SQL_Close()
					return
				endif
				local $data_row
				while _SQL_FetchData($data, $data_row) = $SQL_OK
					if $data_row[2] = '192' Then; 192 -> alive
						;YYYYmmddHHiiss -> ISO: YYYYMMDDThhmmss
						$timestamp = StringRegExpReplace($data_row[0],"^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$", "$1/$2/$3 $4:$5:$6")
						$timestamp = _DateAdd('h', '-1' , $timestamp); GMT+1 to UTC
						$timestamp = StringRegExpReplace($timestamp,"^(\d{4})/(\d{2})/(\d{2}) (\d{2}):(\d{2}):(\d{2})$", "$1$2$3T$4$5$6")
						;write data
						FileWriteLine($csv, $sensor[$i] & ';' & $data_row[1] & ';' & $timestamp)
					endif
				wend
				FileClose($csv)
			endif
		endif
	next
	_SQL_Close()
	_SQL_UnRegisterErrorHandler(); unregister SQL COM handler
EndFunc

func archive()
	$archlist = _FileListToArray(@scriptdir & '\archive', "*.gz")
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

func get_http_error()
	logger("HTTP request timeout.")
EndFunc

func logger($text)
	FileWriteLine($logfile, $text)
endfunc
