#!/bin/bash
#
# UVC 1.00 device USB 2.0 Camera (0c45:6340)
#

ISO=$(date -u +%Y%m%dT%H%M%SZ)
RUNTIME=$(date +%Y%m%dT%H%M%S)

I=0
MAXDIFF=0
MINDIFF=10000

RAMDISK='/root/amusing/ramdisk'

PREFIX1="$RAMDISK/img/alix-$RUNTIME"41
PREFIX2="$RAMDISK/img/alix-$RUNTIME"42

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

echo 24 > /sys/class/gpio/export
echo out > /sys/class/gpio/GPIO24/direction
echo 0 > /sys/class/gpio/GPIO24/value

/sbin/sysctl vm.overcommit_memory=1 >/dev/null

sleep 5

#/usr/bin/streamer -c /dev/video0 -r 2 -s 800x600 -o $PREFIX.jpeg 2>/dev/null
/usr/bin/streamer -c /dev/video0 -s 800x600 -o $PREFIX.jpeg 2>/dev/null

if [ -f "$PREFIX.jpeg" ]; then
	if [ -f "$RAMDISK/img/cam.jpeg" ]; then
		cat <<- EOL | /bin/gzip > $PREFIX1.csv.gz
			box3;phototrapvalue;$(compare $RAMDISK/img/cam.jpeg $PREFIX.jpeg);${ISO}
		EOL
	fi
	cat <<- EOL | /bin/gzip > $PREFIX2.csv.gz
		box3;phototrapimg;$(base64 -w0 $PREFIX.jpeg);${ISO}
	EOL
	mv $PREFIX.jpeg $RAMDISK/img/cam.jpeg
fi

mv $RAMDISK/img/*.gz $RAMDISK/http 2>/dev/null

echo 1 > /sys/class/gpio/GPIO24/value
echo 24 > /sys/class/gpio/unexport
 
/sbin/sysctl vm.overcommit_memory=0 >/dev/null

for F in $(find $RAMDISK/http -type f -name "*.gz"); do
	if [[ $(wget -O /dev/null -q -S --header="X-Location: alix-$RUNTIME"4$I	--post-file=$F http://amusing.nm.cz/sensors/rawpost.php 2>&1 | grep "200") ]]; then
		mv $F $RAMDISK/archive
		((I++))
	fi
done

