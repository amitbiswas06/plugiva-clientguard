=== Plugiva ClientGuard ===
Contributors: amitbiswas06
Tags: admin, safety, guardrails, client mode, hide menu
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.7.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simplify the WordPress admin and help prevent unintended changes with safe defaults and Client Mode.

== Description ==

Plugiva ClientGuard helps prevent unintended changes in the WordPress admin by adding practical guardrails around sensitive operations, settings, content, and administration areas.

It is designed for situations where site owners, clients, teams, or automated tools need to work in WordPress without accidentally changing something important.

ClientGuard combines Client Mode with operation-level protection. Client Mode provides a simplified admin experience by hiding selected administration areas, while protection is applied to sensitive operations themselves rather than relying only on whether an admin interface is visible.

You can protect plugin and theme operations, critical settings, protected content, and other sensitive WordPress operations while keeping the rest of the admin area usable.

Client Mode can also be locked via configuration to prevent it from being disabled from the admin interface:

`define( 'PCGD_LOCK_CLIENT_MODE', true );`

This can be added in wp-config.php or defined programmatically in custom code.

When enabled, Client Mode is forced on and cannot be turned off from the dashboard, helping maintain consistent protection.

ClientGuard also provides Sentinel, an activity and lifecycle event log for protected operations, configuration changes, bypass activity, and other ClientGuard events. Sentinel helps make changes and protection activity visible rather than leaving them unexplained.

On multisite installations, ClientGuard supports site-specific protection and Sentinel event handling while also covering relevant network-level operations.

Instead of blocking access aggressively, ClientGuard applies practical guardrails that let users continue working while reducing the chance of unintended changes.

ClientGuard is ideal for:

- Site owners managing their own WordPress site
- Developers handing off sites to clients
- Teams that want a simplified and safer admin experience
- Sites where administrative changes need to remain controlled and observable

== Client Mode ==

Enable Client Mode to apply ClientGuard's protection controls with one click:

* Restricts plugin installation, deletion, activation, deactivation, and editing
* Restricts theme installation, deletion, switching, and editing
* Restricts Appearance management, including Menus, Widgets, Customizer, and Site Editor
* Protects critical site settings and homepage assignments
* Governs WordPress AI and Connector administration areas when available
* Hides selected administrative areas to simplify the dashboard
* Protects important content from unintended edits, trashing, and permanent deletion
* Hides the ACF admin area automatically when ACF is active

== Individual Protections ==

If Client Mode is not enabled, each protection can be managed independently.

* Lock Theme Switching - Prevents switching, installing, deleting, and editing themes.
* Lock Appearance Management - Restricts Appearance management capabilities, including Menus, Widgets, Customizer, and Site Editor access.
* Lock Plugin Installation - Prevents installing, deleting, and editing plugins.
* Allow Plugin Activation - Allows administrators to activate or deactivate installed plugins while installation protections remain in place.
* Protect Site URLs - Protects selected site configuration areas such as permalink management.
* Content Protection - Prevents editing, trashing, and permanent deletion of selected pages.
* Menu Hiding - Removes selected administrative menus from the dashboard interface.

== Sentinel ==

Sentinel records ClientGuard-related activity to make protection and configuration events visible.

It records events related to:

* Configuration and protection state changes
* Client Mode changes
* Plugin activation policy changes
* Protected content changes
* Blocked, bypassed, and violation events
* ClientGuard lifecycle activity

Sentinel event history is site-scoped and can be managed from the ClientGuard administration area.

* View recorded events with pagination
* Configure event retention
* Clear Sentinel logs when needed
* Export Sentinel event history
* Handle site-specific event history on multisite installations

Sentinel focuses on activity relevant to ClientGuard and is not intended to provide a site-wide activity or security audit log.

== Key Features ==

* One-click Client Mode for a simplified and safer admin experience
* Operational protection for plugin installation, deletion, activation, and deactivation
* Operational protection for theme installation, deletion, and switching
* Protection for critical settings, including Site URLs and Permalinks
* Protection for selected pages from editing, trashing, and permanent deletion
* Protection for Site Editor and sidebar widget operations
* Protection for theme and plugin editor operations
* WordPress AI and Connector governance through operational workflow protection
* Sentinel event logging for ClientGuard-related configuration, operational, bypass, and lifecycle activity
* Site-scoped Sentinel logs with retention, clearing, and export tools
* Multisite-aware protection and Sentinel event handling
* ACF-aware administration controls
* Configuration lock for Client Mode
* Safe defaults with no changes on activation

== What This Plugin Is NOT ==

* Not a security or firewall plugin
* Not a role or permission editor
* Not designed to block administrators entirely

Plugiva ClientGuard focuses on preventing unintended changes, not replacing WordPress security or permission systems.

== Installation ==

1. Upload the `plugiva-clientguard` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Go to **Settings → ClientGuard**
4. Enable Client Mode or configure individual protections as needed

== Frequently Asked Questions ==

= Does this plugin completely block access? =

No. ClientGuard simplifies access and guards critical operations. Protection is applied to specific operations and areas rather than blocking administrators entirely.

= Does Client Mode prevent content editing? =

No. Client Mode is designed to reduce administrative risks while allowing normal content management activities. Editors and administrators can continue managing content unless specific content has been protected using Content Protection.

= What is the difference between Client Mode and individual protections? =

Client Mode applies a recommended set of protections automatically.

Individual protections allow site owners to choose exactly which areas should be restricted, such as plugins, themes, Appearance management, protected content, or selected administrative menus.

= Is this a security plugin? =

No. ClientGuard is designed to prevent unintended changes and control sensitive WordPress operations, not to protect WordPress from attacks.

= What happens to hidden admin areas? =

ClientGuard simplifies the admin experience by hiding or managing certain areas to help prevent unintended changes.

= Does it work with ACF (Advanced Custom Fields)? =

Yes. ClientGuard can hide the ACF admin panel automatically when Client Mode is enabled, helping prevent accidental changes to custom fields.

= Does ClientGuard work with WordPress AI and Connectors? =

Yes. ClientGuard governs available WordPress AI and Connector administration areas through Client Mode, helping prevent unintended changes to these settings and operations.

= Does ClientGuard work with multisite? =

ClientGuard supports multisite installations, including site-specific protections and Sentinel event handling. Network-level plugin and theme operations are also covered where applicable.

= What happens on uninstall? =

ClientGuard removes its plugin settings and other plugin-specific data cleanly when it is uninstalled. Sentinel event logs are preserved by default and can be removed separately when developer cleanup is explicitly enabled.

== Screenshots ==

1. Client Mode settings for a simplified and safer admin experience.
2. General Protection settings for plugins, themes, and critical site settings.
3. Content Protection interface for protecting selected pages.
4. Menu Visibility options for hiding admin menus.

== Developer Hooks ==

Plugiva ClientGuard provides developer-friendly filters and actions for customizing certain protection behaviors and responding to ClientGuard events.

Available hooks include:

* `pcgd_protected_site_identity_options` - Customize the site identity options protected by ClientGuard.
* `pcgd_protected_permalink_options` - Customize the permalink-related options protected by ClientGuard.
* `pcgd_protection_state_changed` - Respond to protection state changes.
* `pcgd_client_mode_changed` - Respond to Client Mode changes.
* `pcgd_plugin_activation_policy_changed` - Respond to changes to the plugin activation policy.
* `pcgd_protected_content_added` - Respond when protected content is added.
* `pcgd_protected_content_removed` - Respond when protected content is removed.
* `pcgd_protection_blocked` - Respond when ClientGuard blocks a protected operation.
* `pcgd_protection_bypassed` - Respond when a protected operation is bypassed.
* `pcgd_protection_violation` - Respond to a protection violation.

For Sentinel event handling, these hooks can be used to observe ClientGuard-related configuration and operational activity.

Additional hooks may be introduced in future versions.

== Changelog ==

= 1.7.0 =
* Added operational protection for plugin and theme installation, deletion, activation, deactivation, switching, and editing.
* Extended protection to critical settings, including Permalinks and Site Identity options.
* Extended Content Protection to prevent protected pages from being trashed or permanently deleted.
* Added protection for Site Editor and sidebar widget operations.
* Added protection for theme and plugin editor operations.
* Added Sentinel for ClientGuard-related configuration, operational, bypass, and lifecycle event logging.
* Added site-scoped Sentinel event history with retention, clearing, and export tools.
* Added multisite support for Sentinel event handling and network-level protection.
* Added developer filters for customizing protected Site Identity and Permalink options.
* Improved multisite handling, uninstall cleanup, and Sentinel lifecycle management.

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

= 1.7.0 =
Adds operational protection for plugins, themes, settings, content, and other sensitive WordPress operations, along with Sentinel event logging for ClientGuard-related activity. Also adds multisite-aware protection and event handling.

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
