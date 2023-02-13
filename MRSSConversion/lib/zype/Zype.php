<?php defined('BASEPATH') OR exit('No direct script access allowed');
require(BASEPATH."/lib/zype/API.php");

class Zype extends ZypeAPI {
    
    public $action = false;
  
    public function __construct() 
    {   
        parent::__construct($apikey,$roapikey,$siteid);
    }
    
    
    //https://api.zype.com/categories?api_key=ZkLpE4GqNl3wAVupf8mHgfrZiM0dsrGYY8AMJsAvJEtVe6SW
    
    
    public function setAPIKey ()
    {
    
    }
    public function getAPIKey ()
    {
        
    }
    
    public function setROAPIKey ()
    {
    
    }
    public function getROAPIKey ()
    {
        
    }
    
    public function setSiteID ()
    {
    
    }
    public function getSiteID ()
    {
        
    }
    
    public function setVideoID ()
    {
    
    }
    public function getVideoID ()
    {
        
    }
    
    public function setSourceID ()
    {
    
    }
    public function getSourceID ()
    {
        
    }
    
}

