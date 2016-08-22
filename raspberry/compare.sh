#!/bin/bash

maxdiff=0
mindiff=10000

img1='archa-cam1-20160822T111616Z.jpeg'
img2='archa-cam2-20160822T111616Z.jpeg'

function compare() {
	for i in $(echo '0 10 200 300 400 500 600 700'); do
		for j in $(echo '0 100 200 300 400 500'); do

			/usr/bin/convert $1 -crop 150x150+$i+$j /tmp/crop1.jpeg
			/usr/bin/convert $2 -crop 150x150+$i+$j /tmp/crop2.jpeg

			diff=`/usr/bin/convert \
				/tmp/crop1.jpeg /tmp/crop2.jpeg \
				-compose Difference \
				-composite \
				-colorspace gray \
				-format '%[fx:mean*100]' info:`
		
			if (( $(echo "$maxdiff < $diff" | bc) )); then maxdiff=$diff; fi
			if (( $(echo "$mindiff > $diff" | bc) )); then mindiff=$diff; fi
		done
	done
	echo $(echo "$maxdiff - $mindiff" | bc)
}

compare $img1 $img2

