<?php
    
session_start();    
  
require_once "config.php";
require_once "functions.php";
global $wpdb;


if ($_POST["form"] == "PURCHASE_AUCTION") {
     $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}session SET command=%s, t_stamp=%d WHERE team_id=%d", json_encode(array("type"=>$_POST["form"], "card_uid"=>$_POST["card_uid"], "title"=>"Přilož kartu pro koupi.")), time(), $_SESSION["login"]->id));
     header("Location: $url_auth");
     exit(); 
}

if ($_POST["form"] == "ABORT_PURCHASE") {
     $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}session SET command=%s, t_stamp=%d WHERE team_id=%d", json_encode(array("type"=>"NONE")), time(), $_SESSION["login"]->id));
     header("Location: $url_index");
     exit(); 
}

if ($_POST["form"] == "BID") {
   $now = strtotime('now');
//   if ($now < $auction_start || $now > $auction_end){       
//      $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}session SET command=%s, t_stamp=%d WHERE team_id=%d", json_encode(array("type"=>"INFO", "title"=>"Aukce teď neprobíhá.", //"color"=>"f44336")), time(), $_SESSION["login"]->id));  
//   } else {
      $highest = $wpdb->get_var($wpdb->prepare("SELECT highest_bet FROM {$db_prefix}auction WHERE uid=%d", $_POST["card_uid"])); 
      if (!$highest && $highest != 0) {
          $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}session SET command=%s, t_stamp=%d WHERE team_id=%d", json_encode(array("type"=>"INFO", "title"=>"Eror: karta neexistuje.", "color"=>"f44336")), time(), $_SESSION["login"]->id));            
      } else if ($highest >= $_POST["bid"]) {
          $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}session SET command=%s, t_stamp=%d WHERE team_id=%d", json_encode(array("type"=>"INFO", "title"=>"Chyba: někdo mezitím přehodil vyšší částkou.", "color"=>"f44336")), time(), $_SESSION["login"]->id));  
      } else {
          $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}auction SET highest_bet=%d, team_id=%d WHERE uid=%d", $_POST["bid"], $_SESSION["login"]->id, $_POST["card_uid"]));
      }
 //  }
     header("Location: $url_index#auction");
     exit(); 
}

if ($_POST["form"] == "SEND_MORE_MONEY") {
    $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}session SET command=%s, t_stamp=%d WHERE team_id=%d", json_encode(array("type"=>"SEND_MORE_MONEY", "team_id"=>$_POST["team_id"], "amount"=>$_POST["amount"], "title"=>"Přilož kartu pro převod prostředků.")), time(), $_SESSION["login"]->id));
    header("Location: $url_auth");
    exit();  
}

if ($_POST["form"] == "SAVE_CARD_STATE") {
    $tid = $_POST["team_id"];
    $test_data = [];
    for($i = 0; $i < count($_POST["card_uid"]); $i++) {
        //necessary! we do it here once
        $_POST["card_data"][$i] = stripslashes($_POST["card_data"][$i]);

        $test_data[]=json_decode($_POST["card_data"][$i]);
        $test_data[$i]->card_uid = $_POST["card_uid"][$i];
    }

    $check = check_valid_map($tid, $test_data);
    if (is_string($check)) {
        message($_SESSION["login"]->id, "Mapa se mezitím změnila! Prosím oprav umístění.", STATUS_ERR);

        // echo " <br><br>";
        // pprint($check);
        // return;

    } else {
        
        // echo " <br><br>";
        // pprint($_POST["card_data"][$i]);
        // echo " <br><br>";

        // print_map($check["map"]);
        // return;


        $msg = null;
        $color = "";
        for($i = 0; $i < count($_POST["card_uid"]); $i++) {
            if (!isset($_POST["card_uid"][$i])) continue;

            if ($wpdb->query($wpdb->prepare("UPDATE {$db_prefix}ownership SET card_data=%s WHERE team_id=%d AND uid=%d LIMIT 1", 
                $_POST["card_data"][$i], $tid, $_POST["card_uid"][$i])) !== false) {
                $msg = "Uloženo";
                $color = "#04AA6D";
            } else {
                $msg = "Něco se nepodařilo :(";
                $color = "#f44336";
            }
        }
        if ($msg) {
            message($_SESSION["login"]->id, $msg, $color);
        } else {
            message($_SESSION["login"]->id, "Žádné změny nebyly provedeny.", STATUS_INFO);
        }
    }
    header("Location: $url_index");
    exit();  
}

if ($_POST["form"] == "SAVE_CARD_STATE_MONSTER") {
    $receiver = $_POST["team_id"];
    $placer = $_SESSION["login"]->id;
     
    $qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}ownership WHERE team_id=%d AND uid=%d", $placer, $_POST["card_uid"]), ARRAY_A);

    if (count($qry) == 1) {
        $v=$qry[0];

        $data = $v["card_data"];
        if (is_string($v["card_data"])) {
            $data = json_decode($v["card_data"], true);
        }
        $data["card_uid"] = $v["uid"];

        if ($data["monster"]) {
    
            $_POST["card_data"] = stripslashes($_POST["card_data"]);
            $test_data = [];
            $test_data[]=json_decode($_POST["card_data"]);
            $test_data[0]->card_uid = $_POST["card_uid"];

            if ($test_data[0]->placed && isset($test_data[0]->corner)) {
                $check = check_valid_map($receiver, $test_data);
    
                if (is_string($check)) {
                    message($_SESSION["login"]->id, "Mapa soupeře se mezitím změnila! Prosím opakuj umístění.", STATUS_ERR);
        
                    // echo " <br><br>";
                    // pprint($check);
                    // return;
        
                } else {
        
                    // echo " <br><br>";
                    // pprint($_POST["card_data"]);
                    // echo " <br><br>";
            
                    // print_map($check["map"]);
                    // return;
        
                    $inserted = $wpdb->query($wpdb->prepare("INSERT INTO {$db_prefix}ownership(team_id,card_id,card_data) VALUES (%d,%d,%s)", 
                         $data["monster"], $v["card_id"], $_POST["card_data"])) !== false;
                    $lastid = $wpdb->insert_id;
                    if ($inserted && $wpdb->query($wpdb->prepare("DELETE FROM {$db_prefix}ownership WHERE uid=%d LIMIT 1", $_POST["card_uid"])) === false) {
                        //try to revert error
                        $wpdb->query($wpdb->prepare("DELETE FROM {$db_prefix}ownership WHERE uid=%d LIMIT 1", $lastid));
                        message($_SESSION["login"]->id, "Něco se nepodařilo, zkus to prosím znovu.", STATUS_ERR);
                    } else if ($inserted) {
                        message($_SESSION["login"]->id, "Úspešně uškozeno ;)", STATUS_INFO);
                    } 
                }
            }
        }
    } else {
        message($_SESSION["login"]->id, "Něco se nepodařilo, zkus to prosím znovu.", STATUS_ERR);
    }
    
    header("Location: $url_index");
    exit();  
}