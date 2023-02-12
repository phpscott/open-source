<?php ini_set('display_errors', 1); // set this to 0 when running production
define('BASEPATH', dirname(__FILE__));
require (BASEPATH."/lib/func.php");

$GLOBALS['fpi']                     = array(); // yep, an array
$GLOBALS['fpi']['stamp']            = date("YdmHis"); // simple timestamp to avoid collisions.
$GLOBALS['fpi']['confdir']          = "conf/"; // need to set this path once here. no other paths should be set here.
$GLOBALS['fpi']['new_data_onrun']   = false; // turn this off to set, so its turned in implicitly during run
$GLOBALS['fpi']['main_data_folder'] = null; // this gets populated if sessions are used.
$GLOBALS['fpi']['pathispassed']     = false; // another check system on if a previous session is used again.
$GLOBALS['fpi']['passedvalidlist']  = false; // if the list being used is valid, populate once verified.
// set some functions to check for, quick and dirty
$GLOBALS['fpi']['sysinit_functions']  = array(
    "EXEC"=>        "exec", // can this exec a command via cmdline? 
    "YAML"=>        "yaml_parse", // need to be able to parse yaml
    "JSONENCODE"=>  "json_encode", // must be able to handle json
    "JSONDECODE"=>  "json_decode", // json decoder/encoder
    "EXPLODE"=>      "explode", // explode content, 
    "IMPLODE"=>      "implode", // implode content, 
    "ARRAYFILTER"=> "array_filter",   // array stuff 
    "FILEGET"=>     "file_get_contents", // getting contents locally and http
    "ISFILE"=>      "is_file", // is a file
    "ISDIR"=>       "is_dir", // is a dir
    "ISWRITE"=>     "is_writable", // is writable
    "FOPEN"=>       "fopen", // open for writing
    "FWRITE"=>      "fwrite", // file write
    "FCLOSE"=>      "fclose", // file close
    "GETENV"=>      "getenv", // get env variables (available/set during script run and expunged after)
    "PUTENV"=>      "putenv", // put env variables (only available in this session (s3))
    "GETOPT"=>      "getopt" // get options passed to the script (-c, -a, -b, -s)
    );

/************* ACTIONS LIST ************************************************************/
// run the specific function/action based on the input
$options = getopt("c:a:s:b:");
// c == Config, name relates directly to saved conf/name.json
// a == Action, correlates directly to the action to be run
// b == Bucket, name of the bucket to use, superseding whats in configs (optional)
// s == Session, file to use instead of running a full ls on a aws s3 bucket (optional)
if (is_array($options) && !empty($options) && count($options) > 1 && $options['a'] !== false)
{
    if ($options['a'] !== "sysinit"): runThisAction("CONFIG",$options); endif;
    switch ($options['a']) 
    { 
        case "sysinit": //step 8
            runThisAction("SYSINIT");
            break;
        case "build":
            runThisAction("BUILD"); // buildHouse
            break;
        
        // start of S3 cases
        case "s3config":
            runThisAction("S3CONFIG",$options); // 
            break;
        case "sets3env":
            runThisAction("S3CONFIG",$options);
            runThisAction("SETS3ENVVAR"); // 
            break;
        case "gets3env":
            runThisAction("S3CONFIG",$options);
            runThisAction("GETS3ENVVAR"); // 
            break;
        case "s3list":
            runThisAction("BUILD");
            runThisAction("S3CONFIG",$options);
            runThisAction("SETS3ENVVAR");
            $bucket = (array_key_exists('b', $options)) ? $options['b'] : false;
            runThisAction("S3BUCKETLIST",$bucket);
            break;
        case "s3cp":
            runThisAction("S3CONFIG",$options);
            runThisAction("SETS3ENVVAR");
            runThisAction("S3FILECP"); 
            break;
        // individual items
        case "cleanhouse": //step 1
            runThisAction("CLEANHOUSE");
            break;
        case "readdir": //step 2
            runThisAction("DIRLIST");
            break;
        case "objects": //step 3
            runThisAction("OBJECTS"); // downloads
            break;
        case "cleanyaml": //step 4
            runThisAction("CLEANYAML");
            break;
        case "parseyaml": //step 5
            runThisAction("PARSEYAML");
            break;
        case "assets": //step 6
            runThisAction("ASSETS");
            break;
        case "xmlitems": //step 7
            runThisAction("XMLITEMS");
            break;
        case "buildmrss": //step 8
            runThisAction("BUILDMRSS");
            break;
        case "pullsubtitles":
            runThisAction("DOWNCAPTIONS");
            break;
        case "pullimages": 
            runThisAction("DOWNIMAGES");
            break;

        
        // compiled groups of cases    
        case "full": // creates new session, or uses the default config, or passed variable, if no list is config, and nothing passed it will end.
            runThisAction("BUILD");
            runThisAction("CLEANHOUSE"); sleep(1);
            runThisAction("DIRLIST"); sleep(1);
            runThisAction("OBJECTS"); sleep(1);
            runThisAction("CLEANYAML"); sleep(1);
            runThisAction("PARSEYAML"); sleep(1);
            runThisAction("ASSETS"); sleep(1);
            runThisAction("XMLITEMS"); sleep(1);
            runThisAction("BUILDMRSS"); sleep(1);
            break;
        case "fulls3": // presumes an S3 run, but can be superceded with a valid -s value.
            runThisAction("BUILD");
            if (false === $GLOBALS['fpi']['passedvalidlist']):
                runThisAction("S3CONFIG",$options);
                runThisAction("SETS3ENVVAR");
                $bucket = (array_key_exists('b', $options)) ? $options['b'] : false;
                runThisAction("S3BUCKETLIST",$bucket);  sleep(2);
            else:
                echo "\n\nSKIPPING: S3 List Generation; Valid List File Found\n";
            endif;
            runThisAction("CLEANHOUSE"); sleep(1);
            runThisAction("DIRLIST"); sleep(1);
            runThisAction("OBJECTS"); sleep(1);
            runThisAction("CLEANYAML"); sleep(1);
            runThisAction("PARSEYAML"); sleep(1);
            runThisAction("ASSETS"); sleep(1);
            runThisAction("XMLITEMS"); sleep(1);
            runThisAction("BUILDMRSS"); sleep(1);
            break;
        default:
            echo "\nERROR:\nNo Valid Action Found\n\n"; exit;
            exit;
    }
    echo "\n\n";
}
else
{
    echo "\nERROR:\nNo Configuration Found\nOR\nNo Valid Action Found\n\n"; exit;
}
/* 
php fpi.php -c zype-filmhub -a all -s 20230502111037
php fpi.php -c zype-filmhub -a full
php fpi.php -c zype-filmhub -a full -s 20230502111037
php fpi.php -c zype-filmhub -a fulls3
php fpi.php -c zype-filmhub -a fulls3 -s 20230502111037

build
readdir
objects
assets
xmlitems
buildmrss
*/