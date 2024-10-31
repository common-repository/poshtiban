<?php


namespace Poshtiban\Options\Settings;


use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Media\Rewrite;

class Media extends SettingsAbstract {
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
				$new_path = $new_options['upload_path'];
				$url      = Main::$api_url . '/partition';
				$token           = $general_settings['token'];
				$headers  = [
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json'
				];
				$response = wp_remote_get( $url, [
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

						if ( isset( $new_path ) && ! empty( $new_path ) ) {
							$sub_folders   = explode( '/', rtrim( $new_path, '/' ) );
							$get_folder_id = Helper::recursive_browse( $sub_folders, $browse_id, $token );
							error_log(json_encode([$sub_folders, $browse_id, $token]));
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


			if ( empty( $old_settings['webhook_url'] ) ) {
				$new_url = wp_generate_password(10, false, false);
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
						$partition_id = $partition->id;
						$url             = Main::$api_url . '/partition/'.$partition_id;
						$headers  = [
							'Authorization' => 'Bearer ' . $token,
							'Content-Type'  => 'application/json'
						];
						$body = [
							'webhook'   => Rewrite::get_webhook_url($new_url)
						];
						$response = wp_remote_request(
							$url,
							[
								'method'  => 'PATCH',
								'headers' => $headers,
								'body'    => json_encode( $body ),
								'timeout' => 60000
							]
						);
						if ( is_wp_error( $response ) ) {
							$error_message = $response->get_error_message();
						} else {
							// $body = json_decode(wp_remote_retrieve_body($response));
							$response_code = wp_remote_retrieve_response_code( $response );
							if ( $response_code == 200 ) {
								$new_options['webhook_url'] = $new_url;
								flush_rewrite_rules();
								$rewrite_ver = get_option( sprintf('%s_rewrite_version_new', Main::$slug), 1 );
								update_option(sprintf('%s_rewrite_version_new', Main::$slug), $rewrite_ver + 1);
							}
						}
					}
				}
			} else {
				$new_options['webhook_url'] = $old_settings['webhook_url'];
				flush_rewrite_rules();
				$rewrite_ver = get_option( sprintf('%s_rewrite_version_new', Main::$slug), 1 );
				update_option(sprintf('%s_rewrite_version_new', Main::$slug), $rewrite_ver + 1);
			}
		}

		return $new_options;
	}

	public function set_id() {
		self::$id = sprintf( '%s-%s-group', Main::$slug, 'media' );
		self::$name = sprintf( '%s-%s-settings', Main::$slug, 'media' );
	}

	public function filter_default( $default, $option, $passed_default ) {
		if ( empty( $default ) ) {
			$default = get_option( 'poshtiban_com_media_settings', [] );
			$backup_old_settings = get_option( 'poshtiban_com_backup_settings', [] );
			if( array_key_exists('webhook_url', $backup_old_settings) ) {
				$default['webhook_url'] = $backup_old_settings['webhook_url'];
			}
		}

		return $default;
	}
}

new Media();