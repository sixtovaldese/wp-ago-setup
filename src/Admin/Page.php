<?php

namespace AgoLab\Setup\Admin;

defined( 'ABSPATH' ) || exit;

class Page {

    public static function render(): void {
        $timezones = timezone_identifiers_list();
        $current_tz = get_option( 'timezone_string', 'UTC' );
        ?>
        <div class="wrap">
            <h1>
                <img src="<?php echo esc_url( AGOSETUP_URL . 'assets/img/agolab.webp' ); ?>" alt="aGo Lab" style="height:28px;width:auto;vertical-align:middle;margin-right:8px">
                <?php esc_html_e( 'aGo First Run', 'ago-setup' ); ?>
                <span style="font-size:12px;color:#999;margin-left:8px">v<?php echo esc_html( AGOSETUP_VERSION ); ?></span>
            </h1>

            <div class="ago-layout">
                <div class="ago-main">

                    <div class="card ago-card ago-intro">
                        <h2><?php esc_html_e( 'Fresh WordPress install? Get it ready in one click.', 'ago-setup' ); ?></h2>
                        <p>
                            <?php esc_html_e( 'aGo First Run cleans up the demo content WordPress creates ("Hello World" post, "Sample Page", default plugins) and applies the configuration that almost every site needs anyway: clean permalinks, timezone, comments off, essential pages.', 'ago-setup' ); ?>
                        </p>
                        <p>
                            <?php esc_html_e( 'Pick the tasks below, click "Run", and you are done. Then deactivate the plugin, it does not need to keep running.', 'ago-setup' ); ?>
                        </p>
                    </div>

                    <div class="card ago-card">
                        <h2><?php esc_html_e( 'First Run Wizard', 'ago-setup' ); ?></h2>
                        <p><?php esc_html_e( 'Select the tasks you want to run and click "Run". Each task is independent.', 'ago-setup' ); ?></p>

                        <div class="ago-section">
                            <h3><?php esc_html_e( 'Cleanup', 'ago-setup' ); ?></h3>
                            <label class="ago-task">
                                <input type="checkbox" name="delete_hello_world" checked>
                                <?php esc_html_e( 'Delete "Hello World" post', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="delete_hello_world"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="delete_sample_page" checked>
                                <?php esc_html_e( 'Delete "Sample Page"', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="delete_sample_page"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="delete_sample_comment" checked>
                                <?php esc_html_e( 'Delete sample comment', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="delete_sample_comment"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="delete_hello_dolly" checked>
                                <?php esc_html_e( 'Delete Hello Dolly plugin', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="delete_hello_dolly"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="delete_akismet">
                                <?php esc_html_e( 'Delete Akismet (only if inactive)', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="delete_akismet"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="delete_unused_themes">
                                <?php esc_html_e( 'Delete unused themes (keep only the active one)', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="delete_unused_themes"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="clean_transients">
                                <?php esc_html_e( 'Clean expired transients and orphan database rows', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="clean_transients"></span>
                            </label>
                        </div>

                        <div class="ago-section">
                            <h3><?php esc_html_e( 'Settings', 'ago-setup' ); ?></h3>
                            <label class="ago-task">
                                <input type="checkbox" name="set_permalinks" checked>
                                <?php esc_html_e( 'Set permalinks to /%postname%/', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="set_permalinks"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="disable_comments" checked>
                                <?php esc_html_e( 'Disable comments globally', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="disable_comments"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="disable_pingbacks" checked>
                                <?php esc_html_e( 'Disable pingbacks/trackbacks', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="disable_pingbacks"></span>
                            </label>
                            <label class="ago-task ago-task-with-select">
                                <input type="checkbox" name="set_timezone">
                                <?php esc_html_e( 'Set timezone', 'ago-setup' ); ?>
                                <select id="ago-timezone-select">
                                    <?php foreach ( $timezones as $tz ) : ?>
                                        <option value="<?php echo esc_attr( $tz ); ?>" <?php selected( $current_tz, $tz ); ?>>
                                            <?php echo esc_html( $tz ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="ago-task-status" data-task="set_timezone"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="discourage_search">
                                <?php esc_html_e( 'Discourage search engines (staging)', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="discourage_search"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="clean_dashboard">
                                <?php esc_html_e( 'Remove default dashboard widgets', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="clean_dashboard"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="create_privacy_page">
                                <?php esc_html_e( 'Create Privacy Policy page (draft)', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="create_privacy_page"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="create_essential_pages">
                                <?php esc_html_e( 'Create essential pages: Contact and About (drafts)', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="create_essential_pages"></span>
                            </label>
                            <label class="ago-task">
                                <input type="checkbox" name="set_static_front_page">
                                <?php esc_html_e( 'Front page: use a static "Home" page instead of latest posts', 'ago-setup' ); ?>
                                <span class="ago-task-status" data-task="set_static_front_page"></span>
                            </label>
                        </div>

                        <div class="ago-actions">
                            <button id="ago-run-btn" class="button button-primary button-hero">
                                <?php esc_html_e( 'Run', 'ago-setup' ); ?>
                            </button>
                            <button id="ago-select-all" class="button" type="button">
                                <?php esc_html_e( 'Select All', 'ago-setup' ); ?>
                            </button>
                            <button id="ago-select-none" class="button" type="button">
                                <?php esc_html_e( 'Select None', 'ago-setup' ); ?>
                            </button>
                        </div>

                        <div id="ago-setup-result" style="display:none"></div>
                    </div>

                    <div class="card ago-card" id="ago-deactivate-card" style="display:none">
                        <h2><?php esc_html_e( 'All done!', 'ago-setup' ); ?></h2>
                        <p><?php esc_html_e( 'Setup complete. You can deactivate this plugin, it has served its purpose.', 'ago-setup' ); ?></p>
                        <button id="ago-deactivate-btn" class="button button-secondary">
                            <?php esc_html_e( 'Complete & Deactivate Plugin', 'ago-setup' ); ?>
                        </button>
                    </div>

                    <div class="card ago-card">
                        <h2><?php esc_html_e( 'Recommended aGo Plugins', 'ago-setup' ); ?></h2>
                        <p><?php esc_html_e( 'Continue optimizing your WordPress site with these free plugins:', 'ago-setup' ); ?></p>
                        <ul class="ago-recommendations">
                            <li>
                                <strong>aGo Cleanup</strong>, <?php esc_html_e( 'Remove WordPress bloat (emojis, embeds, XML-RPC…)', 'ago-setup' ); ?>
                            </li>
                            <li>
                                <strong>aGo Harden</strong>, <?php esc_html_e( 'Security hardening in one click', 'ago-setup' ); ?>
                            </li>
                            <li>
                                <strong>aGo Mail Pilot</strong>, <?php esc_html_e( 'Fix email delivery with SMTP', 'ago-setup' ); ?>
                            </li>
                            <li>
                                <strong>aGo Disable</strong>, <?php esc_html_e( 'Disable Gutenberg, comments, auto-updates…', 'ago-setup' ); ?>
                            </li>
                            <li>
                                <strong>aGo Media</strong>, <?php esc_html_e( 'Auto WebP conversion & image optimization', 'ago-setup' ); ?>
                            </li>
                            <li>
                                <strong>aGo Maintenance</strong>, <?php esc_html_e( 'One-click maintenance mode', 'ago-setup' ); ?>
                            </li>
                            <li>
                                <strong>aGo Admin</strong>, <?php esc_html_e( 'White-label wp-admin for clients', 'ago-setup' ); ?>
                            </li>
                            <li>
                                <strong>aGo Monitor</strong>, <?php esc_html_e( 'Site health checks & reports', 'ago-setup' ); ?>
                            </li>
                            <li>
                                <strong>aGo Migrator</strong>, <?php esc_html_e( 'Full-site backup & migration', 'ago-setup' ); ?>
                            </li>
                        </ul>
                    </div>

                </div>

                <div class="ago-sidebar">

                    <div class="card ago-card">
                        <h3><?php esc_html_e( 'Quick links', 'ago-setup' ); ?></h3>
                        <ul class="ago-features" style="list-style:none;padding:0;margin:0">
                            <li><a href="https://wordpress.org/documentation/" target="_blank" rel="noopener"><?php esc_html_e( 'WordPress documentation', 'ago-setup' ); ?></a></li>
                            <li><a href="https://pagespeed.web.dev/" target="_blank" rel="noopener"><?php esc_html_e( 'Speed test (PageSpeed)', 'ago-setup' ); ?></a></li>
                            <li><a href="https://www.ssllabs.com/ssltest/" target="_blank" rel="noopener"><?php esc_html_e( 'SSL test (SSL Labs)', 'ago-setup' ); ?></a></li>
                        </ul>
                    </div>

                    <div class="card ago-card">
                        <h3><?php esc_html_e( 'About', 'ago-setup' ); ?></h3>
                        <p style="font-size:13px;color:#666">
                            <?php esc_html_e( 'First-run wizard for fresh WordPress installs. Use once, then deactivate.', 'ago-setup' ); ?>
                        </p>
                        <ul class="ago-features">
                            <li><?php esc_html_e( 'Delete demo content', 'ago-setup' ); ?></li>
                            <li><?php esc_html_e( 'Remove default plugins', 'ago-setup' ); ?></li>
                            <li><?php esc_html_e( 'Delete unused themes', 'ago-setup' ); ?></li>
                            <li><?php esc_html_e( 'Configure permalinks & timezone', 'ago-setup' ); ?></li>
                            <li><?php esc_html_e( 'Disable comments & pingbacks', 'ago-setup' ); ?></li>
                            <li><?php esc_html_e( 'Create essential pages', 'ago-setup' ); ?></li>
                            <li><?php esc_html_e( 'Static front page', 'ago-setup' ); ?></li>
                            <li><?php esc_html_e( 'Clean transients & orphans', 'ago-setup' ); ?></li>
                            <li><?php esc_html_e( 'Self-deactivate when done', 'ago-setup' ); ?></li>
                        </ul>
                    </div>

                    <div class="card ago-card ago-donation">
                        <h3><?php esc_html_e( 'Support Open Source', 'ago-setup' ); ?></h3>
                        <p style="font-size:13px;color:#666">
                            <?php esc_html_e( 'If this plugin saves you time, consider supporting our open-source work.', 'ago-setup' ); ?>
                        </p>
                        <div class="ago-donation-amounts">
                            <a href="https://paypal.me/sixtovaldes/3" class="ago-amount" target="_blank" rel="noopener">$3</a>
                            <a href="https://paypal.me/sixtovaldes/5" class="ago-amount" target="_blank" rel="noopener">$5</a>
                            <a href="https://paypal.me/sixtovaldes/10" class="ago-amount" target="_blank" rel="noopener">$10</a>
                        </div>
                        <a href="https://paypal.me/sixtovaldes" class="ago-coffee-btn" target="_blank" rel="noopener">
                            <span class="dashicons dashicons-coffee" style="margin-right:6px"></span>
                            <?php esc_html_e( 'Buy us a coffee', 'ago-setup' ); ?>
                        </a>
                        <p class="ago-donation-note">
                            <?php esc_html_e( 'Voluntary donation. Thank you!', 'ago-setup' ); ?>
                        </p>
                    </div>

                    <div class="ago-footer">
                        <a href="https://ago.cl" target="_blank" rel="noopener" class="ago-footer-logo">
                            <img src="<?php echo esc_url( AGOSETUP_URL . 'assets/img/agolab.webp' ); ?>" alt="aGo Lab" style="height:40px;width:auto">
                        </a>
                        <p>
                            <?php
                            echo wp_kses_post(
                                sprintf(
                                    /* translators: 1: heart icon HTML, 2: aGo Lab link HTML */
                                    __( 'Developed with %1$s by %2$s', 'ago-setup' ),
                                    '<span style="color:#e25555">&#10084;</span>',
                                    '<a href="https://ago.cl" target="_blank" rel="noopener"><strong>aGo Lab</strong></a>'
                                )
                            );
                            ?>
                        </p>
                        <p style="font-size:11px;color:#999">
                            <?php esc_html_e( 'Building tools for the web, one plugin at a time.', 'ago-setup' ); ?>
                        </p>
                    </div>

                </div>
            </div>

        </div>
        <?php
    }
}
