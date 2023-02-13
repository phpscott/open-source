<?php

date_default_timezone_set('UTC');
$nowDate = date('l jS \of F Y h:i:s A');    
$inputString = $nowDate."\n";
$logInput = file_get_contents('php://input');
$logArray = json_decode($logInput, true);
$json = json_encode($logArray,JSON_PRETTY_PRINT);
$logFile = $logArray['event'].".json";
$logPath = "/var/www/html/demo/zype/webhooks/json/";
$log = $logPath.$logFile;
if (!is_file($log))
{
    if (!$fp = fopen($log, 'w')) {
         echo "Cannot open file ($log)";
         exit;
    }
    fclose($fp);
}
if (!is_writable($log)) 
{
    chmod($log, 0755);
}
clearstatcache();
error_log($json."\n", 3, $log);


//$postString = $nowDate."\n";
//$logPost = filter_input_array(INPUT_POST);
//$postString .= isArray ($logPost, $postString);
//foreach ($logGet as $key => $value) { $postString .= $key." = ".$value."\n"; }
//error_log($postString, 3, "/var/www/html/demo/zype/webhooks/webhooks_post.log");

//$getString = $nowDate."\n";
//$logGet = filter_input_array(INPUT_GET);
//$getString .= isArray ($logGet, $getString);
//foreach ($logGet as $key => $value) { $getString .= $key." = ".$value."\n"; }
//error_log($getString, 3, "/var/www/html/demo/zype/webhooks/webhooks_get.log");

/*
category.create
consumer.create
playlist.create
subscription.create
transaction.create
user.create
video.create

category.update
consumer.update
playlist.update
subscription.update

user.update
video.update

category.delete
consumer.delete
playlist.delete
subscription.delete

user.delete
video.delete            
 */