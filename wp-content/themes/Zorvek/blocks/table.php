<?php
$table = get_sub_field('table');

if (!empty($table) && !empty($table['body'])) {
    $LONG_THRESHOLD = 100; // tweak as needed

    // Determine number of columns (prefer header count if present)
    $col_count = !empty($table['header']) ? count($table['header']) : 3;

    // Scan body to find which column indexes are "long"
    $long_cols = [];
    foreach ($table['body'] as $tr) {
        foreach ($tr as $i => $td) {
            $raw = isset($td['c']) ? $td['c'] : '';
            $len = strlen(strip_tags($raw));
            if ($len > $LONG_THRESHOLD) {
                $long_cols[$i] = true; // mark this column as long
            }
        }
    }
?>
    <div id="grid" class="block">
        <div class="container gutter">
            <?php echo '<div class="grid" style="--cols:' . (int) $col_count . '">'; ?>

            <?php if (!empty($table['caption'])): ?>
                <div class="caption"><?php echo esc_html(wp_strip_all_tags($table['caption'])); ?></div>
            <?php endif; ?>

            <?php if (!empty($table['header'])): ?>
                <div class="header">
                    <div class="row">
                        <?php foreach ($table['header'] as $i => $th): ?>
                            <?php
                            $h = isset($th['c']) ? wp_strip_all_tags($th['c']) : '';
                            $is_long_class = isset($long_cols[$i]) ? ' is-long' : '';
                            ?>
                            <div class="cell<?php echo $is_long_class; ?>">
                                <?php echo esc_html($h); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="body">
                <?php foreach ($table['body'] as $tr): ?>
                    <div class="row">
                        <?php foreach ($tr as $i => $td): ?>
                            <?php
                            $c = isset($td['c']) ? $td['c'] : '';
                            $is_long_class = isset($long_cols[$i]) ? ' is-long' : '';
                            ?>
                            <div class="cell<?php echo $is_long_class; ?>">
                                <?php echo wp_kses_post($c); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php echo '</div>'; // .grid 
            ?>
        </div>
    </div>
<?php
}
