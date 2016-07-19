<?php
class Markdown_Importer_Import {

	/**
	 * The path to upload the file to import
	 * @var string
	 */
	protected $upload_path;

	/**
	 * The result messages
	 */
	protected $messages = array();

	public function __construct() {
		$wp_upload_dir     = wp_upload_dir();
		$this->upload_path = untrailingslashit( $wp_upload_dir['basedir'] ) . '/markdown-importer';
	}

	/**
	 * Import process
	 *
	 * @return int The number of success import
	 */
	public function import() {
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

	public function get_messages() {
		return $this->messages;
	}

	protected function _push_message( $message ) {
		$this->messages[] = $message;
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

		foreach ( $files as $file ) {
			$filename = basename( $file );

			// If the file is image, attaching the post
			if ( preg_match( '/\.(jpg|jpeg|gif|png|bmp|svg)$/', $filename ) ) {
				$this->_import_the_image( $file, $post_id );
				continue;
			}

			// If the file is .md, update the post from this .md
			if ( preg_match( '/\.md$/', $filename ) ) {
				if ( $this->_import_the_markdown( $file, $post_id ) ) {
					$this->_push_message(
						sprintf(
							__( 'Imported from %1$s/%2$s', 'markdown-importer' ),
							$dir_name,
							$filename
						)
					);
				} else {
					$this->_push_message(
						sprintf(
							__( 'Failed importing from %1$s/%2$s', 'markdown-importer' ),
							$dir_name,
							$filename
						)
					);
				}
			}
		}

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
