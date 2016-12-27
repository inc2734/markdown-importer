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

	/**
	 * Return messages
	 *
	 * @return array $this->messages
	 */
	public function get_messages() {
		return $this->messages;
	}

	/**
	 * Push message to $this->messages
	 *
	 * @param string $message
	 * @return void
	 */
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
		$has_markdown_file = false;
		$is_imported_markdown_file = false;
		$dir_name = basename( $dir );
		$files    = glob( $dir . '/*' );

		if ( ! $files ) {
			return false;
		}

		$_post = $this->_get_post_by_dir_name( $dir_name );
		if ( ! $_post ) {
			return false;
		}

		$has_markdown_file = $this->_has_markdown_file( $files );
		if ( ! $has_markdown_file ) {
			return false;
		}

		$post_id = $dir_name;

		foreach ( $files as $file ) {
			$filename = basename( $file );

			// If the file is image, attaching the post
			if ( preg_match( '/\.(jpg|jpeg|gif|png|bmp|svg)$/', $filename ) ) {
				$this->_import_the_image( $file, $post_id );
				continue;
			}

			// If the file is .md, update the post from this .md
			if ( preg_match( '/\.md$/', $filename ) ) {
				if ( $is_imported_markdown_file ) {
					$this->_push_message(
						sprintf(
							__( 'Markdown file is imported per post is the only one. %1$s/%2$s', 'markdown-importer' ),
							$dir_name,
							$filename
						)
					);
					continue;
				}

				if ( ! $this->_import_the_markdown( $file, $post_id ) ) {
					$this->_push_message(
						sprintf(
							__( 'Failed importing from %1$s/%2$s', 'markdown-importer' ),
							$dir_name,
							$filename
						)
					);
					continue;
				}

				$this->_push_message(
					sprintf(
						__( 'Imported from %1$s/%2$s', 'markdown-importer' ),
						$dir_name,
						$filename
					)
				);

				$is_imported_markdown_file = true;
			}
		}

		return true;
	}

	/**
	 * Getting WP_Post by directory name
	 *
	 * @param string $dir_name
	 * @return WP_Post|false
	 */
	protected function _get_post_by_dir_name( $dir_name ) {
		if ( ! preg_match( '/^\d+$/', $dir_name ) ) {
			return false;
		}

		$_post = get_post( $dir_name );
		if ( ! $_post ) {
			return false;
		}

		return $_post;
	}

	/**
	 * Whether to have markdown file
	 *
	 * @param array $files Array of filepath
	 * @return boolean
	 */
	protected function _has_markdown_file( $files ) {
		foreach ( $files as $file ) {
			$filename = basename( $file );
			if ( preg_match( '/\.md$/', $filename ) ) {
				return true;
			}
		}
		return false;
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
		$filename      = Markdown_Importer::generate_normalization_filename( $file );
		$new_filepath  = $upload_dir . '/' . $filename;

		if ( file_exists( $new_filepath ) ) {
			$this->_push_message(
				sprintf(
					__( 'Failed importing image %1$s. Already exists', 'markdown-importer' ),
					$file
				)
			);

			return false;
		}

		rename( $file, $new_filepath );

		$wp_check_filetype = wp_check_filetype( $new_filepath );
		$attachment = array(
			'post_mime_type' => $wp_check_filetype['type'],
			'post_title'     => basename( $file ),
			'post_status'    => 'inherit',
			'post_content'   => __( 'Uploaded from the Markdown Importer', 'markdown-importer' ),
		);

		$attach_id = wp_insert_attachment( $attachment, $new_filepath, $post_id );
		if ( ! $attach_id ) {
			return false;
		}

		$attach_data = wp_generate_attachment_metadata( $attach_id, $new_filepath );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		$this->_push_message(
			sprintf(
				__( 'Imported image from %1$s to %2$s', 'markdown-importer' ),
				$file,
				$new_filepath
			)
		);

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

		$Converting_Image = new Markdown_Importer_Converting_Image( $post_id, $content );
		$content = $Converting_Image->convert();

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

		$files = glob( $dir . '/{*,.*}', GLOB_ONLYDIR + GLOB_BRACE );
		foreach ( $files as $file ) {
			$filename = basename( $file );
			if ( $filename === '.' || $filename === '..' ) {
				continue;
			}
			$this->_rmdir( $file );
		}

		$files = glob( $dir . '/{*,.*}', GLOB_BRACE );
		foreach ( $files as $file ) {
			$filename = basename( $file );
			if ( $filename === '.' || $filename === '..' ) {
				continue;
			}
			unlink( $file );
		}

		rmdir( $dir );
	}
}
