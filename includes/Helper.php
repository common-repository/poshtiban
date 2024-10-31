<?php

namespace Poshtiban;

use Poshtiban\Database\Database;
use Poshtiban\Media\Attachment\Attachment;

class Helper {

	private static $remote_upload_statues = [
		10 => 'STATUS_PENDING',
		20 => 'STATUS_STARTING',
		30 => 'STATUS_PROCESSING',
		40 => 'STATUS_DONE',
		90 => 'STATUS_FAILED',
	];

	/**
	 * Render a view template
	 *
	 * @param string $template_name
	 * @param string $template_path
	 * @param array $args
	 */
	public static function view( $template_name, $template_path = '', $args = [] ) {
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args );
		}

		$path = sprintf( '%s/includes/view/', Bootstrap::$path );
		if ( ! empty( $template_path ) ) {
			$path .= sprintf( '%s/', $template_path );
		}

		$slug = Main::$slug;
		$text_domain = Main::$text_domain;
		$name = Main::$name;

		if ( file_exists( sprintf( '%s%s.php', $path, $template_name ) ) ) {
			$template = apply_filters(sprintf('%s_view_template_path', Main::$slug), sprintf( '%s%s.php', $path, $template_name ));
			include $template;
		}
	}

	/**
	 * Get plugin option
	 *
	 * @param $name
	 * @param $group
	 * @param bool $default
	 *
	 * @return bool|mixed
	 */
	public static function get_option( $name, $group, $default = false ) {
		if ( strpos( $group, '-settings' ) !== false ) {
			$option = get_option( $group, [] );
		} else {
			$option = get_option( sprintf( '%s-%s-settings', Main::$slug, $group ), [] );
		}
		if ( is_array( $option ) && array_key_exists( $name, $option ) ) {
			return $option[ $name ];
		}

		return $default;
	}

	/**
	 * @param array $folders
	 * @param $browse_id
	 * @param bool $token
	 *
	 * @return bool
	 */
	public static function recursive_browse( array $folders, $browse_id, $token = false ) {
		if ( ! $token ) {
			$token = self::get_option( 'token', 'general' );
		}
		if ( empty( $folders ) ) {
			return $browse_id;
		} else {
			foreach ( $folders as $folder_key => $folder_name ) {
				$url      = sprintf( '%s/folder/browse/%s', Main::$api_url, $browse_id );
				$headers  = [
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json'
				];
				$response = wp_remote_get( $url, [
					'headers' => $headers,
					'timeout' => 60000
				] );
				if ( is_wp_error( $response ) ) {
					return false;
				} else {
					$response_code = wp_remote_retrieve_response_code( $response );
					if ( $response_code == 200 ) {
						$body        = json_decode( wp_remote_retrieve_body( $response ) );
						$children    = $body->children;
						$child_names = [];
						foreach ( $children as $child ) {
							$child_names[ $child->id ] = $child->name;
						}
						if ( in_array( $folder_name, $child_names ) ) {
							$folder_id = array_search( $folder_name, $child_names );
							unset( $folders[ $folder_key ] );

							return self::recursive_browse( $folders, $folder_id, $token );
						} else {
							$mkdir_url = Main::$api_url . '/folder';
							$body_args = [
								'parent_id' => $browse_id,
								'name'      => $folder_name,
							];
							$response  = wp_remote_post( $mkdir_url, [
								'headers' => $headers,
								'body'    => json_encode( $body_args ),
								'timeout' => 60000
							] );
							if ( is_wp_error( $response ) ) {
								return false;
							} else {
								$response_code = wp_remote_retrieve_response_code( $response );
								if ( $response_code == 201 ) {
									$body      = json_decode( wp_remote_retrieve_body( $response ) );
									$folder_id = $body->id;
									unset( $folders[ $folder_key ] );

									return self::recursive_browse( $folders, $folder_id, $token );
								} else {
									return false;
								}
							}
						}
					} else {
						return false;
					}
				}
			}
		}

		return false;
	}


	/**
	 * Get size information for all currently-registered image sizes.
	 *
	 * @return array $sizes Data for all currently-registered image sizes.
	 * @uses   get_intermediate_image_sizes()
	 *
	 * @global $_wp_additional_image_sizes
	 */
	public static function get_image_sizes() {
		global $_wp_additional_image_sizes;

		$sizes = [];

		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, [ 'thumbnail', 'medium', 'medium_large', 'large' ] ) ) {
				$width  = get_option( "{$_size}_size_w", false );
				$height = get_option( "{$_size}_size_h", false );
				$crop   = get_option( "{$_size}_crop", null );
				if ( $width !== false && $height !== false ) {
					$sizes[ $_size ]['width']  = $width;
					$sizes[ $_size ]['height'] = $height;
					$sizes[ $_size ]['crop']   = (bool) $crop;
				}
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = [
					'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
				];
			}
		}

		return $sizes;
	}

	/**
	 * Return image sizes with applying ration
	 *
	 * @param $width
	 * @param $height
	 *
	 * @return array
	 */
	public static function get_constrain_sizes( $width, $height, $return_dupicate_sizes = false ) {
		$sizes = self::get_image_sizes();

		/**
		 * Filters the "BIG image" threshold value.
		 *
		 * If the original image width or height is above the threshold, it will be scaled down. The threshold is
		 * used as max width and max height. The scaled down image will be used as the largest available size, including
		 * the `_wp_attached_file` post meta value.
		 *
		 * Returning `false` from the filter callback will disable the scaling.
		 *
		 * @param int $threshold The threshold value in pixels. Default 2560.
		 * @param array $imagesize {
		 *     Indexed array of the image width and height in pixels.
		 *
		 * @type int $0 The image width.
		 * @type int $1 The image height.
		 * }
		 *
		 * @param string $file Full path to the uploaded image file.
		 * @param int $attachment_id Attachment post ID.
		 *
		 * @since 5.3.0
		 *
		 */
		$imagesize = [ $width, $height ];
		$threshold = (int) apply_filters( 'big_image_size_threshold', 2560, $imagesize, null, null );
		if ( $threshold && ( $width > $threshold || $height > $threshold ) ) {
			$sizes['scaled'] = [
				'width'         => $threshold,
				'height'        => $threshold,
				'crop'          => false,
				'use_size_name' => true
			];
		}

		foreach ( $sizes as $name => $size ) {
			$dims = image_resize_dimensions( $width, $height, $size['width'], $size['height'], $size['crop'] );
			list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;
			if ( $dst_w === null && $dst_h === null ) {
				unset( $sizes[ $name ] );
			} else {
				$sizes[ $name ]['width']  = $dst_w;
				$sizes[ $name ]['height'] = $dst_h;
			}
		}

		// Remove duplicate sizes
		if ( ! $return_dupicate_sizes ) {
			$result = [];
			foreach ( $sizes as $key => $size ) {
				if ( ! in_array( $size, $result ) ) {
					$result[ $key ] = $size;
				}
			}

			$sizes = $result;
		}


		return $sizes;
	}


	/**
	 * Get size information for a specific image size.
	 *
	 * @param string $size The image size for which to retrieve data.
	 *
	 * @return bool|array $size Size data about an image size or false if the size doesn't exist.
	 * @uses   self::get_image_sizes()
	 *
	 */
	public static function get_image_size( $size ) {
		$sizes = self::get_image_sizes();
		if ( ! is_array( $size ) && isset( $sizes[ $size ] ) && ! empty( $sizes[ $size ] ) ) {
			return $sizes[ $size ];
		}

		return false;
	}

	/**
	 * Get the width of a specific image size.
	 *
	 * @param string $size The image size for which to retrieve data.
	 *
	 * @return bool|string $size Width of an image size or false if the size doesn't exist.
	 * @uses   self::get_image_size()
	 *
	 */
	public static function get_image_width( $size ) {
		if ( ! $size = self::get_image_size( $size ) ) {
			return false;
		}

		if ( isset( $size['width'] ) ) {
			return $size['width'];
		}

		return false;
	}

	/**
	 * Get the height of a specific image size.
	 *
	 * @param string $size The image size for which to retrieve data.
	 *
	 * @return bool|string $size Height of an image size or false if the size doesn't exist.
	 * @uses   self::get_image_size()
	 *
	 */
	public static function get_image_height( $size ) {
		if ( ! $size = self::get_image_size( $size ) ) {
			return false;
		}

		if ( isset( $size['height'] ) ) {
			return $size['height'];
		}

		return false;
	}

	/**
	 * Upload a url to cloud
	 *
	 * @param $url
	 * @param $folder_id
	 * @param string $path
	 *
	 * @return array
	 */
	public static function remote_upload( $url, $folder_id, $path = '', $remove_path = '', $args = [] ) {
		/*if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return [
				'success' => false,
				'message' => __( 'Please enter a valid remote url', Main::$text_domain )
			];
		}*/
		$url = urldecode($url);

		$token     = self::get_option( 'token', 'general' );
		$api_url   = Main::$api_url . '/file';
		$headers   = [
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json'
		];
		$file_name = basename( $url );
		$body_post = [
			'name'       => $file_name,
			'folder_id'  => $folder_id,
			'size'       => 0,
			'public'     => self::get_option( 'upload_type', 'general' ),
			'remote_url' => $url,
		];

		$file_type = wp_check_filetype( basename( $url ), null );
		if ( 0 === strpos( $file_type['type'], 'image/' ) ) {
			list( $width, $height, $type, $attr ) = getimagesize( $url );
			$body_post['imageSizes'] = self::get_constrain_sizes( $width, $height );
			$body_post['original_size'] = [
				'width' => $width,
				'height' => $height,
			];
		}


		if ( ! empty( $path ) ) {
			$body_post['path'] = $path;
		}

		$response = wp_remote_post( $api_url, [
			'headers' => $headers,
			'body'    => json_encode( $body_post ),
			'timeout' => 60000
		] );
		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => $response->get_error_message()
			];
		} else {
			$result_code = wp_remote_retrieve_response_code( $response );
			if ( $result_code == 201 ) {
				$file   = json_decode( wp_remote_retrieve_body( $response ) );
				$remote = self::handle_remote_upload_update( $file, 'STATUS_PENDING', $remove_path, $args );
				if ( $remote ) {
					return [
						'success' => true,
						'message' => __( 'File added to remote upload queue', Main::$text_domain ),
						'file'    => $file
					];
				}

				return [
					'success' => false,
					'message' => __( 'Adding file to remote upload queue failed', Main::$text_domain )
				];
			} else {
				$body = json_decode( wp_remote_retrieve_body( $response ) );

				return [
					'success' => false,
					'message' => $body->message
				];
			}
		}
	}

	/**
	 * Upload a form data to cloud
	 *
	 * @param $file_content
	 * @param string $folder_id
	 * @param string $path
	 *
	 * @param string $file_name
	 * @param string $content_type
	 *
	 * @return array
	 */
	public static function simple_upload(
		$file_content,
		$folder_id,
		$path = '',
		$file_name = '',
		$content_type = 'application/zip'
	) {

		$token   = self::get_option( 'token', 'general' );
		$api_url = Main::$api_url . '/file-upload';


		$post_fields = [
			'folder_id'     => $folder_id,
			'partitionPath' => $path,
		];

		if ( empty( $file_name ) ) {
			$post_fields['generate_name'] = 'uuid';
		}
		$boundary = wp_generate_password( 24 );
		$headers  = [
			'Authorization' => 'Bearer ' . $token,
			'content-type'  => 'multipart/form-data; boundary=' . $boundary
		];
		$payload  = '';
		foreach ( $post_fields as $name => $value ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";
			$payload .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
			$payload .= $value;
			$payload .= "\r\n";
		}
		if ( $file_content ) {
			$payload .= '--' . $boundary;
			$payload .= "\r\n";
			$payload .= 'Content-Disposition: form-data; name="' . 'file' . '"; filename="' . $file_name . '"' . "\r\n";
			$payload .= 'Content-Type: application/zip' . "\r\n";
			$payload .= "\r\n";
			$payload .= $file_content;
			$payload .= "\r\n";
		}
		$payload  .= '--' . $boundary . '--';
		$response = wp_remote_post( $api_url, [
			'headers' => $headers,
			'body'    => $payload,
		] );

		// $response = wp_remote_post( $api_url, $body_post );
		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => '$response->get_error_message()'
			];
		} else {
			$result_code = wp_remote_retrieve_response_code( $response );
			if ( $result_code == 200 ) {
				$file = json_decode( wp_remote_retrieve_body( $response ) );
				if ( $file ) {
					return [
						'success' => true,
						'message' => __( 'File Uploaded successfully', Main::$text_domain )
					];
				}

				return [
					'success' => false,
					'message' => __( 'Adding file to remote upload queue failed', Main::$text_domain )
				];
			} else {
				$body = json_decode( wp_remote_retrieve_body( $response ) );

				return [
					'success' => false,
					'message' => $body->message
				];
			}
		}
	}

	/**
	 * update remote upload list after any update for it's items
	 *
	 * @param $resource
	 * @param $status
	 * @param string $path
	 *
	 * @return bool
	 */
	public static function handle_remote_upload_update( $resource, $status, $path = '', $args = [] ) {
		if ( ! in_array( $status, self::$remote_upload_statues ) ) {
			return false;
		}

		$remote_upload_queue_list = get_option( sprintf( '%s_remote_upload_queue', Main::$slug ), [] );
		$file_id = $resource->id;

		if ( $status === 'STATUS_DONE' && array_key_exists( $file_id, $remote_upload_queue_list ) ) {
			$remove_path     = ( isset( $remote_upload_queue_list[ $file_id ]['path'] ) && ! empty( $remote_upload_queue_list[ $file_id ]['path'] ) ) ? $remote_upload_queue_list[ $file_id ]['path'] : false;
			$attachment_args = ( isset( $remote_upload_queue_list[ $file_id ]['args'] ) && ! empty( $remote_upload_queue_list[ $file_id ]['args'] ) ) ? $remote_upload_queue_list[ $file_id ]['args'] : false;
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
						$sizes     = self::get_constrain_sizes( $width, $height );
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

					}

				endwhile;
				wp_reset_postdata();
			}

		} else {
			if ( $status === 'STATUS_PENDING' ) {
				$remote_upload_queue_list[ $file_id ] = [
					'data'   => $resource,
					'status' => $status,
				];
				if ( isset( $path ) && ! empty( $path ) ) {
					$remote_upload_queue_list[ $file_id ]['path'] = $path;
				}
				if ( ! empty( $args ) ) {
					$remote_upload_queue_list[ $file_id ]['args'] = $args;
				}
			} else {
				$remote_upload_queue_list[ $file_id ]['status'] = $status;
			}

		}
		update_option( sprintf( '%s_remote_upload_queue', Main::$slug ), $remote_upload_queue_list );

		return true;
	}

	/**
	 * Convert persian numbers to english numbers
	 *
	 * @param $number
	 *
	 * @return string|string[]
	 */
	public static function en_number( $number ) {
		$en = [ "0", "1", "2", "3", "4", "5", "6", "7", "8", "9" ];
		$fa = [ "۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹" ];

		return str_replace( $fa, $en, $number );
	}

	/**
	 * Get attachment scaled images by attachment ID
	 *
	 * @param $attachment_id
	 * @param string $size
	 *
	 * @return bool|false|string
	 */
	public static function get_scaled_image_path( $attachment_id, $size = 'thumbnail' ) {
		$file = get_attached_file( $attachment_id, true );
		if ( empty( $size ) || $size === 'full' ) {
			// for the original size get_attached_file is fine
			return realpath( $file );
		}
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return false; // the id is not referring to a media
		}
		$info = image_get_intermediate_size( $attachment_id, $size );
		if ( ! is_array( $info ) || ! isset( $info['file'] ) ) {
			return false; // probably a bad size argument
		}

		return realpath( str_replace( wp_basename( $file ), $info['file'], $file ) );
	}

	/**
	 * Detect debug mode is active or not
	 *
	 * @return bool
	 */
	public static function isDebugMode() {
		$isAdmin = current_user_can('administrator');
		$isDebug = Helper::get_option('debug_mode', 'general', 'no') === 'yes';
		return $isAdmin && $isDebug;
	}
}