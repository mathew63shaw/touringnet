<?php define('WP_USE_THEMES', false);
require($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php'); ?>

<?php require('../connection.php');
mysqli_set_charset($conn, "utf8");

$id = $_GET['name']; //?? 'Luca ENGSTLER'
//$id = mysqli_real_escape_string($conn, $id);
$id2 = $id;
$id2 = stripslashes($id);

$sid = $_GET['series'] ??  '';
$sid = mysqli_real_escape_string($conn, $sid);

$sql = "select circuits.abbreviation, circuits.code, circuits.layout, series, year, concat(year,series) as yrsrs, concat(year,series,round) as yrsrsrd, round, track, coalesce(max(case when driver = '" . $id . "' then qual end), \"-\") as gd, max(case when driver = '" . $id . "' then entrant end) as entrant, max(case when driver = '" . $id . "' then car end) as car, coalesce(max(case when driver = '" . $id . "' then result end), \"-\") as res, race_id from races natural join (select distinct year, series from races where driver = '" . $id . "') x INNER JOIN circuits on races.track = circuits.configuration group by yrsrsrd order by yrsrs, series, cast(round as unsigned)";

$sql2 = "select date_format(min(date),\"%D %b %Y\") as mindate, date_format(max(date),\"%D %b %Y\") as maxdate from races WHERE `Series` = '" . $sid . "' and Result = '1'";

$sql3 = "select distinct concat(year,series) as yrseries from races where driver = '" . $id . "' ";

$result = mysqli_query($conn, $sql);

// echo("Error description: " . mysqli_error($conn));

$result2 = mysqli_query($conn, $sql2);
$result3 = mysqli_query($conn, $sql3);
// var_dump($result);
// var_dump($result2);
// var_dump($result3);



?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head profile="http://gmpg.org/xfn/11">
    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
    <title><?php bloginfo('name'); ?> &raquo; Database &raquo; <?php echo ucwords(strtolower($id)); ?> Race Wins</title>

    <style type="text/css">
        .h4h {
            margin-left: 10px;
        }

        .stats-table {
            width: 96%;
            padding: 3px;
            margin: 5px;
            margin-left: 10px;
        }

        .stats-table img {
            display: inline;
        }

        .stats-table thead {
            background-color: #e5e5e5;
        }

        .stats-table tbody tr {
            border-bottom: 1px dashed #000000;
        }

        .stats-table tbody tr:hover {
            background-color: #d1d5ff;
        }

        .stats-table tbody tr:nth-child(even) {
            background-color: #f7f7f7;
        }

        .stats-table tbody tr:nth-child(even):hover {
            background-color: #d1d5ff;
        }

        tbody tr {
            counter-increment: rownum;
        }

        tbody {
            counter-reset: rownum;
        }

        table#sample1 td:first-child:before {
            content: counter(rownum) " ";
        }

        table#nat-table td.rownums:before {
            content: counter(rownum);
        }

        .rank {
            width: 10%;
        }

        .identifier {
            width: 59%;
        }

        .stat {
            width: 9%;
        }

        .tot {
            width: 11%;
        }

        .percent {
            width: 11%;
        }

        .yrsrs {
            float: left;
            padding: 1px;
            margin-left: 5px;
            border-right: 1px solid #E6E6E6;
            text-align: center;
            width: 99.4%;
        }

        .yrsrs:nth-child(odd) {
            background-color: #F7F7F7;
        }

        .cirrdresgd {
            float: left;
            padding: 1.5px;
            border: 0px dashed #FF00F6;
        }

        .cirrdresgd:last-child {
            padding-right: 2px;
            border-right: 1px dashed #E5E5E5;
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
            margin: 50px;
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

        .tt-suggestion.tt-is-under-cursor {
            background-color: #0097CF;
            color: #FFFFFF;
        }

        .tt-suggestion p {
            margin: 0;
        }

        < !-- Test code below to switch sidebar order for mobile devices. Create a database-specific sidebar and show it first on phones,
        or at the side on PCs etc. Switch test on Engstler for now --><?php if ($id2 == "Franz ENGSTLER") {
                                                                            echo "@media (max-width: 767px) {.td-pb-row{display: flex;flex-direction: column-reverse;}}";
                                                                        } else {
                                                                            echo "";
                                                                        } ?><?php if ($id == "Franz ENGSTLER") {
                                                                                                                                                                                                                                    echo "@media (max-width: 767px) {.td-pb-row{display: flex;flex-direction: column-reverse;}}";
                                                                                                                                                                                                                                } else {
                                                                                                                                                                                                                                    echo "";
                                                                                                                                                                                                                                } ?>
    </style>

    <script src="/results/tablesorter/js/jquery-latest.min.js"></script>
    <script src="typeahead.min.js"></script>
    <script>
        $(document).ready(function() {
            $('input.typeahead').typeahead({
                name: 'typeahead',
                remote: 'search.php?key=%QUERY',
                limit: 10
            });
        });
    </script>

</head>

<?php get_header(); ?>


<body>
    <div class="td-container-wrap" style="padding: 20px;">

        <div class="td-pb-row">

            <div class="td-pb-span8">

                <div class="td-ss-main-content">

                    <div class="clearfix"></div>

                    <a href='../../'><?php bloginfo('name'); ?></a> &raquo; Statistics &raquo; <?php echo ucwords(strtolower($id2)); ?> <?php echo ucwords($sid); ?> race results

                    <div class="td_block_wrap tdb_title tdi_78_07e tdb-single-title td-pb-border-top td_block_template_1">
                        <div class="tdb-block-inner td-fix-index">
                            <h1 class='tdb-title-text' style="font-family: Oxygen; font-size: 32px; font-weight: 800;"><?php echo ucwords(strtolower($id2)); ?> <?php echo ucwords($sid); ?> race results</h1>
                        </div>
                    </div>


                    &nbsp;&nbsp;<em>Note: Data valid for period between <?php if (mysqli_num_rows($result2) > 0) {
                                                                            while ($row = mysqli_fetch_assoc($result2)) {
                                                                                echo $row["mindate"] . " and " . $row["maxdate"];
                                                                            }
                                                                        } else {
                                                                            echo "0 results";
                                                                        }    ?></em>

                    <!-- FREELANCER START HERE -->

                    <?php

                    $default_bg_color = 'background-color: #8EC7D9';
                    $bg_colors = [
                        1 => 'background-color: #FFF559',
                        2 => 'background-color: #D8D8D8',
                        3 => 'background-color: #FFC8A7;',
                        4 => 'background-color: #A7FFAD',
                        5 => 'background-color: #A7FFBC',
                        6 => 'background-color: #A7FFCA',
                        7 => 'background-color: #A7FFD9',
                        8 => 'background-color: #A7FFE8',
                        9 => 'background-color: #A7FFF6',
                        10 => 'background-color: #A7F9FF',
                        11 => 'background-color: #8EC7D9',
                        '-' => 'background-color: #FFFFFF',
                        'R' => 'background-color: #000000; color: #FFFFFF',
                        'NS' => 'background-color: #000000; color: #FFFFFF',
                        'EX' => 'background-color: #000000; color: #FFFFFF',
                        'DQ' => 'background-color: #000000; color: #FFFFFF',
                    ];
                    $data = [];
                    /** 
					The schema of the array which will be used in the following loop
					$entry = [
							'cars' => [],
							'races' => [
								['round' => , 'res' =>, 'gd' => , 'code' => , 'abbreviation'=> , 'yrsrs' => , 'series' => ],
								['round' => , 'res' =>, 'gd' => , 'code' => , 'abbreviation'=> , 'yrsrs' => , 'series' => ],
							]
                     **/
                    $entry = [];

                    // dd($default_bg_color);

                    if (mysqli_num_rows($result) > 0) {

                        while ($row = mysqli_fetch_assoc($result)) {

                            //When first round of next tournament is there, push all the details from the last one if there and then empty the $entry array
                            if ($row['round'] == 1) {
                                if (!empty($entry))
                                    array_push($data, $entry);
                                $entry = array();
                            }

                            // if first time entry then init car
                            if (!isset($entry['cars']))
                                $entry['cars'] = array();

                            if (!isset($entry['year']))
                                $entry['year'] = array();

                            if (!isset($entry['series']))
                                $entry['series'] = array();

                            // if first time entry then init car
                            if (!isset($entry['races']))
                                $entry['races'] = array();

                            // If a unique car in the current tournament then will be added otherwise not
                            if (!in_array($row['car'], $entry['cars']) && $row['car'])
                                array_push($entry['cars'], $row['car']);

                            if (!in_array($row['year'], $entry['year']) && $row['year'])
                                array_push($entry['year'], $row['year']);

                            if (!in_array($row['series'], $entry['series']) && $row['series'])
                                array_push($entry['series'], $row['series']);

                            $race = [
                                'year' => $row['year'],
                                'series' => $row['series'],
                                'res' => $row['res'],
                                'round' => $row['round'],
                                'gd' => $row['gd'],
                                'code' => $row['code'],
                                'abbreviation' => $row['abbreviation'],
                                'layout' => $row['layout'],
                                'yrsrs' => $row['yrsrs'],
                                'raceid' => $row['race_id'],
                            ];

                            array_push($entry['races'], $race);
                        }

                        //Last push because that doesn't get pushed into the $data array due to logic
                        array_push($data, $entry);
                    }

                    // var_dump($data);
                    // die();
                    // var_dump($previousentry);
                    // die();


                    foreach ($data as $entry) {
                        # code...
                        echo "<div class='yrsrs'>";
                        $years = $entry['year'];
                        $cars = $entry['cars'];
                        $races = $entry['races'];
                        $serieses = $entry['series'];

                        print_years($years);
                        print_series($serieses);
                        print_cars($cars);

                        foreach ($races as $race) {

                    ?>

                            <div class='cirrdresgd'>
                                <?php if ($race['series'] == 'ITC') : ?>

                                    <div style='background-color: #E5E5E5;'>
                                        <img src='/results/flag/<?= $race['code'] ?>.gif' style='display: inline;' />
                                    </div>

                                <?php else : ?>
                                    <div style='background-color: #E5E5E5;' onclick='on()'>
                                        <span class="more-info" title="<?= $race['layout']; ?>"><?= $race['abbreviation']; ?></span>
                                    </div>

                                <?php endif; ?>

                                <div style='float: left; padding: 1px; border: 0px dashed #000000;'>
                                    <div style='border: 0px solid #ff0000; min-width: 28px;'><a href='race.php?id=<?= $race['raceid'] ?>'><?= $race['round'] ?></a></div>
                                    <div style='padding: 4px; border: 0px solid #18cb00; <?= print_bg_color($race['res']); ?>'><?= $race['res'] ?></div>
                                    <div style='border: 0px solid #ff9900;'><?= $race['gd'] ?></div>
                                </div>
                            </div>
                        <?php } ?>
                </div>
            <?php }

                    function print_cars($cars)
                    {
                        echo "<p align='left' style='margin-bottom: 0px;'> Cars raced: " . implode(", ", $cars);
                    }
                    function print_years($years)
                    {
                        echo "<div style='float: left; padding-right: 5px;'><b>" . implode(" ", $years) . "</b></div>";
                    }
                    function print_series($serieses)
                    {
                        echo "<p align='left' style='margin-bottom: 0px;'>" . implode(" ", $serieses);
                    }

                    function print_bg_color($res)
                    {
                        global $default_bg_color, $bg_colors;
                        if (isset($bg_colors[$res]))
                            echo $bg_colors[$res];
                        else
                            echo $default_bg_color;
                    }

                    // This is the opening part of the group
                    function print_yrsrsheaders($this_row)
                    {
                        echo "<div style='float: left; padding: 1px; text-align: center; width: 100%;'>\n"; // This is the Year-Series group blue border
                    }

                    // This is the closing part of the group
                    function print_yrsrsfooters($this_row)
                    {
                        echo "</div>";
                    }

                    // This is the car array
                    function print_cararray($this_row)
                    {
                        print_r($uniquecar);
                    }

                    // This is the opening part of the group
                    function print_rdgrpheaders($this_row)
                    {
                        echo "<div style='float: left; padding: 1px;'>\n"; // This is the round group dashed pink border
                    }

                    // This is the closing part of the group
                    function print_rdgrpfooters($this_row)
                    {
                        echo "</div>";
                    }

                    // This is the opening part of the group
                    function print_rdheaders($this_row)
                    {
                        echo "<div style='float: left; padding: 1px;'>\n"; // This is the round group dashed border
                    }

                    // This is the closing part of the group
                    function print_rdfooters($this_row)
                    {
                        echo "</div>";
                    }
            ?>

            <!-- FREELANCER END HERE -->
            <br />

            </div>

        </div>

        <div class="td-pb-span4">

            <div class="td-ss-main-sidebar">

                <div class="clearfix"></div>

                <div class="panel panel-default">
                    <div class="bs-example">
                        <input type="text" name="typeahead" class="typeahead tt-query" autocomplete="off" spellcheck="false" placeholder="Type your Query">
                    </div>
                </div>

                <?php dynamic_sidebar('BTCC'); ?>

                <div class="clearfix"></div>

            </div>

        </div>

    </div>

    </div><!-- End of td-container div -->

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