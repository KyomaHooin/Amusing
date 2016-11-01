#!/bin/bash
#
# Realtek Semiconductor Corp. FULL HD 1080P Webcam [0bda:58b0]
#

ISO=$(date -u +%Y%m%dT%H%M%SZ)
RUNTIME=$(date +%Y%m%dT%H%M%S)

MAXDIFF=0
MINDIFF=10000

RAMDISK='/root/amusing/ramdisk'

PREFIX1="$RAMDISK/img/rpi-$RUNTIME"31
PREFIX2="$RAMDISK/img/rpi-$RUNTIME"32

#----------------

function compare() {
	for i in $(echo '0 100 200 300 400 500 600 700'); do
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

mkdir $RAMDISK/img 2>/dev/null

/usr/local/bin/gpio export 18 out
/usr/local/bin/gpio mode 1 pwm
/usr/local/bin/gpio pwm 1 450

sleep 5

/usr/bin/streamer -c /dev/video-cam0 -r 2 -s 800x600 -o $PREFIX1.jpeg 2>/dev/null

if [ -f "$PREFIX1.jpeg" ]; then
	if [ -f "$RAMDISK/img/cam.jpeg" ]; then
		cat <<- EOL | /bin/gzip > $PREFIX1.csv.gz
			box3;phototrapvalue;$(compare $RAMDISK/img/cam.jpeg $PREFIX1.jpeg);${ISO}
		EOL
	fi
	cat <<- EOL | /bin/gzip > $PREFIX2.csv.gz
		box3;phototrapimg;$(base64 -w0 $PREFIX1.jpeg);${ISO}
	EOL
	mv $PREFIX1.jpeg $RAMDISK/img/cam.jpeg
fi

mv $RAMDISK/img/*.gz $RAMDISK/http 2>/dev/null

/usr/local/bin/gpio mode 1 out
/usr/local/bin/gpio write 1 0
/usr/local/bin/gpio unexport 18

