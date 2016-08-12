;
;  DL-121TH & S3120 parser by Richard Bruna
;
; _GetDLPrumstav ............. Convert DS100 datalogger TSV export to CSV buffer
; _GetDLS3120 ................ Convert datalogger DBF export to CSV buffer
; _GetDLVolcraft ............. Convert DL121-TH datalogger manual XLS to CSV buffer
; _GetDLMerlin ............... Convert DL121-TH datalogger manual XSLX to CSV buffer
;

#include <File.au3>
#include <Xbase.au3>
#include <Excel.au3>

;-------------------------------

;Prumstav DS100 export to CSV data
func _GetDLPrumstav($file)
	local $raw, $data
	_FileReadToArray($file,$raw,0); no count
	if @error then return SetError(1,0, "Failed to read CSV: " & $file)
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

;S3120 datalogger export to CSV data
func _GetDLS3120($file)
	local $raw, $data
	_Xbase_ReadToArray($file, $raw)
	if @error then return SetError(1,0, "Failed to read DBF: " & $file)
	for $i=0 to UBound($raw) - 1
		$timestamp = StringRegExpReplace($raw[$i][0],"(\d+)-(\d+)-(\d+)","$3$1$2") & 'T' & StringRegExpReplace($raw[$i][1],"(\d+):(\d+):(\d+)","$1$2$3")
		$data &= 'sensor' & ';temperature;' & $raw[$i][3] & ';' & $timestamp & @CRLF
		$data &= 'sensor' & ';humidity;' & $raw[$i][4] & ';' & $timestamp & @CRLF
	next
	return $data
EndFunc

;Volcraft DL121-TH manual data to CSV data
func _GetDLVolcraft($file)
	local $raw, $data
	$excel = _Excel_Open(); excel instance
	if @error then return SetError(1,0, "Failed to create XLS object: " & $file)
	$book = _Excel_BookOpen($excel,$file, True, False); invisible read only..
	if @error then return SetError(1,0, "Failed to open XLS workbook for " & $file)
	for $i=5 to $excel.ActiveSheet.UsedRange.Rows.Count; 5+ line
		$raw = _Excel_RangeRead($book,Default,"A" & $i & ":C" & $i); Ax:Cx
		if not $raw[0] then exitloop; end of data
		$timestamp = StringRegExpReplace($raw[0],"(\d\d)-(\d\d)-(\d{4}) (\d\d):(\d\d):(\d\d)","$3$$2$1T$4$5$6")
		$data &= 'sensor' & ';temperature;' & $raw[1] & ';' & $timestamp & @CRLF
		$data &= 'sensor' & ';humidity;' & $raw[2] & ';' & $timestamp & @CRLF
	next
	return $data
EndFunc

;Merlin DS121-TH manual data to CSV data
func _GetDLMerlin($file)
	local $raw, $data
	$excel = _Excel_Open(); excel instance
	if @error then return SetError(1,0, "Failed to create XLS object: " & $file)
	$book = _Excel_BookOpen($excel,$file, True, False); invisible read only..
	if @error then return SetError(1,0, "Failed to open XLS workbook for " & $file)
	for $i=6 to $excel.ActiveSheet.UsedRange.Rows.Count; 6+ line
		$raw = _Excel_RangeRead($book,Default,"A" & $i & ":C" & $i); Ax:Cx
		if not $raw[0] then exitloop; end of data
		$timestamp = StringRegExpReplace($raw[0],"(\d\d)/(\d\d)/(\d{4})","$3$$2$1T120000")
		$data &= 'sensor' & ';temperature;' & $raw[1] & ';' & $timestamp & @CRLF
		$data &= 'sensor' & ';humidity;' & $raw[2] & ';' & $timestamp & @CRLF
	next
	return $data
EndFunc

;-------------------------------