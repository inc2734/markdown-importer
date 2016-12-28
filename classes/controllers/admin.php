<?php
class Markdown_Importer_Admin_Controller {

	/**
	 * @var Markdown_Importer_Import
	 */
	protected $Import;

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

	public function __construct() {
		$this->Import      = new Markdown_Importer_Import();
		$this->page_title  = __( 'Markdown Importer', 'markdown-importer' );
		$this->capability  = 'manage_options';
		$this->action      = 'markdown-importer';

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
			$this->_display_input_page();
		} elseif ( $step === 1 ) {
			$this->_display_complete_page();
		}
	}

	/**
	 * Displaying input page
	 *
	 * @return void
	 */
	protected function _display_input_page() {
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
	 * Displaying complete page
	 *
	 * @return voide
	 */
	protected function _display_complete_page() {
		?>
		<div class="wrap">
			<h2><?php echo esc_html( $this->page_title ); ?></h2>
			<?php
			check_admin_referer( $this->action );

			$imported_count = $this->Import->import();
			$this->_display_import_messages();
			if ( $imported_count ) {
				$this->_display_complete_message();
			} else {
				$this->_display_no_imported_message();
			}
			?>
		</div>
		<?php
	}

	/**
	 * Displaying messages from Import object
	 *
	 * @return void
	 */
	protected function _display_import_messages() {
		$messages = $this->Import->get_messages();
		if ( $messages ) {
			echo '<ul>';
			foreach ( $messages as $message ) {
				printf( '<li>%1$s</li>', esc_html( $message ) );
			}
			echo '</ul>';
		}
	}

	/**
	 * Displaying complete message
	 *
	 * @return void
	 */
	protected function _display_complete_message() {
		?>
		<p>
			<?php esc_html_e( 'Complete!', 'markdown-importer' ); ?>
		</p>
		<?php
	}

	/**
	 * Displaying no imported message
	 *
	 * @return void
	 */
	protected function _display_no_imported_message() {
		?>
		<p>
			<?php esc_html_e( 'There are no markdown files.', 'markdown-importer' ); ?>
		</p>
		<?php
	}
}
