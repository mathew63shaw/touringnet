<?php

/**
 * Get Independent data
 */

// Get independent' table body data
$independent_classification = 'Independent';
$independent_data_sql = "SELECT main.*, ra.class_pos result, ra.round, ra.driver_id2, ra.driver_id3, ra.driver_id4
                    FROM (SELECT p.driver_id, p.rank, p.points, p.`class`, d.driver, d.country, d.image FROM points p, drivers d WHERE p.series='" . $series . "' AND p.year=$year AND p.classification='" . $independent_classification . "' AND d.id=p.driver_id) main
                    LEFT JOIN (SELECT r.class_pos, r.driver_id, r.round, r.`driver_id2`, r.`driver_id3`, r.`driver_id4` FROM races r WHERE r.year=$year AND r.series='" . $series . "' ) ra
                    ON main.driver_id = ra.driver_id
                    ORDER BY main.rank + 0, main.driver_id, ra.driver_id, ra.round + 0";
$independent_data_query_result = mysqli_query($conn, $independent_data_sql);

$independent_data = array();
$independent_cl_flag = false; // cl_flag
$shared_independent = array();
$independent_race_result_array = [];
while ($row = mysqli_fetch_assoc($independent_data_query_result)) {
    $independent_data[] = $row;
    $driver_id = $row['driver_id'];

    // Check cl flag
    if ($row['class'] != '-') {
        $independent_cl_flag = true;
    }

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

    // Get independent race result
    // independent_race_result_array(driver_id => [round => [result, cls_name], ...])

    $independent_race_result_array[$driver_id][$row['round']] = [$row['result'], $cls_name];

    // Process shared independent
    // shared_independent(borrower_id => [round => lend_id, ...]) [1 => 430, 1=>382]

    if ($row['driver_id2']) {
        if (array_key_exists($row['driver_id2'], $shared_independent)) {
            if (!array_key_exists($row['round'], $shared_independent[$row['driver_id2']])) { // [1 => 430, 1 => 382], prefer first element
                $shared_independent[$row['driver_id2']][$row['round']] = $driver_id;
            }
        } else {
            $shared_independent[$row['driver_id2']][$row['round']] = $driver_id;
        }
    }
    if ($row['driver_id3']) {
        if (array_key_exists($row['driver_id3'], $shared_independent)) {
            if (!array_key_exists($row['round'], $shared_independent[$row['driver_id3']])) {
                $shared_independent[$row['driver_id3']][$row['round']] = $driver_id;
            }
        } else {
            $shared_independent[$row['driver_id3']][$row['round']] = $driver_id;
        }
    }
    if ($row['driver_id4']) {
        if (array_key_exists($row['driver_id4'], $shared_independent)) {
            if (!array_key_exists($row['round'], $shared_independent[$row['driver_id4']])) {
                $shared_independent[$row['driver_id4']][$row['round']] = $driver_id;
            }
        } else {
            $shared_independent[$row['driver_id4']][$row['round']] = $driver_id;
        }
    }
}
// echo "<pre>";
// var_dump($shared_independent);
// echo "</pre>";
// exit;