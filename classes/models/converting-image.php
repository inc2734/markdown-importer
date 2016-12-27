<?php
class Markdown_Importer_Converting_Image {

	/**
	 * The Post ID
	 * @var int
	 */
	protected $post_id;

	/**
	 * Markdown content
	 * @var string
	 */
	protected $content;

	/**
	 * The upload directory url of the attachment.
	 * @var string
	 */
	protected $upload_url;

	/**
	 * @param int $post_id
	 * @param string $content
	 */
	public function __construct( $post_id, $content ) {
		$this->post_id = $post_id;
		$this->content = $content;
	}

	/**
	 * Convert markdown image to html image tag
	 *
	 * @return string
	 */
	public function convert() {
		$time             = get_the_time( 'Y/m', $this->post_id );
		$wp_upload_dir    = wp_upload_dir( $time, $this->post_id );
		$this->upload_url = untrailingslashit( $wp_upload_dir['url'] );

		return preg_replace_callback(
			'/\!\[(.*?)\]\((.+?)\)/sm',
			function( $matches ) {
				$filename       = Markdown_Importer::generate_normalization_filename( $matches[2] );
				$attachment_url = $this->upload_url . '/' . $filename;
				$attachment_id  = attachment_url_to_postid( $attachment_url );
				$full           = wp_get_attachment_image_url( $attachment_id, 'full' );
				$large          = wp_get_attachment_image_url( $attachment_id, 'large' );

				if ( ! $full || ! $large ) {
					return;
				}

				return sprintf(
					'<a class="markdown-importer-image-link" href="%1$s"><img class="size-large wp-image-%2$d markdown-importer-image" src="%3$s" alt="%4$s" /></a>',
					esc_url( $full ),
					esc_attr( $attachment_id ),
					esc_url( $large ),
					esc_attr( $matches[1] )
				);
			},
			$this->content
		);
	}
}
