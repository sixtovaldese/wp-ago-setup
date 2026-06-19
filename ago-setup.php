<?php
/**
 * Plugin Name: aGo First Run
 * Plugin URI:  https://ago.cl/herramientas/
 * Description: First-run wizard for fresh WordPress installs. Clean demo content, set permalinks, timezone and language, create essential pages (Privacy, Contact, About), remove unused themes, clean transients and orphan terms, in one click.
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author:      aGo Lab
 * Author URI:  https://ago.cl/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ago-setup
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'AGOSETUP_VERSION', '1.0.0' );
define( 'AGOSETUP_FILE', __FILE__ );
define( 'AGOSETUP_PATH', plugin_dir_path( __FILE__ ) );
define( 'AGOSETUP_URL', plugin_dir_url( __FILE__ ) );

// PSR-4 Autoloader
spl_autoload_register( function ( string $class ): void {
    $prefix = 'AgoLab\\Setup\\';
    if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
        return;
    }
    $relative = substr( $class, strlen( $prefix ) );
    $file     = AGOSETUP_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// Boot
add_action( 'plugins_loaded', [ AgoLab\Setup\Plugin::class, 'instance' ] );
