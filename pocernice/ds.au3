;
; TODO:
;
; -SIDMap faster.. like GetPAdd.. while 1 if 0x00 return
; -load sensor.txt sets..
; -for sensor set GetDSdata() -> arraysearch -> save CSV
; 
;  DS
;
;   -----------------------------
;  | HEADER | DATA               |
;   -----------------------------
;  | 0x1600 |   size - 0x1600    |
;
;
; HEADER
;
;  -------------------------------------------------------------------
; | [magic]  [name] [clock] [?][?] [path + ?] [date]  [? ]  [sensor]  |
;  -------------------------------------------------------------------
; |   2+2  |   20  |   8   |16 |16|    64    |  32  | 128 |  42 * 128 |
; |                       0x100                           |           |
; |                             0x1600                                |
;
;
; max 128 sensors per file
;
; DATA
;
;  -------------------------------------------------
; | [date] [date] [? ] [     value     ] [ padding ] ...
;  -------------------------------------------------
; |   4   |  4   | 12|  8 * [sensor]    |   8*X
;
;									8  *  5  (3 +  2) .....
;                                   8  * 20 (11 +  9) ..... 10 - 20 senzoru...
;                                   8  * 30 (19 + 11) ..... 10 - 20 senzoru...
;                                   8  * 40 (25 + 15) ..... 20 - 40 senzoru...
;                                   8  * 50 (41 + 9)  ..... 40 - 50 senzoru ..
;                                         ????
;
; Slot time [clock] (15 min)
; 2967 data slots per file =>  2967 / 4 / 24 = 30 dni !
;

#NoTrayIcon

#include<Date.au3>
#include<File.au3>

;---------------------------------

;$epoch = int(ByteRead($ds,146,4))
;MsgBox(-1,"conv",_DateAdd('s',$epoch, "1970/01/01 00:00:00"))
;MsgBox(-1,"name",ByteStripString(ByteRead($ds,2,12)))

;return binary offset from file
func ByteRead($file,$offset,$count)
	$bin_file = FileOpen($file, 16); binary..
	FileSetPos($bin_file,$offset,0)
	return FileRead($bin_file,$count)
	FileClose($bin_file)
EndFunc

;retrun zero stripped string from binary
func ByteStripString($bstring)
	local $bstrip
	for $i=1 to BinaryLen($bstring)
		if BinaryMid($bstring,$i,1) <> '0x00' then $bstrip&=chr(BinaryMid($bstring,$i,1))
	next
	return $bstrip
EndFunc

;magic
;if ByteRead($ds,0,2) == '0x0301' then MsgBox(-1,"magic", "magic pass..")

;get SID array
func GetSid($file)
	local $list, $skip=0, $sid
	for $i=0 to 127
		$sid = ByteRead($file,0x100 + $skip,8)
		if BinaryMid($sid,1,1) <> '0x00' then
				$list&= '|' & ByteStripString($sid) & ';' & ByteStripString(ByteRead($file,0x100 + $skip + 8, 32))
		endif
		$skip+=42
	next
	return StringSplit(StringTrimLeft($list, 1),'|', 2); array, no count..
;	return $list; list, no count..
EndFunc

;get data buffer padding..
func GetDSPadding($file,$sid)
	local $byte='0x00', $pad=0, $buff
	while 1
		$buff = ByteRead($file,0x1600 + 20 + 8*$sid + $pad, 1)
		$byte = BinaryMid($buff,1,1)
		if $byte <> '0x00' then return $pad
		$pad+=1
	WEnd
EndFunc

;get data 2D array for 24 hour period
func GetDSData($file,$sid)
	local $data[24][$sid], $pad, $slot
	$pad = GetDSPadding($file,$sid)
	$slot = 20 + 8 * $sid + $pad
	for $i=0 to 23
		for $j=0 to $sid*8 step 8
			$data[$i][$j]=int(ByteRead($file,$1600 + $i * $slot + 20 + $j, 8))
		next
	next
	return $data
endfunc

;---------------------------------

$dslist = _FileListToArray(@ScriptDir & '\ds', "*.DS")
$ds = @ScriptDir & '\ds\' & 'AVZT01.DS'

if ubound($dslist) < 2 then
	MsgBox(-1,"no DS", "No DS!")
else
	for $i=1 to ubound($dslist) - 1
		$size=FileGetSize(@ScriptDir & '\ds\' & $dslist[$i])
		$s_count=UBound(GetSid(@ScriptDir & '\ds\' & $dslist[$i]))
		$padding=GetDSPadding(@ScriptDir & '\ds\' & $dslist[$i], $s_count)
;		MsgBox(-1,"val", "file: " & $dslist[$i] & ' sid ' & $s_count & ' pad ' & $padding)
		MsgBox(-1,"slot", "Size: " & $size & " Slot: " & ($size - 0x1600) / (20 + 8*$s_count + $padding) )
	Next
endif

;---------------------------------

