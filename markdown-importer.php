<?php
/**
 * Plugin name: Markdown importer
 * Plugin URI:
 * Description: Importing posts from markdown files.
 * Version: 0.1.0
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

	/**
	 * Administration page title
	 * @var string
	 */
	protected $page_title;

	/**
	 * The capability
	 * @var string
	 */
	protected $capability;

	/**
	 * The noce action
	 * @var string
	 */
	protected $action;

	/**
	 * The path to upload the file to import
	 * @var string
	 */
	protected $upload_path;

	public function __construct() {
		$this->page_title  = __( 'Markdown Importer', 'markdown-importer' );
		$this->capability  = 'manage_options';
		$this->action      = 'markdown-importer';
		$wp_upload_dir     = wp_upload_dir();
		$this->upload_path = untrailingslashit( $wp_upload_dir['basedir'] ) . '/markdown-importer';

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	}

	public function plugins_loaded() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Adding administration page
	 *
	 * @return void
	 */
	public function admin_menu() {
		$hook = add_submenu_page(
			'tools.php',
			$this->page_title,
			$this->page_title,
			$this->capability,
			get_class( $this ),
			array( $this, 'display' )
		);
	}

	/**
	 * Displaying administration page
	 *
	 * @return void
	 */
	public function display() {
		if ( empty ( $_GET['step'] ) ) {
			$step = 0;
		} else {
			$step = (int) $_GET['step'];
		}

		if ( $step === 0 ) {
			$this->_display_input();
		} elseif ( $step === 1 ) {
			$this->_display_complete();
		}
	}

	/**
	 * Displaying input page
	 *
	 * @return void
	 */
	protected function _display_input() {
		$actionurl = add_query_arg( 'step', 1 );
		$nonce_url = wp_nonce_url( $actionurl, $this->action );
		?>
		<div class="wrap">
			<h2><?php echo esc_html( $this->page_title ); ?></h2>
			<form enctype="multipart/form-data" method="post" action="<?php echo esc_url( $nonce_url ); ?>">
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Run the import', 'markdown-importer' ); ?>">
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Dsiplaying complete page
	 *
	 * @return voide
	 */
	protected function _display_complete() {
		check_admin_referer( $this->action );
		?>
		<div class="wrap">
			<h2><?php echo esc_html( $this->page_title ); ?></h2>
			<?php
			check_admin_referer( $this->action );

			if ( $this->_import() ) {
				$this->_display_complete_message();
			} else {
				$this->_display_error_message();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Dsiplaying complete message
	 *
	 * @return void
	 */
	protected function _display_complete_message() {
		?>
		<p>
			Complete!
		</p>
		<?php
	}

	/**
	 * Dsiplaying error message
	 *
	 * @return void
	 */
	protected function _display_error_message() {
		?>
		<p>
			Error...
		</p>
		<?php
	}

	/**
	 * Import process
	 *
	 * @return int The number of success import
	 */
	protected function _import() {
		$import_count = 0;

		if ( ! file_exists( $this->upload_path ) ) {
			if ( ! mkdir( $this->upload_path ) ) {
				return $import_count;
			}
		}

		$files = glob( $this->upload_path . '/*' );

		if ( ! $files ) {
			return $import_count;
		}

		foreach ( $files as $file ) {
			if ( ! is_dir( $file ) ) {
				unlink( $file );
				continue;
			}

			$target_dir = untrailingslashit( $file );
			if ( $this->_parse_subdir( $target_dir ) ) {
				$import_count ++;
			}
			$this->_rmdir( $file );
		}

		return $import_count;
	}

	/**
	 * Parsing in the subdirectory and imoprting
	 *
	 * @param $dir subdirectory path
	 * @return bool
	 */
	protected function _parse_subdir( $dir ) {
		$dir_name = basename( $dir );
		$files    = glob( $dir . '/*' );

		// @todo
		// .md ファイルがない場合は終了
		// @todo
		// .md ファイルが複数ある場合は2つめ以降を無視

		if ( ! preg_match( '/^\d+$/', $dir_name ) ) {
			return false;
		}
		$post_id = $dir_name;
		$_post   = get_post( $post_id );
		if ( ! $_post ) {
			return false;
		}

		if ( ! $files ) {
			return false;
		}

		echo '<ul>';
		foreach ( $files as $file ) {
			$filename = basename( $file );

			// If the file is image, attaching the post
			if ( preg_match( '/\.(jpg|jpeg|gif|png|bmp|svg)$/', $filename ) ) {
				$this->_import_the_image( $file, $post_id );
				continue;
			}

			// If the file is .md, update the post from this .md
			if ( preg_match( '/\.md$/', $filename ) ) {
				$this->_import_the_markdown( $file, $post_id );
				echo '<li>Import from ' . esc_html( $dir_name ) . ' / ' . esc_html( $filename ) . '</li>';
			}
		}
		echo '</ul>';

		return true;
	}

	/**
	 * Import the image file
	 *
	 * @param string $file
	 * @param int $post_id
	 * @return bool
	 */
	protected function _import_the_image( $file, $post_id ) {
		require_once( ABSPATH . 'wp-admin' . '/includes/media.php' );
		require_once( ABSPATH . 'wp-admin' . '/includes/image.php' );
		$time = get_the_time( 'Y/m', $post_id );
		$wp_upload_dir = wp_upload_dir( $time, $post_id );
		$upload_dir    = untrailingslashit( $wp_upload_dir['path'] );
		$filename      = basename( $file );
		$new_filepath  = $upload_dir . '/' . $filename;

		if ( file_exists( $new_filepath ) ) {
			return false;
		}

		rename( $file, $new_filepath );

		$wp_check_filetype = wp_check_filetype( $new_filepath );
		$attachment = array(
			'post_mime_type' => $wp_check_filetype['type'],
			'post_title'     => $filename,
			'post_status'    => 'inherit',
			'post_content'   => __( 'Uploaded from the Markdown Importer', 'markdown-importer' ),
		);

		$attach_id = wp_insert_attachment( $attachment, $new_filepath, $post_id );
		if ( ! $attach_id ) {
			return false;
		}

		$attach_data = wp_generate_attachment_metadata( $attach_id, $new_filepath );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		return true;
	}

	/**
	 * Import the markdown file
	 *
	 * @param string $file
	 * @param int $post_id
	 * @return bool
	 */
	protected function _import_the_markdown( $file, $post_id ) {
		$content = file_get_contents( $file );

		if ( $content === false ) {
			return false;
		}

		$time = get_the_time( 'Y/m', $post_id );
		$wp_upload_dir = wp_upload_dir( $time, $post_id );
		$upload_url    = untrailingslashit( $wp_upload_dir['url'] );

		$content = preg_replace(
			'/\!\[(.*?)\]\((.+?)\)/sm',
			'<img src="' . esc_url( $upload_url ) . '/$2" alt="$1" />',
			$content
		);

		$_post = array(
			'ID'           => $post_id,
			'post_content' => $content,
		);

		if ( ! wp_update_post( $_post ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Remove directory
	 *
	 * @param $dir The path of the directory to delete
	 * @return void
	 */
	protected function _rmdir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		foreach ( glob( $dir . '/*', GLOB_ONLYDIR ) as $file ) {
			$this->_rmdir( $file );
		}

		foreach ( glob( $dir . '/*' ) as $file ) {
			unlink( $file );
		}

		rmdir( $dir );
	}
}

new Markdown_Importer();
