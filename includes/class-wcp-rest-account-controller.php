<?php

defined('ABSPATH') || exit;

/**
 * REST API Account controller class.
 * @extends WC_REST_Controller
 */
class WCP_REST_Account_Controller extends WP_Rest_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wcperfit/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'account';

    /**
     * Register routes.
     */
    public function register_routes() {
        register_rest_route(
                $this->namespace, '/' . $this->rest_base, array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_account_info'),
            ),
            'schema' => array($this, 'get_schema'),
                )
        );
    }
    
    /**
     * Get base route.
     * @return string
     */
    public function get_base_route() {
        return $this->namespace . '/' . $this->rest_base;
    }

    /**
     * Check whether a given request has permission to read site settings.
     *
     * @param  WP_REST_Request $request Full details about the request.
     * @return WP_Error|boolean
     */
    public function get_account_permissions_check($request) {
        if (!wc_rest_check_manager_permissions('settings', 'read')) {
            return new WP_Error('woocommerce_rest_cannot_view', __('Sorry, you cannot view this resource.', 'woocommerce'), array('status' => rest_authorization_required_code()));
        }
        return true;
    }

    /**
     * Return info of site.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_account_info($request) {
        $custom_logo_id = get_theme_mod('custom_logo');
        $url_logo = wp_get_attachment_url($custom_logo_id);

        $data = array(
            'site_name' => get_bloginfo(),
            'site_url' => get_site_url(),
            'logo_url' => ($url_logo ? $url_logo : null)
        );
        return rest_ensure_response($data);
    }

    /**
     * Get schema conforming to JSON Schema.
     * @return array
     */
    public function get_schema() {
        $schema = array(
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'data_index',
            'type' => 'object',
            'properties' => array(
                'site_name' => array(
                    'description' => __('Site name.', 'woocommerce-perfit'),
                    'type' => 'string',
                    'context' => array('view'),
                    'readonly' => true,
                ),
                'site_url' => array(
                    'description' => __('Site URL.', 'woocommerce-perfit'),
                    'type' => 'string',
                    'context' => array('view'),
                    'readonly' => true,
                ),
                'logo_url' => array(
                    'description' => __('Site logo.', 'woocommerce-perfit'),
                    'type' => 'string',
                    'context' => array('view'),
                    'readonly' => true,
                ),
            ),
        );

        return $this->add_additional_fields_schema($schema);
    }

}
