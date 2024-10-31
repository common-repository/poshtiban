<?php


namespace Poshtiban\Media;


use Poshtiban\Helper;
use Poshtiban\Main;

class Upload {

	/**
	 * Menu constructor.
	 */
	public function __construct() {
		add_filter( 'media_upload_tabs', [ $this, 'upload_tabs' ], 999 );
		add_action( sprintf( 'media_upload_%s', Main::$slug ), [ $this, 'media_tab_content' ] );
	}

	/**
	 * Add new tab to media upload tabs
	 *
	 * @see https://developer.wordpress.org/reference/functions/media_upload_tabs/
	 *
	 * @param $tabs
	 *
	 * @return array
	 */
	public function upload_tabs( $tabs ) {
		$tabs[ Main::$slug ] = sprintf( __( 'Upload to %s', Main::$text_domain ), Main::$name );

		return $tabs;
	}

	/**
	 *  Add content to specific upload-tab views in the legacy (pre-3.5.0) media popup.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/media_upload_tab/
	 */
	public function media_tab_content() {
		wp_iframe( [ $this, 'tab_content' ] );
	}

	/**
	 *  Add content to media tab iframe
	 */
	public function tab_content() {
		// This function is used for print media uploader headers etc.
		media_upload_header();
		Helper::view( 'upload-tab', 'media',[
			'class' => sprintf('%s_upload_box', Main::$slug),
			'id' => sprintf('%s-drag-drop-area', Main::$slug),
		]);
	}
}