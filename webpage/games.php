<?php
require_once(__DIR__ . "/config.php");
include_once(__DIR__ . "/head.php");
?>


<body>
  <div class="container">
    <div class="row">
      <div class="col-md-12">
<?php include("navbar.php") ?>
      </div>
    </div>

    <?php
    $result = $db->query('SELECT * FROM glestserver WHERE status=0 AND connectedClients > 0 ORDER BY lasttime DESC');
    if($result->num_rows > 0){
    ?>
    <div class="row">
      <div class="col-md-12">
        <h2>Available Games</h2>
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
            <?php

            while($row = $result->fetch_assoc()){
            ?>
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
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
<?php
}
unset($result);
$result = $db->query('SELECT * FROM glestserver WHERE status=0 AND connectedClients=0 ORDER BY lasttime DESC');
if($result->num_rows > 0){
?>

    <div class="row">

      <div class="col-md-12">
        <h2>Free Servers</h2>
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
            <?php
            while($row = $result->fetch_assoc()){
            ?>
            <tr>
              <td><?=$row['serverTitle']?></td>
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
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
<?php
}
unset($result);
$result = $db->query('SELECT * FROM glestserver WHERE status=2 ORDER BY lasttime DESC');
if($result->num_rows > 0){
?>
    <div class="row">

      <div class="col-md-12">
        <h2>Ongoing Games</h2>
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
            <?php
            while($row = $result->fetch_assoc()){
            ?>
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
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>

<?php } ?>

    <div class="row">

      <div class="col-md-12">
        <h2>Last 50 Games</h2>
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
            <?php
            $result = $db->query('SELECT * FROM glestserver WHERE status=3 ORDER BY lasttime DESC LIMIT 50');
            while($row = $result->fetch_assoc()){
            ?>
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
