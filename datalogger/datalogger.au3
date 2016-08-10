;
;  DL-121TH & S3120 parser by Richard Bruna
;
; _Get_DL_Prumstav ............. Convert CSV to CSV
; _Get_DL_Volcraft ............. Convert XLS to CSV
; _Get_DL_Merlin ............... Convert XLS to CSV
; _Get_DL_S3120 ................ Convert DBF to CSV
;

#include <File.au3>
#include <Xbase.au3>
#include <Excel.au3>

;-------------------------------

func _Get_DL_Prumstav($file)
	return SetError(1,0, "Parsing " & $file & " failed.")
EndFunc

func _Get_DL_Volcraft($file)
	return SetError(1,0, "Parsing " & $file & " failed.")
EndFunc

func _Get_DL_Merlin($file)
	return SetError(1,0, "Parsing " & $file & " failed.")
EndFunc

func _Get_DL_S3120($file)
	return SetError(1,0, "Parsing " & $file & " failed.")
EndFunc