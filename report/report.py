#!/usr/bin/python
#
# A4: 595 x 842 PT
#

import smtplib,httplib,socket,time,PIL,sys

from reportlab.pdfgen.canvas import Canvas
from reportlab.lib import pagesizes
from reportlab.lib.units import cm

from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from email.mime.application import MIMEApplication

img = open('graph.png','w')

try:# GRAPH
	c = httplib.HTTPSConnection('amusing.nm.cz','443')
	c.request('GET','/muzeum/getplotref/42_1-42_2-43_1-43_2-44_1-44_2-45_1-45_2/1D/1/0/0/0/0')
	r = c.getresponse()
	if r.status == 200:
		img.write(r.read())
		img.close()
except socket.error:
	print("Failed to get graph.")
	sys.exit(3)

try:# PDF
	pdf = Canvas('report.pdf', pagesize=pagesizes.landscape(pagesizes.A4))
	pdf.setFont('Helvetica', 10)
	pdf.drawString(50,550,"Amusing Report 1.2")
	pdf.drawString(640,550,"Vygenerovano: " + time.strftime("%d.%m.%Y %H:%M"))
	pdf.line(50,545,790,543)
	pdf.drawImage('graph.png',70,50,700,470)
	pdf.line(50,45,790,43)
	pdf.showPage()
	pdf.save()
except:
	print('Failed to create PDF.')
	sys.exit(2)


text = """
------

Dobry den,

Automaticky report pro lokalitu: Archa

Amusing

(demo verze)

------

ps: Tato zprava je generovana automaticky. Pro odhalseni napiste na adresu: richard_bruna@nm.cz
"""

try:# MAIL
	msg = MIMEMultipart()
	msg['From'] = 'Amusing Report <webmaster@amusing.nm.cz>'
	msg['To'] = 'richard_bruna@nm.cz'
	msg['Subject'] = "Amusing Report - " + time.strftime("%d.%m.%Y %H:%M")

	msg.attach(MIMEText(text))

	a = MIMEApplication(open('report.pdf').read())
	a['Content-Disposition'] = 'attachment; filename="amusing-report' + time.strftime("%d_%m_%Y_%H_%M") + '.pdf"'

	msg.attach(a)

	s = smtplib.SMTP('ms.nm.cz')
	s.sendmail('webmaster@amusing.nm.cz', 'michal_pech@nm.cz',msg.as_string())
except:
	print ('Failed to send email.')
	sys.exit(1)

