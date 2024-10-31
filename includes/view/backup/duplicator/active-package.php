<tr class="dup-pack-info <?php echo esc_attr($css_alt); ?> <?php echo $is_running_package ? 'is-running' : ''; ?>">
	<td>
		<?php
		echo date_i18n('Y-m-d H:i', $package->Created);
		echo ' '.($pack_build_mode ?
				"<sup title='".__('Archive created as zip file', $text_domain)."'>zip</sup>" :
				"<sup title='".__('Archive created as daf file', $text_domain)."'>daf</sup>");
		?>
	</td>
	<td class="pack-size"><?php echo DUP_Util::byteSize($pack_archive_size); ?></td>
	<td class='pack-name'>
		<?php echo ($pack_dbonly) ? "{$pack_name} <sup title='".esc_attr(__('Database Only', $text_domain))."'>DB</sup>" : esc_html($pack_name); ?><br/>
		<span class="building-info" >
                                <i class="fa fa-cog fa-sm fa-spin"></i> <b>Building Package</b> <span class="perc"><?php echo $pack_perc; ?></span>%
                                &nbsp; <i class="fas fa-question-circle fa-sm" style="color:#2C8021"
                                          data-tooltip-title="<?php esc_attr_e("Package Build Running", $text_domain); ?>"
                                          data-tooltip="<?php esc_attr_e('To stop or reset this package build goto Settings > Advanced > Reset Packages', $text_domain); ?>"></i>
                            </span>
	</td>

	<td class="get-btns">
       <p>
           <a href="<?php echo $packagepath ?>" class="button-primary upload_backup" data-type="package" data-id="<?php echo $package->Hash ?>">
		       <?php printf(__('Upload package to %s', $text_domain),$name); ?>
           </a>
       </p>
        <div class="result" style="font-weight: bold;"></div>
	</td>
</tr>