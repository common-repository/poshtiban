<?php

namespace Poshtiban\Options;

use Poshtiban\Bootstrap;
use Poshtiban\Main;

/**
 * Class Options
 * @package Poshtiban\Options
 */
class Options {

	/**
	 * The single instance of the class.
	 *
	 * @var Options
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
		include_once Bootstrap::$path . 'includes/Options/Menu.php';

		// Include settings
		require_once Bootstrap::$path . 'includes/Options/Settings/SettingsAbstract.php';
		foreach ( glob( Bootstrap::$path . 'includes/Options/Settings/*.php' ) as $filename ) {
			if ( $filename != Bootstrap::$path . 'includes/Options/Settings/SettingsAbstract.php' ) {
				include_once $filename;
			}
		}

		// Include sections
		/*require_once Bootstrap::$path . 'includes/Options/Sections/SectionsAbstract.php';
		foreach ( glob( Bootstrap::$path . 'includes/Options/Sections/*.php' ) as $filename ) {
			if ( $filename != Bootstrap::$path . 'includes/Options/Sections/SectionsAbstract.php' ) {
				include_once $filename;
			}
		}*/
		$sections = $this->getDirFiles(Bootstrap::$path . 'includes/Options/Sections');
		require_once Bootstrap::$path . 'includes/Options/Sections/SectionsAbstract.php';
		foreach ( $sections as $section ) {
			if ( $section != Bootstrap::$path . 'includes/Options/Sections/SectionsAbstract.php' ) {
				include_once $section;
			}
		}


		// Include fields abstract class
		require_once Bootstrap::$path . 'includes/Options/Fields/FieldsAbstract.php';

	}

	/**
	 * Instantiate options classes
	 */
	public function init() {
		new Menu();
	}

	/**
	 * get settings groups
	 *
	 * @return array[]
	 */
	public static function get_groups() {
		$groups = [
			[
				'key'      => 'general',
				'title'    => __( 'General', Main::$text_domain ),
			],
			[
				'key'      => 'media',
				'title'    => __( 'Media', Main::$text_domain ),
			],
			[
				'key'      => 'backup',
				'title'    => __( 'Backup', Main::$text_domain ),
			],
		];
		if ( class_exists( 'WooCommerce' ) ) {
			$groups[] = [
				'key'      => 'woocommerce',
				'title'    => __( 'Woocommerce', Main::$text_domain ),
			];
		}

		return $groups;
	}

	/**
	 * Main Class Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @static
	 * @return Options - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Scan a directory and list all files and folders
	 *
	 * @param string $dir
	 * @param array $result
	 */
	private function getDirFiles($dir)
	{
		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
		$result = [];
		foreach ($files as $file) {
			if ($file->isDir()){
				continue;
			}
			$result[] = $file->getPathname();
		}

		return $result;
	}
}