<?php ini_set('display_errors', 0);

define('BASEPATH', dirname(__FILE__));
require (BASEPATH."/config/config.php");
$get_siteid = trim(filter_input(INPUT_GET, 'siteid', FILTER_SANITIZE_SPECIAL_CHARS));
$siteid = (false !== $get_siteid && $get_siteid !== "" && $get_siteid !== null && array_key_exists($get_siteid, $valid_siteids)) ? $get_siteid : false ;

if (isset($siteid) && false !== $siteid)
{
    date_default_timezone_set('UTC');
    $nowDate = date('l jS \of F Y h:i:s A');
    $stamp = date("YdmHis");
    $inputString = $nowDate."\n";
    $logInput = file_get_contents('php://input');
    $logArray = json_decode($logInput, true);
    $json = json_encode($logArray,JSON_PRETTY_PRINT);
    $logFile = $stamp.".".$logArray['event'].".json";
    $logPath = BASEPATH."/logs".$valid_siteids[$siteid];
    $log = $logPath.$logFile;
    if (!is_file($log))
    {
        if (!$fp = fopen($log, 'w')) {
             echo "Cannot open file ($log)";
             exit;
        }
        if (fwrite($fp, $logInput) === FALSE) {
            echo "Cannot write to file ($log)";
            exit;
        }    

        fclose($fp);
    }
    if (!is_writable($log)) 
    {
        chmod($log, 0755);
    }
    clearstatcache();
    //error_log($json."\n", 3, $log);
}
else
{
    error_log($json."\n", 3, $log);
}