<?php


namespace Poshtiban\Options\Fields\Woocommerce\Main;

use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Options\Fields\FieldsAbstract;
use Poshtiban\Options\Settings\Woocommerce;

class ActiveTime extends FieldsAbstract {
	public $default_value = 10800;

	public function set_id() {
		$this->id = 'active_time';
	}

	public function set_title() {
		$this->title = __('Active time', Main::$text_domain);
	}

	public function set_setting_id() {
		$this->setting_id = Woocommerce::get_options_name();
	}

	public function render() {
		printf( '<input class="regular-text" type="number" min="1" name="%s[%s]" id="%s" value="%s"><span class="description">%s</span><p class="description">%s</p>',
			$this->setting_id,
			$this->id,
			sprintf('%s-%s', Main::$slug, $this->id),
			Helper::get_option($this->id, $this->setting_id, $this->default_value),
			__( 'Enter in second', Main::$text_domain ),
			__( 'Active time for private links', Main::$text_domain )
		);
	}
}