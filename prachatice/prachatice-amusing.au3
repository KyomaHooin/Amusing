;
; Prachatice: Comet DBF -> CSV -> GZ -> HTTP
;
; schtasks /create /tn "Prachatice Amusing HTTP" /tr "c:\prachatice-amusing\prachatice-amusing.exe" /sc HOURLY
;

#AutoIt3Wrapper_Icon=prachatice.ico
#NoTrayIcon

;INCLUDE

#include<Date.au3>
#include<File.au3>
#include<ZLIB.au3>
#include<Xbase.au3>

;VAR

$location = 'prachatice'
$runtime = @YEAR & @MON & @MDAY & 'T' & @HOUR & @MIN & @SEC

$comet = 'notepad.exe'
$comet_location = 'c:/windows/'

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
comet(); Check parent program.
;dbf(); Parse data from DBF
;main(); Pack and transport data over HTTP
;archive(); Archive logrotate
logger("Program end.")
FileClose($logfile)

;--------------------------------------------------

;FUNC
func main()
	;CSV + GZ
	$csvlist = _FileListToArray(@ScriptDir, "*.csv")
	if ubound($csvlist) < 2 then
		logger("No CSV file.")
		return
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

func dbf()
	local $sensor, $dbf; array def.
	$sensorlist = _FileListToArray(@scriptdir, "*.txt")
	if ubound($sensorlist) < 2 then
		logger("No sensor mapping found.")
		return
	else
		for $i=1 to UBound($sensorlist) - 1
			_FileReadToArray(@ScriptDir & '\' & $sensorlist[$i], $sensor, 0); zero based array
			if UBound($sensor) = 0 Then
					logger("Empty sensor file " & $sensorlist[$i])
					return
			endif
			$controller = StringRegExpReplace($sensorlist[$i],"(\d+)-sensor.txt","$1")
			$dbflist = _FileListToArray(@ScriptDir & '\' & $controller, "*.dbf")
			if ubound($dbflist) < 2 then
				logger("No DBF found for controller " & $controller)
				return
			EndIf
			for $j=1 to UBound($dbflist) - 1
				_Xbase_ReadToArray(@ScriptDir & '\' & $controller & '\' & $dbflist[$j], $dbf)
				if @error Then
					logger("Failed to parse DBF " & $dbflist[$j])
					continueloop; skip the broken one..
				endif
				if (UBound($dbf, 2) - 3)/2 <> UBound($sensor) Then; DBF/sensor column test
					logger("DBF/sensor column do not match.")
					continueloop; skip the incorrect one..
				endif
				$csv = FileOpen(@ScriptDir & '\' & $location & '-' & $runtime & '.csv', 1);  1 - append
				if @error Then
					logger("Failed to create CSV file.")
					return
				endif
				for $k=0 to UBound($sensor) - 1
					for $m=0 to UBound($dbf, 1) - 1; rows..
						;dd-mm-YYYY -> YYYYmmdd HH:ii:ss -> HHmmss
						$timestamp = StringRegExpReplace($dbf[$m][0],"^(\d{2})-(\d{2})-(\d{4})$", "$3$2$1") & 'T' & StringRegExpReplace($dbf[$m][1],"^(\d{2}):(\d{2}):(\d{2})$", "$1$2$3")
						;write data
						FileWriteLine($csv, $sensor[$k] & ';' & 'temperature' & ';' & $dbf[$m][$k+2] & ';' & $timestamp ); offset 3 col
						FileWriteLine($csv, $sensor[$k] & ';' & 'humidity' & ';' & $dbf[$m][$k+3] & ';' & $timestamp ); offset 4 col
					next
				next
				FileClose($csv)
			next
		Next
	endif
EndFunc

func comet()
	if not processexists($comet) then
		logger("Export service not running, restarting..")
		Run($comet_location & $comet)
		if @error then logger("Failed to restart export service..")
	endif
endfunc

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

func logger($text)
	FileWriteLine($logfile, $text)
endfunc
