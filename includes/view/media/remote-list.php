<div class="wrap">
	<h1><?php _e( 'Remote uploads list', $text_domain ) ?></h1>
	<table class="wp-list-table widefat fixed striped cloud_files">
		<thead>
		<tr>
			<th><?php _e( 'File ID', $text_domain ) ?></th>
			<th><?php _e( 'File name', $text_domain ) ?></th>
			<th><?php _e( 'Status', $text_domain ) ?></th>
			<th><?php _e( 'Action', $text_domain ) ?></th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<th><?php _e( 'File ID', $text_domain ) ?></th>
			<th><?php _e( 'File name', $text_domain ) ?></th>
			<th><?php _e( 'Status', $text_domain ) ?></th>
            <th><?php _e( 'Action', $text_domain ) ?></th>
        </tr>
		</tfoot>
		<tbody>
		<?php if( $files ): ?>
			<?php foreach( $files as $file_id => $queue_item ): ?>
				<tr>
					<td><?php echo $file_id ?></td>
					<td><?php echo $queue_item['data']->name ?></td>
					<td><?php echo $queue_item['status'] ?></td>
					<td>
                        <a href="<?php echo $file_id; ?>" class="<?php printf('button-secondary %s_recover_remote_file', $slug) ?>">
                            <?php _e('Resend', $text_domain); ?>
                        </a>
                    </td>
				</tr>
			<?php endforeach; ?>
		<?php else: ?>
			<tr>
				<td colspan="3"><?php _e('There is no file in your remote upload list.', $text_domain); ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
</div>
