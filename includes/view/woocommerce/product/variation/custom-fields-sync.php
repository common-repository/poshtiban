<?php

use Poshtiban\Helper;
use Poshtiban\Main;
Helper::view( sprintf('product/variation/custom-fields-%s-checkbox', $type), 'woocommerce', [
	'is_downloadable' => $is_downloadable,
	'text_domain' => Main::$text_domain,
	'downloads' => $downloads,
	'variation' => $post,
	'sync_files_checked' => $sync_files_checked,
	'id' => sprintf('_%s_sync_files_variation_%s', Main::$slug, $post->ID),
	'input_name' => sprintf('_%s_sync_files[%s]', Main::$slug, $post->ID),
]);

if( $is_downloadable && isset( $downloads ) && ! empty( $downloads ) ) {
	Helper::view( sprintf('product/variation/custom-fields-%s-files-list', $type), 'woocommerce', [
		'is_downloadable' => $is_downloadable,
		'text_domain' => Main::$text_domain,
		'downloads' => $downloads,
		'sync_files_checked' => $sync_files_checked,
		'mirror_downloads' => $mirror_downloads,
		'variation' => $post,
	]);
}
?>
