<?php
namespace Frogrammer\ConsentPilot;

if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'Sorry, you are not allowed to access this page.', 'consent-pilot' ) );
}

/**
 * Minimal PSR-4-style autoloader.
 */
final class Autoloader {

	/**
	 * Class name prefix.
	 *
	 * @var string
	 */
	private static $prefix;

	/**
	 * Base directory for class files.
	 *
	 * @var string
	 */
	private static $base_dir;

	/**
	 * Registers the autoloader.
	 *
	 * @param string $prefix   The class name prefix.
	 * @param string $base_dir The base directory for class files.
	 *
	 * @return void
	 */
	public static function register( $prefix, $base_dir ) {
		self::$prefix   = rtrim( $prefix, '\\' ) . '\\';
		self::$base_dir = rtrim( $base_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Loads the given class file if it matches the prefix.
	 *
	 * @param string $class The fully-qualified class name.
	 *
	 * @return void
	 */
	private static function autoload( $class ) {
		if ( strpos( $class, self::$prefix ) !== 0 ) {
			return;
		}

		$relative		= substr( $class, strlen( self::$prefix ) );
		$relative_path	= str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';
		$file			= self::$base_dir . $relative_path;

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
