
#AutoIt3Wrapper_Icon=mapping.ico
#NoTrayIcon

#include <GUIConstantsEx.au3>

$gui = GUICreate("Amusing Mapping Tool v 1.0", 307, 90, 192, 114)
;$gui = GUICreate("Amusing Mapping Tool v 1.0", 307, 150, 192, 114)
$popis_lokalita = GUICtrlCreateLabel("LOKALITA", 8, 6, 55, 17)
$popis_jmeno = GUICtrlCreateLabel("JMENO", 102, 6, 41, 17)
$popis_id = GUICtrlCreateLabel("ID", 285, 6, 15, 17)
$gui_lokalita = GUICtrlCreateCombo("", 8, 25, 81, 25, 0x003); no edit
$gui_nazev = GUICtrlCreateInput("", 100, 25, 153, 21)
$gui_id = GUICtrlCreateInput("", 264, 25, 33, 21,0x0002); align right
$save = GUICtrlCreateButton("ULOZIT", 224, 57, 75, 23)
$gui_error = GUICtrlCreateLabel("", 10, 62, 150, 17)
;$gui_error = GUICtrlCreateLabel("", 10, 62, 150, 100)

$url = '[removed]'

;already running
if UBound(ProcessList("amusing-mapping.exe")) > 2 then Exit

;gui init
GUISetState(@SW_SHOW)
GUICtrlSetColor($gui_error, 0xFF0000)
GUICtrlSetData($gui_lokalita,"terezin|pocernice|prachatice","terezin")
GUICtrlSetState($gui_nazev,$GUI_FOCUS)
GUICtrlSetLimit($gui_id,4,1)
;main

While 1
	;catch event
	$event = GUIGetMsg()
	If $event = $save Then
		if GUICtrlRead($gui_nazev) == '' then
			GUICtrlSetData($gui_error, "Prazdny nazev.")
		ElseIf GUICtrlRead($gui_id) == '' then
			GUICtrlSetData($gui_error, "Prazdne ID.")
		ElseIf StringRegExp(GUICtrlRead($gui_nazev),"\x22|\x27") then; filter quotes
			GUICtrlSetData($gui_error, "Neplatny nazev.")
		ElseIf not StringRegExp(GUICtrlRead($gui_id),"^\d+$") then
			GUICtrlSetData($gui_error, "Neplatne ID.")
		else
			$http_error_handler = ObjEvent("AutoIt.Error", "get_http_error"); register COM error handler
			$http = ObjCreate("winhttp.winhttprequest.5.1"); HTTP object instance
			if @error then
				GUICtrlSetData($gui_error, "Spojeni selhalo.")
			else
				$http.open("POST",$url, False); No async HTTP..
				$http.Send(GUICtrlRead($gui_lokalita) & ';' & GUICtrlRead($gui_nazev) & ';' & GUICtrlRead($gui_id))
				if @error or $http.Status <> 200 then
					GUICtrlSetData($gui_error, "Odeslani dat selhalo.")
				else
					if $http.ResponseText == 'dup' then
						GUICtrlSetData($gui_error, "Duplicitni zaznam.")
					ElseIf $http.ResponseText == 'err' then
						GUICtrlSetData($gui_error, "Ulozeni selhalo.")
					ElseIf $http.ResponseText == 'badip' then
						GUICtrlSetData($gui_error, "Pripojeni omezeno.")
					ElseIf $http.ResponseText == 'ok' then
						GUICtrlSetData($gui_error, "Ulozeno.")
						GUICtrlSetData($gui_nazev, "")
						GUICtrlSetData($gui_id, "")
					Else
						GUICtrlSetData($gui_error, "Chyba serveru: " & $http.ResponseText)
					endif
				endIf
			endif
			$http_error_handler = ""; Unregister COM error handler
		endif
	EndIf
		;exit
	If $event = $GUI_EVENT_CLOSE Then Exit
wend

func get_http_error()
	GUICtrlSetData($gui_error, "Cas pozadavku vyprsel.")
EndFunc
