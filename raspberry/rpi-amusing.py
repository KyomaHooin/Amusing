#!/usr/bin/python
#
# Get serial data and send them gziped over HTTP..
#
# Serial input: 'Got msg #311 : *ZF#T273H370L000B467'
#                                .  .   .   .__ Serial number
#                                .  .   .______ Humidity
#                                .  .__________ Temperature
#                                ._____________ Local ID
#
#
# TODO: All processing in RAM..
#

import httplib
import serial
import socket
import time
import gzip
import os
import re

try:
#
#	#LOG
#
	log = open('rpi-amusing.log','a',0)#non-buffering
	log.write('Program start: ' + time.strftime("%d.%m.%Y %H:%M") + '\n')
#
#	#DIR
#
#	os.mkdir('archive')
#	os.mkdir('http')
#
#	MAIN
#
	while 1:
#
#		#SERIAL
#
		s = serial.Serial('/dev/ttyUSB0',9600)# 8,N,1 [default] 
		data=s.read(64)#blocking read 64 bytes, whatever..
		s.close()
		log.write(data)
		log.write(time.strftime("%H:%M") + "\n")
#
#		#RE(CSV)
#
#		if re.match('^Got',string):
# 	        print re.sub('.* \*([A-Z]{2})#T(\d\d)(\d)H(\d\d)(\d).*$','\\1;temperature;\\2.\\3;humidity;\\4.\\5', string)
#
#		#GZIP[IOError]
#
#		f=location + '-' + time.strftime("%Y%m%dT%H%M%S") + '.csv.gz'
#       	gzip.open('http/' + f, 'wb').write(data)
#		gz=open('http/' + f,'rb')
#
#		#HTTP[socket.error]
#
#		c=httplib.HTTPConnection('amusing.nm.cz', '80', timeout=10)
#		c.request('POST', '[removed]', gz, header)
#		r=c.getresponse()
#		if (r.status == 200):
#			print "ok"
#		gz.close()
#		c.close()

except:
	print 'Something went wrong..'

#archiving..
#os.rename('http/' + f,'archive/' + f)
#log.write('Program end.\n')
