<div class="table-responsive">

        <table class="pointstable">

            <tr class="resultsubheading">
                <td><b>P.</b></td>
                <?php if ($production_cl_flag) { ?>
                    <td><b>CL</b></td>
                <?php } ?>
                <td width="22%"><b>DRIVER</b></td>
                <td width="8%"><b>PTS</b></td>
                <?php $i = 0;
                while ($i < count($column_header)) { ?>
                    <td align='center' colspan='<?php echo $column_header[$i]['duplicated_cnt']; ?>'><img src="<?php echo $column_header[$i]['flag_path']; ?>" title='<?php echo $column_header[$i]['circuit']; ?>'> <?php echo $column_header[$i]['abbreviation']; ?></td>
                <?php $i += $column_header[$i]['duplicated_cnt'];
                } ?>
            </tr>

            <tr class="alternate">
                <td></td>
                <?php
                if ($production_cl_flag) { ?>
                    <td></td>
                <?php } ?>
                <td></td>
                <td></td>
                <?php
                for ($i = 0; $i < count($column_header); $i++) { ?>
                    <td align='center' style="cursor: pointer;" onclick="window.open('<?php echo $column_header[$i]['rd_link']; ?>')">
                        <?php echo $column_header[$i]['round']; ?>
                    </td>
                <?php } ?>
            </tr>

            <?php
            $i = 0;
            while ($i < count($production_data)) {
                $driver_id = $production_data[$i]['driver_id'];
                $exit_flag = false; ?>

                <tr class='alternate2'>
                    <td align='center'><?php echo $production_data[$i]['rank']; ?></td>
                    <?php
                    if ($production_cl_flag) { ?>
                        <td><?php echo $production_data[$i]['class']; ?></td>
                    <?php } ?>
                    <td align='left'>
                        <img src="../results/flag/<?php echo $production_data[$i]['image']; ?>.gif" title="<?php echo $production_data[$i]['country']; ?>">&nbsp;<?php echo $production_data[$i]['driver']; ?>
                    </td>
                    <td><?php echo $production_data[$i]['points']; ?></td>

                    <?php
                    for ($j = 0; $j < count($column_header); $j++) {
                        $round = $column_header[$j]['round'];
                        // drove shared car in the current round
                        if (array_key_exists($round, $shared_production[$driver_id])) { 
                            $lender_id = $shared_production[$driver_id][$round];
                            $lender_race_result = $production_race_result_array[$lender_id][$round][0];
                            $cls_name = $production_race_result_array[$lender_id][$round][1];
                            $race_result = $production_race_result_array[$driver_id][$round][0];

                            // drove his own car in the current round and lender's car
                            if (array_key_exists($round, $production_race_result_array[$driver_id])) { ?>
                                <td align='center' style="background: orangered;">
                                    <?php echo $race_result . '/' . $lender_race_result; ?>
                                </td>
                            <?php
                                $exit_flag = true;
                                $i++;
                            } else { // drove lender's car 
                            ?>
                                <td align='center' class='<?php echo $cls_name; ?>'>
                                    <?php echo $lender_race_result; ?>
                                </td>
                            <?php }
                        } else if (array_key_exists($round, $production_race_result_array[$driver_id])) { // drove his own car in the current round
                            $race_result = $production_race_result_array[$driver_id][$round][0];
                            $cls_name = $production_race_result_array[$driver_id][$round][1];
                            $exit_flag = true;
                            $i++; ?>
                            <td align='center' class='<?php echo $cls_name; ?>'>
                                <?php echo $race_result; ?>
                            </td>

                        <?php } else { ?>
                            <td align='center'>
                                -
                            </td>
                    <?php }
                    }

                    if (!$exit_flag) { // all race results are -
                        $i++;
                    } ?>
                </tr>
            <?php } ?>


        </table>

    </div>