<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmartKartografové</title>
  <link rel="stylesheet" href="style.css" />
  
</head>

<body>

<?php
    
// echo "Forbidden.";
// exit();
    
require_once("config.php");

global $wpdb, $db_prefix;
    
if (isset($_GET["submit"]) && $_GET["submit"]=="do_create") {
    $cnt = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$db_prefix}teams WHERE name LIKE '%s' OR login LIKE '%s'", $_GET["team"], $_GET["login"]));
    if ($cnt) {
        echo "CHYBA: Tým s daným názvem nebo přihlašovacím jménem (login) již existuje.<br><br>";
    } else {
        //data -> empty map
        $wpdb->query($wpdb->prepare("INSERT INTO {$db_prefix}teams(name, login, hotp, cash, data) VALUES (%s, %s, %s, 50, %s);", $_GET["team"], $_GET["login"], $_GET["kod"], "[
            [{},{},{},{},{},{},{},{},{},{},{}],
            [{},{},{},{},{},{},{},{},{},{},{}],
            [{},{},{},{},{},{},{},{},{},{},{}],
            [{},{},{},{},{},{},{},{},{},{},{}],
            [{},{},{},{},{},{},{},{},{},{},{}],
            [{},{},{},{},{},{},{},{},{},{},{}],
            [{},{},{},{},{},{},{},{},{},{},{}],
            [{},{},{},{},{},{},{},{},{},{},{}],
            [{},{},{},{},{},{},{},{},{},{},{}],
            [{},{},{},{},{},{},{},{},{},{},{}],
            [{},{},{},{},{},{},{},{},{},{},{}]
          ]"));

        $lastid = $wpdb->insert_id;
        $tstamp = getTStampDb();

        $wpdb->query($wpdb->prepare("INSERT INTO {$db_prefix}session(team_id, t_stamp, command, abortable) VALUES (%d, %d, %s, 1)", $lastid, time(), json_encode(array("type"=>"NONE"))));

        echo "<script type='text/javascript'>window.onload = (event) => {  window.location.replace(window.location.pathname + '?inserted={$lastid}');  };</script>";  
    }
} 

if (isset($_GET["inserted"])) {
     $team = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}teams WHERE id=%d", $_GET["inserted"]));  
    
     if ($team) {      
         $team = $team[0];   
     } else {
        echo "Něco se nepodařilo. Zeptej se hlavounů.";
        exit();
     }
    
     require_once("ext/Base2n.php");
    
     echo "<h4>Tým {$team->name} byl přidán.</h4>";
     echo "Inicializační údaje pro kartu:<br>";
   
     $converter = new Base2n(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', FALSE, TRUE, TRUE);
     $base32secret = $converter->encode($team->hotp);
       
     echo "<br><br>Prvně nahraj tajný kód:<br><br><b><code style='text-align:center;display: block;'>otpauth://hotp/?secret=$base32secret&digits=6</code></b><br><br><br>Poté nahraj autentizační URL:<br><br><b><code style='text-align:center; display: block;'>{$url_auth}?login={$team->login}&key=</code></b><br>";
    echo "Pro nahrání údajů použij <a href='https://play.google.com/store/apps/details?id=com.wakdev.wdnfc&hl=cs&gl=US'>NFC tools</a> (Write -> Add Record -> Custom URL/URI -> Write / X Bytes).";
} else {
   echo '<H3>Přidej tým:</H3>Zadej název svého týmu, login a dlouhé tajné heslo (HOTP secret). Nic ze zadávaných údajů si NEMUSÍŠ pamatovat.<br> Pro <i>Login</i> a <i>Secret</i> používej prosím pouze čísla a písmena bez diakritiky.<form action="'.$_SERVER["REQUEST_URI"].'"><br> <input name="team" type="text" placeholder="Název týmu" /> (například: Kuliny Rocks!)<br> <input name="login" type="text" placeholder="URL id týmu (login)" /> (například: kuliny9)<br> <input name="kod" type="text" placeholder="HOTP secret" /> (například: 56sdS63d9aQ)<br>    <input type="submit" value="Založit tým" /><input type="hidden" name="submit" value="do_create" /></form>';   
}

 
?>