<input type="text" value="<?php echo $cloud_id; ?>" readonly>
<a href="#" class="<?php printf('%s_copy_file_id %s_tooltip button', $slug, $slug) ?>" data-tooltip="<?php _e('Copy file ID', $text_domain); ?>">
	<span class="dashicons dashicons-admin-page"></span>
</a>
<a href="<?php echo $cloud_urls['full'] ?>" target="_blank" class="<?php printf('%s_tooltip  button', $slug) ?>" data-tooltip="<?php _e('View file', $text_domain); ?>">
	<span class="dashicons dashicons-visibility"></span>
</a>
<a
    href="<?php echo $cloud_urls['full'] ?>"
    class="<?php printf('%s_restore_media %s_tooltip  button', $slug, $slug) ?>"
    data-tooltip="<?php printf(__('Restore from %s to your Wordpress', $text_domain), $name); ?>"
    data-attachment-id="<?php echo $attachment_id; ?>"
>
    <span class="spinner"></span>
    <span class="dashicons dashicons-image-rotate"></span>
</a>

<?php if( $bulk_export_result ): ?>
    <p class="description"><?php echo $bulk_export_result['message'] ?></p>
<?php endif; ?>
<?php if( $bulk_import_result ): ?>
    <p class="description"><?php echo $bulk_import_result['message'] ?></p>
<?php endif; ?>