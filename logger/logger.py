#!/usr/bin/python
#
# Datalogger fetchmail and attachment processsing.
#
# TODO:
#
#  CSV: Little-endian UTF-16 Unicode text -> Prumstav DS100
#  XLS: (xlrd)
# XLSX:
# 

import poplib,email,time,xlrd,sys,re

runtime = time.strftime("%d.%m.%Y %H:%M")
logfile = '/var/log/logger.log'

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

# FUNC

def csv_parse(buff,sid):
	try:
		csv = open('/root/data/pracom-' + time.strftime("%Y%m%dT%H%M$S") + '.csv.tmp','a')
		for line in buff.decode('utf-16').encode('utf-8').splitlines()[1:]:
			ln = line.split(',')
			stamp = time.strftime("%Y%m%dT%H%M%S",time.strptime(ln[1],"%d.%m.%Y %H:%M:%S"))
			if len(ln) == 5:
				csv.write(str(sid) + ';temperature;' + ln[2] + '.' + ln[3] + ';' + stamp + '\n')
				csv.write(str(sid) + ';humidity;' + ln[4] + ';' + stamp + '\n')
			if len(l) == 4:
				csv.write(str(sid) + ';temperature;' + ln[2] + ';' + stamp + '\n')
				csv.write(str(sid) + ';humidity;' + ln[3] + ';' + stamp + '\n')
		csv.close()
	except:
		log.write('Failed to parse CSV file.' + runtime + '\n')
	
def xls_parse(buff,sid,row):
#	try:
	if sid == 1:
		#xls = open('/root/data/' + str(sid) + '.xls','w')
		#xls.write(buff)
		#xls.close()
		book = xlrd.open_workbook(file_contents=buff)
		sheet = book.sheet_by_index(0)
		#sheet.row_values(row_index)[0]
		#for row in range(4,10):
		#	print sheet.row_values(row)[0]
		#for row_index in range(4,sheet.nrows):
		#time_in=sheet.row_values(row_index)[0]
		#ti = datetime.datetime.strptime(time_in, "%d-%m-%Y %H:%M:%S")
       	        #time_out = ti.strftime("%Y%m%dT%H%M%S")
               	#tsv_data_row=[time_out]+sheet.row_values(row_index)[1:3]
		#print(tsv_data_row)
		#csv_writer.writerow(tsv_data_row)
#	except:
#		log.write('Failed to parse XLS file.' + runtime + '\n')
		
# MAIN

try:# MAIN
        log = open(logfile,'a')
except:
        print('Failed to open log file.')
        sys.exit(4)
try:# POP3
	sess = poplib.POP3('[removed]',timeout=10)
	sess.user('[removed]')
	sess.pass_('[removed]')
	msgs = sess.stat()[0]# get last message
	#if msgs == 0:
	#	log.write('Nothing to parse. ' + runtime + '\n')
	#else:
	for m in range(1,msgs + 1):
		popmsg = sess.retr(m)
		msg = email.message_from_string('\n'.join(popmsg[1]))# email parser
		if msg.is_multipart():
			for part in range(1,len(msg.get_payload())):# only attachments
				fn = email.Header.decode_header(msg.get_payload(part).get_filename())[0][0]# filename
				#if re.match('.*(csv)$',fn):
				#	print "Got CSV.", fn
				#	csv_parse(msg.get_payload(part).get_payload(decode=True),part)
				if re.match('.*(xls)$',fn):
				#	print "Got XLS.", fn
					xls_parse(msg.get_payload(part).get_payload(decode=True),part,5)
				#elif re.match('.*(xlsx)$',fn):
				#	print "Got XLSX.", fn
				#	xls_parse(msg.get_payload(part).get_payload(decode=True),part,6)
	sess.quit()
except Exception as e:
	#print e
	log.write('Failed to fetch mail. ' + runtime + '\n')
	sys.exit(1)
log.close()

