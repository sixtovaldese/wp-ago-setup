<?php
/**
 * Smoke tests for aGo First Run.
 *
 * Covers what the WordPress.org review rules of August 2026 ask for (no bundled
 * translations, prefixed identifiers, own parent menu, translatable messages)
 * and the safety of the destructive tasks: nothing runs without an explicit
 * request from a user with the right capability, plugins and themes are only
 * deleted by users allowed to delete them, the demo content is matched by what
 * WordPress created and not by its ID, and deleted content goes to the trash.
 *
 * Run with the plugin deployed:
 *   docker compose run --rm wpcli eval-file \
 *     //var/www/html/wp-content/plugins/ago-setup/tests/smoke.php
 */

use AgoLab\Setup\Admin\Page;
use AgoLab\Setup\Plugin;
use AgoLab\Setup\Tasks\Cleanup;
use AgoLab\Setup\Tasks\Settings;

class AgoSetupSmoke {

    public static int $ok = 0;
    public static int $fail = 0;

    private const ROLE = 'agosetup_smoke_manager';

    /** @var array<string, int> */
    private static array $users = [];

    public static function check( string $name, bool $cond, string $detail = '' ): void {
        if ( $cond ) {
            self::$ok++;
            echo "  ok    {$name}\n";
        } else {
            self::$fail++;
            echo "  FALLA {$name}" . ( $detail ? " ({$detail})" : '' ) . "\n";
        }
    }

    public static function run(): void {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/user.php';

        try {
            self::users();
            self::packaging();
            self::identifiers();
            self::admin_menu();
            self::page();
            self::rest_access();
            self::task_capabilities();
            self::filesystem_credentials();
            self::demo_post( 'delete_hello_world', 'hello-world', 'post', 1 );
            self::demo_post( 'delete_sample_page', 'sample-page', 'page', 2 );
            self::sample_comment();
            self::messages();
            self::http_nonce();
        } finally {
            wp_set_current_user( 0 );
            foreach ( self::$users as $id ) {
                wp_delete_user( $id );
            }
            remove_role( self::ROLE );
        }

        echo "\n" . self::$ok . ' ok, ' . self::$fail . " fallas\n";
        if ( self::$fail ) {
            exit( 1 );
        }
    }

    private static function users(): void {
        add_role( self::ROLE, 'aGo First Run smoke', [ 'read' => true, 'manage_options' => true ] );

        $admin = get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ] );
        self::$users = [];
        foreach ( [ 'subscriber', 'editor', self::ROLE ] as $role ) {
            self::$users[ $role ] = (int) wp_insert_user( [
                'user_login' => 'agosetup_smoke_' . $role . '_' . wp_rand(),
                'user_pass'  => wp_generate_password(),
                'role'       => $role,
            ] );
        }
        $GLOBALS['agosetup_smoke_admin'] = (int) $admin[0];
    }

    private static function admin(): int {
        return (int) $GLOBALS['agosetup_smoke_admin'];
    }

    private static function run_tasks( array $tasks, int $user ): WP_REST_Response {
        wp_set_current_user( $user );
        $request = new WP_REST_Request( 'POST', '/agosetup/v1/run' );
        $request->set_header( 'Content-Type', 'application/json' );
        $request->set_body( wp_json_encode( $tasks ) );
        return rest_do_request( $request );
    }

    /** @return array<string, mixed> */
    private static function result( WP_REST_Response $response, string $task ): array {
        $data = $response->get_data();
        return (array) ( $data['results'][ $task ] ?? [] );
    }

    private static function packaging(): void {
        $data = get_plugin_data( AGOSETUP_FILE, false, false );
        self::check( 'el paquete no lleva carpeta languages', ! is_dir( AGOSETUP_PATH . 'languages' ) );
        self::check( 'el encabezado no declara Domain Path', '' === $data['DomainPath'], $data['DomainPath'] );
        self::check( 'el text domain es el slug', 'ago-setup' === $data['TextDomain'] );

        $source = '';
        foreach ( self::php_files() as $file ) {
            $source .= file_get_contents( $file );
        }
        self::check( 'nadie llama load_plugin_textdomain', ! str_contains( $source, 'load_plugin_textdomain' ) );
    }

    /** @return string[] */
    private static function php_files(): array {
        $files = [ AGOSETUP_FILE, AGOSETUP_PATH . 'uninstall.php' ];
        $it    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( AGOSETUP_PATH . 'src' ) );
        foreach ( $it as $f ) {
            if ( $f->isFile() && 'php' === $f->getExtension() ) {
                $files[] = $f->getPathname();
            }
        }
        return $files;
    }

    private static function identifiers(): void {
        $routes = array_keys( rest_get_server()->get_routes() );
        $own    = array_values( array_filter( $routes, static fn ( $r ) => str_starts_with( $r, '/agosetup/' ) ) );
        $old    = array_filter( $routes, static fn ( $r ) => str_starts_with( $r, '/ago-setup/' ) );
        sort( $own );
        self::check( 'las rutas REST usan el namespace agosetup/v1', [ '/agosetup/v1', '/agosetup/v1/deactivate', '/agosetup/v1/run' ] === $own, implode( ',', $own ) );
        self::check( 'no queda el namespace ago-setup/v1', [] === $old );

        wp_set_current_user( self::admin() );
        set_current_screen( 'tools_page_agosetup' );
        Plugin::instance()->enqueue_assets( 'agosetup-tools_page_agosetup' );
        $data = wp_scripts()->get_data( 'agosetup-admin', 'data' );
        self::check( 'el script se encola con el handle agosetup-admin', wp_script_is( 'agosetup-admin', 'enqueued' ) && wp_style_is( 'agosetup-admin', 'enqueued' ) );
        self::check( 'el objeto JS apunta a agosetup/v1', is_string( $data ) && str_ends_with( (string) ( json_decode( (string) preg_replace( '/^var agosetupData = (.*);$/s', '$1', $data ), true )['restUrl'] ?? '' ), '/agosetup/v1' ), (string) $data );

        preg_match( '/"nonce":"([a-f0-9]+)"/', (string) $data, $m );
        self::check( 'el nonce entregado al JS es de wp_rest', ! empty( $m[1] ) && false !== wp_verify_nonce( $m[1], 'wp_rest' ) );
        self::check( 'los textos del JS se entregan traducibles', is_string( $data ) && str_contains( $data, '"i18n":{' ) );
        wp_set_current_user( 0 );
    }

    private static function reset_menu(): void {
        $GLOBALS['admin_page_hooks']  = [];
        $GLOBALS['menu']              = [];
        $GLOBALS['submenu']           = [];
        $GLOBALS['_registered_pages'] = [];
        $GLOBALS['_parent_pages']     = [];
    }

    /** @return string[] */
    private static function submenu_slugs( string $parent ): array {
        return array_values( array_map( static fn ( $item ) => $item[2], $GLOBALS['submenu'][ $parent ] ?? [] ) );
    }

    private static function admin_menu(): void {
        wp_set_current_user( self::admin() );

        self::reset_menu();
        Plugin::instance()->register_admin_menu();
        self::check( 'sin el menu compartido crea su propio padre agosetup-tools', isset( $GLOBALS['admin_page_hooks']['agosetup-tools'] ) );
        self::check( 'nunca define el slug compartido agolab-tools', ! isset( $GLOBALS['admin_page_hooks']['agolab-tools'] ) );
        self::check( 'la pagina agosetup cuelga de agosetup-tools', [ 'agosetup' ] === self::submenu_slugs( 'agosetup-tools' ), implode( ',', self::submenu_slugs( 'agosetup-tools' ) ) );

        self::reset_menu();
        add_menu_page( 'aGo Tools', 'aGo Tools', 'manage_options', 'agolab-tools', '__return_null' );
        Plugin::instance()->register_admin_menu();
        self::check( 'con el menu compartido se engancha a el', in_array( 'agosetup', self::submenu_slugs( 'agolab-tools' ), true ) );
        self::check( 'con el menu compartido no crea un segundo padre', ! isset( $GLOBALS['admin_page_hooks']['agosetup-tools'] ) );

        self::reset_menu();
        wp_set_current_user( 0 );
    }

    private static function render_page( int $user ): string {
        wp_set_current_user( $user );
        ob_start();
        Page::render();
        $html = (string) ob_get_clean();
        wp_set_current_user( 0 );
        return $html;
    }

    private static function page(): void {
        $html = self::render_page( self::admin() );
        self::check( 'la pagina no imprime script en linea', ! str_contains( $html, '<script' ) );
        self::check( 'la pagina no imprime estilos en linea ni link directo', ! str_contains( $html, '<style' ) && ! str_contains( $html, '<link' ) );
        self::check( 'el administrador ve la tarea de borrar temas', str_contains( $html, 'name="delete_unused_themes"' ) );

        $html = self::render_page( self::$users[ self::ROLE ] );
        self::check( 'sin delete_themes no se ofrece borrar temas', ! str_contains( $html, 'name="delete_unused_themes"' ) );
        self::check( 'sin delete_plugins no se ofrece borrar Hello Dolly ni Akismet', ! str_contains( $html, 'name="delete_hello_dolly"' ) && ! str_contains( $html, 'name="delete_akismet"' ) );
    }

    private static function rest_access(): void {
        $tasks = [ 'discourage_search' => true ];
        $saved = get_option( 'blog_public' );
        update_option( 'blog_public', '1' );

        $r = self::run_tasks( $tasks, 0 );
        self::check( 'sin sesion /run responde 401', 401 === $r->get_status(), (string) $r->get_status() );
        $r = self::run_tasks( $tasks, self::$users['subscriber'] );
        self::check( 'un suscriptor recibe 403', 403 === $r->get_status(), (string) $r->get_status() );
        $r = self::run_tasks( $tasks, self::$users['editor'] );
        self::check( 'un editor recibe 403', 403 === $r->get_status(), (string) $r->get_status() );
        self::check( 'ninguno de los tres cambio un ajuste', '1' === (string) get_option( 'blog_public' ) );

        $r = self::run_tasks( [], self::admin() );
        self::check( 'sin tareas marcadas no corre nada', 200 === $r->get_status() && [] === ( $r->get_data()['results'] ?? null ) );
        $r = self::run_tasks( [ 'discourage_search' => false, 'delete_unused_themes' => 0 ], self::admin() );
        self::check( 'una tarea en false no corre', [] === ( $r->get_data()['results'] ?? null ) );
        self::check( 'el ajuste sigue intacto', '1' === (string) get_option( 'blog_public' ) );

        wp_set_current_user( self::$users[ self::ROLE ] );
        $request = new WP_REST_Request( 'POST', '/agosetup/v1/deactivate' );
        $r       = rest_do_request( $request );
        self::check( 'manage_options sin permiso de desactivar recibe 403', 403 === $r->get_status(), (string) $r->get_status() );
        self::check( 'el plugin sigue activo', is_plugin_active( plugin_basename( AGOSETUP_FILE ) ) );

        $user = new WP_User( self::$users[ self::ROLE ] );
        $user->add_cap( 'activate_plugins' );
        wp_set_current_user( $user->ID );
        $r = rest_do_request( new WP_REST_Request( 'POST', '/agosetup/v1/deactivate' ) );
        self::check( 'activate_plugins no basta para desactivar', 403 === $r->get_status(), (string) $r->get_status() );
        $user->remove_cap( 'activate_plugins' );

        $deny = static function ( $caps, $cap, $user_id, $args ) {
            if ( 'deactivate_plugin' === $cap && ( $args[0] ?? '' ) === plugin_basename( AGOSETUP_FILE ) ) {
                return [ 'do_not_allow' ];
            }
            return $caps;
        };
        add_filter( 'map_meta_cap', $deny, 10, 4 );
        wp_set_current_user( self::admin() );
        $r = rest_do_request( new WP_REST_Request( 'POST', '/agosetup/v1/deactivate' ) );
        remove_filter( 'map_meta_cap', $deny, 10 );
        self::check( 'desactivar consulta deactivate_plugin sobre este plugin', 403 === $r->get_status(), (string) $r->get_status() );
        self::check( 'el plugin sigue activo tras los intentos', is_plugin_active( plugin_basename( AGOSETUP_FILE ) ) );

        update_option( 'blog_public', $saved );
        wp_set_current_user( 0 );
    }

    private static function task_capabilities(): void {
        $themes = array_keys( wp_get_themes() );
        $dolly  = file_exists( WP_PLUGIN_DIR . '/hello.php' );

        $r = self::run_tasks( [ 'delete_unused_themes' => true, 'delete_hello_dolly' => true, 'delete_akismet' => true ], self::$users[ self::ROLE ] );
        self::check( 'manage_options sin delete_themes no borra temas', false === ( self::result( $r, 'delete_unused_themes' )['ok'] ?? null ) );
        self::check( 'manage_options sin delete_plugins no borra Hello Dolly', false === ( self::result( $r, 'delete_hello_dolly' )['ok'] ?? null ) );
        self::check( 'manage_options sin delete_plugins no borra Akismet', false === ( self::result( $r, 'delete_akismet' )['ok'] ?? null ) );
        self::check( 'los temas siguen ahi', array_keys( wp_get_themes( [ 'errors' => null ] ) ) == $themes || array_keys( wp_get_themes() ) === $themes );
        self::check( 'Hello Dolly sigue como estaba', file_exists( WP_PLUGIN_DIR . '/hello.php' ) === $dolly );
    }

    private static function filesystem_credentials(): void {
        $themes = array_keys( wp_get_themes() );
        $force  = static fn () => 'ftpext';
        // Under WP-CLI this filter already answers 'direct', so this one runs last.
        add_filter( 'filesystem_method', $force, PHP_INT_MAX );

        $r = self::run_tasks( [ 'delete_unused_themes' => true, 'delete_hello_dolly' => true ], self::admin() );
        self::check( 'sin acceso directo a disco, borrar temas responde error en JSON', 200 === $r->get_status() && false === ( self::result( $r, 'delete_unused_themes' )['ok'] ?? null ) );
        self::check( 'sin acceso directo a disco, borrar Hello Dolly responde error en JSON', false === ( self::result( $r, 'delete_hello_dolly' )['ok'] ?? null ) );
        wp_cache_delete( 'themes', 'themes' );
        self::check( 'y no toco los temas', array_keys( wp_get_themes() ) === $themes );

        remove_filter( 'filesystem_method', $force, PHP_INT_MAX );
    }

    /**
     * The task removes the post WordPress created at install time, found by its
     * slug. An old version fell back to the post with the install ID, which on a
     * working site can be real content.
     */
    private static function demo_post( string $task, string $slug, string $type, int $install_id ): void {
        global $wpdb;

        $aside = get_posts( [
            'name'        => $slug,
            'post_type'   => $type,
            'post_status' => [ 'publish', 'draft', 'pending', 'private', 'future', 'trash' ],
            'numberposts' => -1,
        ] );
        foreach ( $aside as $p ) {
            $wpdb->update( $wpdb->posts, [ 'post_name' => 'agosetup-smoke-aside-' . $p->ID ], [ 'ID' => $p->ID ] );
            clean_post_cache( $p->ID );
        }

        $created_install = false;
        if ( ! get_post( $install_id ) ) {
            $created_install = (int) wp_insert_post( [
                'import_id'   => $install_id,
                'post_type'   => $type,
                'post_status' => 'publish',
                'post_title'  => 'Contenido real del sitio',
                'post_name'   => 'agosetup-smoke-real-' . $install_id,
            ] ) === $install_id;
        }
        $before = get_post_status( $install_id );

        try {
            $r = self::run_tasks( [ $task => true ], self::admin() );
            self::check( "{$task}: sin '{$slug}' no toca la entrada {$install_id}", get_post_status( $install_id ) === $before, (string) get_post_status( $install_id ) );
            self::check( "{$task}: informa que no habia nada", true === ( self::result( $r, $task )['ok'] ?? null ) );

            $fixture = (int) wp_insert_post( [
                'post_type'   => $type,
                'post_status' => 'publish',
                'post_title'  => 'Demo',
                'post_name'   => $slug,
            ] );

            foreach ( [ 0, self::$users['subscriber'], self::$users['editor'] ] as $user ) {
                self::run_tasks( [ $task => true ], $user );
            }
            self::check( "{$task}: sin permiso la demo sigue publicada", 'publish' === get_post_status( $fixture ) );

            $r = self::run_tasks( [ $task => true ], self::admin() );
            self::check( "{$task}: la demo va a la papelera, no se borra", 'trash' === get_post_status( $fixture ), (string) get_post_status( $fixture ) );
            self::check( "{$task}: responde ok", true === ( self::result( $r, $task )['ok'] ?? null ) );

            if ( get_post( $fixture ) ) {
                wp_delete_post( $fixture, true );
            }
        } finally {
            if ( $created_install && get_post( $install_id ) ) {
                wp_delete_post( $install_id, true );
            }
            foreach ( $aside as $p ) {
                $wpdb->update( $wpdb->posts, [ 'post_name' => $p->post_name ], [ 'ID' => $p->ID ] );
                clean_post_cache( $p->ID );
            }
        }
    }

    /**
     * The sample comment is the one WordPress writes at install time, with the
     * wapuu@wordpress.example address. An old version deleted comment ID 1,
     * whatever it was.
     */
    private static function sample_comment(): void {
        $defaults = get_comments( [ 'author_email' => 'wapuu@wordpress.example', 'status' => 'all', 'fields' => 'ids' ] );
        $statuses = [];
        foreach ( $defaults as $id ) {
            $statuses[ $id ] = wp_get_comment_status( $id );
        }

        // Like the real sample comment, the fixtures hang from a post.
        $host = (int) wp_insert_post( [ 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'agosetup smoke' ] );

        $real = (int) wp_insert_comment( [
            'comment_post_ID'      => $host,
            'comment_author'       => 'Lectora',
            'comment_author_email' => 'lectora@example.com',
            'comment_content'      => 'Comentario real',
            'comment_approved'     => 1,
        ] );
        $demo = (int) wp_insert_comment( [
            'comment_post_ID'      => $host,
            'comment_author'       => 'A WordPress Commenter',
            'comment_author_email' => 'wapuu@wordpress.example',
            'comment_author_url'   => 'https://wordpress.org/',
            'comment_content'      => 'Hi, this is a comment.',
            'comment_approved'     => 1,
        ] );

        try {
            self::run_tasks( [ 'delete_sample_comment' => true ], self::$users['editor'] );
            self::check( 'delete_sample_comment: un editor no puede', 'approved' === wp_get_comment_status( $demo ) );

            $r = self::run_tasks( [ 'delete_sample_comment' => true ], self::admin() );
            self::check( 'delete_sample_comment: el comentario de ejemplo va a la papelera', 'trash' === wp_get_comment_status( $demo ), (string) wp_get_comment_status( $demo ) );
            self::check( 'delete_sample_comment: un comentario real no se toca', 'approved' === wp_get_comment_status( $real ), (string) wp_get_comment_status( $real ) );
            self::check( 'delete_sample_comment: responde ok', true === ( self::result( $r, 'delete_sample_comment' )['ok'] ?? null ) );
        } finally {
            foreach ( [ $real, $demo ] as $id ) {
                if ( get_comment( $id ) ) {
                    wp_delete_comment( $id, true );
                }
            }
            foreach ( $statuses as $id => $status ) {
                if ( get_comment( $id ) && 'trash' === wp_get_comment_status( $id ) && 'trash' !== $status ) {
                    wp_untrash_comment( $id );
                }
            }
            wp_delete_post( $host, true );
        }
    }

    private static function messages(): void {
        $tag = static fn ( $translation, $text, $domain ) => 'ago-setup' === $domain ? '[t] ' . $translation : $translation;
        add_filter( 'gettext', $tag, 10, 3 );

        wp_set_current_user( self::$users[ self::ROLE ] );
        $samples = [
            'zona invalida'          => Settings::set_timezone( 'Marte/Base' ),
            'borrar temas sin poder' => Cleanup::delete_unused_themes(),
        ];
        wp_set_current_user( 0 );
        remove_filter( 'gettext', $tag, 10 );

        foreach ( $samples as $name => $result ) {
            self::check( "el mensaje de {$name} pasa por traduccion", str_starts_with( (string) ( $result['msg'] ?? '' ), '[t] ' ), (string) ( $result['msg'] ?? '' ) );
        }

        $bare = [];
        foreach ( self::php_files() as $file ) {
            foreach ( file( $file ) as $n => $line ) {
                $trim = ltrim( $line );
                if ( str_starts_with( $trim, '//' ) || str_starts_with( $trim, '*' ) || str_starts_with( $trim, '/*' ) ) {
                    continue;
                }
                if ( ! preg_match_all( "/'([A-Z][a-z]+[ :][^']*)'/", $line, $m, PREG_OFFSET_CAPTURE ) ) {
                    continue;
                }
                foreach ( $m[0] as $hit ) {
                    $before = substr( $line, 0, $hit[1] );
                    if ( ! preg_match( '/(__|_e|_x|_n|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(\s*$/', $before ) ) {
                        $bare[] = basename( $file ) . ':' . ( $n + 1 ) . ' ' . $hit[0];
                    }
                }
            }
        }
        self::check( 'ningun texto en ingles queda fuera de una funcion de traduccion', [] === $bare, implode( ' | ', array_slice( $bare, 0, 6 ) ) );

        $js = (string) file_get_contents( AGOSETUP_PATH . 'assets/js/admin.js' );
        self::check( 'el JS no trae textos fijos en ingles', ! preg_match( "/'[A-Z][a-z]+[ :!.\x{2026}]/u", $js ) );
    }

    /**
     * The real request from the admin screen: a session cookie alone does not
     * authenticate a REST call, the wp_rest nonce is required too.
     */
    private static function http_nonce(): void {
        $admin   = self::admin();
        $expire  = time() + HOUR_IN_SECONDS;
        $token   = WP_Session_Tokens::get_instance( $admin )->create( $expire );
        $cookies = [
            AUTH_COOKIE        => wp_generate_auth_cookie( $admin, $expire, 'auth', $token ),
            SECURE_AUTH_COOKIE => wp_generate_auth_cookie( $admin, $expire, 'secure_auth', $token ),
            LOGGED_IN_COOKIE   => wp_generate_auth_cookie( $admin, $expire, 'logged_in', $token ),
        ];
        $_COOKIE[ LOGGED_IN_COOKIE ] = $cookies[ LOGGED_IN_COOKIE ];
        wp_set_current_user( $admin );
        $nonce = wp_create_nonce( 'wp_rest' );
        wp_set_current_user( 0 );
        unset( $_COOKIE[ LOGGED_IN_COOKIE ] );

        $saved = get_option( 'blog_public' );
        update_option( 'blog_public', '1' );

        $host = wp_parse_url( home_url(), PHP_URL_HOST );
        $post = static function ( array $headers ) use ( $host, $cookies ) {
            return wp_remote_post( 'http://nginx/?rest_route=/agosetup/v1/run', [
                'headers'     => array_merge( [ 'Host' => $host, 'X-Forwarded-Proto' => 'https', 'Content-Type' => 'application/json' ], $headers ),
                'cookies'     => $cookies,
                'body'        => wp_json_encode( [ 'discourage_search' => true ] ),
                'redirection' => 0,
                'timeout'     => 15,
            ] );
        };

        $r = $post( [] );
        if ( is_wp_error( $r ) ) {
            self::check( 'el sitio responde por HTTP', false, $r->get_error_message() );
        } else {
            self::check( 'con cookie y sin nonce responde 401', 401 === wp_remote_retrieve_response_code( $r ), (string) wp_remote_retrieve_response_code( $r ) );
            wp_cache_delete( 'alloptions', 'options' );
            self::check( 'y no cambia el ajuste', '1' === (string) get_option( 'blog_public' ) );

            $r = $post( [ 'X-WP-Nonce' => $nonce ] );
            self::check( 'con cookie y nonce la tarea corre', 200 === wp_remote_retrieve_response_code( $r ), (string) wp_remote_retrieve_response_code( $r ) . ' ' . substr( wp_remote_retrieve_body( $r ), 0, 120 ) );
        }

        update_option( 'blog_public', $saved );
        WP_Session_Tokens::get_instance( $admin )->destroy( $token );
    }
}

AgoSetupSmoke::run();
