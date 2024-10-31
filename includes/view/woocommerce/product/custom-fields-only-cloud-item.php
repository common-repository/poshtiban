<tr class="mirror_row">
    <td class="sort"></td>
    <td style="width: 17px;">
        <label for="manually_insert" data-tooltip="<?php _e( 'Insert file id manually', $text_domain ); ?>"
               class="<?php printf( '%s_tooltip', $slug ) ?>">
            <input id="manually_insert" type="checkbox" value="yes">
        </label>
    </td>
    <td class="file_id">
        <input type="text" class="input_text"
               placeholder="<?php _e( 'File ID', $text_domain ); ?>"
               name="<?php printf( '_%s_downloadable_files_ids[]', $slug ) ?>" value="<?php echo $file_id ?>"
               disabled readonly>
    </td>
    <td class="file_name">
        <input type="text" class="input_text"
               placeholder="<?php _e( 'File name', $text_domain ); ?>"
               name="<?php printf( '_%s_downloadable_files_names[]', $slug ) ?>"
               value="<?php echo $file['name'] ?>" disabled readonly>
    </td>
    <td class="file_path">
        <input type="text" class="input_text" placeholder="" name=""
               value="<?php echo $file['path'] ?>" disabled readonly>
    </td>
    <td class="file_url_choose" width="1%"><a
                href="<?php echo esc_url( $image_library_url ); ?>"
                title="<?php printf( __( 'Select file from %s', $text_domain ), $name ); ?>"
                class="<?php printf( 'button thickbox %s_file_selector', $slug ) ?>"
                onclick="cloudAddClass(this)" data-choose="Choose file"
                data-update="Insert file URL"><?php _e( 'Choose file', $text_domain ); ?></a>
    </td>
    <td width="1%">
        <a href="#" class="<?php printf( '%s_get_download_link no_padding button %s_tooltip', $slug, $slug ) ?>"
           data-file-id="<?php echo $file_id ?>"
           data-tooltip="<?php _e( 'Get mirror download link', $text_domain ); ?>"
           data-id="<?php echo $post->ID ?>">
            <span class="spinner"></span>
            <span class="dashicons dashicons-download"></span>
        </a>
    </td>
    <td width="1%"><a href="#" class="delete"><?php _e( 'Delete', $text_domain ); ?></a>
    </td>
</tr>