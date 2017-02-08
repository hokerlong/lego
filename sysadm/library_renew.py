import requests
import re
from lxml import html
from datetime import datetime

name = ''
cardNumber = ''
userID = ''

s = requests.Session()

headers = { 'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36',
            'Content-Type': 'application/x-www-form-urlencoded'
            }
data = { 'name': name,
        'code': cardNumber,
        'submit.x': '56',
        'submit.y': '12',
        'submit': 'submit'
        }
r = s.post('https://sccl.santaclaraca.gov/patroninfo', headers = headers, data = data)

tree = html.fromstring(r.content)

title = tree.xpath('//table[@class="patFunc"]/tr/td/label/a/span/text()')
barcode = tree.xpath('//table[@class="patFunc"]/tr/td[@class="patFuncBarcode"]/text()')
dues = tree.xpath('//table[@class="patFunc"]/tr/td[@class="patFuncStatus"]/text()')
renewids = tree.xpath('//table[@class="patFunc"]/tr/td[@class="patFuncMark"]/input')


i = 0;
for due in dues:
    m = re.search(r"DUE (\d\d)-(\d\d)-(\d\d)", due)
    if m is not None:
        due = datetime(2000+int(m.group(3)),int(m.group(1)),int(m.group(2)),23,59,59)
        left = due - datetime.today()
        if left.days == 0 and left.seconds < 7200:
            print "renew"
            renew_data = { 'currentsortorder': 'current_duedate',
                            'renew0': renewids[i].attrib['value'],
                            'renewsome': 'YES'
                }
            r = s.post('https://sccl.santaclaraca.gov/patroninfo/'+userID+'/items', headers = headers, data = renew_data)
        elif left.days < 3:
            print "return!!!!" + title[i] + barcode[i]
    i += 1
