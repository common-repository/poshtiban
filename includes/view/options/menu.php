<div class="wrap">
    <h1><?php printf( __( '%s Settings', $text_domain ), $name ) ?></h1>
    <h2 class="nav-tab-wrapper" style="margin-bottom: 10px">
		<?php foreach ( $tabs as $group ): ?>
            <a href="<?php echo admin_url( sprintf( 'admin.php?page=%s&tab=%s', $slug, $group['key'] ) ) ?>"
               class="nav-tab <?php echo $active_tab == $group['key'] ? 'nav-tab-active' : ''; ?>"><?php echo $group['title'] ?></a>
		<?php endforeach; ?>
    </h2>

    <!--    TODO: Changer action url    -->
    <form method="post" action="options.php">
		<?php
        if( \Poshtiban\Helper::isDebugMode() ) {
            $options = get_option(sprintf( '%s-%s-settings', $slug, $active_tab ));
            echo '<pre style="direction:ltr; text-align: left;">'; print_r($options); echo '</pre>';
        }
		settings_errors();
		settings_fields( sprintf( '%s-%s-group', $slug, $active_tab ) );
		do_settings_sections( sprintf( '%s-%s-page', $slug, $active_tab ) );
		submit_button();
		?>
    </form>
</div>
