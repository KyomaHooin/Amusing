;
;Hanwell RadioLog 8 proprietary binary RL8 data file parser by Richard Bruna
;
; _GetRLSid ............... Sensor ID from data file buffer.
; _GetRLClean ............. "Clean" slot ID and index.
; _GetRLData .............. Last data array.
;
;  Internal:
;
; _BinToFloat ............. Convert 4 bytes to float number.
;
;---------------------------------

#include<Memory.au3>

;return serial number
func _GetRLSid($buff)
	return BinaryToString(BinaryMid($buff, 0x44, 10))
EndFunc

;return last data ID + index
func _GetRLClean($buff)
	return BinaryMid($buff, 0x1ce, 8)
EndFunc

;return last data pack
func _GetRLdata($buff)
	local $data[2]
	$index = _GetRLClean($buff)
	if $index = '' then return
	for $i= BinaryLen($buff) - 14 to 0x380 step -14
		if $index = BinaryMid($buff, $i + 6, 8) then
			$data[0] = _BinToFloat(BinaryMid($buff, $i -12, 4))
			$data[1] = _BinToFloat(BinaryMid($buff, $i + 2, 4))
			return $data
		endif
	next
endFunc

;convert binary 4-byte to float value
func _BinToFloat($bin)
	$binary_float=DllStructCreate("byte byte[4]")
	$float=DllStructCreate("float")
	DllStructSetData($binary_float,1,$bin)
	_MemMoveMemory($binary_float,$float,4)
	return DllStructGetData($float,1)
EndFunc

;---------------------------------
