=== Plugiva ClientGuard ===
Contributors: plugiva
Tags: admin, safety, plugins, themes, content
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Prevent accidental changes in the WordPress admin by safely guarding plugins, themes, menus, and critical pages.

== Description ==

Plugiva ClientGuard helps site owners and administrators prevent accidental or unwanted changes in the WordPress admin area.

Instead of blocking access aggressively, ClientGuard focuses on *guardrails* — reducing risk while keeping the admin experience calm and predictable.

This plugin is ideal for:
- Client-managed websites
- Multi-admin teams
- Sites where stability matters more than frequent changes

== Key Features ==

* Guard plugin installation, deletion, and activation
* Prevent theme switching
* Hide selected admin menus to reduce clutter
* Protect important pages from editing or deletion
* Safe defaults — nothing is locked on activation
* Direct URLs remain accessible (no hard lockouts)
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

= What happens on uninstall? =
All plugin settings are removed cleanly when the plugin is uninstalled.

== Screenshots ==

1. ClientGuard settings page.
2. Content Protection interface.
3. Menu Visibility options.

== Developer Hooks ==

Plugiva ClientGuard provides filter hooks that allow developers to customize certain behaviors, including admin notice messages, without modifying plugin code.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
