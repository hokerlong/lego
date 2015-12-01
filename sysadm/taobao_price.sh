#!/bin/bash

ps ux | grep -v grep | grep taobao | awk '{print $2}' | xargs -I{} kill {};
/usr/bin/php /home/ec2-user/htdoc/taobao_price.php >> /home/ec2-user//logs/taobao_price.log 2>&1;

