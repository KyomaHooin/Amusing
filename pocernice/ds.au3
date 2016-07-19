;
; HEADER
;
;  -------------------------------------------------------------------------
; | [ magic ]  [ name ] [clock] [?][?] [path + ?] [date]  [? ]   [sensor]   |
;  -------------------------------------------------------------------------
; |     4    |   20    |   8   |16 |16|    64    |  32  | 128 |   42*128    |
; |                       0x100[256]                          |             |
; |                                    0x1600                               |

#NoTrayIcon

#include<Date.au3>
#include<File.au3>

$ds = @ScriptDir & '\ds\' & 'AVZT01.DS'

;func read byte offset from file and return binary string
func ByteRead($file,$offset,$count)
	$bin_file = FileOpen($file, 16); binary..
	FileSetPos($bin_file,$offset,0)
	return FileRead($bin_file,$count)
	FileClose($bin_file)
EndFunc

func ByteStrip($bstring)
	local $bstrip
	for $i=1 to BinaryLen($bstring)
		if BinaryMid($bstring,$i,1) <> '0x00' then $bstrip&=chr(BinaryMid($bstring,$i,1))
	next
	return $bstrip
EndFunc

$epoch = int(ByteRead($ds,146,4))
$epoch2 = int(ByteRead($ds,154,4))

MsgBox(-1,"conv",_DateAdd('s',$epoch, "1970/01/01 00:00:00"))
MsgBox(-1,"conv",_DateAdd('s',$epoch2, "1970/01/01 00:00:00"))

MsgBox(-1,"name",ByteStrip(ByteRead($ds,2,12)))

if ByteRead($ds,0,4) == '0x03010000' then MsgBox(-1,"magic", "magic pass..")





