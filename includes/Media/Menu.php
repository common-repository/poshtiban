<?php


namespace Poshtiban\Media;


use Poshtiban\Helper;
use Poshtiban\Main;

/**
 * Class Menu
 * @package Poshtiban\Media
 */
class Menu {

	/**
	 * Menu constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
	}

	/**
	 *  Add media menus
	 */
	public function admin_menu() {
		//Add new submenu to media page
		add_submenu_page(
			'upload.php',
			sprintf(__( 'Add new upload to %s', Main::$text_domain ), Main::$name),
			sprintf(__( 'Add new upload to %s', Main::$text_domain ), Main::$name),
			'upload_files',
			sprintf('media-%s-new', Main::$slug),
			[ $this,'media_menu_content' ]
		);
		add_submenu_page(
			Main::$slug,
			__( 'Remote upload lists', Main::$text_domain ),
			__('Remote upload lists', Main::$text_domain),
			'manage_options',
			sprintf('%s-remote-uploads', Main::$slug),
			[ $this, 'remote_list_menu_content' ]
		);
	}

	/**
	 *  Render menu content
	 */
	public function media_menu_content() {
		Helper::view( 'upload-menu', 'media', [
			'class' => sprintf('%s_upload_box', Main::$slug),
			'id' => sprintf('%s-drag-drop-area', Main::$slug),
		]);
	}
	/**
	 *  Render menu content
	 */
	public function remote_list_menu_content() {
		$remote_upload_queue_list = get_option(sprintf('%s_remote_upload_queue', Main::$slug), [] );
		if( Helper::isDebugMode() ) {
			echo '<pre style="direction:ltr; text-align: left;">'; print_r($remote_upload_queue_list); echo '</pre>';
		}
		Helper::view( 'remote-list', 'media', [
			'files' => $remote_upload_queue_list,
			'text_domain' => Main::$text_domain,
		]);
	}
}