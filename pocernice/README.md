
DESCRIPTION

Parse Sauter NovaPro 374c temperature/humidity sensor data from proprietary HDB(hystorical database) binary round buffer file into CSV file and transport compressed GZ archives over HTTP.

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
 |   4   |   4  | 12|  8 * [sensor]    |   8*X      |

                                                                       8  *  5  (3 +  2) .....
                                   8  * 20 (11 +  9) ..... 10 - 20 senzoru...
                                   8  * 30 (19 + 11) ..... 10 - 20 senzoru...
                                   8  * 40 (25 + 15) ..... 20 - 40 senzoru...
                                   8  * 50 (41 + 9)  ..... 40 - 50 senzoru ..
                                         ????
 -Padding round to 5/20/30/40/50 byte size for n-sensors.
 -Slot time -> HEADER[clock] => (15 min)
 -Max 2967 data slots per file =>  2967 / 4 / 24 = 30 days
</pre>

FILE

<pre>
pocernice-amusing.au3 - Main program.
 pocernice-sensor.txt - Sensor map plaintext.
        pocernice.ico - Program icon.
</pre>

CONTACT

Author: richard_bruna@nm.cz<br>
Source: https://github.com/KyomaHooin/Amusing/pocernice
