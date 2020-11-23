<?php require $_SERVER['DOCUMENT_ROOT'] . ('/wp-blog-header.php');

// DB connection 
require('../results/connection.php');
mysqli_set_charset($conn, "utf8");


// Get params
$series = $_GET['series'];
$series = mysqli_real_escape_string($conn, $series);
$year = $_GET['year'];


include('./get_column_header.php');
include('./get_drivers_data.php');
include('./get_touring_data.php');
include('./get_independent_data.php');
include('./get_privateers_data.php');
include('./get_trophy_data.php');
include('./get_amateurs_data.php');
include('./get_production_data.php');
include('./get_classB_data.php');

?>


<!DOCTYPE html>

<html xmlns="https://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
    <title><?php bloginfo('name'); ?> &raquo; Results &raquo; British Touring Car Championship &raquo; <?php echo $year; ?> &raquo; Championship Standings</title>

    <script src="../results/js/Chart.bundle.js"></script>
</head>

<?php get_header(); ?>

<div class="td-container">

    <a href='../../../'><?php bloginfo('name'); ?></a> >> <a href='../../index.php'>Results</a> >> <a href='../index.php'>British Touring Car Championship</a> >> <a href='index.php'><?php echo $year; ?></a> >> Championship Standings

    <!-- First table : Drivers | Touring -->

    <table border="0" width="100%">
        <tr>
            <td width='50%'>
                <?php
                if (count($drivers_data)) { ?>
                    <h2><?php echo $year . ' ' . $driver_classification; ?>' Standings</h2>
                <?php } else { ?>
                    <h2><?php echo $year . ' ' . $touring_classification; ?>' Standings</h2>
                <?php } ?>
            </td>
            <td width="50%" align="right"><?php include('../results/pointsselect.php'); ?></td>
        </tr>
    </table>

    The table below displays race finishing positions. Key: R (Retired), NC (Not classified), EX (Excluded), NS (Did not start).<br />

    <?php
    if (count($drivers_data)) {
        include('./drivers_standing_table.php');
    } else {
        include('./touring_standing_table.php');
    }
    ?>


    <!-- Second table : Independent | Privateers | Trophy | Amateurs -->

    <?php
    if (count($independent_data)) { ?>
        <h2><?php echo $year . ' ' . $independent_classification; ?>'s Standings</h2>
    <?php include('./independent_standing_table.php');
    } else if (count($privateers_data)) { ?>
        <h2><?php echo $year . ' ' . $privateers_classification; ?>' Standings</h2>
    <?php include('./privateers_standing_table.php');
    } else if (count($trophy_data)) { ?>
        <h2><?php echo $year . ' ' . $trophy_classification; ?>' Standings</h2>
    <?php include('./trophy_standing_table.php');
    } else if (count($amateurs_data)) { ?>
        <h2><?php echo $year . ' ' . $amateurs_classification; ?>' Standings</h2>
    <?php include('./amateurs_standing_table.php');
    } ?>


    <!-- Third table : Production | Class B -->

    <?php
    if (count($production_data)) { ?>
        <h2><?php echo $year . ' ' . $production_classification; ?>'s Standings</h2>
    <?php include('./production_standing_table.php');
    } else if (count($classB_data)) { ?>
        <h2><?php echo $year . ' ' . $classB_classification; ?> Standings</h2>
    <?php include('./classB_standing_table.php');
    }  ?>


</div>

<?php get_footer(); ?>