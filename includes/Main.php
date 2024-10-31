<?php

namespace Poshtiban;

use Poshtiban\Database\Database;
use Poshtiban\Media\Media;
use Poshtiban\Options\Options;
use Poshtiban\Backup\Backup;
use Poshtiban\Woocommerce\Woocommerce;

/**
 * Class Main
 * @package Poshtiban
 */
class Main {

	/**
	 * The single instance of the class.
	 *
	 * @var Main
	 */
	protected static $_instance = null;

	/**
	 * plugin version
	 *
	 * @var string
	 */
	public static $version = '2.7.1';

	/**
	 * plugin name
	 *
	 * @var string
	 */
	public static $name;

	/**
	 * plugin slug
	 *
	 * @var string
	 */
	public static $slug = 'poshtiban';

	/**
	 * Plugin description
	 *
	 * @var string
	 */
	private $description;

	/**
	 * plugin author name
	 *
	 * @var string|void
	 */
	private $author;

	/**
	 * plugin text domain
	 *
	 * @var string
	 */
	public static $text_domain = 'poshtiban';

	/**
	 * API Base url
	 *
	 * @var string
	 */
	public static $api_url = 'https://atoms.poshtiban.com';

	/**
	 * Website domain
	 *
	 * @var string
	 */
	public static $domain = 'poshtiban.io';

	/**
	 * API domain
	 *
	 * @var string
	 */
	public static $api_domain = 'poshtiban.com';


	/**
	 * Main constructor.
	 */
	private function __construct() {
		$this->includes();
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	/**
	 * include dependencies
	 */
	public function includes() {
		include_once Bootstrap::$path . 'includes/i18n.php';
		include_once Bootstrap::$path . 'includes/Helper.php';
		include_once Bootstrap::$path . 'includes/Backend.php';
		include_once Bootstrap::$path . 'includes/Frontend.php';
		include_once Bootstrap::$path . 'includes/Database/Database.php';
		include_once Bootstrap::$path . 'includes/Options/Options.php';
		include_once Bootstrap::$path . 'includes/Media/Media.php';
		include_once Bootstrap::$path . 'includes/Backup/Backup.php';
		include_once Bootstrap::$path . 'includes/Woocommerce/Woocommerce.php';
	}

	/**
	 * Instantiate plugin classes
	 */
	public function init() {
		self::$name        = __( 'Poshtiban', self::$text_domain );
		$this->description = __( 'Poshtiban official wordpress plugin', self::$text_domain );
		$this->author      = __( 'Poshtiban development team', self::$text_domain );

		Backend::instance();
		Options::instance();
		Media::instance();
		Backup::instance();
		Frontend::instance();
		Database::instance();
		if( class_exists('\Woocommerce') ) {
			Woocommerce::instance();
		}
	}

	/**
	 * Main Class Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @static
	 * @return Main - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}