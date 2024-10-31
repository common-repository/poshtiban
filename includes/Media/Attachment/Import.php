<?php

namespace Poshtiban\Media\Attachment;

use Poshtiban\Database\Database;
use Poshtiban\Helper;
use Poshtiban\Main;

/**
 * Import media from cloud to Wordpress
 *
 * Class Import
 * @package Poshtiban\Media\Attachment
 */
class Import {

	/**
	 * Import constructor.
	 */
	public function __construct() {
		add_filter( 'bulk_actions-upload', [ $this, 'add_bulk_action' ] );
		add_filter( 'handle_bulk_actions-upload', [ $this, 'handle_bulk_action' ], 10, 3 );
		add_action( sprintf( 'wp_ajax_%s_restore_media', Main::$slug ), [ $this, 'ajax_import' ] );
	}

	/**
	 * Add new action to media page's bulk actions
	 *
	 * @param $bulk_actions
	 *
	 * @return mixed
	 */
	public function add_bulk_action( $bulk_actions ) {
		$bulk_actions[ sprintf( '%s-import', Main::$slug ) ] = sprintf( __( 'Download from %s', Main::$text_domain ),
			Main::$name );

		return $bulk_actions;
	}

	/**
	 * Import a cloud media to Wordpress with ajax
	 *
	 */
	public function ajax_import() {
		$attachment_id = ( isset( $_POST['attachment_id'] ) && ! empty( $_POST['attachment_id'] ) ) ? sanitize_text_field( $_POST['attachment_id'] ) : false;

		if ( ! $attachment_id ) {
			wp_send_json_error( __( 'Please select a attachment', Main::$text_domain ) );
		}

		$cloud_id = get_post_meta( $attachment_id, Attachment::get_meta_name( 'id' ), true );
		if ( empty( $cloud_id ) ) {
			wp_send_json_error( __( 'Selected file is not a cloud file', Main::$text_domain ) );
		}

		$file = Attachment::get_file_by_id( $cloud_id );

		if ( ! $file ) {
			wp_send_json_error( __( 'Cloud file does not exists', Main::$text_domain ) );
		}

		if ( ! $file->public ) {
			wp_send_json_error( __( 'This file is private and can not be downloaded by Wordpress',
				Main::$text_domain ) );
		}

		$download = self::remote_download( $file->public_link, $attachment_id );
		if ( $download ) {

			$this->import_attachment( $attachment_id, $cloud_id );

			$attachment_url = wp_get_attachment_url( $attachment_id );
			wp_send_json_success( [
				'message'        => __( 'File downloaded by Wordpress successfully', Main::$text_domain ),
				'attachment_url' => $attachment_url,
				'attachment_id'  => $attachment_id,
			] );
		}

		wp_send_json_error( __( 'Downloading file by Wordpress failed', Main::$text_domain ) );

	}

	/**
	 * Do all action that needed after an attachment imported from cloud to Wordpress
	 *
	 * @param $attachment_id
	 * @param $cloud_id
	 *
	 * @return void
	 */
	public function import_attachment( $attachment_id, $cloud_id ) {
		$urls           = get_post_meta( $attachment_id, Attachment::get_meta_name( 'url' ), true );
		$upload_dir     = wp_get_upload_dir();
		$upload_dir_url = sprintf( '%s/', $upload_dir['baseurl'] );
		$meta_data      = wp_get_attachment_metadata( $attachment_id );
		$fileParts      = pathinfo( $meta_data['file'] );
		foreach ( $urls as $size_name => $find_url ) {
			$replace_url = false;

			if ( $size_name === 'full' ) {
				$replace_url = sprintf( '%s%s', $upload_dir_url, $meta_data['file'] );
			} else {
				if ( array_key_exists( $size_name, $meta_data['sizes'] ) ) {
					$replace_url = sprintf( '%s%s/%s', $upload_dir_url, $fileParts['dirname'],
						$meta_data['sizes'][ $size_name ]['file'] );
				}
			}

			if ( $replace_url ) {
				Database::search_and_replace( $find_url, $replace_url );
			}
		}

		// Delete cloud metas from Wordpress
		delete_post_meta( $attachment_id, Attachment::get_meta_name( 'id' ) );
		delete_post_meta( $attachment_id, Attachment::get_meta_name( 'url' ) );

		$delete_status = Helper::get_option( 'delete_status', 'general' );
		$force_delete  = ( $delete_status === 'permanent' ) ? true : false;

		// Delete file from cloud server
		Attachment::delete_file( $cloud_id, $force_delete );
	}


	/**
	 * Insert an attachment from an URL address.
	 *
	 * @param String $url
	 * @param Int $attachment_id
	 * @param Int $parent_post_id
	 *
	 * @return Int    Attachment ID
	 */
	public static function remote_download( $url, $attachment_id = null, $parent_post_id = null ) {
		if ( ! class_exists( '\WP_Http' ) ) {
			include_once( ABSPATH . WPINC . '/class-http.php' );
		}

		$http     = new \WP_Http();
		$response = $http->request( $url );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		if ( $response['response']['code'] != 200 ) {
			return false;
		}

		$upload = wp_upload_bits( basename( $url ), null, $response['body'] );
		if ( ! empty( $upload['error'] ) ) {
			return false;
		}

		$file_path        = $upload['file'];
		$file_name        = basename( $file_path );
		$file_type        = wp_check_filetype( $file_name, null );
		$attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
		$wp_upload_dir    = wp_upload_dir();

		// Create the attachment
		if ( ! $attachment_id ) {
			$post_info     = [
				'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
				'post_mime_type' => $file_type['type'],
				'post_title'     => $attachment_title,
				'post_content'   => '',
				'post_status'    => 'inherit',
			];
			$attachment_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );
		}

		// Include image.php
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
		update_attached_file( $attachment_id, $file_path );

		// Assign metadata to attachment
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		return $attachment_id;
	}


	/**
	 * Handle import from cloud action of media page's bulk actions
	 *
	 * @param $send_back
	 * @param $do_action
	 * @param $attachment_ids
	 *
	 * @return string
	 */
	public function handle_bulk_action( $send_back, $do_action, $attachment_ids ) {
		if ( $do_action !== sprintf( '%s-import', Main::$slug ) ) {
			return $send_back;
		}

		$result = [];
		foreach ( $attachment_ids as $attachment_id ) {

			$cloud_id = get_post_meta( $attachment_id, Attachment::get_meta_name( 'id' ), true );
			if ( empty( $cloud_id ) ) {
				$result[ $attachment_id ] = [
					'success' => false,
					'message' => __( 'Selected file is not a cloud file', Main::$text_domain )
				];
				continue;
			}

			$file = Attachment::get_file_by_id( $cloud_id );

			if ( ! $file ) {
				$result[ $attachment_id ] = [
					'success' => false,
					'message' => __( 'Cloud file does not exists', Main::$text_domain )
				];
				continue;
			}

			if ( ! $file->public ) {
				$result[ $attachment_id ] = [
					'success' => false,
					'message' => __( 'This file is private and can not be downloaded by Wordpress', Main::$text_domain )
				];
				continue;
			}

			$download = self::remote_download( $file->public_link, $attachment_id );
			if ( $download ) {
				$this->import_attachment( $attachment_id, $cloud_id );
				$attachment_url           = wp_get_attachment_url( $attachment_id );
				$result[ $attachment_id ] = [
					'success'        => true,
					'message'        => __( 'File downloaded by Wordpress successfully', Main::$text_domain ),
					'attachment_url' => $attachment_url,
					'attachment_id'  => $attachment_id,
				];
			} else {
				$result[ $attachment_id ] = [
					'success' => false,
					'message' => __( 'Downloading file by Wordpress failed', Main::$text_domain )
				];
			}

		}

		$key = wp_generate_uuid4();
		update_option( $key, $result );

		$send_back = add_query_arg( sprintf( '%s_import_bulk_result', Main::$slug ), $key, $send_back );

		return $send_back;
	}

}