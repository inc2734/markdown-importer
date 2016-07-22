<?php
class Markdown_Importer_Converting_Image {

	/**
	 * The attachment ID
	 * @var int
	 */
	protected $attachment_id;

	/**
	 * Markdown content
	 * @var string
	 */
	protected $content;

	/**
	 * @param int $attachment_id
	 * @param string $content
	 */
	public function __construct( $attachment_id, $content ) {
		$this->attachment_id = $attachment_id;
		$this->content       = $content;
	}

	/**
	 * Convert markdown image to html image tag
	 *
	 * @return string
	 */
	public function convert() {
		return preg_replace_callback(
			'/\!\[(.*?)\]\((.+?)\)/sm',
			function( $matches ) {
				$attachment = get_page_by_title( $matches[2], 'OBJECT', 'attachment' );
				$full  = $this->get_attachment_url( $attachment->ID, 'full' );
				$large = $this->get_attachment_url( $attachment->ID, 'large' );
				return sprintf(
					'<a href="%1$s"><img src="%2$s" alt="%3$s" /></a>',
					esc_url( $full ),
					esc_url( $large ),
					esc_attr( $matches[1] )
				);
			},
			$this->content
		);
	}

	/**
	 * Return attachment url
	 *
	 * @param int $attachment_id
	 * @param string attachment size
	 * @return string
	 */
	protected function get_attachment_url( $attachment_id, $size ) {
		$src = wp_get_attachment_image_src( $attachment_id, $size );
		if ( ! empty( $src[0] ) ) {
			return $src[0];
		}
	}
}
