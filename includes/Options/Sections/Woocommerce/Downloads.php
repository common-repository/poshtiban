<?php

namespace Poshtiban\Options\Sections\Woocommerce;

use Poshtiban\Main;
use Poshtiban\Options\Sections\SectionsAbstract;

class Downloads extends SectionsAbstract {
	public function get_fields() {
		return [
			[
				'path'  => 'Options/Fields/Woocommerce/Main/ActiveTime.php',
				'class' => '\Poshtiban\Options\Fields\Woocommerce\Main\ActiveTime'
			],
			[
				'path'  => 'Options/Fields/Woocommerce/Main/DownloadLinkTitle.php',
				'class' => '\Poshtiban\Options\Fields\Woocommerce\Main\DownloadLinkTitle'
			],
			[
				'path'  => 'Options/Fields/Woocommerce/Main/ForceDownload.php',
				'class' => '\Poshtiban\Options\Fields\Woocommerce\Main\ForceDownload'
			],
		];
	}
}

new Downloads(
	'woocommerce-downloads',
	__( 'Downloads', Main::$text_domain ),
	'woocommerce'
);