<?php
//	Copyright (C) 2012 Mark Vejvoda, Titus Tscharntke and Tom Reynolds
//	The MegaGlest Team, under GNU GPL v3.0
// ==============================================================

	define( 'INCLUSION_PERMITTED', true );
	require_once( 'config.php' );
	require_once( 'functions.php' );

	// Consider using HTTP POST instead of HTTP GET here, data should always be sent via POST for privacy and security reasons
	// Alternatively, do not retrieve (and transmit) this data at all via HTTP (other than the IP address the game servers advertises) but fetch it from the game server instead

	// consider replacing this by a cron job
	// cleanupServerList();

	// Representation starts here (but it should really be starting much later, there is way too much logic behind this point)
	header( 'Content-Type: text/plain; charset=utf-8' );

        //echo '#0 ' . $_GET['gameUUID'];

        if ( isset( $_GET['gameUUID'] ) ) {
                define( 'DB_LINK', db_connect() );

                $gameUUID = (string) clean_str( $_GET['gameUUID'] );
                $whereClause = 'gameUUID=\'' . mysqli_real_escape_string(Registry::$mysqliLink, $gameUUID ) . '\'';

                $gameDuration = 0;
                $framesToCalculatePlaytime = 0;
                if ( isset( $_GET['framesToCalculatePlaytime'] ) ) {
                        $framesToCalculatePlaytime = (string) clean_str( $_GET['framesToCalculatePlaytime'] );
                        $gameDuration = $framesToCalculatePlaytime / 40 / 60;
                }

                if($gameDuration < MAX_MINS_OLD_COMPLETED_GAMES)
                {
	                $game_completed = @mysqli_query( Registry::$mysqliLink, 'SELECT COUNT(*) FROM glestserver WHERE ' . $whereClause . ' AND status=3;' );
                       	$game_completed_status  = @mysqli_fetch_row( $game_completed );
                        if( $game_completed_status[0] > 0 )
                        {
                                mysqli_query( Registry::$mysqliLink, 'DELETE FROM glestserver WHERE ' . $whereClause . ';');
                                mysqli_query( Registry::$mysqliLink, 'DELETE FROM glestgamestats WHERE ' . $whereClause . ';');
                                mysqli_query( Registry::$mysqliLink, 'DELETE FROM glestgameplayerstats WHERE ' . $whereClause . ';');

                                echo 'OK - ' . $gameDuration;
                                return;
                        }
                }

	        $stats_in_db = @mysqli_query( Registry::$mysqliLink, 'SELECT COUNT(*) FROM glestgamestats WHERE ' . $whereClause . ';' );
               	$statsCount  = @mysqli_fetch_row( $stats_in_db );
	        $player_stats_in_db = @mysqli_query( Registry::$mysqliLink, 'SELECT COUNT(*) FROM glestgameplayerstats WHERE ' . $whereClause . ';');
               	$player_statsCount  = @mysqli_fetch_row( $player_stats_in_db );

                
                $gameUUID = (string) clean_str( $_GET['gameUUID'] );
                $tech = (string) clean_str( $_GET['tech'] );
                $factionCount = 0;
                if ( isset( $_GET['factionCount'] ) ) {
                        $factionCount = (string) clean_str( $_GET['factionCount'] );
                }
                $framesPlayed = 0;
                if ( isset( $_GET['framesPlayed'] ) ) {
                        $framesPlayed = (string) clean_str( $_GET['framesPlayed'] );
                }
                $maxConcurrentUnitCount = 0;
                if ( isset( $_GET['maxConcurrentUnitCount'] ) ) {
                        $maxConcurrentUnitCount = (string) clean_str( $_GET['maxConcurrentUnitCount'] );
                }
                $totalEndGameConcurrentUnitCount = 0;
                if ( isset( $_GET['totalEndGameConcurrentUnitCount'] ) ) {
                        $totalEndGameConcurrentUnitCount = (string) clean_str( $_GET['totalEndGameConcurrentUnitCount'] );
                }
                $isHeadlessServer = 0;
                if ( isset( $_GET['isHeadlessServer'] ) ) {
                        $isHeadlessServer = (string) clean_str( $_GET['isHeadlessServer'] );
                }

                //echo '#1 ' . $whereClause;
                //echo '#2 ' . $statsCount[0];

	        if ( $statsCount[0] > 0 )    // this game is contained in the database
	        { 
                        // update database info on this game server; no checks are performed
	                $result = mysqli_query( Registry::$mysqliLink, 'UPDATE glestgamestats SET ' .
		                'gameUUID=\''                           . mysqli_real_escape_string(Registry::$mysqliLink, $gameUUID )          . '\', ' .
                                'tech=\''                               . mysqli_real_escape_string(Registry::$mysqliLink, $tech )      . '\', ' .
		                'factionCount=\''                       . mysqli_real_escape_string(Registry::$mysqliLink, $factionCount ) . '\', ' .
		                'framesPlayed=\''                       . mysqli_real_escape_string(Registry::$mysqliLink, $framesPlayed )       . '\', ' .
		                'framesToCalculatePlaytime=\''          . mysqli_real_escape_string(Registry::$mysqliLink, $framesToCalculatePlaytime )              . '\', ' .
		                'maxConcurrentUnitCount=\''             . mysqli_real_escape_string(Registry::$mysqliLink, $maxConcurrentUnitCount )               . '\', ' .
		                'totalEndGameConcurrentUnitCount=\''    . mysqli_real_escape_string(Registry::$mysqliLink, $totalEndGameConcurrentUnitCount )           . '\', ' .
		                'isHeadlessServer=\''                   . mysqli_real_escape_string(Registry::$mysqliLink, $isHeadlessServer )       . '\', ' .
		                'lasttime='            . 'now()'                                        .    ' ' .
		                'WHERE ' . $whereClause . ';');

                        if (!$result) {
                                die('part 1a: Invalid query: ' . mysql_error());
                        }

	                echo 'OK1a';
	        }
	        else                                        // this game server is not listed in the database, yet
	        { // check whether this game server is available from the Internet; if it is, add it to the database
                        // update database info on this game server; no checks are performed
	                $result = mysqli_query( Registry::$mysqliLink, 'INSERT INTO glestgamestats SET ' .
		                'gameUUID=\''                           . mysqli_real_escape_string(Registry::$mysqliLink, $gameUUID )          . '\', ' .
                                'tech=\''                               . mysqli_real_escape_string(Registry::$mysqliLink, $tech )      . '\', ' .
		                'factionCount=\''                       . mysqli_real_escape_string(Registry::$mysqliLink, $factionCount ) . '\', ' .
		                'framesPlayed=\''                       . mysqli_real_escape_string(Registry::$mysqliLink, $framesPlayed )       . '\', ' .
		                'framesToCalculatePlaytime=\''          . mysqli_real_escape_string(Registry::$mysqliLink, $framesToCalculatePlaytime )              . '\', ' .
		                'maxConcurrentUnitCount=\''             . mysqli_real_escape_string(Registry::$mysqliLink, $maxConcurrentUnitCount )               . '\', ' .
		                'totalEndGameConcurrentUnitCount=\''    . mysqli_real_escape_string(Registry::$mysqliLink, $totalEndGameConcurrentUnitCount )           . '\', ' .
		                'isHeadlessServer=\''                   . mysqli_real_escape_string(Registry::$mysqliLink, $isHeadlessServer )       . '\';');

                        if (!$result) {
                                die('part 2a: Invalid query: ' . mysql_error());
                        }

                        echo 'OK2b';
	        }

                for ( $factionNumber = 0; $factionNumber < $factionCount ; $factionNumber++)
                {
                        // Player details
                        $factionIndex = 0;
                        if ( isset( $_GET['factionIndex_' . $factionNumber ] ) ) {
                                $factionIndex = clean_str( $_GET['factionIndex_' . $factionNumber] );
                        }
                        
                        $controlType = 0;
                        if ( isset( $_GET['controlType_' . $factionNumber] ) ) {
                                $controlType = clean_str( $_GET['controlType_' . $factionNumber] );
                        }

                        $resourceMultiplier = 0;
                        if ( isset( $_GET['resourceMultiplier_' . $factionNumber] ) ) {
                                $resourceMultiplier = clean_str( $_GET['resourceMultiplier_' . $factionNumber] );
                        }

                        $factionTypeName = "";
                        if ( isset( $_GET['factionTypeName_' . $factionNumber] ) ) {
                                $factionTypeName = (string) clean_str( $_GET['factionTypeName_' . $factionNumber] );
                        }

                        $personalityType = 0;
                        if ( isset( $_GET['personalityType_' . $factionNumber] ) ) {
                                $personalityType = clean_str( $_GET['personalityType_' . $factionNumber] );
                        }

                        $teamIndex = 0;
                        if ( isset( $_GET['teamIndex_' . $factionNumber] ) ) {
                                $teamIndex = clean_str( $_GET['teamIndex_' . $factionNumber] );
                        }

                        $wonGame = 0;
                        if ( isset( $_GET['wonGame_' . $factionNumber] ) ) {
                                $wonGame = clean_str( $_GET['wonGame_' . $factionNumber] );
                        }

                        $killCount = 0;
                        if ( isset( $_GET['killCount_' . $factionNumber] ) ) {
                                $killCount = clean_str( $_GET['killCount_' . $factionNumber] );
                        }

                        $enemyKillCount = 0;
                        if ( isset( $_GET['enemyKillCount_' . $factionNumber] ) ) {
                                $enemyKillCount = clean_str( $_GET['enemyKillCount_' . $factionNumber] );
                        }

                        $deathCount = 0;
                        if ( isset( $_GET['deathCount_' . $factionNumber] ) ) {
                                $deathCount = clean_str( $_GET['deathCount_' . $factionNumber] );
                        }

                        $unitsProducedCount = 0;
                        if ( isset( $_GET['unitsProducedCount_' . $factionNumber] ) ) {
                                $unitsProducedCount = clean_str( $_GET['unitsProducedCount_' . $factionNumber] );
                        }

                        $resourceHarvestedCount = 0;
                        if ( isset( $_GET['resourceHarvestedCount_' . $factionNumber] ) ) {
                                $resourceHarvestedCount = clean_str( $_GET['resourceHarvestedCount_' . $factionNumber] );
                        }

                        $playerName = "";
                        if ( isset( $_GET['playerName_' . $factionNumber] ) ) {
                                $playerName = (string) clean_str( $_GET['playerName_' . $factionNumber] );
                        }

                        $quitBeforeGameEnd = 0;
                        if ( isset( $_GET['quitBeforeGameEnd_' . $factionNumber] ) ) {
                                $quitBeforeGameEnd = clean_str( $_GET['quitBeforeGameEnd_' . $factionNumber] );
                        }

                        $quitTime = 0;
                        if ( isset( $_GET['quitTime_' . $factionNumber] ) ) {
                                $quitTime = clean_str( $_GET['quitTime_' . $factionNumber] );
                        }

                        $playerUUID = "";
                        if ( isset( $_GET['playerUUID_' . $factionNumber] ) ) {
                                $playerUUID = (string) clean_str( $_GET['playerUUID_' . $factionNumber] );
                        }

                        $playerPlatform = "";
                        if ( isset( $_GET['platform_' . $factionNumber] ) ) {
                                $playerPlatform = (string) clean_str( $_GET['platform_' . $factionNumber] );
                        }

                        if($player_statsCount[0] > 0)
                        {
                                $result = mysqli_query( Registry::$mysqliLink, 'UPDATE glestgameplayerstats SET ' .
		                                'gameUUID=\''             . mysqli_real_escape_string(Registry::$mysqliLink, $gameUUID )          . '\', ' .
		                                'factionIndex='           . $factionIndex       . ', ' .
                                                'controlType='            . $controlType        . ', ' .
                                                'resourceMultiplier='     . $resourceMultiplier . ', ' .
                                                'factionTypeName=\''      . mysqli_real_escape_string(Registry::$mysqliLink, $factionTypeName ) . '\', ' .
                                                'personalityType='        . $personalityType    . ', ' .
                                                'teamIndex='              . $teamIndex          . ', ' .
                                                'wonGame='                . $wonGame            . ', ' .
                                                'killCount='              . $killCount          . ', ' .
                                                'enemyKillCount='         . $enemyKillCount     . ', ' .
                                                'deathCount='             . $deathCount         . ', ' .
                                                'unitsProducedCount='     . $unitsProducedCount . ', ' .
                                                'resourceHarvestedCount=' . $resourceHarvestedCount . ', ' .
                                                'playerName=\''           . mysqli_real_escape_string(Registry::$mysqliLink, $playerName ) . '\', ' .
                                                'quitBeforeGameEnd='      . $quitBeforeGameEnd  . ', ' .
                                                'quitTime='               . $quitTime           . ', ' .
                                                'playerUUID=\''           . mysqli_real_escape_string(Registry::$mysqliLink, $playerUUID ) . '\', ' .
                                                'platform=\''             . mysqli_real_escape_string(Registry::$mysqliLink, $playerPlatform ) . '\', ' .
	                                        'lasttime='               . 'now()'             . ' ' .
	                                        'WHERE ' . $whereClause . ' AND factionIndex = ' . $factionIndex . ';');

                                if (!$result) {
                                    die('part 1b: Invalid query: ' . mysql_error());
                                }

                                //echo 'OK1 $factionNumber = ' . $factionNumber;
                                echo 'OK1b' . $factionNumber;
                        }
                        else
                        {
	                        $result = mysqli_query( Registry::$mysqliLink, 'INSERT INTO glestgameplayerstats SET ' .
		                        'gameUUID=\''             . mysqli_real_escape_string(Registry::$mysqliLink, $gameUUID )          . '\', ' .
		                        'factionIndex='           . $factionIndex       . ', ' .
                                        'controlType='            . $controlType        . ', ' .
                                        'resourceMultiplier='     . $resourceMultiplier . ', ' .
                                        'factionTypeName=\''      . mysqli_real_escape_string(Registry::$mysqliLink, $factionTypeName ) . '\', ' .
                                        'personalityType='        . $personalityType    . ', ' .
                                        'teamIndex='              . $teamIndex          . ', ' .
                                        'wonGame='                . $wonGame            . ', ' .
                                        'killCount='              . $killCount          . ', ' .
                                        'enemyKillCount='         . $enemyKillCount     . ', ' .
                                        'deathCount='             . $deathCount         . ', ' .
                                        'unitsProducedCount='     . $unitsProducedCount . ', ' .
                                        'resourceHarvestedCount=' . $resourceHarvestedCount . ', ' .
                                        'playerName=\''           . mysqli_real_escape_string(Registry::$mysqliLink, $playerName ) . '\', ' .
                                        'quitBeforeGameEnd='      . $quitBeforeGameEnd  . ', ' .
                                        'quitTime='               . $quitTime           . ', ' .
                                        'platform=\''             . mysqli_real_escape_string(Registry::$mysqliLink, $playerPlatform ) . '\', ' .
                                        'playerUUID=\''           . mysqli_real_escape_string(Registry::$mysqliLink, $playerUUID ) . '\';');

                                if (!$result) {
                                    die('part 2b: Invalid query: ' . mysql_error());
                                }

                                //echo 'OK2 $factionNumber = ' . $factionNumber;
                                echo 'OK2b' . $factionNumber;
                      }
                }

	        db_disconnect( Registry::$mysqliLink );
        }
?>
