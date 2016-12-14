#!/usr/bin/python
#
# 868Mhz AVR on ttyS0
#
# DATA[5min]: 'Got msg #1 : *ZD#T220H172L594B467'
#

import httplib,serial,socket,time,gzip,sys,os,re

PAYLOAD=''
RAMDISK='/root/amusing/ramdisk/'
CALL=True

try:
	try:	# DIR
		os.mkdir(RAMDISK + 'archive')
		os.mkdir(RAMDISK + 'http')
	except OSError: pass
	try:	# LOG
		LOG = open(RAMDISK + 'alix-amusing.log','a',0)# non-buffering
		LOG.write('Program start: ' + time.strftime("%d.%m.%Y %H:%M") + '\n')
	except IOError:
		print 'Failed to create log file.'
		sys.exit(1)
	while 1:
		try:	# SERIAL
			s = serial.Serial('/dev/ttyS0',9600,timeout=5)# 8,N,1; 5s scan..
			data = s.readline()
			if data != '':
				pattern = re.compile('^.*#T(\d\d)(\d)H(\d\d)(\d)L(\d\d)(\d)B(\d)(\d\d).*$')
				if re.match(pattern, data):# rubbish..
					PAYLOAD+=(re.sub(pattern,'box7;temperature;\\1.\\2;'
						+ time.strftime("%Y%m%dT%H%M%SZ",time.gmtime()), data)
						+ re.sub(pattern,'box7;humidity;\\3.\\4;'
						+ time.strftime("%Y%m%dT%H%M%SZ",time.gmtime()), data)
						+ re.sub(pattern,'box7;light;\\5.\\6;'
						+ time.strftime("%Y%m%dT%H%M%SZ",time.gmtime()), data)
						+ re.sub(pattern,'box7;battery;\\7.\\8;'
						+ time.strftime("%Y%m%dT%H%M%SZ",time.gmtime()), data))
			s.close()
		except serial.SerialException:
			LOG.write('Serial error.\n')
		if int(time.strftime("%M")) % 15 == 0 and CALL: # 15 min interval..
			CALL=False
			try:	# GZIP + PAYLOAD
				GZIP_FILE=RAMDISK + 'http/alix-' + time.strftime("%Y%m%dT%H%M%S") + '70.csv.gz'
				gzip.open(GZIP_FILE, 'ab').write(PAYLOAD)
			except IOError:
				LOG.write('Failed to gzip payload.\n')
			for PACK in os.listdir(RAMDISK + 'http'):
				try:
					GZIP=open(RAMDISK + 'http/' + PACK, 'rb')
				except IOError:
					LOG.write('Fail to read ' + PACK + '.\n')
				try:	# HTTP
					HEADER={'Content-type':'application/octet-stream',
						'X-Location':re.sub('^(.*)\.csv\.gz$','\\1', PACK)}
					c=httplib.HTTPConnection('xx.xx.xx.xx', '80', timeout=10)
					c.request('POST', '[removed]', GZIP, HEADER)
					r=c.getresponse()
					if (r.status == 200):
						try:	# ARCHIVE
							os.rename(RAMDISK + 'http/' + PACK, RAMDISK + 'archive/' + PACK)
						except OSError:
							LOG.write('Nothing to archive.\n')
					else:
						LOG.write('Bad request. ' + PACK + '\n')
					c.close()
					GZIP.close()
				except socket.error:
					LOG.write('Connection error. ' + PACK + '\n')
			# reset buffered payload string..
			PAYLOAD=''
		# reset transport token..
		if int(time.strftime("%M")) % 15 == 1: CALL=True
		# cleanup archive
		for old in os.listdir(RAMDISK + 'archive'):
			if os.path.getmtime(RAMDISK + 'archive/' + old) < (time.time() - 1814400):# 3 week old
				try:
					os.remove(RAMDISK + 'archive/' + old)
				except OSError:
					LOG.write('Failed to remove archive ' + old + '.\n')
except Exception as e:
	print 'Something bad ' + e.args[0]
	sys.exit(2)

