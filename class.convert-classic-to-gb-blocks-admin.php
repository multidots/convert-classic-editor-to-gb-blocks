<?php

/**
 * Exit if accessed directly
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

class CCETGB_Admin_Settings {

    private static $initiated = false;

    public static function ccetgb_init() {
        if ( ! self::$initiated ) {
            self::ccetgb_init_hooks();
        }
    }

    /**
     * Initializes WordPress hooks
     */
    private static function ccetgb_init_hooks() {
        self::$initiated = true;
        add_action( 'wp_before_admin_bar_render', array( __CLASS__, 'ccetgb_admin_bar_render' ) );
        add_action( 'admin_notices', array( __CLASS__, 'ccetgb_admin_notice' ) );
        add_action( 'admin_menu', array( __CLASS__, 'ccetgb_add_settings_options_page' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'ccetgb_enqueque_style' ) );
        add_action( 'admin_init', array(__CLASS__,'ccetgb_redirect_to_welcome_screen') );
    }

    /**
     * Redirect to welcome screen after activate the plugin
     */
    public static function ccetgb_redirect_to_welcome_screen() {
        // if no activation redirect
        if ( ! get_transient('_ccetgb_welcome_screen_redirect')) {
            return;
        }

        // Delete the redirect transient
        delete_transient('_ccetgb_welcome_screen_redirect');

        // if activating from network, or bulk
        if ( is_network_admin() ) {
            return;
        }

        // Redirect to welcome page
        wp_safe_redirect(add_query_arg(array('page' => 'ccetgb-classic-editor-to-gutenberg-blocks'), admin_url('options-general.php')));
        exit();
    }

    /**
     * Add options page under the setting menu
     */
    public static function ccetgb_add_settings_options_page() {
        add_options_page('Convert Classic Editor to Gutenberg Blocks','Convert Classic Editor to Gutenberg Blocks','manage_options','ccetgb-classic-editor-to-gutenberg-blocks', array(__CLASS__,'ccetgb_options_page_settings') );
    }

    /**
     * Plugin setting page
     */
    public static function ccetgb_options_page_settings() {
        require_once( plugin_dir_path( __FILE__ ) . 'inc/plugin-settings-page.php' );
    }

    /**
     * Enqueque style for admin
     */
    public static function ccetgb_enqueque_style() {
        wp_enqueue_style('ccetgb-main-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), false);
    }

    /**
     * Display success message on converted draft post
     */
    public static function ccetgb_admin_notice() {
        global $pagenow;
        if ( 'post.php' === $pagenow ) {
            $success = filter_input( INPUT_GET, 'success', FILTER_SANITIZE_STRING );
            if ( isset( $success ) && ! empty( $success ) ) {
                wp_enqueue_script(
                    'ccetgb-admin-notice', // Unique handle.
                    plugins_url( 'assets/js/admin-notice.js', __FILE__ )
                );
            }
        }
    }

    /**
     * Add menu in admin bar only for classic editor.
     *
     * @since 1.0.0
     * @static
     */
    public static function ccetgb_admin_bar_render() {

        if ( ! is_admin_bar_showing() ) {
            return;
        }

        global $post, $wp_admin_bar;
        if ( isset( $post ) && ! ( strpos( $post->post_content, "<!-- wp:" ) === false ) ){
          return;
        }

        $queried_object = get_queried_object();
        $id  = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

        if ( ! empty( $queried_object ) ) {
            if ( ! empty( $queried_object->post_type ) ) {
                $wp_admin_bar->add_menu( array(
                    'id' => 'ccetgb_copy_to_draft',
                    'title' => esc_attr__("Convert to Gutenberg Blocks", 'convert-classic-editor-to-gutenberg-blocks'),
                    'href' => self::ccetgb_create_clone_post_link( $queried_object->ID )
                ) );
            }
        } else if ( is_admin() && isset( $id ) && ! empty( $id ) ) {
            $wp_admin_bar->add_menu( array(
                'id' => 'ccetgb_copy_to_draft',
                'title' => esc_attr__("Convert to Gutenberg Blocks", 'convert-classic-editor-to-gutenberg-blocks'),
                'href' => self::ccetgb_create_clone_post_link( $id )
            ) );
        }
    }

    /**
     * Create clone post link for admin bar
     *
     * @param int $id
     * @return string
     */
    public static function ccetgb_create_clone_post_link( $id = 0 ) {

        $post = get_post( $id );
        if ( ! $post ) {
            return '';
        }

        $action_name = "ccetgb_copy_as_new_draft";
        $action = '?action='.$action_name.'&amp;post='.$post->ID;
        $post_type_object = get_post_type_object( $post->post_type );

        if ( ! $post_type_object ) {
            return '';
        }

        return wp_nonce_url( apply_filters( 'ccetgb_create_clone_post_link', admin_url( "admin.php". $action ), $post->ID, 'display' ), 'ccetgb_clone_post_' . $post->ID );
    }


    /**
     * Fire on plugin activation and check the multipurpose plugin is active or not
     *
     * @since 1.0.0
     * @static
     */

    public static function ccetgb_plugin_activation() {

        if ( ! is_plugin_active( 'multipurpose-block/index.php' ) ) {

            deactivate_plugins( plugin_basename( __FILE__ ) );

            $error_msg = sprintf( 'This plugin required <a href="https://wordpress.org/plugins/multipurpose-block/">Multipurpose Gutenberg Block</a> plugin to work correctly. <a href="%s">Return</a>', admin_url( 'plugins.php' ) );
            $elment_array = array( 'a' => array( 'href' => array() ) );
            wp_die( wp_kses( $error_msg, $elment_array ));
        } else {
            set_transient('_ccetgb_welcome_screen_redirect', true, 30);
        }
    }
}