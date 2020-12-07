<?php require $_SERVER['DOCUMENT_ROOT'] . ('/wp-blog-header.php');

// DB connection 
require('../results/connection.php');
mysqli_set_charset($conn, "utf8");

// Get params
$series = $_GET['series'];
$series = mysqli_real_escape_string($conn, $series);
$year = $_GET['year'];

/**
 * Get title
 */
$title_sql = "SELECT `code`, title FROM series WHERE `code`='" . $series . "'";
$title_query_result = mysqli_query($conn, $title_sql);
while ($row = mysqli_fetch_assoc($title_query_result)) {
    $series_title = $row['title'];
}

/**
 * Get events-circuits data
 */
$events_circuits_data = [];
$events_circuits_sql = "SELECT e.event_id, e.round, e.date, e.circuit, e.`race_id`, e.`qual_id`,  c.`graphic_path`
                        FROM (SELECT * FROM `event` WHERE `year`='" . $year . "' AND `series`='" . $series . "') e
                        LEFT JOIN circuits c
                        ON e.circuit = c.configuration
                        ORDER BY e.date, e.round+0";
$events_circuits_query_result = mysqli_query($conn, $events_circuits_sql);
while ($row = mysqli_fetch_assoc($events_circuits_query_result)) {
    $events_circuits_data[$row['event_id']][] = [$row['round'], $row['date'], $row['circuit'], $row['race_id'], $row['qual_id'], $row['graphic_path']];
}



/**
 * Get top ten drivers
 */
$classification = 'Drivers';
$top_ten_drivers = [];
$top_ten_sql = "SELECT p.driver_id, d.driver, d.nationality, p.rank, p.points, d.image, d.profile2020 AS `profile`, d.country
                FROM (SELECT * FROM points WHERE `year`='" . $year . "' AND `series`='" . $series . "' AND classification='" . $classification . "' ORDER BY rank+0 LIMIT 10) p
                LEFT JOIN drivers d
                ON p.driver_id = d.id";
$top_ten_query_result = mysqli_query($conn, $top_ten_sql);
if (mysqli_num_rows($top_ten_query_result)) { // classification = Drivers
    while ($row = mysqli_fetch_assoc($top_ten_query_result)) {
        $top_ten_drivers[] = $row;
    }
} else { // classification = Touring
    $classification = 'Touring';
    $top_ten_sql = "SELECT p.driver_id, d.driver, d.nationality, p.rank, p.points, d.image, d.profile2020 AS `profile`, d.country
                FROM (SELECT * FROM points WHERE `year`='" . $year . "' AND `series`='" . $series . "' AND classification='" . $classification . "' ORDER BY rank+0 LIMIT 10) p
                LEFT JOIN drivers d
                ON p.driver_id = d.id";
    $top_ten_query_result = mysqli_query($conn, $top_ten_sql);
    while ($row = mysqli_fetch_assoc($top_ten_query_result)) {
        $top_ten_drivers[] = $row;
    }
}



/**
 * Get driver with most wins
 */
$most_wins_drivers = [];
$most_wins_drivers_sql = "SELECT *
                            FROM (
                            (SELECT races.driver, drivers.image,SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END) AS Wins, COUNT(races.driver) AS Races, ROUND(((SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END)) / COUNT(races.driver))*100,1) AS Percent FROM `drivers` INNER JOIN races ON drivers.id = races.driver_id WHERE `Series` IN ('" . $series . "') AND YEAR='" . $year . "' GROUP BY races.driver HAVING Wins > 0)
                            UNION
                            (SELECT races.driver2 AS Drvr2, drivers.image,SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END) AS Wins, (SELECT COUNT(races.driver) FROM `races` WHERE races.driver = Drvr2) + (SELECT COUNT(races.driver2) FROM `races` WHERE races.driver2 = Drvr2), ROUND(((SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END)) / (SELECT COUNT(races.driver2) FROM `races` WHERE races.driver = Drvr2))*100,1) AS Percent FROM `drivers` INNER JOIN races ON drivers.id = races.driver_id2 WHERE `Series` IN ('" . $series . "') AND YEAR='" . $year . "' GROUP BY races.driver2 HAVING Wins > 0)
                            ORDER BY 3 DESC) temp
                            WHERE temp.Wins=(SELECT MAX(ff.Wins) FROM (
                            (SELECT races.driver, drivers.image,SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END) AS Wins, COUNT(races.driver) AS Races, ROUND(((SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END)) / COUNT(races.driver))*100,1) AS Percent FROM `drivers` INNER JOIN races ON drivers.id = races.driver_id WHERE `Series` IN ('" . $series . "') AND YEAR='" . $year . "' GROUP BY races.driver HAVING Wins > 0)
                            UNION
                            (SELECT races.driver2 AS Drvr2, drivers.image,SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END) AS Wins, (SELECT COUNT(races.driver) FROM `races` WHERE races.driver = Drvr2) + (SELECT COUNT(races.driver2) FROM `races` WHERE races.driver2 = Drvr2), ROUND(((SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END)) / (SELECT COUNT(races.driver2) FROM `races` WHERE races.driver = Drvr2))*100,1) AS Percent FROM `drivers` INNER JOIN races ON drivers.id = races.driver_id2 WHERE `Series` IN ('" . $series . "') AND YEAR='" . $year . "' GROUP BY races.driver2 HAVING Wins > 0)
                            ORDER BY 3 DESC) ff)";
$most_wins_drivers_query_result = mysqli_query($conn, $most_wins_drivers_sql);
while ($row = mysqli_fetch_assoc($most_wins_drivers_query_result)) {
    $most_wins_drivers[] = $row;
}


/**
 * Get driver with most podiums
 */
$most_podiums_data = [];
$most_podiums_sql = "SELECT *
                    FROM (SELECT races.driver, drivers.image,SUM(CASE WHEN races.year='2001' AND races.class_pos IN ('1', '2', '3') AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result IN ('1', '2', '3') AND races.class != 'P' THEN 1 ELSE 0 END) AS Podiums, COUNT(races.driver) AS Races, ROUND(((SUM(CASE WHEN races.year='2001' AND races.class_pos IN ('1', '2', '3') AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result IN ('1', '2', '3') AND races.class != 'P' THEN 1 ELSE 0 END)) / COUNT(races.driver))*100,1) AS Percent FROM `drivers` INNER JOIN races ON drivers.id = races.driver_id WHERE `Series` IN ('" . $series . "') AND YEAR ='" . $year . "' GROUP BY races.driver HAVING Podiums > 0 ORDER BY 3 DESC) temp
                    WHERE temp.Podiums=(SELECT MAX(ff.Podiums) FROM (SELECT races.driver, drivers.image,SUM(CASE WHEN races.year='2001' AND races.class_pos IN ('1', '2', '3') AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result IN ('1', '2', '3') AND races.class != 'P' THEN 1 ELSE 0 END) AS Podiums, COUNT(races.driver) AS Races, ROUND(((SUM(CASE WHEN races.year='2001' AND races.class_pos IN ('1', '2', '3') AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result IN ('1', '2', '3') AND races.class != 'P' THEN 1 ELSE 0 END)) / COUNT(races.driver))*100,1) AS Percent FROM `drivers` INNER JOIN races ON drivers.id = races.driver_id WHERE `Series` IN ('" . $series . "') AND YEAR ='" . $year . "' GROUP BY races.driver HAVING Podiums > 0 ORDER BY 3 DESC) ff)";
$most_podiums_query_result = mysqli_query($conn, $most_podiums_sql);
while ($row = mysqli_fetch_assoc($most_podiums_query_result)) {
    $most_podiums_data[] = $row;
}

/**
 * Get driver with most pole positions
 */
$most_pole_data = [];
$most_pole_sql = "SELECT *
                FROM (SELECT q.`driver`, d.image, SUM(CASE WHEN q.result='1' THEN 1 ELSE 0 END) AS Poles FROM `qualifying` q, drivers d WHERE q.series IN ('" . $series . "') AND q.`year` = '" . $year . "' AND q.topq='Y' AND d.id=q.driver_id GROUP BY q.driver HAVING poles > 0 ORDER BY 2 DESC) temp
                WHERE temp.Poles=(SELECT MAX(ff.Poles) FROM (SELECT q.`driver`, d.image, SUM(CASE WHEN q.result='1' THEN 1 ELSE 0 END) AS Poles FROM `qualifying` q, drivers d WHERE q.series IN ('" . $series . "') AND q.`year` = '" . $year . "' AND q.topq='Y' AND d.id=q.driver_id GROUP BY q.driver HAVING poles > 0 ORDER BY 2 DESC) ff)";
$most_pole_query_result = mysqli_query($conn, $most_pole_sql);
while ($row = mysqli_fetch_assoc($most_pole_query_result)) {
    $most_pole_data[] = $row;
}

/**
 * Get driver with most fastest laps
 */
$most_fastest_data = [];
$most_fastest_sql = "SELECT *
                    FROM (SELECT r.driver, d.image, SUM(CASE WHEN r.fl='Y' THEN 1 ELSE 0 END) AS FastestLaps FROM `races` r, drivers d WHERE r.series IN ('" . $series . "') AND r.year = '" . $year . "' AND r.driver_id=d.id GROUP BY r.driver HAVING FastestLaps > 0 ORDER BY 2 DESC) temp
                    WHERE temp.FastestLaps=(SELECT MAX(ff.FastestLaps) FROM (SELECT r.driver, d.image, SUM(CASE WHEN r.fl='Y' THEN 1 ELSE 0 END) AS FastestLaps FROM `races` r, drivers d WHERE r.series IN ('" . $series . "') AND r.year = '" . $year . "' AND r.driver_id=d.id GROUP BY r.driver HAVING FastestLaps > 0 ORDER BY 2 DESC) ff)";
$most_fastest_query_result = mysqli_query($conn, $most_fastest_sql);
while ($row = mysqli_fetch_assoc($most_fastest_query_result)) {
    $most_fastest_data[] = $row;
}


/**
 * Get footer data (unique series from series table, year)
 */
$footer_data = [];
$footer_sql = "SELECT r.series, r.year, s.title
                FROM (SELECT series, `year`
                FROM races
                GROUP BY series, `year`
                ORDER BY series, `year`) r
                LEFT JOIN series s
                ON r.series=s.code";
$footer_query_result = mysqli_query($conn, $footer_sql);
while ($row = mysqli_fetch_assoc($footer_query_result)) {
    $footer_data[$row['series']][] = [$row['title'], $row['year']];
}

// echo count($footer_data);exit;
// echo "<pre>";
// var_dump($footer_data);
// echo "</pre>";
// exit;



?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
    <title><?php bloginfo('name'); ?> &raquo; Results &raquo; British Touring Car Championship &raquo; 2020 &raquo; Results</title>
</head>
<style>
    .custom-card {
        /* border: solid 2px #f1545a;
        border-radius: 10px; */
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
        padding: 10px;
        margin-bottom: 10px;
    }

    .custom-footer {
        border: solid 1px #ededed;
        margin: 10px;
        padding: 20px;
    }

    .custom-list {
        margin-left: 12px;
        display: flex;
        justify-content: space-between;
    }

    .pos {
        padding-right: 10px;
    }

    .custom-sidebar {
        box-shadow: 10px 8px 10px #f2f2f2;
    }
	
	.dates {
		font-style: italic;
		font-size: 11px;
	}
	.round-date {
		margin-bottom: 5px;
	}
	.qualifying {
		margin-bottom: 5px;
	}
	.qual a{
		font-weight: bold;
		color: #000;
	}
	.qual a:hover{
		text-decoration: underline;
		font-weight: bold;
		color: #000;
	}
	.races {
		margin-bottom: 2px;
	}
	.race a{
	font-weight: bold;
	color: #000;
	}
	.race a:hover{
		text-decoration: underline;
		font-weight: bold;
		color: #000;
	}
	.footeryear {
		border: 1px solid #e5e5e5;
		border-radius: 5px;
		padding: 3px;
		line-height: 12px;
		display: inline-block;
		margin-bottom: 4px;
		background: #F7F7F7;
	}
	.footeryear a {
		color: #000000;
	}
	.footeryear:hover {
		background: #E5E5E5;
	}
	.seriesgroup {
		margin-bottom: 5px;
		border-bottom: 1px dashed #E5E5E5;
	}
</style>

<?php get_header(); ?>

<div class="td-container-wrap" style="padding: 20px;">
    <div class="td-pb-row">
        <div class="td-pb-span12">
            <div class="td-post-header td-pb-padding-side">
                <ul class="td-category">
                    <li class='entry-category'><a href='/'><?php bloginfo('name'); ?></a></li>
                    <li class='entry-category'><a href='/results/index.php'>Results</a></li>
                    <li class='entry-category'><a href='/results/btcc/index.php'><?php echo $series_title; ?></a></li>
                    <li class='entry-category'><a href='index.php'><?php echo $year; ?></a></li>
                </ul>

                <div class="td_block_wrap tdb_title tdi_78_07e tdb-single-title td-pb-border-top td_block_template_1" style="margin-bottom: 0px;">
					<div class="tdb-block-inner td-fix-index">
						<h1 class='tdb-title-text' style="font-family: Oxygen; font-size: 32px; font-weight: 800;"><?php echo $year . ' ' . $series_title; ?> Race Results</h1>
					</div>
				</div>
            </div>
        </div>
    </div>

    <div class="td-pb-row">
        <div class="td-pb-span8 td-main-content">
            <div class="td-ss-main-content">
                <div class="td-post-content">

                    <?php
                    $i = 0;
                    foreach ($events_circuits_data as $key => $values) {
                        // Making rounds
                        $rounds = 'Rounds ';
                        $length = count($values);
                        if ($length == 1) {
                            $rounds = 'Round ';
                            $rounds .= $values[0][0];
                        } else if ($length == 2) {
                            $rounds .= $values[0][0] . ' and ' . $values[1][0];
                        } else {
                            for ($i = 0; $i < $length - 2; $i++) {
                                $rounds .= $values[$i][0] . ', ';
                            }
                            $rounds .= $values[$length - 2][0] . ' and ' . $values[$length - 1][0];
                        }
                        // Get min and max date
                        $from = $values[0][1]; // since date field is ordered by date
                        $to = $values[$length - 1][1];
                    ?>


                        <div class="td-pb-span6" style="padding-left: <?php echo ($i == 1 ? "9px" : "10px"); ?>; padding-right: <?php echo ($i == 1 ? "9px" : "10px"); ?>">

                            <div class="custom-card">
                                <img src="<?php $_SERVER['DOCUMENT_ROOT']; ?><?php echo $values[0][5]; ?>" title="<?php $values[0][2]; ?>" style="width: auto; height: auto;" />
                                <p class="round-date">
                                    <b><?php echo $rounds; ?></b>, 
									<span class="dates">
                                        <?php
										$formattedfrom = date('D M j', strtotime($from));
										$formattedto = date('D M j', strtotime($to));
                                        if ($from == $to) {
                                            echo $formattedfrom;
                                        } else {
                                            echo $formattedfrom . ' - ' . $formattedto;
                                        }
                                        ?>
                                    </span>
								</p>
								<p class="qualifying">
										<span class="qual">
											&raquo; <a href='qualifying.php?id=<?php echo $values[0][4]; ?>'>Qualifying</a>
										</span>
                                </p>
								<p class="races">
										<?php
										foreach ($values as $item) { ?>
												<span class="race" style="margin-bottom: 8px;">
													&raquo; <a href='race.php?id=<?php echo $item[3]; ?>' style='line-height: 16px;'>Round <?php echo $item[0]; ?></a>
												</span><br />
										<?php }
										?>
                                </p>
                            </div>
                        </div>
                    <?php $i++;
                    } ?>

                </div>
            </div>
        </div>

        <div class="td-pb-span4 td-main-sidebar td-pb-border-top" style="padding-right: 40px; margin-top: 21px; padding-bottom: 16px;" role="complementary">
            <div class="td-ss-main-sidebar">
                <aside class="widget widget_meta custom-sidebar">
                    <div class="block-title">
                        <span>Top 10 Drivers</span>
                    </div>

                    <?php
                    for ($i = 0; $i < count($top_ten_drivers); $i++) { ?>
                        <div class="table-row" style="margin-bottom: 10px;">
                            <div class="custom-list">
                                <div>
                                    <b><?php echo $top_ten_drivers[$i]['rank']; ?></b>
                                    &nbsp;&nbsp;<img src="<?php $_SERVER['DOCUMENT_ROOT']; ?>/results/flag/<?php echo $top_ten_drivers[$i]['image']; ?>.gif" title="<?php echo $top_ten_drivers[$i]['country']; ?>">
                                    &nbsp;<?php echo $top_ten_drivers[$i]['driver']; ?>
                                </div>
                                <div class="pos"><?php echo $top_ten_drivers[$i]['points']; ?></div>
                            </div>
                        </div>
                        <hr>
                    <?php }
                    ?>

                    <div class="table-row" style="margin-bottom: 10px;">
                        <div class='standings-topten'><b><a href='<?php echo get_option('home'); ?>/database/standings.php?series=<?php echo $series; ?>&year=<?php echo $year; ?>'>Championship Standings</a></b></div>
                    </div>

                </aside>

                <?php dynamic_sidebar('HomeS1'); ?>
            </div>

            <div class="td-ss-main-sidebar">
                <aside class="widget widget_meta custom-sidebar">
                    <div class="block-title">
                        <span>Champion</span>
                    </div>

                    <div class="table-row" style="margin-bottom: 10px;">
                        <?php if (!empty($top_ten_drivers[0]['profile'])) { ?>
                            <div class='car'><img src='<?php $_SERVER['DOCUMENT_ROOT']; ?>/<?php echo $top_ten_drivers[0]['profile']; ?>' /></div>
                        <?php } ?>
                    </div>
                </aside>

                <?php dynamic_sidebar('HomeS1'); ?>
            </div>

            <div class="td-ss-main-sidebar">
                <aside class="widget widget_meta custom-sidebar">
                    <div class="block-title">
                        <span>Driver with most wins</span>
                    </div>

                    <?php
                    for ($i = 0; $i < count($most_wins_drivers); $i++) { ?>
                        <div class="table-row" style="margin-bottom: 10px;">
                            <div class="custom-list">
                                <div>
                                    <img src="<?php $_SERVER['DOCUMENT_ROOT']; ?>/results/flag/<?php echo $most_wins_drivers[$i]['image']; ?>.gif">
                                    &nbsp;<?php echo $most_wins_drivers[$i]['driver']; ?>
                                </div>
                                <div class='pos'>
                                    Wins:&nbsp;<?php echo $most_wins_drivers[$i]['Wins']; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        if (count($most_wins_drivers) > 1) { ?>
                            <hr>
                        <?php }
                        ?>
                    <?php }
                    ?>
                </aside>

                <?php dynamic_sidebar('HomeS1'); ?>
            </div>

            <div class="td-ss-main-sidebar">
                <aside class="widget widget_meta custom-sidebar">
                    <div class="block-title">
                        <span>Driver with most podiums</span>
                    </div>

                    <?php
                    for ($i = 0; $i < count($most_podiums_data); $i++) { ?>
                        <div class="table-row" style="margin-bottom: 10px;">
                            <div class='custom-list'>
                                <div>
                                    <img src="<?php $_SERVER['DOCUMENT_ROOT']; ?>/results/flag/<?php echo $most_podiums_data[$i]['image']; ?>.gif">
                                    &nbsp;<?php echo $most_podiums_data[$i]['driver']; ?>
                                </div>
                                <div class='pos'>
                                    Podiums:&nbsp;<?php echo $most_podiums_data[$i]['Podiums']; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        if (count($most_podiums_data) > 1) { ?>
                            <hr>
                        <?php }
                        ?>
                    <?php }
                    ?>
                </aside>

                <?php dynamic_sidebar('HomeS1'); ?>
            </div>

            <div class="td-ss-main-sidebar">
                <aside class="widget widget_meta custom-sidebar">
                    <div class="block-title">
                        <span>Driver with most pole positions</span>
                    </div>

                    <?php
                    for ($i = 0; $i < count($most_pole_data); $i++) { ?>
                        <div class="table-row" style="margin-bottom: 10px;">
                            <div class='custom-list'>
                                <div>
                                    <img src="<?php $_SERVER['DOCUMENT_ROOT']; ?>/results/flag/<?php echo $most_pole_data[$i]['image']; ?>.gif">
                                    &nbsp;<?php echo $most_pole_data[$i]['driver']; ?>
                                </div>
                                <div class='pos'>
                                    Pole:&nbsp;<?php echo $most_pole_data[$i]['Poles']; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        if (count($most_pole_data) > 1) { ?>
                            <hr>
                        <?php }
                        ?>
                    <?php }
                    ?>
                </aside>

                <?php dynamic_sidebar('HomeS1'); ?>
            </div>

            <div class="td-ss-main-sidebar">
                <aside class="widget widget_meta custom-sidebar">
                    <div class="block-title">
                        <span>Driver with most fastest laps</span>
                    </div>

                    <?php
                    for ($i = 0; $i < count($most_fastest_data); $i++) { ?>
                        <div class="table-row" style="margin-bottom: 10px;">
                            <div class='custom-list'>
                                <div>
                                    <img src="<?php $_SERVER['DOCUMENT_ROOT']; ?>/results/flag/<?php echo $most_fastest_data[$i]['image']; ?>.gif">
                                    &nbsp;<?php echo $most_fastest_data[$i]['driver']; ?>
                                </div>
                                <div class='pos'>
                                    Fastest:&nbsp;<?php echo $most_fastest_data[$i]['FastestLaps']; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        if (count($most_fastest_data) > 1) { ?>
                            <hr>
                        <?php }
                        ?>
                    <?php }
                    ?>
                </aside>

                <?php dynamic_sidebar('HomeS1'); ?>
            </div>
        </div>
    </div>

    <div class="td-pb-row">
        <div class="td-pb-span12">
            <div class="custom-footer">
                <div class="block-title" style="margin-bottom: 0px;">
                    <span>Browse results by season</span>
                </div>
                <div class="td-post-content" style="margin-top: 0px;">
                    <?php
                    $i = 0;
                    foreach ($footer_data as $key => $values) {
                        echo "<div class='seriesgroup'><b>" . $values[0][0] . ': &nbsp;&nbsp;</b>';
                        $string = '';
						echo "";
                        foreach ($values as $value) {
                            $string .= "<div class='footeryear'><a href='" . get_option('home') . "/database/index.php?series=" . $key . "&year=" . $value[1] . "'>" . $value[1] . "</a></div> ";
                        }
                        echo rtrim($string, "-");
                        echo "</div>";
                        $i++;
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>