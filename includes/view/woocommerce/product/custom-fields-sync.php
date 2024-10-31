<?php

use Poshtiban\Helper;
use Poshtiban\Main;

Helper::view( sprintf('product/custom-fields-%s-checkbox', $type), 'woocommerce', [
	'is_downloadable' => $is_downloadable,
	'text_domain' => Main::$text_domain,
	'downloads' => $downloads,
	'post' => $post,
	'sync_files_checked' => $sync_files_checked,
	'id' => sprintf('_%s_sync_files', Main::$slug),
]);

if( $is_downloadable && isset( $downloads ) && ! empty( $downloads ) ) {
	Helper::view( sprintf('product/custom-fields-%s-files-list', $type), 'woocommerce', [
		'is_downloadable' => $is_downloadable,
		'text_domain' => Main::$text_domain,
		'downloads' => $downloads,
		'sync_files_checked' => $sync_files_checked,
		'mirror_downloads' => $mirror_downloads,
		'post' => $post,
	]);
}
?>
