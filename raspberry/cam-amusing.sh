#!/bin/bash

ISO=`date +%Y%m%dT%H%M%SZ`

C1="/root/amusing/ramdisk/archa-cam1-$ISO-01.jpeg"
C2="/root/amusing/ramdisk/archa-cam2-$ISO-01.jpeg"

echo 'g200' > /dev/AVR

/usr/bin/streamer -c /dev/video0 -t 1 -r 2 -s 800x600 -o $C1 2>/dev/null
/usr/bin/streamer -c /dev/video1 -t 1 -r 2 -s 800x600 -o $C2 2>/dev/null

/bin/gzip $C1 $C2

wget -q -t 1 -O /dev/null --header="X-Location:archa-cam1-$ISO" --post-file=$C1.gz http://10.10.19.44/sensors/rawcam.php
wget -q -t 1 -O /dev/null --header="X-Location:archa-cam2-$ISO" --post-file=$C2.gz http://10.10.19.44/sensors/rawcam.php

#AVR slow loop search..
sleep 20

echo 'g0' >  /dev/AVR

