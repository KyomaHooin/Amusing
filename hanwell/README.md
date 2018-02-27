![Hanwell](https://github.com/KyomaHooin/Amusing/raw/master/hanwell/hanwell_screen.png "screenshot")

DESCRIPTION

Parse "Hanwell RadioLog 8.5.9" temperature/humidity sensor data from XML/RL8 binary into CSV file and transport compressed GZ archives over HTTP.

<pre>
RL8

 -------- --------------
| HEADER |     DATA     |
 -------- --------------
| 0x380  | size - 0x380 |

HEADER

 ---------------------------------------------------------------------------------------------------------------------
| magic | name | ? | serial | 0 | ? | ? | ? | B index | ? | D index | ? | ? | suffix |  0  | C ID | C index | ? |  0  |
 ---------------------------------------------------------------------------------------------------------------------
|   1   |  64  | 2 |   10   | 5 | 6 | 1 | 1 |    4    | 4 |    4    | 1 | 6 |    3   | 349 |  4   |    4    | 4 | 248 |

B = born index ?
D = first discard index
C = last clean ID + index

DATA SLOT

 --------------------------------
| D | S | value | ID | index | 0 |
 --------------------------------
|   1   |   4   | 4  |   4   | 1 |

D = Discard 4bit (0 - ok; 8 - overwrite; 4 - ?)
S = Value sequence 4bit (0 - first 1 - second..)

-Single data pack = 2 slots.
</pre>

FILE

<pre>
hanwell-amusing.au3 - Main program.
 hanwell-sensor.txt - Sensor plain list.
        hanwell.ico - Program icon.
 _XMLDomWrapper.au3 - XML library by Stephen Podhajecki.
           ZLIB.au3 - Compression library by "Ward".
            RL8.au3 - RadioLog 8 parser.
</pre>

CONTACT

Author: richard.bruna@protonmail.com<br>
Source: https://github.com/KyomaHooin/Amusing
