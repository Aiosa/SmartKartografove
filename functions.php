<?php


require_once "config.php";

global $wpdb;

const STATUS_INFO = "#04AA6D";
const STATUS_ERR = "#f44336";

function message($tid, $msg, $status=STATUS_INFO) {
    global $wpdb, $db_prefix;
    $wpdb->query($wpdb->prepare("UPDATE {$db_prefix}session SET command=%s, t_stamp=%d WHERE team_id=%d", 
        json_encode(array("type"=>"INFO", "title"=>$msg, "color"=> $status)), time(), $tid));  
}

function pprint($data) {
    echo "<pre style='text-align: left;'>" .json_encode($data, JSON_PRETTY_PRINT)."</pre>";
}

//ownership must be array, it needs uid!!!!
function check_valid_map($team_id, $ownership_data=null) {
    global $wpdb, $db_prefix;
    global $mountains;

    $mountainData = json_decode($mountains, true);
    $qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}teams WHERE id='%s'", $team_id));
 
    if (!$qry || !count($qry)) {
        return "Invalid team '$team_id'!";
    }
    $team_data = $qry[0];
    $qry = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$db_prefix}ownership s JOIN {$db_prefix}cards c ON s.card_id=c.id WHERE team_id=%d ORDER BY c.id", $team_data->id));  

    $map = json_decode($team_data->data, true);
    if (!count($map)) {
        return "Invalid map for team '$team_id'!";
    }

    foreach($mountainData as $c) {
        $map[$c[0]][$c[1]] = ["mountain"];
    }

    if ($ownership_data) {
        foreach ($ownership_data as $_=>$tmp) {
            $placed = false;
            foreach($qry as $k=>$v) {
                if ($tmp->card_uid == $v->uid) {
                    $v->card_data = $tmp;
                    $placed = true;
                    continue;
                }
            }
            if (!$placed) {
                //dummy node, probably monster
                $qry[]= (object)[
                    "card_data"=>$tmp,
                    "uid"=>$tmp->card_uid,
                    "is_special"=>0
                ];
            }
        }
    }


    $coins_add = $team_data->cash;
    foreach($qry as $k=>$v) {
        $data = $v->card_data;

        if (is_string($data)) {
            $data = json_decode($data);
        }

        if (!isset($data->corner) || !isset($data->placed) || !$data->placed) continue;

        $coords = [];
        $corner = $data->corner;

        array_map(function($c) use (&$coords, $corner) {
            $x = $c[0] + $corner[0];
            $y = $c[1] + $corner[1];

            if (!isset($coords[$x]))$coords[$x] = [];
            $coords[$x][$y] = true;
        },$data->coords);
        
        //special coords
        $scoords = [];
        if (isset($data->scoords)) {
            array_map(function($c) use (&$scoords, $corner) {
                $x = $c[0] + $corner[0];
                $y = $c[1] + $corner[1];
    
                if (!isset($scoords[$x]))$scoords[$x] = [];
                $scoords[$x][$y] = true;
            },$data->scoords);
        }

        for ($i = 0; $i < count($map); $i++) {
            for ($j = 0; $j < count($map[$i]); $j++) {
                
                $cell = $map[$i][$j];
                if (isset($coords[$i]) && isset($coords[$i][$j]) || (isset($cell["uid"]) && $cell["uid"] == $data->uid)) {

                    //collision, cell can be empty, 
                    if (count($cell) > 0 
                            && count($cell)!=1 && (!isset($cell["scord"]) || !$cell["scord"])) { //special coords are not collision-like

                        return "Unable to merge map for team '$team_id'! Occupied cell [$i, $j]: <code>" . print_r($data, true) . "</code><br> Cell data: <code>" . print_r($cell, true) . "</code>";
                    }

                    if (!isset($data->uid)) $data->uid = $data->card_uid;
                    $scoord = isset($cell["scord"]) || (isset($scoords[$i]) && isset($scoords[$i][$j]));

                    $map[$i][$j]["color"] = $data->color;
                    $map[$i][$j]["image"] = $data->image;
                    $map[$i][$j]["monster"] = $data->image;
                    $map[$i][$j]["uid"] = $data->uid;
                    $map[$i][$j]["scord"] = $scoord;
                } 
                
                if (isset($scoords[$i]) && isset($scoords[$i][$j])) {
                    $map[$i][$j]["scord"] = 1;
                }
            }
        }

        // echo "<br>";
        // print_map($map);

        // for ($i = 0; $i < count($map); $i++) {
        //     for ($j = 0; $j < count($map[$i]); $j++) {

        //         $cell = $map[$i][$j];
        //         if (isset($coords[$i]) && isset($coords[$i][$j])) {
        //             if (!isset($data->uid)) $data->uid = $data->card_uid;
        //             $scoord = isset($cell["scord"]) || (isset($scoords[$i]) && isset($scoords[$i][$j]));

        //             $map[$i][$j]=["color"=>$data->color, "image"=>$data->image, 
        //                 "monster"=>isset($data->monster), "uid"=>$data->uid, "scord"=>$scoord];
        //         } 
                
        //         if (isset($scoords[$i]) && isset($scoords[$i][$j])) {
        //             if (count($cell) < 0) {
        //                 $map[$i][$j]=["scord"=>1];
        //             } else {
        //                 $map[$i][$j]["scord"] = 1;
        //             }
        //         }
        //     }
        // }

        if ($v->is_special == 1) {
            $coins_add += 10;
        }
    }
  
    foreach($mountainData as $c) {
        $map[$c[0]][$c[1]] = [];    
    }
    return [
        "map" => $map,
        "cash" => $coins_add
    ];
}

function get_wpdb_err() {
    global $wpdb;
    pprint($wpdb->last_error);
    pprint($wpdb->last_query);
}

function print_map(&$map) {
    foreach($map as $_=>$row) {
        foreach($row as $__ => $cell) {
            if (count($cell) > 0) {
                echo count($cell);
            } else echo "_";
            echo " ";
        }
        echo "<br>";
    }
}

