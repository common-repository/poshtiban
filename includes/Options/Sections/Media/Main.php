<?php

namespace Poshtiban\Options\Sections\Media;

use Poshtiban\Main as MainClass;
use Poshtiban\Options\Sections\SectionsAbstract;

class Main extends SectionsAbstract {
	public function get_fields() {
		return [
			[
				'path'  => 'Options/Fields/Media/Main/UploadPath.php',
				'class' => '\Poshtiban\Options\Fields\Media\Main\UploadPath'
			],
			[
				'path'  => 'Options/Fields/Media/Main/UploadPathId.php',
				'class' => '\Poshtiban\Options\Fields\Media\Main\UploadPathId'
			],
			[
				'path'  => 'Options/Fields/Media/Main/Webhook.php',
				'class' => '\Poshtiban\Options\Fields\Media\Main\Webhook'
			],
		];
	}
}

new Main( 'media-main', __( 'Media Settings', MainClass::$text_domain ), 'media' );