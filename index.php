<?php 
/**
 * Plugin Name: Perfit WooCommerce
 * Plugin URI: https://docs.myperfit.com/es/articles/3976234-integracion-con-woocommerce
 * Description: Sincronizar productos, compras y clientes con Perfit Email Marketing.
 * Version: 1.0.1
 * Author: Perfit dev team
 * Author URI: https://www.myperfit.com
 * Text Domain: perfit-woocommerce
 * Domain Path: /i18n/languages/
 * WC requires at least: 3.1.0
 * WC tested up to: 3.6
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WOOPERFIT_PLUGIN_FILE' ) ) {
    define( 'WOOPERFIT_PLUGIN_FILE', __FILE__ );
}

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommercePerfit', false ) ) {
    include_once dirname( WOOPERFIT_PLUGIN_FILE ) . '/includes/class-wcp.php';
}

if(!function_exists('WCPerfit')){
    /**
     * Returns the main instance of WCPerfit.
     * @return WooCommercePerfit
     */
    function WCPerfit() {
        return WooCommercePerfit::instance();
    }
}

// Global for backwards compatibility.
$GLOBALS['woocommerce-perfit'] = WCPerfit();