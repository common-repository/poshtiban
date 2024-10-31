<?php

namespace Poshtiban\Options\Settings;

use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Media\Rewrite;

class General extends SettingsAbstract {
	protected static $id;
	protected static $name;

	public function sanitize( $new_options ) {
		$old_settings = get_option( self::get_options_name() );
		$old_media_settings = get_option( sprintf( '%s-media-settings', Main::$slug ) );

		$old_token    = ( isset( $old_settings['token'] ) && ! empty( $old_settings['token'] ) ) ? $old_settings['token'] : false;
		$new_token    = ( isset( $new_options['token'] ) && ! empty( $new_options['token'] ) ) ? $new_options['token'] : false;
		$token        = ( $new_token ) ? $new_token : $old_token;
		$media_path   = ( isset( $old_media_settings['upload_path'] ) && ! empty( $old_media_settings['upload_path'] ) ) ? $old_media_settings['upload_path'] : 'media';;
		$upload_path_id = ( isset( $old_media_settings['upload_path_id'] ) && ! empty( $old_media_settings['upload_path_id'] ) ) ? $old_media_settings['upload_path_id'] : false;
		$webhook_url = ( isset( $old_media_settings['webhook_url'] ) && ! empty( $old_media_settings['webhook_url'] ) ) ? $old_media_settings['webhook_url'] : false;
		$backup_path    = ( isset( $old_backup_settings['upload_path'] ) && ! empty( $old_backup_settings['upload_path'] ) ) ? $old_backup_settings['upload_path'] : 'backup';
		$backup_path_id = ( isset( $old_backup_settings['upload_path_id'] ) && ! empty( $old_backup_settings['upload_path_id'] ) ) ? $old_backup_settings['upload_path_id'] : false;
		$wc_path        = ( isset( $old_wc_settings['upload_path'] ) && ! empty( $old_wc_settings['upload_path'] ) ) ? $old_wc_settings['upload_path'] : 'woocommerce';
		$wc_path_id     = ( isset( $old_wc_settings['upload_path_id'] ) && ! empty( $old_wc_settings['upload_path_id'] ) ) ? $old_wc_settings['upload_path_id'] : false;


		$url      = Main::$api_url . '/partition?expand=base_path';
		$headers  = [
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json'
		];
		$response = wp_remote_get( $url, [
			'headers' => $headers,
			'timeout' => 60000
		] );

		if ( ! is_wp_error( $response ) ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code == 200 ) {
				$body             = json_decode( wp_remote_retrieve_body( $response ) );
				$partition        = $body['0'];
				$browse_id        = $partition->root->id;
				$partition_domain = $partition->base_path;
				$pattern          = sprintf( '/%s\/p\/.*/', Main::$domain );
				$subst            = '';
				$domain_match     = preg_match( $pattern, $partition_domain, $matches );
				if ( $domain_match ) {
					$domain                = preg_replace( $pattern, $subst, $partition_domain, 1 );
					$new_options['domain'] = sprintf( '%s%s/', $domain, Main::$domain );
				} else {
					$new_options['domain'] = $partition_domain;
				}

				// Add default media path
				$sub_folders   = explode( '/', rtrim( $media_path, '/' ) );
				$get_folder_id = Helper::recursive_browse( $sub_folders, $browse_id, $token );
				if ( $get_folder_id ) {
					$folder_id = $get_folder_id;
				} else {
					$folder_id = $browse_id;
				}
				$upload_path_id = $folder_id;

				// Add default backup path
				$sub_folders   = explode( '/', rtrim( $backup_path, '/' ) );
				$get_folder_id = Helper::recursive_browse( $sub_folders, $browse_id, $token );
				if ( $get_folder_id ) {
					$folder_id = $get_folder_id;
				} else {
					$folder_id = $browse_id;
				}
				$backup_path_id = $folder_id;

				// Add default woocommerce path
				$sub_folders   = explode( '/', rtrim( $wc_path, '/' ) );
				$get_folder_id = Helper::recursive_browse( $sub_folders, $browse_id, $token );
				if ( $get_folder_id ) {
					$folder_id = $get_folder_id;
				} else {
					$folder_id = $browse_id;
				}
				$wc_path_id = $folder_id;

				if ( $upload_path_id ) {
					$media_settings = [
						'upload_path'    => $media_path,
						'upload_path_id' => $upload_path_id
					];

					if( $webhook_url ) {
						$new_url      = $webhook_url;
					} else {
						$new_url      = wp_generate_password( 10, false, false );
					}
					// Set webhook
					$partition_id = $partition->id;
					$url          = Main::$api_url . '/partition/' . $partition_id;
					$headers      = [
						'Authorization' => 'Bearer ' . $token,
						'Content-Type'  => 'application/json'
					];
					$body         = [
						'webhook' => Rewrite::get_webhook_url($new_url)
					];
					$response     = wp_remote_request( $url, [
							'method'  => 'PATCH',
							'headers' => $headers,
							'body'    => json_encode( $body ),
							'timeout' => 60000
						] );
					if ( ! is_wp_error( $response ) ) {
						// $body = json_decode(wp_remote_retrieve_body($response));
						$response_code = wp_remote_retrieve_response_code( $response );
						if ( $response_code == 200 ) {
							$media_settings['webhook_url'] = $new_url;
						}
					}
					update_option( sprintf( '%s-media-settings', Main::$slug ), $media_settings );

					$rewrite_ver = get_option( sprintf( '%s_rewrite_version_new', Main::$slug ), 1 );
					update_option( sprintf( '%s_rewrite_version_new', Main::$slug ), $rewrite_ver + 1 );
				}
				if ( $backup_path_id ) {

					$backup_settings = [
						'upload_path'    => $backup_path,
						'upload_path_id' => $backup_path_id,
					];

					update_option( sprintf( '%s-backup-settings', Main::$slug ), $backup_settings );
				}
				if ( $wc_path_id ) {
					$wc_settings = [
						'mirror_type'    => 'sync',
						'upload_path'    => $wc_path,
						'upload_path_id' => $wc_path_id,
						'active_time'    => 10800,
						'link_text'      => __( 'Download from mirror', Main::$text_domain ),
					];
					update_option( sprintf( '%s-woocommerce-settings', Main::$slug ), $wc_settings );
				}


			}
		}

		flush_rewrite_rules();

		return $new_options;
	}

	public function set_id() {
		self::$id   = sprintf( '%s-%s-group', Main::$slug, 'general' );
		self::$name = sprintf( '%s-%s-settings', Main::$slug, 'general' );
	}

	public function filter_default( $default, $option, $passed_default ) {
		if ( empty( $default ) ) {
			$old_settings = get_option( 'poshtiban_com_settings', [] );
			$default = $old_settings;
			$default['companion_url'] = sprintf('https://companion.%s', Main::$api_domain);
			$default['tus_url'] = sprintf('https://upload.%s/files', Main::$api_domain);

			if( array_key_exists('type', $old_settings) ) {
				unset($default['type']);
				$default['upload_type'] = $old_settings['type'];
			}
		}

		return $default;
	}
}

new General();