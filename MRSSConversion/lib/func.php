<?php defined('BASEPATH') OR exit('No direct script access allowed');
require(BASEPATH."/lib/vars.php");
require(BASEPATH."/lib/zype/Zype.php");

/************* FUNCTIONS LIST ************************************************************/
# load the json config. todo: allow different configs/paths to be loaded via param
function loadConfig ($options)
{
    echo "\tRunning: loadConfig()\n";
    $file           = $options['c'].".json"; 
    $fileuri        = (is_file($GLOBALS['mcon']['confdir'].$file)) ? $GLOBALS['mcon']['confdir'].$file : false ;
    if (false !== $fileuri):
        $configJSON = file_get_contents($fileuri);
        $config = json_decode($configJSON, 1);
        if (is_array($config) && array_key_exists("data_folder", $config)):
            $GLOBALS['mcon'] = $GLOBALS['mcon'] + $config;
            $GLOBALS['mcon']['main_data_folder'] = $GLOBALS['mcon']['data_folder'];
            $GLOBALS['mcon']['options'] = $options;
            $GLOBALS['mcon']['new_data_onrun'] = (is_array($config) && array_key_exists("new_data_onrun", $config) && $config['new_data_onrun'] === "true") ? true : $GLOBALS['mcon']['new_data_onrun'];         
            if (array_key_exists("s", $options) && $options['s'] !== ""):
                $path = $GLOBALS['mcon']['data_folder'].$GLOBALS['mcon']['sess_data_folder'].$options['s']."/";
                $GLOBALS['mcon']['stamp'] = $options['s'];
                $GLOBALS['mcon']['data_folder'] = $path;
                $GLOBALS['mcon']['pathispassed'] = true;
            elseif ($GLOBALS['mcon']['new_data_onrun'] === true):
                $path = $GLOBALS['mcon']['data_folder'].$GLOBALS['mcon']['sess_data_folder'].$GLOBALS['mcon']['stamp']."/";
                $GLOBALS['mcon']['data_folder'] = $path;
            else:
                $GLOBALS['mcon']['stamp'] = (array_key_exists("dirFile", $GLOBALS['mcon']) && $GLOBALS['mcon']['dirFile'] !== "") ? $GLOBALS['mcon']['dirFile'] : $GLOBALS['mcon']['stamp'] ;
            endif;
        else:
            echo "\n\nERROR: No Data Folder Found\n\n"; exit;
        endif; 
    else:
        echo "\n\nERROR: No Config File Found\n\n"; exit;
    endif;
}
# load the s3 json config. todo: allow different configs/paths to be loaded via param
function loadS3Config ($options)
{
    echo "\tRunning: loadS3Config()\n";
    $file           = $options['c'].".json"; 
    $fileuri        = (is_file($GLOBALS['mcon']['s3_config_dir'].$file)) ? $GLOBALS['mcon']['s3_config_dir'].$file : false ;
    if (false !== $fileuri):
        $configJSON = file_get_contents($fileuri);
        $config = json_decode($configJSON, 1);
        if (is_array($config) && array_key_exists("s3_config", $config)):
            $GLOBALS['mcon'] = $GLOBALS['mcon'] + $config;
        else:
            echo "\n\nERROR: No S3 Path Found\n\n"; exit;
        endif; 
    else:
        echo "\n\nERROR: No S3 Config Found\n\n"; exit;
    endif;
}
# read general data with switch
function runThisAction ($type,$options=false)
{
    //echo "\nrunThisAction\n";
    switch ($type)
    {
        case "SYSINIT":
            echo "\nRunning System Verification (Check)\n";
            systemCheck ();
            break;
        case "BUILD":
            echo "\nBuilding the Data structure\n";
            buildHouse ();
            break;
        case "CONFIG":
            loadConfig ($options);
            $dirFile = (false !== $GLOBALS['mcon']['pathispassed']) ? $GLOBALS['mcon']['stamp'].".txt" : $GLOBALS['mcon']['dirFile'];
            $GLOBALS['mcon']['passedvalidlist'] = (false !== $dirFile && is_file($GLOBALS['mcon']['data_folder'].$GLOBALS['mcon']['dir']['DIRLIST'].$dirFile)) ? true : false;             
            
            // set some conditionals for abending, just incase it gets this far.
            switch ($options['a'])
            {
                case "all":
                    if (false === $GLOBALS['mcon']['passedvalidlist']): 
                        echo "\n\nYou Must Supply a Valid List, else Run Full\n\n"; exit;
                    endif;
                    break;
                case "full":
                    $hasDir = ($GLOBALS['mcon']['dirFile'] !== "") ? true : false;
                    $hasSess = (array_key_exists("s", $GLOBALS['mcon']['options'])) ? true : false;
                    $new_data_onrun = $GLOBALS['mcon']['new_data_onrun'];
                    if (false === $hasDir && false === $hasSess): 
                        echo "\n\nYou Must Supply a Valid List either by Parameter -s or config 'dirFile', else Run FullS3\n\n"; exit;
                    endif;
                    break;
            }
            break;
        case "DOWNIMAGES":
            echo "\nBuilding the Data structure\n";
            downloadImages ();
            break;
        case "DOWNCAPTIONS":
            echo "\nDownloading Closed Captions\n";
            downloadCaptions ();
            break;
        // s3
        case "S3CONFIG":
            loadS3Config ($options);
            break;  
        case "SETS3ENVVAR":
            setS3EnvVars ();
            break;
        case "GETS3ENVVAR":
            getS3EnvVars ();
            break;
        case "S3BUCKETLIST":
            echo "\nGenerating Directory List from S3\n";
            $bucket = (false !== $options) ? $options : $GLOBALS['mcon']['s3Bucket'];
            listS3BucketFiles ($bucket);
            break;
        case "S3FILECP":
            cpFileToS3Bucket ();
            break;
        case "TEST":
            // do some checks on functions if they exist?
            break;
        case "CLEANHOUSE":
            echo "\nCleanHouse\n";
            cleanHouse ();
            break;
        case "DIRLIST":
            echo "\nRead Directory List File, Build SKUIDs & Paths, Create OBJECTS\n";
            $returnArray = parseRawDIRLIST ($GLOBALS['mcon']['stamp'].".txt", $GLOBALS['mcon']['dir']['DIRLIST']);
            // write objects
            writeThisData ($returnArray["data"],$GLOBALS['mcon']['dir']['OBJECTS'],$GLOBALS['mcon']['objectsFile']); 
            // write skuids
            writeThisData ($returnArray["skuids"],$GLOBALS['mcon']['dir']['SKUIDS'],$GLOBALS['mcon']['allskuidsFile']);
            break;
        case "APILIST":
            echo "\nRead API, Build SKUIDs & Paths, Create OBJECTS\n";
            
            break;        
        case "OBJECTS":
            echo "\nRead OBJECTS File, Validate SKUIDs & Create ASSETS, Download YAMLs\n";
            // iterate over objects and build paths for each valid skuid
            $skuids = parseOBJECTS ($GLOBALS['mcon']['objectsFile'], $GLOBALS['mcon']['dir']['OBJECTS']);
            // write valid skuids from objects
            writeThisData ($skuids,$GLOBALS['mcon']['dir']['SKUIDS'],$GLOBALS['mcon']['skuidsFile']);
            break;
        case "CLEANYAML":
            echo "Step 4: Read YAML and Parse Downloaded YAMLs\n";
            // iterate over yaml source and clean yaml file
            $skuids = parseSKUIDS ($GLOBALS['mcon']['skuidsFile'], $GLOBALS['mcon']['dir']['SKUIDS']);
            cleanYAML ($skuids);
            break;
        case "PARSEYAML":
            echo "Step 5: Read YAML and Parse Downloaded YAMLs\n";
            // iterate over objects and build paths for each valid skuid
            $skuids = parseSKUIDS ($GLOBALS['mcon']['skuidsFile'], $GLOBALS['mcon']['dir']['SKUIDS']);
            parseYAML ($skuids);
            break;
        case "ASSETS":
            echo "\nRead ASSETS Files, Create JSON ready for XML (XMLITEMS)\n";
            // parse skuids and parse the assets into json items ready for xml
            $skuids = parseSKUIDS ($GLOBALS['mcon']['skuidsFile'], $GLOBALS['mcon']['dir']['SKUIDS']);
            //print_r($skuids); exit;
            parseASSETS ($skuids, $GLOBALS['mcon']['dir']['YMLJSON']);
            break;
        case "XMLITEMS":
            echo "\nCreate XML READY Items: For each GENRE, Read JSON (XMLITEMS) Files, Create XML ready for MRSS\n";
            // iterate over directory of items and convert to single xml <item>s matching mrss sec
            parseXMLITEMS ($GLOBALS['mcon']['dir']['XMLITEMS']);
            parseXMLITEMS ($GLOBALS['mcon']['dir']['XMLSERIESITEMS']);
            parseXMLITEMS ($GLOBALS['mcon']['dir']['XMLTRAILERITEMS']);
            break;
        case "BUILDMRSS":
            echo "\nCreate MRSS; For each GENRE, Combine XML ready for MRSS based on GENRE\n";
            // iterate over genre folders combining individual items into single mrss (per genre folder)
            buildMRSS ($GLOBALS['mcon']['genre_folders']);
            break;
    }
}
# simple round of try and catch
function systemCheck ()
{
    foreach ($GLOBALS['mcon']['sysinit_functions'] as $key => $value) 
    {
        $hasFailure = false;
        if (function_exists($value)):
            echo "\e[1;32mPASS\e[0m ".$key." Function IS Available\n";
        else:
            echo "\e[1;31mERROR\e[0m: ".$key." Function IS NOT Available.\n";
            $hasFailure = true;
        endif;
    }
    echo "\n";
    if (true !== $hasFailure):
        $output = null; $retval = null;
        exec("aws --version", $output, $retval);
        $bits = (is_array($output) && array_key_exists("0", $output)) ? explode(" ", $output['0']) : false;
        $awsbits = (is_array($bits) && array_key_exists("0", $bits)) ? $bits['0'] : false;
        $hasAWS = strpos($awsbits, "aws-cli");        
        if ($hasAWS !== false):
            echo "\e[1;32mPASS\e[0m AWS-CLI SDK IS Available\n";
        else:
            echo "\e[1;31mERROR\e[0m: AWS-CLI SDK IS NOT Available.\n";
        endif;
    endif;
    echo "\n";
}
# simple function to build directories. requires 1 dir created and chmod 777: data
function buildHouse ()
{
    $buildHouse = array();
    echo "\tRunning: buildHouse()\n";
    $output = null; $retval = null;
    // create the main folder:
    $mainDataFolder = $GLOBALS['mcon']['main_data_folder'];
    echo "\tCreating Main Session Folder: ".$mainDataFolder."\n";
    if (is_dir($mainDataFolder) === false):
        exec('mkdir '.$mainDataFolder, $output, $retval);
        $buildHouse['main_data_folder'] = $output;
        if (is_dir($mainDataFolder) === false):
            echo "\tUnable to create initial directory\n";
            return false;
        else:
            echo "\tCHMOD Main Session Folder\n";
            $output = null; $retval = null;
            exec('chmod 0777 -f '.$mainDataFolder, $output, $retval);
            $buildHouse['main_data_folder_chmod'] = $output;
        endif;  
    else:
        echo "\tMain Session Folder Exists\n";
        $buildHouse['main_data_folder'] = 'Folder Already Exists';
    endif;
    $sessFolder = $mainDataFolder.$GLOBALS['mcon']['sess_data_folder']; //sess_data_folder
    echo "\tCreating Sessions Folder: ".$sessFolder."\n";
    if (is_dir($sessFolder) === false):
        exec('mkdir '.$sessFolder, $output, $retval);
        $buildHouse['sessions_folder'] = $output;
        if (is_dir($sessFolder) === false):
            echo "\tUnable to create sessions directory\n";
            return false;
        else:
            echo "\tCHMOD Sessions Folder\n";
            $output = null; $retval = null;
            exec('chmod 0777 -f '.$sessFolder, $output, $retval);
            $buildHouse['sessions_folder'] = $output;
        endif;  
    else:
        echo "\tSessions Folder Exists\n";
        $buildHouse['sessions_folder'] = 'Folder Already Exists';
    endif;    
    
    if (false !== $GLOBALS['mcon']['pathispassed']):
        echo "\tUsing Existing Session Folder: ".$GLOBALS['mcon']['options']['s']."\n";
        $sessDataFolder = $GLOBALS['mcon']['data_folder'];
        if (is_dir($sessDataFolder) !== false):
            echo "\tExisting Session Folder: ".$GLOBALS['mcon']['options']['s']." FOUND\n";
            $GLOBALS['mcon']['stamp'] = $GLOBALS['mcon']['options']['s'];
            $GLOBALS['mcon']['new_data_onrun'] = false; // skip the next iteration below
            cleanHouse ();
        else:
            echo "\tExisting Session Folder: ".$GLOBALS['mcon']['options']['s']." NOT FOUND\n";
            echo "\n\nEXITing NOW\n\n";
            exit;
        endif;
    endif;
    // this gets skipped if pathispassed is conditioned
    if ($GLOBALS['mcon']['new_data_onrun'] === true)
    {
        $sessAppend = $GLOBALS['mcon']['stamp']."/";
        $sessDataFolder = $GLOBALS['mcon']['data_folder'];
        echo "\tCreating PerRun Session Folder: ".$sessDataFolder."\n";
        
        if (is_dir($sessDataFolder) === false):
            exec('mkdir '.$sessDataFolder, $output, $retval);
            $buildHouse['sess_data_folder'] = $output;
            if (is_dir($sessDataFolder) === false):
                echo "\tUnable to create Sessions directory\n";
                return false;
            else:
                echo "\tCHMOD Sessions Folder\n";
                $output = null; $retval = null;
                exec('chmod 0777 -f '.$sessDataFolder, $output, $retval);
                $buildHouse['sess_data_folder_chmod'] = $output;
            endif;  
        else:
            echo "\tSessions Folder Exists\n";
            $buildHouse['sess_data_folder'] = 'Sessions Folder Already Exists';
        endif;    
    }
    echo "\n";
    echo "\tCreating Other Directories\n\n";
    // do all remaining non genre folders
    foreach ($GLOBALS['mcon']['dir'] as $title => $folder)
    {
        echo "\tCreating ".$title." Folder\n";
        $output = null; $retval = null;
        $thisFolder = $GLOBALS['mcon']['data_folder'].$folder;
        if (is_dir($thisFolder) === false):
            exec('mkdir '.$thisFolder, $output, $retval);
            $buildHouse[$title] = $output;
            if (is_dir($thisFolder) === false):
                echo "\tUnable to create ".$thisFolder." directory\n";
            else:
                echo "\tCHMOD ".$title." Folder\n";
                $output = null; $retval = null;
                exec('chmod 0777 -f '.$thisFolder, $output, $retval);
                $buildHouse[$title] = $output;
            endif;      
        else:
            echo "\t".$title." Folder Exists\n";
            $buildHouse[$title] = 'Folder Already Exists';  
        endif;
    }      
    echo "\n";
    echo "\tCreating Genre Directories\n\n";    
    // now do genres
    foreach ($GLOBALS['mcon']['genre_folders'] as $title => $folder)
    {
        echo "\tCreating ".$title." Folder\n";
        $output = null; $retval = null;
        $thisFolder = $GLOBALS['mcon']['data_folder'].$folder;
        if (is_dir($thisFolder) === false):
            exec('mkdir '.$thisFolder, $output, $retval);
            $buildHouse[$title] = $output;
            if (is_dir($thisFolder) === false):
                echo "\tUnable to create ".$thisFolder." directory\n";
            else:
                echo "\tCHMOD ".$title." Folder\n";
                $output = null; $retval = null;
                exec('chmod 0777 -f '.$thisFolder, $output, $retval);
                $buildHouse[$title] = $output;
            endif;   
        else:
            echo "\t".$title." Folder Exists\n";
            $buildHouse[$title] = 'Folder Already Exists';   
        endif;
    }        
}
function buildRoom ($path)
{
    echo "\tRunning: buildRoom()\n";
    $realPath = $GLOBALS['mcon']['data_folder'].$path."/";
    $output = null; $retval = null;
    if (is_dir($realPath) === false):
        exec('mkdir '.$realPath, $output, $retval);
        if (is_dir($realPath) === false):
            return false;
        else:
            $output = null; $retval = null;
            exec('chmod 0777 -f '.$realPath, $output, $retval);
            return true;
        endif;  
    else:
        return true;
    endif;
    return false; // it should not reach this
}
# simple function to clean a single dir from previously run data
function cleanRoom ($path,$scope=".json")
{
    echo "\tRunning: cleanRoom()\n";
    $realPath = $GLOBALS['mcon']['data_folder'].$path;
    exec('rm -f '.$realPath."/*".$scope, $output, $retval);  
}
# simple function to clean directories from previously run data
function cleanHouse ()
{
    $cleanHouse = array();
    echo "\tRunning: cleanHouse()\n";
    foreach ($GLOBALS['mcon']['genre_folders'] as $title => $folder)
    {
        $path = $GLOBALS['mcon']['data_folder'].$folder;
        $output = null; $retval = null;
        exec('rm -f '.$path."/*", $output, $retval);
        $cleanHouse['genres'] = $output;
    }
    foreach ($GLOBALS['mcon']['dir'] as $title => $folder)
    {
        $output = null; $retval = null;
        $path = $GLOBALS['mcon']['data_folder'].$folder;
        if ($title == "MRSSIMPORT"):
            exec('rm -f '.$path."/*.xml", $output, $retval);
            $cleanHouse['mrss'] = $output;
        elseif ($title == "CAPTIONS"):
            $dirs = array_filter(glob($path."*"), 'is_dir');
            foreach ($dirs as $key => $dir)
            {
                $output = null; $retval = null;
                exec('rm -Rfd '.$dir, $output, $retval);
                $cleanHouse['mrss'] = $output;
            }
            exit;
        elseif ($title == "FILES"):
            exec('rm -f '.$path."/*.json", $output, $retval);
            $cleanHouse['mrss'] = $output;
        elseif ($title !== "DIRLIST" && $title !== "MRSSITEMS"):
            exec('rm -f '.$path."/*", $output, $retval);
            $cleanHouse['dir'] = $output;            
        endif;        
    }    
}
# function to build the mrss file, using xmlitems
function buildMRSS ($folders)
{
    echo "\tRunning: buildMRSS()\n";
    foreach ($folders as $title => $folder)
    {
        $mrssitems = generateItemsList ($folder,".xml");
        if (false !== $mrssitems && is_array($mrssitems) && count($mrssitems) >= 1)
        {
            $mrsspath = $GLOBALS['mcon']["dir"]["MRSSIMPORT"];
            $mrssFile = str_replace(array("/"," & "),"-",$title).".xml";
            removeThisFile ($mrsspath.$mrssFile);
            $itemString = '<rss xmlns:media="https://search.yahoo.com/mrss/" xmlns:dcterms="https://purl.org/dc/terms/" version="2.0">';
            $itemString .= '<channel>';
            $itemString .= '<title>'.str_replace("&", "&amp;", $title).'</title>';
            $itemString .= '<link/>';
            $itemString .= '<description/>';
            writeThisSegment ($itemString,$mrsspath,$mrssFile);
            foreach ($mrssitems as $item)
            {
                $itemString = loadXML ($item);
                writeThisSegment ($itemString,$mrsspath,$mrssFile);
            }
            $itemString = "</channel></rss>";
            writeThisSegment ($itemString,$mrsspath,$mrssFile);
            echo "\tWriting: ".$mrsspath.$mrssFile."\n";
        }
    }
}
# load an xml doc into a single scalar
function loadXML ($folder)
{
    //echo "\tRunning: loadXML()\n";
    $path = $folder;
    return file_get_contents($path);
}
# parse xmlitems into the MRSS Build
function parseXMLITEMS ($path)
{
    echo "\tRunning: parseXMLITEMS()\n";
    $xmlItems = generateItemsList ($path,".json");
    if (false !== $xmlItems && is_array($xmlItems) && count($xmlItems) >= 1)
    {
        foreach ($xmlItems as $item)
        {
            $itemArray = loadJSON ($item);
            $skuid = $itemArray['media:source_id'];
            $genre = $itemArray['channel:title'];
            $mrssItem = buildMRSSItem ($itemArray);
            writeThisData ($mrssItem,$GLOBALS['mcon']['genre_folders'][$genre],$skuid.$GLOBALS['mcon']['mrssXMLItem']);
        }
    }
}
# build a single xml item using mrss element names and include additional metadata for parsing later
function buildMRSSItem ($singleItem)
{
    $sourceID = (array_key_exists('media:source_id', $singleItem)) ? $singleItem["media:source_id"] : false ;
    $itemString = '<item>';
        $itemString .= (array_key_exists("media:title", $singleItem)) ? '<title>'.str_replace("&", "&amp;", $singleItem["media:title"]).'</title>':'<title/>';
        //str_replace(array(" | ","&"), array(", ","&amp;"), $singleItem["media:title"]);
        $itemString .= (array_key_exists("item:guid", $singleItem)) ? '<guid>'.$singleItem["item:guid"].'</guid>':'<guid/>';
        $itemString .= "<link/>";
        $itemString .= (array_key_exists("item:pubDate", $singleItem)) ? '<pubDate>'.date("D, j M Y", mktime(0, 0, 1, 1, 1, $singleItem["item:pubDate"])).' 01:01:01</pubDate>':'';
        $itemString .= (array_key_exists("item:endDate", $singleItem)) ? '<endDate>'.$singleItem["item:endDate"].'</endDate>':'<endDate/>';
        if (array_key_exists("media:content:url", $singleItem["video"])) :
            $duration = (array_key_exists("media:content:duration", $singleItem["video"])) ? ' duration="'.$singleItem["video"]["media:content:duration"].'"' : "" ;
            $width = (array_key_exists("media:content:width", $singleItem["video"])) ? ' width="'.$singleItem["video"]["media:content:width"].'"' : "" ;
            $height = (array_key_exists("media:content:height", $singleItem["video"])) ? ' height="'.$singleItem["video"]["media:content:height"].'"' : "" ;
            $itemString .= '<media:content url="'.$GLOBALS['mcon']['http_root_prefix'].$singleItem["video"]["media:content:url"].'"'.$duration.$width.$height.'>';
        endif;
        $itemString .= (array_key_exists("media:title", $singleItem)) ? '<media:title>'.str_replace("&", "&amp;", $singleItem["media:title"]).'</media:title>' :'';
        $itemString .= (array_key_exists("media:description", $singleItem)) ? '<media:description>'.str_replace("&", "&amp;", $singleItem["media:description"]).'</media:description>' :'';
        // keywords loop
        if (array_key_exists("media:keywords", $singleItem)) :
            $keywordString = implode(",", $singleItem["media:keywords"]);
            $keywordString = str_replace("&", "&amp;", $keywordString);
            $itemString .= '<media:keywords>'.$keywordString.'</media:keywords>';
        endif;
        if (array_key_exists("media:thumbnail:url", $singleItem["image"])) :
            $width = (array_key_exists("media:thumbnail:width", $singleItem["image"])) ? ' width="'.$singleItem["image"]["media:thumbnail:width"].'"' : ' width="1080"' ;
            $height = (array_key_exists("media:thumbnail:height", $singleItem["image"])) ? ' height="'.$singleItem["image"]["media:thumbnail:height"].'"' : ' height="720"' ;
            $itemString .= '<media:thumbnail url="'.$GLOBALS['mcon']['http_root_prefix'].$singleItem["image"]["media:thumbnail:url"].'"'.$width.$height.' />';
        endif;            
        if (array_key_exists("media:category", $singleItem) && is_array($singleItem["media:category"])) :
            $metaString = "";
            $catString = "";
            foreach ($singleItem["media:category"] as $value)
            {
                $pos = strpos($value, ' | ');
                $catExplode = ($pos !== false) ? explode("|", $value) : $value;              
                if (is_array($catExplode) && count($catExplode) > 1):
                    if (trim($catExplode['0']) == $singleItem["channel:title"]):
                        $catString .= str_replace(array(" | ","&"), array(", ","&amp;"), $catExplode['1']).","; //$catExplode['1'].",";
                    else:
                        $metaString .= str_replace(array(" | ","&"), array(", ","&amp;"), $value).",";
                    endif;
                else:
                    $metaString .= str_replace(array(" | ","&"), array(", ","&amp;"), $value).",";
                endif;
            }
            $itemString .= (trim($catString) !== "") ? '<media:category>'.rtrim(trim($catString),",").'</media:category>' : '';
            $itemString .= ($metaString !== "") ? '<meta name="categories" value="'.rtrim(trim($metaString),",").'"/>' : '';
        endif;
        if (array_key_exists("media:subtitle:url", $singleItem["caption"]) && $singleItem["caption"]["media:subtitle:url"] !== null) :

            $captionType = ("true" === $GLOBALS['mcon']['convertCaptions'] && array_key_exists("media:subtitle:type", $singleItem["caption"])) ? convertType($singleItem["caption"]["media:subtitle:type"]) : $singleItem["caption"]["media:subtitle:type"];
            $captionURI = ("true" === $GLOBALS['mcon']['convertCaptions']) ? urlencode($GLOBALS['mcon']['http_root_prefix'].$singleItem["caption"]["media:subtitle:url"]) : $GLOBALS['mcon']['http_root_prefix'].$singleItem["caption"]["media:subtitle:url"];
            if ("true" === $GLOBALS['mcon']['convertCaptions'] && array_key_exists("convertCaptionsUrl", $GLOBALS['mcon']))
            {
                //echo $captionURI."\n\n"; exit;
            }
            $captionsConversion = ("true" === $GLOBALS['mcon']['convertCaptions'] && array_key_exists("convertCaptionsUrl", $GLOBALS['mcon'])) ? $GLOBALS['mcon']['convertCaptionsUrl'].$captionURI : $captionURI;
            $lang = (array_key_exists("media:subtitle:lang", $singleItem["caption"]) && $singleItem["caption"]["media:subtitle:lang"] !== null) ? ' lang="'.$singleItem["caption"]["media:subtitle:lang"].'"' : "" ;
            //$type = (array_key_exists("media:subtitle:type", $singleItem["caption"]) && $singleItem["caption"]["media:subtitle:type"] !== null) ? ' type="'.$captionType.'"' : "" ;
            $type = "";

            $itemString .= '<media:subtitle url="'.$captionsConversion.'"'.$lang.$type.' />';
        endif; 
        $itemString .= (array_key_exists("media:source_id", $singleItem)) ? '<media:source_id>'.$singleItem["media:source_id"].'</media:source_id>':'';
        $itemString .= (array_key_exists("media:country", $singleItem)) ? '<media:country>'.$singleItem["media:country"].'</media:country>':'';
        // series details
        $itemString .= (array_key_exists("media:series_id", $singleItem)) ? '<media:series_id>'.$singleItem["media:series_id"].'</media:series_id>':'';
        $itemString .= (array_key_exists("media:series_id", $singleItem)) ? '<meta name="series_sourceid" value="'.$singleItem["media:series_id"].'"/>' : '';
        $itemString .= (array_key_exists("media:season", $singleItem)) ? '<media:season>'.$singleItem["media:season"].'</media:season>':'';
        $itemString .= (array_key_exists("media:episode", $singleItem)) ? '<media:episode>'.$singleItem["media:episode"].'</media:episode>':'';
        if (array_key_exists("meta", $singleItem) && count($singleItem) >= 1)
        {
            //str_replace("&", "&amp;", $singleItem["media:title"])
            $itemString .= (array_key_exists("media_type", $singleItem["meta"])) ? '<meta name="media_type" value="'.str_replace("&", "&amp;", $singleItem["meta"]["media_type"]).'"/>' : '';
            $itemString .= (array_key_exists("genre", $singleItem["meta"])) ? '<meta name="genre" value="'.str_replace("&", "&amp;", $singleItem["meta"]["genre"]).'"/>' : '';
            $itemString .= (array_key_exists("tagline", $singleItem["meta"])) ? '<meta name="tagline" value="'.str_replace("&", "&amp;", $singleItem["meta"]["tagline"]).'"/>' : '';
            $itemString .= (array_key_exists("copyright", $singleItem["meta"])) ? '<meta name="copyright" value="'.str_replace("&", "&amp;", $singleItem["meta"]["copyright"]).'"/>' : '';
            $itemString .= (array_key_exists("studio", $singleItem["meta"])) ? '<meta name="studio" value="'.str_replace("&", "&amp;", $singleItem["meta"]["studio"]).'"/>' : '';
            $itemString .= (array_key_exists("imdb_id", $singleItem["meta"])) ? '<meta name="imdb_id" value="'.str_replace("&", "&amp;", $singleItem["meta"]["imdb_id"]).'"/>' : '';
            $itemString .= (array_key_exists("production_companies", $singleItem["meta"]) && is_array($singleItem["meta"]["production_companies"])) ? '<meta name="production_companies" value="'.str_replace("&", "&amp;", implode(",", $singleItem["meta"]["production_companies"])).'"/>' : '';
            $itemString .= (array_key_exists("chapters", $singleItem["meta"]) && is_array($singleItem["meta"]["chapters"])) ? '<meta name="chapters" value="'.str_replace("&", "&amp;", implode(",", $singleItem["meta"]["chapters"])).'"/>' : '';            
        }      
        $itemString .= '</media:content>';
    $itemString .= '</item>';  
    return $itemString;   
}
# convert subtitle type
function convertType ($type)
{
    return (false !== $type && $type === "text/plain") ? "application/vtt" : "text/plain";
}
# write post ingest content
function postIngest ($matchSkuID,$postIngest)
{
    $jsonData = json_encode($postIngest);
    writeThisData ($jsonData,$GLOBALS['mcon']['dir']['POSTINGEST'],$matchSkuID.".json"); 
}
# load json file and convert to array
function loadJSON ($file)
{
    $pathandfile = $file;
    $objectJSON = file_get_contents($pathandfile);
    $objects = json_decode($objectJSON, 1);
    return $objects;
}
# generate an items list of files
function generateItemsList ($path,$scope=".json")
{
    //echo "\tRunning: generateItemsList(): ".$path."\n";
    $output=null; $retval=null;   
    $fullpath = $GLOBALS['mcon']['data_folder'].$path;
    $cmd = "find ".$fullpath.". -type f -name '*".$scope."'";
    exec($cmd, $output, $retval);
    if (is_array($output) && !empty($output))
    {
        $newList = array();
        foreach ($output as $json)
        {
            $newList[] = str_replace($fullpath."./", $fullpath, $json);
        }
        return $newList;
    }
    return false;
}
# remove this file
function removeThisFile ($file)
{
    //echo "\tRunning: removeThisFile()\n";
    $output=null; $retval=null;
    $thisFile = $GLOBALS['mcon']['data_folder'].$file;
    if (is_file($thisFile))
    {
        exec('rm -f '.$thisFile, $output, $retval);
        echo "\t".$thisFile.((is_file($thisFile)) ? " is Still a File\n" : " is Deleted\n");
        return true;
    }
    echo "\t".$thisFile." did Not Exist!\n";
    return true;
}
# build local dir list of files
function buildLocalDirList ($dir)
{
    echo "\tRunning: buildLocalDirList()\n";
    $output=null; $retval=null;    
    $thisDir = $GLOBALS['mcon']['data_folder'].$dir;
    $cmd = "find ".$thisDir."/. -type f -name '*.yaml'";
    exec($cmd, $output, $retval);
    if (is_array($output) && !empty($output))
    {
        writeThisData ($output,$GLOBALS['mcon']['dir']['DIRLIST'],$GLOBALS['mcon']['stamp'].".json");
    }
}
# Write the segment
function writeThisSegment ($data,$filePath,$fileName)
{
    //echo "\tRunning: writeThisSegment()\n";
    $writeFile = $GLOBALS['mcon']['data_folder'].$filePath.$fileName;
    // Let's make sure the file exists and is writable first.
    if (is_writable($GLOBALS['mcon']['data_folder'].$filePath)) 
    {
        // open and append
        if (!$fp = fopen($writeFile, 'a+')) 
        {
             echo "\tCannot open file ($writeFile)\n";
             return false;
        }
        // Write $somecontent to our opened file.
        if (fwrite($fp, $data) === FALSE) 
        {
            echo "\tCannot write to file ($writeFile)\n";
            return false;
        }
        fclose($fp);
        return true;
    } 
    echo "\tThe file $writeFile is not writable\n";
    return false;
}
# Write the Iceberg data
function writeThisData ($data,$filePath,$fileName)
{
    $writeFile = $GLOBALS['mcon']['data_folder'].$filePath.$fileName;  
    removeThisFile ($filePath.$fileName);
    //echo "\tRunning: writeThisData()\n";
    # convert to json
    $cleanData = (is_array($data)) ? json_encode($data) : $data;
    // Let's make sure the file exists and is writable first.
    if (is_writable($GLOBALS['mcon']['data_folder'].$filePath)) 
    {
        if (!$fp = fopen($writeFile, 'x+')) 
        {
             echo "\tCannot open file ($writeFile)\n";
             return false;
        }
        if (fwrite($fp, $cleanData) === FALSE) 
        {
            echo "\tCannot write to file ($writeFile)\n";
            return false;
        }
        fclose($fp);
        return true;
    } 
    echo "\tThe file $writeFile is not writable\n";
    return false;
}
# parse the RAW dirlist data: Todo run aws s3 command line and parse output
function parseRawDIRLIST ($file, $dir)
{
    echo "\tRunning: parseRawDIRLIST()\n";

    # Iterate over the file in DIRLIST
    $path = $GLOBALS['mcon']['data_folder'].$dir;
    //echo $path.$file; exit;
    $lines = (is_file($path.$file) === true) ? file($path.$file) : false;
    $dataArray = array();
    $skuidsArray = array();
    // Loop through our array
    if (false === $lines): 
        echo "Lines: ".$lines."\n\n"; exit;
        echo "\nERROR: No Dir File List Found\n\n"; exit;
    else:
        echo "\nLIST Lines Found\n\n";
    endif;
    foreach ($lines as $line) 
    {
        $filetype = substr(trim($line), -5);
        if ($filetype == ".yaml")
        {
            $array = explode(" ", trim($line));
            $results = array_filter($array,"cleanArrayFilter"); $nodes = array_values($results);   
            $theFourthIndex = (array_key_exists("3", $nodes)) ? $nodes['3'] : false;
            $theFourthIndex = str_replace(array($GLOBALS['mcon']['data_folder'].$GLOBALS['mcon']['root_prefix'],$GLOBALS['mcon']['root_prefix']), "", $theFourthIndex);
            $explodedObject = (false !== $theFourthIndex) ? explode("/", $theFourthIndex) : false;
            $explodedSKU = (false !== $explodedObject['0']) ? explode("_", $explodedObject['0']) : false;
            
            $skuid = ($explodedSKU !== false && $explodedSKU['0'] !== "{SKU}") ? $explodedSKU['0'] : false; 
            if ($skuid !== "{SKU}" && false !== $skuid)
            {
                $dataArray[$skuid] = rtrim($theFourthIndex, "/");
                $skuidsArray[] = $skuid;
            }
        }
    }
    return array("data"=>$dataArray,"skuids"=>$skuidsArray);
}
# parse the dirlist data
function parseOBJECTS ($file, $uri)
{
    echo "\tRunning: parseOBJECTS()\n";
    $objectJSON = file_get_contents($GLOBALS['mcon']['data_folder'].$uri.$file);
    $objects = json_decode($objectJSON, 1);
    $skuidsArray = array();
    foreach ($objects as $skuid => $object)
    {
        $folderExplode = explode("/",$object);
        $folder = $folderExplode['0'];
        downloadYAML ($GLOBALS['mcon']['http_uri_prefix'].$object, $skuid);
        $skuidsArray[$skuid] = $folder;       
        echo "Resting Between Downloads\n"; sleep(2);
    }
    return $skuidsArray;
}
# parse the skuids
function parseSKUIDS ($file, $uri)
{
    echo "\tRunning: parseSKUIDS()\n";
    $path = $GLOBALS['mcon']['data_folder'].$uri;
    $skuidsJSON = file_get_contents($path.$file);
    $skuids = json_decode($skuidsJSON, 1);
    return $skuids;  
}
# parse assets and convert to xmlitems for individual valid skuids
function parseASSETS ($skuids, $uri)
{
    echo "\tRunning: parseASSETS()\n";
    $path = $GLOBALS['mcon']['data_folder'];
    foreach ($skuids as $skuid => $folder)
    {
        $file = $skuid.$GLOBALS['mcon']['yamlJSONFile'];
        $assetJSON = file_get_contents($path.$uri.$file);
        $asset = json_decode($assetJSON, 1); 
        switch ($asset['programming_type'])
        {
            case "Single Work":
                mapSingleToItem ($asset, $folder."/");
                break;
            case "Series":
                mapSingleToItem ($asset, $folder."/", true);
                break;
        }
    } 
}
# get director from cast and stringify it
function getDirector ($crews=false)
{
    //echo "\tRunning: getDirector()\n";
    $directorString = "";
    foreach ($crews as $key => $data)
    {
        $name           = (array_key_exists("name", $data)) ? $data['name'] : null;
        $credit           = (array_key_exists("credit", $data)) ? $data['credit'] : null;
        if (strtolower($credit) == "director"):
            $directorString = "Director: ".$name."\\r\\n";
            return $directorString;
        endif;
    }
    return $directorString;
}
# get actors list and build string
function getActors ($cast=false)
{
    //echo "\tRunning: getActors()\n";
    $actorsString = "";
    foreach ($cast as $key => $data)
    {
        $name           = (array_key_exists("name", $data)) ? $data['name'] : null;
        $actorsString .= $name.", ";
    }
    return ($actorsString !== "") ? "Actors: ". rtrim($actorsString, ", ") : "";
}
# map single video to item (both single work and series episodes)
function mapSingleToItem ($asset,$folder,$series=false)
{
    echo "\tRunning: mapSingleToItem()\n";
    
    $xmlitem = array();
    $xmlitem["meta"] = array();
    $postingest = array();
    $matchSkuID = $asset['sku'];
    // extract director, actors into description.
    $directorString         = (array_key_exists("crew", $asset)) ? getDirector($asset['crew']) : "";
    $actorsString           = (array_key_exists("cast", $asset)) ? getActors($asset['cast']) : "";
    $descAddOn              = (($directorString !== "" || $actorsString !== "") ? "\\r\\n" : "").$directorString.$actorsString;
    // media
    $xmlitem['channel:title']           = (array_key_exists("genre", $asset)) ? $asset['genre'] : null;
    $xmlitem['media:title']             = (array_key_exists("title", $asset)) ? $asset['title'] : null;
    $xmlitem['media:description']       = (array_key_exists("description", $asset)) ? $asset['description'].$descAddOn : null;
    $xmlitem['media:category']          = (array_key_exists("secondary_genres", $asset)) ? $asset['secondary_genres'] : null;
    $xmlitem['media:source_id']         = (array_key_exists("sku", $asset)) ? $asset['sku'] : null;
    $xmlitem['media:country']           = (array_key_exists("country_of_origin", $asset)) ? $asset['country_of_origin'] : null;
    $xmlitem['media:keywords']          = (array_key_exists("tags", $asset)) ? $asset['tags'] : null;
    // items
    $xmlitem['item:guid']               = (array_key_exists("sku", $asset)) ? $asset['sku'] : null;
    $xmlitem['item:pubDate']            = (array_key_exists("production_year", $asset)) ? $asset['production_year'] : null;
    // archive for other uses
    $xmlitem["meta"]['genre']                  = (array_key_exists("genre", $asset)) ? $asset['genre'] : null;
    $xmlitem["meta"]['tagline']                = (array_key_exists("tagline", $asset)) ? $asset['tagline'] : null;
    $xmlitem["meta"]['copyright']              = (array_key_exists("copyright", $asset)) ? $asset['copyright'] : null;
    $xmlitem["meta"]['studio']                 = (array_key_exists("studio", $asset)) ? $asset['studio'] : null;
    $xmlitem["meta"]['imdb_id']                = (array_key_exists("imdb_id", $asset)) ? $asset['imdb_id'] : null;
    $xmlitem["meta"]['production_companies']   = (array_key_exists("production_companies", $asset)) ? $asset['production_companies'] : null;
    $xmlitem["meta"]['chapters']                   = (array_key_exists("chapters", $asset)) ? $asset['chapters'] : null;
    $xmlitem["meta"]['ratings']                    = (array_key_exists("ratings", $asset)) ? $asset['ratings'] : null;
    $xmlitem["meta"]['avails']                     = (array_key_exists("avails", $asset)) ? $asset['avails'] : null;
    $xmlitem["meta"]['crew']                     = (array_key_exists("crew", $asset)) ? $asset['crew'] : null;
    $xmlitem["meta"]['cast']                     = (array_key_exists("cast", $asset)) ? $asset['cast'] : null;
    #postingest
    $postingest['cast']                     = (array_key_exists("cast", $asset)) ? $asset['cast'] : null;
    $postingest['crew']                     = (array_key_exists("crew", $asset)) ? $asset['crew'] : null;
    $postingest['avails']                     = (array_key_exists("avails", $asset)) ? $asset['avails'] : null;
    $postingest['ratings']                    = (array_key_exists("ratings", $asset)) ? $asset['ratings'] : null;
    $postingest['chapters']                    = (array_key_exists("chapters", $asset)) ? $asset['chapters'] : null;
    // content
    $xmlitem["video"] = array();
    $xmlitem["video"]['media:content:duration']  = (60*$asset['running_time']); // presuming minutes
    $xmlitem["video"]['media:content:url']       = null;
    $xmlitem["video"]['media:content:width']     = null;
    $xmlitem["video"]['media:content:height']    = null;
    $xmlitem["image"] = array();
    $xmlitem["image"]['media:thumbnail:url']       = null;
    $xmlitem["image"]['media:thumbnail:width']     = null;
    $xmlitem["image"]['media:thumbnail:height']    = null;   
    $xmlitem["caption"] = array();
    $xmlitem["caption"]['media:subtitle:url']      = null;
    $xmlitem["caption"]['media:subtitle:type']     = "text/plain";
    $xmlitem["caption"]['media:subtitle:lang']     = "en-us";   
    $xmlitem["meta"]['media_type'] = "single";
    
    // if its a series do some special things
    if (false !== $series)
    {
        $filesArray = extractFiles($asset['files'],$matchSkuID,true); 
        $filesArray["folder"] = $folder;
        $postingest["videos"] = $filesArray["videos"];
        $postingest["images"] = $filesArray["images"];  
        // determine if it has trailer videos here, since it will not be returning
        
        $hasTrailerVideo = (array_key_exists($matchSkuID, $filesArray["videos"])) ? ((array_key_exists("trailer", $filesArray["videos"][$matchSkuID])) ? true : false) : false ;
        
        $enclosure = array($matchSkuID=>$filesArray);
        writeThisData ($enclosure,$GLOBALS['mcon']['dir']['FILES'],$matchSkuID.$GLOBALS['mcon']['filesJSONFile']);        
        
        $episodes = $asset["episodes"];
        foreach ($episodes as $key => $episode)
        {
            $xmlitem["meta"]['media_type'] = "episode";
            $episodeSkuID                       = (array_key_exists("sku", $episode)) ? $episode['sku'] : null;
            $xmlitem['item:guid']               = $matchSkuID."-".$episodeSkuID;
            $xmlitem['media:title']             = (array_key_exists("name", $episode)) ? $episode['name'] : null;
            $xmlitem['media:description']       = (array_key_exists("description", $episode)) ? $episode['description'].$descAddOn : null;
            $xmlitem['media:episode']           = (array_key_exists("episode_number", $episode)) ? $episode['episode_number'] : null;
            $xmlitem['media:season']            = (array_key_exists("season_number", $episode)) ? $episode['season_number'] : null;
            $xmlitem["video"]['media:content:duration']  = (60*$asset['running_time']);
            $xmlitem['media:series_id']         = $matchSkuID;
            $xmlitem['media:source_id']         = $episodeSkuID;
            $node = "S".$episode['season_number']."E".$episode['episode_number'];
            $node2 = "S0E0";
            if (array_key_exists($node, $filesArray["images"])):
                $filesArray["images"][$episodeSkuID] = $filesArray["images"][$node];
                $postingest["EpisodeSeason"] = $node;
            elseif (array_key_exists($node2, $filesArray["images"])):
                $filesArray["images"][$episodeSkuID] = $filesArray["images"][$node2];
                $postingest["EpisodeSeason"] = $node2;
            endif;
            $itemFiles = mapItemFiles($filesArray,$episodeSkuID,$folder);
            if (false !== $itemFiles)
            {
                foreach ($itemFiles as $fileKey => $fileValue)
                {
                    $xmlitem[$fileKey] = $fileValue;
                }
                $xmlitem["video"]['media:content:duration']  = (60*$asset['running_time']);
                writeThisData ($xmlitem,$GLOBALS['mcon']['dir']['XMLITEMS'],$episodeSkuID.$GLOBALS['mcon']['xmlItemFile']);
                postIngest($episodeSkuID,$postingest);
            }
        }
        // use this to build an item for the series and use the trailer as the main video
        if (false !== $hasTrailerVideo)
        {
            $xmlitem["meta"]['media_type'] = "series";
            $xmlitem["video"]['media:content:url'] = $folder.$filesArray["videos"][$matchSkuID]["trailer"];
            $xmlitem['media:title']             = (array_key_exists("title", $asset)) ? $asset['title'] : null;
            $xmlitem['media:description']       = (array_key_exists("description", $asset)) ? $asset['description'].$descAddOn : null;      
            $xmlitem['item:guid']               = $matchSkuID;
            $xmlitem['media:source_id']         = $matchSkuID;
            $xmlitem["caption"]['media:subtitle:url']      = null;
            $xmlitem["caption"]['media:subtitle:type']     = null;
            $xmlitem["caption"]['media:subtitle:lang']     = null;
            $xmlitem['media:episode']           = null;
            $xmlitem['media:season']            = null;            
            writeThisData ($xmlitem,$GLOBALS['mcon']['dir']['XMLSERIESITEMS'],$matchSkuID.$GLOBALS['mcon']['xmlItemSeriesFile']);
            
            $postingest["EpisodeSeason"] = "Series";
            postIngest($matchSkuID,$postingest);
        }        
    }
    else
    {
        $filesArray = extractFiles($asset['files'],$matchSkuID,false);
        $filesArray["folder"] = $folder;
        // determine if it has trailer videos here, since it will not be returning
        $hasTrailerVideo = (array_key_exists("trailer", $filesArray["videos"]["$matchSkuID"])) ? true : false ;
        $itemFiles = mapItemFiles($filesArray,$matchSkuID,$folder);
        $postingest["videos"] = $filesArray["videos"];
        $postingest["images"] = $filesArray["images"];
        
        if (false !== $itemFiles)
        {
            foreach ($itemFiles as $fileKey => $fileValue)
            {
                $xmlitem[$fileKey] = $fileValue;
            }
            writeThisData ($xmlitem,$GLOBALS['mcon']['dir']['XMLITEMS'],$matchSkuID.$GLOBALS['mcon']['xmlItemFile']);
            $enclosure = array($matchSkuID=>$filesArray);
            writeThisData ($enclosure,$GLOBALS['mcon']['dir']['FILES'],$matchSkuID.$GLOBALS['mcon']['filesJSONFile']);
            postIngest($matchSkuID,$postingest);
            if (false !== $hasTrailerVideo)
            {
                $xmlitem["meta"]['media_type'] = "trailer";
                $xmlitem["meta"]['trailer_for'] = $asset['sku'];
                $xmlitem['media:title']             = "Trailer: ".$xmlitem['media:title'];
                $xmlitem["video"]['media:content:url'] = $folder.$filesArray["videos"]["$matchSkuID"]["trailer"];
                $xmlitem['item:guid']               = (array_key_exists("sku", $asset)) ? $asset['sku']."-T" : "";
                $xmlitem['media:source_id']         = (array_key_exists("sku", $asset)) ? $asset['sku']."-T" :  "";
                $xmlitem["caption"]['media:subtitle:url']      = null;
                $xmlitem["caption"]['media:subtitle:type']     = null;
                $xmlitem["caption"]['media:subtitle:lang']     = null;               
                writeThisData ($xmlitem,$GLOBALS['mcon']['dir']['XMLTRAILERITEMS'],$matchSkuID.$GLOBALS['mcon']['xmlItemTrailerFile']);
                postIngest($asset['sku']."-T",$postingest);
            }
        }
    }
    // send over post ingest data
    
}
# map each items files back into the complete array 
function mapItemFiles ($filesArray,$matchSkuID,$folder)
{
    //echo "\tRunning: mapItemFiles()\n";
    $hasMainImage = false; $hasSecondImage = false; $hasEnSRT = false; $hasOtherMainImage = false;
    
    $hasVideos = (array_key_exists("videos", $filesArray)) ? true : false ;
    $hasImages = (array_key_exists("images", $filesArray)) ? true : false ;
    $hasVideoSKUID = (array_key_exists($matchSkuID, $filesArray["videos"])) ? true : false ;
    $hasImageSKUID = (array_key_exists($matchSkuID, $filesArray["images"])) ? true : false ;
    
    if (false !== $hasVideos && false !== $hasVideoSKUID && false !== $hasImageSKUID)
    {
        $hasMainVideo = (array_key_exists("main", $filesArray["videos"]["$matchSkuID"])) ? true : false ;
        $hasSRT = (array_key_exists("text", $filesArray["videos"]["$matchSkuID"])) ? true : false ;
        
        $hasLandscapeImages = (array_key_exists("landscape", $filesArray["images"]["$matchSkuID"])) ? true : false ;
        $hasOtherImages = (array_key_exists("other", $filesArray["images"]["$matchSkuID"])) ? true : false ;
        if (false !== $hasLandscapeImages) 
        {
            $hasMainImage = (array_key_exists("16x9", $filesArray["images"]["$matchSkuID"]["landscape"])) ? true : false ;
            $hasSecondImage = (array_key_exists("4x3", $filesArray["images"]["$matchSkuID"]["landscape"])) ? true : false ;
        }
        if (false !== $hasOtherImages)
        {
            $hasOtherMainImage = (array_key_exists("0", $filesArray["images"]["$matchSkuID"]["other"])) ? true : false ;
        }
        if (false !== $hasSRT) 
        {
            $hasEnSRT = (array_key_exists("en", $filesArray["videos"]["$matchSkuID"]["text"])) ? true : false ;
        }        
        
        $itemFiles = array();
        $itemFiles["video"] = array(); $itemFiles["image"] = array(); $itemFiles["caption"] = array();
        if (false !== $hasMainVideo)
        {
            $itemFiles["video"]["media:content:url"] = $folder.$filesArray["videos"]["$matchSkuID"]["main"];
        }
        if (false !== $hasEnSRT)
        {
            $itemFiles["caption"]["media:subtitle:url"]     = $folder.$filesArray["videos"]["$matchSkuID"]["text"]["en"];
            $itemFiles["caption"]["media:subtitle:lang"]    = "en-us";
            $itemFiles["caption"]['media:subtitle:type']    = "text/plain";
        } 
        if (false !== $hasMainImage):
            $itemFiles["image"]["media:thumbnail:url"] = $folder.$filesArray["images"]["$matchSkuID"]["landscape"]["16x9"];
        elseif (false !== $hasSecondImage):
            $itemFiles["image"]["media:thumbnail:url"] = $folder.$filesArray["images"]["$matchSkuID"]["landscape"]["4x3"];
        elseif (false !== $hasOtherMainImage):
            $itemFiles["image"]["media:thumbnail:url"] = $folder.$filesArray["images"]["$matchSkuID"]["other"]["0"];
        endif;
        return $itemFiles;
    }
    else
    {
        echo "\n\nSKU: ".$matchSkuID." has NO Files\n\n"; 
    }
    return false;
}
# extract the actual files from the yaml
function extractFiles ($files,$matchSkuID,$series=false)
{
    //echo "\tRunning: extractFiles()\n";
    $videosArray = array();
    $imagesArray = array();
    // map videos
    $videos = $files['videos'];
    foreach ($videos as $key => $file)
    {
        $type = $file['type'];
        $filename = $file['filename'];  
        
        $skuidExplode = ($filename !== "") ? explode("_", $filename) : false;
        // account for the series node prior to skuid
        if (false !== $series):
            $skuid = (false !== $skuidExplode) ? $skuidExplode['1'] : false;
            $skuid = (false !== $skuidExplode && $skuidExplode['0'] == $matchSkuID) ? $matchSkuID : $skuid;
        else:
            $skuid = (false !== $skuidExplode) ? $skuidExplode['0'] : false;
            echo (trim($skuid) === trim($matchSkuID)) ? "" : "MISMATCH: ".$matchSkuID." - ".$skuid."\n";
        endif;
        
        if (false !== $skuid)
        {
            $videosArray[$skuid] = (array_key_exists($skuid, $videosArray)) ? $videosArray[$skuid] : array();
            $videosArray[$skuid]['text'] = (array_key_exists("text", $videosArray[$skuid])) ? $videosArray[$skuid]['text'] : array();
            switch ($type)
            {
                case "main":
                    $videosArray[$skuid]['main'] = $filename;
                    break;
                case "episode":
                    $videosArray[$skuid]['main'] = $filename;
                    break;
                case "text_track":
                    $ccFile = substr($filename, -6); $ccLang = substr($ccFile, 0, 2);
                    $videosArray[$skuid]['text'][$ccLang] = $filename;
                    break;
                case "trailer":
                    $videosArray[$skuid]['trailer'] = $filename;
                    break;
            }
        }
    }
    // map images
    $images = $files['images'];
    foreach ($images as $key => $file)
    {
        $type = $file['type'];
        $filename = $file['filename'];
        $skuidExplode = ($filename !== "") ? explode("_", $filename) : false;
        // account for the series node prior to skuid
        $skuid = (false !== $skuidExplode) ? $skuidExplode['0'] : false;
        if (false !== $skuid)
        {
            $imagesArray[$skuid] = (array_key_exists($skuid, $imagesArray)) ? $imagesArray[$skuid] : array();
            $imagesArray[$skuid]['other'] = (array_key_exists("other", $imagesArray[$skuid])) ? $imagesArray[$skuid]['other'] : array();
            $imagesArray[$skuid]['portrait'] = (array_key_exists("portrait", $imagesArray[$skuid])) ? $imagesArray[$skuid]['portrait'] : array();
            $imagesArray[$skuid]['landscape'] = (array_key_exists("landscape", $imagesArray[$skuid])) ? $imagesArray[$skuid]['landscape'] : array();
            switch ($type)
            {
                case "other":
                    $imagesArray[$skuid]['other'][] = $filename;
                    break;
                case $type:
                    $typeExplode = ($type !== "") ? explode("_", $type) : false;
                    $view = ($typeExplode['0'] == "portrait" || $typeExplode['0'] == "landscape") ? $typeExplode['0'] : "other";
                    $WxH = $typeExplode['1'];
                    $imagesArray[$skuid][$view][$WxH] = $filename;
                    break;
            }
        }
        else
        {
            echo "\tERROR: SKUID == FALSE :".$skuid."\n"; exit;
        }
    }
    return array("images"=>$imagesArray,"videos"=>$videosArray);    
}
# download the YAML and write to json
function downloadYAML ($file, $skuid)
{
    echo "\tRunning: downloadYAML()\n";
    $writeFile = $skuid.$GLOBALS['mcon']['yamlJSONFile'];
    $writeYamlFile = $skuid.$GLOBALS['mcon']['yamlFile'];
    $fileData = file_get_contents($file);

    // lets check that we have http access to the file, otherwise maybe a cp using aws-cli
    $hasERROR = strpos($fileData, "<Error><Code>"); // needs improvement     
    if ($hasERROR === false):
        echo "\t\e[1;32mPASS\e[0m: COPYing File: ".$skuid.": ".$file."\n";
    else:
        echo "\t\e[1;31mERROR\e[0m: COPYing File: ".$skuid.": ".$file."\n";
    endif;    
    writeThisData ($fileData,$GLOBALS['mcon']['dir']['YAML'],$writeYamlFile);  
}
# download captions for each skuid
function downloadCaptions ()
{
    echo "\tRunning: downloadCaptions()\n";
    $path = $GLOBALS['mcon']['dir']['FILES'];
    $list = generateItemsList ($path,".json");
    foreach ($list as $key => $file)
    {
        if (is_file($file)):
            $fileData = file_get_contents($file);
            $fileArray = json_decode($fileData, 1);
            //print_r($fileArray); exit;
            foreach ($fileArray as $skuid => $array)
            {
                $captionsPath = $GLOBALS['mcon']['dir']['CAPTIONS'].$skuid."/";
                // check for the dir
                $isDir = buildRoom($captionsPath);                
                $folder = (array_key_exists("folder", $array)) ? $array["folder"] : "";
                $images = $array["images"];
                $videos = $array["videos"];
                foreach ($videos as $itemid => $value)
                {
                    if (array_key_exists("text", $value))
                    {
                        $captions = $value["text"];
                        if (false !== $isDir):
                            cleanRoom($captionsPath,".json");
                            foreach ($captions as $lang => $caption)
                            {
                                $url = $GLOBALS['mcon']["http_root_prefix"].$folder.$caption;
                                downloadCaption($url,$captionsPath,$caption);
                                sleep(1);
                            }
                        endif;
                    }
                }
            }
        endif;
    }
}
function downloadCaption ($url,$writePath,$name)
{
    echo "\tRunning: downloadCaption()\n";
    $fileData = file_get_contents($url);
    //$GLOBALS['mcon']['data_folder']
    // lets check that we have http access to the file, otherwise maybe a cp using aws-cli
    $hasERROR = strpos($fileData, "<Error><Code>"); // needs improvement     
    if ($hasERROR === false):
        echo "\t\e[1;32mPASS\e[0m: COPYing Captions: ".$name."\n";
    else:
        echo "\t\e[1;31mERROR\e[0m: COPYing Captions: ".$name."\n";
    endif;    
    writeThisData ($fileData,$writePath,$name);  
}
# parse the yaml file into an array, shold be cleaned first.
function parseYAML ($skuids)
{
    echo "\tRunning: parseYAML()\n";
    //print_r($skuids); exit;
    foreach ($skuids as $skuid => $skuidpath)
    {
        $file = $GLOBALS['mcon']['data_folder'].$GLOBALS['mcon']['dir']['CLEANYAML'].$skuid.$GLOBALS['mcon']['yamlFile']; // dir, dir
        $writeFile = $skuid.$GLOBALS['mcon']['yamlJSONFile'];
        $fileData = file_get_contents($file);
        try {              
    $yamlData = yaml_parse($fileData,0);
    $yamlJson = json_encode($yamlData);
            writeThisData ($yamlJson,$GLOBALS['mcon']['dir']['YMLJSON'],$writeFile);
        } catch (Exception $ex) {
            echo "ERROR: ".$ex;
            echo "\n\n"; exit;
        }
    }
}
function cleanYAML ($skuids)
{
    echo "\tRunning: cleanYAML()\n";
    foreach ($skuids as $skuid => $skuidpath)
    {
        $file = $GLOBALS['mcon']['data_folder'].$GLOBALS['mcon']['dir']['YAML'].$skuid.$GLOBALS['mcon']['yamlFile'];
        $cleanFile = $GLOBALS['mcon']['dir']['CLEANYAML'].$skuid.$GLOBALS['mcon']['yamlFile'];// dir, dir
        $lines = file($file);
        $quotes = 0;
        removeThisFile ($cleanFile);
        foreach ($lines as $key => $line) 
        {
            $line = rtrim($line," ");
            $quotes = substr_count($line, '"');
            if ($quotes >= 3)
            {
                $posFirst = strpos($line, '"'); 
                $removeThisMany = ($quotes - 2);
                $posNext = $posFirst;
                do 
                {
                    $posThis = strpos($line, '"', $posNext+1); 
                    $line = substr_replace($line, '', $posThis, 1);
                    $posNext = $posThis;
                    --$removeThisMany;
                } while ($removeThisMany > 0);                
            }
            writeThisSegment ($line,$GLOBALS['mcon']['dir']['CLEANYAML'],$skuid.$GLOBALS['mcon']['yamlFile']);
        }
    }    

}

/********* BOF S3 FUNCTIONS LIST **********************************************************/
# get S3 Env. Vars for this script session only
function getS3EnvVars ()
{
    echo "\tRunning: getS3EnvVarS()\n";
    //echo $AWS_ACCESS_KEY_ID;
    $exportCMDS = $GLOBALS['mcon']['s3_config'];
    $envVars = array();
    foreach ($exportCMDS as $key => $value)
    {
        $envVar = getS3EnvVar ($key);
        if (false !== $envVar)
        {
            $envVars[$key] = $envVar;
        }          
    }
    $GLOBALS['mcon']['s3_env_config'] = $envVars;
}
# get single S3 Var for this script session only
function getS3EnvVar ($thisVar="AWS_DEFAULT_REGION")
{
    echo "\tRunning: getS3EnvVar()\n";
    return getenv($thisVar);  
}
# set S3 Env. Vars for this script session only
function setS3EnvVars ()
{
    echo "\tRunning: setS3EnvVars()\n";
    $exportCMDS = $GLOBALS['mcon']['s3_config'];
    foreach ($exportCMDS as $key => $value)
    {
        setS3EnvVar ($key, $value);         
    } 
}
# set the single env var for this script session only
function setS3EnvVar ($key="AWS_DEFAULT_OUTPUT", $value="json")
{
    //echo "\tRunning: setS3EnvVar()\n";
    $cmd = "$key=$value";
    putenv($cmd);
}
# s3 functions
function listS3BucketFiles($bucket) 
{      
    echo "\tRunning: listS3BucketFiles()\n";
    // aws s3 ls s3://zype-filmhub --recursive --human-readable --summarize | grep '\.yaml$' 
    
    $output=null; $retval=null;
    //$cmd = "aws s3 ls s3://".$bucket." --recursive --human-readable --summarize | grep '\.yaml$'";
    $cmd = "aws s3 ls s3://".$bucket." --recursive --summarize --page-size 400 | grep '\.yaml$'";
    echo "\t".$cmd."\n";
    exec($cmd, $output, $retval);
    if (is_array($output) && !empty($output))
    {
        $list = implode("\n", $output);
        $dirFile = $GLOBALS['mcon']['stamp'].".txt";
        writeThisData ($list,$GLOBALS['mcon']['dir']['DIRLIST'],$dirFile);
        // lets add the new file as the main list to use.
        $GLOBALS['mcon']['dirFile'] = $dirFile;
    }    
}
# filter array for empties
function cleanArrayFilter ($var)
{
    return (trim($var) !== NULL && trim($var) !== FALSE && trim($var) !== "");
}
# cp file
function cpFileToS3Bucket() 
{                    
    //aws s3 cp "$www_document_root/$asset" "s3://$cdnBucket/$asset" 
    //--grants 
    //  "read=uri=http://acs.amazonaws.com/groups/global/AllUsers" 
    //  "full=uri=http://acs.amazonaws.com/groups/global/AuthenticatedUsers" 
    //--region 
    //  "$cf_s3_production_bucket_region"
}
/********* EOF S3 FUNCTIONS LIST **********************************************************/

/********* EOF FUNCTIONS LIST ************************************************************/
