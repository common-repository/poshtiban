<?php

namespace Poshtiban\Backup;

use Poshtiban\Helper;
use Poshtiban\Main;

class Duplicator {

	private $id = 'duplicator';

	public static $title;

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
		self::$title = __( 'Duplicator', Main::$text_domain );
		add_filter( sprintf( '%s_backup_methods', Main::$slug ), [ $this, 'add_method' ] );
		add_action( sprintf( '%s_backup_menu_tab_%s', Main::$slug, $this->id ), [ $this, 'menu_content' ] );
		if ( class_exists( '\DUP_Package' ) ) {
			add_action( sprintf( 'wp_ajax_%s_%s_backup_remote_upload_package', Main::$slug, $this->id ),
				[ $this, 'upload_package' ] );

		}
	}

	public function add_method( array $methods ) {
		$methods[ $this->id ] = __( 'Duplicator', Main::$text_domain );

		return $methods;
	}

	public function menu_content() {
		if ( class_exists( '\DUP_Package' ) ) {
			Helper::view( sprintf( '%s/%s', $this->id, $this->id ), 'backup');
		} else {
			Helper::view( 'inactive-method', 'backup', [
				'title' => self::$title
			]);
		}
	}

	public static function table_row( \DUP_Package $package ) {
		global $packageTablerowCount;

		$is_running_package = $package->isRunning();
		$pack_name          = $package->Name;
		$pack_archive_size  = $package->getArchiveSize();
		$pack_perc          = $package->Status;
		$pack_storeurl      = $package->StoreURL;
		$pack_dbonly        = $package->Archive->ExportOnlyDB;
		$pack_build_mode    = ( $package->Archive->Format === 'ZIP' ) ? true : false;

		//Links
		$uniqueid    = $package->NameHash;
		$packagepath = $pack_storeurl . $package->Archive->File;

		$css_alt                = ( $packageTablerowCount % 2 != 0 ) ? '' : 'alternate';
		$get_package_file_nonce = wp_create_nonce( 'DUP_CTRL_Package_getPackageFile' );

		if ( $package->Status >= 100 || $is_running_package ) {
			Helper::view( 'duplicator/active-package', 'backup', [
				'package'                => $package,
				'is_running_package'     => $is_running_package,
				'pack_name'              => $pack_name,
				'pack_archive_size'      => $pack_archive_size,
				'pack_perc'              => $pack_perc,
				'pack_storeurl'          => $pack_storeurl,
				'pack_dbonly'            => $pack_dbonly,
				'pack_build_mode'        => $pack_build_mode,
				'uniqueid'               => $uniqueid,
				'packagepath'            => $packagepath,
				'css_alt'                => $css_alt,
				'text_domain'            => Main::$text_domain,
				'get_package_file_nonce' => $get_package_file_nonce,
			] );
		} else {
			$error_url = "?page=duplicator&action=detail&tab=detail&id={$package->ID}";
			Helper::view( 'duplicator/failed-package', 'backup', [
				'package'            => $package,
				'is_running_package' => $is_running_package,
				'pack_name'          => $pack_name,
				'pack_archive_size'  => $pack_archive_size,
				'pack_perc'          => $pack_perc,
				'pack_storeurl'      => $pack_storeurl,
				'pack_dbonly'        => $pack_dbonly,
				'pack_build_mode'    => $pack_build_mode,
				'uniqueid'           => $uniqueid,
				'packagepath'        => $packagepath,
				'css_alt'            => $css_alt,
				'error_url'          => $error_url,
				'text_domain'        => Main::$text_domain
			] );
		}
	}

	public function upload_package() {
		$url        = ( isset( $_POST['url'] ) && ! empty( $_POST['url'] ) ) ? esc_url_raw($_POST['url']) : false;
		$package_id = ( isset( $_POST['package_id'] ) && ! empty( $_POST['package_id'] ) ) ? sanitize_text_field($_POST['package_id']) : false;

		if ( ! $package_id ) {
			wp_send_json_error( __( 'Package ID is empty', Main::$text_domain ) );
		}

		$folder_id = Helper::get_option( 'upload_path_id', 'backup' );
		$path      = date_i18n( 'Y/m/d/' . $package_id );
		$upload    = Helper::remote_upload( $url, $folder_id, $path );
		wp_send_json( $upload );
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