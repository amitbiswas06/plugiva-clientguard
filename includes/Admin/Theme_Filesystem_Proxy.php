<?php
/**
 * Filesystem proxy for theme deletion protection.
 *
 * @since 1.7.0
 * @package Plugiva_ClientGuard
 */

defined( 'ABSPATH' ) || exit;

class PCGD_Theme_Filesystem_Proxy {

	/**
	 * Original filesystem object.
	 *
	 * @var object
	 */
	private $filesystem;

	/**
	 * Protected theme stylesheet.
	 *
	 * @var string
	 */
	private $stylesheet;

	/**
	 * Constructor.
	 *
	 * @param object $filesystem Original filesystem object.
	 * @param string $stylesheet Theme stylesheet.
	 */
	public function __construct( $filesystem, $stylesheet ) {
		$this->filesystem = $filesystem;
		$this->stylesheet = $stylesheet;
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

		$theme_dir = trailingslashit(
			get_theme_root() . '/' . $this->stylesheet
		);

		$normalized_file = wp_normalize_path( $file );
		$normalized_dir  = wp_normalize_path( $theme_dir );

		if (
			$normalized_file === untrailingslashit( $normalized_dir ) ||
			strpos( $normalized_file, $normalized_dir ) === 0
		) {
			
			// Notify ClientGuard Sentinel that a protected theme deletion was blocked.
			// @since 1.7.0
			do_action( 'pcgd_protection_blocked', 'theme_guard', 'delete', $this->stylesheet );

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