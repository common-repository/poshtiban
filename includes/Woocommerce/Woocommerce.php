<?php

namespace Poshtiban\Woocommerce;

use Poshtiban\Bootstrap;
use Poshtiban\Main;

class Woocommerce {
	/**
	 * @var string
	 */
	public static $mirror_meta_name;
	/**
	 * @var string
	 */
	public static  $sync_meta_name;
	/**
	 * @var string
	 */
	public static  $downloadable_meta_name;

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
		self::$mirror_meta_name       = sprintf( '_%s_wc_files', Main::$slug );
		self::$sync_meta_name         = sprintf( '_%s_sync_files', Main::$slug );
		self::$downloadable_meta_name = sprintf( '_%s_downloadable_files', Main::$slug );

		$this->includes();
		$this->init();
	}


	/**
	 * include dependencies
	 */
	public function includes() {
		include_once Bootstrap::$path . 'includes/Woocommerce/Product.php';
		include_once Bootstrap::$path . 'includes/Woocommerce/Account.php';
	}

	/**
	 * Instantiate plugin classes
	 */
	public function init() {
		new Product();
		new Account();
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