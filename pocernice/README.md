
DESCRIPTION

Parse "Sauter NovaPro 374c" temperature/humidity sensor data from proprietary HDB(hystorical database) binary round buffer file into CSV file and transport compressed GZ archives over HTTP.

HDB

<pre>
 DS

  ------------------------
 | HEADER |     DATA      |
  ------------------------
 | 0x1600 | size - 0x1600 |


 HEADER

  -------------------------------------------------------------------
 | [magic]  [name] [clock] [?][?] [path + ?] [date]  [? ]  [sensor]  |
  -------------------------------------------------------------------
 |   2+2  |   20  |   8   |16 |16|    64    |  32  | 128 |  42 * 128 |
 |                       0x100                           |           |

 -Max 128 sensors per file.

 DATA SLOT

  --------------------------------------------------
 | [date] [date] [? ] [     value     ] [ padding ] |
  --------------------------------------------------
 |   4   |   4  | 12|  8 * [sensor]    |   8*n      |

 -Padding round to 5/20/30/40/50 byte size for n-sensors.
 -Slot time -> HEADER[clock] => (15 min)
 -Max 2976 data slots per file =>  2967 / 4 / 24 = 31 days
</pre>

FILE

<pre>
pocernice-amusing.au3 - Main program.
 pocernice-sensor.txt - Sensor plain list.
    pocernice-hdb.txt - DS file list.
        pocernice.ico - Program icon.
             ZLIB.au3 - De/compression library by "Ward".
               DS.au3 - HDB parser.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing
