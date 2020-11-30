<?php
define('WP_USE_THEMES', false);
require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php'); ?>

<?php

// DB connection 
require('../results/connection.php');
mysqli_set_charset($conn, "utf8");

// error_reporting(E_ALL);
// ini_set('display_errors',1);

$name = '';
if (isset($_GET['name'])) {
	$name = $_GET['name'];
}

function get_classname($val)
{
	if ($val == 1) {
		$cls_name = 'first';
	} else if ($val == 2) {
		$cls_name = 'second';
	} else if ($val == 3) {
		$cls_name = 'third';
	} else if (
		$val > 3
		&&
		$val < 16
	) {
		$cls_name = 'points';
	} else if ($val > 16) {
		$cls_name = 'nopoints';
	} else {
		$cls_name = 'dnf';
	}
	return $cls_name;
}

function ordinal($number)
{
	$ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
	if ((($number % 100) >= 11) && (($number % 100) <= 13))
		return $number . 'th';
	else
		return $number . $ends[$number % 10];
}

function cars($data)
{
	$result = [];
	for ($i = 0; $i < count($data); $i++) {
		$val = array_values($data)[$i];
		if (!in_array($val[4], $result)) {
			$result[] = $val[4];
		}
	}
	$res = implode(",", $result);
	return $res;
}


// Get date range
$date_range_sql = "SELECT DATE_FORMAT(MIN(DATE),'%D %b %Y') AS mindate, DATE_FORMAT(MAX(DATE),'%D %b %Y') AS maxdate FROM races WHERE driver = '" . $name . "'";
$date_range = mysqli_query($conn, $date_range_sql);


// Get driver information
$driver_data_sql = "SET @driver_id:=(SELECT id FROM drivers WHERE driver='{$name}');
					SELECT dd.driver, rr.mindate, rr.maxdate, gg.series, rr.races, rr.wins, rr.podiums, dd.nationality, dd.dob, dd.profile
					FROM (SELECT @driver_id AS driver_id, DATE_FORMAT(MIN(DATE),'%Y') AS mindate, DATE_FORMAT(MAX(DATE),'%Y') AS maxdate, COUNT(races.driver) AS races, SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END) AS wins,
					SUM(CASE WHEN races.year='2001' AND races.class_pos IN ('1', '2', '3') AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result IN ('1', '2', '3') AND races.class != 'P' THEN 1 ELSE 0 END)
					AS podiums FROM races WHERE driver_id2 = @driver_id OR driver_id3 = @driver_id OR driver_id4 = @driver_id OR driver='{$name}') rr
					LEFT JOIN (SELECT d.id, d.`driver`, d.`nationality`, d.`dob`, d.`profile2020` AS `profile` FROM drivers d WHERE d.driver='{$name}') dd
					ON rr.driver_id=dd.id
					LEFT JOIN (SELECT series, @driver_id AS driver_id FROM races WHERE driver_id2 = @driver_id OR driver_id3 = @driver_id OR driver_id4 = @driver_id OR driver='{$name}' GROUP BY series) gg
					ON rr.driver_id=gg.driver_id";

$driver_data_query_result = mysqli_query($conn, $driver_data_sql);
$driver_data = [];
$series_raced_in = '';
if (mysqli_multi_query($conn, $driver_data_sql)) {
	do {
		// Store first result set
		if ($result = mysqli_store_result($conn)) {
			while ($row = mysqli_fetch_row($result)) {
				$driver_data[] = $row;
				$series_raced_in .= $row[3] . ", ";
			}
			mysqli_free_result($result);
		}
	} while (mysqli_next_result($conn));
}
$series_raced_in = rtrim($series_raced_in, ", ");


/**
 * Get round information for each year and series -> table header
 */
$yrsrs_rounds_sql = "SELECT e.yrsrs, e.year, e.series, e.round, e.date, e.circuit, c.code, c.abbreviation
					FROM (SELECT `year`, `series`, `round`, `date`, circuit, CONCAT(`year`, series) AS yrsrs FROM `event` ORDER BY `date`) e
					LEFT JOIN circuits c
					ON e.circuit = c.configuration
					ORDER BY e.date";
$yrsrs_rounds_query_result = mysqli_query($conn, $yrsrs_rounds_sql);
$yrsrs_rounds = [];
while ($row = mysqli_fetch_assoc($yrsrs_rounds_query_result)) {
	$yrsrs_rounds[$row['yrsrs']][] = $row;
}


/**
 * shared round race information
 */
$shared_info_sql = "SET @driver_id:=(SELECT id FROM drivers WHERE driver='{$name}');			
					SELECT main.series, main.`year`, main.yrsrs, main.round, main.result, main.car, head.classification, head.rank, head.points, main.qual, main.race_id
					FROM (SELECT driver, driver_id, series, `year`, CONCAT(`year`, series) AS yrsrs, `round`, result, car, qual, race_id, CONCAT(`year`, series, driver_id) AS yrsrsid
						FROM races
						WHERE driver_id2 = @driver_id OR driver_id3 = @driver_id OR driver_id4 = @driver_id) main
					LEFT JOIN (SELECT p.driver, p.`driver_id`, p.classification, p.rank, p.points, CONCAT(p.`year`, p.series, p.driver_id) AS yrsrsid
						FROM points p
						WHERE p.driver_id IN (SELECT DISTINCT r.driver_id AS driver_id
						FROM races r
						WHERE r.driver_id2 = @driver_id OR r.driver_id3 = @driver_id OR r.driver_id4 = @driver_id) AND p.classification IN ('Touring', 'Drivers')
						ORDER BY p.`year`, p.classification) head
					ON main.yrsrsid=head.yrsrsid
					ORDER BY main.yrsrs, main.round+0";
$shared_info = [];
$shared_yrsrs = [];
if (mysqli_multi_query($conn, $shared_info_sql)) {
	do {
		// Store first result set
		if ($result = mysqli_store_result($conn)) {
			while ($row = mysqli_fetch_row($result)) {
				$yrsrs = $row[2];
				$shared_yrsrs[] = $yrsrs;
				if (!in_array($yrsrs, $shared_yrsrs)) {
					$shared_yrsrs[] = $yrsrs;
				}
				$round = $row[3];
				$cls_name = get_classname($row[4]);
				$qual_cls_name = get_classname($row[9]);
				// 0 => result, 1=>cls_name, 2=>series, 3=>year, 4=>car, 5=>classification, 6=>rank, 7=>points, 8=>qual, 9=>qual_cls_name,10=>race_id
				$shared_info[$yrsrs][$round] = [$row[4], $cls_name, $row[0], $row[1], $row[5], $row[6], $row[7], $row[8], $row[9], $qual_cls_name, $row[10]];
			}
			mysqli_free_result($result);
		}
	} while (mysqli_next_result($conn));
}


// Get main data
$main_sql = "SELECT main.series, main.`year`, main.yrsrs, main.round, main.result, main.car, head.classification, head.rank, head.points, main.qual, main.race_id
			FROM (SELECT series, `year`, CONCAT(`year`, series) AS yrsrs, `round`, result, car, qual, race_id
				FROM races
				WHERE driver='" . $name . "') main
			LEFT JOIN (SELECT classification, driver, rank, points, CONCAT(`year`, series) AS yrsrs
				FROM points
				WHERE driver='" . $name . "' AND classification IN ('Touring', 'Drivers')
				ORDER BY `year`, classification) head
			ON main.yrsrs=head.yrsrs
			ORDER BY main.yrsrs, main.round+0";
$main_data_query_result = mysqli_query($conn, $main_sql);
$main_data = array();
$main_yrsrs = [];
$full_data = [];
while ($row = mysqli_fetch_assoc($main_data_query_result)) {
	$yrsrs = $row['yrsrs'];
	if (!in_array($yrsrs, $main_yrsrs)) {
		$main_yrsrs[] = $yrsrs;
	}
	$cls_name = get_classname($row['result']);
	$qual_cls_name = get_classname($row['qual']);
	// 0 => result, 1=>cls_name, 2=>series, 3=>year, 4=>car, 5=>classification, 6=>rank, 7=>points, 8=>qual, 9=>qual_cls_name,10=>race_id
	$main_data[$yrsrs][$row['round']] = [$row['result'], $cls_name, $row['series'], $row['year'], $row['car'], $row['classification'], $row['rank'], $row['points'], $row['qual'], $qual_cls_name, $row['race_id']];
}

$full_data = $main_data;
// diff main and share data
$diff_main_share = array_diff($shared_yrsrs, $main_yrsrs);
if ($len = count($diff_main_share)) {
	$array_val = array_values($diff_main_share);
	for ($i = 0; $i < $len; $i++) {
		$temp_key = $array_val[$i];
		$full_data[$temp_key] = $shared_info[$temp_key];
	}
}
ksort($full_data);
// echo "shared_yrsrs>>>>>>>>>>>>>>>>>>>>>>>>>><br>";
// echo "<pre>";
// var_dump($shared_yrsrs);
// echo "</pre>";
// exit;


?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title><?php bloginfo('name'); ?> &raquo; Database &raquo; <?php echo ucwords(strtolower($name)); ?> Race Wins</title>
</head>

<style>
	@media (max-width: 767px) {
		.td-pb-row {
			display: flex;
			flex-direction: column;
		}
	}

	.custom-sidebar {
		box-shadow: 10px 8px 10px #f2f2f2;
		padding: 1px;
	}

	.custom-li {
		display: flex;
		justify-content: space-between;
		padding: 6px;
	}

	.description {
		width: 75%;
	}

	.description td {
		padding-right: 4px;
		padding-top: 0px;
		padding-bottom: 0px;
		margin-bottom: 5px;
		margin-top: 10px;
		border: solid 1px lightgrey;
	}

	.series,
	.year {
		margin-bottom: 0px;
		margin-top: 0px;
	}

	.pointstable {
		margin-top: 5px;
	}

	.pointstable tr td {
		border: solid 1px #777;
	}

	.round {
		cursor: pointer;
		background: #f7f7f7;
	}

	.round:hover {
		background: #d9d9d9;
	}

	.yrsrs {
		float: left;
		text-align: center;
		background: #e5e5e5;
		margin-top: 5px;
		margin-bottom: 10px;
		width: 100%;
	}

	.yrsrs:nth-child(odd) {
		background-color: #F7F7F7;
	}

	.more-info {
		position: relative;
	}

	.more-info .title {
		position: absolute;
		top: 20px;
		background: silver;
		padding: 4px;
		right: 0;
		white-space: nowrap;
	}

	.bs-example {
		font-family: sans-serif;
		position: relative;
		margin-top: 5px;
		margin-bottom: 40px;
		margin-left: 12px;
	}

	.typeahead,
	.tt-query,
	.tt-hint {
		border: 2px solid #CCCCCC;
		border-radius: 8px;
		font-size: 24px;
		height: 30px;
		line-height: 30px;
		outline: medium none;
		padding: 8px 12px;
		width: 396px;
	}

	.typeahead {
		background-color: #FFFFFF;
	}

	.typeahead:focus {
		border: 2px solid #0097CF;
	}

	.tt-query {
		box-shadow: 0 1px 1px rgba(0, 0, 0, 0.075) inset;
	}

	.tt-hint {
		color: #999999;
	}

	.tt-dropdown-menu {
		background-color: #FFFFFF;
		border: 1px solid rgba(0, 0, 0, 0.2);
		border-radius: 8px;
		box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
		margin-top: 12px;
		padding: 8px 0;
		width: 275px;
	}

	.tt-suggestion {
		font-size: 24px;
		line-height: 24px;
		padding: 3px 20px;
	}

	.tt-suggestion:hover {
		color: #f0f0f0;
		background-color: #0097cf;
	}

	.tt-suggestion.tt-is-under-cursor {
		background-color: #0097CF;
		color: #FFFFFF;
	}

	.tt-suggestion p {
		margin: 0;
	}
</style>

<script src="/results/tablesorter/js/jquery-latest.min.js"></script>
<script src="/results/database/typeahead.min.js"></script>

<script>
	$(document).ready(function() {
		$('input.typeahead').typeahead({
			name: 'typeahead',
			remote: 'search.php?key=%QUERY',
			limit: 10
		});
	});
</script>

<?php get_header(); ?>

<body>
	<div class="td-container-wrap" style="padding: 20px;">
		<div class="td-pb-row">
			<div class="td-pb-span8">
				<div class="td-post-header td-pb-padding-side">
					<a href='/'><?php bloginfo('name'); ?></a>
					&raquo;Statistics&raquo;
					<?php echo ucwords(strtolower($name)); ?> race results

					<div class="td_block_wrap tdb_title tdi_78_07e tdb-single-title td-pb-border-top td_block_template_1" style="margin-bottom: 0px;">
						<div class="tdb-block-inner td-fix-index">
							<h1 class='tdb-title-text' style="font-family: Oxygen; font-size: 32px; font-weight: 800;"><?php echo ucwords(strtolower($name)); ?> race results</h1>
						</div>
					</div>
					<em>
						<?php if (mysqli_num_rows($date_range) > 0) {
							while ($row = mysqli_fetch_assoc($date_range)) {
								echo "Note: Data valid for period between" . $row["mindate"] . " and " . $row["maxdate"];
							}
						} ?>
					</em>
				</div>

				<div class="td-ss-main-content" style="margin-top: 30px;">

					<?php
					foreach ($full_data as $key => $data) {
						$first = array_values($full_data[$key])[0];
						$standings_link = 'https://' . $_SERVER['SERVER_NAME'] . "/database/standings.php?series={$first[2]}&year={$first[3]}";
						$yrsrs_link = 'https://' . $_SERVER['SERVER_NAME'] . "/database/index.php?series={$first[2]}&year={$first[3]}"; ?>
						<table class="description">
							<tr>
								<td rowspan="2" class="year">
									<b>
										<h4><a href="<?php echo $yrsrs_link; ?>" target="_blank"><?php echo $first[3]; ?></a></h4>
									</b>
								</td>
								<td rowspan="2" class="series">
									<h4><a href="<?php echo $yrsrs_link; ?>" target="_blank"><?php echo $first[2]; ?></a></h4>
								</td>
								<?php if ($first[5] == 'Drivers') { ?>
									<td><a href="<?php echo $standings_link; ?>" target="_blank">Driver championship:</a></td>
									<td style="background: #00ffbf;">
										<?php if (!empty($first[6])) { ?>
											<span><?php echo ordinal($first[6]); ?>, <?php echo $first[7]; ?> points</span>
										<?php } ?>
									</td>
								<?php } else { ?>
									<td><a href="<?php echo $standings_link; ?>" target="_blank">Touring championship:</a></td>
									<td>
										<?php if (!empty($first[6])) { ?>
											<span style="background: grey;"><?php echo ordinal($first[6]); ?></span>
										<?php } ?>
									</td>
								<?php } ?>
							</tr>
							<tr>
								<td>Cars raced:</td>
								<td><?php echo cars($data); ?></td>
							</tr>
						</table>

						<div class="yrsrs">
							<?php foreach ($yrsrs_rounds[$key] as $header) {
								$rd_link = 'https://' . $_SERVER['SERVER_NAME'] . "/database/races.php?id=" . ($data[$header['round']][10] ?? '');
							?>
								<div style="float:left; padding: 1.5px;">
									<!-- abbreviation  -->
									<div style="background: #E5E5E5;">
										<span class="more-info" title="<?= $header['circuit']; ?>"><?php echo $header['abbreviation']; ?></span>
									</div>

									<div style="padding: 1px; min-width:28px;">
										<!-- round -->
										<?php if (!empty($data[$header['round']][10])) { ?>
											<div class="round" onclick="window.open('<?php echo $rd_link; ?>')" ;?>
											<?php } else { ?>
												<div class="round">
												<?php } ?>
												<span>
													<?php echo $header['round']; ?>
												</span>
												</div>

												<!-- result -->
												<?php if (array_key_exists($header['round'], ($shared_info[$key] ?? []))) {
													if (array_key_exists($header['round'], ($main_data[$key] ?? []))) { // drove shared and his car 
												?>
														<div style="padding: 4px;" class="<?php echo $main_data[$key][$header['round']][1]; ?>">
															<?php echo $main_data[$key][$header['round']][0] . '/' . $shared_info[$key][$header['round']][0]; ?>
														</div>
													<?php } else { // drove shared car 
													?>
														<div style="padding: 4px;" class="<?php echo  $shared_info[$key][$header['round']][1]; ?>">
															<?php echo $shared_info[$key][$header['round']][0]; ?>
														</div>
													<?php }
												} else if (array_key_exists($header['round'], ($main_data[$key] ?? []))) { // drove his car 
													?>
													<div style="padding: 4px;" class="<?php echo $main_data[$key][$header['round']][1]; ?>">
														<?php echo $main_data[$key][$header['round']][0]; ?>
													</div>
												<?php } else { ?>
													<div style="padding: 4px;">
														<?php echo "-"; ?>
													</div>
												<?php }
												?>

												<!-- qual -->
												<?php if (array_key_exists($header['round'], ($shared_info[$key] ?? []))) {
													if (array_key_exists($header['round'], ($main_data[$key] ?? []))) { // drove shared and his car 
												?>
														<div style="padding: 4px;">
															<?php echo $main_data[$key][$header['round']][8] . '/' . $shared_info[$key][$header['round']][8]; ?>
														</div>
													<?php } else { ?>
														<div style="padding: 4px;">
															<?php echo $shared_info[$key][$header['round']][8]; ?>
														</div>
													<?php }
												} else if (array_key_exists($header['round'], ($main_data[$key] ?? []))) { // drove his car 
													?>
													<div style="padding: 4px;">
														<?php echo $main_data[$key][$header['round']][8]; ?>
													</div>
												<?php
												} else {
												?>
													<div style="padding: 4px;">
														<?php echo "-"; ?>
													</div>
												<?php
												}
												?>

											</div>
									</div>
								<?php } ?>
								</div>

								<br>

							<?php }
							?>

						</div>
				</div>

				<div class="td-pb-span4 td-main-sidebar td-pb-border-top" style="padding-right: 40px; margin-top: 21px; padding-bottom: 16px;" role="complementary">
					<div class="td-ss-main-sidebar">

						<div class="clearfix"></div>
						<aside class="widget_meta custom-sidebar">
							<div class="block-title">
								<span>Search for a driver</span>
							</div>

							<div class="panel panel-default">
								<div class="bs-example">
									<input type="text" name="typeahead" class="typeahead tt-query" autocomplete="off" spellcheck="false" placeholder="Type your Query">
								</div>
							</div>
						</aside>
						<div class="clearfix"></div>

						<?php dynamic_sidebar('HomeS1'); ?>
					</div>

					<div class="td-ss-main-sidebar">
						<aside class="widget widget_meta custom-sidebar" style="margin-top: 50px;">
							<div class="block-title">
								<span><?php echo ucwords(strtolower($name)); ?></span>
							</div>

							<div class="table-row" style="margin-bottom: 10px;">
								<?php if (!empty($driver_data[0][9])) { ?>
									<div style="padding: 6px;"><img src='<?php $_SERVER['DOCUMENT_ROOT']; ?>/<?php echo $driver_data[0][9]; ?>' /></div>
								<?php } ?>
								<div class="custom-li">
									<div><b>Nationality:</b></div>
									<div><?php echo $driver_data[0][7]; ?></div>
								</div>
								<div class="custom-li">
									<div><b>Date of birth:</b></div>
									<div><?php echo $driver_data[0][8]; ?></div>
								</div>
								<div class="custom-li">
									<div><b>Races (all series):</b></div>
									<div><?php echo $driver_data[0][4]; ?></div>
								</div>
								<div class="custom-li">
									<div><b>Victories (all series):</b></div>
									<div><?php echo $driver_data[0][5]; ?></div>
								</div>
								<div class="custom-li">
									<div><b>Podiums (all series):</b></div>
									<div><?php echo $driver_data[0][6]; ?></div>
								</div>
								<br>
								<div class="custom-li">
									<div><b>Series raced in:</b></div>
									<div><?php echo $series_raced_in; ?></div>
								</div>
								<div class="custom-li">
									<div><b>Years active:</b></div>
									<div><?php echo $driver_data[0][1]; ?> - <?php echo $driver_data[0][2]; ?></div>
								</div>
							</div>
						</aside>

						<?php dynamic_sidebar('HomeS1'); ?>
					</div>
				</div>

			</div>

		</div>

		<script>
			$(".more-info").click(function() {
				var $title = $(this).find(".title");
				if (!$title.length) {
					$(this).append('<span class="title">' + $(this).attr("title") + '</span>');
				} else {
					$title.remove();
				}
			});
		</script>

		<?php get_footer(); ?>