=== Markdown Importer ===
Contributors: inc2734, megane9988, toro_unit
Donate link: http://www.amazon.co.jp/registry/wishlist/39ANKRNSTNW40
Tags: plugin, importer, markdown
Requires at least: 4.5.3
Tested up to: 4.7.0
Stable tag: 0.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Importing posts from markdown files.

== Description ==

Importing posts from markdown files. When importing, to convert the markdown image to html image tag.

== Installation ==

1. Upload `Smart Custom Fields` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create uploading files. /[Post ID]/foo.md and image files
1. Uploading the files to /wp-content/uploads/markdown-importer/
1. Run on Tools > markdown-importer

== Screenshots ==

== Changelog ==

= 0.2.0 =
* Added messages of importing images.
* Change the image file name to sha1 only if it contains multi-byte characters.

= 0.1.3 =
* Support multibyte filename of image. The filename is hashed string.
* Fixed unicode normalization bug.

= 0.1.2 =
* Fixed bug for using comporser

= 0.1.1 =
* Refactoring

= 0.1.0 =
* Initial release.
