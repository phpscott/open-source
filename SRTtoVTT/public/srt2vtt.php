<?php ini_set('display_errors', 1); // set this to 0 when running production
define('BASEPATH', dirname(dirname(__FILE__)));
require (BASEPATH."/lib/subtitles.php");

$get_feed = filter_input(INPUT_GET, 'feed', FILTER_SANITIZE_SPECIAL_CHARS);
$get_url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_SPECIAL_CHARS);
$this_page = filter_input_array(INPUT_SERVER); //print_r($this_page); exit;

$secure_feed = (false !== $get_feed && $get_feed !== "" && $get_feed !== null) ? trim($get_feed) : false ;
$secure_url = (false !== $get_url && $get_url !== "" && $get_url !== null) ? trim($get_url) : false ;
$this_url = (false !== $this_page && $this_page !== "" && $this_page !== null) ? $this_page['REQUEST_SCHEME']."://".$this_page['HTTP_HOST'].$this_page['REQUEST_URI'] : false ;

if (false !== $secure_feed)
{
    $secure_feed = htmlspecialchars_decode(htmlspecialchars_decode($secure_feed));
    $st = new phpEditSubtitles();
    $st->setFile($secure_feed);
    $st->setType('vtt');
    $st->readFile();
    $output = $st->outputVTT();
    if ($output !== "" && $output !== false)
    {
        //header ("Content-Type: application/vtt");
        echo "WEBVTT"."\r\n".trim($output);
    }
}
else
{
?>
<!DOCTYPE html>
<html>
  <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title>Secure Feed</title>
  </head>
  <body>
      <form action="srt2vtt.php" method="POST" >
          <input type="text" name="url" size="70" />
          <input type="submit" name="Build URL" />
      </form>
      <br/>
      
<?php
if (false !== $secure_url)
{
    $feedurl = urlencode($secure_url);
    $thisUrl = $this_url;
    
    echo "<br/>";
    echo $thisUrl."?feed=".$feedurl;
}
?>  
  </body>
</html>
<?php
}




