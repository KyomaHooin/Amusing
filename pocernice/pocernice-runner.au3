;
; Very simple "hourly" runner..
;
; Start > Startup > pocernce-runner.exe
;

#AutoIt3Wrapper_Icon=pocernice.ico
#NoTrayIcon

$amusing = @ScriptDir & '\pocernice-amusing.exe'
$token=True

while 1
	if @MIN=25 and $token then
		$token=False
		Run($amusing)
	EndIf
	if @MIN=26 then $token=True
	Sleep(5000); 5sec..
WEnd

