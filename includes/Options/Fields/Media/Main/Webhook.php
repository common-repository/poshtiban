<?php

namespace Poshtiban\Options\Fields\Media\Main;

use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Media\Rewrite;
use Poshtiban\Options\Fields\FieldsAbstract;
use Poshtiban\Options\Settings\Backup;
use Poshtiban\Options\Settings\Media;

class Webhook extends FieldsAbstract {
	public $default_value = '';

	public function set_id() {
		$this->id = 'webhook_url';
	}

	public function set_title() {
		$this->title = __('Webhook URL', Main::$text_domain);
	}

	public function set_setting_id() {
		$this->setting_id = Media::get_options_name();
	}

	public function render() {
		$value = Helper::get_option($this->id, $this->setting_id, $this->default_value);
		$permalinks = get_option( 'permalink_structure' );
		$base_url = !empty($permalinks) ? sprintf('%s/%s/', get_bloginfo('url'), Main::$slug) : sprintf( '%s/?%s_web_hook=', get_bloginfo( 'url' ), Main::$slug );

		printf( '
				%s
				<input class="regular-text" type="text" name="%s[%s]" id="%s" value="%s" disabled readonly>
                <a href="%s" target="_blank" class="description">%s</a>
                <p class="description">%s</p>
                ',
			'<code>'.$base_url.'</code>',
			$this->setting_id,
			$this->id,
			sprintf('%s-%s', Main::$slug, $this->id),
			$value,
			Rewrite::get_webhook_url($value),
			__('View webhook page', Main::$text_domain),
			__('This key is auto generated.', Main::$text_domain)
		);
	}
}