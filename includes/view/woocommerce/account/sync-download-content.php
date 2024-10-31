<a
        href="#"
        class="<?php printf( '%s_get_download_link button', $slug ) ?>"
        data-file-id="<?php echo $file_id ?>"
        data-id="<?php echo $download['product_id'] ?>"
        data-order-id="<?php echo $download['order_id'] ?>"
>
    <span class="spinner"></span>
    <span class="dashicons dashicons-download"></span>
    <?php echo $link_text; ?>
</a>