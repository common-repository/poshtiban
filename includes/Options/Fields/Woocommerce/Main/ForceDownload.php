<?php


namespace Poshtiban\Options\Fields\Woocommerce\Main;

use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Options\Fields\FieldsAbstract;
use Poshtiban\Options\Settings\Woocommerce;

class ForceDownload extends FieldsAbstract {
	public $default_value = 'no';

	public function set_id() {
		$this->id = 'force_download';
	}

	public function set_title() {
		$this->title = __('Force download', Main::$text_domain);
	}

	public function set_setting_id() {
		$this->setting_id = Woocommerce::get_options_name();
	}

	public function render() {
		$checked = Helper::get_option($this->id, $this->setting_id, $this->default_value) === 'yes' ? 'checked' : '';

		printf( '<label for="%s"><input type="checkbox" name="%s" id="%s" value="yes" %s>%s</label><p class="description">%s</p>',
			sprintf('%s-%s', Main::$slug, $this->id),
			sprintf('%s[%s]', $this->setting_id, $this->id),
			sprintf('%s-%s', Main::$slug, $this->id),
			$checked,
			__( 'Enable force download', Main::$text_domain ),
			__( 'If this option is activated, all woocommerce link will forced to download', Main::$text_domain )
		);
	}
}