;
; Hanwell RadioLog RL8 -> CSV -> GZIP -> HTTP
;
; schtasks /create /tn "Hanwell Amusing" /tr "c:\hanwell-amusing\hanwell-amusing.exe" /sc HOURLY
;

#AutoIt3Wrapper_Icon=hanwell.ico
#NoTrayIcon

;INCLUDE

#include<Date.au3>
#include<File.au3>
#include<ZLIB.au3>
#include<RL8.au3>

;VAR

$location='hanwell'

$map = @ScriptDir & '\' & $location & '-sensor.txt'
$rlpath= 'c:\RadioLog8forMuseums\Local'

$runtime = @YEAR & @MON & @MDAY & 'T' & @HOUR & @MIN & @SEC

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
rl8(); Parse data from binary
main(); Pack and transport data over HTTP
archive(); Archive logrotate
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

func rl8()
	local $mapping
	$csv = FileOpen(@ScriptDir & '\' & $location & '-' & $runtime & '.csv', 1); append
	if @error then
		logger("Failed to create CSV file.")
		return
	endif
	_FileReadToArray($map, $mapping, 0); 0 based..
	if @error then
		logger("Failed to open mapping file.")
		return
	endif
	$rlist = _FileListToArray($rl8path, "*.rl8")
	if ubound($rlist) < 2 then
		logger("No sensor files..")
		return
	else
		for $i=1 to UBound($rlist) - 1
			$rl = FileOpen($rlist[$i], 16); binary
			if @error then
				logger("Failed to open sensor file " & $rlist[$i])
				continueloop
			else
				$timestamp = get_timestamp($rlist[$i]); before loading into memory..
				$rl_bin = FileRead($rl); read file into memory..
				if @error then
					logger("Failed to read sensor file " & $rlist[$i])
					FileClose($rl)
					continueloop
				else
					FileClose($rl); close the file..
					$sid = _RLGetSid($rl_bin)
					if $sid = '' then
						logger("Failed to read serial from memory.")
						continueloop
					endif
					$data = _RLGetData($rl_bin)
					if $data = '' then
						logger("Failed to read data from memory.")
						continueloop
					endif
					$type = get_sensor_type($sid,$mapping)
					if $type = '' then
						logger("No mapping for serial." & $serial)
						continueloop
					endif
					;write CSV..
					FileWriteLine($csv, $sid & $type[0] & $data[0] & $timestamp)
					if ubound($type) = 2 then; second slot..
						FileWriteLine($csv, $sid & type[1] & $data[1] & $timestamp)
					endif
				endif
				FileClose($rl_bin); clear memory..
			endif
		next
		FileClose($csv); close CSV..
	endif
	FileClose($map); close map..
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

func get_timestamp($file)
	$mtime = FileGetTime($file); FT_MODIFIED
	$utc_time = _DateAdd('h', -1 + _Date_Time_GetTimeZoneInformation()[1]/60, _
			$mtime[0] & '/' & $mtime[1] & '/' $mtime[2] & ' ' & $mtime[3] & ':' $mtime[4] & ':' $mtime[5])
	return StringRegexpReplace($utc_time, "^(\d{4})/(\d{2})/(\d{2}) (\d{2}):(\d{2}):(\d{2})$", "$1$2$3T$4$5$6Z")
endFunc

func get_sensor_type($sid,$map)
	for $i to Ubound($map) - 1
		if StringInStr($sid,$map[$i]) then return StringSplit($map[$i], ';', 2); array, no count..
	next
endFunc

func get_http_error()
	logger("HTTP request timeout.")
EndFunc

func logger($text)
	FileWriteLine($logfile, $text)
endfunc

