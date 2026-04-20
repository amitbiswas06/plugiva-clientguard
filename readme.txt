=== Plugiva ClientGuard - Client Proofing & Admin Safety ===
Contributors: amitbiswas06
Tags: admin control, hide menu, client dashboard, prevent changes, plugin management
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Prevent unwanted WordPress changes and protect critical settings with one-click Client Mode.

== Description ==

Plugiva ClientGuard helps you safely hand over WordPress to clients without worrying about accidental changes.

With one-click Client Mode, you can protect plugins, themes, and critical settings while keeping the admin area clean and usable.

Instead of blocking access aggressively, ClientGuard applies smart guardrails - allowing users to work freely without breaking important parts of your site.

This plugin is ideal for:
- Client-managed websites
- Multi-admin teams
- Sites where stability matters more than frequent changes

== Client Mode ==

Enable Client Mode to instantly apply safe defaults:

* Prevent plugin installation and deletion
* Disable theme switching
* Hide sensitive admin menus
* Protect important content from edits
* Protect critical settings like Site URL and Permalinks
* Hide ACF (Advanced Custom Fields) admin automatically

Perfect for handing over websites to clients with confidence.

== Key Features ==

* One-click Client Mode for instant protection
* Guard plugin installation, deletion, and activation
* Prevent theme switching and editing
* Hide selected admin menus (including ACF when active)
* Protect important pages from editing or deletion
* Protect critical WordPress settings (Site URL, Permalinks)
* Works with ACF / SCF automatically
* Safe defaults - nothing is locked on activation
* Clean uninstall with no leftover data

== What This Plugin Is NOT ==

* Not a security or firewall plugin
* Not a role or permission editor
* Not designed to block administrators entirely

Plugiva ClientGuard focuses on preventing mistakes, not enforcing restrictions.

== Installation ==

1. Upload the `plugiva-clientguard` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to **Settings → ClientGuard**
4. Enable only the guards you need

== Frequently Asked Questions ==

= Does this plugin completely block access? =
No. ClientGuard hides menus and guards actions, but direct URLs remain accessible unless explicitly guarded.

= Is this a security plugin? =
No. This plugin is designed to prevent accidental changes, not to secure WordPress from attacks.

= Can administrators still access hidden pages via URL? =
Yes. Menu hiding is for visibility only. Action blocking is handled separately where needed.

= Does it work with ACF (Advanced Custom Fields)? =
Yes. ClientGuard can hide the ACF admin panel automatically when Client Mode is enabled, helping prevent accidental changes to custom fields.

= What happens on uninstall? =
All plugin settings are removed cleanly when the plugin is uninstalled.

== Screenshots ==

1. Client Mode settings for WordPress admin protection.
2. General Protection settings for plugins, themes, and critical site settings.
3. Content Protection interface for protecting selected pages.
4. Menu Visibility options for hiding admin menus.

== Developer Hooks ==

Plugiva ClientGuard provides filter hooks that allow developers to customize certain behaviors, including admin notice messages, without modifying plugin code.

== Changelog ==

= 1.2.0 =
* Added Site URL protection to prevent login and access issues
* Added Permalink settings guard in Client Mode
* Improved admin safety with critical settings protection

= 1.1.0 =
* Added Client Mode for one-click admin protection
* Improved menu control and UI behavior
* Added ACF integration (auto-hide in Client Mode)
* Enhanced capability handling for plugins and themes
* Improved admin experience with clearer controls

= 1.0.1 =
* Refined plugin description and tags for improved clarity and discoverability.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.2.0 =
Adds protection for critical WordPress settings like Site URL and Permalinks to prevent accidental site breakage.

= 1.1.0 =
Introduces Client Mode for one-click protection and adds ACF integration for safer client-managed sites.

= 1.0.1 =
Refined description and tag improvements.

= 1.0.0 =
Initial release.
