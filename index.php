<?php
session_start();
require_once "config.php";

//ID of team to log in as, or true to use id 1
//$testing=4;
//$testing_runtime=4;

if ($testing) {
    if (!isset($_SESSION["login"])) {
        $_SESSION["login"] = (object)[];
    }
    $_SESSION["login"]->id = is_numeric($testing) ? $testing : 1;
} else if (!isset($_SESSION["login"]) && $testing_runtime) {
    $_SESSION["login"] = (object)[];
    $_SESSION["login"]->id = is_numeric($testing_runtime) ? $testing_runtime : 1;
} else {
    if(!isset($_SESSION["tstamp"]) || $_SESSION["tstamp"] < time()) {
        $team_data = "";       
        header("Location: $url_auth");
        exit();   
      }
}

global $wpdb;

$infoMsg = "";
$infoBg = "";

$qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}session WHERE team_id='%s' LIMIT 1", $_SESSION["login"]->id));
if ($qry) {
    $ses = $qry[0];
    $cmd = json_decode($ses->command);
    
    switch($cmd->type) {
        case 'NONE':
            break;
        case 'INFO':
            $infoMsg = $cmd->title;
            $infoBg = isset($cmd->color) ? $cmd->color : "#2196F3";
            $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}session SET command=%s, t_stamp=%d WHERE team_id=%d", json_encode(array("type"=>"NONE")), time(), $_SESSION["login"]->id));
            break;
        default:
            //some complex action required, go to auth
            header("Location: $url_auth");
            exit(); 
    }
    
} else {
  $wpdb->query($wpdb->prepare("INSERT INTO {$db_prefix}session(team_id, t_stamp, command, abortable) VALUES (%d, %d, %s, 1)", $team_data->id, time(), json_encode(array("type"=>"NONE"))));
}


$qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}teams WHERE id='%s'", $_SESSION["login"]->id));

if ($qry) {
 $_SESSION["login"] = $qry[0]; 
} else {
 if ($testing) echo "Invalid team id!";
 else header("Location: $url_auth");
 exit();  
}   
$team_data = $_SESSION["login"];
$monster = false;

$qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}ownership s JOIN {$db_prefix}cards c ON s.card_id=c.id WHERE team_id=%d ORDER BY c.id", $team_data->id), ARRAY_A);  
$cards_to_place = [];

foreach($qry as $k=>$v) {
    $data = $v["card_data"];
    if (is_string($v["card_data"])) {
        $data = json_decode($v["card_data"], true);
    }

    if (!$data) {
        continue; //todo error!!!! invalid json
    }

    if (!$data["monster"] || ($data["placed"] && $data["monster"] == $team_data->id)) {
        $data["card_uid"] = $v["uid"];
        $cards_to_place[]= $data;
    } else if ($data["monster"]) {
        $monster = true;
    }
}
$cards_to_place = json_encode($cards_to_place);
unset($qry);

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

<body style="background:url(img/bg.jpg) repeat;">
    <div id="dimmer" onclick="hidePopup();"></div> 
    <br>
    
    <h2 style="display:inline-block;"><?php 
    
    if ($testing) echo "TESTING!&emsp;";
    echo $team_data->name; 
    
    ?> </h2>
        
    <span style='position: absolute; right: 19px; top: 19px; font-weight: 700;'> <?php echo $team_data->cash; ?> &#8524;</span><br>

    <br>

    <p class="m-2">Umisťuj na mapu získané dílky a plň úkoly (A-D), které se vyhodnocují v daný čas. Pokud
        svoje dílky daný den neumístíš na mapu, přijdeš o ně. A pozor! Možná se tu toulají nestvůry...</p>

<br>
<h3 id="evalutaion">Hodnocení</h3>
<p>Hodnotí se: v pondělí večer (A, B), středa večer (B,C), pátek večer (C, D), sobota večer (D, A)</p>
<div class="d-flex flex-row m-2" style="place-content: center; flex-wrap: wrap">
<span class="d-flex flex-column">A<img src="img/u2.png" style="width: 150px"/></span>
<span class="d-flex flex-column">B<img src="img/u3.png" style="width: 150px"/></span>
<span class="d-flex flex-column">C<img src="img/u4.png" style="width: 150px"/></span>
<span class="d-flex flex-column">D<img src="img/u1.png" style="width: 150px"/></span>
</div>
<br>


    
<?php




echo <<<EOF

<h3 id="map_header">Mapa</h3>

<p>Nepřiřazené dílky lze tapnutím otáčet a podržením překlápět. Dílek, který je přiřazen, lze tapnutím vrátit. 
Změny je nutné <b>uložit</b>. Neuložené a nepoužité dílky další den <b>mizí</b>!</p>

<div id="map" style="display:inline-block;"></div>


<div id="update-card-items-state"></div>
<input name="sub" type="submit" value="Uložit" class="form-control" onclick="saveCardItems()">

<script>
document.addEventListener("DOMContentLoaded", function(event) {   //mountains data: (hardcoded)
  kartografove($('#map'), {$team_data->data}, {$cards_to_place}, 'all', $mountainSpecs, $mountains)
});


function saveCardItems() {
    const cont = $("#update-card-items-state");
    const cards = window.view.getCurrentItems();
    const inputCards = cards.map((card, i) => {
        if (!card.card_uid) return ""; //todo errror?

        const id = card.card_uid;
        delete card.card_uid;
        return `
            <input name="card_uid[\${i}]" form="update-state" type="hidden" value="\${id}">
            <input name="card_data[\${i}]" form="update-state" type="hidden" value='\${JSON.stringify(card)}'>
        `;
    }).join("");

    cont.html(`
<form action="$url_submit" method="post" id="update-state">
<input name="team_id" type="hidden" value="{$team_data->id}">
<input name="form" type="hidden" value="SAVE_CARD_STATE">
\${inputCards}
</form>`);

    $("#update-state").trigger( "submit" );

}
</script>

EOF;


if ($monster) {
    echo <<<EOF
    &emsp;<input type="submit" value="Máš k dispozici příšeru! \n Škodit" class="form-control" onclick="window.location='monster.php'">
EOF;
}

$astart = date('H:i', $auction_start+$time_shift);
$aend = date('H:i', $auction_end+$time_shift);

echo <<<EOF
<br><br><br><h3 id="auction">Aukce ($astart - $aend)</h3>
EOF;

$now = strtotime('now');


$qry = $wpdb->get_results("SELECT * FROM {$db_prefix}auction s JOIN {$db_prefix}cards c ON s.card_id=c.id LEFT OUTER JOIN {$db_prefix}teams t ON s.team_id=t.id WHERE s.t_stamp >= {$auction_start} AND c.count_avail > 0");
$leftovers = $wpdb->get_results("SELECT * FROM {$db_prefix}auction s JOIN {$db_prefix}cards c ON s.card_id=c.id JOIN {$db_prefix}teams t ON s.team_id=t.id WHERE s.t_stamp < {$auction_start} AND t.id={$team_data->id}");  

if ($leftovers) {
  echo "<div style='text-align: center;'>Tvoje výhra čeká na vyzvednutí.</div><br>";  
}


if (!$qry) {
    if ($now < $auction_start || $now > $auction_end) {
        $difftime = $now < $auction_start ? $auction_start - $now : $auction_start - $now + 86400; //next day
        $hours = floor($difftime / 3600);
        $mins = floor($difftime / 60);
        $time_str = $hours > 1 ? "$hours hodin" : "$mins minut";
        echo "Do příští aukce zbývá {$time_str}.<br>";
        if ($leftovers) {
            echo "Karty můžeš vyzvednout pouze v době aukce - počkej až aukce začne.<br>";  
            foreach ($leftovers as $key=>$toPurchase) {
                echo "<div class='card'><img src='img/{$toPurchase->image_src}'><div class='card-desc'>Cena $toPurchase->highest_bet &#8524;<br>Vyhrál: $team_data->name</div></div>";      
            }   
        }

    } else {
       if (!$leftovers) {
           echo "Nejsou k dispozici žádné karty.<br>";  
       } else {
           foreach ($leftovers as $key=>$toPurchase) {
                 echo "<div class='card' onclick='fireBuyCard(this, {$toPurchase->uid}, {$team_data->cash}, {$toPurchase->highest_bet},  \"{$toPurchase->image_src}\", \"$url_submit\", \"PURCHASE_AUCTION\");'>
                 <img class='pickup' src='img/pickup.png'><img src='img/{$toPurchase->image_src}'><div class='card-desc'>Cena $toPurchase->highest_bet &#8524;<br>Vyhrál: $team_data->name</div></div>";      
           }      
       }
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
        echo "Karty můžeš vyzvednout pouze v době aukce - počkej až aukce začne.<br>";  

        if ($leftovers) {
            foreach ($leftovers as $key=>$toPurchase) {
                echo "<div class='card'><img src='img/{$toPurchase->image_src}'><div class='card-desc'>Cena $toPurchase->highest_bet &#8524;<br>Vyhrál: $team_data->name</div></div>";      
            }   
        }
        
        //auction not valid
        foreach ($qry as $key=>$card) { 
           if (! $card->name) continue; //only show won cards (existing team name)
           $opacity = $card->team_id == $team_data->id ? 1 : 0.5;
           echo "<div class='card' style='opacity: {$opacity};'><img src='img/{$card->image_src}'><div class='card-desc'>Cena $card->highest_bet &#8524;<br>Vyhrál: $card->name</div></div>";     
        }
    //else in auction time
    } else {
         $mins = floor(($auction_end - $now) / 60);
         if ($mins < 60) {
             echo "Do konce aukce zbývá $mins minut.<br>";
         }
        
        if ($leftovers) {
            echo "<div style='filter:brightness(0.5); background: rgba(0, 0, 0, 0.3);'>";
        }
        
        foreach ($qry as $key=>$card) {
           $commandJS = $leftovers ? "" : "onclick='fireBidCard(this, {$card->uid}, {$team_data->cash}, $card->highest_bet, \"{$card->image_src}\", \"$url_submit\");'";
           $team = $card->name ? "Vítězí: &nbsp;" . $card->name : "Ještě nikdo nepřihodil.";
           echo "<div class='card' $commandJS><img src='img/{$card->image_src}'><div class='card-desc'> $card->highest_bet &#8524;<br>$team</div></div>";      
        }
        
        if ($leftovers) {
           echo "<br><br></div><div style='position: absolute; width: 100%; top:5px;'>";
           $counter = 0;
           foreach ($leftovers as $key=>$toPurchase) {
               echo "<div class='card' onclick='fireBuyCard(this, {$toPurchase->uid}, {$team_data->cash}, {$toPurchase->highest_bet}, \"{$toPurchase->image_src}\", \"$url_submit\", \"PURCHASE_AUCTION\");'>
                    <img class='pickup' src='img/pickup.png'><img src='img/{$toPurchase->image_src}'><div class='card-desc'>Cena $toPurchase->highest_bet &#8524;<br></div></div>"; 
               if ($counter++ > 2) break; //show at most 3
           }    
               
           echo "</div>";    
        }
    }   
    echo "</div>";
}




echo "<br><br><h3>Převod &#8524;</h3>";
$qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}teams WHERE id != %d", $team_data->id));
if (count($qry) < 1) {
    echo 'Žádné další týmy neexistují - počkej až hra začne.';
} else {
    echo '<form action="'.$url_submit.'" method="post"> Poslat částku: <input type="number" name="amount" min="1" class="form-control" max="'.$team_data->cash.'" value="1"><select class="form-control" name="team_id">';
    foreach($qry as $key=>$team) {
        echo '<option value="'.$team->id.'">'.$team->name.'</option>';   
   }
   echo '</select>
           <input class="form-control" name="sub" type="submit" value="Poslat">
           <input class="form-control" name="form" type="hidden" value="SEND_MORE_MONEY">
           </form>';
}




echo '<br><br><h3>Statistiky</h3>';
$qry = $wpdb->get_results("SELECT name, score FROM {$db_prefix}teams ORDER BY score DESC");  
echo '<b>Žbříček:</b>';
if (count($qry) < 0 || $qry[0]->score == 0) {
    echo ' počkej až hra začne.';
} else {
    echo '<ul style="    list-style: inside; text-align: left; width: max-content; margin: 10px auto;">';
    foreach ($qry as $key=>$v) {
        echo "<li>$v->name ($v->score)</li>";
   } 
   echo "</ul>";
}
echo "<br><br>";


// echo '<b>Nejvíce prašulí:</b> &nbsp;';
// $qry = $wpdb->get_results("SELECT name FROM {$db_prefix}teams WHERE cash IN (SELECT MAX(cash) FROM {$db_prefix}teams)");  
// foreach ($qry as $key=>$value) {
//      echo $value->name . "&emsp;";
// } 
// echo "<br><br><br>";

// echo '<br><br><h3>Oznámení</h3>Prašule za Kubb budou rozděleny v pátek ráno. Turnaj se nestihl dohrát. Aukce v pátek bude končit přesně v 13:00. <b>Hra končí v pátek večer, na závěrečném pátečním večeru bude vyhodnocení.<br><br>';

if ($infoMsg) {
    echo "<div class=\"alert\" style=\"background:$infoBg; width: 100vw; z-index:9999;\"><span class=\"closebtn\" onclick=\"this.parentElement.style.display='none';\">&times;</span>$infoMsg</div>";
    echo "<script type=\"text/javascript\">setTimeout(function() {var x = document.getElementsByClassName(\"alert\");for (var i = 0; i < x.length; i++) { x[i].style.display = 'none';}}, 7000);</script>";
}

?>
    <div id="popup" style="display:none;"></div>
    
    
    <script type="text/javascript">
    
    var popup = document.getElementById("popup");
    var dimmer = document.getElementById('dimmer');
 
    function firePopup(self, content) {
        popup.style.display = 'block';
        dimmer.style.display = 'block';
        popup.innerHTML = content;
    }

  
    function fireBidCard(self, cardUid, money, cardCost, cardImg, url) {
       if (money < cardCost) {
         alert("Not enough money.");
         return;
       }
        
        
       let biddedmin = cardCost+2;
        var content = `<div class="popup-container" style="background:transparent;">
           <form action="${url}" method="post" style="">
           <img src="img/${cardImg}" style="max-width:120px;">
           <div style="text-align:right;">
           <span style="color:white">Minimální příhoz: ${biddedmin} &#8524;</span><br><span style="color:white">K dispozici: ${money} &#8524;</span><br>

           <input class="form-control"  type="number" name="bid" min="${biddedmin}" value="${biddedmin}">
           <input class="form-control"  name="sub" type="submit" value="Přihodit">
           <input class="form-control"  name="form" type="hidden" value="BID">
           <input class="form-control"  name="card_uid" type="hidden" value="${cardUid}"></div>
           </form></div>`; 

       firePopup(self, content);
    }

    function fireBuyCard(self, cardUid, money, cardCost, cardImg, url, type="PURCHASE") {
        if (money < cardCost) {
         alert("No money");
         return;
        }

        var content = `<div class="popup-container" style="background:transparent;">
           <form action="${url}" method="post" style="">
           <img src="img/${cardImg}" style="max-width:120px;">
           <div style="text-align:right;">

           <span style="color:white"> Cena: ${cardCost} &#8524;</span><br><span style="color:white">Zůstatek: ${money-cardCost} &#8524;</span><br>

           <input class="form-control"  name="sub" type="submit" value="Koupit">
           <input class="form-control"  name="form" type="hidden" value=${type}>
           <input class="form-control"  name="card_uid" type="hidden" value="${cardUid}"></div>
           </form></div>`;   

       firePopup(self, content);
    }

    function hidePopup() {
        popup.style.display = 'none';
        dimmer.style.display = 'none';
    }
        
    </script>
</body>
</html>