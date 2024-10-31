<?php


namespace Poshtiban\Media\Attachment;


use Poshtiban\Bootstrap;
use Poshtiban\Database\Database;
use Poshtiban\Helper;
use Poshtiban\Main;

class Attachment {

	/**
	 * Attachment constructor.
	 */
	public function __construct() {
		add_action( sprintf( 'wp_ajax_%s_add_attachment_by_uppy', Main::$slug ), [ $this, 'add_attachment_by_uppy' ] );
		add_filter( 'attachment_fields_to_edit', [ $this, 'show_cloud_id_in_media_edit' ], 10, 2 );
		add_filter( 'attachment_fields_to_save', [ $this, 'save_cloud_id' ], 10, 2 );
		add_filter( 'wp_get_attachment_url', [ $this, 'change_attachment_url' ], 10, 2 );
		add_filter( 'image_downsize', [ $this, 'image_downsize' ], 10, 3 );
		add_filter( 'delete_attachment', [ $this, 'delete_attachment' ], 10, 3 );
		add_filter( 'manage_media_columns', [ $this, 'add_media_list_column' ] );
		add_action( 'manage_media_custom_column', [ $this, 'media_list_column_content' ], 10, 3 );
		add_filter( 'wp_calculate_image_srcset', [ $this, 'calculate_image_srcset' ], 10, 5 );
		add_action( sprintf( '%s_webhook_triggered', Main::$slug ), [ $this, 'update_remote_upload' ], 10, 4 );
		add_filter( 'wp_get_attachment_metadata', [ $this, 'filter_attachment_metadata' ], 10, 2 );

		$this->includes();
		$this->init();
	}


	/**
	 * include dependencies
	 */
	public function includes() {
		include_once Bootstrap::$path . 'includes/Media/Attachment/Import.php';
		include_once Bootstrap::$path . 'includes/Media/Attachment/Export.php';
	}

	/**
	 * Instantiate plugin classes
	 */
	public function init() {
		new Import();
		new Export();
	}

	/**
	 *  Add new attachment after uppy upload completed.
	 */
	public function add_attachment_by_uppy() {

		$upload_id    = ( isset( $_POST['upload_id'] ) && ! empty( $_POST['upload_id'] ) ) ? sanitize_text_field($_POST['upload_id']) : false;
		$width        = ( isset( $_POST['width'] ) && ! empty( $_POST['width'] ) ) ? intval($_POST['width']) : false;
		$height       = ( isset( $_POST['height'] ) && ! empty( $_POST['height'] ) ) ? intval($_POST['height']) : false;
		$file         = $this->get_file_by_upload_id( $upload_id );

		$file_name      = ( isset( $file->name ) && ! empty( $file->name ) ) ? $file->name : false;
		$cloud_id       = ( isset( $file->id ) && ! empty( $file->id ) ) ? $file->id : false;
		$path           = ( isset( $_POST['path'] ) && ! empty( $_POST['path'] ) ) ? sanitize_text_field($_POST['path']) : false;
		$url            = ( isset( $file->public_link ) && ! empty( $file->public_link ) ) ? $file->public_link : false;
		$imagesSizes    = ( isset( $file->images ) && ! empty( $file->images ) ) ? $file->images : [];
		$parent_post_id = ( isset( $_POST['parent'] ) && ! empty( $_POST['parent'] ) ) ? intval(sanitize_text_field($_POST['parent'])) : false;

		if ( $file_name === false || $cloud_id === false ) {
			wp_send_json_error( __( 'File not added to wordpress. please upload it again!', Main::$text_domain ) );
		}

		$wp_file_type  = wp_check_filetype( $file_name, null );
		$attachment    = [
			'post_mime_type' => $wp_file_type['type'],
			'post_parent'    => $parent_post_id,
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $file_name ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];
		$attachment_id = wp_insert_attachment( $attachment, ltrim( $path, '/' ) . '/' . $file_name, $parent_post_id );
		if ( ! is_wp_error( $attachment_id ) ) {

			$metadata = self::generate_attachment_metadata( $attachment_id, false, [
				'width'  => $width,
				'height' => $height,
				'file'   => $file,
			] );
			if ( ! $metadata ) {
				$metadata = ( isset( $file->metadata ) && ! empty( $file->metadata ) ) ? (array) $file->metadata : false;
			}

			wp_update_attachment_metadata( $attachment_id, $metadata );
			update_post_meta( $attachment_id, self::get_meta_name( 'id' ), $cloud_id );

			$sizes     = get_intermediate_image_sizes();
			$cloud_url = [ 'full' => $url ];
			if ( ! empty( $imagesSizes ) ) {
				$images = Helper::get_constrain_sizes( $width, $height );
				foreach ( $sizes as $size ) {
					if ( array_key_exists( $size, $images ) ) {
						$fileParts = pathinfo( $file_name );
						$thumbName = $fileParts['filename'] . '_' . $images[ $size ]['width'] . 'x' . $images[ $size ]['height'] . '.' . $fileParts['extension'];
						//$cloud_url[ $size ] = $images[$size]['public_link'];
						$cloud_url[ $size ] = str_replace( $file_name, $thumbName, $url );
					}
				}
			}

			update_post_meta( $attachment_id, self::get_meta_name( 'url' ), $cloud_url );

			wp_send_json_success( __( 'File uploaded successfully', Main::$text_domain ) );
		}
		wp_send_json_error( __( 'File not added to wordpress. please upload it again!', Main::$text_domain ) );
	}

	/**
	 * Get file info by TUS upload ID
	 *
	 * @param $upload_id
	 *
	 * @return bool|string
	 */
	public function get_file_by_upload_id( $upload_id ) {
		$url      = sprintf( '%s/file/get-by-upload/%s', Main::$api_url, $upload_id );
		$token    = Helper::get_option( 'token', 'general' );
		$headers  = [
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json'
		];
		$response = wp_remote_request( $url, [
			'method'  => 'GET',
			'headers' => $headers,
			'timeout' => 60000
		] );

		if ( is_wp_error( $response ) ) {
			// $error_message = $response->get_error_message();

			return false;
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code == 200 ) {
				$body = json_decode( wp_remote_retrieve_body( $response ) );

				return $body;
			}

			return false;
		}
	}

	/**
	 * Get file info by cloud file ID
	 *
	 * @param $file_id
	 *
	 * @return bool|string
	 */
	public static function get_file_by_id( $file_id ) {
		$url      = sprintf( '%s/file/%s', Main::$api_url, $file_id );
		$token    = Helper::get_option( 'token', 'general' );
		$headers  = [
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json'
		];
		$response = wp_remote_request( $url, [
			'method'  => 'GET',
			'headers' => $headers,
			'timeout' => 60000
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code == 200 ) {
				$body = json_decode( wp_remote_retrieve_body( $response ) );

				return $body;
			}

			return false;
		}
	}

	/**
	 * Generate post thumbnail attachment meta data.
	 *
	 * redefinition for wp_generate_attachment_metadata function
	 *
	 * @param int $attachment_id Attachment Id to process.
	 * @param bool $file Filepath of the Attached image.
	 *
	 * @return mixed Metadata for attachment.
	 * @since 2.1.0
	 */
	public static function generate_attachment_metadata( $attachment_id, $file = false, $args = [] ) {
		$attachment = get_post( $attachment_id );
		$metadata   = [];
		$support    = false;
		$mime_type  = get_post_mime_type( $attachment );

		if ( preg_match( '!^image/!', $mime_type ) ) {
			$metadata = self::generate_image_meta( $args['width'], $args['height'], $args['file'] );
		} elseif ( wp_attachment_is( 'video', $attachment ) ) {
			$metadata = self::generate_video_meta( $args['file'] );
			$support  = current_theme_supports( 'post-thumbnails',
					'attachment:video' ) || post_type_supports( 'attachment:video', 'thumbnail' );
		} elseif ( wp_attachment_is( 'audio', $attachment ) ) {
			$metadata = self::generate_audio_meta( $args['file'] );
			$support  = current_theme_supports( 'post-thumbnails',
					'attachment:audio' ) || post_type_supports( 'attachment:audio', 'thumbnail' );
		}

		if ( $support && ! empty( $metadata['image']['data'] ) ) {
			// Check for existing cover.
			$hash   = md5( $metadata['image']['data'] );
			$posts  = get_posts( [
				'fields'         => 'ids',
				'post_type'      => 'attachment',
				'post_mime_type' => $metadata['image']['mime'],
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'meta_key'       => '_cover_hash',
				'meta_value'     => $hash
			] );
			$exists = reset( $posts );

			if ( ! empty( $exists ) ) {
				update_post_meta( $attachment_id, '_thumbnail_id', $exists );
			} else {
				$ext = '.jpg';
				switch ( $metadata['image']['mime'] ) {
					case 'image/gif':
						$ext = '.gif';
						break;
					case 'image/png':
						$ext = '.png';
						break;
				}
				$basename = str_replace( '.', '-', basename( $file ) ) . '-image' . $ext;
				$uploaded = wp_upload_bits( $basename, '', $metadata['image']['data'] );
				if ( false === $uploaded['error'] ) {
					$image_attachment = [
						'post_mime_type' => $metadata['image']['mime'],
						'post_type'      => 'attachment',
						'post_content'   => '',
					];
					/**
					 * Filters the parameters for the attachment thumbnail creation.
					 *
					 * @param array $image_attachment An array of parameters to create the thumbnail.
					 * @param array $metadata Current attachment metadata.
					 * @param array $uploaded An array containing the thumbnail path and url.
					 *
					 * @since 3.9.0
					 *
					 */
					$image_attachment = apply_filters( 'attachment_thumbnail_args', $image_attachment, $metadata,
						$uploaded );

					$sub_attachment_id = wp_insert_attachment( $image_attachment, $uploaded['file'] );
					add_post_meta( $sub_attachment_id, '_cover_hash', $hash );
					$attach_data = wp_generate_attachment_metadata( $sub_attachment_id, $uploaded['file'] );
					wp_update_attachment_metadata( $sub_attachment_id, $attach_data );
					update_post_meta( $attachment_id, '_thumbnail_id', $sub_attachment_id );
				}
			}
		} elseif ( 'application/pdf' === $mime_type ) {
			// Try to create image thumbnails for PDFs
			$fallback_sizes = [
				'thumbnail',
				'medium',
				'large',
			];

			/**
			 * Filters the image sizes generated for non-image mime types.
			 *
			 * @param array $fallback_sizes An array of image size names.
			 * @param array $metadata Current attachment metadata.
			 *
			 * @since 4.7.0
			 *
			 */
			$fallback_sizes = apply_filters( 'fallback_intermediate_image_sizes', $fallback_sizes, $metadata );

			$sizes                      = [];
			$_wp_additional_image_sizes = wp_get_additional_image_sizes();

			foreach ( $fallback_sizes as $s ) {
				if ( isset( $_wp_additional_image_sizes[ $s ]['width'] ) ) {
					$sizes[ $s ]['width'] = intval( $_wp_additional_image_sizes[ $s ]['width'] );
				} else {
					$sizes[ $s ]['width'] = get_option( "{$s}_size_w" );
				}

				if ( isset( $_wp_additional_image_sizes[ $s ]['height'] ) ) {
					$sizes[ $s ]['height'] = intval( $_wp_additional_image_sizes[ $s ]['height'] );
				} else {
					$sizes[ $s ]['height'] = get_option( "{$s}_size_h" );
				}

				if ( isset( $_wp_additional_image_sizes[ $s ]['crop'] ) ) {
					$sizes[ $s ]['crop'] = $_wp_additional_image_sizes[ $s ]['crop'];
				} else {
					// Force thumbnails to be soft crops.
					if ( ! 'thumbnail' === $s ) {
						$sizes[ $s ]['crop'] = get_option( "{$s}_crop" );
					}
				}
			}

			// Only load PDFs in an image editor if we're processing sizes.
			if ( ! empty( $sizes ) ) {
				$editor = wp_get_image_editor( $file );

				if ( ! is_wp_error( $editor ) ) { // No support for this type of file
					/*
					 * PDFs may have the same file filename as JPEGs.
					 * Ensure the PDF preview image does not overwrite any JPEG images that already exist.
					 */
					$dirname      = dirname( $file ) . '/';
					$ext          = '.' . pathinfo( $file, PATHINFO_EXTENSION );
					$preview_file = $dirname . wp_unique_filename( $dirname, wp_basename( $file, $ext ) . '-pdf.jpg' );

					$uploaded = $editor->save( $preview_file, 'image/jpeg' );
					unset( $editor );

					// Resize based on the full size image, rather than the source.
					if ( ! is_wp_error( $uploaded ) ) {
						$editor = wp_get_image_editor( $uploaded['path'] );
						unset( $uploaded['path'] );

						if ( ! is_wp_error( $editor ) ) {
							$metadata['sizes']         = $editor->multi_resize( $sizes );
							$metadata['sizes']['full'] = $uploaded;
						}
					}
				}
			}
		}

		// Remove the blob of binary data from the array.
		if ( $metadata ) {
			unset( $metadata['image']['data'] );
		}
		$metadata                = apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachment_id );
		$metadata['can']['save'] = false;

		/**
		 * Filters the generated attachment meta data.
		 *
		 * @param array $metadata An array of attachment meta data.
		 * @param int $attachment_id Current attachment ID.
		 *
		 * @since 2.1.0
		 *
		 */
		return $metadata;
	}

	/**
	 * Generate a meta name based on type
	 *
	 * @param $type
	 *
	 * @return string
	 */
	public static function get_meta_name( $type ) {
		return sprintf( '_%s_%s', Main::$slug, $type );
	}


	/**
	 * Add new form field to attachment details page.
	 *
	 * @param $form_fields
	 * @param $post
	 *
	 * @return array
	 * @see wp-admin/includes/media.php function get_attachment_fields_to_edit() line 1164
	 *
	 */
	public function show_cloud_id_in_media_edit( $form_fields, $post ) {
		$cloud_id                 = get_post_meta( $post->ID, self::get_meta_name( 'id' ), true );
		$disabled                 = apply_filters( sprintf( '%s_is_save_cloud_id_allowed', Main::$slug ),
			false ) ? '' : 'readonly disabled';
		$field_id                 = rtrim( '/', self::get_meta_name( 'id' ) );
		$html_id                  = sprintf( 'attachments-%s-%s', $post->ID, $field_id );
		$html_name                = sprintf( 'attachments[%s][%s]', $post->ID, $field_id );
		$form_fields[ $field_id ] = [
			'label' => __( 'Cloud ID', Main::$text_domain ),
			'input' => 'html',
			'value' => $cloud_id ? $cloud_id : 'xc',
			'html'  => sprintf( '<input type="text" class="widefat" id="%s" name="%s" value="%s" %s>', $html_id,
				$html_name, $cloud_id, $disabled ),
			// 'helps' => __( 'Information to credit the source of this uploaded file.' )
		];

		return $form_fields;
	}


	/**
	 * Save cloud id
	 *
	 * @param $post
	 * @param $attachment
	 *
	 * @return mixed
	 */
	public function save_cloud_id( $post, $attachment ) {
		$field_id = rtrim( '/', self::get_meta_name( 'id' ) );
		if ( apply_filters( sprintf( '%s_is_save_cloud_id_allowed', Main::$slug ), false ) ) {
			if ( isset( $attachment[ $field_id ] ) ) {
				// update_post_meta(postID, meta_key, meta_value);
				update_post_meta( $post['ID'], self::get_meta_name( 'id' ), $attachment[ $field_id ] );
			}
		}

		// If want to make it mandatory:
		// $post['errors']['my_field']['errors'][] = "Error message";

		return $post;
	}


	/**
	 * Change attachment url for cloud attachments
	 *
	 * @param $url
	 * @param $attachment_id
	 *
	 * @return mixed
	 */
	public function change_attachment_url( $url, $attachment_id ) {
		$cloud_id  = get_post_meta( $attachment_id, self::get_meta_name( 'id' ), true );
		$cloud_url = get_post_meta( $attachment_id, self::get_meta_name( 'url' ), true );
		if ( isset( $cloud_id ) && ! empty( $cloud_id ) && isset( $cloud_url['full'] ) && ! empty( $cloud_url['full'] ) ) {
			$url = $cloud_url['full'];
		}

		return $url;
	}

	/**
	 * Add file sizes
	 *
	 * @see https://developer.wordpress.org/reference/hooks/image_downsize/
	 *
	 * @param $output
	 * @param $attachment_id
	 * @param $size
	 *
	 * @return array
	 */
	public function image_downsize( $output, $attachment_id, $size ) {
		$cloud_id  = get_post_meta( $attachment_id, self::get_meta_name( 'id' ), true );
		$cloud_url = get_post_meta( $attachment_id, self::get_meta_name( 'url' ), true );
		$mime_type = get_post_mime_type( $attachment_id );
		$is_image  = ( $mime_type == 'image/jpeg' || $mime_type == 'image/png' || $mime_type == 'image/gif' ) ? true : false;
		if ( isset( $cloud_id ) && ! empty( $cloud_id ) && isset( $cloud_url ) && ! empty( $cloud_url ) && $is_image ) {
			if ( is_array( $size ) ) {
				$width           = $size['0'];
				$height          = $size['1'];
				$is_intermediate = false;
				$size_name       = $width . 'x' . $height;
				if ( array_key_exists( $size_name, $cloud_url ) ) {
					$img_url = $cloud_url[ $size_name ];
				} else {
					$img_url                 = self::generate_image_size( $cloud_id, $width, $height );
					$cloud_url[ $size_name ] = $img_url;
					update_post_meta( $attachment_id, self::get_meta_name( 'url' ), $cloud_url );
				}
			} else {
				if( empty($size) ) {
					$size = 'full';
				}
				$width              = Helper::get_image_width( $size );
				$height             = Helper::get_image_height( $size );
				$intermediate_sizes = get_intermediate_image_sizes();
				$is_intermediate    = ( in_array( $size, $intermediate_sizes ) ) ? true : false;
				if( array_key_exists($size, $cloud_url) ) {
					$img_url            = $cloud_url[ $size ];
				} else {
					$img_url            = self::generate_image_size( $cloud_id, $width, $height );
					$cloud_url[ $size ] = $img_url;
					update_post_meta( $attachment_id, self::get_meta_name( 'url' ), $cloud_url );
					$cloud_file = self::get_file_by_id($cloud_id);

					$metadata = Attachment::generate_attachment_metadata( $attachment_id, false, [
						'width'  => $width,
						'height' => $height,
						'file'   => $cloud_file,
					] );
					wp_update_attachment_metadata( $attachment_id, $metadata );
				}
			}
			$output = [ $img_url, $width, $height, $is_intermediate ];
		}

		return $output;
	}

	/**
	 * Generate an image with species sizes
	 *
	 * @param $cloud_id
	 * @param $width
	 * @param $height
	 *
	 * @return bool|string
	 */
	public static function generate_image_size($cloud_id, $width, $height) {
		$generate_url = Main::$api_url . '/file/' . $cloud_id . '/generate';
		$token        = Helper::get_option( 'token', 'general' );
		$body_args    = [
			'width'  => $width,
			'height' => $height,
			'public' => Helper::get_option( 'upload_type', 'general' )
		];
		$headers      = [
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json'
		];
		$response     = wp_remote_post( $generate_url, [
			'headers' => $headers,
			'body'    => json_encode( $body_args ),
			'timeout' => 60000
		] );
		if ( ! is_wp_error( $response ) ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( $response_code == 200 ) {
				$body = json_decode( wp_remote_retrieve_body( $response ) );
				if ( is_array( $body ) ) {
					$body = $body[0];
				}
				$img_url                 = $body->public_link;
				return $img_url;
			}
		}

		return false;
	}


	/**
	 * Delete file from cloud servers after a Wordpress media deleted
	 *
	 * @see https://developer.wordpress.org/reference/hooks/delete_attachment/
	 *
	 * @param $attachment_id
	 */
	public function delete_attachment( $attachment_id ) {
		$delete_status = Helper::get_option( 'delete_status', 'general' );
		$force_delete  = ( $delete_status === 'permanent' ) ? true : false;

		$cloud_id  = get_post_meta( $attachment_id, self::get_meta_name( 'id' ), true );
		$cloud_url = get_post_meta( $attachment_id, self::get_meta_name( 'url' ), true );

		// Delete file from cloud server
		self::delete_file( $cloud_id, $force_delete );

		// delete post metas
		delete_post_meta( $attachment_id, self::get_meta_name( 'id' ), $cloud_id );
		delete_post_meta( $attachment_id, self::get_meta_name( 'url' ), $cloud_url );
	}


	/**
	 * Trash or delete a cloud file.
	 *
	 * When an attachment is permanently deleted, the file will be removed.
	 *
	 * The attachment is moved to the trash instead of permanently deleted.
	 *
	 * @param string $file_id File ID.
	 * @param bool $force_delete Optional. Whether to bypass trash and force deletion.
	 *                           Default false.
	 *
	 * @return boolean true on success, false or null on failure.
	 */
	public static function delete_file( $file_id, $force_delete = false ) {
		$url   = Main::$api_url . '/file/' . $file_id;
		$token = Helper::get_option( 'token', 'general' );
		if ( $force_delete ) {
			$headers  = [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json'
			];
			$response = wp_remote_request( $url, [
				'method'  => 'DELETE',
				'headers' => $headers,
				'timeout' => 60000
			] );

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();

				return false;
			} else {
				$response_code = wp_remote_retrieve_response_code( $response );
				if ( $response_code == 200 ) {
					return true;
				}

				return false;
			}
		} else {
			$headers  = [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json'
			];
			$response = wp_remote_request( $url, [
				'method'  => 'PATCH',
				'headers' => $headers,
				'body'    => json_encode( [ 'fire' => 'EVENT_TRASH' ] ),
				'timeout' => 60000
			] );
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();

				return false;
			} else {
				//$body = json_decode(wp_remote_retrieve_body($response));
				$response_code = wp_remote_retrieve_response_code( $response );
				if ( $response_code == 200 ) {
					return true;
				}

				return false;
			}
		}

	}

	/**
	 * Add new column to media list table
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function add_media_list_column( $columns ) {
		$columns[ Main::$slug ] = __( 'Cloud actions', Main::$text_domain );

		return $columns;
	}

	/**
	 * Add content to new media list table column
	 *
	 * @param $column_name
	 * @param $attachment_id
	 */
	public function media_list_column_content( $column_name, $attachment_id ) {
		if ( $column_name !== Main::$slug ) {
			return;
		}

		$cloud_id  = get_post_meta( $attachment_id, self::get_meta_name( 'id' ), true );
		$cloud_url = get_post_meta( $attachment_id, self::get_meta_name( 'url' ), true );
		if ( $cloud_id ) {
			$template = 'is-on-cloud-media-list';
		} else {
			$template = 'is-not-on-cloud-media-list';
		}

		$attachment_url = wp_get_attachment_url( $attachment_id );

		// Get upload bulk action result
		$export_bulk_result_key = isset( $_GET[ sprintf( '%s_export_bulk_result',
				Main::$slug ) ] ) ? $_GET[ sprintf( '%s_export_bulk_result', Main::$slug ) ] : false;
		$export_result          = false;
		if ( $export_bulk_result_key ) {
			$bulk_result = get_option( $export_bulk_result_key );
			if ( is_array( $bulk_result ) && array_key_exists( $attachment_id, $bulk_result ) ) {
				$export_result = $bulk_result[ $attachment_id ];
				if ( $export_result['success'] ) {
					$template  = 'is-on-cloud-media-list';
					$cloud_id  = $export_result['file']->id;
					$cloud_url = $export_result['file']->images;
				}
			}
		}


		$import_bulk_result_key = isset( $_GET[ sprintf( '%s_import_bulk_result',
				Main::$slug ) ] ) ? $_GET[ sprintf( '%s_import_bulk_result', Main::$slug ) ] : false;
		$import_result          = false;
		if ( $import_bulk_result_key ) {
			$import_bulk_result = get_option( $import_bulk_result_key );
			if ( is_array( $import_bulk_result ) && array_key_exists( $attachment_id, $import_bulk_result ) ) {
				$import_result = $import_bulk_result[ $attachment_id ];
				if ( $import_result['success'] ) {
					$template       = 'is-not-on-cloud-media-list';
					$attachment_id  = $import_result['attachment_id'];
					$attachment_url = $import_result['attachment_url'];
				}
			}
		}

		if( Helper::isDebugMode()  ) {
			$cloud_id  = get_post_meta( $attachment_id, self::get_meta_name( 'id' ), true );
			$cloud_url = get_post_meta( $attachment_id, self::get_meta_name( 'url' ), true );
			echo 'Cloud ID: ';
			var_dump($cloud_id);
			echo '<pre style="direction:ltr; text-align: left;">Cloud URL: '; print_r($cloud_url); echo '</pre>';
		}
		Helper::view( $template, 'media', [
			'attachment_id'      => $attachment_id,
			'cloud_id'           => $cloud_id,
			'cloud_urls'         => $cloud_url,
			'slug'               => Main::$slug,
			'text_domain'        => Main::$text_domain,
			'name'               => Main::$name,
			'attachment_url'     => $attachment_url,
			'bulk_export_result' => $export_result,
			'bulk_import_result' => $import_result,
		] );
	}

	/**
	 * Generate image files meta for wordpress integration
	 *
	 * @param $width
	 * @param $height
	 * @param $file
	 *
	 * @return array
	 */
	public static function generate_image_meta( $width, $height, $file ) {
		$constrain_sizes = Helper::get_constrain_sizes( $width, $height );
		$wp_file_type    = wp_check_filetype( $file->name, null );
		$domain          = Helper::get_option( 'domain', 'general' );
		$search          = $domain;
		// Check if domain is cloud default domain, Add a p/ after the domain!
		if ( strpos( $domain, Main::$domain ) !== false ) {
			$search = sprintf( '%sp/', $search );
		}

		$sizes = [];
		foreach ( $constrain_sizes as $size_name => $constrain_size ) {
			$fileParts           = pathinfo( $file->name );
			$thumbName           = $fileParts['filename'] . '_' . $constrain_size['width'] . 'x' . $constrain_size['height'] . '.' . $fileParts['extension'];
			$sizes[ $size_name ] = [
				'file'      => $thumbName,
				'width'     => $constrain_size['width'],
				'height'    => $constrain_size['height'],
				'mime-type' => $wp_file_type['type'],
			];
		}

		$meta = [
			'width'      => $width,
			'height'     => $height,
			'file'       => str_replace( $search, '', $file->public_link ),
			'sizes'      => $sizes,
			'image_meta' => [],
		];

		return $meta;
	}

	/**
	 * Generate video files meta for wordpress integration
	 *
	 * @param $file
	 *
	 * @return array
	 */
	public static function generate_video_meta( $file ) {
		$metadata                      = ( isset( $file->metadata ) && ! empty( $file->metadata ) ) ? (array) $file->metadata : [];
		$metadata['created_timestamp'] = $file->created_at;

		return $metadata;
	}

	/**
	 * Generate audio files meta for wordpress integration
	 *
	 * @param $file
	 *
	 * @return array
	 */
	public static function generate_audio_meta( $file ) {
		$metadata = ( isset( $file->metadata ) && ! empty( $file->metadata ) ) ? (array) $file->metadata : [];

		return $metadata;
	}


	/**
	 * Calculate an image's 'srcset' sources.
	 *
	 *
	 * @param array $sources {
	 *     One or more arrays of source data to include in the 'srcset'.
	 *
	 * @type array $width {
	 * @type string $url The URL of an image source.
	 * @type string $descriptor The descriptor type used in the image candidate string,
	 *                                  either 'w' or 'x'.
	 * @type int $value The source width if paired with a 'w' descriptor, or a
	 *                                  pixel density value if paired with an 'x' descriptor.
	 *     }
	 * }
	 *
	 * @param array $size_array {
	 *     An array of requested width and height values.
	 *
	 * @type int $0 The width in pixels.
	 * @type int $1 The height in pixels.
	 * }
	 *
	 * @param string $image_src The 'src' of the image.
	 * @param array $image_meta The image meta data as returned by 'wp_get_attachment_metadata()'.
	 * @param int $attachment_id Image attachment ID or 0.
	 */
	public function calculate_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		$cloud_id = get_post_meta( $attachment_id, self::get_meta_name( 'id' ), true );
		$is_cloud = isset( $cloud_id ) && ! empty( $cloud_id );
		if ( $is_cloud ) {
			$domain = Helper::get_option( 'domain', 'general' );
			// Check if domain is cloud default domain, Add a p/ after the domain!
			if ( strpos( $domain, Main::$domain ) !== false ) {
				$domain = sprintf( '%sp/', $domain );
			}
			$upload_dir     = wp_get_upload_dir();
			$upload_dir_url = sprintf( '%s/', $upload_dir['baseurl'] );

			if( Helper::isDebugMode() ) {
				echo '<pre style="direction:ltr; text-align: left;">Domain: '; print_r($domain); echo '</pre>';
				echo '<pre style="direction:ltr; text-align: left;">STRPOS:'; var_dump(strpos( $domain, Main::$domain )); echo '</pre>';
				echo '<pre style="direction:ltr; text-align: left;">Upload dir url: '; print_r($upload_dir_url); echo '</pre>';
				echo '<pre style="direction:ltr; text-align: left;">Sources: '; print_r($sources); echo '</pre>';
			}

			foreach ( $sources as $index => $source ) {
				$sources[ $index ]['url'] = str_replace( $upload_dir_url, $domain, $source['url'] );
			}

		}

		return $sources;
	}

	/**
	 * Handle remote download event webhook
	 *
	 * @param $resource
	 * @param $event_name
	 * @param $status_name
	 * @param $status_code
	 */
	public function update_remote_upload( $resource, $event_name, $status_name, $status_code ) {
		if ( $event_name === 'EVENT_REMOTE_DOWNLOAD' ) {
			Helper::handle_remote_upload_update( $resource, $status_name );
		}
	}

	/**
	 * Fix metadata for old attachments
	 * If file meta is a url, change it to a path: this needed for generating same img srcset for all type of attachments
	 * If attachment uploaded in old version of plugin, regenerate it's metadata
	 *
	 * @param array|bool $metadata
	 * @param int $attachment_id
	 * @param string $context
	 */
	public function filter_attachment_metadata( $metadata, $attachment_id ) {

		// Generate metadata for attachments who uploaded in old versions of plugin
		if( is_array($metadata) && !array_key_exists('file', $metadata) && array_key_exists('mime_type', $metadata) && preg_match( '!^image/!', $metadata['mime_type'] ) ) {
			$cloud_id  = get_post_meta( $attachment_id, self::get_meta_name( 'id' ), true );
			$cloud_url = get_post_meta( $attachment_id, self::get_meta_name( 'url' ), true );
			if( $cloud_url && $cloud_id ) {
				$cloud_file = self::get_file_by_id($cloud_id);

				if ($cloud_file->public) {
					list( $width, $height, $type, $attr ) = getimagesize( $cloud_url['full'] );
					$metadata = Attachment::generate_attachment_metadata( $attachment_id, false, [
						'width'  => $width,
						'height' => $height,
						'file'   => $cloud_file,
					] );
					wp_update_attachment_metadata( $attachment_id, $metadata );
				}

			}

		}

		// If attachment uploaded in old version of plugin, regenerate it's metadata
		if( is_array($metadata) && array_key_exists('file', $metadata) && filter_var($metadata['file'], FILTER_VALIDATE_URL) ) {
			$domain = Helper::get_option( 'domain', 'general' );
			// Check if domain is cloud default domain, Add a p/ after the domain!
			if ( strpos( $domain, Main::$domain ) !== false ) {
				$domain = sprintf( '%sp/', $domain );
			}

			$metadata['file'] = str_replace($domain, '', $metadata['file']);
		}


		return $metadata;
	}

}