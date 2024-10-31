<?php

namespace Poshtiban\Woocommerce;

use Poshtiban\Helper;
use Poshtiban\Main;
use Poshtiban\Media\Attachment\Attachment;

class Product {
	/**
	 * @var bool|mixed
	 */
	private $type;
	/**
	 * @var string
	 */
	private $mirror_meta_name;
	/**
	 * @var string
	 */
	private $sync_meta_name;
	/**
	 * @var string
	 */
	private $downloadable_meta_name;

	/**
	 * Product constructor.
	 */
	public function __construct() {
		$this->type                   = Helper::get_option( 'mirror_type', 'woocommerce' );
		$this->mirror_meta_name       = Woocommerce::$mirror_meta_name;
		$this->sync_meta_name         = Woocommerce::$sync_meta_name;
		$this->downloadable_meta_name = Woocommerce::$downloadable_meta_name;

		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'custom_fields' ], 9 );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_sync_files' ] );

		add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'variable_custom_fields' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ $this, 'save_variable_sync_files' ], 10, 2 );

		add_action( sprintf( 'wp_ajax_%s_generate_mirror_link', Main::$slug ), [ $this, 'generate_mirror' ] );
		add_action( sprintf( 'wp_ajax_%s_save_downloadable_files', Main::$slug ),
			[ $this, 'save_downloadable_files' ] );

		add_action( sprintf( 'wp_ajax_%s_download_generator', Main::$slug ), [ $this, 'download_generator' ] );
		add_action( sprintf( 'wp_ajax_%s_download_generator', Main::$slug ), [ $this, 'download_generator' ] );

		add_action( sprintf( 'wp_ajax_%s_woocommerce_file_selector', Main::$slug ), [ $this, 'selector_tab_content' ] );
		add_action( sprintf( 'wp_ajax_%s_browse_folder', Main::$slug ), [ $this, 'browse_folder_ajax' ] );

		add_filter('woocommerce_gallery_image_html_attachment_image_params', [$this, 'attachment_image_params'], 10, 4);
	}

	/**
	 *  HTML for simple downloadable product custom fields
	 */
	public function custom_fields() {
		global $post;
		$product         = wc_get_product( $post->ID );
		$downloads       = $product->get_downloads();
		$is_downloadable = $product->is_downloadable();

		if ( $this->type == 'sync' ) {
			$template           = 'product/custom-fields-sync';
			$mirror_downloads   = get_post_meta( $post->ID, $this->mirror_meta_name, true );
			$sync_files         = get_post_meta( $post->ID, $this->sync_meta_name, true );
			$sync_files_checked = ( empty( $sync_files ) || $sync_files == 'yes' ) ? 'checked' : '';
			if ( empty( $mirror_downloads ) ) {
				$mirror_downloads = [];
			}

			$args = [
				'type'               => $this->type,
				'is_downloadable'    => $is_downloadable,
				'text_domain'        => Main::$text_domain,
				'downloads'          => $downloads,
				'sync_files_checked' => $sync_files_checked,
				'mirror_downloads'   => $mirror_downloads,
				'post'               => $post,
			];
		} else {
			$template = 'product/custom-fields-only-cloud';
			$files    = get_post_meta( $post->ID, $this->downloadable_meta_name, true );
			add_thickbox();

			$image_library_url = add_query_arg( [
				'action' => sprintf( '%s_woocommerce_file_selector', Main::$slug ),
				'width'  => '1000',
				'height' => '800',
			], admin_url( 'admin-ajax.php' ) );

			$args = [
				'type'              => $this->type,
				'is_downloadable'   => $is_downloadable,
				'text_domain'       => Main::$text_domain,
				'slug'              => Main::$slug,
				'downloads'         => $downloads,
				'files'             => $files,
				'post'              => $post,
				'image_library_url' => $image_library_url,
				'upload_path'       => Helper::get_option( 'upload_path', 'woocommerce' ),
			];
		}
		Helper::view( $template, 'woocommerce', $args );
	}

	/**
	 *  HTML for variable downloadable product custom fields
	 */
	public function variable_custom_fields( $loop, $variation_data, $variation ) {
		$product         = wc_get_product( $variation->ID );
		$downloads       = $product->get_downloads();
		$is_downloadable = $product->is_downloadable();

		if ( $this->type == 'sync' ) {
			$template           = 'product/variation/custom-fields-sync';
			$mirror_downloads   = get_post_meta( $variation->ID, $this->mirror_meta_name, true );
			$sync_files         = get_post_meta( $variation->ID, $this->sync_meta_name, true );
			$sync_files_checked = ( empty( $sync_files ) || $sync_files == 'yes' ) ? 'checked' : '';
			if ( empty( $mirror_downloads ) ) {
				$mirror_downloads = [];
			}

			$args = [
				'type'               => $this->type,
				'is_downloadable'    => $is_downloadable,
				'text_domain'        => Main::$text_domain,
				'downloads'          => $downloads,
				'sync_files_checked' => $sync_files_checked,
				'mirror_downloads'   => $mirror_downloads,
				'post'               => $variation,
			];
		} else {
			$template = 'product/variation/custom-fields-only-cloud';
			$files    = get_post_meta( $variation->ID, $this->downloadable_meta_name, true );
			add_thickbox();

			$image_library_url = add_query_arg( [
				'action' => sprintf( '%s_woocommerce_file_selector', Main::$slug ),
				'width'  => '1000',
				'height' => '800',
			], admin_url( 'admin-ajax.php' ) );

			$args = [
				'type'              => $this->type,
				'is_downloadable'   => $is_downloadable,
				'text_domain'       => Main::$text_domain,
				'slug'              => Main::$slug,
				'downloads'         => $downloads,
				'files'             => $files,
				'post'              => $variation,
				'image_library_url' => $image_library_url,
				'upload_path'       => Helper::get_option( 'upload_path', 'woocommerce' ),
			];
		}
		Helper::view( $template, 'woocommerce', $args );
	}

	/**
	 * Save cloud downloadable files on sync mode for simple product
	 *
	 * @param $variation_id
	 */
	public function save_sync_files( $variation_id ) {
		$post_sync_files = sanitize_key( $_POST[ sprintf( '_%s_sync_files', Main::$slug ) ] );
		$is_sync_checked = isset( $post_sync_files ) && ! empty( $post_sync_files );

		if ( $is_sync_checked ) {
			update_post_meta( $variation_id, $this->sync_meta_name, 'yes' );
		} else {
			update_post_meta( $variation_id, $this->sync_meta_name, 'no' );
		}

		$wc_file_urls = isset( $_POST['_wc_file_urls'] ) ? (array) $_POST['_wc_file_urls'] : [];
		$wc_file_urls = array_map( 'esc_url_raw', $wc_file_urls );
		$wc_file_names = isset( $_POST['_wc_file_names'] ) ? (array) $_POST['_wc_file_names'] : [];
		$wc_file_names = array_map( 'sanitize_text_field', $wc_file_names );

		if ( !empty( $wc_file_urls ) && !empty($wc_file_names ) && $is_sync_checked ) {
			$current_mirrors = get_post_meta( $variation_id, $this->mirror_meta_name, true );
			$names           = [];
			for ( $i = 0; $i < count( $wc_file_urls ); $i ++ ) {
				$names[ $wc_file_urls[ $i ] ] = $wc_file_names[ $i ];
			}
			if ( empty( $current_mirrors ) ) {
				$current_mirrors = [];
			}

			if ( !empty( $wc_file_urls ) ) {
				$mirror_files = [];
				$folder_id    = Helper::get_option( 'upload_path_id', 'woocommerce' );
				foreach ( $wc_file_urls as $wc_file_url ) {
					$has_key = array_search( $wc_file_url, array_column( $current_mirrors, 'url' ) );
					if ( $has_key === false ) {
						$path   = sprintf( '/product-%d', $variation_id );
						$upload = Helper::remote_upload( $wc_file_url, $folder_id, $path );
						if ( $upload['success'] ) {
							$mirror_files[] = [
								'name' => $names[ $wc_file_url ],
								'id'   => $upload['file']->id,
								'url'  => $wc_file_url,
							];
						}
					} else {
						$mirror_files[] = $current_mirrors[ $has_key ];
					}
				}
				update_post_meta( $variation_id, $this->mirror_meta_name, $mirror_files );
			}
		}
	}

	/**
	 * Save cloud downloadable files on single mode for simple product
	 */
	public function save_downloadable_files() {
		$files   = ( isset( $_POST['files'] ) && ! empty( $_POST['files'] ) ) ? $_POST['files'] : [];
		$post_id = ( isset( $_POST['post_id'] ) && ! empty( $_POST['post_id'] ) ) ? sanitize_text_field( $_POST['post_id'] ) : false;

		if ( empty( $files ) ) {
			update_post_meta( $post_id, $this->downloadable_meta_name, [] );
			wp_send_json_success( [
				'message' => __( 'File list saved.', Main::$text_domain ),
				'files'   => []
			] );
		}

		$current_files = get_post_meta( $post_id, $this->downloadable_meta_name, true );
		if ( empty( $current_files ) ) {
			$current_files = [];
		}

		// Remove files that are already in current files but not in new files
		if ( ! empty( $current_files ) ) {
			foreach ( $current_files as $file_id => $current_file ) {
				if ( ! array_key_exists( $file_id, $files ) ) {
					unset( $current_files[ $file_id ] );
				}
			}
		}

		foreach ( $files as $file_id => $file_name ) {
			if ( ! array_key_exists( $file_id, $current_files ) ) {
				if ( empty( $file_name ) ) {
					// User insert file id - need to get file metadata from cloud

					$url     = sprintf( '%s/file/%s', Main::$api_url, $file_id );
					$token   = Helper::get_option( 'token', 'general' );
					$headers = [
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
							$body = json_decode( wp_remote_retrieve_body( $response ) );
							if ( isset( $body->name ) && ! empty( $body->name ) ) {
								$current_files[ $file_id ] = [
									'name' => $body->name,
									'path' => __( 'Unknown path', Main::$text_domain ),
								];
							}
						}
					}
				} else {
					// User selected file Id
					$current_files[ $file_id ] = [
						'name' => $file_name,
						'path' => '/Partition\'s home/' . Helper::get_option( 'upload_path', 'woocommerce' ) . '/',
					];
				}
			}
		}

		update_post_meta( $post_id, $this->downloadable_meta_name, $current_files );

		wp_send_json_success( [
			'message'    => __( 'File list saved.', Main::$text_domain ),
			'files'      => $current_files,
			'product_id' => $post_id
		] );


	}

	public function save_variable_sync_files( $variation_id ) {
		// Sync. files checkbox
		$post_sync_files = sanitize_key( $_POST[ sprintf( '_%s_sync_files', Main::$slug ) ][$variation_id] );
		$is_sync_checked = isset( $post_sync_files ) && ! empty( $post_sync_files );

		$product_id      = wp_get_post_parent_id( $variation_id );
		if ( $is_sync_checked ) {
			update_post_meta( $variation_id, $this->sync_meta_name, 'yes' );
		} else {
			update_post_meta( $variation_id, $this->sync_meta_name, 'no' );
		}

		$wc_file_urls = isset( $_POST['_wc_variation_file_urls'] ) ? (array) $_POST['_wc_variation_file_urls'] : [];
		$wc_file_urls = array_map( 'esc_url_raw', $wc_file_urls[$variation_id] );
		$wc_file_names = isset( $_POST['_wc_variation_file_names'] ) ? (array) $_POST['_wc_variation_file_names'] : [];
		$wc_file_names = array_map( 'sanitize_text_field', $wc_file_names[$variation_id] );

		if ( !empty( $wc_file_urls ) && !empty( $wc_file_names ) && $is_sync_checked ) {
			$current_mirrors = get_post_meta( $variation_id, $this->mirror_meta_name, true );
			$names           = [];
			for ( $i = 0; $i < count( $wc_file_urls ); $i ++ ) {
				$names[ $wc_file_urls[ $i ] ] = $wc_file_names[ $i ];
			}
			if ( empty( $current_mirrors ) ) {
				$current_mirrors = [];
			}

			if ( is_array( $wc_file_urls ) ) {
				$mirror_files = [];
				$folder_id    = Helper::get_option( 'upload_path_id', 'woocommerce' );
				$path         = sprintf( '/product-%d', $product_id );
				foreach ( $wc_file_urls as $wc_file_url ) {
					$has_key = array_search( $wc_file_url, array_column( $current_mirrors, 'url' ) );
					if ( $has_key === false ) {
						$upload = Helper::remote_upload( $wc_file_url, $folder_id, $path );
						if ( $upload['success'] ) {
							$mirror_files[] = [
								'name' => $names[ $wc_file_url ],
								'id'   => $upload['file']->id,
								'url'  => $wc_file_url,
							];
						}

					} else {
						$mirror_files[] = $current_mirrors[ $has_key ];
					}
				}
				update_post_meta( $variation_id, $this->mirror_meta_name, $mirror_files );
			}
		}
	}

	/**
	 *  Update woocommerce sync files through ajax request
	 */
	public function generate_mirror() {
		$url             = ( isset( $_POST['url'] ) && ! empty( $_POST['url'] ) ) ? esc_url_raw( $_POST['url'] ) : false;
		$post_id         = ( isset( $_POST['post_id'] ) && ! empty( $_POST['post_id'] ) ) ? sanitize_text_field( $_POST['post_id'] ) : false;
		$product         = wc_get_product( $post_id );
		$downloads       = $product->get_downloads();
		$current_mirrors = get_post_meta( $post_id, $this->mirror_meta_name, true );
		if ( ! $current_mirrors ) {
			$current_mirrors = [];
		}
		$folder_id = Helper::get_option( 'upload_path_id', 'woocommerce' );

		foreach ( $downloads as $key => $download ) {
			if ( $download->get_file() == $url ) {
				$name   = $download->get_name();
				$path   = sprintf( '/product-%d', intval( $post_id ) );
				$upload = Helper::remote_upload( $url, $folder_id, $path );
				if ( $upload['success'] ) {
					$mirror_key = false;
					foreach ( $current_mirrors as $index => $current_mirror ) {
						if ( $current_mirror['url'] == $url ) {
							$mirror_key = $index;
							break;
						}
					}

					if ( $mirror_key !== false ) {
						$current_mirrors[ $mirror_key ]['id'] = $upload['file']->id;
					} else {
						$current_mirrors[] = [
							'name' => $name,
							'url'  => $url,
							'id'   => $upload['file']->id
						];
					}
				} else {
					wp_send_json_error( $upload['message'] );
				}
			}
		}

		update_post_meta( $post_id, $this->mirror_meta_name, $current_mirrors );
		wp_send_json_success( [ 'text' => __( 'File uploaded.', Main::$text_domain ), 'id' => $upload['file']->id ] );


	}

	/**
	 *  Generate a secure download link for downloadable files through ajax
	 */
	public function download_generator() {
		$nonce = sanitize_text_field($_POST['nonce']);
		if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) ) {
			wp_send_json_error( __( 'Nonce code is invalid', Main::$text_domain ) );
		}

		if ( isset( $_POST['file_id'] ) && ! empty( $_POST['file_id'] ) ) {
			$requested_file_id = sanitize_text_field( $_POST['file_id'] );
		}

		if ( isset( $_POST['product'] ) && ! empty( $_POST['product'] ) ) {
			$product_id = intval( $_POST['product'] );
		} else {
			wp_send_json_error( __( 'Please select a product', Main::$text_domain ) );
		}
		if ( isset( $_POST['order_id'] ) && ! empty( $_POST['order_id'] ) ) {
			$order_id = intval( $_POST['order_id'] );
		} else {
			$order_id = false;
		}

		$file_belong_product      = false;
		$current_mirrors          = get_post_meta( $product_id, $this->mirror_meta_name, true );
		$cloud_downloadable_files = get_post_meta( $product_id, $this->downloadable_meta_name, true );
		if ( isset( $current_mirrors ) && ! empty( $current_mirrors ) ) {
			foreach ( $current_mirrors as $current_mirror ) {
				if ( $requested_file_id == $current_mirror['id'] ) {
					$file_belong_product = true;
					break;
				}
			}
		}
		if ( isset( $cloud_downloadable_files ) && ! empty( $cloud_downloadable_files ) ) {
			foreach ( $cloud_downloadable_files as $file_id => $cloud_downloadable_file ) {
				if ( $requested_file_id == $file_id ) {
					$file_belong_product = true;
					break;
				}
			}
		}
		if ( $file_belong_product === false ) {
			wp_send_json_error( __( 'This file does not belong to this product.', Main::$text_domain ) );
		}

		$user_id               = get_current_user_id();
		$current_user          = get_userdata( $user_id );
		$current_user_is_buyer = wc_customer_bought_product( $current_user->user_email, $current_user->ID, $product_id );

		if (!$current_user_is_buyer && !current_user_can('administrator')) {
			if (!$order_id ) {
				wp_send_json_error( __( 'Please select an order', Main::$text_domain ) );
			}

			$data_store           = \WC_Data_Store::load( 'customer-download' );
			$download_permissions = $data_store->get_downloads(
				array(
					'order_id' => $order_id,
					'orderby'  => 'product_id',
				)
			);
			$file_counter = 1;
			$permitted_products = [];
			if ( $download_permissions && sizeof( $download_permissions ) > 0 ) {
				$product = wc_get_product($product_id);
				foreach ( $download_permissions as $download ) {
					if ( ! $product || $product->get_id() !== $download->get_product_id() ) {
						$product      = wc_get_product( $download->get_product_id() );
						$file_counter = 1;
					}

					// don't show permissions to files that have since been removed.
					if ( ! $product || ! $product->exists() || ! $product->has_file( $download->get_download_id() ) ) {
						continue;
					}

					$file_counter++;
					$permitted_products[] = $download->get_product_id();
				}
			}
			if (in_array($product_id, $permitted_products)) {
				$current_user_is_buyer = true;
			}
		}

		if ( $current_user_is_buyer || user_can( $user_id, 'administrator' ) ) {
			$expires  = Helper::get_option( 'active_time', 'woocommerce' );
			$token    = Helper::get_option( 'token', 'general' );
			$url      = sprintf( '%s/file/%s/generate/%d', Main::$api_url, $requested_file_id, $expires );
			$headers  = [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json'
			];
			$response = wp_remote_post( $url, [ 'headers' => $headers, 'timeout' => 60000 ] );

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				wp_send_json_error( $error_message );
			} else {
				$body = json_decode( wp_remote_retrieve_body( $response ) );
				if ( is_object( $body ) ) {
					wp_send_json_error( $body->message );
				} else {
					$domain = Helper::get_option( 'domain', 'general' );
					if ( substr( $domain, - 1 ) === '/' ) {
						$body = ltrim( $body, '/' );
					}
					$url = $domain . $body;
					if( Helper::get_option('force_download', 'woocommerce') ) {
						$url = add_query_arg('dl', 1, $url);
					}
					wp_send_json_success( $url );
				}

			}

		} else {
			wp_send_json_error( __( 'You did not bought this product.', Main::$text_domain ) );
		}

		wp_send_json_error( __( 'Something goes wrong. please try again.', Main::$text_domain ) );

	}

	public function selector_tab_content() {
		$path_id = Helper::get_option( 'upload_path_id', 'woocommerce' );
		if ( empty( $path_id ) ) {
			$class = 'notice notice-error';
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ),
				esc_html( __( 'Please set woocommerce folder first.', Main::$text_domain ) ) );
		}

		$browse = $this->browse_folder( $path_id );
		if ( $browse['success'] ) {
			Helper::view( 'product/cloud-files-list', 'woocommerce', [
				'files'       => $browse['payload'],
				'text_domain' => Main::$text_domain,
				'slug'        => Main::$slug,
			] );
		} else {
			$class = 'notice notice-error';
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $browse['message'] ) );
		}
		die();
	}

	public function browse_folder_ajax() {
		$folder_id = ( isset( $_POST['folder_id'] ) && ! empty( $_POST['folder_id'] ) ) ? sanitize_text_field( $_POST['folder_id'] ) : false;
		wp_send_json( $this->browse_folder( $folder_id ) );
	}

	public function browse_folder( $folder_id ) {
		$url      = sprintf( '%s/folder/browse/%s', Main::$api_url, $folder_id );
		$token    = Helper::get_option( 'token', 'general' );
		$headers  = [
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json'
		];
		$body     = [
			'type' => 'file'
		];
		$response = wp_remote_get( $url, [
			'headers' => $headers,
			'body'    => [ json_encode( $body ) ],
			'timeout' => 60000
		] );
		$result   = [
			'success' => true,
			'message' => __( 'Getting folder list successfully done', Main::$text_domain ),
			'payload' => [],
			'test'    => $url,
		];

		if ( is_wp_error( $response ) ) {
			$message           = $response->get_error_message();
			$result['success'] = false;
			$result['message'] = $message;

			return $result;
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			$body          = json_decode( wp_remote_retrieve_body( $response ) );
			if ( $response_code == 200 ) {
				$files             = $body->children;
				$result['payload'] = $files;

				return $result;
			} else {
				$result['success'] = false;
				$result['message'] = $body->message;

				return $result;
			}
		}
	}

	/**
	 * Change woocommerce image html attachment image params to fix lightbox zoom problem
	 *
	 * @param $params
	 * @param $attachment_id
	 * @param $image_size
	 * @param $main_image
	 *
	 * @return mixed
	 */
	public function attachment_image_params( $params, $attachment_id, $image_size, $main_image ) {
		$cloud_id  = get_post_meta( $attachment_id, Attachment::get_meta_name( 'id' ), true );
		$cloud_url = get_post_meta( $attachment_id, Attachment::get_meta_name( 'url' ), true );

		if ( $cloud_url && $cloud_id ) {
			$size = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
			$large_image_width = false;
			$large_image_height = false;

			if ( array_key_exists( $size, $cloud_url ) ) {
				$img_url = $cloud_url[ $size ];
				list( $width, $height, $type, $attr ) = getimagesize( $img_url );
				$large_image_width = $width;
				$large_image_height = $height;
			} else {
				$width              = Helper::get_image_width( $size );
				$height             = Helper::get_image_height( $size );
				if( $width && $height ) {
					$img_url            = Attachment::generate_image_size( $cloud_id, $width, $height );
					$cloud_url[ $size ] = $img_url;
					update_post_meta( $attachment_id, Attachment::get_meta_name( 'url' ), $cloud_url );
					$cloud_file = Attachment::get_file_by_id( $cloud_id );

					$metadata = Attachment::generate_attachment_metadata( $attachment_id, false, [
						'width'  => $width,
						'height' => $height,
						'file'   => $cloud_file,
					] );
					wp_update_attachment_metadata( $attachment_id, $metadata );
					$large_image_width = $width;
					$large_image_height = $height;
				}
			}

			if( $large_image_width && $large_image_height ) {
				$params['data-large_image_width'] = $large_image_width;
				$params['data-large_image_height'] = $large_image_height;
			}

		}

		return $params;
	}
}