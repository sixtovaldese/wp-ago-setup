<?php

namespace AgoLab\Setup;

defined( 'ABSPATH' ) || exit;

class Plugin {

    private static ?self $instance = null;

    private const REST_NAMESPACE = 'agosetup/v1';

    private const MENU_SLUG = 'agosetup-tools';

    /**
     * Each task the wizard can run, keyed by the name the admin screen sends.
     * A task runs only when the request names it with a true value.
     */
    private const TASKS = [
        'delete_hello_world'     => [ Tasks\Cleanup::class, 'delete_hello_world' ],
        'delete_sample_page'     => [ Tasks\Cleanup::class, 'delete_sample_page' ],
        'delete_sample_comment'  => [ Tasks\Cleanup::class, 'delete_sample_comment' ],
        'delete_hello_dolly'     => [ Tasks\Cleanup::class, 'delete_hello_dolly' ],
        'delete_akismet'         => [ Tasks\Cleanup::class, 'delete_akismet' ],
        'set_permalinks'         => [ Tasks\Settings::class, 'set_permalinks' ],
        'disable_comments'       => [ Tasks\Comments::class, 'disable_comments' ],
        'disable_pingbacks'      => [ Tasks\Comments::class, 'disable_pingbacks' ],
        'discourage_search'      => [ Tasks\Settings::class, 'discourage_search_engines' ],
        'clean_dashboard'        => [ Tasks\Settings::class, 'clean_dashboard_widgets' ],
        'create_privacy_page'    => [ Tasks\Settings::class, 'create_privacy_page' ],
        'delete_unused_themes'   => [ Tasks\Cleanup::class, 'delete_unused_themes' ],
        'create_essential_pages' => [ Tasks\Settings::class, 'create_essential_pages' ],
        'set_static_front_page'  => [ Tasks\Settings::class, 'set_static_front_page' ],
        'clean_transients'       => [ Tasks\Cleanup::class, 'clean_transients_and_orphans' ],
    ];

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function register_admin_menu(): void {
        /*
         * Other plugins from the same author may already have added a shared
         * parent menu. When one is there the page goes under it, so the
         * dashboard does not grow a second top level entry for the same set of
         * tools. When there is none, this plugin creates its own parent under
         * its own prefix and never defines the shared slug.
         */
        $parent = 'agolab-tools';

        if ( empty( $GLOBALS['admin_page_hooks'][ $parent ] ) ) {
            $parent = self::MENU_SLUG;

            add_menu_page(
                __( 'aGo Tools', 'ago-setup' ),
                __( 'aGo Tools', 'ago-setup' ),
                'manage_options',
                $parent,
                '__return_null',
                'dashicons-hammer',
                81
            );
        }

        add_submenu_page(
            $parent,
            __( 'aGo First Run', 'ago-setup' ),
            __( 'First Run', 'ago-setup' ),
            'manage_options',
            'agosetup',
            [ Admin\Page::class, 'render' ]
        );

        remove_submenu_page( $parent, $parent );
    }

    public function register_rest_routes(): void {
        register_rest_route( self::REST_NAMESPACE, '/run', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_run' ],
            'permission_callback' => static fn () => current_user_can( 'manage_options' ),
        ] );

        register_rest_route( self::REST_NAMESPACE, '/deactivate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_deactivate' ],
            'permission_callback' => [ $this, 'can_deactivate' ],
        ] );
    }

    public function handle_run( \WP_REST_Request $request ): \WP_REST_Response {
        $tasks   = (array) $request->get_json_params();
        $results = [];

        foreach ( self::TASKS as $key => $callback ) {
            if ( empty( $tasks[ $key ] ) ) {
                continue;
            }
            $results[ $key ] = call_user_func( $callback );
        }

        if ( ! empty( $tasks['set_timezone'] ) ) {
            $tz = sanitize_text_field( wp_unslash( (string) ( $tasks['timezone_value'] ?? 'UTC' ) ) );
            $results['set_timezone'] = Tasks\Settings::set_timezone( $tz );
        }

        return new \WP_REST_Response( [ 'results' => $results ] );
    }

    /**
     * Who may turn this plugin off: the plugin-specific deactivate_plugin
     * capability, and on multisite a network-active copy only by someone who
     * manages network plugins, as on the Network Admin plugins screen.
     */
    public function can_deactivate(): bool {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $file = plugin_basename( AGOSETUP_FILE );

        if ( is_multisite() && is_plugin_active_for_network( $file ) ) {
            return current_user_can( 'manage_network_plugins' );
        }

        return current_user_can( 'deactivate_plugin', $file );
    }

    public function handle_deactivate(): \WP_REST_Response {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $file = plugin_basename( AGOSETUP_FILE );
        deactivate_plugins( $file, false, is_multisite() && is_plugin_active_for_network( $file ) );
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
            'restUrl'  => rest_url( self::REST_NAMESPACE ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'adminUrl' => admin_url(),
            'i18n'     => [
                'noTask'       => __( 'Please select at least one task.', 'ago-setup' ),
                'running'      => __( 'Running...', 'ago-setup' ),
                'run'          => __( 'Run', 'ago-setup' ),
                /* translators: %d: number of tasks that ran */
                'done'         => __( 'Tasks completed: %d', 'ago-setup' ),
                'issues'       => __( 'Some tasks had issues. Check the status next to each task.', 'ago-setup' ),
                'error'        => __( 'The request failed:', 'ago-setup' ),
                'deactivating' => __( 'Deactivating...', 'ago-setup' ),
                'deactivate'   => __( 'Complete & Deactivate Plugin', 'ago-setup' ),
            ],
        ] );
    }
}
