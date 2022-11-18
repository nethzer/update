<?php
namespace Plugin\Sideloader;

 /**
  * Function handles downloading a remote file and inserting it
  * into the WP Media Library.
  * @param string $url HTTP URL address of a remote file
  * @param int $post_id The post ID the media is associated with
  * @param string $desc Description of the side-loaded file
  * @param string $post_data Post data to override
  * 
  * @see https://developer.wordpress.org/reference/functions/media_handle_sideload/
  *
  * @example $attachment_id = sideload( $url [, $post_id [, $desc [, $post_data]]] );
  * @return int|WP_Error The ID of the attachment or a WP_Error on failure
  */
function sideloader($url, $post_id = 0, $desc = null, $post_data = null) {
	// URL Validation
	if ( ! wp_http_validate_url( $url ) ) {
		return new WP_Error('invalid_url', 'File URL is invalid', array( 'status' => 400 ));
	}
	
	// Gives us access to the download_url() and media_handle_sideload() functions.
	if( ! function_exists('download_url') || !function_exists('media_handle_sideload') ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
	}
	
	// Download file to temp dir.
	$temp_file = download_url( $url );
	
	if ( is_wp_error( $temp_file ) ) {
		@unlink($temp_file);
		return $temp_file;
	}
	
	// An array similar to that of a PHP `$_FILES` POST array
	$file_url_path = parse_url($url, PHP_URL_PATH);
	$file_info = wp_check_filetype( $file_url_path );
	$file = array(
		'tmp_name' => $temp_file,
		'type'     => $file_info['type'],
		'name'     => basename( $file_url_path ),
		'size'     => filesize( $temp_file ),
	);
	
	// Move the temporary file into the uploads directory.
	$attachment_id = media_handle_sideload( $file, $post_id, $desc, $post_data );
	
	@unlink($temp_file);
	return $attachment_id;
}
