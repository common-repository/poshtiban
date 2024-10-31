<a href="<?php echo $attachment_url ?>" data-attachment_id="<?php echo $attachment_id ?>" class="<?php printf('button-secondary %s_upload_to_cloud', $slug) ?>">
    <span class="spinner"></span>
    <?php printf(__('Upload to %s', $text_domain), $name); ?>
</a>
<?php if( $bulk_export_result ): ?>
    <p class="description"><?php echo $bulk_export_result['message'] ?></p>
<?php endif; ?>
<?php if( $bulk_import_result ): ?>
    <p class="description"><?php echo $bulk_import_result['message'] ?></p>
<?php endif; ?>