;============================================================================================
; Title ..........: Xbase
; AutoIt Version..: 3.3.12
; Description ....: transfer data between dbf files and AutoIt arrays
; Author..........: A.R.T. Jonkers (RTFC)
; Release.........: 0.6
; Latest revision.: 18 Aug 2014
; License.........: free for personal use; free distribution allowed provided
;							the author is credited; all other rights reserved.
; Tested on.......: W7Pro/64
; Dependencies....: none
; Forum Link......: not yet assigned
;============================================================================================
; Summary (Wotsit?)
;
;	_Xbase_ReadToArray($filename, ByRef $array)		copy data from dbf file into AutoIt array
;	_Xbase_WriteFromArray($filename, ByRef $array)	copy data from AutoIt array to existing or new dbf file
;
; No guarantees, no warranties, no liability for damages, no refunds. Use at your own risk.
;
;============================================================================================
;	Application (WhyBother?)
;
; * Control formatting with the following switches:
;
;	$fieldNamesInTopRow	(R/W)	stored in array col0/copied from col0 to field descriptors in dbf header
;
;	$scientificToNumeric	(R)	store values using scientific notation in array
;	$NumericToScientific	(W)	ditto in dbf
;	$NumericToDouble		(W)	store as binary doubles (no loss of significant digits)
;
;	$formatDate				(R)	use current date formatting convention (see UDF _Xbase_FormatDate)
;	$unformatDate			(W)	strip current date formatting convention before storing
;
;	$Write1DAs2D			(R)	recover 2D matrix from ColMajor sequentially stored 1D dbf (see Remarks)
;	$write2DAs1D			(W)	store 2D matrix whose number of cols exceeds Xbase maximum as 1D
;
;============================================================================================
;	Remarks (Just run it!)
;
; * BEWARE: maximum size of an AutoIt array: 2^24 = 16 MB = 16,777,216 elements;
;		an Xbase dbf file can be considerably larger than that.
;
; * Depending on the Xbase signature (version number/file subtype), dbf files
;		have specific limits on the number of fields (array columns).
; 		If a 2D array exceeds this limit, it will be stored as a 1D ColMajor
;		sequence (this can also be forced).
;	Reloading using either _Xbase_ReadToArray or the MatrixFileConverter utility
;		will recover the 2D structure (minus the original field names).
;
; * dBase III(+)/IV recognise only the following field types:
;		C(haracter), N(umeric), F(loat), D(ate), L(ogical), M(emo).
;	If other field types are present (e.g., (B)inary), dBase will throw
;		an "invalid header" error.
;
; * Xbase fields array structure:
;		$Xbase_fields[0][o]="Field_Name"
;		$Xbase_fields[0][1]="Field_Type"
;		$Xbase_fields[0][2]="Field_Length"
;		$Xbase_fields[0][3]="Field_Decimals"
;		$Xbase_fields[0][4]="Indexed"
;		$Xbase_fields[0][5]="Scientific Notation"	; internal flag
;
; * if $Write1DAs2D>0, _Xbase_ReadToArray() will interpret the integer value
;		as the number of columns to rebuild a ColMajor-stored 1D sequence as 2D.
;
;============================================================================================
#include <Array.au3>
#include <Math.au3>
#include <String.au3>
#include <WinAPI.au3>
#include <WinAPIEx.au3>

#include-once
#NoTrayIcon

#Region Globals

Global $showWarnings=False ;True
Global $showProgress=False ;True
Global $diagnostics=False ;True

Global Const $Xbase_signature_default=0x03		; Xbase signature (see _Xbase_SignatureString for alternatives)
Global Const $Xbase_maxXbaseRecords=(2^32)-1		; 4,294,967,296	(4 GB -1)
Global Const $Xbase_maxAutoItArraySize=2^24		; 16,777,216		(16 MB)
Global Const $Xbase_maxcharacter_length=254		; Xbase limitation
Global Const $Xbase_2Dmarker=202331218				; $EIGEN_MATRIXFILEMARKER
Global Const $Xbase_fieldPadding=_StringRepeat(" ",$Xbase_maxcharacter_length)
Global Const $Xbase_maxnumeric_length=20			; Xbase limitation
Global Const $Xbase_maxdecimals=18					; at most equal to (field_length-2)
; Note: the sign and decimal point position are part of the integer part of a numeric field

Global $Xbase_maxRecordlength			; version-dependent
Global $Xbase_maxfields					; version-dependent
Global $dBase_III=False					; version-dependent
Global $dBase_IV=False					; version-dependent
Global $Xbase_decimals=6				; default: float
Global $Xbase_dateFormat="italian"	; see options in _Xbase_FormatDate()

Global $Xbase_signature
Global $Xbase_year
Global $Xbase_month
Global $Xbase_day
Global $Xbase_number_of_fields
Global $Xbase_number_of_records
Global $Xbase_header_length
Global $Xbase_record_length
Global $Xbase_free_record_thread
Global $Xbase_multi_user1a
Global $Xbase_multi_user1b
Global $Xbase_incomplete_transaction
Global $Xbase_encryption_flag
Global $Xbase_MDX_flag
Global $Xbase_language_driver
Global $Xbase_fields

_PrepFieldsList()

; all Little_Endian
Global Const $XbaseHeaderStruct= "align 1;" & _
	"byte signature;" & _				; see separate translation function
	"byte year;" & _						; value +1900
	"byte month;" & _						;
	"byte day;" & _						;
	"dword number_of_records;" & _	; max 4 GB
	"word header_length;" & _			; includes field descriptors + final 0x0D marker
	"word record_length;" & _			; sum of all field_lengths + 1 (one extra for deletion flag)
	"word reserved1;" & _				; internal (filled with 0x0000 in dBase IV)
	"boolean incomplete_transaction;" & _	; internal (dbase IV)
	"boolean encryption_flag;" & _	; internal
	"dword free_record_thread;" & _	; LAN only; used here for Matrix marker if converted between 2D and 1D
	"dword multi_user1a;" & _			; dbase III+; used here to save 2D dims if stored as 1D
	"dword multi_user1b;" & _			; dbase III+
	"boolean MDX_flag;" & _				; dbase IV multi-index file
	"byte language_driver;" & _		; internal
	"word reserved2"						; internal

Global Const $XbaseFieldDescriptorStruct= ""& _	; alignment intentionally omitted here
	"char field_name[11];" & _			; chr(0)-terminated ASCII string (10 chars, space-padded)
	"char field_type;" & _				; 1-letter ASCII code; see conversion function
	"dword field_data_address;" & _	; dBase: address in memory (4 bytes); FoxPro: offset of field from beginning of record (2 bytes)
	"byte field_length;" & _			; total width (incl decimals + decimal point)
	"byte decimal_count;" & _			; non-integer part of width (if numeric)
	"word multi_user2;" & _				; internal
	"byte work_area;" & _				; internal
	"word multi_user3;" & _				; internal
	"boolean setfields_flag;" & _		; for writing out selected fields only
	"byte reserved3[7];" & _			; internal
	"boolean field_indexed_flag"		; T: key exists for this field in .mdx

Global $XbaseHeader=DllStructCreate($XbaseHeaderStruct)
If @error Then _Xbase_Fatal("Unable to create XbaseHeader buffer")
Global Const $XbaseHeaderPointer=DllStructGetPtr($XbaseHeader)
Global Const $XbaseHeaderSize=DllStructGetSize($XbaseHeader)

Global $XbaseFieldDescriptor=DllStructCreate("align 1;" & $XbaseFieldDescriptorStruct)
If @error Then _Xbase_Fatal("Unable to create XbaseFieldDescriptor buffer")
Global Const $XbaseFieldDescriptorPointer=DllStructGetPtr($XbaseFieldDescriptor)
Global Const $XbaseFieldDescriptorSize=DllStructGetSize($XbaseFieldDescriptor)

Global $XbaseEOFmarker=DllStructCreate("align 1;byte")
If @error Then _Xbase_Fatal("Unable to create $XbaseEOFmarker buffer")
DllStructSetData($XbaseEOFmarker,1,0x1a)

#EndRegion Globals

#cs
;$inputfile="test.dbf"
$inputfile="dummy.dbf"
Local $test
$scientificToNumeric=True
$formatDate=True
$fieldNamesInTopRow=True	; determines array row zero's contents (False: 1st data row)
_Xbase_ReadToArray($inputfile, $test, $fieldNamesInTopRow, $scientificToNumeric, $formatDate)

_ArrayDisplay($xbase_fields,"fields")
;$Xbase_fields=0
_ArrayDisplay($test,"data")

$NumericToScientific=True
$NumericToDouble=False
$unformatDate=True
$write2DAs1D=False ; True
$outputfile="test.dbf"
_Xbase_WriteFromArray($outputfile,$test,$fieldNamesInTopRow, $NumericToScientific, $NumericToDouble, $unformatDate, $write2DAs1D)

Local $test2
$Write1DAs2D=-1
_Xbase_ReadToArray($outputfile,$test2, $fieldNamesInTopRow, $scientificToNumeric, $formatDate, $Write1DAs2D)
_ArrayDisplay($test2,"test2")

#ce

#Region main UDFs

Func _Xbase_ReadHeader($filename, $checkFileSize=True)

	$hFile = _WinAPI_CreateFile($filename,2,2)	; file exists, open for reading
	If $hFile=0 Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Unable to open target file "&$filename&" for reading")
		Return SetError(-1,0,False)
	EndIf

	; read Xbase header
	$bytesread=0
	_WinAPI_SetFilePointerEx($hFile,0)
	$readokay=_WinAPI_ReadFile($hFile,$XbaseHeaderPointer,$XbaseHeaderSize,$bytesread)
	If $readokay=False Or $bytesread<>$XbaseHeaderSize Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Error in file header read")
		_WinAPI_CloseHandle($hFile)
		Return SetError(-1,0,False)
	EndIf

	$Xbase_signature=DllStructGetData($XbaseHeader, "signature")
	$Xbase_year=Number(DllStructGetData($XbaseHeader, "year"))+1900
	$Xbase_month=Number(DllStructGetData($XbaseHeader, "month"))
	$Xbase_day=Number(DllStructGetData($XbaseHeader, "day"))
	$Xbase_number_of_records=Number(DllStructGetData($XbaseHeader, "number_of_records"))
	$Xbase_header_length=Number(DllStructGetData($XbaseHeader, "header_length"))
	$Xbase_record_length=Number(DllStructGetData($XbaseHeader, "record_length"))
	$Xbase_free_record_thread=Number(DllStructGetData($XbaseHeader, "free_record_thread"))
	$Xbase_multi_user1a=Number(DllStructGetData($XbaseHeader, "multi_user1a"))
	$Xbase_multi_user1b=Number(DllStructGetData($XbaseHeader, "multi_user1b"))
	$Xbase_incomplete_transaction=(Number(DllStructGetData($XbaseHeader, "incomplete_transaction"))=1)
	$Xbase_encryption_flag=(Number(DllStructGetData($XbaseHeader, "encryption_flag"))=1)
	$Xbase_MDX_flag=(Number(DllStructGetData($XbaseHeader, "MDX_flag"))=1)
	$Xbase_language_driver=DllStructGetData($XbaseHeader, "language_driver")

	If $Xbase_number_of_records<0 Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Number of records should be >=0")
		Return SetError(-1,0,False)
	EndIf

	_Xbase_SignatureString($Xbase_signature)	; update dBase_III/IV flags
	If ($dBase_III Or $dBase_IV) Then
		$Xbase_maxRecordlength=4000
		$Xbase_maxfields=127		; constrained by max. header size (4 KB)
	Else
		$Xbase_maxRecordlength=32*1024
		$Xbase_maxfields=2047	; constrained by max. header size (64 KB)
	EndIf

	$Xbase_number_of_fields=Int($Xbase_header_length-$XbaseHeaderSize-1)/$XbaseFieldDescriptorSize
	If $Xbase_number_of_fields<1 Or $Xbase_number_of_fields>$Xbase_maxfields Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Invalid header in file "&$filename)
		Return SetError(-1,0,False)
	EndIf

	If $Xbase_record_length<1 Or $Xbase_record_length>$Xbase_maxRecordlength Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Record length (" & $Xbase_record_length & " bytes) is out of bounds")
		Return SetError(-1,0,False)
	EndIf

	$logical_filesize=($Xbase_header_length+($Xbase_number_of_records*$Xbase_record_length))+1
	$physical_filesize=FileGetSize($filename)
	If $checkFileSize=True And $Logical_filesize<>$physical_filesize Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Logical file size (" & $Logical_filesize & " bytes) does not match physcial file size (" & $physical_filesize & " bytes)")
		Return SetError(-1,0,False)
	EndIf

	; read fields descriptors
	$dim2=UBound($Xbase_fields,2)
	ReDim $Xbase_fields[$Xbase_number_of_fields+1][$dim2]

	For $fc=1 To $Xbase_number_of_fields
		$bytesread=0
		$readokay=_WinAPI_ReadFile($hFile,$XbaseFieldDescriptorPointer,$XbaseFieldDescriptorSize,$bytesread)
		If $readokay=False Or $bytesread<>$XbaseFieldDescriptorSize Then
			MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Error in field descriptor read: " & $fc)
			_WinAPI_CloseHandle($hFile)
			Return SetError(-1,0,False)
		EndIf
		$Xbase_fields[$fc][0]=DllStructGetData($XbaseFieldDescriptor, "field_name")
		$Xbase_fields[$fc][1]=DllStructGetData($XbaseFieldDescriptor, "field_type")
		$Xbase_fields[$fc][2]=Number(DllStructGetData($XbaseFieldDescriptor, "field_length"))
		$Xbase_fields[$fc][3]=Number(DllStructGetData($XbaseFieldDescriptor, "decimal_count"))
		$Xbase_fields[$fc][4]=(DllStructGetData($XbaseFieldDescriptor, "field_indexed_flag")=1)
		$Xbase_fields[$fc][5]=False	; internal scientific notation flag
	Next
	$Xbase_fields[0][0]=UBound($Xbase_fields)-1
	_WinAPI_CloseHandle($hFile)

	If $diagnostics=True Then
		MsgBox(0,"Xbase header test", "File: " & $filename & @CR & @CR & _
			"$Xbase_signature: " & $Xbase_signature & ": " & _Xbase_SignatureString($Xbase_signature) & @CR & _
			"$Xbase_year: " & $Xbase_year & @CR & _
			"$Xbase_month: " & $Xbase_month & @CR & _
			"$Xbase_day: " & $Xbase_day & @CR & _
			"$Xbase_number_of_records: " & $Xbase_number_of_records & @CR & _
			"$Xbase_header_length: " & $Xbase_header_length & @CR & _
			"$Xbase_record_length: " & $Xbase_record_length & @CR & _
			"$Xbase_incomplete_transaction: " & $Xbase_incomplete_transaction & @CR & _
			"$Xbase_encryption_flag: " & $Xbase_encryption_flag & @CR & _
			"$Xbase_MDX_flag: " & $Xbase_MDX_flag & @CR & _
			"$Xbase_language_driver: " & $Xbase_language_driver & ": " & _Xbase_LanguageDriverString($Xbase_language_driver) & @CR)

		_ArrayDisplay($Xbase_fields,"database fields")
	EndIf

EndFunc


Func _Xbase_ReadToArray($filename, ByRef $array, $fieldNamesInTopRow=False, $scientificToNumeric=True, $formatDate=True, $Write1DAs2D=-1)

	If Not FileExists($filename) Then Return SetError(-1,0,False)
	_Xbase_ReadHeader($filename)
	If @error Then Return SetError(-1,0,False)

	; do we store field names per col in top row?
	If $fieldNamesInTopRow=True Then
		Local $startrow=1
	Else
		Local $startrow=0
	EndIf

	If $Xbase_free_record_thread=$Xbase_2Dmarker Then $Write1DAs2D=$Xbase_multi_user1b

	Local $elements=$Xbase_number_of_records*$Xbase_number_of_fields
	If $elements>$Xbase_maxAutoItArraySize Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Number of database elements (fields x records = " & $elements & ") exceeds maximum AuotIt Array size (" & $Xbase_maxAutoItArraySize & ")")
		Return SetError(-1,0,False)
	EndIf

	If $Write1DAs2D<=0 Then
		Dim $array[$Xbase_number_of_records+$startrow][$Xbase_number_of_fields]
		$Xbase_2Dnumber_of_records=-1

	Else	; reshape array as 2D container with <$Write1DAs2D> columns
		If Mod($elements,$Write1DAs2D)<>0 Then
			MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Number of database elements (fields x records = " & $elements & ") is no exact multiple of the parsed number of columns (" & $Write1DAs2D & ")")
			Return SetError(-1,0,False)
		EndIf

		; Xbase_fields can be user-defined, providing it matches in the number of variables (target array columns)
		$dim2=UBound($Xbase_fields,2)
		If $dim2>1 And $dim2<>$Write1DAs2D Then
			MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Presupplied Xbase_fields number of columns ("&$dim2&") does not match parsed number of columns (" & $Write1DAs2D & ")")
			Return SetError(-1,0,False)
		EndIf

		$Xbase_2Dnumber_of_records=$elements/$Write1DAs2D
		Dim $array[$Xbase_2Dnumber_of_records+$startrow][$Write1DAs2D]
	EndIf

	If $fieldNamesInTopRow=True Then
		If $Write1DAs2D<1 Or $Xbase_number_of_fields>1 Then	; second condition to allow user to provide their own $Xbase_fields array
			For $cc=1 To $Xbase_number_of_fields
				$array[0][$cc-1]=$Xbase_fields[$cc][0]
			Next
		Else
			For $cc=1 To $Write1DAs2D
				$array[0][$cc-1]="Col" & $cc-1
			Next
		EndIf
	EndIf

	; create struct for records
	$XbaseRecordStruct= "align 1;char;"	; deletion flag (20h (space): valid; 2eh ("*"): deleted
	For $fc=1 To $Xbase_number_of_fields
		$XbaseRecordStruct&=_Xbase_DataTypeToStructType($Xbase_fields[$fc][1],$Xbase_fields[$fc][2]) & ";"
	Next
	$XbaseRecordStruct=StringTrimRight($XbaseRecordStruct,1)	; clip last semi-colon
	$XbaseRecord=DllStructCreate($XbaseRecordStruct)
	If @error Then _Xbase_Fatal("Unable to create XbaseRecord buffer, error: " & @error)
	$XbaseRecordPointer=DllStructGetPtr($XbaseRecord)
	$XbaseRecordSize=DllStructGetSize($XbaseRecord)

	If $XbaseRecordSize<>$Xbase_record_length Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Reconstructed record size (" & $XbaseRecordSize & " bytes) does not match record length as stated in header (" & $Xbase_record_length & " bytes)")
		Return SetError(-1,0,False)
	EndIf

	; open database file
	$hFile = _WinAPI_CreateFile($filename,2,2)	; file exists, open for reading
	If $hFile=0 Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Unable to open target file "&$filename&" for reading")
		Return SetError(-1,0,False)
	EndIf

	; read all recs, store as rows
	; assumption: if signature=0x30 (Visual FoxPro with DBC), $Xbase_header_length includes the extra 264 bytes for DBC (between terminator 0x0D and start of first record)
	_WinAPI_SetFilePointerEx($hFile,$Xbase_header_length)

	; reset reformatting for absent field types
	$scientificToNumeric	=($scientificToNumeric And _ArraySearch($Xbase_fields,True,1,0,0,0,1,5)>0)
	$formatDate				=($formatDate And _ArraySearch($Xbase_fields,"D",1.0,0,0,1,2)>0)

	Local $rowshift=0
	Local $colshift=0
	If $showprogress=True Then ProgressOn("Reading file","Processing Xbase file","Please wait...")

	For $rc=1 To $Xbase_number_of_records
		If $showprogress=True And Mod($rc,200)=0 Then ProgressSet(100*($rc-1)/$Xbase_number_of_records,"Reading record " & $rc & " of " & $Xbase_number_of_records & "...")
		$bytesread=0
		$readokay=_WinAPI_ReadFile($hFile,$XbaseRecordPointer,$XbaseRecordSize,$bytesread)
		If $readokay=False Or $bytesread<>$XbaseRecordSize Then
			MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Error while reading record " & $rc)
			_WinAPI_CloseHandle($hFile)
			Return SetError(-1,0,False)
		EndIf

		For $fc=1 To $Xbase_number_of_fields
			$currow=$startrow+$rc-1-$rowshift
			$curcol=$colshift+$fc-1
			$array[$currow][$curcol]=DllStructGetData($XbaseRecord,$fc+1)	; first entry = deletion flag (ignored here)
			If $Xbase_fields[$fc][1]="N" Then $array[$currow][$curcol]=Number(StringStripWS($array[$currow][$curcol],8))
			If $scientificToNumeric=True And $Xbase_fields[$fc][5]=True And _IsScientificNotation($array[$rc][$curcol]) Then $array[$currow][$curcol]=Execute($array[$currow][$curcol])	; execute evaluates scientific notation, returns value
			If $formatDate=True And $Xbase_fields[$fc][1]="D" Then $array[$currow][$curcol]=_Xbase_FormatDate($array[$currow][$curcol])
		Next

		; special case: ColMajor 1D storage of 2D array
		If $rc=($curcol+1)*$Xbase_2Dnumber_of_records Then
			$colshift+=1	; applies only if $Write1DAs2D = True
			$rowshift=$colshift*$Xbase_2Dnumber_of_records
		EndIf

	Next
	If $showprogress=True Then ProgressOff()
	_WinAPI_CloseHandle($hFile)

	Return True
EndFunc


Func _Xbase_WriteFromArray($filename, ByRef $array, $fieldNamesInTopRow=False, $NumericToScientific=False, $NumericToDouble=False, $unformatDate=True, $write2DAs1D=False)
; If a fully-filled $Xbase_fields array is not supplied, it will be created,
;	requiring a full array scan to determine field lengths, decimals, etc.

	; validity checks
	If Not IsArray($array) Then Return SetError(-1,0,False)
	If FileExists($filename) And $showWarnings=True Then
		If MsgBox($MB_SYSTEMMODAL+$MB_ICONWARNING+$MB_OKCANCEL,"Warning","Target file " & @CR & $filename & @CR & "already exists. Overwrite?")=2 Then Return SetError(-1,0,False)
	EndIf
	_Xbase_SignatureString($Xbase_signature)
	If @error Then $Xbase_signature=0x03	; Xbase file

	; do we have field names per col?
	If $fieldNamesInTopRow=True Then
		Local $startrow=1
	Else
		Local $startrow=0
	EndIf

	; get array specs
	$arraydim0=UBound($array,0)				; dims
	$arraydim1=UBound($array,1)-$startrow	; data rows	(dbf records)
	$arraydim2=UBound($array,2)				; cols		(dbf fields)

	; array empty?
	If $arraydim0*$arraydim1<=0 Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "parsed array is empty")
		Return SetError(-1,0,False)
	EndIf

	; determine how to store spatially (multicol/single col)
	If $arraydim2=0 Then
		$Xbase_number_of_records=$arraydim1
		$write2DAs1D=False	; already 1 col, so no need to force it
	Else
		If $arraydim2<=$Xbase_maxfields Then	; parsed param $write2DAs1D determines output shape
			$Xbase_number_of_records=$arraydim1
			$Xbase_number_of_fields=$arraydim2
		Else	; won't fit into dbf: store multi-col sequentially in single column
			$Xbase_number_of_records=$arraydim1*$arraydim2
			$Xbase_number_of_fields=1	; multi var (sequentially; should all be of same type/size), single col output
			$write2DAs1D=True		; can also be set by user (parsed parameter)
		EndIf
	EndIf

	; check number of records
	If $Xbase_number_of_records>$Xbase_maxXbaseRecords Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Maximum number of records (" & $Xbase_maxXbaseRecords& ") exceeded in array")
		Return SetError(-1,0,False)
	EndIf

	; are field names and types provided?
	Local $createFieldsList=False
	If IsArray($Xbase_fields)=0 Then
		$createFieldsList=True
	Else
		$fdim1=UBound($Xbase_fields,1)-1
		If $fdim1=0 Or $fdim1<>$Xbase_number_of_fields Then $createFieldsList=True
	EndIf

	; if not presupplied, build a new fields list from scratch
	If $createFieldsList=True Then
		_PrepFieldsList()

		Local $fieldsToStore=_Max(1,$arraydim2)
		For $rc=1 To $fieldsToStore
			If $fieldNamesInTopRow=False Then
				$fname="Col"&$rc
			Else
				If $arraydim2>0 Then
					$fname=$array[0][$rc-1]
				Else
					$fname=$array[0]
				EndIf
			EndIf
			_ArrayAdd($Xbase_fields, $fname & "|C|1|0|False|False")
		Next
		$Xbase_fields[0][0]=UBound($Xbase_fields)-1

		; prep scan of field widths and types
		Local $maxwidth[$fieldsToStore+1]
		Local $maxintwidth[$fieldsToStore+1]
		Local $isNumeric[$fieldsToStore+1]
		Local $isScientific[$fieldsToStore+1]
		Local $maxdecimals[$fieldsToStore+1]

		For $cc=1 To $fieldsToStore
			$maxwidth[$cc]=1
			$maxintwidth[$cc]=1
			$isNumeric[$cc]=True
			$isScientific[$cc]=True
			$maxdecimals[$cc]=0
		Next

		; full array scan to determine properties per field (column)
		If $showprogress=True Then ProgressOn("Reading file","Scanning array","Please wait...")
		If $arraydim2>0 Then		; multi-column array
			For $rc=$startrow To $arraydim1
				If $showprogress=True And Mod($rc,200)=0 Then ProgressSet(100*($rc-1)/$Xbase_number_of_records,"Reading row " & $rc & " of " & $Xbase_number_of_records & "...")
				For $cc=1 To $Xbase_fields[0][0]
					$content=StringStripWS($array[$rc][$cc-1],1+2)
					$maxwidth[$cc]=_Max($maxwidth[$cc],StringLen($array[$rc][$cc-1]))
					$maxintwidth[$cc]=_Max($maxintwidth[$cc],StringLen(Int($array[$rc][$cc-1])))
					$isNumeric[$cc]=($isNumeric[$cc] And (StringIsFloat($content) Or StringIsInt($content)))
					If StringIsFloat($content) Then $maxdecimals[$cc]=_Max($maxdecimals[$cc],StringLen($content)-StringInStr($content,"."))
				Next
			Next

		Else						; single-column array
			For $rc=$startrow To $arraydim1
				If $showprogress=True And Mod($rc,1000)=0 Then ProgressSet(100*($rc-1)/$Xbase_number_of_records,"Reading row " & $rc & " of " & $Xbase_number_of_records & "...")
				$content=StringStripWS($array[$rc],1+2)
				$maxwidth[1]=_Max($maxwidth,StringLen($content))
				$isNumeric[1]=($isNumeric[1]=true) And (StringIsFloat($content) Or StringIsInt($content))
				If StringIsFloat($content) Then $maxdecimals[1]=_Max($maxdecimals[1],StringLen($content)-StringInStr($content,"."))
			Next
		EndIf
		If $showprogress=True Then ProgressOff()

		; if no field types are provided, we can only distinguish C/N
		Local $scientificWidth = 3 + $Xbase_decimals + 5		; [+/-][integer digit][dot]<decimals>[e][+/-][3-digit exponent]
		Local $maxmaxwidth=0
		For $cc=1 To $Xbase_fields[0][0]

			If $isNumeric[$cc]=True Then
				If $NumericToDouble=True Then
					$xbase_fields[$cc][1]= "B"
					$xbase_fields[$cc][2]= 8
					$xbase_fields[$cc][3]= 0
				ElseIf $NumericToScientific=False Then
					$xbase_fields[$cc][1]= "N"
					$xbase_fields[$cc][2]= $maxwidth[$cc]
					$xbase_fields[$cc][3]= _Max(0,_Min($maxdecimals[$cc],$maxwidth[$cc]-2))
				Else
					$xbase_fields[$cc][1]= "C"
					$xbase_fields[$cc][2]= $scientificWidth
					$xbase_fields[$cc][3]= 0
					$xbase_fields[$cc][5]= True	; special conversion case
				EndIf
			Else
				$xbase_fields[$cc][1]= "C"
				$xbase_fields[$cc][2]= $maxwidth[$cc]
				$xbase_fields[$cc][3]= 0
				$xbase_fields[$cc][5]= False
			EndIf

			; check widths
			If $Xbase_fields[$cc][1]="C" And $Xbase_fields[$cc][2]>$Xbase_maxcharacter_length Then
				MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Maximum character field width (" & $Xbase_maxcharacter_length & ") exceeded in field " & $cc)
				If $diagnostics=True Then _ArrayDisplay($Xbase_fields, "invalid fields list")
				Return SetError(-1,0,False)

			ElseIf $Xbase_fields[$cc][1]="N" Then
				If $Xbase_fields[$cc][2]>$Xbase_maxnumeric_length Then
					MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Maximum numeric field width (" & $Xbase_maxnumeric_length & ") exceeded in field " & $cc & ";" & @CR & "you may wish to consider storing these values in scientific notation instead (call with $NumericToScientific=True)")
					If $diagnostics=True Then _ArrayDisplay($Xbase_fields, "invalid fields list")
					Return SetError(-1,0,False)
				EndIf
				If $Xbase_fields[$cc][3]>$Xbase_maxdecimals Then
					MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Maximum number of decimals (" & $Xbase_maxdecimals & ") exceeded in field " & $cc & ";" & @CR & "you may wish to consider storing these values in scientific notation instead (call with $NumericToScientific=True)")
					If $diagnostics=True Then _ArrayDisplay($Xbase_fields, "invalid fields list")
					Return SetError(-1,0,False)
				EndIf
				If $maxintwidth[$cc]>$Xbase_fields[$cc][2]-$Xbase_fields[$cc][3]-($Xbase_fields[$cc][3]>0) Then	; subtract one extra from integer part for decimal dot, IF decimals>0
					MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Maximum integer width exceeds available space in field" & $cc & @CR & "you may wish to consider storing these values as binary doubles or using scientific notation instead (call with $NumericToDouble=True or $NumericToScientific=True)")
					If $diagnostics=True Then _ArrayDisplay($Xbase_fields, "invalid fields list")
					Return SetError(-1,0,False)
				EndIf
			EndIf
		Next
	EndIf		; we now have a rebuilt fields list
	If $diagnostics=True Then _ArrayDisplay($Xbase_fields, "new fields list")

	; determine maximum width over all fields
	Local $maxmaxwidth=$xbase_fields[1][2]
	Local $maxmaxdecimals=$xbase_fields[1][3]
	For $cc=2 To $Xbase_fields[0][0]
		$maxmaxwidth=_Max($maxmaxwidth,$xbase_fields[$cc][2])
		$maxmaxdecimals=_Max($maxmaxdecimals,$xbase_fields[$cc][3])
	Next

	; check size if multi-column is to be squeezed into single column
	If $write2DAs1D=True Then		; either all fields are same type (use that), or all will be stored as character field
		Local $commontype=$Xbase_fields[1][1]
		For $cc=2 To $Xbase_fields[0][0]
			If $commontype<>$Xbase_fields[$cc][1] Then $commontype="C"
		Next
		If $commontype<>"N" Then $maxmaxdecimals=0

		If $maxmaxwidth>$Xbase_maxcharacter_length Then
			MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Maximum character field width (" & $Xbase_maxcharacter_length & ") exceeded; width of largest array element: " & $maxmaxwidth)
			Return SetError(-1,0,False)
		EndIf

		$Xbase_number_of_fields=1
		$Xbase_record_length=1+$maxmaxwidth
	Else
		; determine record length
		$Xbase_record_length=1	; one extra for preceding deletion flag
		For $cc=1 To $Xbase_fields[0][0]
			$Xbase_record_length+=$Xbase_fields[$cc][2]
		Next
		$Xbase_number_of_fields=$Xbase_fields[0][0]
	EndIf

	; create struct for multiple field descriptors
	Local $XbaseAllFieldsStruct= "align 1;"
	For $cc=1 To $Xbase_number_of_fields
		$XbaseAllFieldsStruct&=$XbaseFieldDescriptorStruct & ";"
	Next
	$XbaseAllFieldsStruct&="byte"	; field list terminator 0x0d
	If $Xbase_signature=0x30 Then $XbaseAllFieldsStruct&=";byte[264]"	; Visual FoxPro DataBase Container (DBC)

	$XbaseAllFields=DllStructCreate($XbaseAllFieldsStruct)
	If @error Then _Xbase_Fatal("Unable to create XbaseAllFields buffer, error: " & @error)
	$Xbase_header_length=$XbaseHeaderSize+DllStructGetSize($XbaseAllFields)

	; fill all field descriptors
	If $write2DAs1D=False Then
		For $fc=1 To $Xbase_number_of_fields
			$offset=($fc-1)*11
			DllStructSetData($XbaseAllFields,$offset+1,$Xbase_fields[$fc][0])
			DllStructSetData($XbaseAllFields,$offset+2,$Xbase_fields[$fc][1])
			DllStructSetData($XbaseAllFields,$offset+4,$Xbase_fields[$fc][2])
			DllStructSetData($XbaseAllFields,$offset+5,$Xbase_fields[$fc][3])
			DllStructSetData($XbaseAllFields,$offset+11,$Xbase_fields[$fc][4])
		Next
	Else
		DllStructSetData($XbaseAllFields,1,"Col0")
		DllStructSetData($XbaseAllFields,2,$commontype)
		DllStructSetData($XbaseAllFields,4,$maxmaxwidth)
		DllStructSetData($XbaseAllFields,5,$maxmaxdecimals)
		DllStructSetData($XbaseAllFields,11,False)
	EndIf

	; fill new header struct
	DllStructSetData($XbaseHeader, "signature", $Xbase_signature)
	DllStructSetData($XbaseHeader, "year", @YEAR-1900)
	DllStructSetData($XbaseHeader, "month", @MON)
	DllStructSetData($XbaseHeader, "day", @MDAY)
	DllStructSetData($XbaseHeader, "header_length",$Xbase_header_length)	; file offset of first record
	DllStructSetData($XbaseHeader, "record_length", $Xbase_record_length)
	DllStructSetData($XbaseHeader, "incomplete_transaction", 0)
	DllStructSetData($XbaseHeader, "encryption_flag", 0)
	DllStructSetData($XbaseHeader, "MDX_flag", 0)
	DllStructSetData($XbaseHeader, "language_driver", 0)
	If $write2DAs1D=False Then
		DllStructSetData($XbaseHeader, "number_of_records", $Xbase_number_of_records)
		DllStructSetData($XbaseHeader, "free_record_thread", 0)
		DllStructSetData($XbaseHeader, "multi_user1a", 0)
		DllStructSetData($XbaseHeader, "multi_user1b", 0)
	Else	; store the original dimensions in some rarely-used dBase III+ header fields
		DllStructSetData($XbaseHeader, "number_of_records", $arraydim1*$arraydim2)
		DllStructSetData($XbaseHeader, "free_record_thread", $Xbase_2Dmarker)	; $EIGEN_MATRIXFILEMARKER
		DllStructSetData($XbaseHeader, "multi_user1a", $arraydim1)
		DllStructSetData($XbaseHeader, "multi_user1b", $arraydim2)
	EndIf

	; add field list terminator
	DllStructSetData($XbaseAllFields,1+($Xbase_number_of_fields*11),0x0d)

	; create struct for records
	Local $XbaseRecordStruct= "align 1;char;"	; deletion flag (20h (space): valid; 2eh ("*"): deleted
	If $write2DAs1D=False Then
		For $fc=1 To $Xbase_number_of_fields
			$XbaseRecordStruct&=_Xbase_DataTypeToStructType($Xbase_fields[$fc][1],$Xbase_fields[$fc][2]) & ";"
		Next
	Else
		$XbaseRecordStruct&=_Xbase_DataTypeToStructType($commontype,$maxmaxwidth) & ";"
	EndIf
	$XbaseRecordStruct=StringTrimRight($XbaseRecordStruct,1)	; clip last semi-colon
	$Xbase_record_length=DllStructGetSize($XbaseRecordStruct)

	$XbaseRecord=DllStructCreate($XbaseRecordStruct)
	If @error Then _Xbase_Fatal("Unable to create XbaseRecord buffer")
	$XbaseRecordPointer=DllStructGetPtr($XbaseRecord)
	$XbaseRecordSize=DllStructGetSize($XbaseRecord)

	; create new file; existing file is overwritten; open for writing
	$hFile = _WinAPI_CreateFile($filename,1,4)
	If $hFile=0 Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Unable to open target file "&$filename&" for writing")
		Return SetError(-1,0,False)
	EndIf

	; write out new header
	$bytesread=0
	$readokay=_WinAPI_WriteFile($hFile,$XbaseHeaderPointer,$XbaseHeaderSize,$bytesread)
	If $readokay=False Or $bytesread<>$XbaseHeaderSize Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Error in file header write")
		_WinAPI_CloseHandle($hFile)
		Return SetError(-1,0,False)
	EndIf

	; write out all field descriptors
	$bytesread=0
	$bytestoread=DllStructGetSize($XbaseAllFields)
	$readokay=_WinAPI_WriteFile($hFile,DllStructGetPtr($XbaseAllFields),$bytestoread,$bytesread)
	If $readokay=False Or $bytesread<>$bytestoread Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Error in field descriptors write")
		_WinAPI_CloseHandle($hFile)
		Return SetError(-1,0,False)
	EndIf

	; write out all records
	If $write2DAs1D=False Then	; no sequenced cols? (most likely case)
		Local $stripped
		If $showprogress=True Then ProgressOn("Writing file",$filename,"Please wait...")

		For $rc=$startrow To $Xbase_number_of_records - (Not $startrow)	; kinda cute, no?
			DllStructSetData($XbaseRecord,1,0x20)	; deletion flag = False
			If $showprogress=True And Mod($rc,200)=0 Then ProgressSet(100*($rc-1)/$Xbase_number_of_records,"Writing record " & $rc & " of " & $Xbase_number_of_records & "...")

			If $arraydim2>0 Then						; 2D array? (most likely case)
				For $cc=1 To $Xbase_fields[0][0]
					$stripped=StringStripWS($array[$rc][$cc-1],1+2)
					Switch $Xbase_fields[$cc][1]
						Case "B"
							Switch $Xbase_fields[$cc][5]
								Case False
									DllStructSetData($XbaseRecord,$cc+1,Number($stripped))
								Case Else	; scientific notation
									DllStructSetData($XbaseRecord,$cc+1,StringFormat("%." & $Xbase_decimals & "e",$array[$rc][$cc-1]))
							EndSwitch

						Case "C"
							Switch $Xbase_fields[$cc][5]
								Case False
									Switch StringRegExp($stripped,"^[+-]?[0-9]+\.?[0-9]*$")
										Case False
											DllStructSetData($XbaseRecord,$cc+1,$array[$rc][$cc-1])	; unstripped character string
										Case Else
											DllStructSetData($XbaseRecord,$cc+1,StringLeft($Xbase_fieldPadding,$Xbase_fields[$cc][2]-StringLen($stripped)) & $stripped)	; right-aligned numeric string
									EndSwitch
								Case Else	; scientific notation
									DllStructSetData($XbaseRecord,$cc+1,StringFormat("%." & $Xbase_decimals & "e",$array[$rc][$cc-1]))
							EndSwitch

						Case "D"	; fixed length (8 digits); no need to pad here
							Switch $unformatDate
								Case True
									DllStructSetData($XbaseRecord,$cc+1,_Xbase_UnFormatDate($stripped))
								Case Else
									DllStructSetData($XbaseRecord,$cc+1,$stripped)
							EndSwitch

						Case Else
							Switch StringRegExp($stripped,"^[+-]?[0-9]+\.?[0-9]*$")	; float or int?
								Case False
									DllStructSetData($XbaseRecord,$cc+1,$stripped)	; left_aligned
								Case Else
									$stripped=_Xbase_PadDecimals($stripped,$Xbase_fields[$cc][3])
									DllStructSetData($XbaseRecord,$cc+1,StringLeft($Xbase_fieldPadding,$Xbase_fields[$cc][2]-StringLen($stripped)) & $stripped)	; right-aligned
							EndSwitch
					EndSwitch
				Next

			Else					; 1D array
				$stripped=StringStripWS($array[$rc],1+2)
				Switch $Xbase_fields[1][1]
					Case "B"
							Switch $Xbase_fields[$cc][5]
								Case False
									DllStructSetData($XbaseRecord,$cc+1,Number($stripped))
								Case Else	; scientific notation
									DllStructSetData($XbaseRecord,$cc+1,StringFormat("%." & $Xbase_decimals & "e",$array[$rc][$cc-1]))
							EndSwitch

					Case "C"
						Switch $Xbase_fields[1][5]
							Case False
								Switch StringRegExp($stripped,"^[+-]?[0-9]+\.?[0-9]*$")
									Case False
										DllStructSetData($XbaseRecord,$cc+1,$array[$rc])	; unstripped char string
									Case Else
										DllStructSetData($XbaseRecord,$cc+1,StringLeft($Xbase_fieldPadding,$Xbase_fields[$cc][2]-StringLen($stripped)) &$stripped)	; right-aligned numeric string
								EndSwitch
							Case Else	; scientific notation
								DllStructSetData($XbaseRecord,2,StringFormat("%." & $Xbase_decimals & "e",$array[$rc]))
						EndSwitch

					Case "D"	; fixed length (8 digits); no need to pad here
						Switch $unformatDate
							Case True
								DllStructSetData($XbaseRecord,2,_Xbase_UnFormatDate($stripped))
							Case Else
								DllStructSetData($XbaseRecord,2,$stripped)
						EndSwitch

					Case Else
						Switch StringRegExp($stripped,"^[+-]?[0-9]+\.?[0-9]*$")
							Case False
								DllStructSetData($XbaseRecord,$cc+1,$stripped)	; left_aligned
							Case Else
								$stripped=_Xbase_PadDecimals($stripped,$Xbase_fields[$cc][3])
								DllStructSetData($XbaseRecord,$cc+1,StringLeft($Xbase_fieldPadding,$Xbase_fields[$cc][2]-StringLen($stripped)) & $stripped)	; right-aligned
						EndSwitch
				EndSwitch
			EndIf

			; write out new record
			$readokay=_WinAPI_WriteFile($hFile,$XbaseRecordPointer,$XbaseRecordSize,$bytesread)
			If $readokay=False Or $bytesread<>$XbaseRecordSize Then
				MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Error in file header write")
				_WinAPI_CloseHandle($hFile)
				Return SetError(-1,0,False)
			EndIf
		Next

	Else	; single-field output of 2D array (by definition: multiple cols of same type (or forced "C"), sequential)
		For $cc=1 To $Xbase_fields[0][0]

			For $rc=$startrow To $Xbase_number_of_records - (Not $startrow)
				DllStructSetData($XbaseRecord,1,0x20)	; deletion flag = False
				If $showprogress=True And Mod($rc,200)=0 Then ProgressSet(100*($rc-1)/$Xbase_number_of_records,"var " & $cc & " of " & $Xbase_fields[0][0] & "; writing record " & $rc & " of " & $Xbase_number_of_records & "...")

				$stripped=StringStripWS($array[$rc][$cc-1],1+2)
				Switch $Xbase_fields[$cc][1]
					Case "B"
						Switch $Xbase_fields[$cc][5]
							Case False
								DllStructSetData($XbaseRecord,$cc+1,Number($stripped))
							Case Else	; scientific notation
								DllStructSetData($XbaseRecord,$cc+1,StringFormat("%." & $Xbase_decimals & "e",$array[$rc][$cc-1]))
						EndSwitch

					Case "C"
						Switch $Xbase_fields[$cc][5]
							Case False
									Switch StringRegExp($stripped,"^[+-]?[0-9]+\.?[0-9]*$")
										Case False
											DllStructSetData($XbaseRecord,2,$array[$rc][$cc-1] & StringLeft($Xbase_fieldPadding,$maxmaxwidth-StringLen($array[$rc][$cc-1])))	; unstripped character string
										Case Else
											DllStructSetData($XbaseRecord,2,StringLeft($Xbase_fieldPadding,$maxmaxwidth-StringLen($stripped)) & $stripped)	; right-aligned numeric string
									EndSwitch
							Case Else	; scientific notation
								DllStructSetData($XbaseRecord,2,StringLeft($Xbase_fieldPadding,$maxmaxwidth-StringLen($stripped)) & StringFormat("%." & $Xbase_decimals & "e",$stripped))
						EndSwitch

					Case "D"
						Switch $unformatDate
							Case True
								$numericDate=_Xbase_UnFormatDate($stripped)
								DllStructSetData($XbaseRecord,2, $numericDate & StringLeft($Xbase_fieldPadding,$maxmaxwidth-StringLen($numericDate)))
							Case Else
								DllStructSetData($XbaseRecord,2,$stripped)
						EndSwitch

					Case Else
						Switch StringRegExp($stripped,"^[+-]?[0-9]+\.?[0-9]*$")
							Case False
								DllStructSetData($XbaseRecord,2,$stripped & StringLeft($Xbase_fieldPadding,$maxmaxwidth-StringLen($stripped)))	; left_aligned
							Case Else	; numeric
								$stripped=_Xbase_PadDecimals($stripped,$Xbase_fields[$cc][3])
								DllStructSetData($XbaseRecord,2,StringLeft($Xbase_fieldPadding,$maxmaxwidth-StringLen($stripped)) & $stripped)	; right-aligned
						EndSwitch
				EndSwitch

				; write out new record
				$readokay=_WinAPI_WriteFile($hFile,$XbaseRecordPointer,$XbaseRecordSize,$bytesread)
				If $readokay=False Or $bytesread<>$XbaseRecordSize Then
					MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Error in file header write")
					_WinAPI_CloseHandle($hFile)
					Return SetError(-1,0,False)
				EndIf

			Next
		Next
	EndIf
	If $showprogress=True Then ProgressOff()

	; write EOF marker
	$bytesread=0
	$bytestoread=DllStructGetSize($XbaseEOFmarker)
	$readokay=_WinAPI_WriteFile($hFile,DllStructGetPtr($XbaseEOFmarker),$bytestoread,$bytesread)
	If $readokay=False Or $bytesread<>$bytestoread Then
		MsgBox($MB_SYSTEMMODAL+$MB_ICONERROR,"Xbase Error", "Error in EOF marker write")
		_WinAPI_CloseHandle($hFile)
		Return SetError(-1,0,False)
	EndIf

	; close file
	_WinAPI_CloseHandle($hFile)

	Return True
EndFunc

#EndRegion main UDFs

#Region auxiliary UDFs

Func _Xbase_DataTypeString($type)
; source: http://www.clicketyclick.dk/databases/xbase/format/dbf.html
; Note: data types V,X are not supported here

	Switch $type
		Case "C"
			Return "Character; ASCII text < 254 characters long in dBASE. Only fields <= 40 characters can be indexed."
		Case "N"
			Return "Number; ASCII text up to 20 characters long (include sign and decimal point)."
		Case "L"
			Return "Logical; boolean/byte (8 bit) Legal values: ? (not initialised), Y,y,N,n,F,f,T,t"
		Case "D"
			Return "Date; format YYYYMMDD."
		Case "M"
			Return "Memo; pointer to ASCII text field in memo file 10 digits representing a pointer to a DBT block (default is blanks)."
		Case "F"
			Return "Floating point; (dBASE IV+, FoxPro, Clipper) 20 digits."
		Case "B"
			Return "Binary; (dBASE V) Like Memo fields, but not for text processing."
		Case "G"
			Return "General (dBASE V: like Memo) OLE Objects in MS Windows versions"
		Case "P"
			Return "Picture; (FoxPro) Like Memo fields, but not for text processing."
		Case "Y"
			Return "Currency (FoxPro)"
		Case "T"
			Return "DateTime (FoxPro) The first 4 bytes are a 32-bit little-endian integer representation of the Julian date, where Oct. 15, 1582 = 2299161 per www.nr.com/julian.html The last 4 bytes are a 32-bit little-endian integer time of day represented as milliseconds since midnight."
		Case "I"
			Return "Integer; 4 byte little endian integer (FoxPro)"
		Case "@"
			Return "Timestamp. First long repecents date and second long time. Date is the number of days since January 1st, 4713 BC. Time is hours * 3600000L + minutes * 60000L + seconds * 1000L."
		Case "O"
			Return "Double (no conversion)"
		Case "+"
			Return "Autoincrement (no conversion)"
		Case Else
			Return SetError(-1,0,"unsupported field data type")
	EndSwitch

EndFunc


Func _Xbase_DataTypeToStructType($type, $length=1)
; source: http://www.clicketyclick.dk/databases/xbase/format/dbf.html
; Note: types V,X are not supported here

	Switch $type
		Case "C","N"
			If $length<=0 Then Return ""
			Return "char[" & $length & "]"
		Case "L"
			Return "char"
		Case "D"
			Return "char[8]"
		Case "M"
			Return "byte[10]"
		Case "F"
			Return "char[20]"	; note that float is converted, but double is not
		Case "B","G","P","Y"
			If $length<=0 Then Return ""
			Return "byte[" & $length & "]"
		Case "T","@"
			Return "long[2]"
		Case "I"
			Return "int"
		Case "O"
			Return "double"
		Case "+"
			Return "long"
		Case Else
			If $length<=0 Then Return ""
			Return "byte[" & $length & "]"
	EndSwitch

EndFunc


Func _Xbase_Fatal($msg)

	MsgBox(262144+65536+$MB_ICONERROR,"Xbase Fatal Error",$msg)
	Exit(1)

EndFunc


Func _Xbase_FormatDate($yyyymmdd)

	$yyyymmdd=StringStripWS($yyyymmdd,1+2)

	Local $yr,$mo,$dy
	If StringLen($yyyymmdd)=8 Then
		$yy=StringLeft($yyyymmdd,4)
		$mm=StringMid($yyyymmdd,5,2)
		$dd=Stringright($yyyymmdd,2)
	ElseIf StringLen($yyyymmdd)=6 Then
		$yy=StringLeft($yyyymmdd,2)
		$mm=StringMid($yyyymmdd,3,2)
		$dd=Stringright($yyyymmdd,2)
	Else
		Return SetError(-1,0,$yyyymmdd)
	EndIf

	Switch $Xbase_dateFormat	; this is the dBase IV v2.0 standard (source: Borland's dBase IV language reference manual)
		Case "american","mdy"
			Return $mm & "/" & $dd & "/" & $yy
		Case "ansi"
			Return $yy & "." & $mm & "/" & $dd
		Case "british","french","dmy"
			Return $dd & "/" & $mm & "/" & $yy
		Case "german"
			Return $dd & "." & $mm & "." & $yy
		Case "italian"
			Return $dd & "-" & $mm & "-" & $yy
		Case "japan","ymd"
			Return $yy & "/" & $mm & "/" & $dd
		Case "usa"
			Return $mm & "-" & $dd & "/" & $yy
		Case Else
			Return SetError(-1,0,$yyyymmdd)
	EndSwitch

EndFunc


Func _IsScientificNotation($string)

	Return StringRegExp(StringStripWS($string,8),"^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$")

EndFunc


Func _Xbase_LanguageDriverString($codepage)
; source: http://www.clicketyclick.dk/databases/xbase/format/dbf.html

	Switch $codepage
		Case 0x00
			Return "default code page"
		Case 0x01
			Return "DOS USA code page 437"
		Case 0x02
			Return "DOS Multilingual code page 850"
		Case 0x03
			Return "Windows ANSI	code page 1252"
		Case 0x04
			Return "Standard Macintosh"
		Case 0x64
			Return "EE MS-DOS	code page 852"
		Case 0x65
			Return "Nordic MS-DOS code page 865"
		Case 0x66
			Return "Russian MS-DOS code page 866"
		Case 0x67
			Return "Icelandic MS-DOS"
		Case 0x68
			Return "Kamenicky (Czech) MS-DOS"
		Case 0x69
			Return "Mazovia (Polish) MS-DOS"
		Case 0x6A
			Return "Greek MS-DOS (437G)"
		Case 0x6B
			Return "Turkish MS-DOS"
		Case 0x96
			Return "Russian Macintosh"
		Case 0x97
			Return "Eastern European Macintosh"
		Case 0x98
			Return "Greek Macintosh"
		Case 0xC8
			Return "Windows EE code page 1250"
		Case 0xC9
			Return "Russian Windows"
		Case 0xCA
			Return "Turkish Windows"
		Case 0xCB
			Return "Greek Windows"
		Case Else
			Return SetError(-1,0,"unrecognised language driver (code page)")
	EndSwitch

EndFunc


Func _Xbase_LogicalToBoolean($logical)

	Switch $logical
		Case "T","Y"
			Return True
		Case "F","N"
			Return False
		Case Else
			Return SetError(-1,0,"")
	EndSwitch
EndFunc


Func _PrepFieldsList()

	Global $Xbase_fields[1][6]

	$Xbase_fields[0][1]="Field_Type"
	$Xbase_fields[0][2]="Field_Length"
	$Xbase_fields[0][3]="Field_Decimals"
	$Xbase_fields[0][4]="Indexed"
	$Xbase_fields[0][5]="Scientific Notation"	; internal

EndFunc


Func _Xbase_SignatureString($sig)
; source: http://www.clicketyclick.dk/databases/xbase/format/dbf.html

	$dBase_III=False
	$dBase_IV=False
	Switch $sig
		Case 0x02
			Return "FoxBase"
		Case 0x03
			Return "Xbase File without .dbt memo file"
		Case 0x04
			$dBase_IV=True
			Return "dBASE IV w/o memo file"
		Case 0x05
			Return "dBASE V w/o memo file"
		Case 0x07
			$dBase_III=True
			Return "VISUAL OBJECTS (first 1.0 versions) for the Dbase III files w/o memo file"
		Case 0x30
			Return "Visual FoxPro"
		Case 0x30
			Return "Visual FoxPro w. .dbc"
		Case 0x31
			Return "Visual FoxPro w. AutoIncrement field"
		Case 0x43
			Return ".dbv memo var size (Flagship)"
		Case 0x7B
			$dBase_IV=True
			Return "dBASE IV with memo"
		Case 0x83
			Return "Xbase File with .dbt memo file"
		Case 0x83
			$dBase_III=True
			Return "dBASE III+ with memo file"
		Case 0x87
			$dBase_III=True
			Return "VISUAL OBJECTS (first 1.0 versions) for the Dbase III files (NTX clipper driver) with memo file"
		Case 0x8B
			Return "dBASE IV w. memo"
		Case 0x8E
			$dBase_IV=True
			Return "dBASE IV w. SQL table"
		Case 0xB3
			Return ".dbv and .dbt memo (Flagship)"
		Case 0xE5
			Return "Clipper SIX driver w. SMT memo file"	;Note! Clipper SIX driver sets lowest 3 bytes to 110 in descriptor of crypted databases. So, 3->6, 83h->86h, F5->F6, E5->E6 etc.
		Case 0xF5
			Return "FoxPro w. memo file"
		Case 0xFB
			Return "FoxPro"
		Case Else
			Return SetError(-1,0,"unrecognised signature")
	EndSwitch

EndFunc


Func _Xbase_UnFormatDate($datestring)

	$split=StringSplit($datestring,"/.-")
	If $split[0]<>3 Then Return $datestring

	Switch $Xbase_dateFormat	; this is the dBase IV standard
		Case "american","mdy","usa"
			Return $split[3] & $split[1] & $split[2]
		Case "ansi","japan","ymd"
			Return $split[1] & $split[2] & $split[3]
		Case "british","french","dmy","german","italian"
			Return $split[3] & $split[2] & $split[1]
		Case Else
			Return SetError(-1,0,$datestring)
	EndSwitch

EndFunc

Func _Xbase_PadDecimals($value,$expectedDecimals)

	If StringRegExp($value,"^[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?$") Then
		Return String(Round(Execute($value),$expectedDecimals))
	Else
		Return String(Round($value,$expectedDecimals))
	EndIf

EndFunc


Func _Xbase_Sign($value)	; for field padding only

	Select
		Case $value>=0	; includes zero
			Return 1
		Case $value<0
			Return -1
		Case Else
			Return ""
	EndSelect

EndFunc

#EndRegion auxiliary UDFs
