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

$hdb = @ScriptDir & '\' & $location & '-hdb.txt'
$map = @ScriptDir & '\' & $location & '-sensor.txt'
;$ds_path = 'c:\pvmpdata\Projekt\DEPOZIT\DS\'
$ds_path = 'c:\pocernice\ds\'

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

func ds()
	local $ds, $file, $mapping, $sid, $type, $data, $csv
	_FileReadToArray($hdb,$ds,0)
	if @error then
		logger('Missing HDB list.')
		return
	endif
	_FileReadToArray($map, $mapping, 0); zero based array
	if @error Then
		logger('Missing sensor file.')
		return
	endif
	$csv = FileOpen(@ScriptDir & '\' & $location & '-' & $runtime & '.csv', 1);  1 - append
	if @error Then
		logger("Failed to create CSV file.")
		return
	endif
	for $i=0 to UBound($ds) - 1
		$file = @ScriptDir & '\' & $ds[$i] & '.DS'
		FileCopy($ds_path & $ds[$i] & '.DS', $file)
		if @error then
			logger('Failed to create DS copy.')
			continueloop
		endif
		$sid = _GetDSSidArray($file)
		if $sid = '' then
			logger('Failed to get SID from DS file.')
			continueloop
		Endif
		$data = _GetDSData($file,UBound($sid))
		if $data = '' then
			logger('Failed to get data.')
			ContinueLoop
		Endif
		for $j=0 to UBound($sid) - 1
			$time = _DateAdd('h', '-1', $dstime); GMT+1 to UTC and time counter reset..
			;Sauter duplicity bug
			if $ds[$i] = 'DVZT01'  and $sid[$j] = 'N017M008' then $sid[$j] = 'B017M008'
			if $ds[$i] = 'DVZT01'  and $sid[$j] = 'N017M009' then $sid[$j] = 'B017M009'
			if $ds[$i] = 'DVZT01'  and $sid[$j] = 'N017M010' then $sid[$j] = 'B017M010'
			if $ds[$i] = 'DVZT01'  and $sid[$j] = 'N017M011' then $sid[$j] = 'B017M011'
			if $ds[$i] = 'DVZT01'  and $sid[$j] = 'N017M012' then $sid[$j] = 'B017M012'
			if $ds[$i] = 'DVZT01'  and $sid[$j] = 'N017M013' then $sid[$j] = 'B017M013'
			if $ds[$i] = 'BVZT02A' and $sid[$j] = 'N040M005' then $sid[$j] = 'B040M005'
			if $ds[$i] = 'BVZT02B' and $sid[$j] = 'N040M005' then $sid[$j] = 'B140M005'
			if $ds[$i] = 'BVZT04'  and $sid[$j] = 'N040M005' then $sid[$j] = 'B240M005'
			for $k=0 to UBound($data) - 1
				$type = GetSensorType($sid[$j],$mapping)
				if $type == '' Then
					logger('Failed to find SID type mapping.')
					ContinueLoop
				endif
				FileWriteLine($csv, $sid[$j] & ';' & $type & ';' & $data[$k][$j] & ';' & StringRegExpReplace($time, "^(\d{4})/(\d{2})/(\d{2}) (\d{2}):(\d{2}):(\d{2})$", "$1$2$3T$4$5$6"))
				$time = _DateAdd('n', '-15', $time)
			next
		next
		FileDelete($file)
	next
	FileClose($csv)
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

func GetSensorType($sid,$map)
	for $i=0 to UBound($map) - 1
		if $sid = StringRegExpReplace($map[$i],"^(.*);.*$","$1") then Return StringRegExpReplace($map[$i],"^.*;(.*)$","$1")
	Next
	return
EndFunc
