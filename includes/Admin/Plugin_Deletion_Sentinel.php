<?php
/**
 * Plugin deletion operational Sentinel.
 *
 * @since 1.7.0
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Admin_Plugin_Deletion_Sentinel {

	/**
	 * Plugin Guard instance.
	 *
	 * @var PCGD_Admin_Plugin_Guard
	 */
	private $guard;

	/**
	 * Original filesystem object for the current deletion.
	 *
	 * @var object|null
	 */
	private $original_filesystem = null;

	/**
	 * Plugin currently being deleted.
	 *
	 * @var string
	 */
	private $plugin_file = '';

	/**
	 * Temporary uninstall.php state.
	 *
	 * @var array
	 */
	private $uninstall_restore = array();

	/**
	 * Whether the Sentinel is currently protecting a deletion.
	 *
	 * @var bool
	 */
	private $active = false;

	/**
	 * Constructor.
	 *
	 * @param PCGD_Admin_Plugin_Guard $guard Plugin Guard instance.
	 */
	public function __construct( $guard ) {
		$this->guard = $guard;
	}

	/**
	 * Register operational hooks.
	 *
	 * @param PCGD_Core_Loader $loader Loader instance.
	 */
	public function register( $loader ) {

		$loader->add_action(
			'pre_uninstall_plugin',
			$this,
			'protect_uninstall',
			10,
			2
		);

		$loader->add_filter(
			'option_uninstall_plugins',
			$this,
			'filter_uninstall_plugins',
			10,
			2
		);

		$loader->add_action(
			'delete_plugin',
			$this,
			'intercept_plugin_deletion',
			10,
			1
		);

		$loader->add_action(
			'deleted_plugin',
			$this,
			'finish_plugin_deletion',
			10,
			2
		);
	}

	/**
	 * Determine whether plugin deletion is protected.
	 *
	 * @return bool
	 */
	private function is_protected() {
		return $this->guard->is_plugin_operations_protected();
	}

	/**
	 * Protect uninstall operations.
	 *
	 * @param string $plugin_file Plugin file.
	 * @param bool   $network_wide Whether network-wide.
	 */
	public function protect_uninstall( $plugin_file, $network_wide ) {

		if ( ! $this->is_protected() ) {
			return;
		}

		$this->active      = true;
		$this->plugin_file = plugin_basename( $plugin_file );

		$this->suppress_uninstall_file();
	}

	/**
	 * Suppress uninstall.php temporarily.
	 */
	private function suppress_uninstall_file() {

		$plugin_dir = WP_PLUGIN_DIR . '/' . dirname( $this->plugin_file );
		$uninstall  = $plugin_dir . '/uninstall.php';

		if ( ! file_exists( $uninstall ) ) {
			return;
		}

		$temp = $plugin_dir . '/.pcgd-uninstall.php';

		if ( @rename( $uninstall, $temp ) ) {
			$this->uninstall_restore = array(
				'original' => $uninstall,
				'temp'     => $temp,
			);
		}
	}

	/**
	 * Suppress registered uninstall callbacks.
	 *
	 * @param mixed  $value  Option value.
	 * @param string $option Option name.
	 * @return mixed
	 */
	public function filter_uninstall_plugins( $value, $option ) {

		if ( 'uninstall_plugins' !== $option ) {
			return $value;
		}

		if ( ! $this->active || empty( $this->plugin_file ) ) {
			return $value;
		}

		if ( ! is_array( $value ) ) {
			return $value;
		}

		if ( isset( $value[ $this->plugin_file ] ) ) {
			unset( $value[ $this->plugin_file ] );
		}

		return $value;
	}

	/**
	 * Intercept the actual plugin filesystem deletion.
	 *
	 * @param string $plugin_file Plugin file.
	 */
	public function intercept_plugin_deletion( $plugin_file ) {

		if ( ! $this->is_protected() ) {
			return;
		}

		$this->active      = true;
		$this->plugin_file = plugin_basename( $plugin_file );

		global $wp_filesystem;

		if ( ! is_object( $wp_filesystem ) ) {
			return;
		}

		$this->original_filesystem = $wp_filesystem;

		$wp_filesystem = new PCGD_Plugin_Filesystem_Proxy(
			$wp_filesystem,
			$this->plugin_file
		);
	}

	/**
	 * Finish the deletion attempt.
	 *
	 * @param string $plugin_file Plugin file.
	 * @param bool   $deleted Whether deletion succeeded.
	 */
	public function finish_plugin_deletion( $plugin_file, $deleted ) {

		if ( $this->original_filesystem ) {
			global $wp_filesystem;

			$wp_filesystem = $this->original_filesystem;
		}

		$this->restore_uninstall_file();

		$this->original_filesystem = null;
		$this->plugin_file         = '';
		$this->uninstall_restore   = array();
		$this->active              = false;
	}

	/**
	 * Restore uninstall.php.
	 */
	private function restore_uninstall_file() {

		if (
			empty( $this->uninstall_restore['original'] ) ||
			empty( $this->uninstall_restore['temp'] )
		) {
			return;
		}

		$original = $this->uninstall_restore['original'];
		$temp     = $this->uninstall_restore['temp'];

		if (
			! file_exists( $original ) &&
			file_exists( $temp )
		) {
			@rename( $temp, $original );
		}
	}
}