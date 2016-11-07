#!/usr/bin/python
# -*- coding: utf-8 -*-
#
# A4: 595 x 842 PT
#

import StringIO,smtplib,httplib,time,sys

from reportlab.pdfgen.canvas import Canvas
from reportlab.lib import pagesizes
from reportlab.lib.utils import ImageReader

from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from email.mime.application import MIMEApplication

fbuff = StringIO.StringIO()
ibuff = StringIO.StringIO()

spravce = '[removed]'

report= {'[removed]':('archa','42_1-42_2-43_1-43_2-44_1-44_2-45_1-45_2')
}

#

for email in report.keys():
	try:# GRAPH
		c = httplib.HTTPSConnection('[removed]','443')
		c.request('GET','/muzeum/getplotref/' + report[email][1] + '/1D/1/0/0/0/0')
		r = c.getresponse()
		if r.status == 200:
			ibuff.write(r.read())
			ibuff.seek(0)# return goddamnit..
	except:
		print("Failed to get graph.")
		sys.exit(3)

	try:# PDF
		pdf = Canvas(fbuff, pagesize=pagesizes.landscape(pagesizes.A4))
		pdf.setFont('Helvetica', 10)
		pdf.drawString(50,550,"Amusing Report 1.2")
		pdf.drawString(640,550,"vygenerováno v " + time.strftime("%d.%m.%Y %H:%M"))
		pdf.line(50,545,790,543)
		pdf.drawImage(ImageReader(ibuff),70,50,700,470)
		pdf.line(50,45,790,43)
		pdf.showPage()
		pdf.save()
	except:
		print('Failed to create PDF.')
		sys.exit(2)

	try:# MAIL
		text = "\n\nDobrý den,\n\nAutomatický report pro lokalitu: " + \
			report[email][0] + \
			"\n\nAmusing NM\n\n------\n\n" + \
			"Tato zpráva je generována bez možnosti příjmu Vaší odpovedi.\n" + \
			"Pro odhlášení napište na adresu: " + spravce

		msg = MIMEMultipart()
		msg['From'] = 'Amusing Report <[removed]>'
		msg['To'] = email
		msg['Subject'] = "Amusing Report - " + time.strftime("%d.%m.%Y %H:%M")
		msg.attach(MIMEText(text,'plain','utf-8'))

		att = MIMEApplication(fbuff.getvalue())
		att['Content-Disposition'] = 'attachment; filename="' + \
			'amusing-report-' + \
			report[email][0] + \
			'-' + \
			time.strftime("%d_%m_%Y_%H_%M") + \
			'.pdf"'
		
		msg.attach(att)

		s = smtplib.SMTP('[removed]')
		s.sendmail('[removed]', email, msg.as_string())
	except:
		print ('Failed to send email.')
		sys.exit(1)

fbuff.close()
ibuff.close()
