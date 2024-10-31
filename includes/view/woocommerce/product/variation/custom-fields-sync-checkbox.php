<div class="options_group show_if_variation_downloadable">
    <p class="form-field form-row form-row-first">
        <label for="<?php echo $id; ?>"><?php _e( 'Sync downloadable files', $text_domain ); ?></label>
        <input type="checkbox" class="checkbox" style=""
               name="<?php echo $input_name; ?>"
               id="<?php echo $id; ?>"
               value="yes" <?php echo $sync_files_checked ?>>
        <span class="woocommerce-help-tip"
              data-tip="<?php printf( __( 'Sync downloadable files to %s', $text_domain ), \Poshtiban\Main::$name ) ?>"></span>
    </p>
</div>
