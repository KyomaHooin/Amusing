;
; Sauter NovaPro 374c propretary binary HDB database parser by Richard Bruna
;

#include<Memory.au3>
#include<File.au3>

;---------------------------------

;return binary offset
func ByteRead($file,$offset,$count)
	$bin_file = FileOpen($file, 16); binary..
	if @error then return
	FileSetPos($bin_file,$offset,0)
	return FileRead($bin_file,$count)
	FileClose($bin_file)
EndFunc

;get sensors
func GetDSSidArray($file)
	local $list, $skip=0, $sid
	for $i=0 to 127*42 step 42
		$sid = ByteRead($file,0x100 + $i,8)
		if $sid = '' then return
		if BinaryMid($sid,1,1) <> '0x00' then
				$list&= '|' & BinaryToString($sid)
		else
			return StringSplit(StringTrimLeft($list, 1),'|', 2); array, no count..
		endif
	next
EndFunc

;get data slot padding
func GetDSPadding($file,$sid)
	local  $buff, $pad=0
	while 1
		$buff = ByteRead($file,0x1600 + 20 + 8*$sid + $pad, 1)
		if $buff = '' then return
		if $buff <> '0x00' then return $pad
		$pad+=1
	WEnd
EndFunc

;get sensors data for 24 hour period
func GetDSData($file,$sid)
	local $data[24][$sid], $pad, $slot
	$pad = GetDSPadding($file,$sid)
	if $pad = '' then return
	$slot = 20 + 8*$sid + $pad
	for $i=0 to 23
		for $j=0 to $sid - 1
			$data[$i][$j]=BinToFloat(ByteRead($file,0x1600 + $i * $slot + 20 + $j*8, 8))
		next
	next
	return $data
endfunc

;convert binary 8-byte to float value
func BinToFloat($bin)
	$binary_float=DllStructCreate("byte byte[8]")
	$float=DllStructCreate("double")
	DllStructSetData($binary_float,1, $bin)
	_MemMoveMemory($binary_float,$float,8)
	return DllStructGetData($float,1)
EndFunc

;return zero stripped string from binary
func ByteStripString($bstring)
	local $bstrip
	for $i=1 to BinaryLen($bstring)
		if BinaryMid($bstring,$i,1) <> '0x00' then $bstrip&=chr(BinaryMid($bstring,$i,1))
	next
	return $bstrip
EndFunc

;get SID count
func GetSidCount($file)
	local $byte, $sid=0
	for $i= 0 to 127 * 42 step 42
		$byte = ByteRead($file, 0x100 + $i, 1)
		if $byte = '' then return
		if $byte == '0x00' then return $sid
		$sid+=1
	next
endfunc

;get sensors and descriptions
func GetSid($file)
	local $list, $skip=0, $sid
	for $i=0 to 127
		$sid = ByteRead($file,0x100 + $skip,8)
		if $sid = '' then return
		if BinaryMid($sid,1,1) <> '0x00' then
				$list&= '|' & ByteStripString($sid) & ';' & ByteStripString(ByteRead($file,0x100 + $skip + 8, 32))
		endif
		$skip+=42
	next
	return StringSplit(StringTrimLeft($list, 1),'|', 2); array, no count..
EndFunc

;---------------------------------

