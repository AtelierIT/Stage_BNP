<?php

$bs = "\n"; //break string
if (strlen($_SERVER['HTTP_HOST'])) echo $bs = "<br />";

if ($_GET['run']) {



if( ($fp = popen("rm -rf /home/stagebon/stage.bonaparteshop.com/html/var/cache/mage*", "r")) ) {
echo date("l, F d, Y h:i" ,time()); 

echo $bs.$bs."Clearing Cache".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}


if( ($fp = popen("rm /home/stagebon/stage.bonaparteshop.com/html/var/export/*.csv", "r")) ) {
echo $bs.$bs."Clearing local export of product data".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}


if( ($fp = popen("ls -l /home/stagebon/stage.bonaparteshop.com/html/var/export/", "r")) ) {
echo $bs.$bs."Checking that the clearing went well".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}


if( ($fp = popen("php /home/stagebon/stage.bonaparteshop.com/html/exportProdz.php", "r")) ) {
echo $bs.$bs."Running the product export function".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}


if( ($fp = popen("ls -l /home/stagebon/stage.bonaparteshop.com/html/var/export/", "r")) ) {
echo $bs.$bs."Checking for product data file".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}


if( ($fp = popen("ssh bonapart@mceuk001-lb.nexcess.net rm /chroot/home/bonapart/bonaparteshop.com/html/magmi/.var/import/*.csv", "r")) ) {
echo $bs.$bs."Cleaning up".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}


if( ($fp = popen("rsync -av --delete /home/stagebon/stage.bonaparteshop.com/html/var/export/*.csv bonapart@mceuk001-lb.nexcess.net:/chroot/home/bonapart/bonaparteshop.com/html/magmi/.var/import/", "r")) ) {
echo $bs.$bs."Moving product data to new server".$bs;
echo $bs."Product".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}


if( ($fp = popen("rsync -av --delete /home/stagebon/stage.bonaparteshop.com/html/media/catalog/product/ bonapart@mceuk001-lb.nexcess.net:/home/bonapart/bonaparteshop.com/html/media/catalog/product/", "r")) ) {
echo $bs.$bs."Media".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}


if( ($fp = popen("ssh bonapart@mceuk001-lb.nexcess.net php /home/bonapart/bonaparteshop.com/html/cron.php", "r")) ) {
echo $bs.$bs."Clearing old data ".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}


if( ($fp = popen("ssh bonapart@mceuk001-lb.nexcess.net php /home/bonapart/bonaparteshop.com/html/magmi/cli/magmi.cli.php -profile=default -mode=create", "r")) ) {
echo $bs.$bs."Importing product data via Magmi".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}


if( ($fp = popen("ssh bonapart@mceuk001-lb.nexcess.net rm /home/bonapart/bonaparteshop.com/html/var/import/*.csv ", "r")) ) {
echo $bs.$bs."Cleaning up".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}


if( ($fp = popen("ssh bonapart@mceuk001-lb.nexcess.net rm -rf /home/bonapart/bonaparteshop.com/html/var/cache/* ", "r")) ) {
echo $bs.$bs."Clearing Cache".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}


if( ($fp = popen("ssh bonapart@mceuk001-lb.nexcess.net php /home/bonapart/bonaparteshop.com/html/shell/indexer.php  -reindexall", "r")) ) {
echo $bs.$bs."Rebuilding indexes".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    	fclose($fp);
echo date("l, F d, Y h:i" ,time()); 
}

}


?>

<form method="get">
<button type="submit">MoveToLive</button>
<input type="hidden" name="run" value="true" />
</form>
