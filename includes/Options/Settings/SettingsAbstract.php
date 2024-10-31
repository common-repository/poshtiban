<?php

namespace Poshtiban\Options\Settings;

abstract class SettingsAbstract {
	abstract public function sanitize($new_options);
	abstract public function set_id();

	public function __construct()
	{
		$this->set_id();
		add_action( 'admin_init', [ $this, 'init' ] );
		add_filter(sprintf('default_option_%s', get_called_class()::$name), [$this, 'filter_default'], 10, 3);
	}

	public function init() {
		$args         = [ 'sanitize_callback' => [ $this, 'sanitize' ] ];
		register_setting( get_called_class()::$id, get_called_class()::$name, $args );
	}

	public static function get_options_name() {
		return get_called_class()::$name;
	}

	public function filter_default( $default, $option, $passed_default ) {
		return $default;
	}
}