<?php


namespace Poshtiban\Options;


use Poshtiban\Helper;
use Poshtiban\Main;

class Menu {

	/**
	 * Menu constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
	}

	public function admin_menu() {
		add_menu_page( Main::$name, Main::$name, 'manage_options', Main::$slug, null, 'dashicons-shield' );
		add_submenu_page( Main::$slug, __( 'Settings', Main::$text_domain ), __( 'Settings', Main::$text_domain ),
			'manage_options', Main::$slug, [ $this, 'content' ] );
	}

	public function content() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field($_GET['tab']) : 'general';
		$tabs       = Options::get_groups();

		Helper::view('menu', 'options', ['active_tab' => $active_tab, 'tabs' => $tabs]);
	}
}