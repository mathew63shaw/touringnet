<div class="pointstable">
    <?php
    for ($i = 0; $i < count($top_ten_drivers); $i++) { ?>
        <div class="table-row">
            <div class='pos'><?php echo $top_ten_drivers[$i]['rank']; ?></div>
            <div class='driver'><img src="<?php $_SERVER['DOCUMENT_ROOT']; ?>/results/flag/<?php echo $top_ten_drivers[$i]['image']; ?>.gif" title="<?php echo $top_ten_drivers[$i]['country']; ?>">&nbsp;<?php echo $top_ten_drivers[$i]['driver']; ?></div>
            <?php if ($i == 0) { ?>
                <div class='car'><img src='<?php $_SERVER['DOCUMENT_ROOT']; ?>/<?php echo $top_ten_drivers[$i]['profile']; ?>' /></div>
            <?php } ?>
            <div class='pts'><?php echo $top_ten_drivers[$i]['points']; ?></div>
        </div>
    <?php }
    ?>

</div>
<div class="pointstable">
    <div class="table-row">
        <div class='standings-topten'><b><a href='<?php echo get_option('home'); ?>/database/standings.php?series=<?php echo $series; ?>&year=<?php echo $year; ?>'>Championship Standings</a></b></div>
    </div>
</div>