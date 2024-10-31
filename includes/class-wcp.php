<?php

/**
 * WooCommerce Perfit setup
 */
defined('ABSPATH') || exit;

/**
 * Main WooCommercePerfit Class.
 *
 * @class WooCommercePerfit
 */
class WooCommercePerfit {

    /**
     * version.
     * @var string
     */
    public $version = '1.0.1';

    /**
     * The single instance of the class.
     * @var WooCommercePerfit
     */
    protected static $_instance = null;

    /**
     * Main Instance.
     *
     * Ensures only one instance of WooCommercePerfit is loaded or can be loaded.
     *
     * @static
     * @return WooCommercePerfit - Main instance.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /** @var WC_Logger Logger instance */
    public static $log = false;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->define_constants();
        $this->define_tables();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Logging method.
     *
     * @param string $message Log message.
     * @param string $level   Optional. Default 'info'.
     *     emergency|alert|critical|error|warning|notice|info|debug
     */
    public static function log($message, $level = 'info') {
        if (empty(self::$log)) {
            self::$log = wc_get_logger();
        }
        self::$log->log($level, $message, array('source' => __CLASS__));
        error_log($message);
    }

    /**
     * When WP has loaded all plugins, trigger the `woocommerce-perfit_loaded` hook.
     *
     * This ensures `woocommerce-perfit_loaded` is called only after all other plugins
     * are loaded, to avoid issues caused by plugin directory naming changing
     * the load order.
     */
    public function on_plugins_loaded() {
        do_action('woocommerce-perfit_loaded');
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks() {
        register_activation_hook(WOOPERFIT_PLUGIN_FILE, array('WCP_Install', 'install'));
        register_uninstall_hook(WOOPERFIT_PLUGIN_FILE, array('WCP_Install', 'uninstall'));
        register_shutdown_function(array($this, 'log_errors'));

        add_action('plugins_loaded', array($this, 'on_plugins_loaded'), -1);
        add_action('init', array($this, 'init'), 0);
        add_action('activated_plugin', array($this, 'activated_plugin'));
        add_action('deactivated_plugin', array($this, 'deactivated_plugin'));
        add_action('woocommerce-perfit_options_save', array($this, 'activated_key'));
        add_action('woocommerce-perfit_options_delete', array($this, 'inactivated_key'));

        add_filter('woocommerce_webhook_payload', array($this, 'filter_woocommerce_webhook_payload'), 10, 4);
    }

    /**
     * Ensures fatal errors are logged so they can be picked up in the status report.
     */
    public function log_errors() {
        $error = error_get_last();
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR), true)) {
            $logger = wc_get_logger();
            $logger->critical(
                    /* translators: 1: error message 2: file name and path 3: line number */
                    sprintf(__('%1$s in %2$s on line %3$s', 'woocommerce-perfit'), $error['message'], $error['file'], $error['line']) . PHP_EOL, array(
                'source' => 'fatal-errors',
                    )
            );
            do_action('woocommerce-perfit_shutdown_error', $error);
        }
    }

    /**
     * Define WOOPERFIT Constants.
     */
    private function define_constants() {
        $this->define('WOOPERFIT_ABSPATH', dirname(WOOPERFIT_PLUGIN_FILE) . '/');
        $this->define('WOOPERFIT_PLUGIN_BASENAME', plugin_basename(WOOPERFIT_PLUGIN_FILE));
        $this->define('WOOPERFIT_VERSION', $this->version);
        $this->define('WOOPERFIT_NOTICE_MIN_PHP_VERSION', '7.0');
        $this->define('WOOPERFIT_NOTICE_MIN_WP_VERSION', '5.0');
    }

    /**
     * Register custom tables within $wpdb object.
     */
    private function define_tables() {
        global $wpdb;
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Include required core files used in admin.
     */
    public function includes() {
        include_once WOOPERFIT_ABSPATH . 'includes/class-wcp-install.php';
        include_once WOOPERFIT_ABSPATH . 'includes/class-wcp-rest-account-controller.php';
        include_once WOOPERFIT_ABSPATH . 'includes/class-wcp-settings-tab.php';

        include_once WOOPERFIT_ABSPATH . 'lib/Perfit.php';
    }

    /**
     * Init WooCommercePerfit when WordPress Initialises.
     */
    public function init() {
        // Before init action.
        do_action('before_woocommerce-perfit_init');

        // Set up localisation.
        $this->load_plugin_textdomain();

        add_action('rest_api_init', array($this, 'register_rest_routes'), 10);

        if (is_admin()) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            if (is_plugin_active('woocommerce/woocommerce.php') == false) {
                add_action('admin_notices', function() {
                    $message = __('WooCommerce Perfit needs WooCommerce to run. Please, install and active WooCommerce plugin.', 'woocommerce-perfit');
                    printf('<div class="%1$s"><p>%2$s</p></div>', 'notice notice-error', $message);
                });
            }
        }

        // Init action.
        do_action('woocommerce-perfit_init');
    }

    /**
     * Register REST API routes.
     */
    public function register_rest_routes() {
        $WCP_REST_Account_Controller = new WCP_REST_Account_Controller();
        $WCP_REST_Account_Controller->register_routes();
    }

    /**
     * Load Localisation files.
     *
     * Note: the first-loaded translation file overrides any following ones if the same translation is present.
     *
     * Locales found in:
     *      - WP_LANG_DIR/woocommerce-perfit/woocommerce-perfit-LOCALE.mo
     */
    public function load_plugin_textdomain() {
        if (function_exists('determine_locale')) {
            $locale = determine_locale();
        } else {
            $locale = is_admin() ? get_user_locale() : get_locale();
        }

        $locale = apply_filters('plugin_locale', $locale, 'woocommerce-perfit');

        unload_textdomain('woocommerce-perfit');
        load_textdomain('woocommerce-perfit', WP_LANG_DIR . '/woocommerce-perfit/woocommerce-perfit-' . $locale . '.mo');
        load_plugin_textdomain('woocommerce-perfit', false, plugin_basename(dirname(WOOPERFIT_PLUGIN_FILE)) . '/i18n/languages');
    }

    /**
     * Get the plugin url.
     *
     * @return string
     */
    public function plugin_url() {
        return untrailingslashit(plugins_url('/', WOOPERFIT_PLUGIN_FILE));
    }

    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function plugin_path() {
        return untrailingslashit(plugin_dir_path(WOOPERFIT_PLUGIN_FILE));
    }

    /**
     * Get Ajax URL.
     *
     * @return string
     */
    public function ajax_url() {
        return admin_url('admin-ajax.php', 'relative');
    }

    /**
     * Set tablenames inside WPDB object.
     */
    public function wpdb_table_fix() {
        $this->define_tables();
    }

    /**
     * Any plugin is activated.
     * @param string $filename The filename of the activated plugin.
     */
    public function activated_plugin($filename) {
        
    }

    /**
     * Any plugin is deactivated.
     *
     * @param string $filename The filename of the deactivated plugin.
     */
    public function deactivated_plugin($filename) {
        if (0 !== strpos($filename, 'woocommerce-perfit')) {
            return;
        }
        $this->inactivated_key();
    }

    /**
     * Get WC latest version
     * @return string E.g. v3
     */
    private function get_wc_api_version() {
        $rest_api_versions = wc_get_webhook_rest_api_versions();
        $wc_api_version = str_replace('wp_api_', '', end($rest_api_versions));
        return $wc_api_version;
    }

    /**
     * Ran when user save new key.
     * @param string $apikey
     */
    public function activated_key($apikey) {

        $this->log(__CLASS__ . ' activated_key');

        $perfit = new WooPerfitSDK\WooPerfitSDK();
        $perfit->apiKey($apikey);

        $wc_api_version = $this->get_wc_api_version();
        $base_url = get_home_url();
        $WCP_REST_Account_Controller = new WCP_REST_Account_Controller();

        $wcAuthResponse = $this->create_wc_api_consumer();

        $params = array(
            'wc' => array(
                'api_version' => $wc_api_version,
                'consumer_key' => $wcAuthResponse['consumer_key'],
                'consumer_secret' => $wcAuthResponse['consumer_secret'],
            ),
            'url' => $base_url,
            'auth' => 'api-key-de-wooocommerce',
            'urls' => array(
                'products' => $base_url . '/wp-json/wc/' . $wc_api_version . '/products',
                'customers' => $base_url . '/wp-json/wc/' . $wc_api_version . '/customers',
                'orders' => $base_url . '/wp-json/wc/' . $wc_api_version . '/orders',
                'account' => $base_url . '/wp-json/' . $WCP_REST_Account_Controller->get_base_route()
            )
        );

        $this->log(__CLASS__ . ' activated_key before call API Perfit: ' . print_r($params, true));

        $response = $perfit->post('/integrations/woocommerce/configure', $params);

        $this->log(__CLASS__ . ' activated_key response call API Perfit: ' . print_r($response, true));

        if (isset($response->success) && $response->success) {
            $perfit_webhook_url = $response->data->webhook_url;
            update_option('woocommerce-perfit-wcauthkey', $wcAuthResponse['key_id']);
            update_option('woocommerce-perfit-created', $response->data->created);
            update_option('woocommerce-perfit-apikey', $response->data->api_key);
            update_option('woocommerce-perfit-account', $response->data->account);
            update_option('woocommerce-perfit-webhook_url', $perfit_webhook_url);

            $this->create_wc_api_hooks($perfit_webhook_url);
        } else if ($response->error->status == 401) {
            WC_Admin_Settings::add_error(esc_html__('Unathorized', 'woocommerce-perfit'));
        } else if (!empty($response->error->userMessage)) {
            WC_Admin_Settings::add_error($response->error->userMessage);
        } else {
            WC_Admin_Settings::add_error(esc_html__('Unathorized', 'woocommerce-perfit'));
        }
    }

    /**
     * Create READ WC API Consumer.
     * @return false|array E.g.
     * {
     *        "key_id": 1,
     *        "user_id": 123,
     *        "consumer_key": "ck_xxxxxxxxxxxxxxxx",
     *        "consumer_secret": "cs_xxxxxxxxxxxxxxxx",
     *        "key_permissions": "read_write"
     * }
     */
    private function create_wc_api_consumer() {
        global $wpdb;

        $app_name = 'Perfit integration';
        $scope = 'read'; // read_write | read | write
        $consumer_key = 'ck_' . wc_rand_hash();
        $consumer_secret = 'cs_' . wc_rand_hash();

        $wcAuthResponse = false;

        $table = $wpdb->prefix . 'woocommerce_api_keys';
        $results = $wpdb->get_results("SELECT * FROM $table WHERE consumer_key='" . wc_api_hash($consumer_key) . "'");
        if (count($results) <= 0) {
            $values = array(
                'user_id' => get_current_user_id(),
                'description' => $app_name,
                'permissions' => $scope,
                'consumer_key' => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret,
                'truncated_key' => substr($consumer_key, -7),
            );
            $wpdb->insert($table, $values, array('%d', '%s', '%s', '%s', '%s', '%s'));

            $wcAuthResponse = array(
                "key_id" => $wpdb->insert_id,
                "user_id" => $values['user_id'],
                "consumer_key" => $consumer_key,
                "consumer_secret" => $consumer_secret,
                "key_permissions" => $scope
            );
        }
        return $wcAuthResponse;
    }

    /**
     * Ran when user delete the key.
     */
    public function inactivated_key() {
        $this->log(__CLASS__ . ' inactivated_key.');

        $apikey = get_option('woocommerce-perfit-apikey', '');
        $perfit_webhook_url = get_option('woocommerce-perfit-webhook_url', '');
        if (!empty($apikey) && !empty($perfit_webhook_url)) {
            $perfit = new WooPerfitSDK\WooPerfitSDK();
            $perfit->apiKey($apikey);

            $perfit->post('/integrations/woocommerce/cancel', array());

            $wcAuthKeyId = get_option('woocommerce-perfit-wcauthkey', '');
            delete_option('woocommerce-perfit-wcauthkey');
            delete_option('woocommerce-perfit-created');
            delete_option('woocommerce-perfit-apikey');
            delete_option('woocommerce-perfit-account');
            delete_option('woocommerce-perfit-webhook_url');
            if (!empty($wcAuthKeyId)) {
                $this->maybe_delete_key(array('key_id' => $wcAuthKeyId));
            }
            $this->delete_wc_api_hooks($perfit_webhook_url);
        }
    }

    /**
     * Maybe delete key.
     * @param array $key Key.
     */
    private function maybe_delete_key($key) {
        global $wpdb;
        if (isset($key['key_id'])) {
            $wpdb->delete($wpdb->prefix . 'woocommerce_api_keys', array('key_id' => $key['key_id']), array('%d'));
        }
    }

    /**
     * Create WC API Hooks.
     * @param string $perfit_webhook_url
     * @return void
     */
    private function create_wc_api_hooks($perfit_webhook_url) {
        $wc_api_version_number = str_replace('v', '', $this->get_wc_api_version());
        $userID = get_current_user_id();

        $topics_list = array(
            'product.created' => 'product created', 'product.updated' => 'product updated', 'product.deleted' => 'product deleted',
            'order.created' => 'order created', 'order.updated' => 'order updated',
            'customer.created' => 'customer created', 'customer.updated' => 'customer updated',
            'action.woocommerce_add_to_cart' => 'product add_to_cart',
        );
        foreach ($topics_list as $topic => $topic_name) {
            $webhook = new WC_Webhook();
            $webhook->set_name($topic_name);
            $webhook->set_status('active');
            $webhook->set_delivery_url($perfit_webhook_url);
            $webhook->set_topic($topic);
            $webhook->set_api_version($wc_api_version_number);
            $webhook->set_user_id($userID);
            $webhook->set_pending_delivery(false);
            $webhook->save();
        }
    }

    /**
     * Delete WC API Hooks.
     * @param string $perfit_webhook_url
     * @return void
     */
    private function delete_wc_api_hooks($perfit_webhook_url) {
        $data_store = WC_Data_Store::load('webhook');
        $webhooks_list_ids = $data_store->search_webhooks(array('limit' => -1));
        if (!empty($webhooks_list_ids) && is_array($webhooks_list_ids) && count($webhooks_list_ids) > 0) {
            $webhooks = array_map('wc_get_webhook', $webhooks_list_ids);
            foreach ($webhooks as $webhook) {
                if (strpos($webhook->get_delivery_url(), $perfit_webhook_url) !== false) {
                    $webhook->delete();
                }
            }
        }
    }

    /**
     * filter action to add custom payload to the webhook payload
     *
     * @param mixed $payload
     * @param mixed $resource
     * @param mixed $resource_id
     * @param mixed $this_id
     */
    public function filter_woocommerce_webhook_payload($payload, $resource, $resource_id, $this_id) {
        if (class_exists('WooCommercePerfit') &&
                $resource == 'action' &&
                is_array($payload) &&
                isset($payload['action']) && $payload['action'] == 'woocommerce_add_to_cart') {

            global $wpdb;

            $this->log(__CLASS__ . ' ' . __FUNCTION__ . ' parameters: ' . print_r(array(
                        'payload' => $payload,
                        'resource' => $resource,
                        'resource_id' => $resource_id,
                        'this_id' => $this_id
                            ), true));

            $row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}woocommerce_sessions WHERE session_value LIKE '%" . $resource_id . "%' ORDER BY session_expiry LIMIT 1");
            if ($row) {
                // $this->log(__CLASS__ . ' '. __FUNCTION__ . ' row: ' . print_r($row, true));
                $v = maybe_unserialize($row->session_value);
                if (isset($v['cart']) && isset($v['customer'])) {
                    $cart = maybe_unserialize($v['cart']);
                    $customer = maybe_unserialize($v['customer']);
                    $payload['cart'] = $cart;
                    $payload['customer'] = $customer;
                }
            }
        }
        return $payload;
    }

}
