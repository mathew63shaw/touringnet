<?php
define('WP_USE_THEMES', false);
require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

require('../results/connection.php');

mysqli_set_charset($conn, "utf8");

$id = $_GET['id'];
$id = mysqli_real_escape_string($conn, $id);

$sql = "SELECT CASE WHEN CAST(qualifying.result AS UNSIGNED) = 0 THEN result ELSE CAST(qualifying.result AS UNSIGNED) END AS pos,qualifying.number, qualifying.`class`,
qualifying.driver, qualifying.driver_id, qualifying.driver2, qualifying.driver_id2, qualifying.driver3, qualifying.driver_id3, qualifying.driver4, qualifying.driver_id4,
drivers.image AS img, qualifying.entrant, qualifying.car, qualifying.laps, qualifying.time, qualifying.gap, qualifying.`qseg`, qualifying.id, qualifying.`date`
FROM `drivers` 
INNER JOIN qualifying 
ON drivers.id = qualifying.driver_id
WHERE qualifying.qual_id = {$id} AND qualifying.result > 0
ORDER BY id, pos ASC";

$sqlnc = "SELECT CASE WHEN CAST(qualifying.result AS UNSIGNED) = 0 THEN result ELSE CAST(qualifying.result AS UNSIGNED) END AS pos,qualifying.number, qualifying.`class`,
qualifying.driver, qualifying.driver_id, qualifying.driver2, qualifying.driver_id2, qualifying.driver3, qualifying.driver_id3, qualifying.driver4, qualifying.driver_id4,
drivers.image AS img, qualifying.entrant, qualifying.car, qualifying.laps, qualifying.time, qualifying.gap, qualifying.id
FROM `drivers` 
INNER JOIN qualifying 
ON drivers.id = qualifying.driver_id
WHERE qualifying.race_id = {$id} AND qualifying.result = 0
ORDER BY id, pos ASC";

$sql2 = "SELECT DISTINCT circuits.`layout`, qualifying.`series`, qualifying.`year`, qualifying.`round`, DATE FROM qualifying, circuits WHERE qualifying.qual_id = {$id} AND qualifying.`track` = circuits.`configuration`";
$sqlcircuit = "SELECT DISTINCT circuits.graphic_path, circuits.circuit FROM circuits LEFT JOIN qualifying ON qualifying.track = circuits.configuration WHERE qualifying.qual_id = {$id}";
$sqlnotes = "SELECT DISTINCT notes.note FROM notes LEFT JOIN qualifying ON qualifying.race_id = notes.race_id WHERE qualifying.qual_id = {$id}";
$sqlprev = "SELECT DISTINCT qual_id FROM qualifying WHERE qual_id = (SELECT MAX(qual_id) FROM qualifying WHERE qual_id < {$id})";
$sqlnext = "SELECT DISTINCT qual_id FROM qualifying WHERE qual_id = (SELECT MIN(qual_id) FROM qualifying WHERE qual_id > {$id})";
$sql_series_prev = "SELECT MAX(qual_id) AS prev_race_id, series, `year` FROM qualifying WHERE series=(SELECT series FROM qualifying WHERE qual_id = {$id} LIMIT 1) AND qual_id < {$id}";
$sql_series_next = "SELECT MIN(qual_id) AS next_race_id, series, `year` FROM qualifying WHERE series=(SELECT series FROM qualifying WHERE qual_id = {$id} LIMIT 1) AND qual_id > {$id}";

$footer_sql = "SELECT r.round, c.layout, r.qual_id
FROM (SELECT rr.`round`, rr.track, rr.`qual_id`
FROM qualifying rr, (SELECT series, `year` FROM qualifying WHERE qual_id = {$id} LIMIT 1) temp
WHERE rr.series=temp.series AND rr.`year`=temp.year
GROUP BY rr.`round`) r
LEFT JOIN circuits c
ON r.track=c.configuration
ORDER BY r.`round`+0";

$result = mysqli_query($conn, $sql);
$resultnc = mysqli_query($conn, $sqlnc);
$result2 = mysqli_query($conn, $sql2);
$resultcircuit = mysqli_query($conn, $sqlcircuit);
$resultnotes = mysqli_query($conn, $sqlnotes);
$resultprev = mysqli_query($conn, $sqlprev);
$resultnext = mysqli_query($conn, $sqlnext);
$result_series_prev = mysqli_query($conn, $sql_series_prev);
$result_series_next = mysqli_query($conn, $sql_series_next);
$footer_result = mysqli_query($conn, $footer_sql);


// Check existing more than one driver
$q1_results = [];
$q2_results = [];
$q3_results = [];
$q4_results = [];
$no_exit_more_than_one_driver = 1;
$race_date = '';
while ($row = mysqli_fetch_assoc($result)) {
    $race_date = $row['date'];
    if ($row['driver2'] || $row['driver3'] || $row['driver4']) {
        $no_exit_more_than_one_driver = 0;
    }
    if ($row['qseg'] == 'Q1') {
        $q1_results[] = $row;
    }
    if ($row['qseg'] == 'Q2') {
        $q2_results[] = $row;
    }
    if ($row['qseg'] == 'Q3') {
        $q3_results[] = $row;
    }
    if ($row['qseg'] == 'Q4') {
        $q4_results[] = $row;
    }
}


$nc_results = [];
$no_exit_more_than_one_driver_nc = 1;
while ($row = mysqli_fetch_assoc($resultnc)) {
    $nc_results[] = $row;
    if ($row['driver2'] || $row['driver3'] || $row['driver4']) {
        $no_exit_more_than_one_driver_nc = 0;
    }
}


// Drivers data
$sqldrivers = "SELECT id, image AS img FROM drivers ORDER BY id";
$drivers_result = mysqli_query($conn, $sqldrivers);
$drivers = [];
while ($row = mysqli_fetch_assoc($drivers_result)) {
    $drivers[$row['id']] = $row['img'];
}

$series = '';
$year = '';
$layout = '';
$round = '';
while ($row = mysqli_fetch_assoc($result2)) {
    $series = $row['series'];
    $year = $row['year'];
    $layout = $row['layout'];
    $round = $row['round'];
}

function print_q($results, $no_exit_more_than_one_driver, $drivers, $title)
{
    $html = '<h6>' . ($title ? 'QUALIFYING - ' . $title : 'QUALIFYING') . '</h6>
            <div class="tb-row header"><div class="wrapper pos-nr-cl">
                <div class="column pos"><span class="circled">P</span></div>
                    <div class="column nr"><span class="number">NO</span></div>
                    <div class="column cl">Cl</div>
                </div>
                <div class="wrapper driver-nat">
                    <div class="column driver">
                        <div class="inline-driver-nat">
                            <div>DRIVER</div>
                            <div>NAT</div>
                        </div>
                    </div>
                </div>
                <div class="wrapper entrant-car">
                    <div class="column entrant">ENTRANT</div>
                    <div class="column car">CAR</div>
                </div>
                <div class="wrapper laps-time-best-gd">
                    <div class="' . ($no_exit_more_than_one_driver ? "wrapper no-one-more-driver" : "wrapper laps-time") . '">
                        <div class="column laps">LAPS</div>
                        <div class="column time">TIME</div>
                    </div>
                    <div class="' . ($no_exit_more_than_one_driver ? "wrapper no-one-more-driver" : "wrapper best-gd") . '">
                        <div class="column gd">GAP</div>
                    </div>
                </div>
            </div>';

    foreach ($results as $row) {
        $html .= "<div class='tb-row'>
            <div class='wrapper pos-nr-cl'><div class='column pos'><span class='circled'>" . $row["pos"] . "</span></div><div class='column nr'><span class='number'>" . $row["number"] . "</span></div><div class='column cl'>" . (($row["class"] == 'M' or $row["class"] == 'I') ? '<span class="spanclass">' : "") . $row["class"] . (($row["class"] == 'M' or $row["class"] == 'I') ? '</span>' : "") . "</div></div>
            <div class='wrapper driver-nat'>
                <div class='column driver'>
                    <div class='inline-driver-nat'>
                        <a href='driver.php?name=" . $row["driver"] . "'>" . $row["driver"] . "</a>
                        <img src='../results/flag/" . $row["img"] . ".gif' />
                    </div>"
            . ($row["driver2"] ?
                "<div class='inline-driver-nat'>
                        <a href='driver.php?name=" . $row["driver2"] . "'>" . $row["driver2"] . "</a>
                        <img src='../results/flag/" . $drivers[$row['driver_id2']] . ".gif' />
                    </div>" : "")
            . ($row["driver3"] ?
                "<div class='inline-driver-nat'>
                        <a href='driver.php?name=" . $row["driver3"] . "'>" . $row["driver3"] . "</a>
                        <img src='../results/flag/" . $drivers[$row['driver_id3']] . ".gif' />
                    </div>" : "")
            . ($row["driver4"] ?
                "<div class='inline-driver-nat'>
                        <a href='driver.php?name=" . $row["driver4"] . "'>" . $row["driver4"] . "</a>
                        <img src='../results/flag/" . $drivers[$row['driver_id4']] . ".gif' />
                    </div>" : "") . "												
                </div>
            </div>
            <div class='wrapper entrant-car'><div class='column entrant'>" . $row["entrant"] . "</div><div class='column car'>" . $row["car"] . "</div></div>
            <div class='wrapper laps-time-best-gd'><div class='" . ($no_exit_more_than_one_driver ? 'wrapper no-one-more-driver' : 'wrapper laps-time') . "'><div class='column laps'>" . $row["laps"] . "</div><div class='column time'>" . $row["time"] . "</div></div><div class='" . ($no_exit_more_than_one_driver ? 'wrapper no-one-more-driver' : 'wrapper laps-time') . "'><div class='column gd'>" . $row["gap"] . "</div></div></div>
            </div>";
    }

    echo $html;
}
?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head profile="http://gmpg.org/xfn/11">
    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
    <title><?php bloginfo('name'); ?> &raquo; Database &raquo; <?php echo $series . " " . $year; ?> &raquo; Round <?php echo $round; ?> Results</title>
    <link rel="stylesheet/less" type="text/css" href="qualifying.less" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/less.js/3.0.0/less.min.js"></script>
</head>

<?php get_header(); ?>

<body style="display: none;">
    <div class="td-container-wrap" style="padding: 20px;">

        <div class="td-pb-row">

            <div class="td-pb-span12">

                <div class="td-ss-main-content">

                    <div class="clearfix"></div>

                    <a href='../../'><?php bloginfo('name'); ?></a> &raquo; <a href="index.php?series=<?php echo $series; ?>&year=<?php echo $year; ?>">Database</a> &raquo; <?php echo $series . " " . $year; ?> &raquo; Round <?php echo $round; ?> Results

                    <div class="stats-div">

                        <div class="container-fluid" style="margin-top: 10px">

                            <div class="td_block_wrap tdb_title tdi_78_07e tdb-single-title td-pb-border-top td_block_template_1" style="margin-bottom: 10px;">
                                <div class="tdb-block-inner td-fix-index">
                                    <h1 class="tdb-title-text" style="font-family: Oxygen; font-size: 20px; font-weight: 800; line-height: 25px; width: 100%;"><?php echo $series . " " . $year . " &raquo; " . $layout . " Round " . $round; ?> Results</h1>
                                </div>
                            </div>

                            <?php
                            if (count($q2_results) == 0 && count($q3_results) == 0 && count($q4_results) == 0) {
                                print_q($q1_results, $no_exit_more_than_one_driver, $drivers, '');
                            } else {
                                if (count($q4_results) > 0) {
                                    print_q($q4_results, $no_exit_more_than_one_driver, $drivers, 'Q4');
                                }
                                if (count($q3_results) > 0) {
                                    print_q($q3_results, $no_exit_more_than_one_driver, $drivers, 'Q3');
                                }
                                if (count($q2_results) > 0) {
                                    print_q($q2_results, $no_exit_more_than_one_driver, $drivers, 'Q2');
                                }
                                if (count($q1_results) > 0) {
                                    print_q($q1_results, $no_exit_more_than_one_driver, $drivers, 'Q1');
                                }
                            }
                            ?>

                            <?php if (count($nc_results) > 0) { ?>
                                <div class="tb-row header">
                                    <div style="width: 100%;">Not classified</div>
                                </div>
                            <?php foreach ($nc_results as $row) {
                                    echo "<div class='tb-row'>
										<div class='wrapper pos-nr-cl'><div class='column pos'><span class='circled'>" . $row["pos"] . "</span></div><div class='column nr'><span class='number'>" . $row["number"] . "</span></div><div class='column cl'>" . (($row["class"] == 'M' or $row["class"] == 'I') ? '<span class="spanclass">' : "") . $row["class"] . (($row["class"] == 'M' or $row["class"] == 'I') ? '</span>' : "") . "</div></div>
										<div class='wrapper driver-nat'>
											<div class='column driver'>
												<div class='inline-driver-nat'>
													<a href='driver.php?name=" . $row["driver"] . "'>" . $row["driver"] . "</a>
													<img src='../results/flag/" . $row["img"] . ".gif' />
												</div>"
                                        . ($row["driver2"] ?
                                            "<div class='inline-driver-nat'>
													<a href='driver.php?name=" . $row["driver2"] . "'>" . $row["driver2"] . "</a>
													<img src='../results/flag/" . $drivers[$row['driver_id2']] . ".gif' />
												</div>" : "")
                                        . ($row["driver3"] ?
                                            "<div class='inline-driver-nat'>
													<a href='driver.php?name=" . $row["driver3"] . "'>" . $row["driver3"] . "</a>
													<img src='../results/flag/" . $drivers[$row['driver_id3']] . ".gif' />
												</div>" : "")
                                        . ($row["driver4"] ?
                                            "<div class='inline-driver-nat'>
													<a href='driver.php?name=" . $row["driver4"] . "'>" . $row["driver4"] . "</a>
													<img src='../results/flag/" . $drivers[$row['driver_id4']] . ".gif' />
												</div>" : "") . "												
											</div>
										</div>
										<div class='wrapper entrant-car'><div class='column entrant'>" . $row["entrant"] . "</div><div class='column car'>" . $row["car"] . "</div></div>
										<div class='wrapper laps-time-best-gd'><div class='" . ($no_exit_more_than_one_driver_nc ? 'wrapper no-one-more-driver' : 'wrapper laps-time') . "'><div class='column laps'>" . $row["laps"] . "</div><div class='column time'>" . $row["time"] . "</div></div><div class='" . ($no_exit_more_than_one_driver_nc ? 'wrapper no-one-more-driver' : 'wrapper laps-time') . "'><div class='column gd'>" . $row["gap"] . "</div></div></div>
									  </div>";
                                }
                            }

                            mysqli_close($conn);

                            ?>

                            <br />

                        </div>

                    </div>

                </div>

            </div>

            <!-- Circuit info box -->
            <div class="td-pb-span6">
                <div class="td-ss-main-content">
                    <div class="stats-div">
                        <div class="container-fluid">
                            <div class="tb-row header">
                                <div style="width: 100%;">Circuit info</div>
                            </div>
                            <div style="width: 100%; float: left; display: flex; align-items: center; justify-content: center; padding-top: 5px;">
                                <?php if (mysqli_num_rows($resultcircuit) > 0) {
                                    while ($row = mysqli_fetch_assoc($resultcircuit)) {
                                        echo "<a href='/circuit-wins-list?track=" . $row["circuit"] . "'><img src=" . $row["graphic_path"] . " style='max-width: 350px; object-fit: cover; object-position: 30% 130%; width: 350px; height: 240px;' /></a>";
                                    }
                                } else {
                                    echo "";
                                }    ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Note box -->
            <div class="td-pb-span6">
                <div class="td-ss-main-content">
                    <div class="stats-div">
                        <div class="container-fluid">
                            <div class="tb-row header">
                                <div style="width: 100%;">Notes</div>
                            </div>

                            <div style="padding: 5px;">

                                <div class="fastest-laps-container">

                                    <div>
                                        <b>Date of race:</b>&nbsp;&nbsp; <?php echo $race_date; ?>
                                    </div>
                                </div>

                                <div style="margin-top: 5px;">
                                    <?php if (mysqli_num_rows($resultnotes) > 0) {
                                        while ($row = mysqli_fetch_assoc($resultnotes)) {
                                            echo "<p style='padding: 5px;'>" . $row["note"] . "</p>";
                                        }
                                    } else {
                                        echo "No notes on this race.";
                                    }    ?>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Prev and Next race -->
            <div class="td-pb-span12">
                <div class="td-ss-main-content">
                    <div class="stats-div">
                        <div class="container-fluid">
                            <div style="width: 100%; padding-bottom: 5px;">
                                <?php if (mysqli_num_rows($resultprev) > 0) {
                                    while ($row = mysqli_fetch_assoc($resultprev)) {
                                        echo "<span class='prevrace'><a href='qualifying.php?id=" . $row["qual_id"] . "'>Previous race</a></span>";
                                    }
                                } else {
                                    echo "";
                                }    ?>
                                <?php if (mysqli_num_rows($resultnext) > 0) {
                                    while ($row = mysqli_fetch_assoc($resultnext)) {
                                        echo "<span class='nextrace'><a href='qualifying.php?id=" . $row["qual_id"] . "'>Next race</a></span>";
                                    }
                                } else {
                                    echo "";
                                }    ?>
                                <div style="clear: both;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Previous and Next series race buttons -->
            <div class="td-pb-span12" style="margin-top: 5px;">
                <div class="td-ss-main-content">
                    <div class="stats-div">
                        <div class="container-fluid">
                            <div style="width: 100%; padding-bottom: 5px;">
                                <?php if (mysqli_num_rows($result_series_prev) > 0) {
                                    while ($row = mysqli_fetch_assoc($result_series_prev)) {
                                        echo $row['prev_race_id'] ? "<span class='prevrace'><a href='qualifying.php?id=" . $row["prev_race_id"] . "'>Previous race (" . $series . ")</a></span>" : "";
                                    }
                                } else {
                                    echo "";
                                }    ?>
                                <?php if (mysqli_num_rows($result_series_next) > 0) {
                                    while ($row = mysqli_fetch_assoc($result_series_next)) {
                                        echo $row["next_race_id"] ? "<span class='nextrace'><a href='qualifying.php?id=" . $row["next_race_id"] . "'>Next race (" . $series . ")</a></span>" : "";
                                    }
                                } else {
                                    echo "";
                                }    ?>
                                <div style="clear: both;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="td-pb-span12" style="margin-top: 5px;">
                <div class="td-ss-main-content">
                    <div class="container-fluid">
                        <div class="tb-row header">
                            <div style="width: 100%;">Footer</div>
                        </div>
                        <div style="width: 100%; padding: 5px;">
                            <?php if (mysqli_num_rows($footer_result) > 0) {
                                while ($row = mysqli_fetch_assoc($footer_result)) {
                                    echo "<a href='qualifying.php?id={$row['qual_id']}'>Round " . $row['round'] . ": " . $row['layout'] . "</a>,&nbsp;";
                                }
                                echo "<a href='index.php?series={$series}&year={$year}'>" . $series . " " . $year . "</a>";
                            } else {
                                echo "";
                            }    ?>

                            <div style="clear: both;"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div><!-- End of td-container div -->

    <?php get_footer(); ?>

    <script>
        (function() {
            jQuery('body').css('display', 'block');
        })();
    </script>