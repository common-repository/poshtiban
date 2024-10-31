<tr class="dup-pack-info  <?php echo esc_attr($css_alt); ?>">
	<td><?php echo DUP_Package::getCreatedDateFormat($package->Created, DUP_Settings::get_create_date_format()); ?></td>
	<td class="pack-size"><?php echo DUP_Util::byteSize($pack_archive_size); ?></td>
	<td class='pack-name'><?php echo esc_html($pack_name); ?></td>
	<td class="get-btns error-msg" colspan="3">
                            <span>
                                <i class="fa fa-exclamation-triangle fa-sm"></i>
                                <a href="<?php echo esc_url($error_url); ?>"><?php esc_html_e("Error Processing", 'duplicator') ?></a>
                            </span>
		<a class="button no-select" title="<?php esc_attr_e("Package Details", 'duplicator') ?>" href="<?php echo esc_url($error_url); ?>">
			<i class="fa fa-archive fa-sm"></i>
		</a>
	</td>
</tr>