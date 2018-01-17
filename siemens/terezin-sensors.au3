#NoTrayIcon

#include<_SQL.au3>

$sql_host = 'localhost\DESIGO'
$sql_user = 'sa'
$sql_pass = 'des1go$insight'
$sql_db = 'DIV23!PRJ=Terezin!DB=ISHTTND'; InSigHT TeNDencies

;-----

_SQL_RegisterErrorHandler(); register ADODB COM handler

$log = @ScriptDir & '\' & 'dump.log'

$adodb = _SQL_Startup(); ADODB object instance
if $adodb = $SQL_ERROR then
	FileWriteLine($log, "ADODB instance failed.")
	exit
endif

$sql = _sql_Connect($adodb, $sql_host, $sql_db, $sql_user, $sql_pass)
if $sql = $SQL_ERROR then
	FileWriteLine($log, "SQL connection failed.")
	exit
endif

$query = "SELECT Path FROM Designation"
$data  = _SQL_Execute($adodb, $query)
if $data = $SQL_ERROR then
	FileWriteLine($log, "SQL QUERY failed.")
	_SQL_Close()
	exit
endif

$output = FileOpen(@ScriptDir & '\' & 'list.txt', 1);  1 - append
if @error Then
	FileWriteLine($log, "OUTPUT file failed.")
	_SQL_Close()
	exit
endif

local $data_row
while _SQL_FetchData($data, $data_row) = $SQL_OK
	FileWriteLine($output, $data_row[0])
wend

FileClose($output)

_SQL_Close()
_SQL_UnRegisterErrorHandler(); unregister SQL COM handler

