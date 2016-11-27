#!/usr/bin/python
#
# Datalogger fetchmail and attachment processsing.
#
#  CSV: UTF-16-LE Unicode - Prumstav DS100
#  XLS: Composite Document File V2 LE - Volcraft DL121-TH
# XLSX: Microsoft Excel 2007+ - Merlin HM8
#
# TODO:
#
# mail drop
# miss notification
#

import poplib,email,time,xlrd,sys,os,re

location = ('prumstav','pracom','merlin')

runtime = time.strftime("%Y%m%dT%H%M%S")

logfile = '/var/log/logger.log'
data = '/var/www/sensors/data/'

prumstav = {'185':'prumstav1',
	    '170':'prumstav2',
	    '180':'prumstav3',
	    '194':'prumstav4',
	    '165':'prumstav5'}

pracom = {'Data1':'pracom1',
	  'Data2':'pracom2',
	  'Data3':'pracom3',
	  'Data4':'pracom4',
	  'Data5':'pracom5',
	  'Data6':'pracom6',
	  'Data7':'pracom7',
	  'Data8':'pracom8'}

merlin = {'chabry':'merlin2'}

#------------------------------------

def csv_parse(buff,sid):
	try:
		csv = open('/tmp/prumstav-' + runtime + '.csv','a')
		for line in buff.decode('utf-16').encode('utf-8').splitlines()[1:]:
			ln = line.split(',')
			stamp = time.strftime("%Y%m%dT%H%M%SZ",time.strptime(ln[1],"%d.%m.%Y %H:%M:%S"))
			if len(ln) == 5:
				csv.write(str(sid) + ';temperature;' + ln[2] + '.' + ln[3] + ';' + stamp + '\n')
				csv.write(str(sid) + ';humidity;' + ln[4] + ';' + stamp + '\n')
			if len(ln) == 4:
				csv.write(str(sid) + ';temperature;' + ln[2] + ';' + stamp + '\n')
				csv.write(str(sid) + ';humidity;' + ln[3] + ';' + stamp + '\n')
		csv.close()
	except:
		log.write('Failed to parse CSV file.' + runtime + '\n')
	
def xls_parse(buff,sid):
	try:
		csv = open('/tmp/pracom-' + runtime + '.csv','a')
		book = xlrd.open_workbook(file_contents=buff)
		sheet = book.sheet_by_index(0)
		for i in range(4,sheet.nrows):
			stamp = time.strftime("%Y%m%dT%H%M%SZ",time.strptime(sheet.row_values(i)[0],"%d-%m-%Y %H:%M:%S"))
			csv.write(str(sid) + ';temperature;' + str(sheet.row_values(i)[1]) + ';' + stamp + '\n')
			csv.write(str(sid) + ';humidity;' + str(sheet.row_values(i)[2]) + ';' + stamp + '\n')
		csv.close()
	except:
		log.write('Failed to parse XLS file.' + runtime + '\n')

def xlsx_parse(buff,sid):
	try:
		csv = open('/tmp/merlin-' + runtime + '.csv','a')
		book = xlrd.open_workbook(file_contents=buff)
		sheet = book.sheet_by_index(0)
		for i in range(5,sheet.nrows):
			date = xlrd.xldate.xldate_as_tuple(sheet.row_values(i)[0],book.datemode)
			stamp = time.strftime("%Y%m%dT120000Z",time.strptime(str(date[0])
				+ ' ' + str(date[1])
				+ ' ' + str(date[2]),"%Y %m %d"))
			csv.write(str(sid) + ';temperature;' + str(sheet.row_values(i)[2]) + ';' + stamp + '\n')
			csv.write(str(sid) + ';humidity;' + str(sheet.row_values(i)[1]) + ';' + stamp + '\n')
		csv.close()
	except:
		log.write('Failed to parse XLSX file.' + runtime + '\n')

#------------------------------------

try:# LOG
        log = open(logfile,'a')
except:
        print('Failed to open log file.')
        sys.exit(1)
try:# POP3
	sess = poplib.POP3('[removed]',timeout=10)
	sess.user('[removed]')
	sess.pass_('[removed]')
	msgs = sess.stat()[0]# get last message
	for m in range(1,msgs + 1):
		popmsg = sess.retr(m)
		msg = email.message_from_string('\n'.join(popmsg[1]))# email parser
		#print msg['From']
		if msg.is_multipart():
			for part in range(1,len(msg.get_payload())):# only attachments
				fn = email.Header.decode_header(msg.get_payload(part).get_filename())[0][0]# filename
				#print fn
				#if re.match('^\d+ .*csv$',fn):
				#	sid = prumstav[re.sub('^(\d+) .*','\\1',fn)]
				#	csv_parse(msg.get_payload(part).get_payload(decode=True),sid)
				#elif re.match('^.*- \d+ -.*$',fn):
				#	sid = prumstav[re.sub('^.*- (\d+) -.*$','\\1',fn)]
				#	csv_parse(msg.get_payload(part).get_payload(decode=True),sid)
				#elif re.match('Data_?\d.*xls$',fn):
				#	sid = pracom[re.sub('^Data_?(\d).*','Data\\1',fn)]
				#	xls_parse(msg.get_payload(part).get_payload(decode=True),sid)
				#elif re.match('.*_chabry_.*xlsx$',fn):
				#	sid = merlin['chabry']
				#	xlsx_parse(msg.get_payload(part).get_payload(decode=True),sid)
	sess.quit()
except:
	log.write('Failed to fetch mail. ' + runtime + '\n')
	sys.exit(2)

for model in location:
	try:# CHOWN & MOVE
		os.chown('/tmp/' + model + '-' + runtime + '.csv',33,33)# www-data:www-data
		os.rename('/tmp/' + model + '-' + runtime + '.csv', '/root/data/' + model + '-' + runtime + '.csv')
	except pass

log.close()
