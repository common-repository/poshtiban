<?php

namespace Poshtiban\Media;

use Poshtiban\Bootstrap;
use Poshtiban\Media\Attachment\Attachment;

class Media {

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
		include_once Bootstrap::$path . 'includes/Media/Rewrite.php';
		include_once Bootstrap::$path . 'includes/Media/Upload.php';
		include_once Bootstrap::$path . 'includes/Media/Menu.php';
		include_once Bootstrap::$path . 'includes/Media/Attachment/Attachment.php';
	}

	/**
	 * Instantiate plugin classes
	 */
	public function init() {
		new Rewrite();
		new Upload();
		new Menu();
		new Attachment();
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