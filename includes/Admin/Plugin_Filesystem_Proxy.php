<?php
/**
 * Filesystem proxy for plugin deletion protection.
 *
 * @since 1.7.0
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Plugin_Filesystem_Proxy {

	/**
	 * Original filesystem object.
	 *
	 * @var object
	 */
	private $filesystem;

	/**
	 * Protected plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Constructor.
	 *
	 * @param object $filesystem Original filesystem object.
	 * @param string $plugin_file Plugin file.
	 */
	public function __construct( $filesystem, $plugin_file ) {
		$this->filesystem = $filesystem;
		$this->plugin_file = $plugin_file;
	}

	/**
	 * Intercept filesystem deletion.
	 *
	 * @param string       $file      File or directory.
	 * @param bool         $recursive Whether recursive.
	 * @param string|false $type      Resource type.
	 * @return bool
	 */
	public function delete( $file, $recursive = false, $type = false ) {

		$plugin_dir = trailingslashit(
			WP_PLUGIN_DIR . '/' . dirname( $this->plugin_file )
		);

		$normalized_file = wp_normalize_path( $file );
		$normalized_dir  = wp_normalize_path( $plugin_dir );

		if (
			$normalized_file === untrailingslashit( $normalized_dir ) ||
			strpos( $normalized_file, $normalized_dir ) === 0
		) {

			// Notify ClientGuard Sentinel that a protected plugin deletion was blocked.
			// @since 1.7.0
			do_action( 'pcgd_protection_blocked', 'plugin_guard', 'delete', $this->plugin_file );

			return false;
		}

		return $this->filesystem->delete(
			$file,
			$recursive,
			$type
		);
	}

	/**
	 * Forward all other methods.
	 *
	 * @param string $method Method name.
	 * @param array  $arguments Arguments.
	 * @return mixed
	 */
	public function __call( $method, $arguments ) {
		return call_user_func_array(
			array( $this->filesystem, $method ),
			$arguments
		);
	}
}