<?php defined('BASEPATH') OR exit('No direct script access allowed');
require(BASEPATH."/lib/zype/API.php");

class Zype extends ZypeAPI {
    
    public $action = false;
  
    public function __construct($apikey,$roapikey,$siteid) 
    {   
        parent::__construct($apikey,$roapikey,$siteid);
    }
    
    
    //https://api.zype.com/categories?api_key=ZkLpE4GqNl3wAVupf8mHgfrZiM0dsrGYY8AMJsAvJEtVe6SW
    
}

