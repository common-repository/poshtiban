<?php

namespace Poshtiban\Database;

use Poshtiban\Bootstrap;
use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Options\Options;

class Database {

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
		$this->includes();
		$this->init();
	}

	/**
	 * include dependencies
	 */
	public function includes() {
		include_once Bootstrap::$path . 'includes/Database/SRDB.php';
	}

	/**
	 * Instantiate plugin classes
	 */
	public function init() {
	}

	public static function search_and_replace( $search_for, $replace_with, $tables = [] ) {
		global $wpdb;

		if( empty($tables) ) {
			$tables = [
				$wpdb->posts,
				$wpdb->postmeta,
				// $wpdb->usermeta,
				// $wpdb->options,
			];
		}
		$srdb       = new SRDB();
		$result = [];
		foreach ( $tables as $tbl ) {
			$args = [
				'case_insensitive' => 'off',
				'replace_guids'    => 'off',
				'dry_run'          => 'off',
				'search_for'       => $search_for,
				'replace_with'     => $replace_with,
				'completed_pages'  => 0,
			];
			$result[$tbl] = $srdb->srdb( $tbl, $args );
		}

		return $result;
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