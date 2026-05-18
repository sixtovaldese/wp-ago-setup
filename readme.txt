=== aGo First Run ===
Contributors: sixtovaldese
Donate link: https://paypal.me/sixtovaldes
Tags: setup wizard, post-install, clean install, permalinks, defaults
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Post-installation wizard for WordPress: clean demo content, configure permalinks, disable comments, set timezone and more in one click.

== Description ==

aGo Setup is a one-shot wizard that prepares a fresh WordPress install with sensible defaults. Run it once after activation and your site is ready for development.

**What it does**

* Removes default demo content: Hello World post, Sample Page, default Privacy Policy draft.
* Removes default WordPress comments.
* Removes Hello Dolly and Akismet (optional).
* Sets permalinks to "Post name".
* Disables comments site-wide.
* Sets timezone to UTC or your selection.
* Sets date and time format.
* Sets default blog title and tagline.
* Empties the default "Uncategorized" category description.
* Each action is a checkbox: pick what you want.

No external services. Settings are stored only while the wizard runs.

== Installation ==

1. Upload the `ago-setup` folder to `/wp-content/plugins/` or install via the Plugins screen.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to aGo Tools, then Setup.
4. Tick the actions you want and click Run wizard.

== Frequently Asked Questions ==

= Is it safe on an existing site? =

The wizard targets default WordPress demo data. Read each checkbox before running and uncheck anything you do not want to remove.

= Can I run it more than once? =

Yes. Each run only acts on the items currently ticked.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
