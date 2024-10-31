<?php


namespace Poshtiban\Options\Fields\General\Main;

use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Options\Fields\FieldsAbstract;
use Poshtiban\Options\Settings\General;

class UploadType extends FieldsAbstract {
	public $default_value = 'public';

	public function set_id() {
		$this->id = 'upload_type';
	}

	public function set_title() {
		$this->title = __('Default upload type', Main::$text_domain);
	}

	public function set_setting_id() {
		$this->setting_id = General::get_options_name();
	}

	public function render() {
		$types = [
			'private' => __('Private', Main::$text_domain),
			'public' => __('Public', Main::$text_domain),
		];

		echo $this->select_field(
			$types,
			Helper::get_option($this->id, $this->setting_id, $this->default_value),
			sprintf('%s[%s]', $this->setting_id, $this->id),
			false
		);

	}
}