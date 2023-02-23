<?php ini_set('display_errors', 1); // set this to 0 when running production

$video_zype_signature       = '';
$video_zype_timestamp       = floor(microtime(true));
// get the address of the user viewing the page
$global_vars                = filter_input_array(INPUT_SERVER);
$video_zype_ipaddress       = $global_vars['REMOTE_ADDR'];

// set your secret signature key: found in your Account > Settings
$video_zype_signkey         = ""; // secret id
// this needs to be set to your Player API Key, and an APP Key
$video_zype_apikey          = ""; // api_key use your player key
$video_zype_appkey          = ""; // app_key, must be used to access api

// set the method type to generate a signature on, using app_key or api_key
$video_key_type             = "api"; // set to app or api (only used for generation and must match player embed)

// set a static video id for testing
$video_zypeid               = "63e4a2ed5f91df0001c3d64f";

// set the video api url to get the video content, metadata, etc.
$api_getvideo_url           = 'https://api.zype.com/videos/'.$video_zypeid.'?app_key=' . $video_zype_appkey;

// Read JSON file
$json_data = file_get_contents( $api_getvideo_url );

// conditional on the json_data
if ( ! empty( $json_data ) ):

    // Decode JSON data into PHP array
    $response_data      = json_decode( $json_data );

    // get response video id and title
    $video_zype_apiid   = $response_data->response->_id;
   
    // evaluate all values as string (should be ksort order)
    $zype_signature_array = array();
    
    // quick swith on the type of key (app_key or api_key)
    switch ($video_key_type) {
        case "app":
            $zype_signature_array['app_key']    = strval($video_zype_appkey); // array value for signature
            $zype_signature_method              = 'app_key=' . $video_zype_appkey; // method for video embed script
            break;
        case "api":
            $zype_signature_array['api_key']    = strval($video_zype_apikey); // array value for signature
            $zype_signature_method              = 'api_key=' . $video_zype_apikey; // method for video embed script
            break;
    }
    
    // set some values in an array, or create a callback to set as strings
    $zype_signature_array['remote_ip'] = strval($video_zype_ipaddress);
    $zype_signature_array['ts'] = strval($video_zype_timestamp);
    $zype_signature_array['video_id'] = strval($video_zype_apiid);   
    
    // loop to further sanitize content and urlencode, and lowercase
    $signature_string = array();
    foreach ($zype_signature_array as $param_name => $param_value)
    {
        $param_value = trim(strtolower($param_value));
        $signature_string[] = $param_name."=".urlencode($param_value);
    }
    
    // implode the array with an ampersand 
    $signature_query = implode("&", $signature_string);

    // downcase the full string, to ensure we have everything lowered
    $video_zype_signature_string = strtolower( $signature_query );

    // Using the signing key create a binary SHA-256 HMAC digest
    // Note: the signature key is found in your Account Settings
    $video_zype_signature_hash = hash_hmac( 'sha256', $video_zype_signature_string, $video_zype_signkey, true );

    // URL safe Base64 encode the digest
    $video_zype_base64 = strtr( base64_encode( $video_zype_signature_hash ), '+/', '-_' );
    
    // required for base64 url encoding.
    $video_zype_signature = str_replace('=', '%3D', $video_zype_base64);
?>

    <div id="zype_<?php echo $video_zype_apiid; ?>"></div>
    <script src="https://player.zype.com/embed/<?php echo $video_zype_apiid; ?>.js?<?php echo $zype_signature_method; ?>&signature=<?php echo trim($video_zype_signature); ?>&ts=<?php echo $zype_signature_array['ts']; ?>" type="text/javascript"></script>    

<?php 
endif;