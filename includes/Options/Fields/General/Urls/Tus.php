<?php


namespace Poshtiban\Options\Fields\General\Urls;

use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Options\Fields\FieldsAbstract;
use Poshtiban\Options\Settings\General;

class Tus extends FieldsAbstract {
	public $default_value = NULL;

	public function set_id() {
		$this->id = 'tus_url';
	}

	public function set_title() {
		$this->title = __('TUS', Main::$text_domain);
	}

	public function set_setting_id() {
		$this->setting_id = General::get_options_name();
	}

	public function set_default_value() {
		$this->default_value = sprintf('https://upload.%s/files', Main::$api_domain);
	}

	public function render() {
		printf( '<input class="regular-text" type="url" name="%s[%s]" id="%s" value="%s"><p class="description">%s<code>%s</code></p>',
			$this->setting_id,
			$this->id,
			sprintf('%s-%s', Main::$slug, $this->id),
			Helper::get_option($this->id, $this->setting_id, $this->default_value),
			__('Default Value:', Main::$text_domain),
			sprintf('https://upload.%s/files', Main::$api_domain)
		);
	}
}