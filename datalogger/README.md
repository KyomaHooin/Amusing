![Amusing Mapping tool](https://github.com/KyomaHooin/Amusing/raw/master/datalogger/datalogger_screen.png "screenshot")

DESCRIPTION

Parse data from DS100, S3120, D3120, ZTH, DL-121DH, HM8 datalogger to CSV and transport GZIP payload over HTTP.

EXPORT EXTENSION
<pre>
 CSV - prumstav, datalogger
 DBF - s3120, d3120, zth
 XLS - pracom
XLSX - merlin
</pre>
DATA STRUCTURE
<pre>
(parsed import)

/../serial/filename.*
/../serial/..
/../serial/..

(raw import)

/../vendor-filename.csv
</pre>

FILE

<pre>
datalogger-amusing.au3 - Main program.
        datalogger.au3 - Logger parser.
             Excel.au3 - Fixed XLS library.
             Xbase.au3 - FoxBase+/dBase III DBF library by A.R.T. Jonkers.
              ZLIB.au3 - De/compression library by "Ward".
        datalogger.ico - Icon file.
 datalogger_screen.png - Screenshot.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing
