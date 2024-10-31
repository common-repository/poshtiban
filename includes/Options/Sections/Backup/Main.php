<?php

namespace Poshtiban\Options\Sections\Backup;

use Poshtiban\Main as MainClass;
use Poshtiban\Options\Sections\SectionsAbstract;

class Main extends SectionsAbstract {
	public function get_fields() {
		return [
			[
				'path'  => 'Options/Fields/Backup/Main/UploadPath.php',
				'class' => '\Poshtiban\Options\Fields\Backup\Main\UploadPath'
			],
			[
				'path'  => 'Options/Fields/Backup/Main/UploadPathId.php',
				'class' => '\Poshtiban\Options\Fields\Backup\Main\UploadPathId'
			],
		];
	}
}

new Main( 'backup-main', __( 'Backup Settings', MainClass::$text_domain ), 'backup' );