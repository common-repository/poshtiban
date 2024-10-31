<div class="options_group">
    <div class="form-field downloadable_files">
        <label for=""><?php printf( __( '%s files', $text_domain ), $name ); ?></label>
        <table class="widefat">
            <thead>
            <tr>
                <th class="sort">&nbsp;</th>
                <th><?php _e( 'File ID', $text_domain ); ?> </th>
                <th colspan="3"><?php _e( 'File url', $text_domain ); ?></th>
            </tr>
            </thead>
            <tbody class="ui-sortable">
			<?php foreach ( $downloads as $checksum => $download ):
				$mirror_key = array_search( $download->get_file(), array_column( $mirror_downloads, 'url' ) );
				$mirror_download = ( $mirror_key !== false ) ? $mirror_downloads[ $mirror_key ] : false;
				?>
                <tr class="mirror_row">
                    <td class="sort"></td>
                    <td class="file_id">
                        <input type="text" class="input_text" placeholder=""
                               value="<?php echo ( isset( $mirror_download['id'] ) && ! empty( $mirror_download['id'] ) ) ? $mirror_download['id'] : ''; ?>"
                               disabled>
                    </td>
                    <td class="file_url">
                        <input type="text" class="input_text" value="<?php echo $download->get_file(); ?>" disabled>
                    </td>
                    <td width="10%">
                        <a href="#"
                           class="<?php echo sprintf( '%s_send_to_mirror no_padding button %s_tooltip',$slug, $slug ) ?>"
                           data-tooltip="<?php _e( 'Upload to mirror server', $text_domain ); ?>"
                           data-id="<?php echo $post->ID ?>"><span class="spinner"></span> <span
                                    class="dashicons dashicons-controls-repeat"></span></a>
						<?php if ( isset( $mirror_download['id'] ) && ! empty( $mirror_download['id'] ) ): ?>
                            <a href="#"
                               class="<?php echo sprintf( '%s_get_download_link no_padding button %s_tooltip', $slug, $slug ) ?>"
                               data-file-id="<?php echo $mirror_download['id']; ?>"
                               data-tooltip="<?php _e( 'Get mirror download link', $text_domain ); ?>"
                               data-id="<?php echo $post->ID ?>"><span class="spinner"></span> <span
                                        class="dashicons dashicons-download"></span></a>
						<?php endif; ?>
                    </td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>
    </div><!--form-field downloadable_files-->
</div>
