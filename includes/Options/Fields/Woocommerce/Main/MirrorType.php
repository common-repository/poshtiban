<?php


namespace Poshtiban\Options\Fields\Woocommerce\Main;

use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Options\Fields\FieldsAbstract;
use Poshtiban\Options\Settings\Woocommerce;

class MirrorType extends FieldsAbstract {
	public $default_value = 'sync';

	public function set_id() {
		$this->id = 'mirror_type';
	}

	public function set_title() {
		$this->title = __('Mirror type', Main::$text_domain);
	}

	public function set_setting_id() {
		$this->setting_id = Woocommerce::get_options_name();
	}

	public function render() {
		$types = [
			'sync' => __('Synchronised', Main::$text_domain),
			Main::$slug => sprintf(__('Just %s', Main::$text_domain), Main::$name),
		];

		echo $this->select_field(
			$types,
			Helper::get_option($this->id, $this->setting_id, $this->default_value),
			sprintf('%s[%s]', $this->setting_id, $this->id),
			false
		);
		printf(
			'<p class="description"><code>%s</code> %s</p>',
			__('Synchronised', Main::$text_domain),
			sprintf(__('Synchronise all woocommerce downloadable files in %s automatically. And give users 2 download link.', Main::$text_domain), Main::$name)
		);
		printf(
			'<p class="description"><code>%s</code> %s</p>',
			sprintf(__('Just %s', Main::$text_domain), Main::$name),
			sprintf(__('Only use %s files and give user 1 download link from it', Main::$text_domain), Main::$name)
		);

	}
}