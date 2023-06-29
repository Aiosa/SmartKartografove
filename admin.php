<?php

session_start();
require_once "config.php";
require_once "functions.php";
global $wpdb;

?>

<!DOCTYPE html>
<html data-light-theme="light">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmartKartografové</title>

  <link rel="stylesheet" type="text/css" href="./css/primer_css.css">
    <link rel="stylesheet" type="text/css" href="./css/app.css" />
    <link rel="stylesheet" type="text/css" href="./css/map.css">
    <link rel="stylesheet" type="text/css" href="./css/map2023.css">


    <script src="./js/jquery-2.2.3.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jquery.ui/1.11.4/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>

    <script src="./js/bundle.js"></script>
  
</head>

<body style="background:url(img/bg.png) repeat;">
<?php
if (isset($_POST["target_team"])) $_SESSION["target_team"] = $_POST["target_team"];


function merge_map($team_id) {
    global $wpdb, $db_prefix;

    $result = check_valid_map($team_id);

    if (is_string($result)) {
        echo $result;
        return;
    }

    if ($wpdb->query($wpdb->prepare("INSERT INTO {$db_prefix}team_history(team_id,cash,score,data,tstamp) SELECT id,cash,score,data,CURRENT_TIMESTAMP() FROM {$db_prefix}teams WHERE id=%d", 
            $team_id)) !== false
         && $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}teams SET data=%s, cash=%d WHERE id=%d", 
            json_encode($result["map"]), $result["cash"], $team_id)) !== false 
         && $wpdb->query($wpdb->prepare("DELETE FROM {$db_prefix}ownership WHERE team_id=%d", $team_id)) !== false) {
      echo "Succesfully baked.<br>";
    } else {
      echo "Baking failed!<br>";
      get_wpdb_err();
    }
}


$command=isset($_POST["cmd"]) ? $_POST["cmd"] : "";
switch ($command) {
    case "MERGE_MAP":
        $qry = $wpdb->get_results($wpdb->prepare("SELECT id, name FROM {$db_prefix}teams"));
        foreach($qry as $k=>$v) {
            $name = $v->name;
            echo "Merging team $name<br>...";
            merge_map($v->id);
        }
        break;
    case "MERGE_MAP_SINGLE":
        if (isset($_SESSION["target_team"])) {
            merge_map($_SESSION["target_team"]);
        }
        break;
    case "SET_AUCTION":
        $now = strtotime('now');
        // if ($now < $auction_start || $now > $auction_end) {
            $now = $auction_start+10;
            if (!$wpdb->query($wpdb->prepare("INSERT INTO {$db_prefix}auction(card_id,t_stamp) SELECT id, $now FROM {$db_prefix}cards WHERE data NOT LIKE '%monster%' ORDER BY RAND() LIMIT %d", $_POST["amount"]))) {
                echo "Failed!<br>";
                get_wpdb_err();
            }
        // } else {
        //     echo "Auction is still in progress! Unable to change auction when running!<br>";
        // }
        break; 
    case "SCORE":

        if (!isset($_SESSION["target_team"]) || !$_SESSION["target_team"]) {
            echo "No team selected!";
            break;
        }
        $qry = $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}teams SET score=%d WHERE id='%d'", $_POST["amount"], $_SESSION["target_team"]));
        break;
    case "MONSTER_ARRIVAL":
        $count = $_POST["index"];
        $qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}cards WHERE data LIKE '%monster%'"));
        $now = strtotime('now');

        if (!$qry || count($qry) < $count) {
            echo "Failed - invalid monster number $count!<br>";
            break;
        }
        $monster = $qry[$count-1];
        $teams = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}teams ORDER BY RAND()"));
        $teams_size = count($teams);
        $monster_data = $monster->data;
        if (is_string($monster_data)) {
            $monster_data = json_decode($monster_data);
        }

        if (!$monster_data) {
            echo "Failed - invalid monster JSON data!<br>";
            break;
        }

        for ($i = 0; $i < $teams_size; $i++) {
            $j = ($i+1) % $teams_size;

            $from_team = $teams[$i]->id;
            $to_team = $teams[$j]->id;
            $monster_data->monster = $to_team;

            $wpdb->query($wpdb->prepare("INSERT INTO {$db_prefix}ownership(team_id,card_id,card_data) VALUES (%d,%d,%s)", 
                $from_team,$monster->id,json_encode($monster_data)));
        }
        break;    
    default: break;
}

?>
<br>
<form action="?" method="post" id="merge_all">
<input name="cmd" type="hidden" value="MERGE_MAP">
<input name="sub" type="submit" value="Zapéct mapy" class="form-control">
</form>
<br><br>
<form action="?" method="post" id="auction_generate">
<input name="cmd" type="hidden" value="SET_AUCTION">
<input name="amount" type="number" class="form-control" value="6">
<input name="sub" type="submit" value="Nahrát do aukce" class="form-control">
</form>
<br><br>
<form action="?" method="post" id="auction_generate">
<input name="cmd" type="hidden" value="MONSTER_ARRIVAL">
<input name="index" type="number" class="form-control" value="1">
<input name="sub" type="submit" value="Příšery!" class="form-control">
</form>
<br>
<?php




echo "<br><h3>Vyber tým</h3>";
$qry = $wpdb->get_results("SELECT * FROM {$db_prefix}teams");
if (count($qry) < 1) {
    echo 'Žádné další týmy neexistují - počkej až hra začne.';
} else {
    echo '<form action="?" method="post" id="select_team"><select name="target_team" id="select-team" class="form-control"> <option></option>';
    foreach($qry as $key=>$team) {
        $value = "";
        if ($team->id == $_SESSION["target_team"]) $value = " selected";
        echo '<option value="'.$team->id.'" '.$value.'>'.$team->name.'</option>';   
   }

   echo <<<EOF
   <input name="sub" type="submit" value="Zobrazit" class="form-control">
   </select></form>
EOF;
}



if (!isset($_SESSION["target_team"]) || !$_SESSION["target_team"]) {
    exit();
}

$qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}teams WHERE id='%d'", $_SESSION["target_team"]));
 
if (!$qry || !count($qry)) {
   echo "Invalid team '{$_SESSION['target_team']}'!";
   exit();  
}
$team_data = $qry[0];


$qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}ownership s JOIN {$db_prefix}cards c ON s.card_id=c.id WHERE team_id=%d ORDER BY c.id", $team_data->id), ARRAY_A);  
$cards_to_place = [];

foreach($qry as $k=>$v) {

    $data = $v["card_data"];
    if (is_string($v["card_data"])) {
        $data = json_decode($v["card_data"], true);
    }
    if (!$data) {
        echo "ERR: not parseable: " . print_r($data, true) . "<br><br>";
        continue;
    }
    $data["card_uid"] = $v["uid"];
    $cards_to_place[]= $data;

}
$cards_to_place = json_encode($cards_to_place);
unset($qry);


echo <<<EOF

<figure>

<div id="map" style="touch-action: none; user-select: none; display:inline-block;"></div>

</figure>
<script>
document.addEventListener("DOMContentLoaded", function(event) {   //mountains data: (hardcoded)
  kartografove($('#map'), {$team_data->data}, {$cards_to_place}, 'none', $mountainSpecs, $mountains)
});

</script>

<p>Team score: <b>{$team_data->score}</b></p>
<form action="?" method="post" id="auction_generate">
<input name="cmd" type="hidden" value="SCORE">
<input name="amount" type="number" class="form-control" value="{$team_data->score}">
<input name="sub" type="submit" value="Nastavit score" class="form-control">
</form>
EOF;



?>


       
    
<?php
    
// $teams = $wpdb->get_results("SELECT * FROM {$db_prefix}teams");
// foreach ($teams as $key=>$team) {
//     echo "<h3>" . $team->name . "</h3>";
//     printQry($wpdb->get_results("SELECT * FROM {$db_prefix}cards c JOIN {$db_prefix}ownership o ON o.card_id=c.id WHERE o.team_id=$team->id AND o.placeable>0"), $team);
// }

// function printQry($qry, $team) {
//    if (!$qry) {
//     echo "Žádné karty k dispozici.<br>";   
//     } else {
//       echo "<div>";
//        foreach ($qry as $key=>$card) {
//            echo "<form style='display:contents;' action='{$_SERVER['QUERY_STRING']}' method='post'><div class='card' style='max-width:200px;'><span style='position: absolute; font-size: 18pt; opacity: 0.6; right: 3px; top: 3px; background: white; border-radius: 17px; padding: 2px;width: 28px;height: 28px; text-align: center;'>$card->id</span><img src='img/{$card->image_src}'><input type='hidden' name='team' value='$team->id'><input type='hidden' name='card' value='$card->id'><input type='submit' value='Převzít'></div></form>";      
//     }
//     echo "</div>";
// }      
 
// }



echo '<hr><h3 id="auction">Aukce</h3>';
$now = strtotime('now');
$qry = $wpdb->get_results("SELECT * FROM {$db_prefix}auction s JOIN {$db_prefix}cards c ON s.card_id=c.id LEFT OUTER JOIN {$db_prefix}teams t ON s.team_id=t.id");

if (!$qry) {
    if ($now < $auction_start || $now > $auction_end) {
        $difftime = $now < $auction_start ? $auction_start - $now : $auction_start - $now + 86400; //next day
        $hours = floor($difftime / 3600);
        $mins = floor($difftime / 60);
        $time_str = $hours > 1 ? "$hours hodin" : "$mins minut";
        echo "Do přístí aukce zbývá {$time_str}.<br>";
    } else {
       echo "Nejsou k dispozici žádné karty.<br>";   
    }
} else {
    echo "<div style='position:relative'>";
    
    //if auction does not run
    if ($now < $auction_start || $now > $auction_end){
        $difftime = $now < $auction_start ? $auction_start - $now : $auction_start - $now + 86400; //next day
        $hours = floor($difftime / 3600);
        $mins = floor($difftime / 60);
        $time_str = $hours > 1 ? "$hours hodin" : "$mins minut";
        
        echo "Do přístí aukce zbývá {$time_str}.<br>";
       
    //else in auction time
    } else {
         $mins = floor(($auction_end - $now) / 60);
         if ($mins < 60) {
             echo "Do konce aukce zbývá $mins minut.<br>";
         }
        
        foreach ($qry as $key=>$card) {
           $team = $card->name ? "Vítězí: &nbsp;" . $card->name : "Ještě nikdo nepřihodil.";
           $old = $card->t_stamp < $auction_start ? "<br>Již je mimo aukci." : "";
           echo "<div class='card'><img src='img/{$card->image_src}'><div class='card-desc'> $card->highest_bet &#8524;<br>$team $old</div></div>";      
        }
    }   
    echo "</div>";
}



?>
  
</body>
</html>