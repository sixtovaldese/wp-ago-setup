<?php

namespace AgoLab\Setup\Tasks;

defined( 'ABSPATH' ) || exit;

class Cleanup {

    private static function ensure_plugin_api(): void {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( ! function_exists( 'delete_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
    }

    public static function delete_hello_world(): array {
        $post = get_page_by_path( 'hello-world', OBJECT, 'post' );
        if ( ! $post ) {
            // Try by ID (default post ID 1)
            $post = get_post( 1 );
            if ( ! $post || 'post' !== $post->post_type ) {
                return [ 'ok' => true, 'msg' => 'Already deleted' ];
            }
        }
        $deleted = wp_delete_post( $post->ID, true );
        return $deleted
            ? [ 'ok' => true, 'msg' => 'Hello World post deleted' ]
            : [ 'ok' => false, 'msg' => 'Could not delete Hello World post' ];
    }

    public static function delete_sample_page(): array {
        $page = get_page_by_path( 'sample-page', OBJECT, 'page' );
        if ( ! $page ) {
            $page = get_post( 2 );
            if ( ! $page || 'page' !== $page->post_type ) {
                return [ 'ok' => true, 'msg' => 'Already deleted' ];
            }
        }
        $deleted = wp_delete_post( $page->ID, true );
        return $deleted
            ? [ 'ok' => true, 'msg' => 'Sample Page deleted' ]
            : [ 'ok' => false, 'msg' => 'Could not delete Sample Page' ];
    }

    public static function delete_sample_comment(): array {
        $comment = get_comment( 1 );
        if ( ! $comment ) {
            return [ 'ok' => true, 'msg' => 'Already deleted' ];
        }
        $deleted = wp_delete_comment( 1, true );
        return $deleted
            ? [ 'ok' => true, 'msg' => 'Sample comment deleted' ]
            : [ 'ok' => false, 'msg' => 'Could not delete sample comment' ];
    }

    public static function delete_hello_dolly(): array {
        self::ensure_plugin_api();
        $plugin_file = 'hello.php';
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;

        if ( ! file_exists( $plugin_path ) ) {
            return [ 'ok' => true, 'msg' => 'Hello Dolly not found' ];
        }

        if ( is_plugin_active( $plugin_file ) ) {
            deactivate_plugins( $plugin_file );
        }

        $result = delete_plugins( [ $plugin_file ] );
        if ( is_wp_error( $result ) ) {
            return [ 'ok' => false, 'msg' => $result->get_error_message() ];
        }

        return [ 'ok' => true, 'msg' => 'Hello Dolly deleted' ];
    }

    public static function delete_akismet(): array {
        self::ensure_plugin_api();
        $plugin_file = 'akismet/akismet.php';
        $plugin_path = WP_PLUGIN_DIR . '/akismet/akismet.php';

        if ( ! file_exists( $plugin_path ) ) {
            return [ 'ok' => true, 'msg' => 'Akismet not found' ];
        }

        if ( is_plugin_active( $plugin_file ) ) {
            return [ 'ok' => false, 'msg' => 'Akismet is active, deactivate it first' ];
        }

        $result = delete_plugins( [ $plugin_file ] );
        if ( is_wp_error( $result ) ) {
            return [ 'ok' => false, 'msg' => $result->get_error_message() ];
        }

        return [ 'ok' => true, 'msg' => 'Akismet deleted' ];
    }

    /** Delete all themes except the currently active one. */
    public static function delete_unused_themes(): array {
        if ( ! function_exists( 'delete_theme' ) ) {
            require_once ABSPATH . 'wp-admin/includes/theme.php';
        }
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();

        $active  = get_stylesheet();
        $parent  = get_template();
        $themes  = wp_get_themes();
        $deleted = [];
        $errors  = [];

        foreach ( $themes as $slug => $theme ) {
            if ( $slug === $active || $slug === $parent ) continue;
            $result = delete_theme( $slug );
            if ( is_wp_error( $result ) ) {
                $errors[] = $slug;
            } else {
                $deleted[] = $slug;
            }
        }

        if ( ! empty( $errors ) && empty( $deleted ) ) {
            return [ 'ok' => false, 'msg' => 'Could not delete themes: ' . implode( ', ', $errors ) ];
        }
        if ( empty( $deleted ) ) {
            return [ 'ok' => true, 'msg' => 'No unused themes found' ];
        }
        return [ 'ok' => true, 'msg' => 'Deleted: ' . implode( ', ', $deleted ) ];
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
                'Removed %d expired transients, %d orphan term relationships, %d orphan post metas',
                (int) $expired, (int) $orphan_rels, (int) $orphan_meta
            ),
        ];
    }
}
