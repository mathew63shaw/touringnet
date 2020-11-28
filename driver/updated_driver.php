<?php
define('WP_USE_THEMES', false);
require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php'); ?>

<?php

// DB connection 
require('../results/connection.php');
mysqli_set_charset($conn, "utf8");

$name = '';
if (isset($_GET['name'])) {
    $name = $_GET['name'];
}

// Get date range
$date_range_sql = "SELECT DATE_FORMAT(MIN(DATE),'%D %b %Y') AS mindate, DATE_FORMAT(MAX(DATE),'%D %b %Y') AS maxdate FROM races WHERE driver = '" . $name . "'";
$date_range = mysqli_query($conn, $date_range_sql);


// Get driver information
$driver_data_sql = "SELECT rr.driver, rr.mindate, rr.maxdate, gg.series, rr.races, rr.wins, rr.podiums, dd.nationality, dd.dob, dd.profile
					FROM (SELECT driver, DATE_FORMAT(MIN(DATE),'%Y') AS mindate, DATE_FORMAT(MAX(DATE),'%Y') AS maxdate, COUNT(races.driver) AS races, SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END) AS wins,
					SUM(CASE WHEN races.year='2001' AND races.class_pos IN ('1', '2', '3') AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result IN ('1', '2', '3') AND races.class != 'P' THEN 1 ELSE 0 END)
					AS podiums FROM races WHERE driver='" . $name . "') rr
					LEFT JOIN (SELECT d.`driver`, d.`nationality`, d.`dob`, d.`profile2020` AS `profile` FROM drivers d WHERE d.driver='" . $name . "') dd
					ON rr.driver=dd.driver
					LEFT JOIN (SELECT DISTINCT series, driver FROM races WHERE driver='" . $name . "') gg
					ON rr.driver=gg.driver";

$driver_data_query_result = mysqli_query($conn, $driver_data_sql);
$driver_data = [];
$series_raced_in = '';
while ($row = mysqli_fetch_assoc($driver_data_query_result)) {
    $driver_data[] = $row;
    $series_raced_in .= $row['series'] . ", ";
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
// $yrsrs_rounds_temp = [];
// $yrsrs_grouped = []; // distincted year-series pair
while ($row = mysqli_fetch_assoc($yrsrs_rounds_query_result)) {
    // if (!in_array($row['yrsrs'], $yrsrs)) {
    // 	$yrsrs_grouped[] = $row['yrsrs'];
    // }
    $yrsrs_rounds[$row['yrsrs']][] = $row;
}
// $i = 0;
// while ($i < count($yrsrs_grouped)) {
// 	$yrsrs = $yrsrs_grouped[$i]; // 1957BTCC
// 	$temp = $yrsrs_rounds_temp[$yrsrs]; // Choose an array for year-series
// 	$j = 0;
// 	$duplicated_cnt = 0;
// 	while ($j < count($temp)) { // Processing duplicated circuit field
// 		$duplicated_cnt = 0;
// 		$original_j = $j;
// 		$base = $temp[$j]['circuit'];
// 		while ($base == $temp[$j]['circuit']) {
// 			$duplicated_cnt++;
// 			$j++;
// 		}
// 		for ($k = 0; $k < $duplicated_cnt; $k++) {
// 			$new_array = array(
// 				'yrsrs' => $yrsrs,
// 				'circuit' => $temp[$original_j]['circuit'],
// 				'round' => $temp[$original_j]['round'],
// 				'abbreviation' => $temp[$original_j]['abbreviation'],
// 				'duplicated_cnt' => $duplicated_cnt,
// 				'flag_path' => "../results/flag/" . $temp[$original_j]['code'] . ".gif",
// 				'rd_link' => 'https://' . $_SERVER['SERVER_NAME'] . "/results/" . $temp[$original_j]['year'] . "/rd" . $temp[$original_j]['round'] . ".php"
// 			);
// 			$original_j++;
// 			$yrsrs_rounds[$yrsrs][] = $new_array;
// 		}
// 	}
// 	$i++;
// }
// echo "<pre>";
// var_dump($yrsrs_rounds['1989DTM']);
// echo "</pre>";
// exit;


/**
 * shared round race information
 */
$shared_info_sql = "SET @driver_id:=(SELECT id FROM drivers WHERE driver='{$name}');
					SELECT CONCAT(`year`, series) AS yrsrs, driver, `round`, result
					FROM races
					WHERE driver_id2 = @driver_id OR driver_id3 = @driver_id OR driver_id4 = @driver_id;";
$shared_info = [];
if (mysqli_multi_query($conn, $shared_info_sql)) {
    do {
        // Store first result set
        if ($result = mysqli_store_result($conn)) {
            while ($row = mysqli_fetch_row($result)) {
                $shared_info[$row[0]][$row[2]] = $row[3];
            }
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($conn));
}

// Get main data
$main_sql = "SELECT main.series, main.`year`, main.yrsrs, main.round, main.result, main.car, head.classification, head.rank, head.points
			FROM (SELECT series, `year`, CONCAT(`year`, series) AS yrsrs, `round`, result, car
				FROM races
				WHERE driver='" . $name . "') main
			LEFT JOIN (SELECT classification, driver, rank, points, CONCAT(`year`, series) AS yrsrs
				FROM points
				WHERE driver='" . $name . "' AND classification IN ('Touring', 'Drivers')
				ORDER BY `year`, classification) head
			ON main.yrsrs=head.yrsrs
			ORDER BY main.yrsrs, main.round";
$main_data_query_result = mysqli_query($conn, $main_sql);


$main_data = array();
while ($row = mysqli_fetch_assoc($main_data_query_result)) {
    $yrsrs = $row['yrsrs'];

    // class name according to race result
    if ($row['result'] == 1) {
        $cls_name = 'first';
    } else if ($row['result'] == 2) {
        $cls_name = 'second';
    } else if ($row['result'] == 3) {
        $cls_name = 'third';
    } else if (
        $row['result'] > 3
        &&
        $row['result'] < 16
    ) {
        $cls_name = 'points';
    } else if ($row['result'] > 16) {
        $cls_name = 'nopoints';
    } else {
        $cls_name = 'dnf';
    }

    // Get drivers race result
    // drivers_race_result_array(yrsrs => [round => [res, cls_name], ...])
    $main_data[$yrsrs][$row['round']] = [$row['result'], $cls_name, $row['series'], $row['year'], $row['car'], $row['classification'], $row['series'], $row['rank'], $row['points']];
}
// $first = array_values($main_data['1989DTM'])[0];
// echo "<pre>";
// var_dump($first);
// echo "</pre>";
// exit;

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

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head profile="http://gmpg.org/xfn/11">
    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
    <title><?php bloginfo('name'); ?> &raquo; Database &raquo; <?php echo ucwords(strtolower($name)); ?> Race Wins</title>
</head>

<style>
    .custom-sidebar {
        box-shadow: 10px 8px 10px #f2f2f2;
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
</style>

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
                    // $main_data[$yrsrs][$row['round']] = [$row['result'], $cls_name, $row['series'], $row['year'], $row['car'], $row['classification'], $row['series'], $row['rank'], $row['points']];
                    foreach ($main_data as $key => $data) {
                        $first = array_values($main_data[$key])[0]; ?>
                        <div class="table-responsive">
                            <table class="description">
                                <tr>
                                    <td rowspan="2" class="year">
                                        <b>
                                            <h4><?php echo $first[3]; ?></h4>
                                        </b>
                                    </td>
                                    <td rowspan="2" class="series">
                                        <h4><?php echo $first[6]; ?></h4>
                                    </td>
                                    <?php if ($first[5] == 'Drivers') { ?>
                                        <td>Driver championship:</td>
                                        <td><span style="background: #00ffbf;"><?php echo ordinal($first[7]); ?>, <?php echo $first[8]; ?> points</span></td>
                                    <?php } else { ?>
                                        <td>Touring championship:</td>
                                        <td><span style="background: grey;"><?php echo ordinal($first[7]); ?></span></td>
                                    <?php } ?>
                                </tr>
                                <tr>
                                    <td>Cars raced:</td>
                                    <td><?php echo cars($data); ?></td>
                                </tr>
                            </table>

                            <table class="pointstable">
                                <tr class="resultsubheading">
                                    <?php
                                    foreach ($yrsrs_rounds[$key] as $header) { ?>
                                        <td align='center'><img src="../results/flag/<?php echo $header['code']; ?>.gif" title='<?php echo $header['circuit']; ?>'>
                                            <?php echo $header['abbreviation']; ?>
                                        </td>
                                    <?php
                                    } ?>
                                </tr>

                                <tr class="alternate">
                                    <?php
                                    foreach ($yrsrs_rounds[$key] as $header) {
                                        $rd_link = 'https://' . $_SERVER['SERVER_NAME'] . "/results/" . $header['year'] . "/rd" . $header['round'] . ".php"; ?>
                                        <td align='center' style="cursor: pointer;" onclick="window.open('<?php echo $rd_link; ?>')">
                                            <?php echo $header['round']; ?>
                                        </td>
                                    <?php
                                    } ?>
                                </tr>

                                <tr class='alternate2'>
                                    <?php
                                    // $main_data[$yrsrs][$row['round']] = [$row['result'], $cls_name, $row['series'], $row['year'], $row['car'], $row['classification'], $row['series'], $row['rank'], $row['points']];
                                    foreach ($yrsrs_rounds[$key] as $header) {
                                        // echo $key."<br>";
                                        // echo $header['round']."<br>";
                                        // echo "<pre>";
                                        // var_dump($shared_info[$key]);
                                        // echo "</pre>";
                                        // exit;
                                        if (array_key_exists($header['round'], $shared_info[$key])) {
                                            if (array_key_exists($header['round'], $data)) { // drove shared and his car 
                                    ?>
                                                <td align='center' style="background: orangered;">
                                                    <?php echo $data[$header['round']][0] . '/' . $shared_info[$key][3]; ?>
                                                </td>
                                            <?php } else { // drove shared car 
                                            ?>
                                                <td align='center' class='<?php echo $data[$header['round']][1]; ?>'>
                                                    <?php echo $data[$header['round']][0]; ?>
                                                </td>
                                            <?php }
                                        } else if (array_key_exists($header['round'], $data)) { // drove his car 
                                            ?>
                                            <td align='center' class='<?php echo $data[$header['round']][1]; ?>'>
                                                <?php echo $data[$header['round']][0]; ?>
                                            </td>
                                        <?php } else { ?>
                                            <td align='center'>
                                                -
                                            </td>
                                    <?php }
                                    } ?>
                                </tr>

                            </table>
                        </div>

                        <br>





                    <?php }
                    ?>


















                </div>
            </div>

            <div class="td-pb-span4 td-main-sidebar td-pb-border-top" style="padding-right: 40px; margin-top: 21px; padding-bottom: 16px;" role="complementary">
                <div class="td-ss-main-sidebar">
                    <aside class="widget widget_meta custom-sidebar">
                        <div class="block-title">
                            <span>Search for a driver</span>
                        </div>

                        <div class="table-row" style="margin-bottom: 10px; margin-left: 12px">
                            <input type="text" name="typeahead" class="typeahead tt-query" autocomplete="off" spellcheck="false" placeholder="Type your Query">
                        </div>
                    </aside>

                    <?php dynamic_sidebar('HomeS1'); ?>
                </div>

                <div class="td-ss-main-sidebar">
                    <aside class="widget widget_meta custom-sidebar">
                        <div class="block-title">
                            <span><?php echo ucwords(strtolower($name)); ?></span>
                        </div>

                        <div class="table-row" style="margin-bottom: 10px;">
                            <?php if (!empty($driver_data[0]['profile'])) { ?>
                                <div style="padding: 6px;"><img src='<?php $_SERVER['DOCUMENT_ROOT']; ?>/<?php echo $driver_data[0]['profile']; ?>' /></div>
                            <?php } ?>
                            <div class="custom-li">
                                <div><b>Nationality:</b></div>
                                <div><?php echo $driver_data[0]['nationality']; ?></div>
                            </div>
                            <div class="custom-li">
                                <div><b>Date of birth:</b></div>
                                <div><?php echo $driver_data[0]['dob']; ?></div>
                            </div>
                            <div class="custom-li">
                                <div><b>Races (all series):</b></div>
                                <div><?php echo $driver_data[0]['races']; ?></div>
                            </div>
                            <div class="custom-li">
                                <div><b>Victories (all series):</b></div>
                                <div><?php echo $driver_data[0]['wins']; ?></div>
                            </div>
                            <div class="custom-li">
                                <div><b>Podiums (all series):</b></div>
                                <div><?php echo $driver_data[0]['podiums']; ?></div>
                            </div>
                            <br>
                            <div class="custom-li">
                                <div><b>Series raced in:</b></div>
                                <div><?php echo $series_raced_in; ?></div>
                            </div>
                            <div class="custom-li">
                                <div><b>Years active:</b></div>
                                <div><?php echo $driver_data[0]['mindate']; ?> - <?php echo $driver_data[0]['maxdate']; ?></div>
                            </div>
                        </div>
                    </aside>

                    <?php dynamic_sidebar('HomeS1'); ?>
                </div>
            </div>


        </div>




    </div>


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