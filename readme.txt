=== aGo First Run ===
Contributors: agolab
Donate link: https://paypal.me/sixtovaldes
Tags: setup wizard, post-install, clean install, permalinks, defaults
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Post-install wizard for WordPress: clean demo content, set permalinks, disable comments, set timezone and create essential pages in one click.

== Description ==

aGo First Run is a one-shot wizard that prepares a fresh WordPress install with sensible defaults. Run it once after activation, then deactivate the plugin: it does not need to keep running.

Every action is an independent checkbox. You pick what you want, click Run, and the wizard reports the result of each task.

**What it does**

* Deletes default demo content: the "Hello World" post, the "Sample Page" and the sample comment.
* Deletes the Hello Dolly plugin, and Akismet when it is inactive.
* Deletes unused themes, keeping only the active one.
* Cleans expired transients and orphan database rows.
* Sets permalinks to /%postname%/.
* Disables comments globally and closes pingbacks and trackbacks.
* Sets the site timezone to the value you choose.
* Discourages search engines (useful on staging).
* Removes the default dashboard widgets for the current user.
* Creates a Privacy Policy page as a draft.
* Creates Contact and About pages as drafts.
* Sets a static "Home" page as the front page.
* Can self-deactivate once you are done.

== Installation ==

1. Upload the `ago-setup` folder to `/wp-content/plugins/` or install via the Plugins screen.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to aGo Tools, then First Run.
4. Tick the tasks you want and click Run.

== Frequently Asked Questions ==

= Is it safe on an existing site? =

The wizard targets default WordPress demo data and common defaults. Read each checkbox before running and uncheck anything you do not want to apply. Pages are created as drafts so nothing is published without your review.

= Can I run it more than once? =

Yes. Each run only acts on the items currently ticked.

= Does it store any data? =

No persistent options are created by the plugin. The pages and settings it produces are standard WordPress content and options that you can edit or revert at any time.

== Screenshots ==

1. First Run wizard with one checkbox per task.
2. Run results, with the outcome reported per task.
3. Self-deactivate option once setup is done.

== External services ==

This plugin does not connect to or send data to any external service. All tasks run locally against your own WordPress installation. The donation links and the aGo Lab link in the admin page point to PayPal and ago.cl, opened only when the user clicks them.

== Privacy ==

The plugin stores no persistent options of its own. It modifies standard WordPress settings (permalinks, comments, timezone, front page) and creates draft pages, all of which you can edit or revert. It sends no data to third parties. Since it stores nothing of its own, uninstall has nothing to remove.

== Changelog ==

= 1.0.0 =
* Initial release.
* One-click first-run wizard with per-task checkboxes.
* Clean demo content, Hello Dolly, inactive Akismet and unused themes.
* Set permalinks, timezone, disable comments and pingbacks.
* Create Privacy, Contact, About and static Home pages as drafts.
* Clean expired transients and orphan database rows.
* English, Spanish and Brazilian Portuguese included.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
