<div class="options_group show_if_downloadable">
    <p class="form-field show_if_downloadable">
        <label for="<?php echo $id; ?>"><?php _e( 'Sync downloadable files', $text_domain ); ?></label>
        <input type="checkbox" class="checkbox" style="" name="<?php echo $id; ?>"
               id="<?php echo $id; ?>" value="yes" <?php echo $sync_files_checked ?>>
        <span class="description"><?php printf( __( 'Sync downloadable files to %s', $text_domain ), $name ); ?></span>
    </p>
</div>