<?php defined('BASEPATH') OR exit('No direct script access allowed');

$GLOBALS['mcon']                     = array (); // yep, an array
$GLOBALS['mcon']['stamp']            = date("YdmHis"); // simple timestamp to avoid collisions.
$GLOBALS['mcon']['confdir']          = "conf/"; // need to set this path once here. no other paths should be set here.
$GLOBALS['mcon']['new_data_onrun']   = false; // turn this off to set, so its turned in implicitly during run
$GLOBALS['mcon']['main_data_folder'] = null; // this gets populated if sessions are used.
$GLOBALS['mcon']['pathispassed']     = false; // another check system on if a previous session is used again.
$GLOBALS['mcon']['passedvalidlist']  = false; // if the list being used is valid, populate once verified.
// set some functions to check for, quick and dirty
$GLOBALS['mcon']['sysinit_functions']  = array (
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