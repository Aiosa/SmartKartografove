<?php

session_start();
define('OTP_SYNC', 50);

?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CardCassone</title>
  <link rel="stylesheet" href="style.css" />
</head>

<body>
<br>


<?php

require_once "config.php";
global $wpdb, $db_prefix;

$login = isset($_GET["login"]) ? $_GET["login"] : null;
$key = isset($_GET["key"]) ? $_GET["key"] : null;


// ATTEMPT TO AUTHENTICATE
if (isset($login) && isset($key)) {    
   
   require_once "ext/hotp.php";

    $qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}teams WHERE login='%s'", $login));

    if ($qry) {
        $qry = $qry[0]; 
    } else {
       echo '<h2 style="text-align: center;">Něco se nepodařilo. Zkus akci znova.</h2>';
       exit();
    }

    
    if(checkHotpAndUpdate($wpdb, $db_prefix, $key, $qry->hotp, $qry->counter, $login)){
        $_SESSION["login"] = $qry;       

        $qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}session WHERE team_id='%s' LIMIT 1", $_SESSION["login"]->id));
        if ($qry) {
           $sessionCmd = array("type"=>"NONE");

           $ses = $qry[0];
           $cmd = json_decode($ses->command);

           switch($cmd->type) {
               case 'PURCHASE_AUCTION':
                   if($_SESSION["tstamp"] && $_SESSION["tstamp"] < time()) {
                       //re-request authentication
                       $_SESSION["tstamp"] = time() + 7200;
                       header("Location: $url_auth");
                       exit();
                   }
                   
                   $card = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}cards JOIN {$db_prefix}auction ON card_id=id WHERE uid=%d LIMIT 1", $cmd->card_uid));
                   if (!$card) {
                       $sessionCmd = array("type"=>"INFO", "title"=>"Pokoušíš se koupit neexistující kartu :( Dej vědět hlavounům.", "color"=>"#f44336");
                       break;   
                   }
                   $card = $card[0];
                   if ($card->count_avail < 1) {
                       $sessionCmd = array("type"=>"INFO", "title"=>"Tato karta už je vyprodaná :( Dej vědět hlavounům.", "color"=>"#f44336");
                       break;  
                   }
  
                   if ($card->highest_bet > $_SESSION["login"]->cash) {
                       $sessionCmd = array("type"=>"INFO", "title"=>"Nemáš dostatek peněz.", "color"=>"#ff9800");
                       break;
                   }
                   
                   $wpdb->query($wpdb->prepare("INSERT INTO {$db_prefix}ownership(team_id, card_id, card_data) VALUES (%d, %d, %s)", $_SESSION["login"]->id, $card->id, $card->data));                   
                   $cash = $_SESSION["login"]->cash - $card->highest_bet;
                   $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}teams SET cash=%d WHERE id=%d", $cash, $_SESSION["login"]->id));
    
				   //POSSIBLY PROBLEMATIC: also check TSTAMP
                   $wpdb->query($wpdb->prepare("DELETE FROM {$db_prefix}auction WHERE team_id=%d AND uid=%d LIMIT 1", $_SESSION["login"]->id, $card->uid));
                   $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}cards SET count_avail=count_avail-1 WHERE id=%d", $card->id));
                   $sessionCmd = array("type"=>"INFO", "title"=>"Karta byla zakoupena.", "color"=>"#04AA6D");
                   break;
               case 'SEND_MORE_MONEY':
                   if ($_SESSION["cash"] > $cmd->amount || $cmd->amount < 1) {
                       $sessionCmd = array("type"=>"INFO", "title"=>"Neplatná částka pro převod.", "color"=>"#ff9800");
                       break;
                   }
                   if (!$wpdb->query($wpdb->prepare("UPDATE {$db_prefix}teams SET cash=cash+%d WHERE id=%d", $cmd->amount, $cmd->team_id))) {
                       $sessionCmd = array("type"=>"INFO", "title"=>"Převod se nezdařil: neplatný tým.", "color"=>"#ff9800");
                       break;
                   }
				   if (!$wpdb->query($wpdb->prepare("UPDATE {$db_prefix}teams SET cash=cash-%d WHERE id=%d", $cmd->amount, $_SESSION["login"]->id))) {
                       $sessionCmd = array("type"=>"INFO", "title"=>"Převod se nezdařil: kontaktuj prosím hlavouny.", "color"=>"#f44336");
                       break;
                   }
                   $sessionCmd = array("type"=>"INFO", "title"=>"Částka {$cmd->amount} &#8524; byla převedena.", "color"=>"#04AA6D");
                   break;
                   
               default:
                   echo "NOTHING";
                   break;      
           }
           $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}session SET command=%s, t_stamp=%d WHERE team_id=%d", json_encode($sessionCmd), time(), $_SESSION["login"]->id));
        } else {
           $wpdb->query($wpdb->prepare("INSERT INTO {$db_prefix}session(team_id, t_stamp, command, abortable) VALUES (%d, %d, %s, 1)", $_SESSION["login"]->id, time(), json_encode(array("type"=>"NONE"))));   
        }
        
        $_SESSION["tstamp"] = time() + 7200;
        
        header("Location: $url_index");
        exit();
    } else {
         echo "Autentizace selhala. Zkus to prosím znovu.<br><br>";
	}
}



//IF VALID AUTHENTICATION IN NEAR HISTORY, ALLOW ACTIONS SUCH AS ABORT OF PURCHASE
if(isset($_SESSION["tstamp"]) && $_SESSION["tstamp"] > time()) {
    $qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}session WHERE team_id='%s' LIMIT 1", $_SESSION["login"]->id));
    if ($qry) {
          $ses = $qry[0];  
            
          if ($ses && $ses->abortable && $ses->t_stamp > time() + 60) {
              $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}session SET command=%s, t_stamp=%d WHERE team_id=%d", json_encode(array("type"=>"NONE")), time(), $qry->id));
          } else {
              $cmd = json_decode($ses->command);

              switch($cmd->type) {
                  case 'INFO':
                      //should be handled elsewhere
                      header("Location: $url_index");
                      exit();
                  case 'SEND_MORE_MONEY':
                  case 'PURCHASE':
                      $form =  '<form action="'.$url_submit.'" method="post" style="z-index:99"><div style="text-align:center"><input name="sub" type="submit" value="Zrušit"></div><input name="form" type="hidden" value="ABORT_PURCHASE"></form><br>';
                      addAuthNotice($cmd->title, $form); 
                      break;
				  case 'PURCHASE_AUCTION':
                      $form =  '<form action="'.$url_submit.'" method="post" style="z-index:99"><div style="text-align:center"><input name="sub" type="submit" value="Zpět"></div><input name="form" type="hidden" value="ABORT_PURCHASE"></form><br>';
                      addAuthNotice($cmd->title, $form); 
                      break;
                  case 'AUTH_NOTICE':
                      addAuthNotice($cmd->title);
                      break;
                  case 'NONE':
                  default:
                      addAuthNotice("Přilož kartu");    
                      break;     
               } 
          } 
    }  
} else {
   addAuthNotice("Přilož kartu pro autentizaci.");    
}

    
               
               
function checkHotpAndUpdate($wpdb, $db_prefix, $code, $secret, $counter, $username){
    for($i = 0; $i < OTP_SYNC; $i++){
        $counterTry = $counter + $i;
        $hotp = HOTP::generateByCounter($secret, $counterTry);
  
        $guess = $hotp->toHOTP(6);
        if($guess == $code){
            $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}teams SET counter={$counterTry} WHERE login='%s'", $username));
            return True;
        }
    }
    
    return False;
}



function addAuthNotice($title, $details="") {
  echo '<h2 style="text-align: center;">'.$title.'</h2>'.$details.'<div style="
    position: relative;
"><img src="/ms/img/tap_phone.gif" style="width: 100%; max-width: 400px; position: absolute;"><img src="/ms/img/tap_phone.gif" style="width: 100%;position: absolute;"></div>';

  echo '<script type="text/javascript">
  //close AUTH window when not focused
window.onblur = function() {
  window.close();
  window.open("close.php", "_self").close();
}
</script>';   
    
}

?>