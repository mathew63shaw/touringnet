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
                        ORDER BY e.round+0";
$events_circuits_query_result = mysqli_query($conn, $events_circuits_sql);
while ($row = mysqli_fetch_assoc($events_circuits_query_result)) {
    $events_circuits_data[] = $row;
}
// Making rounds
$rounds = 'Rounds ';
$length = count($events_circuits_data);
for ($i = 0; $i < $length - 2; $i++) {
    $rounds .= $events_circuits_data[$i]['round'] . ', ';
}
$rounds .= $events_circuits_data[$length - 2]['round'] . ' and ' . $events_circuits_data[$length - 1]['round'];
// Get date range
$date_range_sql = "SELECT MIN(`date`) AS from_date, MAX(`date`) AS to_date FROM `event` WHERE `year`='" . $year . "' AND `series`='" . $series . "'";
$date_range_query_result = mysqli_query($conn, $date_range_sql);
$date_range = [];
while ($row = mysqli_fetch_assoc($date_range_query_result)) {
    $date_range[] = $row;
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
                            (SELECT races.driver, drivers.image,SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END) AS Wins, COUNT(races.driver) AS Races, ROUND(((SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END)) / COUNT(races.driver))*100,1) AS Percent FROM `drivers` INNER JOIN races ON drivers.id = races.driver_id WHERE `Series` IN ('".$series."') AND YEAR='".$year."' GROUP BY races.driver HAVING Wins > 0)
                            UNION
                            (SELECT races.driver2 AS Drvr2, drivers.image,SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END) AS Wins, (SELECT COUNT(races.driver) FROM `races` WHERE races.driver = Drvr2) + (SELECT COUNT(races.driver2) FROM `races` WHERE races.driver2 = Drvr2), ROUND(((SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END)) / (SELECT COUNT(races.driver2) FROM `races` WHERE races.driver = Drvr2))*100,1) AS Percent FROM `drivers` INNER JOIN races ON drivers.id = races.driver_id2 WHERE `Series` IN ('".$series."') AND YEAR='".$year."' GROUP BY races.driver2 HAVING Wins > 0)
                            ORDER BY 3 DESC) temp
                            WHERE temp.Wins=(SELECT MAX(ff.Wins) FROM (
                            (SELECT races.driver, drivers.image,SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END) AS Wins, COUNT(races.driver) AS Races, ROUND(((SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END)) / COUNT(races.driver))*100,1) AS Percent FROM `drivers` INNER JOIN races ON drivers.id = races.driver_id WHERE `Series` IN ('".$series."') AND YEAR='".$year."' GROUP BY races.driver HAVING Wins > 0)
                            UNION
                            (SELECT races.driver2 AS Drvr2, drivers.image,SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END) AS Wins, (SELECT COUNT(races.driver) FROM `races` WHERE races.driver = Drvr2) + (SELECT COUNT(races.driver2) FROM `races` WHERE races.driver2 = Drvr2), ROUND(((SUM(CASE WHEN races.year='2001' AND races.class_pos='1' AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result='1' AND races.class != 'P' THEN 1 ELSE 0 END)) / (SELECT COUNT(races.driver2) FROM `races` WHERE races.driver = Drvr2))*100,1) AS Percent FROM `drivers` INNER JOIN races ON drivers.id = races.driver_id2 WHERE `Series` IN ('".$series."') AND YEAR='".$year."' GROUP BY races.driver2 HAVING Wins > 0)
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
                    FROM (SELECT races.driver, drivers.image,SUM(CASE WHEN races.year='2001' AND races.class_pos IN ('1', '2', '3') AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result IN ('1', '2', '3') AND races.class != 'P' THEN 1 ELSE 0 END) AS Podiums, COUNT(races.driver) AS Races, ROUND(((SUM(CASE WHEN races.year='2001' AND races.class_pos IN ('1', '2', '3') AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result IN ('1', '2', '3') AND races.class != 'P' THEN 1 ELSE 0 END)) / COUNT(races.driver))*100,1) AS Percent FROM `drivers` INNER JOIN races ON drivers.id = races.driver_id WHERE `Series` IN ('".$series."') AND YEAR ='".$year."' GROUP BY races.driver HAVING Podiums > 0 ORDER BY 3 DESC) temp
                    WHERE temp.Podiums=(SELECT MAX(ff.Podiums) FROM (SELECT races.driver, drivers.image,SUM(CASE WHEN races.year='2001' AND races.class_pos IN ('1', '2', '3') AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result IN ('1', '2', '3') AND races.class != 'P' THEN 1 ELSE 0 END) AS Podiums, COUNT(races.driver) AS Races, ROUND(((SUM(CASE WHEN races.year='2001' AND races.class_pos IN ('1', '2', '3') AND races.class='' THEN 1 ELSE 0 END)+SUM(CASE WHEN races.year!='2001' AND races.result IN ('1', '2', '3') AND races.class != 'P' THEN 1 ELSE 0 END)) / COUNT(races.driver))*100,1) AS Percent FROM `drivers` INNER JOIN races ON drivers.id = races.driver_id WHERE `Series` IN ('".$series."') AND YEAR ='".$year."' GROUP BY races.driver HAVING Podiums > 0 ORDER BY 3 DESC) ff)";
$most_podiums_query_result = mysqli_query($conn, $most_podiums_sql);
while ($row = mysqli_fetch_assoc($most_podiums_query_result)) {
    $most_podiums_data[] = $row;
}

/**
 * Get driver with most pole positions
 */
$most_pole_data = [];
$most_pole_sql = "SELECT *
                FROM (SELECT q.`driver`, d.image, SUM(CASE WHEN q.result='1' THEN 1 ELSE 0 END) AS Poles FROM `qualifying` q, drivers d WHERE q.series IN ('".$series."') AND q.`year` = '".$year."' AND q.topq='Y' AND d.id=q.driver_id GROUP BY q.driver HAVING poles > 0 ORDER BY 2 DESC) temp
                WHERE temp.Poles=(SELECT MAX(ff.Poles) FROM (SELECT q.`driver`, d.image, SUM(CASE WHEN q.result='1' THEN 1 ELSE 0 END) AS Poles FROM `qualifying` q, drivers d WHERE q.series IN ('".$series."') AND q.`year` = '".$year."' AND q.topq='Y' AND d.id=q.driver_id GROUP BY q.driver HAVING poles > 0 ORDER BY 2 DESC) ff)";
$most_pole_query_result = mysqli_query($conn, $most_pole_sql);
while ($row = mysqli_fetch_assoc($most_pole_query_result)) {
    $most_pole_data[] = $row;
}

/**
 * Get driver with most fastest laps
 */
$most_fastest_data = [];
$most_fastest_sql = "SELECT *
                    FROM (SELECT r.driver, d.image, SUM(CASE WHEN r.fl='Y' THEN 1 ELSE 0 END) AS FastestLaps FROM `races` r, drivers d WHERE r.series IN ('".$series."') AND r.year = '".$year."' AND r.driver_id=d.id GROUP BY r.driver HAVING FastestLaps > 0 ORDER BY 2 DESC) temp
                    WHERE temp.FastestLaps=(SELECT MAX(ff.FastestLaps) FROM (SELECT r.driver, d.image, SUM(CASE WHEN r.fl='Y' THEN 1 ELSE 0 END) AS FastestLaps FROM `races` r, drivers d WHERE r.series IN ('".$series."') AND r.year = '".$year."' AND r.driver_id=d.id GROUP BY r.driver HAVING FastestLaps > 0 ORDER BY 2 DESC) ff)";
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
    // $temp_title = $row['series'];
    $footer_data[$row['series']][] = [$row['title'], $row['year']];
}

// echo count($footer_data);exit;
// echo "<pre>";
// var_dump($footer_data);
// echo "</pre>";
// exit;



?>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
    <title><?php bloginfo('name'); ?> &raquo; Results &raquo; British Touring Car Championship &raquo; 2020 &raquo; Results</title>
</head>
<style>
    .custom-card {
        border: solid 2px #f1545a;
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 10px;
    }

    .custom-footer {
        border: solid 1px grey;
        margin: 10px;
        padding: 10px;
    }
</style>

<?php get_header(); ?>

<div class="td-container">
    <div class="td-pb-row">
        <div class="td-pb-span12">
            <div class="td-post-header td-pb-padding-side">
                <ul class="td-category">
                    <li class='entry-category'><a href='/'><?php bloginfo('name'); ?></a></li>
                    <li class='entry-category'><a href='/results/index.php'>Results</a></li>
                    <li class='entry-category'><a href='/results/btcc/index.php'><?php echo $series_title; ?></a></li>
                    <li class='entry-category'><a href='index.php'><?php echo $year; ?></a></li>
                </ul>

                <header>
                    <h1 class='entry-title'><?php echo $year . ' ' . $series_title; ?> Results</h1>
                </header>
            </div>
        </div>
    </div>

    <div class="td-pb-row">
        <div class="td-pb-span8 td-main-content">
            <div class="td-ss-main-content">
                <div class="td-post-content">

                    <?php
                    $i = 0;
                    while ($i < $length) {
                        $event_id = $events_circuits_data[$i]['event_id']; ?>
                        <div class="td-pb-span6" style="padding-left: 10px; padding-right: 10px">
                            <div class="custom-card">
                                <img src="<?php $_SERVER['DOCUMENT_ROOT']; ?><?php echo $events_circuits_data[$i]['graphic_path']; ?>" title="<?php $events_circuits_data[$i]['circuit']; ?>" style="width: auto; height: auto;" />
                                <p>
                                    <b><?php echo $rounds; ?></b>
                                    <br /><br />

                                    <em><?php echo $date_range[0]['from_date']; ?> - <?php echo $date_range[0]['to_date']; ?></em>
                                    <br /><br />

                                    <a href='qual<?php echo $events_circuits_data[$i]['qual_id']; ?>.php'>
                                        Qualifying
                                    </a>
                                    <br />

                                    <ul>
                                        <?php
                                        while ($event_id == $events_circuits_data[$i]['event_id']) { ?>
                                            <li>
                                                <a href='rd<?php echo $events_circuits_data[$i]['race_id']; ?>.php'>
                                                    Round <?php echo $events_circuits_data[$i]['round']; ?>
                                                </a>
                                            </li>
                                            <!-- <br /> -->
                                        <?php $i++;
                                        }
                                        ?>
                                    </ul>

                                </p>
                            </div>
                        </div>
                    <?php }
                    ?>

                </div>
            </div>
        </div>

        <div class="td-pb-span4 td-main-sidebar td-pb-border-top" role="complementary">
            <div class="td-ss-main-sidebar">
                <aside class="widget widget_meta">
                    <div class="block-title">
                        <span>Top 10 Drivers</span>
                    </div>

                    <ul>
                        <li>
                            <?php include $_SERVER['DOCUMENT_ROOT'] . '/results/2020/modules/pointsbtcc.php'; ?>
                        </li>
                    </ul>
                </aside>

                <?php dynamic_sidebar('HomeS1'); ?>
            </div>

            <div class="td-ss-main-sidebar">
                <aside class="widget widget_meta">
                    <div class="block-title">
                        <span>Driver with most wins</span>
                    </div>

                    <?php
                    for ($i = 0; $i < count($most_wins_drivers); $i++) { ?>
                        <div class="table-row" style="margin-bottom: 5px;">
                            <div class='pos'><b>Wins :</b> &nbsp;<?php echo $most_wins_drivers[$i]['Wins']; ?></div>
                            <div class='driver'><img src="<?php $_SERVER['DOCUMENT_ROOT']; ?>/results/flag/<?php echo $most_wins_drivers[$i]['image']; ?>.gif">&nbsp;<b><?php echo $most_wins_drivers[$i]['driver']; ?></b></div>
                            <div class='pts'><b>Percent :</b> &nbsp;<?php echo $most_wins_drivers[$i]['Percent']; ?></div>
                        </div>
                    <?php }
                    ?>
                </aside>

                <?php dynamic_sidebar('HomeS1'); ?>
            </div>

            <div class="td-ss-main-sidebar">
                <aside class="widget widget_meta">
                    <div class="block-title">
                        <span>Driver with most modiums</span>
                    </div>

                    <?php
                    for ($i = 0; $i < count($most_podiums_data); $i++) { ?>
                        <div class="table-row" style="margin-bottom: 5px;">
                            <div class='pos'><b>Podiums :</b> &nbsp;<?php echo $most_podiums_data[$i]['Podiums']; ?></div>
                            <div class='driver'><img src="<?php $_SERVER['DOCUMENT_ROOT']; ?>/results/flag/<?php echo $most_podiums_data[$i]['image']; ?>.gif">&nbsp;<b><?php echo $most_podiums_data[$i]['driver']; ?></b></div>
                            <div class='pts'><b>Percent :</b> &nbsp;<?php echo $most_podiums_data[$i]['Percent']; ?></div>
                        </div>
                    <?php }
                    ?>
                </aside>

                <?php dynamic_sidebar('HomeS1'); ?>
            </div>

            <div class="td-ss-main-sidebar">
                <aside class="widget widget_meta">
                    <div class="block-title">
                        <span>Driver with most pole positions</span>
                    </div>

                    <?php
                    for ($i = 0; $i < count($most_pole_data); $i++) { ?>
                        <div class="table-row" style="margin-bottom: 5px;">
                            <div class='pos'><b>Pole :</b> &nbsp;<?php echo $most_pole_data[$i]['Poles']; ?></div>
                            <div class='driver'><img src="<?php $_SERVER['DOCUMENT_ROOT']; ?>/results/flag/<?php echo $most_pole_data[$i]['image']; ?>.gif">&nbsp;<b><?php echo $most_pole_data[$i]['driver']; ?></b></div>
                        </div>
                    <?php }
                    ?>
                </aside>

                <?php dynamic_sidebar('HomeS1'); ?>
            </div>

            <div class="td-ss-main-sidebar">
                <aside class="widget widget_meta">
                    <div class="block-title">
                        <span>Driver with most fastest laps</span>
                    </div>

                    <?php
                    for ($i = 0; $i < count($most_fastest_data); $i++) { ?>
                        <div class="table-row" style="margin-bottom: 5px;">
                            <div class='pos'><b>Fastest :</b> &nbsp;<?php echo $most_fastest_data[$i]['FastestLaps']; ?></div>
                            <div class='driver'><img src="<?php $_SERVER['DOCUMENT_ROOT']; ?>/results/flag/<?php echo $most_fastest_data[$i]['image']; ?>.gif">&nbsp;<b><?php echo $most_fastest_data[$i]['driver']; ?></b></div>
                        </div>
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
                <div class="block-title">
                    <span>Browse results by person</span>
                </div>
                <div class="td-post-content">
                    <?php
                    $i = 0;
                    foreach ($footer_data as $key => $values) {
                        echo "<b>" . $values[0][0] . ': &nbsp;&nbsp;</b>';
                        $string = '';
                        foreach ($values as $value) {
                            $string .= "<a href='" . get_option('home') . "/database/standings.php?series=" . $key . "&year=" . $value[1] . "'>" . $value[1] . "</a>-";
                        }
                        echo rtrim($string, "-");
                        echo "<br>";
                        $i++;
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>