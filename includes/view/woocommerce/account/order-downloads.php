<?php if( !empty($downloads) ): ?>
    <section class="woocommerce-order-downloads">
		<?php if ( $show_title ) : ?>
            <h2 class="woocommerce-order-downloads__title"><?php esc_html_e( 'Downloads', 'woocommerce' ); ?></h2>
		<?php endif; ?>

        <table class="woocommerce-table woocommerce-table--order-downloads shop_table shop_table_responsive order_details">
            <thead>
            <tr>
                <th><?php _e('File name', $text_domain); ?></th>
                <th><?php _e('Download', $text_domain); ?></th>
            </tr>
            </thead>
			<?php foreach ( $downloads as $product_id => $download_files ) : ?>
				<?php foreach ( $download_files as $file_id => $download_name ) : ?>
                    <tr>
                        <td><?php echo $download_name; ?></td>
                        <td>
                            <a href="#" class="<?php printf( '%s_get_download_link button', $slug ) ?>" data-file-id="<?php echo $file_id ?>" data-id="<?php echo $product_id ?>">
                                <span class="spinner"></span>
                                <span class="dashicons dashicons-download"></span>
								<?php echo $link_text; ?>
                            </a>
                        </td>
                    </tr>
				<?php endforeach; ?>
			<?php endforeach; ?>
        </table>
    </section>
<?php else: ?>
    <div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
        <a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php esc_html_e( 'Browse products', 'woocommerce' ); ?>
        </a>
		<?php esc_html_e( 'No downloads available yet.', 'woocommerce' ); ?>
    </div>
<?php endif; ?>
