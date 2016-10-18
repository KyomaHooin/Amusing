;
;Hanwell RadioLog 8 proprietary binary RL8 data file parser by Richard Bruna
;
; _GetRLSid ....... Sensor ID from data file.
;
;  Internal:
;
; _ByteRead ............... Read single byte from file by given byte offset.
; _BinToFloat ............. Convert 4 bytes to float number.
;
;---------------------------------

#include<Memory.au3>

;return serial number
func _GetRLSid($file)
	return BinaryToString(_ByteRead($file, 0x43, 10))
EndFunc

;return binary offset
func _ByteRead($file,$offset,$count)
	local $byte
	$bin_file = FileOpen($file, 16); binary..
	if @error then return
	FileSetPos($bin_file,$offset,0)
	$byte = FileRead($bin_file,$count)
	FileClose($bin_file)
	return $byte
EndFunc

;convert binary 4-byte to float value
func _BinToFloat($bin)
	$binary_float=DllStructCreate("byte byte[4]")
	$float=DllStructCreate("float")
	DllStructSetData($binary_float,1, $bin)
	_MemMoveMemory($binary_float,$float,4)
	return DllStructGetData($float,1)
EndFunc

;---------------------------------

