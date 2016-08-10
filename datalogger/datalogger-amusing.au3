;
; Manual "datalogger" parse CSV -> GZIP -> HTTP
;
; Prumstav - CSV
; Volcraft - XLS
; Merlin   - XLS
; S3120    - DBF
;

#AutoIt3Wrapper_Icon=datalogger.ico
#NoTrayIcon

;INCLUDE
#include <GUIConstantsEx.au3>
#include <Datalogger.au3>
#include <ZLIB.au3>

;VAR
$location='datalogger'
$runtime = @YEAR & @MON & @MDAY & 'T' & @HOUR & @MIN & @SEC

;CONTROL
;already running
if UBound(ProcessList(@ScriptName)) > 2 then Exit

;LOGGING
$logfile = FileOpen(@scriptdir & '\' & $location & '-amusing.log', 1); 1 = append
if @error then exit; silent exit..
logger(@CRLF & "Program start: " & $runtime)

;GUI
$gui = GUICreate("Datalogger v 1.2", 351, 91)
$gui_type = GUICtrlCreateCombo("", 6, 8, 75,25, 0x003); no edit
$gui_path = GUICtrlCreateInput("", 87, 8, 175, 21)
$button_path = GUICtrlCreateButton("Prochazet", 270, 8, 75, 21)
$gui_progress = GUICtrlCreateProgress(6, 38, 338, 16)
$gui_error = GUICtrlCreateLabel("", 8, 65, 168, 17)
$button_export = GUICtrlCreateButton("Export", 188, 63, 75, 21)
$button_exit = GUICtrlCreateButton("Exit", 270, 63, 75, 21)

;GUI INIT
GUICtrlSetData($gui_type,"Merlin|Prumstav|S3120|Volcraft","Merlin")
GUICtrlSetState($gui_path,$GUI_FOCUS)
GUISetState(@SW_SHOW)

While 1
	$event = GUIGetMsg(); catch event
	if $event = $button_path Then; data path
		$logger_path = FileSelectFolder("Datalogger Directory", @HomeDrive)
		if not @error Then
				GUICtrlSetData($gui_path, $logger_path)
				GUICtrlSetData($gui_error,''); clear error
		endif
	EndIf
	if $event = $button_export Then; export
		if GUICtrlRead($gui_path) == '' then
			GUICtrlSetData($gui_error, "Chyba: Prazdna cesta.")
		ElseIf not FileExists(GUICtrlRead($gui_path)) Then
			GUICtrlSetData($gui_error, "Chyba: Neplatny adresar.")
		Else
			GUICtrlSetData($gui_error,''); clear error
			switch GUICtrlRead($gui_type); get all files by type
				case 'Prumstav'
					$datalist = _FileListToArray(GUICtrlRead($gui_path), "*.csv")
				case 'Volcraft','Merlin'
					$datalist = _FileListToArray(GUICtrlRead($gui_path), "*.xls")
				case 'S3120'
					$datalist = _FileListToArray(GUICtrlRead($gui_path), "*.dbf")
			EndSwitch
			if ubound($datalist) < 2 then
				GUICtrlSetData($gui_error, "Chyba: Adresar neobsahuje data.")
			Else; parse data to RAM
				GUICtrlSetState($button_export,$GUI_DISABLE); disable re-export
				for $i=1 to UBound($datalist) - 1; parse & export
					GUICtrlSetData($gui_error, "Exportuji: " & $datalist[$i]); display current file
					switch GUICtrlRead($gui_type); get all files by type
						case 'Prumstav'
							$csv = _Get_DL_Prumstav($datalist[$i])
						case 'Volcraft'
							$csv = _Get_DL_Volcraft($datalist[$i])
						case 'Merlin'
							$csv = _Get_DL_Merlin($datalist[$i])
						case 'S3120'
							$csv = _Get_DL_S3120($datalist[$i])
					EndSwitch
					if @error Then
						logger($csv)
					else
						export($csv)
					endif
					GUICtrlSetData($gui_progress, round( $i / (UBound($datalist) -1) * 100)); update progress
				Next
				GUICtrlSetData($gui_error, ''); clear error
				GUICtrlSetState($button_export,$GUI_ENABLE); enable export
				GUICtrlSetData($gui_progress,0); clear progress
			EndIf
			GUICtrlSetData($gui_error,'Export dokoncen!'); done
		endif
	endif
	If $event = $GUI_EVENT_CLOSE or $event = $button_exit then
		logger("Program end.")
		FileClose($logfile)
		Exit; exit
	endif
WEnd

;FUNC

Func export($data)
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
		$http.SetRequestHeader("X-Location", StringRegExpReplace($payload, "^(" & $location & "-\d+T\d+)(.*)","$1"))
			$http.Send($payload)
			if @error or $http.Status <> 200 then
				logger("Payload HTTP transfer failed.")
			endif
		EndIf
	endif
	$http_error_handler = ""; Unregister COM error handler
EndFunc

func get_http_error()
	GUICtrlSetData($gui_error, "HTTP request timeout.")
EndFunc

func logger($text)
	FileWriteLine($logfile, $text)
endfunc
