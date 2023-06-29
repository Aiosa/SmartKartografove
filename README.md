# SmartKartografové

Requirements:
 - apache 2.4 with MySQL database and Wordpress
    - wordpress web itself not used, just its PHP API
 - set correct .htaccess paths
 - setup database (import schema + cards data)
 - setup config.php

Requirements for SmartCard:
 - smart card reader, smart cards, software to upload HoTP with NDEF tag (JCAppStore)
 - access add_team.php and register team, follow instructions
 - teams can access system using the smard card
 - admin can control the game using admin.php

Admin .htpasswd:
 admin | koberec12
