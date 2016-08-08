
#AutoIt3Wrapper_Icon=datalogger.ico
#NoTrayIcon

;INCLUDE

#include<GUIConstantsEx.au3>
#include<GUIListBox.au3>
#include<Date.au3>
#include<File.au3>
#include<ZLIB.au3>
#include<datalogger.au3>

;GUI

$gui = GUICreate("Datalogger v 1.2", 351, 91, 258, 155)
$gui_type = GUICtrlCreateCombo("", 6, 8, 75,25, 0x003); no edit
$gui_path = GUICtrlCreateInput("", 87, 8, 175, 21)
$button_path = GUICtrlCreateButton("Prochazet", 270, 8, 75, 21)
$gui_progress = GUICtrlCreateProgress(6, 38, 338, 16)
$gui_error = GUICtrlCreateLabel("", 8, 65, 168, 17)
$button_export = GUICtrlCreateButton("Export", 188, 63, 75, 21)
$button_exit = GUICtrlCreateButton("Exit", 270, 63, 75, 21)

;GUI INIT
GUICtrlSetColor($gui_error, 0xFF0000)
GUICtrlSetData($gui_type,"Merlin|Procom|Prumstav|S3120|Volcraft","Merlin")
GUICtrlSetState($gui_path,$GUI_FOCUS)

;CONTROL

;already running
if UBound(ProcessList(@ScriptName)) > 2 then Exit
;dirs
DirCreate(@scriptDir & '\http') 
DirCreate(@scriptDir & '\archive') 

;MAIN

GUISetState(@SW_SHOW)

While 1
	;catch event
	$event = GUIGetMsg()
	;data path
	if $event = $button_path Then
		;clear input
		GUICtrlSetData($gui_path,'')
		$logger_path = FileSelectFolder("Datalogger Directory", @HomeDrive, Default ,$gui)
		if not @error Then GUICtrlSetData($gui_path, $logger_path)
		MsgBox(-1,"var",$logger_path)
	EndIf
	;data_export
	if $event = $button_export then
		if GUICtrlRead($gui_path) == '' then
			GUICtrlSetData($gui_error, "Neplatna cesta.")
		elseif not FileExists(GUICtrlRead($gui_path)) then
			GUICtrlSetData($gui_error, "Neplatny adresar.")
		endif
	else
		;read file list to array
		;for loop call parser -> export -> archive
	endif
	;exit
	if $event = $GUI_EVENT_CLOSE or $event = $button_exit then exit
WEnd
