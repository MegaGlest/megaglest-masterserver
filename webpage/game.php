<?php
define( 'INCLUSION_PERMITTED', true );
require_once(__DIR__ . "/functions.php");
include_once(__DIR__ . "/head.php");
?>

<body>
  <div class="container">
    <div class="row">
      <div class="col-md-12">
<?php include(__DIR__ . "/navbar.php") ?>
      </div>
    </div>
    <div class="row">

      <div class="col-md-12">
        <?php
        $db=createDbObject();
        $gameUUID  = $db->real_escape_string($_GET['uuid']);
        $result = $db->query("SELECT * FROM glestserver WHERE gameUUID='$gameUUID' LIMIT 1");
        $row = $result->fetch_assoc();
        $note_tech = $row['tech'];
        $note_ip = $row['ip'];
        ?>
        <h2><?=$row['serverTitle']?></h2>

        <table class="table table-bordered table-striped">
          <tbody>
            <tr>
              <th>Title</th>
              <th>Version</th>
              <th>Country</th>
              <th>Techtree</th>
              <th>Map</th>
              <th>Tileset</th>
              <th>Platform</th>
              <th>Play Date</th>
            </tr>
            <tr>
              <td><a href="game.php?uuid=<?=$row['gameUUID']?>"><?=$row['serverTitle']?></a></td>
              <td><?=$row['glestVersion']?></td>
              <?php if($row['country'] !=""){ ?>
              <td><img src="img/flags/<?=strtolower($row['country'])?>.png" title="<?=$row['country']?>"/></td>
              <?php
            }else {
              ?>
              <td>?</td>
              <?php } ?>
              <td><?=$row['tech']?></td>
              <td><?=$row['map']?></td>
              <td><?=$row['tileset']?></td>
              <td>
                <?php
                $platform = $row['platform'];
                $platform_short = substr($platform,0,1);
                if($platform_short == "L"){
                ?>
                  <img src="img/os/linux.png" title="<?=$platform?>"/>
                <?php
              } else if($platform_short == "W"){
                ?>
                <img src="img/os/windows.png" title="<?=$platform?>"/>
                <?php
              } else {
                echo $platform;
              }
                ?>
                </td>
              <td><?=$row['lasttime']?></td>
            </tr>
          </tbody>
        </table>

        <table class="table table-bordered table-striped">
          <tbody>
            <tr>
              <th>Player</th>
              <th>Faction</th>
              <th>Team</th>
              <th>Winner</th>
              <th>Kills</th>
              <th>Enemy Kills</th>
              <th>Units Produced</th>
              <th>Quit</th>
              <th>Resources</th>
              <th>Score</th>
            </tr>
            <?php
            $note_quit = False;
            $note_win = False;
            $note_humans = 0;
            $note_ais = 0;
            $note_ai_strength = True;
            $note_ai_ultra = 0;
            $note_ai_mega = 0;
            $note_ai_multiply = 0;

            $result = $db->query("SELECT * FROM glestgameplayerstats WHERE gameUUID='$gameUUID'");
            while($row = $result->fetch_assoc()){
            ?>
            <tr>
              <?php
              $controlType = $row['controlType'];
                if($controlType == 5 || $controlType == 7){
                  $note_humans++;
              ?>
                <td><a href="player.php?name=<?=$row['playerName']?>"><?=$row['playerName']?></a></td>
              <?php } else {
                  $note_ais++;
                $CPUtype = "";
                if($controlType == 1){
                  $CPUtype = "Easy";
                  $note_ai_strength = False;
                } else if($controlType == 2){
                  $CPUtype = "Normal";
                  $note_ai_strength = False;
                } else if($controlType == 3){
                  $note_ai_ultra++;
                  $note_ai_multiply += $row['resourceMultiplier'];
                  $CPUtype = "Ultra";
                } else if($controlType == 4){
                  $note_ai_mega++;
                  $note_ai_multiply += $row['resourceMultiplier'];
                  $CPUtype = "Mega";
                }
              ?>

                <td>CPU <?=$CPUtype?> <?=substr($row['resourceMultiplier'],0,3)?></td>
              <?php } ?>
              <td><?=$row['factionTypeName']?></td>
              <td><?=intval($row['teamIndex'])+1?></td>
              <td>
                <?php
                if($row['wonGame'] == 1){
                  echo '<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>';
                } else {
                  echo '<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>';
                  if($controlType == 5 || $controlType == 7){
                    $note_win = True;
                  }
                }
                ?>
                </td>
              <td><?=$row['killCount']?></td>
              <td><?=$row['enemyKillCount']?></td>
              <td><?=$row['unitsProducedCount']?></td>
              <td>
                <?php
                if($row['quitBeforeGameEnd'] == 1){
                  echo '<span class="glyphicon glyphicon glyphicon-off" aria-hidden="true"></span>';
                  $note_quit = True;
                } else {
                  echo ' ';
                }
                ?>
              </td>
              <td><?=$row['resourceHarvestedCount']?></td>
              <td><?=ceil($row['enemyKillCount'] * 100 + $row['unitsProducedCount'] * 50 + $row['resourceHarvestedCount'] / 10)?></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>



  <?php 
  	//close connection to db
	$db->close();
	include_once(__DIR__ . "/foot.php");
  ?>
</body>

</html>
