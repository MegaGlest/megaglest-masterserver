<?php
//      Copyright (C) 2012 Mark Vejvoda, Titus Tscharntke and Tom Reynolds
//      The MegaGlest Team, under GNU GPL v3.0
// ==============================================================

        define( 'INCLUSION_PERMITTED', true );
        require_once( 'config.php' );
        require_once( 'functions.php' );

        $link = db_connect();
        //mysql_select_db(MYSQL_DATABASE);
        $recents_query = mysqli_query(Registry::$mysqliLink,'SELECT name, players FROM recent_servers' );
        echo "<table>";
        while($recent_server = mysqli_fetch_assoc($recents_query)) {
                echo "<tr><td>{$recent_server['name']}</td><td>{$recent_server['players']}</td></tr>";
        }
        echo "</table>";

        db_disconnect($link);
?>
