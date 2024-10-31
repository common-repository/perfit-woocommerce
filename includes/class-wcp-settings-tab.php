<?php

defined('ABSPATH') || exit;

class WCP_Settings_Tab {

    /**
     * $id 
     * @var string
     */
    public $id = 'perfit';

    /**
     * __construct
     * class constructor will set the needed filter and action hooks
     */
    function __construct() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'actions'));
            add_filter('woocommerce_settings_tabs_array', array($this, 'woocommerce_settings_tabs_array'), 50);
            //show settings tab
            add_action('woocommerce_settings_' . $this->id, array($this, 'show_settings_tab'));
        }
    }

    /**
     * woocommerce_settings_tabs_array 
     * Used to add a WooCommerce settings tab
     * @param  array $settings_tabs
     * @return array
     */
    function woocommerce_settings_tabs_array($settings_tabs) {
        $settings_tabs[$this->id] = __('Perfit', 'woocommerce-perfit');
        return $settings_tabs;
    }

    /**
     * show_settings_tab
     * @return void
     */
    function show_settings_tab() {
        $apikey = get_option('woocommerce-perfit-apikey', '');
        $account = get_option('woocommerce-perfit-account', '');
        $apikeyMask = '';
        if (!empty($account)) {
            $apikeyMask = str_pad($account, 50, "*", STR_PAD_RIGHT);
        }
        require_once WOOPERFIT_ABSPATH . 'includes/admin/views/html-settings.php';
    }

    /**
     * Check if is this settings page.
     *
     * @return bool
     */
    private function is_this_settings_page() {
        return isset($_GET['page'], $_GET['tab']) && 'wc-settings' === $_GET['page'] && $this->id === $_GET['tab']; // input var okay, CSRF ok.
    }

    /**
     * Admin actions.
     */
    public function actions() {
        if ($this->is_this_settings_page()) {
            // Save.
            if (isset($_POST['save']) && isset($_POST['apikey'])) { // WPCS: input var okay, CSRF ok.
                $this->save();
            }
            // Delete.
            if (isset($_GET['action']) && $_GET['action'] == 'logout') { // input var okay, CSRF ok.
                $this->logout();
            }
        }
    }

    /**
     * Save method.
     */
    private function save() {
        check_admin_referer('woocommerce-settings');

        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to update Webhooks', 'woocommerce'));
        }

        $apiKey = sanitize_text_field(wp_unslash(trim($_POST['apikey'])));
        
        // Run actions.
        do_action('woocommerce-perfit_options_save', $apiKey);
        
        $errors = array();
        if ($errors) {
            // Redirect to webhook edit page to avoid settings save actions.
            wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=' . $this->id . '&error=' . rawurlencode(implode('|', $errors))));
            exit();
        }
    }

    /**
     * Delete.
     */
    private function logout() {
        // Run actions.
        do_action('woocommerce-perfit_options_delete');        
        wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=' . $this->id));
        exit();
    }

}

new WCP_Settings_Tab();
