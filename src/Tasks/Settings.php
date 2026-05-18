<?php

namespace AgoLab\Setup\Tasks;

defined( 'ABSPATH' ) || exit;

class Settings {

    public static function set_permalinks(): array {
        global $wp_rewrite;

        update_option( 'permalink_structure', '/%postname%/' );
        $wp_rewrite->set_permalink_structure( '/%postname%/' );
        $wp_rewrite->flush_rules();

        return [ 'ok' => true, 'msg' => 'Permalinks set to /%postname%/' ];
    }

    public static function set_timezone( string $timezone ): array {
        $valid = in_array( $timezone, timezone_identifiers_list(), true );

        if ( ! $valid && 'UTC' !== $timezone ) {
            return [ 'ok' => false, 'msg' => 'Invalid timezone: ' . $timezone ];
        }

        update_option( 'timezone_string', $timezone );
        update_option( 'gmt_offset', '' );

        return [ 'ok' => true, 'msg' => 'Timezone set to ' . $timezone ];
    }

    public static function discourage_search_engines(): array {
        update_option( 'blog_public', '0' );
        return [ 'ok' => true, 'msg' => 'Search engines discouraged' ];
    }

    public static function clean_dashboard_widgets(): array {
        $user_id = get_current_user_id();
        $meta    = [
            'meta-box-order_dashboard'   => [
                'normal'  => '',
                'side'    => '',
                'column3' => '',
                'column4' => '',
            ],
            'metaboxhidden_dashboard'    => [
                'dashboard_quick_press',
                'dashboard_primary',
                'dashboard_right_now',
                'dashboard_activity',
                'dashboard_site_health',
            ],
        ];

        foreach ( $meta as $key => $value ) {
            update_user_meta( $user_id, $key, $value );
        }

        return [ 'ok' => true, 'msg' => 'Dashboard widgets cleaned for current user' ];
    }

    public static function create_privacy_page(): array {
        $existing = (int) get_option( 'wp_page_for_privacy_policy' );
        if ( $existing && get_post( $existing ) ) {
            return [ 'ok' => true, 'msg' => 'Privacy Policy page already exists' ];
        }

        $page_id = wp_insert_post( [
            'post_title'   => __( 'Privacy Policy', 'ago-setup' ),
            'post_content' => self::privacy_content(),
            'post_status'  => 'draft',
            'post_type'    => 'page',
        ] );

        if ( is_wp_error( $page_id ) ) {
            return [ 'ok' => false, 'msg' => $page_id->get_error_message() ];
        }

        update_option( 'wp_page_for_privacy_policy', $page_id );

        return [ 'ok' => true, 'msg' => 'Privacy Policy page created (draft)' ];
    }

    private static function privacy_content(): string {
        return '<!-- wp:heading --><h2>' . esc_html__( 'Who we are', 'ago-setup' ) . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . esc_html__( 'Our website address is:', 'ago-setup' ) . ' ' . esc_url( home_url() ) . '</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2>' . esc_html__( 'What personal data we collect and why', 'ago-setup' ) . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . esc_html__( 'Edit this page to add your privacy policy content.', 'ago-setup' ) . '</p><!-- /wp:paragraph -->';
    }

    /** Create Contact and About pages as drafts (Privacy uses create_privacy_page). */
    public static function create_essential_pages(): array {
        $created = [];
        $skipped = [];

        $pages = [
            'contact' => [
                'title'   => __( 'Contact', 'ago-setup' ),
                'content' => '<!-- wp:heading --><h2>' . esc_html__( 'Get in touch', 'ago-setup' ) . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . esc_html__( 'Edit this page to add your contact information, form or map.', 'ago-setup' ) . '</p><!-- /wp:paragraph -->',
            ],
            'about' => [
                'title'   => __( 'About', 'ago-setup' ),
                'content' => '<!-- wp:heading --><h2>' . esc_html__( 'About us', 'ago-setup' ) . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . esc_html__( 'Edit this page to tell visitors who you are and what you do.', 'ago-setup' ) . '</p><!-- /wp:paragraph -->',
            ],
        ];

        foreach ( $pages as $slug => $data ) {
            if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
                $skipped[] = $slug;
                continue;
            }
            $id = wp_insert_post( [
                'post_type'    => 'page',
                'post_status'  => 'draft',
                'post_title'   => $data['title'],
                'post_name'    => $slug,
                'post_content' => $data['content'],
            ], true );
            if ( ! is_wp_error( $id ) ) $created[] = $slug;
        }

        $msg = [];
        if ( $created ) $msg[] = 'Created: ' . implode( ', ', $created );
        if ( $skipped ) $msg[] = 'Already existed: ' . implode( ', ', $skipped );
        return [ 'ok' => true, 'msg' => $msg ? implode( '. ', $msg ) : 'Nothing to do' ];
    }

    /** Configure the front page to show a static page (creates a "Home" draft if none). */
    public static function set_static_front_page(): array {
        $home = get_page_by_path( 'home', OBJECT, 'page' );
        if ( ! $home ) {
            $id = wp_insert_post( [
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => __( 'Home', 'ago-setup' ),
                'post_name'    => 'home',
                'post_content' => '<!-- wp:paragraph --><p>' . esc_html__( 'Welcome. Edit this page to design your homepage.', 'ago-setup' ) . '</p><!-- /wp:paragraph -->',
            ], true );
            if ( is_wp_error( $id ) ) {
                return [ 'ok' => false, 'msg' => $id->get_error_message() ];
            }
            $home_id = $id;
        } else {
            $home_id = $home->ID;
            if ( 'publish' !== $home->post_status ) {
                wp_update_post( [ 'ID' => $home_id, 'post_status' => 'publish' ] );
            }
        }

        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $home_id );

        return [ 'ok' => true, 'msg' => 'Front page set to "Home" (static)' ];
    }
}
