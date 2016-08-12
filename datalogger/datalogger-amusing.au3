;
; Export manual datalogger DATA -> CSV -> GZIP -> HTTP
;
; -CSV buffer to file
;

#AutoIt3Wrapper_Icon=datalogger.ico
#NoTrayIcon

;INCLUDE
#include <GUIConstantsEx.au3>
#include <Datalogger.au3>
#include <ZLIB.au3>
#include <GUIEdit.au3>

;VAR
$location='datalogger'
$runtime = @YEAR & @MON & @MDAY & 'T' & @HOUR & @MIN & @SEC

;CONTROL
;already running
if UBound(ProcessList(@ScriptName)) > 2 then Exit

;LOGGING
$logfile = FileOpen(@scriptdir & '\' & $location & '-amusing.log', 1); append..
if @error then exit; silent exit..
$last = FileReadLine(@scriptdir & '\' & $location & '-amusing.log', -1); history..
logger(@CRLF & "Program start: " & $runtime)

;GUI
$gui = GUICreate("Datalogger v 1.2", 351, 91)
$gui_type = GUICtrlCreateCombo("", 6, 8, 75,25, 0x003); no edit
$gui_path = GUICtrlCreateInput($last, 87, 8, 175, 21)
$button_path = GUICtrlCreateButton("Prochazet", 270, 8, 75, 21)
$gui_progress = GUICtrlCreateProgress(6, 38, 338, 16)
$gui_error = GUICtrlCreateLabel("", 8, 65, 168, 17)
$button_export = GUICtrlCreateButton("Export", 188, 63, 75, 21)
$button_exit = GUICtrlCreateButton("Exit", 270, 63, 75, 21)

;GUI INIT
GUICtrlSetData($gui_type,"merlin|prumstav|s3120|volcraft","merlin")
GUICtrlSetState($gui_type,$GUI_DISABLE)
GUICtrlSetState($gui_path,$GUI_FOCUS)
_GUICtrlEdit_SetSel($gui_path,-1,-1)
GUISetState(@SW_SHOW)

While 1
	$event = GUIGetMsg(); catch event
	;serial solving
	if GUICtrlGetState($gui_type) == $GUI_DISABLE + $GUI_SHOW and StringRegExp(GUICtrlRead($gui_path),'(prumstav\d+|pracom\d+|\d{8})\\?$') then GUICtrlSetState($gui_type,$GUI_ENABLE)
	if GUICtrlGetState($gui_type) == $GUI_ENABLE + $GUI_SHOW and not StringRegExp(GUICtrlRead($gui_path),'(prumstav\d+|pracom\d+|\d{8})\\?$') then GUICtrlSetState($gui_type,$GUI_DISABLE)
	if $event = $button_path Then; data path
		$logger_path = FileSelectFolder("Datalogger/Serial Directory", @HomeDrive, Default, $last)
		if not @error Then GUICtrlSetData($gui_path, $logger_path)
	EndIf
	if $event = $button_export Then; export
		if GUICtrlRead($gui_path) == '' then
			GUICtrlSetData($gui_error, "Chyba: Prazdna cesta.")
		ElseIf not FileExists(GUICtrlRead($gui_path)) Then
			GUICtrlSetData($gui_error, "Chyba: Adresar neexistuje.")
		;type solving
		elseif StringRegExp(GUICtrlRead($gui_path),"(prumstav|volcraft|merlin|s3120)\\?$") then
			$type = StringRegExpReplace(GUICtrlRead($gui_path),".*(prumstav|volcraft|merlin|s3120)\\?$","$1")
			$seriallist = _FileListToArray(GUICtrlRead($gui_path), Default, 2); dirs only..
			if ubound($seriallist) < 2 then
				logger("Adresar neobsahuje senzor: " & GUICtrlRead($gui_path))
			else
				for $i=1 to UBound($seriallist) - 1
					$filelist = getSIDarray($type, GUICtrlRead($gui_path) & '\' & $seriallist[$i])
					if ubound($filelist) < 2 then
						logger("Adresar neobsahuje data: " & GUICtrlRead($gui_path) & '\' & $seriallist[$i])
					Else
						GUICtrlSetState($button_export,$GUI_DISABLE); disable re-export
						for $j=1 to UBound($filelist) - 1
							$csv = getCSV($type, GUICtrlRead($gui_path) & '\' & $seriallist[$i] & '\' & $filelist[$j])
							if @error Then
								logger($csv)
							else
								export($csv)
								if not @error then FileMove(GUICtrlRead($gui_path) & '\' & $seriallist[$i] & '\' & $filelist[$j], GUICtrlRead($gui_path)& '\' & $seriallist[$i] & '\' & $filelist[$j] & '.done')
							endif
							GUICtrlSetData($gui_progress, round( $j / (UBound($filelist) - 1) * 100)); update progress
						next
					Endif
				next
				GUICtrlSetState($button_export,$GUI_ENABLE); enable export
				GUICtrlSetData($gui_progress,0); clear progress
				GUICtrlSetData($gui_error, "Hotovo!")
			endif
		;sensor solving
		elseif StringRegExp(GUICtrlRead($gui_path),'(prumstav\d+|pracom\d+|\d{8})\\?$') then
			$filelist = getSIDarray(GUICtrlRead($gui_type), GUICtrlRead($gui_path))
			if ubound($filelist) < 2 then
				logger("Adresar neobsahuje data: " & GUICtrlRead($gui_path))
			else
				GUICtrlSetState($button_export,$GUI_DISABLE); disable re-export
				for $i=1 to UBound($filelist) - 1
					$csv = getCSV(GUICtrlRead($gui_type), GUICtrlRead($gui_path) & '\' & $filelist[$i])
					if @error then
						logger($csv)
					else
						export($csv)
						if not @error then FileMove(GUICtrlRead($gui_path) & '\' & $filelist[$i], GUICtrlRead($gui_path) & $filelist[$i] & '.done')
					endif
					GUICtrlSetData($gui_progress, round( $i / (UBound($filelist) - 1) * 100)); update progress
				next
				GUICtrlSetState($button_export,$GUI_ENABLE); enable export
				GUICtrlSetData($gui_progress,0); clear progress
				GUICtrlSetData($gui_error, "Hotovo!")
			endif
		else
			GUICtrlSetData($gui_error, "Chyba: Neplatny nazev adresare.")
		endif
	endif
	If $event = $GUI_EVENT_CLOSE or $event = $button_exit then
		logger("Program end.")
		if GUICtrlRead($gui_path) then FileWrite($logfile, GUICtrlRead($gui_path)); history..
		if not GUICtrlRead($gui_path) then FileWrite($logfile, @CRLF); no history..
		FileClose($logfile)
		Exit; exit
	endif
WEnd

;FUNC

Func getCSV($sid,$file)
	local $data
	switch $sid
		case 'prumstav'
			$data = _GetDLPrumstav($file)
		case 'volcraft'
			$data = _GetDLVolcraft($file)
		case 'merlin'
			$data = _GetDLMerlin($file)
		case 's3120'
			$data = _GetDLS3120($file)
	EndSwitch
	if @error then SetError(1, 0, $data)
	return $data
EndFunc

func getSIDarray($sid,$dir)
	local $datalist
	switch $sid
		case 'prumstav'
			$datalist = _FileListToArray($dir, "*.csv", 1); files only..
		case 'volcraft','merlin'
			$datalist = _FileListToArray($dir, "*.xls", 1); files only..
		case 's3120'
			$datalist = _FileListToArray($dir, "*.dbf", 1); files only..
	EndSwitch
	Return $datalist
EndFunc

Func export($data)
	$http_error_handler = ObjEvent("AutoIt.Error", "get_http_error"); register COM error handler
	$http = ObjCreate("winhttp.winhttprequest.5.1"); HTTP object instance
	if @error then
		logger("HTTP failed to create session.")
		return SetError(-1)
	else
		$payload = _ZLIB_GZCompress($data)
		if @error then
			logger("Failed to create payload.")
			return SetError(-1)
		else
		$http.open("POST","[removed]", False); No async HTTP..
		$http.SetRequestHeader("X-Location", StringRegExpReplace($payload, "^(" & $location & "-\d+T\d+)(.*)","$1"))
			$http.Send($payload)
			if @error or $http.Status <> 200 then
				logger("Payload HTTP transfer failed.")
				return SetError(-1)
			endif
		EndIf
	endif
	$http_error_handler = ""; Unregister COM error handler
EndFunc

func get_http_error()
;	GUICtrlSetData($gui_error, "HTTP request timeout.")
	logger("HTTP request timeout.")
EndFunc

func logger($text)
	FileWriteLine($logfile, $text)
endfunc
