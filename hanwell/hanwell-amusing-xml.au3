;
; Hanwell RadioLog XML -> CSV -> GZIP -> HTTP
;
; schtasks /create /tn "Hanwell Amusing" /tr "c:\hanwell-amusing\hanwell-amusing.exe" /sc MINUTE /mo 15
;

#AutoIt3Wrapper_Icon=hanwell.ico
#NoTrayIcon

;INCLUDE

#include<Date.au3>
#include<File.au3>
#include<ZLIB.au3>
#include<_XMLDomWrapper.au3>

;VAR

$location='hanwell'
$hanwell = 'c:\Radiolog8ForMuseums\Local\XML\masterstatus.xml'

$map = @ScriptDir & '\' & $location & '-sensor.txt'
$xml = @ScriptDir & '\' & 'master.xml'

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
xml(); Parse data from XML
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

func xml()
	local $mapping
	local $child[5] = ['ID','InService','Data0','Data1','LastSignalTime']

	$xmlfile = FileOpen($xml,258); UTF-8 & overwrite
	if @error then
		logger("Failed to open XML file.")
		return
	endif
	FileWrite($xmlfile,FileRead($hanwell)); iso-8859-1 => utf-8
	if @error then
		logger("Failed to write XML file.")
		return
	endif
	FileClose($xmlfile)
	_FileReadToArray($map, $mapping, 0); 0 based..
	if @error then
		logger("Failed to read mapping file.")
		return
	endif
	$csv = FileOpen(@ScriptDir & '\' & $location & '-' & $runtime & '.csv', 1); append
	if @error then
		logger("Failed to create CSV file.")
		return
	endif
	_XMLFileOpen($xml)
	if @error then
		logger("Failed to create XML instance.")
		return
	endif
	$cnt = _XMLGetNodeCount('/LiveSensorData/Sensor')
	if @error then
		logger("No XML data.")
		return
	endif
	local $data[$cnt][5]
	for $i = 1 to $cnt
		for $j = 0 to ubound($child)-1
			$data[$i-1][$j] = _XMLGetValue('/LiveSensorData/Sensor[' & $i & ']/' & $child[$j])[1]
			if @error then
				logger("Failed to get XML value.")
				continueloop
			endif
		next
	next
	for $k = 0 to UBound($data) - 1
		if $data[$k][1] == 'true' and $data[$k][4] then; active sensor and running
			$type = get_sensor_type($data[$k][0],$mapping)
			if $type = '' then
				logger("No mapping for ID. " & $data[$k][0])
				continueloop
			endif
			$timestamp = get_timestamp($data[$k][4]); RT to UTC
			if $data[$k][2] then; check empty
				FileWriteLine($csv, $type[0] & ';' & $type[1] & ';' & $data[$k][2] & ';' & $timestamp)
			endif
			if ubound($type) = 3 then
				if $data[$k][3] then; check empty
					FileWriteLine($csv, $type[0] & ';' & $type[2] & ';' & $data[$k][3] & ';' & $timestamp)
				endif
			endif
		endif
	next
	FileClose($csv)
EndFunc

func archive()
	$archlist = _FileListToArray(@scriptdir & '\archive', "*.gz")
	if ubound($archlist) < 2 then
		logger("No file to cleanup..")
	else
		for $i=1 to UBound($archlist) - 1
			$ctime = FileGetTime(@ScriptDir & '\archive\' & $archlist[$i], 1); FT_CREATED -> array
			if _DateAdd('w', '-3', @YEAR & '/' & @MON & '/' & @MDAY) > $ctime[0] & '/' & $ctime[1] & '/' & $ctime[2] then; older than 3 weeks
				FileDelete(@ScriptDir & '\archive\' & $archlist[$i])
			endif
		next
	EndIf
endfunc

func get_timestamp($time)
	$rtime = StringRegExpReplace($time, "^(\d{2})(\d{2})(\d{4}) (\d{2}):(\d{2}):(\d{2})$", "$3/$2/$1 $4:$5:$6")
	$utc_time = _DateAdd('h', -1 + _Date_Time_GetTimeZoneInformation()[1]/60, $rtime)
	return StringRegexpReplace($utc_time, "^(\d{4})/(\d{2})/(\d{2}) (\d{2}):(\d{2}):(\d{2})$", "$1$2$3T$4$5$6Z")
endFunc

func get_sensor_type($id,$map)
	for $i=0 to Ubound($map) - 1
		$line = StringSplit($map[$i], ';', 2)
		if  $id == $line[0] then
			_ArrayDelete($line,0)
			return $line
		endif
	next
endFunc

func get_http_error()
	logger("HTTP request timeout.")
EndFunc

func logger($text)
	FileWriteLine($logfile, $text)
endfunc
