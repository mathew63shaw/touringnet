<?php
/**
 *  Get table column header
 */

$column_header = array();
$header_column_sql = "SELECT e.event_id, e.circuit, e.round, c.code, c.abbreviation
                    FROM (SELECT * FROM `event` WHERE `year`=$year AND `series`='" . $series . "') e
                    LEFT JOIN circuits c
                    ON e.circuit = c.configuration
                    ORDER BY e.date";
$header_column_query_result = mysqli_query($conn, $header_column_sql);
$temp_column_header = array();
while ($row = mysqli_fetch_assoc($header_column_query_result)) {
    $temp_column_header[] = $row;
}
$i = 0;
while ($i < count($temp_column_header)) { // Processing duplicated circuit field
    $duplicated_cnt = 0;
    $original_i = $i;
    $temp = $temp_column_header[$i]['circuit'];
    while ($temp == $temp_column_header[$i]['circuit']) {
        $duplicated_cnt++;
        $i++;
    }
    for ($j = 0; $j < $duplicated_cnt; $j++) {
        $new_array = array(
            'circuit' => $temp_column_header[$original_i]['circuit'],
            'round' => $temp_column_header[$original_i]['round'],
            'abbreviation' => $temp_column_header[$original_i]['abbreviation'],
            'duplicated_cnt' => $duplicated_cnt,
            'flag_path' => "../results/flag/" . $temp_column_header[$original_i]['code'] . ".gif",
            'rd_link' => 'https://' . $_SERVER['SERVER_NAME'] . "/results/" . $year . "/rd" . $temp_column_header[$original_i]['round'] . ".php"
        );
        $original_i++;
        $column_header[] = $new_array;
    }
}
