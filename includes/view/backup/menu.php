<?php

use Poshtiban\Main;

?>
<div class="wrap">
    <h1><?php printf( __( '%s Backup methods', Main::$text_domain ), Main::$name ) ?></h1>
    <h2 class="nav-tab-wrapper" style="margin-bottom: 10px">
		<?php foreach ( $methods as $key => $method ): ?>
            <a href="<?php echo admin_url( sprintf( 'admin.php?page=%s&tab=%s', Main::$slug, $key ) ) ?>"
               class="nav-tab <?php echo $active_tab == $key ? 'nav-tab-active' : ''; ?>"><?php echo $method ?></a>
		<?php endforeach; ?>
    </h2>
	<?php
	foreach ( $methods as $index => $method ) {
		do_action( sprintf( '%s_backup_menu_tab', Main::$slug ), $active_tab );
		do_action( sprintf( '%s_backup_menu_tab_%s', Main::$slug, $index ), $active_tab );
	}
	?>
</div>
