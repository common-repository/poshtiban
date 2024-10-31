<?php


namespace Poshtiban\Media;

use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Options\Settings\Media;

/**
 * Class Menu
 * @package Poshtiban\Media
 */
class Rewrite {

	/**
	 * Menu constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'add_rewrite' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
		add_action('update_option_permalink_structure', [ $this, 'update_webhook_url' ], 10, 3 );
	}

	public function add_rewrite() {
		$current_rewrite_version = get_option( sprintf( '%s_rewrite_version_old', Main::$slug ), 0 );
		$hook                    = Helper::get_option( 'webhook_url', 'media' );
		$regex                   = sprintf( '%s/%s', Main::$slug, $hook );
		$query                   = sprintf( 'index.php?%s_web_hook=%s', Main::$slug, $hook );
		add_rewrite_rule( $regex, $query, 'top' );
		$rewrite_ver = get_option( sprintf( '%s_rewrite_version_new', Main::$slug ), 1 );
		if ( $current_rewrite_version < $rewrite_ver ) {
			flush_rewrite_rules();
			update_option( sprintf( '%s_rewrite_version_old', Main::$slug ), $rewrite_ver );
		}
	}

	public function add_query_vars( $query_vars ) {
		$query_vars[] = sprintf( '%s_web_hook', Main::$slug );

		return $query_vars;
	}

	public function template_redirect() {
		$hook     = Helper::get_option( 'webhook_url', 'media' );
		$hook_key = sprintf( '%s_web_hook', Main::$slug );
		global $wp;
		if ( isset( $wp->query_vars[ $hook_key ] ) && $wp->query_vars[ $hook_key ] == $hook ) {
			if( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
				// get the raw POST data
				$raw_data   = file_get_contents( "php://input" );
				$event_data = json_decode( $raw_data );
				if ( $event_data ) {
					$resource    = $event_data->resource;
					$event_name  = 'EVENT_REMOTE_DOWNLOAD';
					$status_code = $event_data->status_code;
					$status_name = $event_data->status;
					do_action( sprintf( '%s_webhook_triggered', Main::$slug ), $resource, $event_name, $status_name,
						$status_code );
				}
				exit();
			} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
				wp_send_json([
					'success' => true,
					'message' => __('Webhook page is available', Main::$text_domain)
				]);
			}

		}
	}

	public static function get_webhook_url($path = false) {
		if ( ! $path ) {
			$path = Helper::get_option( 'webhook_url', 'media' );
		}
		$permalinks = get_option( 'permalink_structure' );
		if ( empty( $permalinks ) ) {
			$webhook_url = sprintf( '%s/?%s_web_hook=%s', get_bloginfo( 'url' ), Main::$slug, $path );
		} else {
			$webhook_url = sprintf( '%s/%s/%s', get_bloginfo( 'url' ), Main::$slug, $path );
		}

		return $webhook_url;
	}

	public function update_webhook_url( $old_value, $new_value, $option_name) {
		$url             = Main::$api_url . '/partition';
		$token = Helper::get_option( 'token', 'general' );
		$headers         = [
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json'
		];
		$response        = wp_remote_get( $url, [
			'headers' => $headers,
			'timeout' => 60000
		] );
		if ( !is_wp_error( $response ) ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code == 200 ) {
				$body      = json_decode( wp_remote_retrieve_body( $response ) );
				$partition = $body['0'];
				$partition_id = $partition->id;
				$url             = Main::$api_url .'/partition/'.$partition_id;
				$headers  = [
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json'
				];
				$body = [
					'webhook'   => self::get_webhook_url()
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
			}
		}
	}
}