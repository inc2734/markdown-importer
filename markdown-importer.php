<?php
/**
 * Plugin name: Markdown importer
 * Plugin URI:
 * Description: Importing posts from markdown files.
 * Version: 0.1.2
 * Author: inc2734
 * Author URI: http://2inc.org
 * Created: July 19, 2016
 * Modified:
 * Text Domain: markdown-importer
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */
class Markdown_Importer {

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	public function plugins_loaded() {
		require_once plugin_dir_path( __FILE__ ) . 'classes/controllers/admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'classes/models/import.php';
		require_once plugin_dir_path( __FILE__ ) . 'classes/models/converting-image.php';
		new Markdown_Importer_Admin_Controller();
	}
}

new Markdown_Importer();
