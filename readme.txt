=== aGo First Run ===
Contributors: agolab
Donate link: https://paypal.me/sixtovaldes
Tags: setup wizard, post-install, clean install, permalinks, defaults
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Post-install wizard for WordPress: clean demo content, set permalinks, disable comments, set timezone and create essential pages in one click.

== Description ==

aGo First Run is a one-shot wizard that prepares a fresh WordPress install with sensible defaults. Run it once after activation, then deactivate the plugin: it does not need to keep running.

Every action is an independent checkbox. Nothing runs until you tick it and click Run, and the wizard reports the result of each task next to it.

**What it does**

* Moves the default demo content to the trash: the "Hello World" post, the "Sample Page" and the sample comment.
* Deletes the Hello Dolly plugin, and Akismet when it is inactive.
* Deletes unused themes, keeping the active one and its parent theme.
* Cleans expired transients and orphan database rows.
* Sets permalinks to /%postname%/.
* Disables comments globally and closes pingbacks and trackbacks.
* Sets the site timezone to the value you choose.
* Discourages search engines (useful on staging).
* Hides the default dashboard widgets for your account.
* Creates a Privacy Policy page as a draft.
* Creates Contact and About pages as drafts.
* Sets a published static "Home" page as the front page.
* Can deactivate itself once you are done.

**Built to be safe on a site that is already in use**

* The demo content is found by what WordPress created at install time (the hello-world and sample-page slugs, the address of the sample comment), never by its ID, so a post that only shares the ID of the old demo is left alone.
* Posts, pages and comments go to the trash, so you can restore them from the usual WordPress screens.
* Plugins and themes are deleted only by users who are allowed to delete them, and never on a multisite network, where every site shares them.
* When the server needs FTP credentials to delete files, the task reports it and leaves the files in place.

== Installation ==

1. Upload the `ago-setup` folder to `/wp-content/plugins/` or install via the Plugins screen.
2. Activate the plugin through the Plugins menu in WordPress.
3. Go to aGo Tools, then First Run.
4. Tick the tasks you want and click Run.

== Frequently Asked Questions ==

= Is it safe on an existing site? =

The wizard targets default WordPress demo data and common defaults. Read each checkbox before running and untick anything you do not want to apply. Demo content goes to the trash, and the Privacy Policy, Contact and About pages are created as drafts. The static front page task publishes a "Home" page, because a front page has to be published.

= Who can run it? =

Users who can manage options (administrators). Deleting plugins or themes also requires the permission to delete them, and those tasks are not offered on multisite.

= Can I run it more than once? =

Yes. Each run only acts on the items currently ticked.

= Does it store any data? =

No persistent options are created by the plugin. The pages and settings it produces are standard WordPress content and options that you can edit or revert at any time.

= Is the plugin translated? =

The plugin uses your WordPress site language. Translations are contributed through translate.wordpress.org, and the screens show in English until one exists for your language.

== Screenshots ==

1. First Run wizard with one checkbox per task.
2. Run results, with the outcome reported per task.
3. Self-deactivate option once setup is done.

== External services ==

This plugin does not connect to or send data to any external service. All tasks run locally against your own WordPress installation.

The settings screen links to external sites that only open when you click them: the aGo Lab website and documentation, the WordPress documentation, PageSpeed Insights, SSL Labs and PayPal donation pages. No data is transmitted automatically.

== Privacy ==

The plugin stores no persistent options of its own. It modifies standard WordPress settings (permalinks, comments, timezone, search engine visibility, front page), hides dashboard widgets for the user who runs that task, and creates pages, all of which you can edit or revert. It sends no data to third parties. Since it stores nothing of its own, uninstall has nothing to remove.

== Changelog ==

= 1.0.0 =
* Initial release.
* One-click first-run wizard with per-task checkboxes.
* Demo content to the trash; Hello Dolly, inactive Akismet and unused themes deleted.
* Set permalinks and timezone, disable comments and pingbacks.
* Create Privacy, Contact and About pages as drafts, and a static Home page.
* Clean expired transients and orphan database rows.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
