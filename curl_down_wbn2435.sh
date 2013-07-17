#!/bin/bash

curl -o /chroot/home/stagebon/stage.bonaparteshop.com/html/dump_files/WBN2435 -u bapweb:PTG433 ftp://ftp1.pulsen.se/KUND/WBN/WBN2435
date
DATE1=`date -r /chroot/home/stagebon/stage.bonaparteshop.com/html/dump_files/WBN2435 +%s`;
DATE2=`date +%s`;
DIFF1=`expr $DATE2 - $DATE1`;
DIFF=`expr $DIFF1 / 60`;

if [ $DIFF -lt 20 ]
then

php -f /chroot/home/stagebon/stage.bonaparteshop.com/html/shell/bonaparte_import.php -- -type stockincr

#php -f /var/www/magento/shell/bonaparte_import.php -- -type stockincr
echo -e "\n"
date
fi