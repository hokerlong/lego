#!/bin/bash

PID=`ps ux | grep taobao_price.php | grep -v grep | awk '{print $2}'`
if [[ "" !=  "$PID" ]]; then
  echo "killing $PID"
  kill -9 $PID
fi

/usr/bin/php /home/ec2-user/htdoc/taobao_price.php 2>&1 >> /home/ec2-user/logs/taobao_price.log

