<?php


if(isset($_SERVER['SERVER_SOFTWARE'])){
    echo $_SERVER['SERVER_SOFTWARE'];
    echo " Note that this game works only with apache v2.4+! <br>";
} else {
    echo "Could not guess the apache server version. Note that this game works only with apache v2.4+! <br>";
}

echo "<br>You need to change the .htaccess file to reach the .htpasswd file location: it is '" . dirname(__FILE__) . "/.htpasswd'.<br><br>";

echo "To initialize MySQL database, import schema.sql!<br><br>";
exit();

// require_once("config.php");

// global $wpdb, $db_prefix;
// $wpdb->query("DROP TABLE IF EXISTS {$db_prefix}session");
// $wpdb->query("CREATE TABLE {$db_prefix}session(team_id INT NOT NULL PRIMARY KEY, t_stamp INT DEFAULT 0, command TEXT, abortable TINYINT);");

// $wpdb->query("DROP TABLE IF EXISTS {$db_prefix}cards");
// $wpdb->query("CREATE TABLE {$db_prefix}cards(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, count_avail INT DEFAULT 0, value INT DEFAULT 0, is_special TINYINT, image_src VARCHAR(255), data TEXT);");

// $wpdb->query("DROP TABLE IF EXISTS {$db_prefix}teams");
// $wpdb->query("CREATE TABLE {$db_prefix}teams(id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, name TEXT, login TEXT, hotp VARCHAR(63), counter INT DEFAULT 0, score INT DEFAULT 0, cash INT DEFAULT 0, data TEXT);");

// $wpdb->query("DROP TABLE IF EXISTS {$db_prefix}ownership");
// $wpdb->query("CREATE TABLE {$db_prefix}ownership(uid INT NOT NULL PRIMARY KEY, team_id INT NOT NULL, card_id INT NOT NULL, card_data TEXT, PRIMARY KEY (team_id, card_id));");

// $wpdb->query("DROP TABLE IF EXISTS {$db_prefix}auction");
// $wpdb->query("CREATE TABLE {$db_prefix}auction(uid INT NOT NULL PRIMARY KEY, card_id INT NOT NULL, highest_bet INT DEFAULT 0, team_id INT DEFAULT -1, t_stamp INT DEFAULT 0);");

// $wpdb->query("DROP TABLE IF EXISTS {$db_prefix}shop");
//$wpdb->query("CREATE TABLE {$db_prefix}shop(team_id INT NOT NULL, card_id INT NOT NULL, purchased TINYINT DEFAULT 0);");

?>