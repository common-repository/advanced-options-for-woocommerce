<?php

/**
 * Plugin Name: Advanced Options for WooCommerce
 * Plugin URI: https://en.condless.com/advanced-options-for-woocommerce/
 * Description: WooCommerce plugin for more options and customization. Simple and Easy to use.
 * Version: 1.1.6
 * Author: Condless
 * Author URI: https://en.condless.com/
 * Developer: Condless
 * Developer URI: https://en.condless.com/
 * Contributors: condless
 * Text Domain: advanced-options-for-woocommerce
 * Domain Path: /i18n/languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.2
 * Tested up to: 6.5
 * Requires PHP: 7.0
 * WC requires at least: 3.4
 * WC tested up to: 8.9
 */

/**
 * Exit if accessed directly
 */
defined( 'ABSPATH' ) || exit;

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || get_site_option( 'active_sitewide_plugins') && array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins' ) ) ) {

	/**
	 * Advanced Options for WooCommerce Class.
	 */
	class WC_AOW {

		/**
		 * Construct class
		 */
		public function __construct() {
			add_action( 'before_woocommerce_init', function() {
				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				}
			} );
			add_action( 'plugins_loaded', [ $this, 'init' ] );
		}

		/**
		 * WC init
		 */
		public function init() {
			$this->init_textdomain();
			$this->init_settings();
			$this->init_functions();
		}

		/**
		 * Load text domain for internationalization
		 */
		public function init_textdomain() {
			load_plugin_textdomain( 'advanced-options-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages' );
		}

		/**
		 * WC settings init
		 */
		public function init_settings() {
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'wc_update_settings_link' ] );
			add_filter( 'plugin_row_meta', [ $this, 'wc_add_plugin_links' ], 10, 4 );
			add_filter( 'woocommerce_settings_tabs_array', [ $this, 'wc_add_settings_tab' ], 50 );
			add_action( 'woocommerce_settings_tabs_aow', [ $this, 'wc_settings_tab' ] );
			add_action( 'woocommerce_update_options_aow', [ $this, 'wc_update_settings' ] );
		}

		/**
		 * WC functions init
		 */
		public function init_functions() {
			if ( 'yes' === get_option( 'wc_aow_processing_quantity' ) ) {
				add_action( 'admin_enqueue_scripts', [ $this, 'wc_admin_total_column' ] );
				add_filter( 'manage_edit-product_columns', [ $this, 'wc_admin_column' ] );
				add_action( 'manage_posts_custom_column', [ $this, 'wc_populate_column' ] );
				add_filter( 'manage_edit-product_sortable_columns', [ $this, 'wc_sortable_column' ] );
			}
		}

		/**
		 * Add plugin links to the plugin menu
		 * @param mixed $links
		 * @return mixed
		 */
		public function wc_update_settings_link( $links ) {
			array_unshift( $links, '<a href=' . esc_url( add_query_arg( 'page', 'wc-settings&tab=aow', get_admin_url() . 'admin.php' ) ) . '>' . __( 'Settings' ) . '</a>' );
			return $links;
		}

		/**
		 * Add plugin meta links to the plugin menu
		 * @param mixed $links_array
		 * @param mixed $plugin_file_name
		 * @param mixed $plugin_data
		 * @param mixed $status
		 * @return mixed
		 */
		public function wc_add_plugin_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
			if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
				$sub_domain = 'he_IL' === get_locale() ? 'www' : 'en';
				$links_array[] = "<a href=https://$sub_domain.condless.com/advanced-options-for-woocommerce/>" . __( 'Docs', 'woocommerce' ) . '</a>';
				$links_array[] = "<a href=https://$sub_domain.condless.com/contact/>" . _x( 'Contact', 'Theme starter content' ) . '</a>';
			}
			return $links_array;
		}

		/**
		 * Add a new settings tab to the WooCommerce settings tabs array
		 * @param array $settings_tabs
		 * @return array
		 */
		public function wc_add_settings_tab( $settings_tabs ) {
			$settings_tabs['aow'] = __( 'Advanced Options', 'advanced-options-for-woocommerce' );
			return $settings_tabs;
		}

		/**
		 * Use the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function
		 * @uses woocommerce_admin_fields()
		 * @uses self::wc_get_settings()
		 */
		public function wc_settings_tab() {
			woocommerce_admin_fields( self::wc_get_settings() );
		}

		/**
		 * Use the WooCommerce options API to save settings via the @see woocommerce_update_options() function
		 * @uses woocommerce_update_options()
		 * @uses self::wc_get_settings()
		 */
		public function wc_update_settings() {
			woocommerce_update_options( self::wc_get_settings() );
		}

		/**
		 * Get all the settings for this plugin for @see woocommerce_admin_fields() function
		 * @return array Array of settings for @see woocommerce_admin_fields() function
		 */
		public function wc_get_settings() {
			$settings = [
				'section_title'	=> [
					'name'	=> __( 'Settings' ),
					'type'	=> 'title',
					'id'	=> 'wc_aow_section_title'
				],
				'prepare'	=> [
					'name'		=> __( 'Quantity', 'woocommerce' ) . ' ' . __( 'Processing', 'woocommerce' ),
					'desc'		=> __( 'Adds in the admin products table a column of the current items quantity in proccessing orders', 'advanced-options-for-woocommerce' ),
					'type'		=> 'checkbox',
					'default'	=> 'no',
					'id'		=> 'wc_aow_processing_quantity'
				],
				'section_end'	=> [
					'type'	=> 'sectionend',
					'id'	=> 'wc_aow_section_end'
				],
			];
			return apply_filters( 'wc_aow_settings', $settings );
		}

		/**
		 * Add a new column of quantity
		 * @param mixed $hook
		 */
		public function wc_admin_total_column( $hook ) {
			if ( 'edit.php' === $hook && isset( $_GET['post_type'] ) && 'product' === $_GET['post_type'] ) {
				$orders = wc_get_orders( apply_filters( 'aow_orders', [
					'limit'		=> -1,
					'type'		=> 'shop_order',
					'status'	=> 'processing'
				] ) );
				foreach ( $orders as $order ) {
					$order_items = $order->get_items( [ 'line_item' ] );
					if ( ! is_wp_error( $order_items ) ) {
						foreach ( $order_items as $order_item ) {
							if ( isset( $_GET[ $order_item->get_product_id() ] ) ) {
								$_GET[ $order_item->get_product_id() ] += $order_item->get_quantity();
							} else {
								$_GET[ $order_item->get_product_id() ] = $order_item->get_quantity();
							}
						}
					}
				}
			}
		}

		/**
		 * Add new admin column title
		 * @param mixed $columns_array
		 * @return mixed
		 */
		public function wc_admin_column( $columns_array ) {
			$columns_array['prepare'] = __( 'Quantity', 'woocommerce' ) . ' ' . __( 'Processing', 'woocommerce' );
			return $columns_array;
		}

		/**
		 * Populate new admin column
		 * @param mixed $column_name
		 * @return mixed
		 */
		public function wc_populate_column( $column_name ) {
			if ( 'prepare' === $column_name ) {
				echo isset( $_GET[ get_the_ID() ] ) ? wc_clean( $_GET[ get_the_ID() ] ) : '0';
			}
		}

		/**
		 * Make the new column sortable
		 * @param mixed $sortable_columns
		 * @return mixed
		 */
		public function wc_sortable_column( $sortable_columns ) {
			$sortable_columns['prepare'] = 'prepare';
			return $sortable_columns;
		}
	}

	/**
	 * Instantiate class
	 */
	$advanced_options_for_woocommerce = new WC_AOW();
};
