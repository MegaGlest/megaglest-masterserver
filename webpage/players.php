<?php
define( 'INCLUSION_PERMITTED', true );
include_once(__DIR__ . "/functions.php");
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
        <h2>Top 100 Players</h2>
        <?php
        if(isset($_GET['time'])){
          $time = intval($_GET['time']);
          if($time == 1){
            $timelimit = " and s.lasttime >= DATE_SUB(NOW(), INTERVAL 1 MONTH)  ";
          } else if($time == 2) {
            $timelimit = " and s.lasttime >= DATE_SUB(NOW(), INTERVAL 1 WEEK)  ";
          } else {
            $timelimit = " and s.lasttime >= DATE_SUB(NOW(), INTERVAL 1 DAY)  ";
          }
        } else {
          $timelimit=" ";
          $time = 0;
        }
        ?>
        <ul class="nav nav-tabs">
          <li role="presentation"<?php if($time == 0){echo ' class="active"';}?>><a href="players.php">All Time</a></li>
          <li role="presentation"<?php if($time == 1){echo ' class="active"';}?>><a href="?time=1">Month</a></li>
          <li role="presentation"<?php if($time == 2){echo ' class="active"';}?>><a href="?time=2">Week</a></li>
          <li role="presentation"<?php if($time == 3){echo ' class="active"';}?>><a href="?time=3">Day</a></li>
        </ul>

        <table class="table table-bordered table-striped">
          <tbody>
            <tr>
              <th>#</th>
              <th>Player Name</th>
              <th>Games Played</th>
              <th>Time Played</th>
            </tr>
            <?php
            $db=createDbObject();
            $position = 0;
    	      $result = $db->query('select playername, count(*) as c, sum(ggs.framesToCalculatePlaytime)/30 as playtime from glestgameplayerstats s , glestgamestats ggs where s.gameUUID=ggs.gameUUID and controltype>4 '.$timelimit.' and playername != "newbie" group by playername having c >1 order by c desc,playername  LIMIT 100');
      	    while($row = $result->fetch_assoc()){
            $position++;
            $time = secondsToTime($row['playtime']);
            ?>
            <tr>
              <td><?=$position?></td>
              <td><a href="player.php?name=<?=$row['playername']?>"><?=$row['playername']?></a></td>
              <td><?=$row['c']?></td>
              <td><?php
              if($time['d']>0){
                echo "<b>".$time['d']."</b> <small>days</small> ";
              }
              echo "<b>".$time['h']."</b> <small>hours</small> <b>".$time['m']."</b> <small>min.</small> ";
              ?></td>
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
