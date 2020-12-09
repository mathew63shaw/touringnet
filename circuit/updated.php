<?php require('../../../wp-blog-header.php'); ?>

<?php
require_once '../toplists/connection.php';

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
	die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn,"utf8");

$id = $_GET['track'];
$id = mysqli_real_escape_string($conn, $id);
$id2 = $id;

$sql = "SELECT circuits.circuit, circuits.variant as trvar, races.track as trck, races.series as cship, races.year as yr, races.round as rd, races.result as res, races.driver as pilot, races.entrant as team, races.car as vehicle, races.time as duration, races.date as dte From circuits LEFT JOIN races on circuits.configuration = races.track Where circuit = '" . $id . "' and case when races.series=\"BTCC\" and races.year=\"2001\" and races.class_pos=\"1\" and races.class=\"\" then Class_Pos=\"1\" else Result=\"1\" and Class!=\"P\" end order by 12, 4 asc";

$sql2 = "select circuits.circuit, date_format(min(date),'%D %b %Y') as mindate, date_format(max(date),'%D %b %Y') as maxdate from circuits LEFT JOIN races on circuits.configuration = races.track WHERE circuit = '" . $id . "' and Result = '1'";

$sql3 = "select circuit, layout, configuration, from_year, to_year, graphic_path, excerpt from circuits WHERE circuit = '" . $id . "' order by 2, 4 asc";

// This is along the right lines but doesn't work due to the structure of circuits and qualifying - it ends up tripling certain rows based on duplicate circuit configurations. "SELECT circuits.circuit, qualifying.track as trck, qualifying.series as cship, qualifying.year as yr, qualifying.round as rd, qualifying.result as res, qualifying.driver as pilot, qualifying.entrant as team, qualifying.car as vehicle, qualifying.time as duration From circuits LEFT JOIN qualifying on circuits.configuration = qualifying.track Where `track` = '" .$id. "' and `TopQ` = 'Y' and `Result` = '1' order by 4, 3, 5 asc limit 100"

$sql4 = $sql3;

$sql5 = $sql3;

$result = mysqli_query($conn, $sql);
$result2 = mysqli_query($conn, $sql2);
$result3 = mysqli_query($conn, $sql3);
$result4 = mysqli_query($conn, $sql4);
$result5 = mysqli_query($conn, $sql5);

?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head profile="http://gmpg.org/xfn/11">

<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title><?php bloginfo('name'); ?> &raquo; Database &raquo; <?php echo $id ?> Race Winners</title>

<link rel="stylesheet/less" type="text/css" href="flex.less" />
<script src="//cdnjs.cloudflare.com/ajax/libs/less.js/3.0.0/less.min.js" ></script>

<style type="text/css">

.h4h {
	margin-left: 10px;
}

.tb-row:nth-of-type(even) {
	background-color: #f7f7f7;
}
.tb-row:hover {
	background-color: #d1d5ff;
}

/* Style the tab */
.tab {
    overflow: hidden;
    border: 1px solid #ccc;
    background-color: #f1f1f1;
}

/* Style the buttons that are used to open the tab content */
.tab button {
    background-color: inherit;
    float: left;
    border: none;
    outline: none;
    cursor: pointer;
    padding: 14px 16px;
    transition: 0.3s;
}

/* Change background color of buttons on hover */
.tab button:hover {
    background-color: #ddd;
}

/* Create an active/current tablink class */
.tab button.active {
    background-color: #ccc;
}

/* Style the tab content */
.tabcontent {
    display: none;
    padding: 6px 12px;
    border-top: none;
}

</style>

<script src="/results/tablesorter/js/jquery-latest.min.js"></script>

<script src="/results/tablesorter/js/jquery.tablesorter.min.js"></script>
<script src="/results/tablesorter/js/jquery.tablesorter.widgets.min.js"></script>
	<script>
	$(function(){
		$('table').tablesorter({
			widgets        : ['columns'],
			usNumberFormat : false,
			sortReset      : true,
			sortRestart    : true
		});
	});
	</script>

</head>

<?php include (TEMPLATEPATH . '/header2.php'); ?>

<body>
<div class="td-container-wrap" style="padding: 20px;">

	<div class="td-pb-row">

		<div class="td-pb-span12">

			<div class="td-ss-main-content">

				<div class="clearfix"></div>

				<a href='../../'><?php bloginfo('name'); ?></a> &raquo; Results &raquo; Statistics &raquo; <?php echo $id2 ?> race winners
				
				<h4 class="h4h"><?php echo $id2 ?> race winners</h4>
				
					<?php 
					  $previousrow = array ('layout' => ''); 

					  if (mysqli_num_rows($result3) > 0) {
						while($row = mysqli_fetch_assoc($result3)) {
						  if($row['layout'] != $previousrow['layout']) { // new group
							if ($previousrow['layout'] !== '') { // not on first row
							  print_footers ($previousrow);
							}
							print_headers ($row);
						  } 
						  echo "<button class='tablinks' id='tcnopen' onclick=\"openCircuit(event, '" . $row['from_year'] . "-" . $row['configuration'] . "')\" > " . $row['from_year'] . "&raquo;" . $row['to_year'] . "</button>"; // will depend on $this_row
						  $previousrow = $row;
						}
						print_footers ($previousrow);
					  } 

					
					function print_headers ($this_row) {
					  echo "<div class='tab'>\n"; // will depend on $this_row
					}

					function print_footers ($this_row) {
					  echo "</div><!-- Close tab div -->"; // will depend on $this_row
					}
					?>
				
				<section class="circuit-info" style="display: flex; height: 280px;">
					
					<div style="display: inline-block;">

						<?php if (mysqli_num_rows($result4) > 0) {while($row = mysqli_fetch_assoc($result4)) { ?>
						
						<div class="tabcontent" id="<?php echo $row["from_year"]; ?>-<?php echo $row["configuration"]; ?>">
						  <p><img src="<?php echo $row["graphic_path"]; ?>" style="max-width: 360px;" /></p>
						</div>
						
						<?php } } else { echo "Unknown"; } ?>
					
					</div>
					
					<div style="display: inline-block; padding: 10px;">
					
						<?php if (mysqli_num_rows($result5) > 0) {while($row = mysqli_fetch_assoc($result5)) { ?>
						
						<?php echo $row["excerpt"]; ?>
						
						<?php } } else { echo "Unknown"; } ?>
						
					</div>
					
				</section>
				
				&nbsp;&nbsp;<em>Note: Data valid for period between <?php if (mysqli_num_rows($result2) > 0) {while($row = mysqli_fetch_assoc($result2)) { echo $row["mindate"] . " and " . $row["maxdate"]; } } else {	echo "0 results"; }	?></em>
				
				<input type="text" id="myInput" onkeyup="myFunction()" placeholder="Filter on table contents..." title="Type in a name">
				
				<div class="stats-div">

					<div class="container-fluid" style="margin-top: 10px">
						<div class="tb-row header">
							<div class="wrapper text-2">
							  <div class="wrapper text-2">
								<div class="text-series">Series</div>
								<div class="text-year">Year</div>
							  </div>
							</div>
							<div class="wrapper text-2">
							  <div class="wrapper text-2">
								<div class="text-layout">Layout</div>
								<div class="text-driver">Driver</div>
							  </div>
							</div>
							<div class="wrapper text-2">
							  <div class="wrapper text-2">
								<div class="text-entrant">Entrant</div>
								<div class="text-car">Car</div>
							  </div>
							</div>
							<div class="wrapper text-4">
							  <div class="wrapper text-4">
								<div class="text-time">Time</div>
							  </div>
							</div>
						</div>

							<?php if (mysqli_num_rows($result) > 0) {
							// output data of each row
							while($row = mysqli_fetch_assoc($result)) {
								echo "<div class='tb-row'><div class='wrapper text-2'><div class='wrapper text-2'><div class='text-series' title='" . $row["cship"] . "'>" . (( $row["cship"] == 'TCR Asia') ? 'TCR AS' : (( $row["cship"] == 'STW Cup') ? 'STW' : (( $row["cship"] == 'ETC Cup') ? 'ETC' : $row["cship"] ) ) ) . "</div><div class='text-year'>" . $row["yr"]. "</div></div></div><div class='wrapper text-2'><div class='wrapper text-2'><div class='text-layout'>" . $row["trvar"] . "</div><div class='text-driver' title='" . $row["pilot"] . "'><a href='driver-wins.php?series=" . $row["cship"] . "&driver=" . $row["pilot"] . "'>" . mb_strimwidth($row["pilot"],0,20,".."). "</a></div></div></div><div class='wrapper text-2'><div class='wrapper text-2'><div class='text-entrant' title='" . $row["team"] . "'>" . mb_strimwidth($row["team"],0,35,"..") . "</div><div class='text-car' title='" . $row["vehicle"] . "'>" . mb_strimwidth($row["vehicle"],0,27,"…") . "</div></div></div><div class='wrapper text-4'><div class='wrapper text-4'><div class='text-time'>" . $row["duration"]. "</div></div></div></div>";
							}
							} else { echo "0 results"; }

							mysqli_close($conn);

							?>
					</div>
				
				</div>
				
				<br />
			
			</div>

		</div>
	
	</div>
	
</div><!-- End of td-container div -->

<script>
function openCircuit(evt, cityName) {
    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the button that opened the tab
    document.getElementById(cityName).style.display = "block";
    evt.currentTarget.className += " active";
}
// Get the element with id="defaultOpen" and click on it
	document.getElementById("tcnopen").click();
	
	function myFunction() {
      // Declare variables 
      var input, filter, table, tr, td, i, occurrence;

      input = document.getElementById("myInput");
      filter = input.value.toUpperCase();
      table = document.getElementById("nat-table");
      tr = table.getElementsByTagName("tr");

      // Loop through all table rows, and hide those who don't match the search query
     for (i = 1; i < tr.length; i++) {
         occurrence = false; // Only reset to false once per row.
         td = tr[i].getElementsByTagName("td");
         for(var j=1; j< td.length; j++){                
             currentTd = td[j];
             if (currentTd ) {
                 if (currentTd.innerHTML.toUpperCase().indexOf(filter) > -1) {
                     tr[i].style.display = "";
                     occurrence = true;
                 } 
             }
         }
         if(!occurrence){
             tr[i].style.display = "none";
         } else {
			 tr[i].style.display = "";
		 }
     }
   }
   
</script>

<?php get_footer(); ?>