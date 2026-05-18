<?php

namespace AgoLab\Setup\Tasks;

defined( 'ABSPATH' ) || exit;

class Comments {

    public static function disable_comments(): array {
        update_option( 'default_comment_status', 'closed' );
        update_option( 'require_name_email', 1 );
        update_option( 'comment_registration', 1 );

        // Close comments on all existing posts
        global $wpdb;
        $wpdb->update( $wpdb->posts, [ 'comment_status' => 'closed' ], [ 'comment_status' => 'open' ] );

        return [ 'ok' => true, 'msg' => 'Comments disabled globally' ];
    }

    public static function disable_pingbacks(): array {
        update_option( 'default_ping_status', 'closed' );

        // Close pingbacks on all existing posts
        global $wpdb;
        $wpdb->update( $wpdb->posts, [ 'ping_status' => 'closed' ], [ 'ping_status' => 'open' ] );

        return [ 'ok' => true, 'msg' => 'Pingbacks/trackbacks disabled globally' ];
    }
}
