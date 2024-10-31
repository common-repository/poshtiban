<?php

namespace Poshtiban\Backup;

use Poshtiban\Bootstrap;
use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Options\Options;

class Backup {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 */
	protected static $_instance = null;

	/**
	 * Options constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		$this->includes();
		$this->init();
	}


	public function admin_menu() {
		if( !empty($this->backup_methods()) ) {
			add_submenu_page(
				Main::$slug,
				__( 'Backup', Main::$text_domain ),
				__('Backup', Main::$text_domain),
				'manage_options',
				sprintf('%s-backup', Main::$slug),
				[ $this, 'content' ]
			);
		}
	}

	public function content() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field($_GET['tab']) : 'duplicator';
		$methods       = $this->backup_methods();

		Helper::view('menu', 'backup', ['active_tab' => $active_tab, 'methods' => $methods]);
	}

	public function backup_methods() {
		return apply_filters(sprintf('%s_backup_methods', Main::$slug), []);
	}

	/**
	 * include dependencies
	 */
	public function includes() {
		include_once Bootstrap::$path . 'includes/Backup/Duplicator.php';
	}

	/**
	 * Instantiate plugin classes
	 */
	public function init() {
		new Duplicator();
	}


	/**
	 * Main Class Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @static
	 * @return static - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}