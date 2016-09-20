;
; Export manual datalogger DATA -> CSV -> GZIP -> HTTP
;

#AutoIt3Wrapper_Icon=datalogger.ico
#NoTrayIcon

;INCLUDE
#include <GUIConstantsEx.au3>
#include <GUIComboBox.au3>
#include <GUIEdit.au3>
#include <Datalogger.au3>
#include <ZLIB.au3>

;VAR
$location='datalogger'
$runtime = @YEAR & @MON & @MDAY & 'T' & @HOUR & @MIN & @SEC

;CONTROL
;already running
if UBound(ProcessList(@ScriptName)) > 2 then Exit

;LOGGING
$logfile = FileOpen(@scriptdir & '\' & $location & '-amusing.log', 1); append..
if @error then exit; silent exit..
$history = FileReadLine(@scriptdir & '\' & $location & '-amusing.log', -1)
$last = StringRegExpReplace($history, "(.*)\|.*", "$1"); last dir..
logger(@CRLF & "Program start: " & $runtime)

;GUI
$gui = GUICreate("Datalogger v 1.5", 351, 91)
$gui_type = GUICtrlCreateCombo("", 6, 8, 75,25, 0x003); no edit
$gui_path = GUICtrlCreateInput($last, 87, 8, 175, 21)
$button_path = GUICtrlCreateButton("Prochazet", 270, 8, 75, 21)
$gui_progress = GUICtrlCreateProgress(6, 38, 338, 16)
$gui_error = GUICtrlCreateLabel("", 8, 65, 168, 15)
$button_export = GUICtrlCreateButton("Export", 188, 63, 75, 21)
$button_exit = GUICtrlCreateButton("Konec", 270, 63, 75, 21)

;GUI INIT
GUICtrlSetData($gui_type,"s3120|prumstav|pracom|merlin|zth|d3120|datalogger","s3120")
GUICtrlSetState($gui_path,$GUI_FOCUS)
_GUICtrlEdit_SetSel($gui_path,-1,-1)
_GUICtrlComboBox_SetCurSel($gui_type, StringRegExpReplace($history, ".*\|(\d)", "$1"))
GUISetState(@SW_SHOW)

While 1
	$event = GUIGetMsg(); catch event
	if $event = $button_path Then; data path
		$logger_path = FileSelectFolder("Datalogger/Serial Directory", @HomeDrive, Default, $last)
		if not @error then
				GUICtrlSetData($gui_path, $logger_path)
				$last = $logger_path; update last..
		endif
	EndIf
	if $event = $button_export Then; export
		if GUICtrlRead($gui_path) == '' then
			GUICtrlSetData($gui_error, "Chyba: Prazdna cesta.")
		ElseIf not FileExists(GUICtrlRead($gui_path)) Then
			GUICtrlSetData($gui_error, "Chyba: Adresar neexistuje.")
		else
			$filelist = getSIDarray(GUICtrlRead($gui_type), GUICtrlRead($gui_path))
			if ubound($filelist) < 2 then
				GUICtrlSetData($gui_error, "Chyba: Adresar neobsahuje data.")
			else
				for $i=1 to UBound($filelist) - 1
					GUICtrlSetData($gui_error, StringRegExpReplace($filelist[$i], ".*\\(.*)$", "$1"))
					GUICtrlSetData($gui_progress, round( $i / (UBound($filelist) - 1) * 100)); update progress
					$csv = getCSV(GUICtrlRead($gui_type), StringRegExpReplace($filelist[$i], ".*\\(.*)\\.*$", "$1"), $filelist[$i])
					if @error then
						logger($csv)
					else
						if GUICtrlRead($gui_type) = 'datalogger' and StringRegExp($filelist[$i],".*\\.*-.*$") then export(StringRegExpReplace($filelist[$i],".*\\(.*)-.*$","$1"), $runtime & StringRegExpReplace($i,"(?<!\d)(\d)(?!\d)","0$1"), $csv)
						if GUICtrlRead($gui_type) <> 'datalogger' then export(GUICtrlRead($gui_type), $runtime & StringRegExpReplace($i,"(?<!\d)(\d)(?!\d)","0$1"), $csv)
						if @error then FileMove($filelist[$i], $filelist[$i] & '.done', 1); overwrite
					endif
				next
				GUICtrlSetData($gui_progress,0); clear progress
				GUICtrlSetData($gui_error, "Hotovo!")
			endif
		endif
	endif
	If $event = $GUI_EVENT_CLOSE or $event = $button_exit then
		logger("Program end.")
		FileWrite($logfile, GUICtrlRead($gui_path) & '|' & _GUICtrlComboBox_GetCurSel($gui_type)); history..
		FileClose($logfile)
		Exit; exit
	endif
WEnd

;FUNC
Func getCSV($type,$serial,$file)
	local $data
	switch $type
		case 'prumstav'
			$data = _GetDS100($serial, $file)
		case 's3120','d3120','zth'
			$data = _GetDS3120($serial, $file)
		case 'pracom'
			$data = _GetDL121TH($serial, $file)
		case 'merlin'
			$data = _GetDLHM8($serial, $file)
		case 'datalogger'
			$data = _GetDL($file)
	EndSwitch
	if @error then SetError(1, 0, $data)
	return $data
EndFunc

func getSIDarray($type,$dir)
	local $datalist
	switch $type
		case 'prumstav','datalogger'
			$datalist = _FileListToArrayRec($dir, '*.csv', 1, 1, 1, 2); recursion, files only, fullpath, sorted..
		case 's3120','d3120','zth'
			$datalist = _FileListToArrayRec($dir, '*.dbf', 1, 1, 1, 2); recursion, files only, fullpath, sorted..
		case 'pracom'
			$datalist = _FileListToArrayRec($dir, '*.xls', 1, 1, 1, 2); recursion, files only, fullpath, sorted..
		case 'merlin'
			$datalist = _FileListToArrayRec($dir, '*.xlsx', 1, 1, 1, 2); recursion, files only, fullpath, sorted..
	EndSwitch
	Return $datalist
EndFunc

Func export($type,$timestamp,$data)
	$http_error_handler = ObjEvent("AutoIt.Error", "get_http_error"); register COM error handler
	$http = ObjCreate("winhttp.winhttprequest.5.1"); HTTP object instance
	if @error then
		logger("HTTP failed to create session.")
		return
	else
		$payload = _ZLIB_GZCompress($data)
		if @error then
			logger("Failed to create payload.")
			return
		else
			$http.open("POST","[removed]", False); No async HTTP..
			$http.SetRequestHeader("X-Location", $type & '-' & $timestamp)
			$http.Send($payload)
			if @error or $http.Status <> 200 then
					logger("Payload HTTP transfer failed.")
					return
			endif
		EndIf
	endif
	$http_error_handler = ""; Unregister COM error handler
	return SetError(1,0,"Transport succeed.")
EndFunc

func get_http_error()
	logger("HTTP request timeout.")
EndFunc

func logger($text)
	FileWriteLine($logfile, $text)
endfunc
