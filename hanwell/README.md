
DESCRIPTION

Parse "Hanwell RadioLog 8" temperature/humidity sensor data from proprietary RL8 binary data file into CSV file and transport compressed GZ archives over HTTP.

RL8

<pre>

RL8

 -------- --------------
| HEADER |     DATA     |
 -------- --------------
| 0x380  | size - 0x380 |

HEADER

 ----------------------------------------------------------------------
| [magic] | [name] | 0a | ? |   [serial]   |  ?  |  00    |  ? |  00   |
 ----------------------------------------------------------------------
|    1    |   64   | 2  | 1 |   10 + 0x00  |  32 |  346   | 12 |  884  | 

-One sensor per file.

DATA SLOT

 --------------------------------
| D | T |  value  | ID | counter |
 --------------------------------
| 1 | 1 |    4    | 4  |    5    |

- Discard bit: 0 - ok          8 - overwrite
- Type bit:    0 - temperature 1 - humidity
</pre>

FILE

<pre>
  hanwell-amusing.au3 - Main program.
   hanwell-sensor.txt - Sensor plain list.
          hanwell.ico - Program icon.
             ZLIB.au3 - De/compression library by "Ward".
              RL8.au3 - RadioLog 8 parser.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing
