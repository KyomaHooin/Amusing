;
; Sauter NovaPro 374c binary HDB DS -> CSV -> GZIP -> HTTP
;
; schtasks /create /tn "Pocernice Amusing HTTP" /tr "c:\pocernice-amusing\pocernice-amusing.exe" /sc HOURLY
;

#AutoIt3Wrapper_Icon=pocernice.ico
#NoTrayIcon

;INCLUDE

#include<Date.au3>
#include<File.au3>
#include<ZLIB.au3>
#include<DS.au3>

;VAR

$location='pocernice'

$HDB = @ScriptDir & '\' & $location & '-hdb.txt'
$MAP = @ScriptDir & '\' & $location & '-sensor.txt'
;$DSPATH = 'c:\pvmpdata\Projekt\DEPOZIT\DS\'
$DSPATH = 'c:\pocernice\ds\'

$runtime = @YEAR & @MON & @MDAY & 'T' & @HOUR & @MIN & @SEC
$dstime =  @YEAR & '/' & @MON & '/' & @MDAY & ' ' & @HOUR & ':' & '45' & ':' & '00'


;--------------------------------------------------

;CONTROL

;RUN
if ubound(ProcessList(@ScriptName), $UBOUND_ROWS) > 2 then exit; check if running or silent exit..

;DIRS
DirCreate(@scriptdir & '\archive')
DirCreate(@scriptdir & '\http')

;MAIN
$logfile = FileOpen(@scriptdir & '\' & $location & '-amusing.log', 1); 1 = append
if @error then exit; silent exit..
logger(@CRLF & "Program start: " & $runtime)
ds(); Parse data from DS buffer
;main(); Pack and transport data over HTTP
;archive(); Archive logrotate
logger("Program end.")
FileClose($logfile)

;--------------------------------------------------

;FUNC
func main()
	;CSV + GZIP
	$csvlist = _FileListToArray(@ScriptDir, "*.csv")
	if ubound($csvlist) < 2 then
		logger("No new CSV files..")
	else
		for $i=1 to ubound($csvlist) - 1
			_ZLIB_GZFileCompress(@ScriptDir & '\' & $csvlist[$i], @ScriptDir & '\http\' & $csvlist[$i] & '.gz')
			if not @error = 1 then
				logger("Failed to GZIP file " & $csvlist[$i])
				continueloop; skip the broken one
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
			$http.open("POST","http://amusing.nm.cz/sensors/rawpost.php", False); No async HTTP..
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

func ds()
	local $DS, $file, $mapping, $sid, $conversion, $data, $csv
	_FileReadToArray($HDB,$DS,0)
	if ubound($DS) < 2 then
		logger('Missing HDB list.')
		return
	endif
	$mapping = GetDSMapping($MAP)
	if $mapping = '' then
		logger('Missing sensor list.')
		return
	endif
	for $i=0 to UBound($DS) - 1
		FileCopy($DSPATH & $DS[$i] & '.DS', @ScriptDir & '\' & $DS[$i] & '.DS')
		if @error then
				logger('Failed to create DS copy.')
				continueloop
		endif
		$file = @ScriptDir & '\' & $DS[$i] & '.DS'
		$sid = _GetDSSidArray($file)
		if $sid = '' then
			logger('Failed to get SID from DS file.')
			continueloop
		Endif
		$conversion = Conversion($mapping,$DS[$i],$sid)
		if $conversion = '' then
			logger('Failed to get Conversion.')
			continueloop
		endif
		$data = _GetDSData($file,UBound($sid))
		if $data = '' then
			logger('Failed to get data.')
			ContinueLoop
		Endif
		$csv = FileOpen(@ScriptDir & '\' & $location & '-' & $runtime & '.csv', 1);  1 - append
		if @error Then
			logger("Failed to create CSV file.")
			return
		endif
		for $j=0 to UBound($conversion) - 1
			$time = $dstime; reset time counter..
			for $k=0 to UBound($data) - 1
				FileWriteLine($csv, $conversion[$j][0] & ';' & $conversion[$j][1] & ';' & $data[$k][$conversion[$j][2]] & ';' & StringRegExpReplace($time, "^(\d+)/(\d+)/(\d+) (\d+):(\d+):(\d+)$", "\1\2\3T\4\5\6"))
				$time = _DateAdd('n', '-15', $time)
			next
		next
		FileClose($csv)
		FileDelete($file)
	next
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

func GetDSMapping($file)
	local $max,$map,$line
	$max = _FileCountLines($file)
	if @error then return
	local $mapping[$max][3]
	$map=FileOpen($file)
	if @error then return
	for $i=0 to $max - 1
		$line = StringSplit(FileReadLine($map),';',2); no count..
		if UBound($line) <> 3 then return
		$mapping[$i][0]=$line[0]
		$mapping[$i][1]=$line[1]
		$mapping[$i][2]=$line[2]
	Next
	FileClose($map)
	return $mapping
EndFunc

;create conversion table
func Conversion($map,$ds,$sid)
	local $row, $data_row
	$row = _ArrayFindAll($map,$ds,Default,Default,Default,Default,0); search DS name from mappping..
	if @error then
		return
	else
		local $conversion[UBound($row)][3]
		for $i=0 to UBound($row) - 1
			$conversion[$i][0]=$map[$row[$i]][1]; store sensor name
			$conversion[$i][1]=$map[$row[$i]][2]; store value type
			$data_row = _ArraySearch($sid,$map[$row[$i]][1]); search data index from SID array..
			if @error then MsgBox(-1,"err",'No sensor data mapping found.'); CHECK !?
			$conversion[$i][2]=$data_row; store data index
		next
	endif
	Return $conversion
EndFunc
