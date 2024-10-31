<?php

namespace Poshtiban\Options\Sections\Woocommerce;

use Poshtiban\Main as MainClass;
use Poshtiban\Options\Sections\SectionsAbstract;

class Main extends SectionsAbstract {
	public function get_fields() {
		return [
			[
				'path'  => 'Options/Fields/Woocommerce/Main/MirrorType.php',
				'class' => '\Poshtiban\Options\Fields\Woocommerce\Main\MirrorType'
			],
			[
				'path'  => 'Options/Fields/Woocommerce/Main/UploadPath.php',
				'class' => '\Poshtiban\Options\Fields\Woocommerce\Main\UploadPath'
			],
			[
				'path'  => 'Options/Fields/Woocommerce/Main/UploadPathId.php',
				'class' => '\Poshtiban\Options\Fields\Woocommerce\Main\UploadPathId'
			],
		];
	}
}

new Main( 'woocommerce-main', __( 'General', MainClass::$text_domain ), 'woocommerce' );