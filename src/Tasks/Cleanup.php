<?php

namespace AgoLab\Setup\Tasks;

defined( 'ABSPATH' ) || exit;

class Cleanup {

    /** Address WordPress gives the sample comment it writes at install time. */
    private const SAMPLE_COMMENT_EMAIL = 'wapuu@wordpress.example';

    /**
     * Plugins and themes are shared by every site of a network, so a site
     * administrator never deletes them, and on a single site only a user who
     * may delete them does.
     */
    public static function can_delete_plugins(): bool {
        return ! is_multisite() && current_user_can( 'delete_plugins' );
    }

    public static function can_delete_themes(): bool {
        return ! is_multisite() && current_user_can( 'delete_themes' );
    }

    private static function ensure_plugin_api(): void {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( ! function_exists( 'get_filesystem_method' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
    }

    /**
     * Deleting files without direct disk access makes WordPress print a
     * credentials form and stop, which would break the JSON response.
     */
    private static function filesystem_ready(): bool {
        self::ensure_plugin_api();
        return 'direct' === get_filesystem_method();
    }

    /** @return array{ok: bool, msg: string} */
    private static function filesystem_error(): array {
        return [ 'ok' => false, 'msg' => __( 'WordPress needs FTP credentials to delete files on this server. Delete it from the Plugins or Themes screen.', 'ago-setup' ) ];
    }

    /** @return array{ok: bool, msg: string} */
    private static function not_allowed(): array {
        return [ 'ok' => false, 'msg' => __( 'Your account cannot delete plugins or themes on this site.', 'ago-setup' ) ];
    }

    /**
     * Moves to the trash the demo post or page WordPress creates at install
     * time, found by its slug. Content that only shares its ID is left alone.
     *
     * @return array{ok: bool, msg: string}
     */
    private static function trash_demo( string $slug, string $type, string $done, string $failed ): array {
        $post = get_page_by_path( $slug, OBJECT, $type );
        if ( ! $post || 'trash' === $post->post_status ) {
            return [ 'ok' => true, 'msg' => __( 'Not found, nothing to do', 'ago-setup' ) ];
        }
        return wp_trash_post( $post->ID )
            ? [ 'ok' => true, 'msg' => $done ]
            : [ 'ok' => false, 'msg' => $failed ];
    }

    public static function delete_hello_world(): array {
        return self::trash_demo(
            'hello-world',
            'post',
            __( 'Hello World post moved to the trash', 'ago-setup' ),
            __( 'Could not move the Hello World post to the trash', 'ago-setup' )
        );
    }

    public static function delete_sample_page(): array {
        return self::trash_demo(
            'sample-page',
            'page',
            __( 'Sample Page moved to the trash', 'ago-setup' ),
            __( 'Could not move the Sample Page to the trash', 'ago-setup' )
        );
    }

    public static function delete_sample_comment(): array {
        $ids = get_comments( [
            'author_email' => self::SAMPLE_COMMENT_EMAIL,
            'status'       => 'all',
            'fields'       => 'ids',
        ] );
        if ( ! $ids ) {
            return [ 'ok' => true, 'msg' => __( 'Not found, nothing to do', 'ago-setup' ) ];
        }
        foreach ( $ids as $id ) {
            if ( ! wp_trash_comment( (int) $id ) ) {
                return [ 'ok' => false, 'msg' => __( 'Could not move the sample comment to the trash', 'ago-setup' ) ];
            }
        }
        return [ 'ok' => true, 'msg' => __( 'Sample comment moved to the trash', 'ago-setup' ) ];
    }

    public static function delete_hello_dolly(): array {
        if ( ! self::can_delete_plugins() ) {
            return self::not_allowed();
        }
        if ( ! self::filesystem_ready() ) {
            return self::filesystem_error();
        }

        $plugin_file = 'hello.php';
        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
            return [ 'ok' => true, 'msg' => __( 'Hello Dolly not found', 'ago-setup' ) ];
        }

        if ( is_plugin_active( $plugin_file ) ) {
            deactivate_plugins( $plugin_file );
        }

        $result = delete_plugins( [ $plugin_file ] );
        if ( is_wp_error( $result ) ) {
            return [ 'ok' => false, 'msg' => $result->get_error_message() ];
        }

        return [ 'ok' => true, 'msg' => __( 'Hello Dolly deleted', 'ago-setup' ) ];
    }

    public static function delete_akismet(): array {
        if ( ! self::can_delete_plugins() ) {
            return self::not_allowed();
        }
        if ( ! self::filesystem_ready() ) {
            return self::filesystem_error();
        }

        $plugin_file = 'akismet/akismet.php';
        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
            return [ 'ok' => true, 'msg' => __( 'Akismet not found', 'ago-setup' ) ];
        }

        if ( is_plugin_active( $plugin_file ) ) {
            return [ 'ok' => false, 'msg' => __( 'Akismet is active, deactivate it first', 'ago-setup' ) ];
        }

        $result = delete_plugins( [ $plugin_file ] );
        if ( is_wp_error( $result ) ) {
            return [ 'ok' => false, 'msg' => $result->get_error_message() ];
        }

        return [ 'ok' => true, 'msg' => __( 'Akismet deleted', 'ago-setup' ) ];
    }

    /** Delete all themes except the active one and its parent. */
    public static function delete_unused_themes(): array {
        if ( ! self::can_delete_themes() ) {
            return self::not_allowed();
        }
        if ( ! self::filesystem_ready() ) {
            return self::filesystem_error();
        }
        if ( ! function_exists( 'delete_theme' ) ) {
            require_once ABSPATH . 'wp-admin/includes/theme.php';
        }
        WP_Filesystem();

        $active  = get_stylesheet();
        $parent  = get_template();
        $deleted = [];
        $errors  = [];

        foreach ( array_keys( wp_get_themes() ) as $slug ) {
            if ( $slug === $active || $slug === $parent ) {
                continue;
            }
            $result = delete_theme( $slug );
            if ( is_wp_error( $result ) ) {
                $errors[] = $slug;
            } else {
                $deleted[] = $slug;
            }
        }

        if ( ! empty( $errors ) && empty( $deleted ) ) {
            /* translators: %s: comma separated list of theme folders */
            return [ 'ok' => false, 'msg' => sprintf( __( 'Could not delete themes: %s', 'ago-setup' ), implode( ', ', $errors ) ) ];
        }
        if ( empty( $deleted ) ) {
            return [ 'ok' => true, 'msg' => __( 'No unused themes found', 'ago-setup' ) ];
        }
        /* translators: %s: comma separated list of theme folders */
        return [ 'ok' => true, 'msg' => sprintf( __( 'Deleted: %s', 'ago-setup' ), implode( ', ', $deleted ) ) ];
    }

    /** Remove expired transients and orphan rows. */
    public static function clean_transients_and_orphans(): array {
        global $wpdb;

        $now = time();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $expired = $wpdb->query( $wpdb->prepare(
            "DELETE a, b FROM {$wpdb->options} a, {$wpdb->options} b
             WHERE a.option_name LIKE %s
               AND a.option_name NOT LIKE %s
               AND b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
               AND b.option_value < %d",
            $wpdb->esc_like( '_transient_' ) . '%',
            $wpdb->esc_like( '_transient_timeout_' ) . '%',
            $now
        ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $orphan_rels = $wpdb->query(
            "DELETE tr FROM {$wpdb->term_relationships} tr
             LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             WHERE tt.term_taxonomy_id IS NULL"
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $orphan_meta = $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm
             LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.ID IS NULL"
        );

        return [
            'ok'  => true,
            'msg' => sprintf(
                /* translators: 1: expired transients removed, 2: orphan term relationships removed, 3: orphan post meta rows removed */
                __( 'Removed %1$d expired transients, %2$d orphan term relationships and %3$d orphan post meta rows', 'ago-setup' ),
                (int) $expired,
                (int) $orphan_rels,
                (int) $orphan_meta
            ),
        ];
    }
}
