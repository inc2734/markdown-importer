<?php
/**
 * Plugin name: Markdown importer
 * Plugin URI:
 * Description: Importing posts from markdown files.
 * Version: 0.2.0
 * Author: inc2734
 * Author URI: http://2inc.org
 * Created: July 19, 2016
 * Modified: December 27, 2016
 * Text Domain: markdown-importer
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
class Markdown_Importer {

	public static $map;

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	public function plugins_loaded() {
		require_once plugin_dir_path( __FILE__ ) . 'classes/controllers/admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'classes/models/import.php';
		require_once plugin_dir_path( __FILE__ ) . 'classes/models/converting-image.php';
		require_once plugin_dir_path( __FILE__ ) . 'classes/models/unicode-normalization.php';
		new Markdown_Importer_Admin_Controller();
	}

	/**
	 * When filename has non half-width character, filename converted with sha1.
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function generate_normalization_filename( $filename ) {
		$pathinfo              = pathinfo( $filename );
		$Unicode_Normalization = new Markdown_Importer_Unicode_Normalization( basename( $filename ) );
		$filename              = $Unicode_Normalization->convert();

		if ( ! preg_match( '/^[a-zA-Z0-9_-]+\.' . $pathinfo['extension'] . '$/', $filename ) ) {
			$filename = sha1( $filename ) . '.' . $pathinfo['extension'];
		}
		return $filename;
	}
}

new Markdown_Importer();
