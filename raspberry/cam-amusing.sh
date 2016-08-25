#!/bin/bash

ISO=$(date -u +%Y%m%dT%H%M%SZ)
RUNTIME=$(date +%Y%m%dT%H%M%S)

MAXDIFF=0
MINDIFF=10000

RAMDISK='/root/amusing/ramdisk'

PREFIX1="$RAMDISK/img/archa-$RUNTIME"01
PREFIX2="$RAMDISK/img/archa-$RUNTIME"02

#----------------

function compare() {
	for i in $(echo '0 10 200 300 400 500 600 700'); do
		for j in $(echo '0 100 200 300 400 500'); do

			/usr/bin/convert $1 -crop 150x150+$i+$j /tmp/crop1.jpeg
			/usr/bin/convert $2 -crop 150x150+$i+$j /tmp/crop2.jpeg

			diff=$(/usr/bin/convert \
				/tmp/crop1.jpeg /tmp/crop2.jpeg \
				-compose Difference \
				-composite \
				-colorspace gray \
				-format '%[fx:mean*100]' info:)
		
			if (( $(echo "$MAXDIFF < $diff" | bc) )); then MAXDIFF=$diff; fi
			if (( $(echo "$MINDIFF > $diff" | bc) )); then MINDIFF=$diff; fi
		done
	done
	echo $(echo "$MAXDIFF - $MINDIFF" | bc | sed -r 's/^(-?)\./\10\./')
}

#----------------

mkdir $RAMDISK/img 2>/dev/null

echo 'g200' > /dev/AVR

/usr/bin/streamer -c /dev/video0 -t 5 -r 2 -s 800x600 -o $PREFIX1-01.jpeg 2>/dev/null
/usr/bin/streamer -c /dev/video1 -t 5 -r 2 -s 800x600 -o $PREFIX2-01.jpeg 2>/dev/null

rm $RAMDISK/img/*-{01..04}.jpeg

if [ -f "$RAMDISK/img/cam1.jpeg" -a -f "$RAMDISK/img/cam2.jpeg" ]; then
	VALUE1=$(compare $RAMDISK/img/cam1.jpeg $PREFIX1-05.jpeg)
	VALUE2=$(compare $RAMDISK/img/cam2.jpeg $PREFIX2-05.jpeg)
fi

mv $PREFIX1-05.jpeg $RAMDISK/img/cam1.jpeg
mv $PREFIX2-05.jpeg $RAMDISK/img/cam2.jpeg

if [ "$VALUE1" -a "$VALUE2" ]; then
	echo -e "archa_box2_cam1;phototrapvalue;$VALUE1;$ISO\n\
archa_box2_cam1;phototrapimg;$(base64 -w0 $RAMDISK/img/cam1.jpeg);$ISO" | /bin/gzip > $PREFIX1.csv.gz
	echo -e "archa_box2_cam2;phototrapvalue;$VALUE2;$ISO\n\
archa_box2_cam2;phototrapimg;$(base64 -w0 $RAMDISK/img/cam2.jpeg);$ISO" | /bin/gzip > $PREFIX2.csv.gz
	mv $PREFIX1.csv.gz $RAMDISK/http
	mv $PREFIX2.csv.gz $RAMDISK/http
fi

echo 'g0' >  /dev/AVR

