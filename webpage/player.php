<?php
require_once("config.php");
include_once("functions.php");
include_once("head.php");
?>

<!-- used for javascript expand of the game limit
<script type="text/javascript">
$limit=10;
</script>
-->
<body>
  <div class="container">
    <div class="row">
      <div class="col-md-12">
<?php include("navbar.php") ?>
      </div>
    </div>
    <?php
    $name  = $db->real_escape_string($_GET['name']);
	//games
	$result = $db->query("SELECT COUNT(*) as count,SUM(wonGame=1) as won , SUM(ggs.framesToCalculatePlaytime)/30 as playtime FROM glestgameplayerstats s, glestgamestats ggs WHERE playerName='$name' AND controltype>4 AND s.gameUUID=ggs.gameUUID"); //controltype needs to be 5 to prevent counting of cpus when their name is not set
	$row = $result->fetch_assoc();
	$totalGames = $row['count'];
	$won = $row['won'];
	$playtime = secondsToTime($row['playtime']);
	$lost = $row['count']-$row['won'];
	//name
	//we need this if we change to playeruuids
	//$result = $db->query("SELECT playerName FROM glestgameplayerstats WHERE playerName='$name' LIMIT 1");
	//$name = $result->fetch_assoc()['playerName'];
	//faction
	$result = $db->query("SELECT factionTypeName,COUNT(*) as count FROM glestgameplayerstats WHERE playerName='$name' GROUP BY factionTypeName ORDER BY count DESC");
	$faction = $result->fetch_assoc();
	//map
	$result = $db->query("SELECT a.map as map, COUNT(*) as count FROM glestserver a LEFT JOIN glestgamestats b ON a.gameUUID = b.gameUUID INNER JOIN glestgameplayerstats c ON a.gameUUID = c.gameUUID AND c.playerName='$name' WHERE status = 3 GROUP BY a.map ORDER BY count DESC");
	$map = $result->fetch_assoc();
	//tileset
	$result = $db->query("SELECT a.tileset as tileset, COUNT(*) as count FROM glestserver a LEFT JOIN glestgamestats b ON a.gameUUID = b.gameUUID INNER JOIN glestgameplayerstats c ON a.gameUUID = c.gameUUID AND c.playerName='$name' WHERE status = 3 GROUP BY a.tileset ORDER BY count DESC");
	$tileset = $result->fetch_assoc();
    ?>
    <div class="row">
      <div class="col-md-12">
        <h2><?=$name."s profile"?></h2>
      </div>
    </div>
    <div class="row">
      <div class="col-md-6">
        <h3>General Stats</h3>
        <table class="table table-bordered table-striped">
          <tbody>
            <tr>
              <td>Total Games</td>
              <td><?='<b>'.$totalGames.'</b> (<small>Won:</small> '.$won.' <small>Lost:</small> '.$lost.')'?></td>
            </tr>            <tr>
              <td>Total Time played</td>
              <td><?php
              if($playtime['d']>0){
                echo "<b>".$playtime['d']."</b> <small>days</small> ";
              }
              echo "<b>".$playtime['h']."</b> <small>hours</small> <b>".$playtime['m']."</b> <small>min.</small> ";
              ?></td>
            </tr>
           <tr>
              <td>Most played Faction</td>
              <td><?='<b>'.$faction['factionTypeName'].'</b> ('.$faction['count'].' <small>times</small>)'?></td>
            </tr>
            <tr>
              <td>Most played Map</td>
              <!-- we don't use this now, maybe later <td><?='<b>'.ucfirst($map['map']).'</b> ('.$map['count'].' <small>times</small>)'//map is lowercase somehow?></td>-->
              <td><?='<b>'.$map['map'].'</b> ('.$map['count'].' <small>times</small>)'//map is lowercase somehow?></td>
            </tr>
            <tr>
              <td>Most played Tileset</td>
              <!-- we don't use this now, maybe later <td><?='<b>'.ucfirst($tileset['tileset']).'</b> ('.$tileset['count'].' <small>times</small>)'//tileset is lowercase somehow?></td>-->
              <td><?='<b>'.$tileset['tileset'].'</b> ('.$tileset['count'].' <small>times</small>)'//tileset is lowercase somehow?></td>
            </tr>
         </tbody>
        </table>
    </div>
    <div class="row">
      <div class="col-md-6">
        <h3>Last Games</h3>
        <?php
		$gameLimit=3;
        if(isset($_GET['gameLimit'])){
			$gameLimit=$_GET['gameLimit'];
		}else{
			$gameLimit=50;
		}
        $result = $db->query("SELECT gameUUID FROM glestgameplayerstats WHERE playerName='$name' AND controltype>4 ORDER BY lasttime DESC LIMIT ".$gameLimit); //controltype needs to be 5 to prevent counting of cpus when their name is not set
        $games = array();
        while($row = $result->fetch_assoc()) {
            array_push($games,$row['gameUUID']);
        }
        unset($result);
        ?>
        <table class="table table-bordered table-striped" id="games-table">
          <?php
          foreach($games as $game){
            unset($result);
            unset($row);
            $result = $db->query("SELECT * FROM glestserver WHERE gameUUID='$game' LIMIT 1");
            $row = $result->fetch_assoc();
            if($row['serverTitle'] != ""){
            ?>
            <tr>
            <td><a href="game.php?uuid=<?=$game?>"><?=$row['serverTitle']?></a></td>
            <td><?=$row['lasttime']?></td>
            </tr>
            <?php
          	}
          }
          ?>
        </table>
		<?php
		if($totalGames>$gameLimit){
			echo ("<a href=\"?name=$name&gameLimit=".($gameLimit+50)."\">Load More Games</a>");
		}
		?>
		<!--javascript solution for lower load on server
		<a href="javascript:;" onClick="$( '#games-table' ).load( 'player.php?name=<?=$name.'&gameLimit='?>'+($limit+<?=$gameLimit?>)+' #games-table' );$limit=$limit+10;">Load More Games JAVA</a>
		-->
      </div>
    </div>
  </div>



  <?php 
  	//close connection to db
	$db->close();
	include_once("foot.php");
  ?>
</body>

</html>
