<?php

namespace Poshtiban\Woocommerce;

use Poshtiban\Helper;
use Poshtiban\Main;

class Account {
	/**
	 * @var bool|mixed
	 */
	private $type;

	/**
	 * Account constructor.
	 */
	public function __construct() {
		$this->type = Helper::get_option( 'mirror_type', 'woocommerce' );

		if ( $this->type == 'sync' ) {
			add_filter( 'woocommerce_account_downloads_columns', [ $this, 'download_columns' ] );
			add_action( sprintf( 'woocommerce_account_downloads_column_%s_download', Main::$slug ),
				[ $this, 'download_column_content' ] );
		} else {
			// make is_download_permitted false for removing woocommerce download table
			add_filter( 'woocommerce_order_is_download_permitted', '__return_false' );
			// Show cloud download table
			add_action( 'woocommerce_order_details_before_order_table', [ $this, 'order_downloads' ] );

			// remove woocommerce_account_downloads for replacing it with cloud account download content
			add_action( 'init', [ $this, 'remove_actions' ] );
			add_action( 'woocommerce_account_downloads_endpoint', [ $this, 'account_downloads' ] );
		}

	}

	public function download_columns( $columns ) {
		$columns[ sprintf( '%s_download', Main::$slug ) ] = __( 'Mirror Download', Main::$text_domain );

		return $columns;
	}

	public function download_column_content( $download ) {
		$mirror_downloads = get_post_meta( $download['product_id'], Woocommerce::$mirror_meta_name, true );
		$isSync           = get_post_meta( $download['product_id'], Woocommerce::$sync_meta_name, true );
		$file_id          = false;
		if ( isset( $mirror_downloads ) && ! empty( $mirror_downloads ) ) {
			foreach ( $mirror_downloads as $mirror_download ) {
				if ( $mirror_download['url'] == $download['file']['file'] ) {
					$file_id = $mirror_download['id'];
					break;
				}
			}
		}
		if ( $file_id && $isSync == 'yes' ) {
			Helper::view( 'account/sync-download-content', 'woocommerce', [
				'text_domain' => Main::$text_domain,
				'slug'        => Main::$slug,
				'file_id'     => $file_id,
				'download'    => $download,
				'link_text'   => Helper::get_option( 'download_link_title', 'woocommerce', __('Mirror download', Main::$text_domain) ),
			] );
		}
	}

	public function order_downloads( \WC_Order $order ) {
		$is_download_permitted  = $order->has_status( 'completed' ) || ( 'yes' === get_option( 'woocommerce_downloads_grant_access_after_payment' ) && $order->has_status( 'processing' ) );
		if(!$is_download_permitted) {
			return;
		}
		$downloads = [];
		$items     = $order->get_items();
		foreach ( $items as $item ) {
			$product_id       = $item->get_product_id();
			$variation_id     = $item->get_variation_id();
			$get_product_id   = $variation_id ? $variation_id : $product_id;
			$mirror_downloads = get_post_meta( $get_product_id, Woocommerce::$downloadable_meta_name, true );
			if ( isset( $mirror_downloads ) && ! empty( $mirror_downloads ) ) {
				$downloads[ $get_product_id ] = [];
				foreach ( $mirror_downloads as $file_id => $mirror_download ) {
					if ( ! array_key_exists( $file_id, $downloads[ $get_product_id ] ) ) {
						$downloads[ $get_product_id ][ $file_id ] = $mirror_download['name'] ? $mirror_download['name'] : basename($mirror_download['url']);
					}
				}
			}
		}
		$show_title = true;
		if ( apply_filters( sprintf( '%s_show_download_template', Main::$slug ), true, $this->type ) ) {
			Helper::view( 'account/order-downloads', 'woocommerce', [
				'text_domain' => Main::$text_domain,
				'slug'        => Main::$slug,
				'show_title'  => $show_title,
				'downloads'   => $downloads,
				'link_text'   => Helper::get_option( 'download_link_title', 'woocommerce' ),
			] );
		}
		 do_action( sprintf( '%s_downloads_template', Main::$slug ), $downloads, $show_title, [$order] );
	}

	public function remove_actions() {
		remove_action( 'woocommerce_account_downloads_endpoint', 'woocommerce_account_downloads' );
	}

	public function account_downloads() {
		$customer_orders = get_posts( [
			'numberposts' => - 1,
			'meta_key'    => '_customer_user',
			'meta_value'  => get_current_user_id(),
			'post_type'   => wc_get_order_types(),
			'post_status' => [ 'wc-processing', 'wc-completed' ],
		] );
		$downloads       = [];
		foreach ( $customer_orders as $order_object ) {
			$order = wc_get_order( $order_object->ID );
			$is_download_permitted  = $order->has_status( 'completed' ) || ( 'yes' === get_option( 'woocommerce_downloads_grant_access_after_payment' ) && $order->has_status( 'processing' ) );
			if( !$is_download_permitted ) {
				continue;
			}

			$items = $order->get_items();
			foreach ( $items as $item ) {
				$product_id       = $item->get_product_id();
				$variation_id     = $item->get_variation_id();
				$get_product_id   = $variation_id ? $variation_id : $product_id;
				$mirror_downloads = get_post_meta( $get_product_id, Woocommerce::$downloadable_meta_name, true );
				if ( isset( $mirror_downloads ) && ! empty( $mirror_downloads ) ) {
					$downloads[ $get_product_id ] = [];
					foreach ( $mirror_downloads as $file_id => $mirror_download ) {
						if ( ! array_key_exists( $file_id, $downloads[ $get_product_id ] ) ) {
							$downloads[ $get_product_id ][ $file_id ] = $mirror_download['name'] ? $mirror_download['name'] : basename($mirror_download['url']);
						}
					}
				}
			}
		}
		$show_title = false;
		if ( apply_filters( sprintf( '%s_show_download_template', Main::$slug ), true, $this->type ) ) {
			Helper::view( 'account/order-downloads', 'woocommerce', [
				'text_domain' => Main::$text_domain,
				'slug'        => Main::$slug,
				'show_title'  => $show_title,
				'downloads'   => $downloads,
				'link_text'   => Helper::get_option( 'download_link_title', 'woocommerce' ),
			] );
		}
		do_action( sprintf( '%s_downloads_template', Main::$slug ), $downloads, $show_title, $customer_orders );
	}

}