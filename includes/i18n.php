<?php

namespace Poshtiban;

class i18n {

	/**
	 * The single instance of the class.
	 *
	 * @var i18n
	 */
	protected static $_instance = null;

	/**
	 * Main constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'load_text_domain' ] );
	}


	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	public static function load_text_domain() {
		$plugin_rel_path = plugin_basename( Bootstrap::$path ) . '/languages';
		load_plugin_textdomain( Main::$text_domain, false, $plugin_rel_path );
	}

	/**
	 * Main Class Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @static
	 * @return i18n - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}
i18n::instance();