<?php

use Poshtiban\Helper;

?>
<div class="options_group show_if_variation_downloadable <?php printf( '%s_files_group', $slug ) ?>">
    <div class="form-field downloadable_files <?php printf( '%s_files', $slug ) ?>">
        <div class="spinner"></div>
        <label for=""><?php _e( 'Cloud files', $text_domain ); ?></label>
        <table class="widefat <?php printf( '%s_files_table', $slug ) ?>">
            <thead>
            <tr>
                <th class="sort" colspan="2">&nbsp;</th>
                <th><?php _e( 'File ID', $text_domain ); ?> </th>
                <th><?php _e( 'File Name', $text_domain ); ?> </th>
                <th colspan="4"><?php _e( 'File Path', $text_domain ); ?></th>
            </tr>
            </thead>
            <tbody class="ui-sortable">
            <script>
              function cloudAddClass(element) {
                element.classList.add('currentSelector');
              }
            </script>
			<?php if ( empty( $files ) ): ?>
                <tr class="mirror_row">
                    <td class="sort"></td>
                    <td style="width: 17px;">
                        <label for="manually_insert"
                               data-tooltip="<?php _e( 'Insert file id manually', $text_domain ); ?>"
                               class="<?php printf( '%s_tooltip', $slug ) ?>">
                            <input id="manually_insert"
                                   style="width: 16px; min-width: 0;"
                                   type="checkbox"
                                   value="yes">
                        </label>
                    </td>
                    <td class="file_id">
                        <input type="text" class="input_text"
                               placeholder="<?php _e( 'File ID', $text_domain ); ?>"
                               name="<?php printf( '_%s_downloadable_files_ids[]', $slug ) ?>" value="" disabled
                               readonly>
                    </td>
                    <td class="file_name">
                        <input type="text" class="input_text"
                               placeholder="<?php _e( 'File name', $text_domain ); ?>"
                               name="<?php printf( '_%s_downloadable_files_names[]', $slug ) ?>" value="" disabled
                               readonly>
                    </td>
                    <td class="file_path">
                        <input type="text" class="input_text" placeholder="" name=""
                               value="/Patition's home/<?php echo $upload_path; ?>/"
                               disabled readonly>
                    </td>
                    <td class="file_url_choose" width="1%">
                        <a href="<?php echo $image_library_url; ?>"
                           title="<?php printf( __( 'Select file from %s', $text_domain ), \Poshtiban\Main::$name ); ?>"
                           class="button thickbox <?php printf( '%s_file_selector', $slug ) ?>"
                           onclick="cloudAddClass(this)"
                           data-choose="Choose file"
                           data-update="Insert file URL">
							<?php _e( 'Choose file', $text_domain ); ?>
                        </a>
                    </td>
                    <td width="1%">
                        <a href="#"
                           class="<?php echo sprintf( '%s_get_download_link no_padding button %s_tooltip', $slug,
							   $slug ) ?>"
                           data-tooltip="<?php _e( 'Get mirror download link', $text_domain ); ?>"
                           data-id="<?php echo $post->ID ?>"><span class="spinner"></span> <span
                                    class="dashicons dashicons-download"></span></a>
                    </td>
                    <td width="1%"><a href="#" class="delete"><?php _e( 'Delete', $text_domain ); ?></a></td>
                </tr>
			<?php else:
				foreach ( $files as $file_id => $file ) {
					$args = [
						'text_domain'       => $text_domain,
						'file_id'           => $file_id,
						'file'              => $file,
						'image_library_url' => $image_library_url,
						'post'              => $post,
					];
					Helper::view( 'product/variation/custom-fields-only-cloud-item', 'woocommerce', $args );
				}
			endif;
			?>

            </tbody>
            <tfoot>
            <tr>
                <th colspan="7">
                    <a href="#" class="button insert" data-row="<tr class=&quot;mirror_row&quot;>

                                                                    <td class=&quot;sort&quot;></td>
                                                                    <td style=&quot;width: 17px;&quot;>
                                <label for=&quot;manually_insert&quot; data-tooltip=&quot;<?php _e( 'Insert file id manually',
						$text_domain ); ?>&quot; class=&quot;<?php printf( '%s_tooltip',
						$slug ) ?>&quot;><input id=&quot;manually_insert&quot; style=&quot;width: 16px; min-width: 0;&quot; type=&quot;checkbox&quot; value=&quot;yes&quot;></label>
                            </td>
<td class=&quot;file_id&quot;>
    <input type=&quot;text&quot; class=&quot;input_text&quot; placeholder=&quot;<?php _e( 'File ID',
						$text_domain ); ?>&quot; name=&quot;<?php printf( '_%s_downloadable_files_ids[]',
						$slug ) ?>&quot; value=&quot;&quot; disabled readonly>
</td>
<td class=&quot;file_name&quot;>
    <input type=&quot;text&quot; class=&quot;input_text&quot; placeholder=&quot;<?php _e( 'File name',
						$text_domain ); ?>&quot; name=&quot;<?php printf( '_%s_downloadable_files_names[]',
						$slug ) ?>&quot; value=&quot;&quot; disabled readonly>
</td>
<td class=&quot;file_path&quot;>
    <input type=&quot;text&quot; class=&quot;input_text&quot; placeholder=&quot;&quot; value=&quot;/Patition's home/<?php echo $upload_path; ?>/&quot;
           disabled readonly>
</td>
<td class=&quot;file_url_choose&quot; width=&quot;1%&quot;><a href=&quot;<?php echo esc_url( $image_library_url ); ?>&quot; title=&quot;<?php printf( __( 'Select file from %s',
						$text_domain ),
						\Poshtiban\Main::$name ); ?>&quot; class=&quot;button thickbox <?php printf( '%s_file_selector',
						$slug ) ?>&quot; onclick=&quot;cloudAddClass(this)&quot; data-choose=&quot;Choose file&quot;
                                          data-update=&quot;Insert file URL&quot;><?php _e( 'Choose file',
						$text_domain ); ?></a>
</td>
<td width=&quot;1%&quot;>
    <a href=&quot;#&quot; class=&quot;<?php echo sprintf( '%s_get_download_link no_padding button %s_tooltip', $slug,
						$slug ) ?>&quot;
       data-tooltip=&quot;<?php _e( 'Get mirror download link',
						$text_domain ); ?>&quot; data-id=&quot;<?php echo $post->ID ?>&quot;><span
            class=&quot;spinner&quot;></span> <span class=&quot;dashicons dashicons-download&quot;></span></a>
</td>
<td width=&quot;1%&quot;><a href=&quot;#&quot; class=&quot;delete&quot;><?php _e( 'Delete', $text_domain ); ?></a></td>
                                                                </tr>
                                "><?php _e( 'Add File', $text_domain ); ?></a>

                    <a href="#" type="button" class="button <?php printf( 'save_%s_files', $slug ) ?> button-primary"
                       data-id="<?php echo $post->ID ?>"><?php _e( 'Save changes', $text_domain ); ?></a>
                    <span class="result"></span>
                </th>
            </tr>
            </tfoot>
        </table>
    </div><!--form-field downloadable_files-->
</div><!--options_group-->
