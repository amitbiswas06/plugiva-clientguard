=== Plugiva ClientGuard - Safe WordPress for Clients ===
Contributors: amitbiswas06
Tags: admin, safety, guardrails, client mode, hide menu
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simplify the WordPress admin and help prevent unintended changes with safe defaults and Client Mode.

== Description ==

Plugiva ClientGuard simplifies the WordPress admin by removing unnecessary risk points and helping prevent unintended changes.

It adds practical guardrails to sensitive areas - by reducing access to settings that are rarely needed in day-to-day use.

With one-click Client Mode, you can protect plugins, themes, and critical settings while keeping the admin area clean and usable.

You can also lock Client Mode via configuration to prevent it from being disabled from the admin interface:

`define( 'PCGD_LOCK_CLIENT_MODE', true );`

This can be added in wp-config.php or defined programmatically in custom code.

When enabled, Client Mode is forced on and cannot be turned off from the dashboard, ensuring consistent protection.

Instead of blocking access aggressively, ClientGuard applies smart guardrails, allowing users to work freely without breaking important parts of your site.

ClientGuard is ideal for:

- Site owners managing their own WordPress site
- Developers handing off sites to clients
- Teams that want a simplified and safer admin experience

== Client Mode ==

Enable Client Mode to instantly apply safe defaults:

* Limits plugin installation and deletion to maintain stability
* Keeps the active theme in place to preserve site layout
* Hides sensitive admin menus to simplify the interface
* Keeps important content safe from edits
* Keeps critical settings like Site URL and Permalinks stable
* Hides ACF (Advanced Custom Fields) admin automatically

== Key Features ==

* One-click Client Mode for a simplified admin experience
* Keeps plugin changes controlled for stability
* Keeps the active theme consistent
* Hides selected admin menus (including ACF when active)
* Keeps important pages safe from editing or deletion
* Keeps critical WordPress settings stable (Site URL, Permalinks)
* Works with ACF (Advanced Custom Fields) automatically
* Safe defaults - nothing changes on activation
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
No. ClientGuard simplifies access and guards critical actions. Some advanced areas are managed automatically to prevent unintended changes.

= Is this a security plugin? =
No. This plugin is designed to prevent accidental changes, not to secure WordPress from attacks.

= What happens to hidden admin areas? =
ClientGuard simplifies the admin experience by hiding or managing certain areas to help prevent unintended changes.

= Does it work with ACF (Advanced Custom Fields)? =
Yes. ClientGuard can hide the ACF admin panel automatically when Client Mode is enabled, helping prevent accidental changes to custom fields.

= What happens on uninstall? =
All plugin settings are removed cleanly when the plugin is uninstalled.

== Screenshots ==

1. Client Mode settings for a simplified and safer admin experience.
2. General Protection settings for plugins, themes, and critical site settings.
3. Content Protection interface for protecting selected pages.
4. Menu Visibility options for hiding admin menus.

== Developer Hooks ==

Plugiva ClientGuard includes developer-friendly hooks for customizing certain behaviors, such as modifying admin notice messages. Additional hooks may be introduced in future versions.

== Changelog ==

= 1.4.0 =
* Simplified admin experience by removing unnecessary notices
* Introduced centralized Client Mode messaging
* Added guided onboarding for Client Mode
* Hid critical settings like Site URLs from General Settings
* Improved UX by reducing friction and confusion
* Added nonce validation for secure actions

= 1.3.0 =
- Add configuration-based lock for Client Mode via `PCGD_LOCK_CLIENT_MODE`
- Introduce centralized settings state resolver for consistent UI behavior
- Improve settings and menu UI consistency under Client Mode

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

= 1.4.0 =
Improves admin experience with simplified UI, centralized messaging, and guided Client Mode onboarding.

= 1.3.0 =
Adds a configuration lock for Client Mode to prevent accidental disabling.

= 1.2.0 =
Adds protection for critical WordPress settings like Site URL and Permalinks to prevent accidental site breakage.

= 1.1.0 =
Introduces Client Mode for one-click protection and adds ACF integration for safer client-managed sites.

= 1.0.1 =
Refined description and tag improvements.

= 1.0.0 =
Initial release.
