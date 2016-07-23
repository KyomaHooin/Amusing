;
; Sauter NovaPro 374c propretary binary HDB database DS file parser by Richard Bruna
;
; _GetDSSidArray .......... Sensor ID array from file.
; _GetDSPadding ........... Number of data slot zero-padding bytes.
; _GetDSLastOffset ........ Last data slot offset.
; _GetDSData .............. 6 Hour converted data array.
; _GetDSSidCount .......... Sensor count in file.
; _GetDSSidAll ............ Sensor ID and description array from file.
; _GetDSDateAll ........... Return all dates for all data-slots.
;
;  Internal:
;
; _ByteRead ............... Read single byte from file by given byte offset.
; _ByteStripString ........ Zero byte stripped string.
; _BinToFloat ............. Convert 8 bytes to float number.
; _BinToDate .............. Convert 4 bytes to datetime.
;

#include<Memory.au3>
#include<File.au3>

;---------------------------------

;get sensors
func _GetDSSidArray($file)
	local $list, $sid
	for $i=0 to 127*42 step 42
		$sid = _ByteRead($file,0x100 + $i,8)
		if $sid = '' then return
		if BinaryMid($sid,1,1) == '0x00' then exitloop
		$list&= '|' & BinaryToString($sid)
	next
	return StringSplit(StringTrimLeft($list, 1),'|', 2); array, no count..
EndFunc

;get data slot padding
func _GetDSPadding($file,$sid)
	local  $buff, $pad
	while 1
		$buff = _ByteRead($file,0x1600 + 20 + 8*$sid + $pad, 1)
		if $buff = '' then return
		if $buff <> '0x00' then return $pad
		$pad+=1
	WEnd
EndFunc

;get last record offset
func _GetDSLastOffset($file,$sid,$pad)
	local $slot, $last, $date
	$slot = 20 + 8*$sid + $pad
	$last=_ByteRead($file, 0x9A, 4); last header date
	for $i=0 to 2975
		$date=_ByteRead($file,0x1600 + $i * $slot, 4)
		if $last = $date then return $i
	next
endfunc

;get sensors data for last 6 hour period = 4 * 6 (* 15min)
func _GetDSData($file,$sid)
	local $data[24][$sid], $pad, $slot, $buff
	$pad = _GetDSPadding($file,$sid)
	if $pad = '' then return
	$offset = _GetDSLastOffset($file,$sid,$pad)
	$slot = 20 + 8*$sid + $pad
	for $i=0 to 23
		for $j=0 to $sid - 1
			$data[$i][$j]=_BinToFloat(_ByteRead($file,0x1600 + ($offset - $buff ) * $slot + 20 + $j*8, 8)); FIFO
		next
		if $offset - $buff = 0 then; buffer overflow
				$offset = 2975
				$buff = 0
		else
			$buff+=1
		endif
	next
	return $data
endfunc

;get SID count
func _GetDSSidCount($file)
	local $byte, $sid
	for $i= 0 to 127 * 42 step 42
		$byte = _ByteRead($file, 0x100 + $i, 1)
		if $byte = '' then return
		if $byte == '0x00' then exitloop
		$sid+=1
	next
	return $sid
endfunc

;get sensors and descriptions
func _GetDSSidAll($file)
	local $list, $sid
	for $i= 0 to 127 * 42 step 42
		$sid = _ByteRead($file,0x100 + $i,8)
		if $sid = '' then return
		if BinaryMid($sid,1,1) == '0x00' then exitloop
		$list&= '|' & BinaryToString($sid) & ';' & _ByteStripString(_ByteRead($file,0x100 + $i + 8, 32))
	next
	return StringSplit(StringTrimLeft($list, 1),'|', 2); array, no count..
EndFunc

;get all dates for all data-slots
func _GetDSDateAll($file,$sid)
	local $data[2976], $pad, $slot
	$pad = _GetDSPadding($file,$sid)
	if $pad = '' then return
	$slot = 20 + 8*$sid + $pad
	for $i=0 to 2975
		$data[$i]=_BinToDate(_ByteRead($file,0x1600 + $i * $slot, 4))
	next
	return $data
endfunc

;return binary offset
func _ByteRead($file,$offset,$count)
	$bin_file = FileOpen($file, 16); binary..
	if @error then return
	FileSetPos($bin_file,$offset,0)
	return FileRead($bin_file,$count)
	FileClose($bin_file)
EndFunc

;return zero stripped string from binary
func _ByteStripString($bstring)
	local $bstrip
	for $i=1 to BinaryLen($bstring)
		if BinaryMid($bstring,$i,1) <> '0x00' then $bstrip&=chr(BinaryMid($bstring,$i,1))
	next
	return $bstrip
EndFunc

;convert binary 8-byte to float value
func _BinToFloat($bin)
	$binary_float=DllStructCreate("byte byte[8]")
	$float=DllStructCreate("double")
	DllStructSetData($binary_float,1, $bin)
	_MemMoveMemory($binary_float,$float,8)
	return DllStructGetData($float,1)
EndFunc

;convert binary 4-byte to datetime
func _BinToDate($bin)
	local $date = DllCall("msvcrt.dll", "str:cdecl", "ctime", "int*", $bin)
	return StringRegExpReplace($date[0],@LF &"$","$1")
EndFunc

;---------------------------------

