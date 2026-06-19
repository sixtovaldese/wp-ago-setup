<?php

namespace AgoLab\Setup;

defined( 'ABSPATH' ) || exit;

class Plugin {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function load_textdomain(): void {
        load_plugin_textdomain( 'ago-setup', false, dirname( plugin_basename( AGOSETUP_FILE ) ) . '/languages' );
    }

    public function register_admin_menu(): void {
        if ( empty( $GLOBALS['admin_page_hooks']['agolab-tools'] ) ) {
            add_menu_page(
                __( 'aGo Tools', 'ago-setup' ),
                __( 'aGo Tools', 'ago-setup' ),
                'manage_options',
                'agolab-tools',
                '__return_null',
                'dashicons-hammer',
                81
            );
        }

        add_submenu_page(
            'agolab-tools',
            __( 'aGo First Run', 'ago-setup' ),
            __( 'First Run', 'ago-setup' ),
            'manage_options',
            'agosetup',
            [ Admin\Page::class, 'render' ]
        );

        remove_submenu_page( 'agolab-tools', 'agolab-tools' );
    }

    public function register_rest_routes(): void {
        register_rest_route( 'ago-setup/v1', '/run', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_run' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        register_rest_route( 'ago-setup/v1', '/deactivate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_deactivate' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );

        register_rest_route( 'ago-setup/v1', '/status', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_status' ],
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    public function handle_status(): \WP_REST_Response {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return new \WP_REST_Response( [
            'hello_world'    => ! empty( get_page_by_path( 'hello-world', OBJECT, 'post' ) ),
            'sample_page'    => ! empty( get_page_by_path( 'sample-page', OBJECT, 'page' ) ),
            'hello_dolly'    => file_exists( WP_PLUGIN_DIR . '/hello.php' ),
            'akismet'        => file_exists( WP_PLUGIN_DIR . '/akismet/akismet.php' ) && ! is_plugin_active( 'akismet/akismet.php' ),
            'permalinks'     => get_option( 'permalink_structure' ) !== '/%postname%/',
            'comments'       => get_option( 'default_comment_status' ) !== 'closed',
            'pingbacks'      => get_option( 'default_ping_status' ) !== 'closed',
            'timezone'       => get_option( 'timezone_string', '' ),
            'search_engines' => (int) get_option( 'blog_public', 1 ) === 1,
            'privacy_page'   => empty( get_option( 'wp_page_for_privacy_policy' ) ),
        ] );
    }

    public function handle_run( \WP_REST_Request $request ): \WP_REST_Response {
        $tasks   = $request->get_json_params();
        $results = [];

        if ( ! empty( $tasks['delete_hello_world'] ) ) {
            $results['delete_hello_world'] = Tasks\Cleanup::delete_hello_world();
        }

        if ( ! empty( $tasks['delete_sample_page'] ) ) {
            $results['delete_sample_page'] = Tasks\Cleanup::delete_sample_page();
        }

        if ( ! empty( $tasks['delete_sample_comment'] ) ) {
            $results['delete_sample_comment'] = Tasks\Cleanup::delete_sample_comment();
        }

        if ( ! empty( $tasks['delete_hello_dolly'] ) ) {
            $results['delete_hello_dolly'] = Tasks\Cleanup::delete_hello_dolly();
        }

        if ( ! empty( $tasks['delete_akismet'] ) ) {
            $results['delete_akismet'] = Tasks\Cleanup::delete_akismet();
        }

        if ( ! empty( $tasks['set_permalinks'] ) ) {
            $results['set_permalinks'] = Tasks\Settings::set_permalinks();
        }

        if ( ! empty( $tasks['disable_comments'] ) ) {
            $results['disable_comments'] = Tasks\Comments::disable_comments();
        }

        if ( ! empty( $tasks['disable_pingbacks'] ) ) {
            $results['disable_pingbacks'] = Tasks\Comments::disable_pingbacks();
        }

        if ( ! empty( $tasks['set_timezone'] ) ) {
            $tz = sanitize_text_field( $tasks['timezone_value'] ?? 'UTC' );
            $results['set_timezone'] = Tasks\Settings::set_timezone( $tz );
        }

        if ( ! empty( $tasks['discourage_search'] ) ) {
            $results['discourage_search'] = Tasks\Settings::discourage_search_engines();
        }

        if ( ! empty( $tasks['clean_dashboard'] ) ) {
            $results['clean_dashboard'] = Tasks\Settings::clean_dashboard_widgets();
        }

        if ( ! empty( $tasks['create_privacy_page'] ) ) {
            $results['create_privacy_page'] = Tasks\Settings::create_privacy_page();
        }

        if ( ! empty( $tasks['delete_unused_themes'] ) ) {
            $results['delete_unused_themes'] = Tasks\Cleanup::delete_unused_themes();
        }

        if ( ! empty( $tasks['create_essential_pages'] ) ) {
            $results['create_essential_pages'] = Tasks\Settings::create_essential_pages();
        }

        if ( ! empty( $tasks['set_static_front_page'] ) ) {
            $results['set_static_front_page'] = Tasks\Settings::set_static_front_page();
        }

        if ( ! empty( $tasks['clean_transients'] ) ) {
            $results['clean_transients'] = Tasks\Cleanup::clean_transients_and_orphans();
        }

        return new \WP_REST_Response( [ 'results' => $results ] );
    }

    public function handle_deactivate(): \WP_REST_Response {
        deactivate_plugins( plugin_basename( AGOSETUP_FILE ) );
        return new \WP_REST_Response( [ 'deactivated' => true ] );
    }

    public function enqueue_assets( string $hook ): void {
        if ( ! str_ends_with( $hook, '_page_agosetup' ) ) {
            return;
        }

        wp_enqueue_style(
            'agosetup-admin',
            AGOSETUP_URL . 'assets/css/admin.css',
            [],
            AGOSETUP_VERSION
        );

        wp_enqueue_script(
            'agosetup-admin',
            AGOSETUP_URL . 'assets/js/admin.js',
            [],
            AGOSETUP_VERSION,
            true
        );

        wp_localize_script( 'agosetup-admin', 'agosetupData', [
            'restUrl' => rest_url( 'ago-setup/v1' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'adminUrl' => admin_url(),
        ] );
    }
}
