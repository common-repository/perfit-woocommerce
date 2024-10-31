<?php

defined('ABSPATH') || exit;

/**
 * WCP_Install Class.
 */
class WCP_Install {

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_filter( 'plugin_action_links_' . WOOPERFIT_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
	}

    /**
     * Install.
     */
    public static function install() {
        // Check if we are not already running this routine.
        if ('yes' === get_transient('wcperfit_installing')) {
            return;
        }

        // If we made it till here nothing is running yet, lets set the transient now.
        set_transient('wcperfit_installing', 'yes', MINUTE_IN_SECONDS * 10);
        
        WCPerfit()->wpdb_table_fix();
        
        delete_transient('wcperfit_installing');

        do_action('woocommerce-perfit_installed');
    }
	
	/**
	 * Show action links on the plugin screen.
	 *
	 * @param mixed $links Plugin Action links.
	 *
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=perfit' ) . '" aria-label="' . esc_attr__( 'View WooCommerce settings', 'woocommerce' ) . '">' . esc_html__( 'Settings', 'woocommerce' ) . '</a>',
		);
		return array_merge( $action_links, $links );
	}
    
    /**
     * Uninstall.
     */
    public static function uninstall() {
        // Check if we are not already running this routine.
        if ('yes' === get_transient('wcperfit_uninstalling')) {
            return;
        }

        // If we made it till here nothing is running yet, lets set the transient now.
        set_transient('wcperfit_uninstalling', 'yes', MINUTE_IN_SECONDS * 10);
        
        WCPerfit()->inactivated_key();
        
        delete_transient('wcperfit_uninstalling');

        do_action('woocommerce-perfit_uninstalled');
    }
}
WCP_Install::init();