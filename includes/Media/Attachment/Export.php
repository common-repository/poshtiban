<?php

namespace Poshtiban\Media\Attachment;

use Poshtiban\Database\Database;
use Poshtiban\Helper;
use Poshtiban\Main;

/**
 * Export media from Wordpress to cloud
 *
 * Class Export
 * @package Poshtiban\Media\Attachment
 */
class Export {

	/**
	 * Export constructor.
	 */
	public function __construct() {
		add_filter( 'bulk_actions-upload', [ $this, 'add_bulk_action' ] );
		add_filter( 'handle_bulk_actions-upload', [ $this, 'handle_bulk_action' ], 10, 3 );
		add_action( sprintf( 'wp_ajax_%s_remote_upload', Main::$slug ), [ $this, 'ajax_export' ] );
		add_action( sprintf( 'wp_ajax_%s_update_remote_file_status', Main::$slug ),
			[ $this, 'update_remote_file_status' ] );
	}


	/**
	 * Add new action to media page's bulk actions
	 *
	 * @param $bulk_actions
	 *
	 * @return array
	 */
	public function add_bulk_action( $bulk_actions ) {
		$bulk_actions[ sprintf( '%s-upload', Main::$slug ) ] = sprintf( __( 'Upload to %s', Main::$text_domain ),
			Main::$name );

		return $bulk_actions;
	}


	/**
	 *  Upload a Wordpress media url to cloud with ajax
	 */
	public function ajax_export() {
		$attachment_id = ( isset( $_POST['attachment_id'] ) && ! empty( $_POST['attachment_id'] ) ) ? intval( $_POST['attachment_id'] ) : false;
		if ( $attachment_id ) {
			$upload = $this->export_attachment( $attachment_id, [ 'replace_urls' => true ] );
			if ( $upload['success'] ) {
				update_post_meta( $attachment_id, Attachment::get_meta_name( 'id' ), $upload['file']->id );
			}
		} else {
			$url = ( isset( $_POST['url'] ) && ! empty( $_POST['url'] ) ) ? esc_url_raw( $_POST['url'] ) : false;
			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				wp_send_json( [
					'success' => false,
					'message' => __( 'Please enter a valid remote url', Main::$text_domain )
				] );
			}

			if ( ( isset( $_POST['folder_id'] ) && ! empty( $_POST['folder_id'] ) ) ) {
				$folder_id = sanitize_text_field( $_POST['folder_id'] );
			} else {
				$folder_id = Helper::get_option( 'upload_path_id', 'media' );
			}

			if ( ( isset( $_POST['path'] ) && ! empty( $_POST['path'] ) ) ) {
				$path = sanitize_text_field( $_POST['path'] );
			} else {
				$year  = date( 'Y' );
				$month = date( 'm' );
				$path  = sprintf( '/%s/%s', $year, $month );
			}
			$upload = Helper::remote_upload( $url, $folder_id, $path );
		}

		wp_send_json( $upload );
	}


	/**
	 * Handle upload action of media page's bulk actions
	 *
	 * @param $send_back
	 * @param $do_action
	 * @param $attachment_ids
	 *
	 * @return string
	 */
	public function handle_bulk_action( $send_back, $do_action, $attachment_ids ) {
		if ( $do_action !== sprintf( '%s-upload', Main::$slug ) ) {
			return $send_back;
		}

		$result = [];
		foreach ( $attachment_ids as $attachment_id ) {
			$cloud_id = get_post_meta( $attachment_id, Attachment::get_meta_name( 'id' ), true );
			if ( ! $cloud_id ) {
				$remove_path  = [ get_attached_file( $attachment_id ) ];
				$remove_paths = [];
				foreach ( Helper::get_image_sizes() as $size => $info ) {
					$path_exists = Helper::get_scaled_image_path( $attachment_id, $size );
					if ( $path_exists ) {
						$remove_paths[] = $path_exists;
					}
				}
				$attachment_url = wp_get_attachment_url( $attachment_id );
				$folder_id      = Helper::get_option( 'upload_path_id', 'media' );
				$year           = date( 'Y' );
				$month          = date( 'm' );
				$path           = sprintf( '/%s/%s', $year, $month );

				$upload = Helper::remote_upload( $attachment_url, $folder_id, $path,
					array_unique( array_merge( $remove_path, $remove_paths ) ) );
				if ( $upload['success'] ) {
					update_post_meta( $attachment_id, Attachment::get_meta_name( 'id' ), $upload['file']->id );
				}

				$result[ $attachment_id ] = $upload;
			} else {
				$result[ $attachment_id ] = [
					'success' => false,
					'message' => __( 'This is already a cloud file', Main::$text_domain )
				];
			}
		}

		$key = wp_generate_uuid4();
		update_option( $key, $result );

		$send_back = add_query_arg( sprintf( '%s_export_bulk_result', Main::$slug ), $key, $send_back );

		return $send_back;
	}


	/**
	 * Do all action that needed after an attachment exported from Wordpress to cloud
	 *
	 * @param $attachment_id
	 * @param $cloud_id
	 *
	 * @return array
	 */
	public function export_attachment( $attachment_id, $args = [] ) {
		$up_load_dir    = wp_upload_dir();
		$meta           = wp_get_attachment_metadata( $attachment_id );
		$attachment_url = wp_get_attachment_url( $attachment_id );
		$folder_id      = Helper::get_option( 'upload_path_id', 'media' );

		$remove_paths = [ get_attached_file( $attachment_id ) ];
		$file_parts   = pathinfo( get_attached_file( $attachment_id ) );
		foreach ( $meta['sizes'] as $name => $info ) {
			$remove_paths[] = sprintf( '%s/%s', $file_parts['dirname'], $info['file'] );
		}
		if ( isset( $meta['original_image'] ) && ! empty( $meta['original_image'] ) ) {
			$remove_paths[] = sprintf( '%s/%s', $file_parts['dirname'], $meta['original_image'] );
		}

		$upload = Helper::remote_upload( $attachment_url, $folder_id, $up_load_dir['subdir'], $remove_paths, $args );

		return $upload;
	}

	public function update_remote_file_status() {
		$file_id = ( isset( $_POST['file_id'] ) && ! empty( $_POST['file_id'] ) ) ? sanitize_text_field( $_POST['file_id'] ) : false;
		if( !$file_id ) {
			wp_send_json_error(__('File ID is empty', Main::$text_domain));
		}
		$url = sprintf('%s/file/%s', Main::$api_url, $file_id);
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
			$resource = json_decode( wp_remote_retrieve_body( $response ) );
			if ( $response_code == 200 ) {
				$remote_upload_queue_list = get_option( sprintf( '%s_remote_upload_queue', Main::$slug ), [] );
				$remove_path     = ( isset( $remote_upload_queue_list[ $file_id ]['path'] ) && ! empty( $remote_upload_queue_list[ $file_id ]['path'] ) ) ? $remote_upload_queue_list[ $file_id ]['path'] : false;
				$attachment_args = ( isset( $remote_upload_queue_list[ $file_id ]['args'] ) && ! empty( $remote_upload_queue_list[ $file_id ]['args'] ) ) ? $remote_upload_queue_list[ $file_id ]['args'] : false;
				$args     = [
					'posts_per_page' => - 1,
					'post_type'      => 'attachment',
					'post_status'    => 'any',
					'meta_key'       => Attachment::get_meta_name( 'id' ),
					'meta_value'     => $file_id
				];
				$my_query = new \WP_Query( $args );
				if ( $my_query->have_posts() ) {
					while ( $my_query->have_posts() ):
						$my_query->the_post();
						$attachment_id = get_the_ID();
						$metadata      = (array) $resource->metadata;
						if ( ! $metadata ) {
							list( $width, $height, $type, $attr ) = getimagesize( $resource->public_link );
							$metadata = Attachment::generate_attachment_metadata( get_the_ID(), false, [
								'width'  => $width,
								'height' => $height,
								'file'   => $resource,
							] );
						}
						$old_meta = wp_get_attachment_metadata($attachment_id);
						wp_update_attachment_metadata( $attachment_id, $metadata );

						$imagesSizes = ( isset( $resource->images ) && ! empty( $resource->images ) ) ? $resource->images : [];
						if ( ! empty( $imagesSizes ) ) {
							$sizes     = Helper::get_constrain_sizes( $width, $height );
							$fileParts = pathinfo( $resource->name );
							$cloud_url = [ 'full' => $resource->public_link ];
							foreach ( $sizes as $size_name => $size ) {
								$file_name = $fileParts['filename'] . '_' . $size['width'] . 'x' . $size['height'] . '.' . $fileParts['extension'];
								if ( $size['use_size_name'] ) {
									$file_name = $fileParts['filename'] . '-' . $size_name . '.' . $fileParts['extension'];
								}

								$cloud_url[$size_name] = str_replace( $resource->name, $file_name,$resource->public_link );
							}

							update_post_meta( get_the_ID(), Attachment::get_meta_name( 'url' ), $cloud_url );

							if ( isset( $attachment_args['replace_urls'] ) && ! empty( isset( $attachment_args['replace_urls'] ) ) ) {
								// Replace Wordpress url with cloud urls
								$upload_dir     = wp_get_upload_dir();
								$upload_dir_url = sprintf( '%s/', $upload_dir['baseurl'] );
								$meta_data      = wp_get_attachment_metadata( $attachment_id );
								$fileParts      = pathinfo( $old_meta['file'] );

								foreach ( $cloud_url as $size_name => $replace_url ) {
									$find_url = false;

									if ( $size_name === 'full' ) {
										$find_url = sprintf( '%s%s', $upload_dir_url, $old_meta['file'] );
									} else {
										if ( array_key_exists( $size_name, $meta_data['sizes'] ) ) {
											$find_url = sprintf( '%s%s/%s', $upload_dir_url, $fileParts['dirname'],
												$old_meta['sizes'][ $size_name ]['file'] );
										}
									}
									if ( $find_url ) {
										Database::search_and_replace( $find_url, $replace_url );
									}
								}
							}

							if ( $remove_path ) {
								if ( is_array( $remove_path ) ) {
									foreach ( $remove_path as $item ) {
										unlink( $item );
									}
								} else {
									unlink( $remove_path );
								}
								/*global $wp_filesystem;
								$wp_filesystem->delete( $path );*/
							}
							unset( $remote_upload_queue_list[ $file_id ] );
						}

					endwhile;
					wp_reset_postdata();

					update_option( sprintf( '%s_remote_upload_queue', Main::$slug ), $remote_upload_queue_list );
					wp_send_json_success();
				}
			} else {
				wp_send_json_error($resource->message);
			}
		}

		wp_send_json_error($response->get_error_message());
	}
}