<?php


namespace Poshtiban\Options\Fields\Media\Main;

use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Options\Fields\FieldsAbstract;
use Poshtiban\Options\Settings\Media;

class UploadPath extends FieldsAbstract {
	public $default_value = '';

	public function set_id() {
		$this->id = 'upload_path';
	}

	public function set_title() {
		$this->title = __('Upload path', Main::$text_domain);
	}

	public function set_setting_id() {
		$this->setting_id = Media::get_options_name();
	}

	public function render() {
		printf( '
				<code class="description">%s</code>
				<input class="regular-text" type="text" name="%s[%s]" id="%s" value="%s">
				<span class="description">%s</span>
                <p class="description">%s</p>
                ',
			__( 'Partition home/', Main::$text_domain ),
			$this->setting_id,
			$this->id,
			sprintf('%s-%s', Main::$slug, $this->id),
			Helper::get_option($this->id, $this->setting_id, $this->default_value),
			__('If leave empty, partition\'s home will be used. use slash separated directory names.', Main::$text_domain),
			__('Place where your files will be stored in your partition', Main::$text_domain)
		);
	}
}