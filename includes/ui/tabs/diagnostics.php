<?php

defined( '\\ABSPATH' ) || exit;

?>
<h2 class="title">
    <?php esc_html_e( 'Diagnostics', 'redis-cache' ); ?>
</h2>

<textarea class="large-text readonly" rows="20" readonly><?php include dirname( __DIR__ ) . '/diagnostics.php'; ?></textarea>
