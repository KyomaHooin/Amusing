#!/usr/bin/python
#
# DHT22 GPIO
#
# DATA[5min]: 'Temperature 23.5C, Humidity: 54.5%'
#

import httplib,socket,time,gzip,sys,os,re

PAYLOAD=''
RAMDISK='/root/amusing/ramdisk/'
DHT='/usr/local/bin/getDHT 22 4 > /tmp/dht 2>/dev/null'
TOKEN=True
CALL=True

try:
	try:	# DIR
		os.mkdir(RAMDISK + 'archive')
		os.mkdir(RAMDISK + 'http')
	except OSError: pass
	try:	# LOG
		LOG = open(RAMDISK + 'rpi-amusing.log','a',0)# non-buffering
		LOG.write('Program start: ' + time.strftime("%d.%m.%Y %H:%M") + '\n')
	except IOError:
		print 'Failed to create log file.'
		sys.exit(1)
	while 1:
		if int(time.strftime("%M")) % 5 == 0 and TOKEN:# 5 min data interval..
			TOKEN=False
			cmd = os.system(DHT)# DHT
			if cmd == 0: # shell return value
				try:
					data = open('/tmp/dht','r').read()
					if data != '':# empty..
						pattern = re.compile('^.*(\d\d).(\d)C.*(\d\d).(\d)%.*$')
						if re.match(pattern, data):# rubbish..
							PAYLOAD+=(re.sub(pattern,'box3;temperature;\\1.\\2;'
							+ time.strftime("%Y%m%dT%H%M%SZ",time.gmtime()), data)
							+ re.sub(pattern,'box3;humidity;\\3.\\4;'
							+ time.strftime("%Y%m%dT%H%M%SZ",time.gmtime()), data))
				except IOError:
					LOG.write('Failed to read DHT data file.' + '\n')
			else:
				LOG.write('Failed to call DHT binary.' + '\n')
		if int(time.strftime("%M")) % 5 == 1: TOKEN=True # reset transport token..
		if int(time.strftime("%M")) % 15 == 0 and CALL: # 15 min http interval..
			CALL=False
			try:	# GZIP + PAYLOAD
				GZIP_FILE=RAMDISK + 'http/rpi-' + time.strftime("%Y%m%dT%H%M%S") + '30.csv.gz'
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
					c=httplib.HTTPConnection('amusing.nm.cz', '80', timeout=10)
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
		# prevent CPU exhaustion..
		time.sleep(5)
except Exception as e:
	print 'Something bad ' + e.args[0]
	sys.exit(2)

