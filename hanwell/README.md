
DESCRIPTION

Parse "Hanwell RadioLog 8" temperature/humidity sensor data from proprietary RL8 binary file into CSV file and transport compressed GZ archives over HTTP.

<pre>
RL8

 -------- --------------
| HEADER |     DATA     |
 -------- --------------
| 0x380  | size - 0x380 |

HEADER

 -----------------------------------------------------------------------------------------------------------------------
| magic | name | ? | serial | 0 | ? | ? | ? | B index | ? | W index | ? | ? | [suffix] |  0  | D ID | D index | ? |  0  |
 -----------------------------------------------------------------------------------------------------------------------
|   1   |  64  | 2 |   10   | 5 | 6 | 1 | 1 |    4    | 4 |    4    | 1 | 6 |    3     | 349 |  4   |    4    | 4 | 248 |

B = born index
W = write index
D = discard ID/index

DATA SLOT

 --------------------------------
| D | T | value | ID | index | 0 |
 --------------------------------
|   1   |   4   | 4  |   4   | 1 |

D = Discard 4bit (0 - ok; 8 - overwrite; 4 - ?)
T = Type 4bit (0 - temperature; 1 - humidity)
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
