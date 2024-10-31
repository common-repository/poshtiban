<?php


namespace Poshtiban;


class Frontend {

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
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function enqueue_scripts() {

		// TODO: Minify
		wp_enqueue_style( Main::$slug, Bootstrap::$url . 'assets/frontend/css/style.min.css', [], Main::$version,
			'all' );

		// TODO: Minify
		wp_enqueue_script( Main::$slug, Bootstrap::$url . 'assets/frontend/js/script.min.js', [ 'jquery' ],
			Main::$version, true );

		wp_localize_script( Main::$slug, Main::$slug , [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ajax-nonce' ),
			'error'   => __( 'Somethings goes wrong. Please try again.', Main::$text_domain ),
			'uploadPathId'   => Helper::get_option( 'upload_path_id', 'media' ),
			'woocommerceUploadPathId'   => Helper::get_option( 'upload_path_id', 'woocommerce' ),
			'uploadType'   => Helper::get_option( 'upload_type', 'general' ),
			'uppySelector'   => sprintf('#%s-drag-drop-area', Main::$slug),
			'helper'   => [
				'token' => Helper::get_option( 'token', 'general' ),
				'get_image_sizes' => Helper::get_image_sizes(),
				'mirror_type' => Helper::get_option('mirror_type', 'woocommerce'),
				'urls' => [
					'companion' => Helper::get_option('companion_url', 'general'),
					'tus' => Helper::get_option('tus_url', 'general'),
				],
			],
		] );
	}


	/**
	 * Main Class Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @static
	 * @return self - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}