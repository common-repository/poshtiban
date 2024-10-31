<?php


namespace Poshtiban\Options\Settings;


use Poshtiban\Helper;
use Poshtiban\Main;

class Woocommerce extends SettingsAbstract {
	protected static $id;
	protected static $name;

	public function sanitize( $new_options ) {
		$general_settings     = get_option( sprintf( '%s-general-settings', Main::$slug ) );
		if( array_key_exists('token', $general_settings) && $general_settings['token'] ) {
			$old_settings     = get_option( self::get_options_name() );

			if( !$new_options['upload_path_id'] ) {
				$new_options['upload_path_id'] = (isset($old_settings['upload_path_id']) && !empty($old_settings['upload_path_id'])) ? $old_settings['upload_path_id'] : '';
			}

			if ( $old_settings['upload_path'] != $new_options['upload_path'] || empty( $new_options['upload_path'] ) || empty( $old_settings['upload_path_id'] ) ) {
				$new_backup_path = $new_options['upload_path'];
				$url             = Main::$api_url . '/partition';
				$token           = $general_settings['token'];
				$headers         = [
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json'
				];
				$response        = wp_remote_get( $url, [
					'headers' => $headers,
					'timeout' => 60000
				] );
				if ( is_wp_error( $response ) ) {
					$new_options['upload_path'] = $old_settings['upload_path'];
				} else {
					$response_code = wp_remote_retrieve_response_code( $response );
					if ( $response_code == 200 ) {

						$body      = json_decode( wp_remote_retrieve_body( $response ) );
						$partition = $body['0'];
						$browse_id = $partition->root->id;

						if ( isset( $new_backup_path ) && ! empty( $new_backup_path ) ) {
							$sub_folders   = explode( '/', rtrim( $new_backup_path, '/' ) );
							$get_folder_id = Helper::recursive_browse( $sub_folders, $browse_id, $token );
							if ( $get_folder_id ) {
								$folder_id = $get_folder_id;
							} else {
								$folder_id = $browse_id;
							}
						} else {
							$folder_id = $browse_id;
						}
						$new_options['upload_path_id'] = $folder_id;
					} else {
						$new_options['upload_path'] = $old_settings['upload_path'];
					}
				}
			}
		}

		return $new_options;
	}

	public function set_id() {
		self::$id = sprintf( '%s-%s-group', Main::$slug, 'woocommerce' );
		self::$name = sprintf( '%s-%s-settings', Main::$slug, 'woocommerce' );
	}

	public function filter_default( $default, $option, $passed_default ) {
		if ( empty( $default ) ) {
			$default = [];
			$old_settings = get_option( 'poshtiban_com_wc_settings', [] );

			if( array_key_exists('woocommerce_upload_path', $old_settings) ) {
				$default['upload_path'] = $old_settings['woocommerce_upload_path'];
			}
			if( array_key_exists('woocommerce_upload_path_id', $old_settings) ) {
				$default['upload_path_id'] = $old_settings['woocommerce_upload_path_id'];
			}
			if( array_key_exists('link_text', $old_settings) ) {
				$default['download_link_title'] = $old_settings['link_text'];
			}
			if( array_key_exists('woocommerce_time', $old_settings) ) {
				$default['active_time'] = $old_settings['woocommerce_time'];
			}
			if( array_key_exists('mirror_type', $old_settings) ) {
				$default['mirror_type'] = $old_settings['mirror_type'];
			}
		}

		return $default;
	}

}

new Woocommerce();