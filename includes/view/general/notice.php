<div class="notice notice-<?php echo $type; echo $is_dismissible ? $is_dismissible : ''; ?> ">
	<p>
		<?php echo $text ?>
	<?php if( $link ): ?>
		<a class="button-primary" href="<?php echo $link['url'] ?>"><?php echo $link['text'] ?></a>
	<?php endif; ?>
	</p>
</div>