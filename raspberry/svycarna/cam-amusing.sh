#!/bin/bash
#
#  ? Camera processing
#

ISO=$(date -u +%Y%m%dT%H%M%SZ)
RUNTIME=$(date +%Y%m%dT%H%M%S)

MAXDIFF=0
MINDIFF=10000

RAMDISK='/root/amusing/ramdisk'

PREFIX="$RAMDISK/img/svycarna-$RUNTIME"31

#----------------

function compare() {
	for i in $(echo '0 10 200 300 400 500 600 700'); do
		for j in $(echo '0 100 200 300 400 500'); do

			/usr/bin/convert $1 -crop 150x150+$i+$j /tmp/crop1.jpeg
			/usr/bin/convert $2 -crop 150x150+$i+$j /tmp/crop2.jpeg

			DIFF=$(/usr/bin/convert \
				/tmp/crop1.jpeg /tmp/crop2.jpeg \
				-compose Difference \
				-composite \
				-colorspace gray \
				-format '%[fx:mean*100]' info:)
		
			if (( $(echo "$MAXDIFF < $DIFF" | bc) )); then MAXDIFF=$DIFF; fi
			if (( $(echo "$MINDIFF > $DIFF" | bc) )); then MINDIFF=$DIFF; fi
		done
	done
	echo $(echo "$MAXDIFF - $MINDIFF" | bc | sed -r 's/^(-?)\./\10\./')
}

#----------------

mkdir $RAMDISK/img $RAMDISK/http 2>/dev/null

#echo 'g200' > /dev/AVR

sleep 5

for i in $(echo '01 02 03 04 05 06 07 08 09 10'); do
	/usr/bin/streamer -c /dev/video-cam0 -r 2 -s 800x600 -o $PREFIX-$i.jpeg 2>/dev/null
done

rm $RAMDISK/img/*-{01..09}.jpeg 2>/dev/null

if [ -f "$PREFIX-10.jpeg" ]; then
	if [ -f "$RAMDISK/img/cam.jpeg" ]; then
		cat <<- EOL | /bin/gzip > $PREFIX.csv.gz
			svycarna_box;phototrapvalue;$(compare $RAMDISK/img/cam.jpeg $PREFIX-10.jpeg);${ISO}
		EOL
	fi
	cat <<- EOL | /bin/gzip > $PREFIX.csv.gz
		svycarna_box;phototrapimg;$(base64 -w0 $PREFIX-10.jpeg);${ISO}
	EOL
	mv $PREFIX-10.jpeg $RAMDISK/img/cam.jpeg
fi

mv $RAMDISK/img/*.gz $RAMDISK/http 2>/dev/null

#echo 'g0' >  /dev/AVR

