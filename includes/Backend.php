<?php


namespace Poshtiban;


use Poshtiban\Media\Rewrite;

class Backend {

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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( sprintf( 'wp_ajax_%s_get_constrain_dimensions', Main::$slug ), [ $this, 'get_constrain_dimensions' ] );
		add_action( 'admin_notices', [$this, 'webhook_notices'] );
	}

	public function enqueue_scripts() {

		// TODO: Minify
		wp_enqueue_style( Main::$slug, Bootstrap::$url . 'assets/backend/css/style.min.css', [], Main::$version, 'all' );
		wp_enqueue_style( sprintf( 'uppy-%s', Main::$slug ), Bootstrap::$url . 'assets/backend/css/uppy.min.css', [],
			'1.14.1' );


		// TODO: Minify
		wp_enqueue_script( Main::$slug, Bootstrap::$url . 'assets/backend/js/script.min.js', [ 'jquery' ], Main::$version,
			true );
		wp_enqueue_script( sprintf( 'uppy-%s', Main::$slug ), Bootstrap::$url . 'assets/backend/js/uppy.min.js', [],
			'1.14.1' );
		wp_enqueue_script( 'uppy-locale',
			sprintf( '%sassets/backend/js/locales/%s.min.js', Bootstrap::$url, get_locale() ), [], '1.14.1' );
		$up_load_dir    = wp_upload_dir();
		wp_localize_script( Main::$slug, Main::$slug, [
			'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
			'nonce'                   => wp_create_nonce( 'ajax-nonce' ),
			'error'                   => __( 'Somethings goes wrong. Please try again.', Main::$text_domain ),
			'uploadPathId'            => Helper::get_option( 'upload_path_id', 'media' ),
			'woocommerceUploadPathId' => Helper::get_option( 'upload_path_id', 'woocommerce' ),
			'uploadType'              => Helper::get_option( 'upload_type', 'general' ),
			'uppySelector'            => sprintf( '#%s-drag-drop-area', Main::$slug ),
			'fillBackupItems'         => __( 'Please select at least one backup item.', Main::$text_domain ),
			'selectFiles'             => __( 'Please select or enter at least one file.', Main::$text_domain ),
			'fileId'                  => __( 'File ID', Main::$text_domain ),
			'fileName'                => __( 'File Name', Main::$text_domain ),
			'chooseFile'              => __( 'Choose file', Main::$text_domain ),
			'getMirrorLink'           => __( 'Get mirror download link', Main::$text_domain ),
			'delete'                  => __( 'Delete', Main::$text_domain ),
			'copied'                  => __( 'Copied!', Main::$text_domain ),
			'insertFileManually'      => __( 'Insert file id manually', Main::$text_domain ),
			'uploadToCloud'           => sprintf( __( 'Upload to %s', Main::$text_domain ), Main::$name ),
			'SelectFileTitle'         => sprintf( __( 'Select file from %s', Main::$text_domain ), Main::$name ),
			'uploadSubDir'         => $up_load_dir['subdir'],
			'image_library_url'       => add_query_arg( [
				'action' => sprintf( '%s_woocommerce_file_selector', Main::$slug ),
				'width'  => '1000',
				'height' => '800',
			], admin_url( 'admin-ajax.php' ) ),
			'locale'                  => get_locale(),
			'helper'                  => [
				'token'           => Helper::get_option( 'token', 'general' ),
				'get_image_sizes' => Helper::get_image_sizes(),
				'mirror_type'     => Helper::get_option( 'mirror_type', 'woocommerce' ),
				'urls'            => [
					'companion' => Helper::get_option( 'companion_url', 'general' ),
					'tus'       => Helper::get_option( 'tus_url', 'general' ),
				],
				'my' => function($e) {
					return $e;
				}
			],
		] );
	}

	public function get_constrain_dimensions() {
		$width = ( isset( $_POST['width'] ) && ! empty( $_POST['width'] ) ) ? intval( $_POST['width'] ) : false;
		$height = ( isset( $_POST['height'] ) && ! empty( $_POST['height'] ) ) ? intval( $_POST['height'] ) : false;

		wp_send_json_success(Helper::get_constrain_sizes($width, $height));
	}

	public function webhook_notices() {
		$webhook = Helper::get_option('webhook_url', 'media');
		if( !$webhook ) {
			if( Helper::isDebugMode() ) {
				var_dump($webhook);
			}
			Helper::view('notice', 'general', [
				'type' => 'error',
				'is_dismissible' => false,
				'text' => sprintf(__('Webhook url option did not set on your website. %s Needs this option to work correctly', Main::$text_domain), Main::$name),
				'link' => []
			]);
		}

		/*$response = wp_remote_get( $webhook_url );
		$hasError = true;
		if ( !is_wp_error( $response ) ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code == 200 ) {
				$body = json_decode( wp_remote_retrieve_body( $response ) );
				$hasError = !$body->success;
			}
		}*/

		$check_webhook_url = Main::$api_url . '/partition/check-webhook';
		$token = Helper::get_option('token', 'general');
		$hasError = true;
		$headers  = [
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json'
		];
		$response = wp_remote_get( $check_webhook_url, [
			'headers' => $headers,
			'timeout' => 60000
		] );
		if ( !is_wp_error( $response ) ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code == 200 ) {
				$body = json_decode( wp_remote_retrieve_body( $response ) );
				$hasError = !$body->webhook_status;
			}
		}

		if( $hasError ) {
			if( Helper::isDebugMode() ) {
				echo 'Response code: '.$response_code;
				$debug_body = wp_remote_retrieve_body( $response );
				echo '<pre style="direction:ltr; text-align: left;">'; print_r($debug_body); echo '</pre>';
			}

			Helper::view('notice', 'general', [
				'type' => 'error',
				'is_dismissible' => false,
				'text' => sprintf(__('Your webhook url is unavailable. %s Needs this option to work correctly', Main::$text_domain), Main::$name),
				'link' => [
					'url' => '#',
					'text' => 'Check it out'
				]
			]);
		}

		$url = Main::$api_url . '/partition';
		$token = Helper::get_option('token', 'general');
		$headers  = [
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json'
		];
		$response = wp_remote_get( $url, [
			'headers' => $headers,
			'timeout' => 60000
		] );
		if ( !is_wp_error( $response ) ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code == 200 ) {
				$body = json_decode( wp_remote_retrieve_body( $response ) );
				$partition = $body['0'];
				$webhook_url = Rewrite::get_webhook_url();
				if( $webhook_url !== $partition->webhook ) {
					if( Helper::isDebugMode() ) {
						echo '<pre style="direction:ltr; text-align: left;">Webhook URL: '; print_r($webhook_url); echo '</pre>';
						echo '<pre style="direction:ltr; text-align: left;">Partition URL: '; print_r($partition->webhook); echo '</pre>';
					}

					Helper::view('notice', 'general', [
						'type' => 'error',
						'is_dismissible' => false,
						'text' => __('Your webhook url is not as the same as partition url. In this state, remote uploads does not work correctly ', Main::$text_domain),
						'link' => [
							'url' => '#',
							'text' => 'Check it out'
						]
					]);
				}
			}
		}

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