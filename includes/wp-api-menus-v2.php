<?php
/**
 * WP REST API Menu routes
 *
 * @package WP_API_Menus
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (! class_exists('WP_REST_Menus')) :


    /**
     * WP REST Menus class.
     *
     * WP API Menus support for WP API v2.
     *
     * @package WP_API_Menus
     * @since 1.2.0
     */
    class WP_REST_Menus
    {


        /**
         * Get WP API namespace.
         *
         * @since 1.2.0
         * @return string
         */
        public static function get_api_namespace()
        {
            return 'wp/v2';
        }


        /**
         * Get WP API Menus namespace.
         *
         * @since 1.2.1
         * @return string
         */
        public static function get_plugin_namespace()
        {
            return 'wp-api-menus/v2';
        }


        /**
         * Register menu routes for WP API v2.
         *
         * @since  1.2.0
         */
        public function register_routes()
        {
            register_rest_route(self::get_plugin_namespace(), '/menus', array(
                array(
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => array( $this, 'get_menus' ),
                )
            ));

            register_rest_route(self::get_plugin_namespace(), '/menus/(?P<id>\d+)', array(
                array(
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => array( $this, 'get_menu' ),
                    'args'     => array(
                        'context' => array(
                        'default' => 'view',
                        ),
                    ),
                )
            ));

            register_rest_route(self::get_plugin_namespace(), '/locations', array(
                array(
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => array( $this, 'get_menu_locations' ),
                )
            ));

            register_rest_route(self::get_plugin_namespace(), '/locations/(?P<location>[a-zA-Z0-9_-]+)', array(
                array(
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => array( $this, 'get_menu_location' ),
                )
            ));
        }

        /**
         * Get menus.
         *
         * @since  1.2.0
         * @return array All registered menus
         */
        public function get_menus()
        {
            $rest_url = trailingslashit(get_rest_url() . self::get_plugin_namespace() . '/menus/');
            $wp_menus = wp_get_nav_menus();

            $rest_menus = array();

            foreach ($wp_menus as $wp_menu) {
                $rest_menus[] = $this->get_menu(array( 'id' => $wp_menu->term_id ));
            }

            return apply_filters('rest_menus_format_menus', $rest_menus);
        }


        /**
         * Get a menu.
         *
         * @since  1.2.0
         * @param  $request
         * @return array Menu data
         */
        public function get_menu($request)
        {
            $id             = (int) $request['id'];
            $rest_url       = get_rest_url() . self::get_plugin_namespace() . '/menus/';
            $wp_menu_object = $id ? wp_get_nav_menu_object($id) : array();
            $wp_menu_items  = $id ? wp_get_nav_menu_items($id) : array();

            $rest_menu = array();

            if ($wp_menu_object) {
                $menu = (array) $wp_menu_object;
                $rest_menu['id']          = abs($menu['term_id']);
                $rest_menu['name']        = $menu['name'];
                $rest_menu['slug']        = $menu['slug'];
                $rest_menu['count']       = abs($menu['count']);

                $rest_menu['items'] = call_user_func_array(array( new WalkerRestMenu(), 'walk' ), array($wp_menu_items, 0));

                $rest_menu['_links'] = array(
                    'self' => array(
                        'href'   => $rest_url . $id,
                    ),
                    'collection' => array(
                        'href'   => $rest_url,
                    ),
                );
            }

            return apply_filters('rest_menus_format_menu', $rest_menu);
        }

        /**
         * Get menu locations.
         *
         * @since 1.2.0
         * @param  $request
         * @return array All registered menus locations
         */
        public static function get_menu_locations($request)
        {
            $locations        = get_nav_menu_locations();
            $registered_menus = get_registered_nav_menus();
            $rest_url         = get_rest_url() . self::get_plugin_namespace() . '/locations/';
            $rest_menus       = array();

            if ($locations && $registered_menus) {
                foreach ($registered_menus as $slug => $label) {

                    // Sanity check
                    if (! isset($locations[ $slug ])) {
                        continue;
                    }

                    $rest_menu = &$rest_menus[];

                    $rest_menu['slug']                        = $slug;
                    $rest_menu['label']                       = $label;
                    $rest_menu['id']                          = $locations[ $slug ];
                    $rest_menu['meta']['links']['collection'] = $rest_url;
                    $rest_menu['meta']['links']['self']       = $rest_url . $slug;
                }
            }

            return $rest_menus;
        }


        /**
         * Get menu for location.
         *
         * @since 1.2.0
         * @param  $request
         * @return array The menu for the corresponding location
         */
        public function get_menu_location($request)
        {
            $params     = $request->get_params();
            $location   = $params['location'];
            $locations  = get_nav_menu_locations();
            $registered_menus = get_registered_nav_menus();

            if (!isset($locations[$location])) {
                return array();
            }

            $wp_menu_object = wp_get_nav_menu_object($locations[$location]);
            $wp_menu_items = wp_get_nav_menu_items($wp_menu_object->term_id);

            $rest_url = get_rest_url() . self::get_plugin_namespace() . '/locations/';

            $menu_location = array();
            $menu_location['slug']                        = $location;
            $menu_location['label']                       = $registered_menus[$location];
            $menu_location['id']                          = $locations[$location];

            $menu_location['items'] = call_user_func_array(array( new WalkerRestMenu(), 'walk' ), array($wp_menu_items, 0));

            $menu_location['meta']['links']['collection'] = $rest_url;
            $menu_location['meta']['links']['self']       = $rest_url . $location;

            return $menu_location;
        }
    }

endif;
