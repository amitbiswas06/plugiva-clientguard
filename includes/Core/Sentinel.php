<?php
/**
 * Sentinel database handler.
 *
 * @since 1.7.0
 * @package Plugiva_ClientGuard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles ClientGuard Sentinel database setup.
 *
 * @since 1.7.0
 */
class PCGD_Core_Sentinel {

	/**
	 * Sentinel table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name = $wpdb->prefix . 'pcgd_sentinel';
	}

    /**
     * Register Sentinel hooks.
     *
     * @since 1.7.0
     *
     * @param PCGD_Core_Loader $loader Loader instance.
     * @return void
     */
    public function register_hooks( $loader ) {

        $loader->add_action( 'pcgd_protection_blocked', $this, 'record_operational_event', 10, 3 );

        $loader->add_action( 'pcgd_protection_bypassed', $this, 'record_operational_event', 10, 3 );

        $loader->add_action( 'pcgd_protection_violation', $this, 'record_operational_event', 10, 3 );

        $loader->add_action( 'pcgd_protection_state_changed', $this, 'record_config_event', 10, 4 );

        $loader->add_action( 'pcgd_client_mode_changed', $this, 'record_config_event', 10, 4 );

        $loader->add_action( 'pcgd_plugin_activation_policy_changed', $this, 'record_config_event', 10, 4 );

        $loader->add_action( 'pcgd_protected_content_added', $this, 'record_config_event', 10, 3 );

        $loader->add_action( 'pcgd_protected_content_removed', $this, 'record_config_event', 10, 3 );

    }

	/**
	 * Create the Sentinel table if it does not exist.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function maybe_create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			blog_id BIGINT(20) UNSIGNED NOT NULL,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			category VARCHAR(40) NOT NULL,
			event VARCHAR(40) NOT NULL,
			context VARCHAR(40) NOT NULL,
			action VARCHAR(40) NOT NULL,
			target VARCHAR(255) NOT NULL,
			details TEXT NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY blog_id_created_at (blog_id, created_at)
		) $charset_collate;";

		dbDelta( $sql );
	}

    /**
     * Determine the Sentinel event category from a hook.
     *
     * @since 1.7.0
     *
     * @param string $hook Sentinel hook name.
     * @return string Config or operational category, or an empty string for an unknown hook.
     */
    private function get_sentinel_event_category( $hook ) {
        $hook = sanitize_key( $hook );

        $config_hooks = array(
            'pcgd_protection_state_changed',
            'pcgd_client_mode_changed',
            'pcgd_plugin_activation_policy_changed',
            'pcgd_protected_content_added',
            'pcgd_protected_content_removed',
        );

        if ( in_array( $hook, $config_hooks, true ) ) {
            return 'config';
        }

        $operational_hooks = array(
            'pcgd_protection_blocked',
            'pcgd_protection_bypassed',
            'pcgd_protection_violation',
        );

        if ( in_array( $hook, $operational_hooks, true ) ) {
            return 'operational';
        }

        return '';
    }

    /**
     * Record a Sentinel configuration event.
     *
     * @since 1.7.0
     *
     * @param mixed ...$args Event arguments provided by the triggering hook.
     * @return void
     */
    public function record_config_event( ...$args ) {

        $hook     = current_filter();
        $category = $this->get_sentinel_event_category( $hook );

        if ( 'config' !== $category ) {
            return;
        }

        $context = '';
        $event   = '';
        $target  = '';
        $details = array(
            'old_value' => null,
            'new_value' => null,
            'text'      => '',
        );

        switch ( $hook ) {

            case 'pcgd_protection_state_changed':
                $context = isset( $args[0] ) ? sanitize_key( $args[0] ) : '';
                $event   = isset( $args[1] ) ? sanitize_key( $args[1] ) : '';
                $target  = $context;

                $old_value = ! empty( $args[3] );
                $new_value = ! empty( $args[2] );

                $details['old_value'] = $old_value;
                $details['new_value'] = $new_value;
                $details['text']      = sprintf(
                    /* translators: 1: previous protection state, 2: new protection state. */
                    __( 'Protection state changed from %1$s to %2$s.', 'plugiva-clientguard' ),
                    $old_value ? __( 'enabled', 'plugiva-clientguard' ) : __( 'disabled', 'plugiva-clientguard' ),
                    $new_value ? __( 'enabled', 'plugiva-clientguard' ) : __( 'disabled', 'plugiva-clientGuard' )
                );
                break;

            case 'pcgd_client_mode_changed':
                $context = isset( $args[0] ) ? sanitize_key( $args[0] ) : '';
                $event   = isset( $args[1] ) ? sanitize_key( $args[1] ) : '';
                $target  = $context;

                $old_value = ! empty( $args[3] );
                $new_value = ! empty( $args[2] );

                $details['old_value'] = $old_value;
                $details['new_value'] = $new_value;
                $details['text']      = sprintf(
                    /* translators: 1: previous Client Mode state, 2: new Client Mode state. */
                    __( 'Client Mode changed from %1$s to %2$s.', 'plugiva-clientguard' ),
                    $old_value ? __( 'enabled', 'plugiva-clientguard' ) : __( 'disabled', 'plugiva-clientguard' ),
                    $new_value ? __( 'enabled', 'plugiva-clientguard' ) : __( 'disabled', 'plugiva-clientguard' )
                );
                break;

            case 'pcgd_plugin_activation_policy_changed':
                $context = isset( $args[0] ) ? sanitize_key( $args[0] ) : '';
                $event   = isset( $args[1] ) ? sanitize_key( $args[1] ) : '';
                $target  = $context;

                $old_value = ! empty( $args[3] );
                $new_value = ! empty( $args[2] );

                $details['old_value'] = $old_value;
                $details['new_value'] = $new_value;
                $details['text']      = sprintf(
                    /* translators: 1: previous plugin activation policy, 2: new plugin activation policy. */
                    __( 'Plugin activation policy changed from %1$s to %2$s.', 'plugiva-clientguard' ),
                    $old_value ? __( 'allowed', 'plugiva-clientguard' ) : __( 'blocked', 'plugiva-clientguard' ),
                    $new_value ? __( 'allowed', 'plugiva-clientguard' ) : __( 'blocked', 'plugiva-clientguard' )
                );
                break;

            case 'pcgd_protected_content_added':
                $context = isset( $args[0] ) ? sanitize_key( $args[0] ) : '';
                $event   = isset( $args[1] ) ? sanitize_key( $args[1] ) : '';
                $target  = isset( $args[2] ) ? absint( $args[2] ) : 0;

                $details['text'] = __( 'Protected content added.', 'plugiva-clientguard' );
                break;

            case 'pcgd_protected_content_removed':
                $context = isset( $args[0] ) ? sanitize_key( $args[0] ) : '';
                $event   = isset( $args[1] ) ? sanitize_key( $args[1] ) : '';
                $target  = isset( $args[2] ) ? absint( $args[2] ) : 0;

                $details['text'] = __( 'Protected content removed.', 'plugiva-clientguard' );
                break;

            default:
                return;
        }

        if ( '' === $context || '' === $event ) {
            return;
        }

        $details = wp_json_encode( $details );

        if ( false === $details ) {
            return;
        }

        $this->insert_sentinel_event( $category, $event, $context, $target, $details, $hook );
    }

    /**
     * Record a Sentinel operational event.
     *
     * @since 1.7.0
     *
     * @param string $context Event context.
     * @param string $event   Event name.
     * @param string $target  Target of the operation.
     * @return void
     */
    public function record_operational_event( $context, $event, $target ) {

        $hook     = current_filter();
        $category = $this->get_sentinel_event_category( $hook );
        $text     = '';

        if ( 'operational' !== $category ) {
            return;
        }

        $context = sanitize_key( $context );
        $event   = sanitize_key( $event );
        $target  = sanitize_text_field( $target );

        if ( '' === $context || '' === $event || '' === $target ) {
            return;
        }

        switch ( $hook ) {

            case 'pcgd_protection_blocked':
                $text = __( 'Protection was blocked.', 'plugiva-clientguard' );
                break;

            case 'pcgd_protection_bypassed':
                $text = __( 'Protection was bypassed.', 'plugiva-clientguard' );
                break;

            case 'pcgd_protection_violation':
                $text = __( 'A protection violation occurred.', 'plugiva-clientguard' );
                break;

            default:
                return;
        }

        $details = wp_json_encode(
            array(
                'old_value' => null,
                'new_value' => null,
                'text'      => $text,
            )
        );

        if ( false === $details ) {
            return;
        }

        $this->insert_sentinel_event( $category, $event, $context, $target, $details, $hook );
    }

    /**
     * Insert a Sentinel event into the database.
     *
     * @since 1.7.0
     *
     * @param string $category Event category.
     * @param string $event    Event name.
     * @param string $context  Event context.
     * @param string $target   Event target.
     * @param string $details  Additional event details.
     * @param string $hook     Triggering Sentinel hook.
     * @return bool True on success, false on failure.
     */
    private function insert_sentinel_event( $category, $event, $context, $target, $details, $hook ) {
        global $wpdb;

        $category = sanitize_key( $category );
        $event    = sanitize_key( $event );
        $context  = sanitize_key( $context );
        $target   = sanitize_text_field( $target );
        $details  = sanitize_textarea_field( $details );
        $hook     = sanitize_key( $hook );

        if ( '' === $category || '' === $event || '' === $context || '' === $target || '' === $hook ) {
            return false;
        }

        if ( ! in_array( $category, array( 'config', 'operational' ), true ) ) {
            return false;
        }

        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $wpdb->esc_like( $this->table_name )
            )
        );

        if ( $this->table_name !== $table_exists ) {
            return false;
        }

        $blog_id = absint( get_current_blog_id() );
        $user_id = absint( get_current_user_id() );

        if ( $blog_id < 1 ) {
            return false;
        }

        $inserted = $wpdb->insert(
            $this->table_name,
            array(
                'blog_id'    => $blog_id,
                'user_id'    => $user_id,
                'category'   => $category,
                'event'      => $event,
                'context'    => $context,
                'action'     => $hook,
                'target'     => $target,
                'details'    => $details,
                'created_at' => current_time( 'mysql', true ),
            ),
            array(
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );

        if ( false === $inserted ) {
            return false;
        }

        return true;
    }

}