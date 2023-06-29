<?php
    
global $db_prefix;
$db_prefix = "ms21_";
define('PATH_TO_WP_ROOT', "../");


//can set true or ID of team to log in as
$testing = false;
$testing_runtime = false;

$url_auth = "https://manzelska.setkani.org/ms/auth.php";
$url_index = "https://manzelska.setkani.org/ms/";
$url_submit = "https://manzelska.setkani.org/ms/submit.php";

define('CURR', '&#8524;');
setlocale(LC_TIME, "cs_CZ");

$mountains = "[[1,9],[3,2],[5,8],[7,4],[9,1]]";
$mountainSpecs = '{color:"brown", image:"mountain.png"}';

//!in utc
$auction_start = strtotime('today 7:30');
$auction_end = strtotime('today 22:00');
$time_shift = 7200;

function getTStamp() {
 	return date('Y-m-d H:i:s', time());  
}

function getTStampDb() {
 	return date("'Y-m-d H:i:s'", time());  
}


//common requires
require_once(PATH_TO_WP_ROOT . "wp-load.php");


?>