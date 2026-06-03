=== Plugiva ClientGuard - Safe WordPress for Clients ===
Contributors: amitbiswas06
Tags: admin, safety, guardrails, client mode, hide menu
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.6.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simplify the WordPress admin and help prevent unintended changes with safe defaults and Client Mode.

== Description ==

Plugiva ClientGuard simplifies and protects the WordPress admin by reducing risky operations and helping prevent unintended changes.

It adds practical guardrails to sensitive areas by reducing access to risky operations and settings that are rarely needed in day-to-day site management.

With one-click Client Mode, you can protect plugins, themes, and critical settings while keeping the admin area clean and usable.

You can also lock Client Mode via configuration to prevent it from being disabled from the admin interface:

`define( 'PCGD_LOCK_CLIENT_MODE', true );`

This can be added in wp-config.php or defined programmatically in custom code.

When enabled, Client Mode is forced on and cannot be turned off from the dashboard, ensuring consistent protection.

Client Mode also governs WordPress AI and Connector administration areas when available.

Lock Appearance Management restricts Appearance-related administration, including Menus, Widgets, Customizer, and Site Editor access.

Instead of blocking access aggressively, ClientGuard applies smart guardrails, allowing users to work freely without breaking important parts of your site.

ClientGuard is ideal for:

- Site owners managing their own WordPress site
- Developers handing off sites to clients
- Teams that want a simplified and safer admin experience

== Client Mode ==

Enable Client Mode to instantly apply safe defaults:

* Restricts plugin installation, deletion, and editing
* Prevents theme switching and theme management changes
* Restricts Appearance management (Menus, Widgets, Customizer, and Site Editor)
* Protects critical site settings and homepage assignments
* Governs WordPress AI and Connector administration areas when available
* Hides selected administrative areas to simplify the dashboard
* Protects important content from unintended edits
* Hides the ACF admin area automatically when ACF is active

== Individual Protections ==

If Client Mode is not enabled, each protection can be managed independently.

* Lock Theme Switching - Prevents switching, installing, deleting, and editing themes.
* Lock Appearance Management - Restricts Appearance management capabilities, including Menus, Widgets, Customizer, and Site Editor access.
* Lock Plugin Installation - Prevents installing, deleting, and editing plugins.
* Allow Plugin Activation - Allows administrators to activate or deactivate installed plugins while installation protections remain in place.
* Protect Site URLs - Protects selected site configuration areas such as permalink management.
* Content Protection - Prevents editing of selected pages.
* Menu Hiding - Removes selected administrative menus from the dashboard interface.

== Key Features ==

* One-click Client Mode for instant admin protection
* Optional configuration lock for Client Mode
* WordPress AI and Connector governance support
* Compatible with modern WordPress operational workflows
* ACF-aware administration controls
* Safe defaults with no changes on activation
* Clean uninstall with no leftover settings

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

= Does Client Mode prevent content editing? =

No. Client Mode is designed to reduce administrative risks while allowing normal content management activities. Editors and administrators can continue managing content unless specific pages or posts have been protected using Content Protection.

= What is the difference between Client Mode and individual protections? =

Client Mode applies a recommended set of protections automatically.

Individual protections allow site owners to choose exactly which areas should be restricted, such as plugins, themes, Appearance management, protected content, or selected administrative menus.

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

= 1.6.0 =
* Added Appearance Governance to help prevent unintended changes to menus, widgets, customizer, and site editor settings.
* Added Lock Appearance Management as a standalone protection.
* Improved Client Mode governance to include Appearance management controls.
* Improved settings state handling for governance-managed options.
* Improved menu visibility UI consistency when Appearance Governance is active.

= 1.5.2 =
* Added dashboard governance for improved admin consistency.
* Hide WordPress AI dashboard widgets in Client Mode.
* Hide Activity and At a Glance dashboard widgets in Client Mode.
* Hide Activity and At a Glance dashboard widgets when admin menus are hidden.
* Improved dashboard simplification and client-facing usability.

= 1.5.1 =
* Improved admin bar consistency by respecting ClientGuard menu visibility settings
* Hidden menus are now also removed from relevant admin bar navigation shortcuts
* Protected content no longer shows frontend Edit shortcuts when editing is restricted
* Improved Client Mode navigation consistency across admin and frontend views
* Refined frontend admin bar governance architecture

= 1.5.0 =
* Improved compatibility with modern WordPress operational workflows introduced in WordPress 7.0
* Extended plugin installation protections to REST-based provisioning workflows
* Added Client Mode governance for WordPress AI and Connectors admin surfaces
* Suspends WordPress AI runtime features while Client Mode is active
* Improved operational consistency across admin and REST contexts

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

= 1.6.0 =
Adds Appearance Governance and a new Lock Appearance Management option to help protect menus, widgets, customizer, and site editor access from unintended changes.

= 1.5.2 =
Adds dashboard governance to keep the WordPress dashboard aligned with Client Mode and hidden menu settings.

= 1.5.1 =
Improves admin bar consistency by aligning frontend shortcuts with ClientGuard menu visibility and protected content settings.

= 1.5.0 =
Improves compatibility with modern WordPress operational workflows and extends Client Mode governance to new WordPress 7.0 admin surfaces.

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
