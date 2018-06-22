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
	 * @param string $type error|warning|success|info
	 * @return void
	 */
	protected function _push_message( $message, $type = 'info' ) {
		$this->messages[] = array(
			'message' => $message,
			'type'    => $type,
		);
	}

	protected function _is_image( $file ) {
		if ( preg_match( '/\.(jpg|jpeg|gif|png|bmp|svg)$/', $file ) ) {
			return true;
		}
		return false;
	}

	protected function _is_markdown_file( $file ) {
		if ( preg_match( '/\.md$/', $file ) ) {
			return true;
		}
	}

	/**
	 * Parsing in the subdirectory and imoprting
	 *
	 * @param $dir subdirectory path
	 * @return bool
	 */
	protected function _parse_subdir( $dir ) {
		$has_markdown_file         = false;
		$is_imported_markdown_file = false;
		$dir_name                  = basename( $dir );
		$files                     = glob( $dir . '/*' );

		if ( ! $files ) {
			$this->_push_message(
				sprintf(
					__( 'Files not found in <code>%1$s</code>.', 'markdown-importer' ),
					$dir_name
				),
				'error'
			);

			return false;
		}

		$_post = $this->_get_post_by_dir_name( $dir_name );
		if ( ! $_post ) {
			$this->_push_message(
				sprintf(
					__( 'The post that post ID <code>%1$s</code> is not found.', 'markdown-importer' ),
					$dir_name
				),
				'error'
			);

			return false;
		}

		$markdown_files = array();
		$images         = array();

		foreach ( $files as $file ) {
			if ( $this->_is_image( $file ) ) {
				$images[] = $file;
			}

			if ( $this->_is_markdown_file( $file ) ) {
				$markdown_files[] = $file;
			}
		}

		if ( ! $markdown_files ) {
			$this->_push_message(
				sprintf(
					__( 'Markdown file is not found in <code>%1$s</code>.', 'markdown-importer' ),
					$dir_name
				),
				'error'
			);

			return false;
		}

		$post_id = $dir_name;

		// Attaching images into the post
		foreach ( $images as $file ) {
			$this->_import_the_image( $file, $post_id );
		}

		// Updating the post from markdown file
		foreach ( $markdown_files as $file ) {
			if ( $is_imported_markdown_file ) {
				$this->_push_message(
					sprintf(
						__( 'Markdown file is imported per post is the only one. <code>%1$s</code>', 'markdown-importer' ),
						$file
					),
					'warning'
				);

				break;
			}

			if ( ! $this->_import_the_markdown( $file, $post_id ) ) {
				$this->_push_message(
					sprintf(
						__( 'Failed importing from <code>%1$s</code>', 'markdown-importer' ),
						$file
					),
					'error'
				);

				continue;
			}

			$this->_push_message(
				sprintf(
					__( 'Imported from <code>%1$s</code>', 'markdown-importer' ),
					$file
				),
				'success'
			);

			$is_imported_markdown_file = true;
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
		$filename      = Markdown_Importer::generate_normalization_filename( $file, $post_id );
		$new_filepath  = $upload_dir . '/' . $filename;

		if ( ! $this->_is_image( $file ) ) {
			$this->_push_message(
				sprintf(
					__( 'Failed importing <code>%1$s</code>. This is not image.', 'markdown-importer' ),
					$file
				),
				'error'
			);

			return false;
		}

		if ( file_exists( $new_filepath ) ) {
			$this->_push_message(
				sprintf(
					__( 'Failed importing image <code>%1$s</code>. Already exists', 'markdown-importer' ),
					$file
				),
				'error'
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
			$this->_push_message(
				sprintf(
					__( 'Failed importing image <code>%1$s</code>. <code>wp_insert_attachment()</code> return false.', 'markdown-importer' ),
					$file
				),
				'error'
			);

			return false;
		}

		$attach_data = wp_generate_attachment_metadata( $attach_id, $new_filepath );
		if ( ! $attach_data ) {
			$this->_push_message(
				sprintf(
					__( 'Failed importing image <code>%1$s</code>. <code>wp_generate_attachment_metadata()</code> return false.', 'markdown-importer' ),
					$file
				),
				'error'
			);

			return false;
		}

		$attach_data = wp_update_attachment_metadata( $attach_id, $attach_data );
		if ( ! $attach_data ) {
			$this->_push_message(
				sprintf(
					__( 'Failed importing image <code>%1$s</code>. <code>wp_update_attachment_metadata()</code> return false.', 'markdown-importer' ),
					$file
				),
				'error'
			);

			return false;
		}

		$this->_push_message(
			sprintf(
				__( 'Imported image from <code>%1$s</code> to <code>%2$s</code>', 'markdown-importer' ),
				$file,
				$new_filepath
			),
			'success'
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
		if ( ! $this->_is_markdown_file( $file ) ) {
			$this->_push_message(
				sprintf(
					__( 'Failed importing <code>%1$s</code>. This is not markdown file.', 'markdown-importer' ),
					$file
				),
				'error'
			);

			return false;
		}

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
