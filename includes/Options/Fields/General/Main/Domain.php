<?php


namespace Poshtiban\Options\Fields\General\Main;

use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Options\Fields\FieldsAbstract;
use Poshtiban\Options\Settings\General;

class Domain extends FieldsAbstract {
	public $default_value = '';

	public function set_id() {
		$this->id = 'domain';
	}

	public function set_title() {
		$this->title = __('Domain', Main::$text_domain);
	}

	public function set_setting_id() {
		$this->setting_id = General::get_options_name();
	}

	public function render() {
		printf( '<input class="regular-text" type="text" name="%s[%s]" id="%s" value="%s" readonly>',
			$this->setting_id,
			$this->id,
			sprintf('%s-%s', Main::$slug, $this->id),
			Helper::get_option($this->id, $this->setting_id, $this->default_value)
		);
	}
}