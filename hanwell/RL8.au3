;
;Hanwell RadioLog 8 proprietary binary RL8 data file parser by Richard Bruna
;
; _GetRLSid ............... Sensor ID from data file buffer.
; _GetRLClean ............. Last data ID and index.
;
;  Internal:
;
; _ByteRead ............... Read single byte from file buffer by given byte offset.
; _BinToFloat ............. Convert 4 bytes to float number.
;
;---------------------------------

#include<Memory.au3>
#include<File.au3>

;return serial number
func _GetRLSid($buff)
	return BinaryToString(_ByteRead($buff, 0x43, 10))
EndFunc

;return last data ID + index
func _GetRLClean($buff)
	return _ByteRead($buff, 0x1cd, 8)
EndFunc

;return last data pack
func _GetRLdata($buff)
	local $data[2]
	$index = _GetRLClean($buff)
	for $i=0 to (FileGetSize($buff) - 0x380) / 14 step 14
		if $index =  _ByteRead($buff, 0x380 + $i + 5, 8) then
			return data = [ _BinToFloat(_ByteRead($buff, 0x380 + $i + 1, 4), _
					_BinToFloat(_ByteRead($buff, 0x380 + $i + 15, 4)]
		endif
	next
endFunc

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
	DllStructSetData($binary_float,1,$bin)
	_MemMoveMemory($binary_float,$float,4)
	return DllStructGetData($float,1)
EndFunc

;---------------------------------

