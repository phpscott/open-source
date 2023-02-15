<?php ini_set('display_errors', 1); // set this to 0 when running production

define('BASEPATH', dirname(__FILE__));
require (BASEPATH."/lib/func.php");

/************* ACTIONS LIST ************************************************************/
// run the specific function/action based on the input
$options = getopt("c:a:s:b:");
// c == Config, name relates directly to saved conf/name.json
// a == Action, correlates directly to the action to be run
// b == Bucket, name of the bucket to use, superseding whats in configs (optional)
// s == Session, file to use instead of running a full ls on a aws s3 bucket (optional)
if (is_array($options) && !empty($options) && count($options) > 1 && $options['a'] !== false && function_exists("runThisAction"))
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
            runThisAction("OBJECTS");
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
        case "pullcaptions":
            runThisAction("DOWNCAPTIONS");
            break;
        case "pullimages": 
            runThisAction("DOWNIMAGES");
            break;

        
        // compiled groups of cases    
        case "full": // creates new session, or uses the default config, or passed variable, if no list is config, and nothing passed it will end.
            // runThisAction("SYSINIT");
            runThisAction("BUILD"); // build
            runThisAction("CLEANHOUSE"); sleep(1); // clean
            runThisAction("DIRLIST"); sleep(1); // readdir
            runThisAction("OBJECTS"); sleep(1); // objects
            runThisAction("CLEANYAML"); sleep(1); // cleanyaml
            runThisAction("PARSEYAML"); sleep(1); // parseyaml
            runThisAction("ASSETS"); sleep(1); //assets
            runThisAction("XMLITEMS"); sleep(1); // xmlitems
            runThisAction("BUILDMRSS"); sleep(1); // buildmrss
            runThisAction("DOWNCAPTIONS"); // pullcaptions
            break;
        case "fulls3": // presumes an S3 run, but can be superceded with a valid -s value.
            runThisAction("BUILD");
            if (false === $GLOBALS['mcon']['passedvalidlist']):
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
            runThisAction("DOWNCAPTIONS");
            break;
        case "fullzype": // presumes an API call, but can be superceded with a valid -s value.
            runThisAction("BUILD");
            if (false === $GLOBALS['mcon']['passedvalidlist']):
                runThisAction("ZYPECONFIG",$options);
                runThisAction("SETZYPEENVVAR");
                runThisAction("ZYPEVIDEOLIST");  sleep(2);
            else:
                echo "\n\nSKIPPING: S3 List Generation; Valid List File Found\n";
            endif;
            runThisAction("CLEANHOUSE"); sleep(1);
            runThisAction("DIRLIST"); sleep(1);
            runThisAction("OBJECTS"); sleep(1);
            // runThisAction("CLEANYAML"); sleep(1);
            // runThisAction("PARSEYAML"); sleep(1);
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
php mcon.php -c mirrordog -a all -s 20230502111037
php mcon.php -c mirrordog -a full
php mcon.php -c mirrordog -a full -s 20230502111037
php mcon.php -c mirrordog -a fulls3
php mcon.php -c mirrordog -a fulls3 -s 20230502111037
*/