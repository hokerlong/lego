#!/bin/bash

ps ux | grep taobao | awk '{print $2}' | xargs -I{} kill {}
cd ~/htdoc;
php taobao_price.php >> ~/logs/taobao_price.log
