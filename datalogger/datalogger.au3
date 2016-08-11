;
;  DL-121TH & S3120 parser by Richard Bruna
;
; _GetDLPrumstav ............. Convert CSV to CSV
; _GetDLS3120 ................ Convert DBF to CSV
; _GetDLVolcraft ............. Convert XLS to CSV
; _GetDLMerlin ............... Convert XLSX to CSV
;

#include <File.au3>
#include <Xbase.au3>
#include <Excel.au3>

;-------------------------------

func _GetDLPrumstav($file)
	local $raw, $data
	_FileReadToArray($file,$raw,0); no count
	if @error then return SetError(1,0, "Failed to read " & $file)
	for $i=1 to UBound($raw) - 1; skip first line..
		$line = StringSplit($raw[$i],",",2); no count..
		$timestamp = StringRegExpReplace($line[1],"(\d+)\.(\d+).(\d+) (\d+):(\d+):(\d+)"," $3 $2 $1 $4 $5 $6 ")
		$timestamp = StringRegExpReplace($timestamp," (\d) "," 0$1 "); fix lead zero..
		$timestamp = StringRegExpReplace($timestamp," (\d+) (\d+) (\d+) (\d+) (\d+) (\d+) ","$3$2$1T$4$5$6"); to ISO..
		if UBound($line) = 5 then; missused CSV delimeter..
			$data &= 'serial' & ';temperature;' & $line[2] & '.' & $line[3] & ';' & $timestamp & @CRLF
			$data &= 'serial' & ';humidity;' & $line[4] & ';' & $timestamp & @CRLF
		else
			$data &= 'serial' & ';temperature;' & $line[2] & ';' & $timestamp & @CRLF
			$data &= 'serial' & ';humidity;' & $line[3] & ';' & $timestamp & @CRLF
		endif
	next
	return $data
EndFunc

func _GetDLS3120($file)
	local $raw, $data
	_Xbase_ReadToArray($file, $raw)
	if @error then return SetError(1,0, "Failed to read " & $file)
	for $i=0 to UBound($raw) - 1
		$timestamp = StringRegExpReplace($raw[$i][0],"(\d+)-(\d+)-(\d+)","$3$1$2") & 'T' & StringRegExpReplace($raw[$i][1],"(\d+):(\d+):(\d+)","$1$2$3")
		$data &= 'sensor' & ';temperature;' & $raw[$i][3] & ';' & $timestamp & @CRLF
		$data &= 'sensor' & ';humidity;' & $raw[$i][4] & ';' & $timestamp & @CRLF
	next
	return $data
EndFunc

func _GetDLVolcraft($file)
;	return SetError(1,0, "Parsing " & $file & " failed.")
EndFunc

func _GetDLMerlin($file)
;	return SetError(1,0, "Parsing " & $file & " failed.")
EndFunc
