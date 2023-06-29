<?php
session_start();
   
require_once "config.php";

  //tidi enable
    // if (!isset($_SESSION["tstamp"]) || $_SESSION["tstamp"] < time() || !isset($_SESSION["login"])) {
    //   $team_data = "";       
    //   header("Location: $url_auth");
    //   exit();   
    // }
  
global $wpdb;
$qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}teams WHERE id='%s'", $_SESSION["login"]->id));
if (!$qry) {
    header("Location: $url_auth");
    exit();  
}
$team_data = $qry[0];

$qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}ownership WHERE team_id=%d", $team_data->id), ARRAY_A);

$cards_to_place = [];
foreach($qry as $k=>$v) {
    $data = $v["card_data"];
    if (is_string($v["card_data"])) {
        $data = json_decode($v["card_data"], true);
    }

    //if monster tile, not placed and not for this team
    if ($data["monster"] && !$data["placed"] && $data["monster"] != $team_data->id) {
        $data["card_uid"] = $v["uid"];
        $cards_to_place[]= $data;
        break; //just single monster allowed
    }
}
if (count($cards_to_place) < 1) {
    header("Location: $url_index");
    exit();  
}
$to_place = $cards_to_place[0];
$monster_id = $to_place["card_uid"];

$qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}teams WHERE id='%s'", $to_place["monster"]));
if (!$qry || count($qry) < 1) {
    header("Location: $url_index");
    exit();  
}
$team_target = $qry[0]; 

//add oponnent non-monster cards
$qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}ownership s JOIN {$db_prefix}cards c ON s.card_id=c.id WHERE team_id=%d ORDER BY c.id", $team_target->id), ARRAY_A);  
foreach($qry as $k=>$v) {
    $data = $v["card_data"];
    if (is_string($v["card_data"])) {
        $data = json_decode($v["card_data"], true);
    }

    if (!$data) {
        continue; //todo error!!!! invalid json
    }

    if (!$data["placed"]) {
        continue; //do not show
    }

    if (!$data["monster"] || ($data["placed"] && $data["monster"] == $team_target->id)) {
        $data["card_uid"] = $v["uid"];
        $cards_to_place[]= $data;
    } else if ($data["monster"]) {
        $monster = true;
    }
}

$cards_to_place = json_encode($cards_to_place);

?>

<!DOCTYPE html>
<html data-light-theme="light">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kartografové</title>

  <link rel="stylesheet" type="text/css" href="./css/primer_css.css">
    <link rel="stylesheet" type="text/css" href="./css/app.css" />
    <link rel="stylesheet" type="text/css" href="./css/map.css">
    <link rel="stylesheet" type="text/css" href="./css/map2023.css">
    <script src="./js/jquery-2.2.3.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jquery.ui/1.11.4/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <script src="./js/bundle.js"></script>
  
</head>

<body style="background:url(img/bg.jpg) repeat;">
    <div id="dimmer" onclick="hidePopup();"></div> 
    <h2 style="display:inline-block;"> <?php echo $team_data->name ?></h2>&emsp; 
    <span style='font-weight: 700;'> škodí týmu: <?php echo $team_target->name ?></span><br>
   
    <input name="sub" type="submit" value="Uložit" class="form-control d-block" style="margin: 10px auto;" onclick="saveCardItems()">

    <div id="map" style="touch-action: none; user-select: none; display:inline-block;"></div>

    <div id="update-card-items-state"></div>

<script>
document.addEventListener("DOMContentLoaded", function(event) {   //mountains data: (hardcoded)
  kartografove($('#map'), <?php echo $team_target->data ?>, <?php echo $cards_to_place ?>, 'monster', 
  <?php echo $mountainSpecs ?>, <?php echo $mountains ?>);
});


function saveCardItems() {
    const cont = $("#update-card-items-state");
    const cards = window.view.getCurrentItems();

    const monsterCard = cards.find((card, i) => {
        if (!card.card_uid) return false; //todo errror?
        const id = card.card_uid;

        //!important
        if (Number.parseInt(id) !== <?php echo $monster_id; ?>) return false;
        return true;
        
    });

    const id = monsterCard.card_uid;
    delete monsterCard.card_uid;
    cont.html(`
<form action="<?php echo $url_submit ?>" method="post" id="update-state">
<input name="team_id" type="hidden" value="<?php echo $team_target->id ?>">
<input name="form" type="hidden" value="SAVE_CARD_STATE_MONSTER">
<input name="card_uid" form="update-state" type="hidden" value="${id}">
<input name="card_data" form="update-state" type="hidden" value='${JSON.stringify(monsterCard)}'>
</form>`);
  $("#update-state").trigger( "submit" );
}
</script>
</body>
</html>
