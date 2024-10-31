<?php


namespace Poshtiban\Options\Fields\Woocommerce\Main;

use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Options\Fields\FieldsAbstract;
use Poshtiban\Options\Settings\Woocommerce;

class DownloadLinkTitle extends FieldsAbstract {
	public $default_value;

	public function set_id() {
		$this->id = 'download_link_title';
	}

	public function set_title() {
		$this->title = __('Downlaod link title', Main::$text_domain);
	}

	public function set_default_value() {
		$this->default_value = __('Mirror download', Main::$text_domain);
	}

	public function set_setting_id() {
		$this->setting_id = Woocommerce::get_options_name();
	}

	public function render() {
		printf( '<input class="regular-text" type="text" name="%s[%s]" id="%s" value="%s"><p class="description">%s</p>',
			$this->setting_id,
			$this->id,
			sprintf('%s-%s', Main::$slug, $this->id),
			Helper::get_option($this->id, $this->setting_id, $this->default_value),
			__( 'Download link text in frontend', Main::$text_domain )

		);
	}
}