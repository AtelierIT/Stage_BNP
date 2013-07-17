<?php

echo 'Remember to copy the CMS Pages and Blocks from the system and import them into the new';
echo '<br />';


$bs = "\n"; //break string
if (strlen($_SERVER['HTTP_HOST'])) echo $bs = "<br />";

if ($_GET['run']) {

if( ($fp = popen("rsync -avz --delete /home/stagebon/stage.bonaparteshop.com/html/app/design/frontend/default/bonaparte/ bonapart@mceuk001-lb.nexcess.net:/home/bonapart/bonaparteshop.com/html/app/design/frontend/default/bonaparte/", "r")) ) {
echo date("l, F d, Y h:i" ,time()); 

echo $bs.$bs."Copying skin 1".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}

if( ($fp = popen("rsync -avz --delete /home/stagebon/stage.bonaparteshop.com/html/skin/frontend/default/bonaparte/ bonapart@mceuk001-lb.nexcess.net:/home/bonapart/bonaparteshop.com/html/skin/frontend/default/bonaparte/", "r")) ) {
echo $bs.$bs."Copying Skin 2".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}

if( ($fp = popen("rsync -avz /home/stagebon/stage.bonaparteshop.com/html/thumbs/ bonapart@mceuk001-lb.nexcess.net:/home/bonapart/bonaparteshop.com/html/thumbs/", "r")) ) {
echo $bs.$bs."Copying Thumbs".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}

if( ($fp = popen("rsync -avz --delete /home/stagebon/stage.bonaparteshop.com/html/media/wysiwyg/ bonapart@mceuk001-lb.nexcess.net:/home/bonapart/bonaparteshop.com/html/media/wysiwyg/", "r")) ) {
echo $bs.$bs."Copying WYSIWYG files".$bs;
while( !feof($fp) ){
        echo fread($fp, 1024);
        echo $bs;
        flush(); // you have to flush buffer
    }
    fclose($fp);
}

}


?>


<form method="get">
<button type="submit">MoveDesignToLive</button>
<input type="hidden" name="run" value="true" />
</form>
