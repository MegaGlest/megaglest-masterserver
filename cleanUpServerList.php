<?php
//      Copyright (C) 2012 Mark Vejvoda, Titus Tscharntke and Tom Reynolds
//      The MegaGlest Team, under GNU GPL v3.0
// ==============================================================

	// This script can be invoked directly by a cron job on a regular basis:
	// /path/to/php -f /path/to/script.php

	define( 'INCLUSION_PERMITTED', true );
	require_once( __DIR__ . '/config.php' );
	require_once( __DIR__ . '/functions.php' );

	define( 'DB_LINK', db_connect() );

	cleanupServerList();

	db_disconnect( DB_LINK );
?>
