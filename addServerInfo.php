<?php
//	Copyright (C) 2012 Mark Vejvoda, Titus Tscharntke and Tom Reynolds
//	The MegaGlest Team, under GNU GPL v3.0
// ==============================================================

	define( 'INCLUSION_PERMITTED', true );

	require_once( 'registry.php' );
	require_once( 'config.php' );
	require_once( 'functions.php' );

	// Consider using HTTP POST instead of HTTP GET here, data should always be sent via POST for privacy and security reasons
	// Alternatively, do not retrieve (and transmit) this data at all via HTTP (other than the IP address the game servers advertises) but fetch it from the game server instead

	// general info:
	$glestVersion      = (string) clean_str( $_GET['glestVersion'] );
	$platform          = (string) clean_str( $_GET['platform'] );
	$binaryCompileDate = (string) clean_str( $_GET['binaryCompileDate'] );
	if ( isset( $_GET['privacyPlease'] ) ) {
		$privacyPlease = (int) $_GET['privacyPlease'];
	}
	else
	{
		$privacyPlease = 0;
	}

	// Helper to resolve country code honoring privacy preference.
    function resolve_country( $ip, $privacyPlease ) {
        if ( (int)$privacyPlease !== 0 ) {
            return '';
        }

		if ( extension_loaded('maxminddb') ) {
            $dbPath = defined('GEOIP_DB_PATH') && GEOIP_DB_PATH !== '' ? GEOIP_DB_PATH : '';
            
            if ( $dbPath !== '' && file_exists($dbPath) ) {
                try {
                    // 🚀 Instantiates the native C-compiled binary reader class
                    $reader = new \MaxMind\Db\Reader($dbPath);
                    $record = $reader->get($ip);
                    $reader->close();

                    if (isset($record['country']['iso_code'])) {
                        return $record['country']['iso_code'];
                    }
                } catch (\Exception $e) {
                    error_log("MaxMind PECL Extension Exception: " . $e->getMessage());
                }
            } else {
                error_log("GeoIP2 Warning: Database missing or unconfigured at: " . $dbPath);
            }
        }


        if ( defined('ALLOW_FALLBACK_GEOLOCATION_LOOKUP') && ALLOW_FALLBACK_GEOLOCATION_LOOKUP === true ) {
            $url = 'http://ip-api.com/line/' . rawurlencode( $ip ) . '?fields=countryCode';
            $ctx = stream_context_create(array('http' => array('timeout' => 2)));
            $res = @file_get_contents( $url, false, $ctx );
            if ( $res !== false ) {
                return trim( $res );
            }
        }
        
        return '';
    }

	// game info:
    $serverTitle = (string) clean_str( $_GET['serverTitle'] );
    
	// Determine the real game server IP securely
    $remote_ip = '';

    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $remote_ip = clean_str($_SERVER['HTTP_X_REAL_IP']);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $remote_ip = clean_str(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    } 
    
    // If headers are missing, use the direct connection IP
    if (empty($remote_ip) || !filter_var($remote_ip, FILTER_VALIDATE_IP)) {
        $remote_ip = !empty($_SERVER['REMOTE_ADDR']) ? clean_str($_SERVER['REMOTE_ADDR']) : '';
    }

    // If it's a local/Docker internal IP, attempt the legacy external lookup function
    if ( $remote_ip === '127.0.0.1' || strncmp( $remote_ip, '172.', 4 ) === 0 || strncmp( $remote_ip, '192.168.', 8 ) === 0 || empty($remote_ip) )
    {
        if (function_exists('get_external_ip')) {
            $ext = get_external_ip();
            if (!empty($ext) && filter_var($ext, FILTER_VALIDATE_IP)) { 
                $remote_ip = $ext; 
            }
        }
    }

    // 🚨 EMERGENCY BREAK: If everything failed, do not try to open a socket to nothing
    if (empty($remote_ip)) {
        error_log("Masterserver Error: Could not determine client IP address.");
        die("unable to determine your IP address");
    }

	$service_port      = (int)    clean_str( $_GET['externalconnectport'] );

	// If the game server port was not transmitted...
	if ( $service_port == '' || $service_port == 0 ) 
	{
		// ..then assume the default port
		$service_port = 61357;
		// ... alternatively, refuse such servers
		/*
		header( 'Content-Type: text/plain; charset=utf-8' );
		die( 'Invalid external connect port.');
		*/
	}	

	
	// game setup info:
	$tech              = (string) clean_str( $_GET['tech'] );
	$map               = (string) clean_str( $_GET['map'] );
	$tileset           = (string) clean_str( $_GET['tileset'] );
	$activeSlots       = (int)    clean_str( $_GET['activeSlots'] );
	$networkSlots      = (int)    clean_str( $_GET['networkSlots'] );
	$connectedClients  = (int)    clean_str( $_GET['connectedClients'] );
	
	$status = 0;
    	if(isset($_GET["gameStatus"])) {
        	$status  	   = (int)    clean_str( $_GET['gameStatus'] );
	}
    
	$gameCmd = "";
    	if(isset($_GET["gameCmd"])) {
        	$gameCmd = (string)    clean_str( $_GET['gameCmd'] );
	}
	
	define( 'DB_LINK', db_connect() );

	// consider replacing this by a cron job
	cleanupServerList();

        $gameUUID = "";
        $whereClause = 'ip=\'' . mysqli_real_escape_string(Registry::$mysqliLink, $remote_ip ) . '\' && externalServerPort=\'' . mysqli_real_escape_string(Registry::$mysqliLink, $service_port ) . '\';';
        if ( isset( $_GET['gameUUID'] ) ) {
                $gameUUID  = (string) clean_str( $_GET['gameUUID'] );
                $whereClause = 'gameUUID=\'' . mysqli_real_escape_string(Registry::$mysqliLink, $gameUUID ) . '\';';
        }
        // echo '#1 ' . $whereClause;

	$server_in_db = @mysqli_query(Registry::$mysqliLink, 'SELECT ip, externalServerPort FROM glestserver WHERE ' . $whereClause );
       	$server       = @mysqli_fetch_row( $server_in_db );

	// Representation starts here (but it should really be starting much later, there is way too much logic behind this point)
	header( 'Content-Type: text/plain; charset=utf-8' );

	if ( (version_compare($glestVersion,"v3.4.0-dev","<") && $connectedClients == $networkSlots)  || $gameCmd == "gameOver")   // game servers' slots are all full
	{ 
                if($gameCmd == "gameOver" && $gameUUID != "") 
                {
                        // update database info on this game server; no checks are performed
		        mysqli_query(Registry::$mysqliLink, 'UPDATE glestserver SET ' .
			        'glestVersion=\''      . mysqli_real_escape_string(Registry::$mysqliLink, $glestVersion )      . '\', ' .
			        'platform=\''          . mysqli_real_escape_string(Registry::$mysqliLink, $platform )          . '\', ' .
			        'binaryCompileDate=\'' . mysqli_real_escape_string(Registry::$mysqliLink, $binaryCompileDate ) . '\', ' .
			        'serverTitle=\''       . mysqli_real_escape_string(Registry::$mysqliLink, $serverTitle )       . '\', ' .
			        'tech=\''              . mysqli_real_escape_string(Registry::$mysqliLink, $tech )              . '\', ' .
			        'map=\''               . mysqli_real_escape_string(Registry::$mysqliLink, $map )               . '\', ' .
			        'tileset=\''           . mysqli_real_escape_string(Registry::$mysqliLink, $tileset )           . '\', ' .
			        'activeSlots=\''       . mysqli_real_escape_string(Registry::$mysqliLink, $activeSlots )       . '\', ' .
			        'networkSlots=\''      . mysqli_real_escape_string(Registry::$mysqliLink, $networkSlots )      . '\', ' .
			        'connectedClients=\''  . mysqli_real_escape_string(Registry::$mysqliLink, $connectedClients )  . '\', ' .
			        'externalServerPort=\''. mysqli_real_escape_string(Registry::$mysqliLink, $service_port )      . '\', ' .
			        'status=\''            . mysqli_real_escape_string(Registry::$mysqliLink, $status )            . '\', ' .
			        'lasttime='            . 'now()'                                        .    ' ' .
			        'WHERE ' . $whereClause);
                }
                else 
                {
                        // delete server; no checks are performed
		        mysqli_query(Registry::$mysqliLink, 'DELETE FROM glestserver WHERE ' . $whereClause );
                }
		echo 'OK' ;
	}                                                                      // game in progress
        else 
        {
			// FIX: Safe array extraction check protecting against empty database query sets
			if ( is_array($server) ) {
				$game_host_ip   = $server[0];
				$game_host_port = $server[1];
			} else {
				$game_host_ip   = '';
				$game_host_port = '';
			}

			if ( $gameUUID != "" ) {
				$server_in_db_not_timedout = @mysqli_query(Registry::$mysqliLink, 'DELETE FROM glestserver WHERE ip=\'' . 
							mysqli_real_escape_string(Registry::$mysqliLink, $remote_ip ) . '\' AND externalServerPort=\'' . 
							mysqli_real_escape_string(Registry::$mysqliLink, $service_port ) . '\' AND gameUUID <> \'' . 
							mysqli_real_escape_string(Registry::$mysqliLink, $gameUUID ) . '\' AND status in (0,1,2);' );
			}

	        if ( ($remote_ip == $game_host_ip && $service_port == $game_host_port) || $status == 2 )    // this server is contained in the database
	        { 
                        if ( $remote_ip == $game_host_ip && $service_port == $game_host_port)
                        {
                                // update database info on this game server; no checks are performed
		                mysqli_query(Registry::$mysqliLink, 'UPDATE glestserver SET ' .
			                'glestVersion=\''      . mysqli_real_escape_string(Registry::$mysqliLink, $glestVersion )      . '\', ' .
			                'platform=\''          . mysqli_real_escape_string(Registry::$mysqliLink, $platform )          . '\', ' .
			                'binaryCompileDate=\'' . mysqli_real_escape_string(Registry::$mysqliLink, $binaryCompileDate ) . '\', ' .
			                'serverTitle=\''       . mysqli_real_escape_string(Registry::$mysqliLink, $serverTitle )       . '\', ' .
			                'tech=\''              . mysqli_real_escape_string(Registry::$mysqliLink, $tech )              . '\', ' .
			                'map=\''               . mysqli_real_escape_string(Registry::$mysqliLink, $map )               . '\', ' .
			                'tileset=\''           . mysqli_real_escape_string(Registry::$mysqliLink, $tileset )           . '\', ' .
			                'activeSlots=\''       . mysqli_real_escape_string(Registry::$mysqliLink, $activeSlots )       . '\', ' .
			                'networkSlots=\''      . mysqli_real_escape_string(Registry::$mysqliLink, $networkSlots )      . '\', ' .
			                'connectedClients=\''  . mysqli_real_escape_string(Registry::$mysqliLink, $connectedClients )  . '\', ' .
			                'externalServerPort=\''. mysqli_real_escape_string(Registry::$mysqliLink, $service_port )      . '\', ' .
			                'status=\''            . mysqli_real_escape_string(Registry::$mysqliLink, $status )            . '\', ' .
			                'lasttime='            . 'now()'                                        .    ' ' .
			                'WHERE ' . $whereClause);
		                //updateServer($remote_ip, $service_port, $serverTitle, $connectedClients, $networkSlots);
		                echo 'OK';
                        }
                        else if ($status == 2)
                        {

							$country = resolve_country( $remote_ip, $privacyPlease );

	                        // cleanup old entrys with same remote port and ip
	                        // I hope this fixes those double entrys of servers
	                        mysqli_query(Registry::$mysqliLink, 'DELETE FROM glestserver WHERE '. $whereClause );

	                        // insert new entry
	                        mysqli_query(Registry::$mysqliLink, 'INSERT INTO glestserver SET ' .
		                        'glestVersion=\''      . mysqli_real_escape_string(Registry::$mysqliLink, $glestVersion )      . '\', ' .
		                        'platform=\''          . mysqli_real_escape_string(Registry::$mysqliLink, $platform )          . '\', ' .
		                        'binaryCompileDate=\'' . mysqli_real_escape_string(Registry::$mysqliLink, $binaryCompileDate ) . '\', ' .
		                        'serverTitle=\''       . mysqli_real_escape_string(Registry::$mysqliLink, $serverTitle )       . '\', ' .
		                        'ip=\''                . mysqli_real_escape_string(Registry::$mysqliLink, $remote_ip )         . '\', ' .
		                        'tech=\''              . mysqli_real_escape_string(Registry::$mysqliLink, $tech )              . '\', ' .
		                        'map=\''               . mysqli_real_escape_string(Registry::$mysqliLink, $map )               . '\', ' .
		                        'tileset=\''           . mysqli_real_escape_string(Registry::$mysqliLink, $tileset )           . '\', ' .
		                        'activeSlots=\''       . mysqli_real_escape_string(Registry::$mysqliLink, $activeSlots )       . '\', ' .
		                        'networkSlots=\''      . mysqli_real_escape_string(Registry::$mysqliLink, $networkSlots )      . '\', ' .
		                        'connectedClients=\''  . mysqli_real_escape_string(Registry::$mysqliLink, $connectedClients )  . '\', ' .
		                        'externalServerPort=\''. mysqli_real_escape_string(Registry::$mysqliLink, $service_port )      . '\', ' .
		                        'country=\''           . mysqli_real_escape_string(Registry::$mysqliLink, $country )           . '\', ' .
		                        'status=\''            . mysqli_real_escape_string(Registry::$mysqliLink, $status )            . '\', ' .
                                        'gameUUID=\''          . mysqli_real_escape_string(Registry::$mysqliLink, $gameUUID )     . '\';'
	                        );
	                        echo 'OK';
                        }
	        }
	        else                                        // this game server is not listed in the database, yet
	        { // check whether this game server is available from the Internet; if it is, add it to the database
		        sleep(8); // was 3
		        $socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
		        if ( $socket < 0 ) {
		            echo 'socket_create() failed.' . PHP_EOL . ' Reason: ' . socket_strerror( $socket ) . PHP_EOL;
		        } 
		        socket_set_nonblock( $socket )
	              		or die( 'Unable to set nonblock on socket.' );
	              
	                $timeout = 10;  //timeout in seconds
		        echo 'Trying to connect to \'' . $remote_ip . '\' using port \'' . $service_port . '\'...' . PHP_EOL;
		
		        $canconnect = true;
		        $time = time();
		        error_reporting( E_ERROR );
		
		        for ( ; !@socket_connect( $socket, $remote_ip, $service_port ); )
	            	{
	              		$socket_last_error = socket_last_error( $socket );
	              		if ( $socket_last_error == 115 || $socket_last_error == 114)
	              		{
	                		if ( ( time() - $time ) >= $timeout )
	                		{
	                  			$canconnect = false;
	                  			echo 'socket_connect() failed.' . PHP_EOL . ' Reason: (' . $socket_last_error . ') ' . socket_strerror( $socket_last_error ) . PHP_EOL;
	                  			break;
	                		}
	                		sleep( 1 );
	                		continue;
	              		}
	              		// for answers on this see: http://bobobobo.wordpress.com/2008/11/09/resolving-winsock-error-10035-wsaewouldblock/
	              		else if($socket_last_error == 10035 || $socket_last_error == 10037) {
	              			break;
	              		}
	              		
	              		$canconnect = false;
	                	echo 'socket_connect() failed.' . PHP_EOL . ' Reason: (' . $socket_last_error . ') ' . socket_strerror( $socket_last_error ) . PHP_EOL;
	                  	break;
	            	}	
		
		        socket_set_block( $socket )
              			or die( 'Unable to set block on socket.' );

		        //echo "and now read ....";
		        //$buf = socket_read($socket, 161);
		        //echo $buf ."\n";

		        // Make sure its a glest server connecting
		        //
		        // struct Data{
		        //	int8 messageType;
		        //	NetworkString<maxVersionStringSize> versionString;
		        //	NetworkString<maxNameSize> name;
		        //	int16 playerIndex;
		        //	int8 gameState;
		        // };
		
		
		        if ( $canconnect == true ) {
			        $data_from_server = socket_read( $socket, 1 );
		        }
		

		        socket_close( $socket );
		
		        error_reporting( E_ALL );

		        if ( $canconnect == false )
               		{
			        echo 'wrong router setup';
		        }
         		/*
		        else if ( $data_from_server != 1 )   // insert serious verification here
		        {
			        echo "invalid handshake!";
		        }
		        */
		        else  // connection to game server succeeded, protocol verification succeeded
		        { // add this game server to the database
					$country = resolve_country( $remote_ip, $privacyPlease );

			        // cleanup old entrys with same remote port and ip
			        // I hope this fixes those double entrys of servers
			        mysqli_query(Registry::$mysqliLink, 'DELETE FROM glestserver WHERE '. $whereClause );
			        // insert new entry
			        mysqli_query(Registry::$mysqliLink, 'INSERT INTO glestserver SET ' .
				        'glestVersion=\''      . mysqli_real_escape_string(Registry::$mysqliLink, $glestVersion )      . '\', ' .
				        'platform=\''          . mysqli_real_escape_string(Registry::$mysqliLink, $platform )          . '\', ' .
				        'binaryCompileDate=\'' . mysqli_real_escape_string(Registry::$mysqliLink, $binaryCompileDate ) . '\', ' .
				        'serverTitle=\''       . mysqli_real_escape_string(Registry::$mysqliLink, $serverTitle )       . '\', ' .
				        'ip=\''                . mysqli_real_escape_string(Registry::$mysqliLink, $remote_ip )         . '\', ' .
				        'tech=\''              . mysqli_real_escape_string(Registry::$mysqliLink, $tech )              . '\', ' .
				        'map=\''               . mysqli_real_escape_string(Registry::$mysqliLink, $map )               . '\', ' .
				        'tileset=\''           . mysqli_real_escape_string(Registry::$mysqliLink, $tileset )           . '\', ' .
				        'activeSlots=\''       . mysqli_real_escape_string(Registry::$mysqliLink, $activeSlots )       . '\', ' .
				        'networkSlots=\''      . mysqli_real_escape_string(Registry::$mysqliLink, $networkSlots )      . '\', ' .
				        'connectedClients=\''  . mysqli_real_escape_string(Registry::$mysqliLink, $connectedClients )  . '\', ' .
				        'externalServerPort=\''. mysqli_real_escape_string(Registry::$mysqliLink, $service_port )      . '\', ' .
				        'country=\''           . mysqli_real_escape_string(Registry::$mysqliLink, $country )           . '\', ' .
				        'status=\''            . mysqli_real_escape_string(Registry::$mysqliLink, $status )            . '\', ' .
                                        'gameUUID=\''          . mysqli_real_escape_string(Registry::$mysqliLink, $gameUUID )          . '\';'
			        );
			        echo 'OK';
			        //addLatestServer($remote_ip, $service_port, $serverTitle, $connectedClients, $networkSlots);
		        }
	        }
        }
	db_disconnect( Registry::$mysqliLink );
?>
