![Amusing Mapping tool](https://github.com/KyomaHooin/Amusing/raw/master/datalogger/datalogger_screen.png "screenshot")

DESCRIPTION

Parse data from DS100, S3120, D3120, ZTH, DL-121DH, HM8 datalogger to CSV and transport GZIP payload over HTTP.

DIRECTORY STRUCTURE

<pre>
[type]       [serial]       [data]

ZTH          \d{8}          *.dbf
D3120
S3120

Prumstav     prumstav\d+    *.csv
Datalogger   \d{8}

Pracom       pracom\d+      *.xls
Merlin       merlin\d+      *.xlsx
</pre>

FILE

<pre>
datalogger-amusing.au3 - Main program.
        datalogger.au3 - Logger parser.
             Xbase.au3 - FoxBase+/dBase III DBF library by A.R.T. Jonkers.
              ZLIB.au3 - De/compression library by "Ward".
        datalogger.ico - Icon file.
 datalogger_screen.png - Screenshot.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing
