<?php
/**
 * QR PNG generation and Apply persistence (server-only).
 *
 * @package S2J_Webinar_Source_Qr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Load Composer autoload for chillerlan/php-qrcode.
 *
 * @return bool
 */
function s2j_wsq_qr_autoload_available() {
	static $loaded = null;
	if ( null !== $loaded ) {
		return $loaded;
	}

	$autoload = defined( 'S2J_WEBINAR_SOURCE_QR_PLUGIN_DIR' )
		? S2J_WEBINAR_SOURCE_QR_PLUGIN_DIR . 'vendor/autoload.php'
		: dirname( __DIR__, 2 ) . '/vendor/autoload.php';

	if ( is_readable( $autoload ) ) {
		require_once $autoload;
	}

	$loaded = class_exists( QRCode::class, false );
	return $loaded;
}

/**
 * @param string $hex #RRGGBB.
 * @return array{0: int, 1: int, 2: int}
 */
function s2j_wsq_hex_to_rgb( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( strlen( $hex ) !== 6 ) {
		return array( 0, 0, 0 );
	}
	return array(
		(int) hexdec( substr( $hex, 0, 2 ) ),
		(int) hexdec( substr( $hex, 2, 2 ) ),
		(int) hexdec( substr( $hex, 4, 2 ) ),
	);
}

/**
 * Map document size_px to chillerlan module scale (approximate output dimension).
 *
 * @param int $size_px Target size hint.
 * @return int
 */
function s2j_wsq_qr_scale_for_size_px( $size_px ) {
	$size_px = max( 256, min( 2048, (int) $size_px ) );
	return max( 2, (int) round( $size_px / 40 ) );
}

/**
 * Allowed media MIME types for center icon (CoverArt-style raster list).
 *
 * @return list<string>
 */
function s2j_wsq_qr_allowed_icon_mime_types() {
	return array(
		'image/jpeg',
		'image/png',
		'image/gif',
		'image/webp',
	);
}

/**
 * Load a raster icon from a media attachment as a GD image.
 *
 * @param int $attachment_id Attachment post ID.
 * @return GdImage|WP_Error
 */
function s2j_wsq_gd_load_icon_from_attachment( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( $attachment_id <= 0 ) {
		return new WP_Error(
			'qr_generation_failed',
			__( 'Center icon attachment is invalid.', 's2j-webinar-source-qr' ),
			array( 'status' => 400 )
		);
	}

	if ( 'attachment' !== get_post_type( $attachment_id ) ) {
		return new WP_Error(
			'qr_generation_failed',
			__( 'Center icon attachment is invalid.', 's2j-webinar-source-qr' ),
			array( 'status' => 400 )
		);
	}

	$mime = get_post_mime_type( $attachment_id );
	if ( ! is_string( $mime ) || ! in_array( $mime, s2j_wsq_qr_allowed_icon_mime_types(), true ) ) {
		return new WP_Error(
			'qr_generation_failed',
			__( 'Center icon must be a JPEG, PNG, GIF, or WebP image.', 's2j-webinar-source-qr' ),
			array( 'status' => 400 )
		);
	}

	$path = get_attached_file( $attachment_id );
	if ( ! is_string( $path ) || ! is_readable( $path ) ) {
		return new WP_Error(
			'qr_generation_failed',
			__( 'Center icon file could not be read.', 's2j-webinar-source-qr' ),
			array( 'status' => 500 )
		);
	}

	$image = s2j_wsq_gd_load_image_from_path( $path, $mime );
	if ( is_wp_error( $image ) ) {
		return $image;
	}

	return $image;
}

/**
 * @param string $path Absolute file path.
 * @param string $mime MIME type.
 * @return GdImage|WP_Error
 */
function s2j_wsq_gd_load_image_from_path( $path, $mime ) {
	switch ( $mime ) {
		case 'image/jpeg':
			$image = function_exists( 'imagecreatefromjpeg' ) ? @imagecreatefromjpeg( $path ) : false;
			break;
		case 'image/png':
			$image = function_exists( 'imagecreatefrompng' ) ? @imagecreatefrompng( $path ) : false;
			break;
		case 'image/gif':
			$image = function_exists( 'imagecreatefromgif' ) ? @imagecreatefromgif( $path ) : false;
			break;
		case 'image/webp':
			$image = function_exists( 'imagecreatefromwebp' ) ? @imagecreatefromwebp( $path ) : false;
			break;
		default:
			$image = false;
	}

	if ( false === $image || ! ( $image instanceof GdImage ) ) {
		return new WP_Error(
			'qr_generation_failed',
			__( 'Center icon could not be decoded.', 's2j-webinar-source-qr' ),
			array( 'status' => 500 )
		);
	}

	return $image;
}

/**
 * Composite center icon onto a QR PNG (white quiet-zone plate + scaled icon).
 *
 * @param string $png_bytes         Raw QR PNG.
 * @param int    $icon_attachment_id Media attachment ID; 0 skips overlay.
 * @return string|WP_Error PNG bytes.
 */
function s2j_wsq_overlay_center_icon_on_qr_png( $png_bytes, $icon_attachment_id ) {
	$icon_attachment_id = (int) $icon_attachment_id;
	if ( $icon_attachment_id <= 0 ) {
		return $png_bytes;
	}

	$qr = @imagecreatefromstring( $png_bytes );
	if ( false === $qr || ! ( $qr instanceof GdImage ) ) {
		return new WP_Error(
			'qr_generation_failed',
			__( 'Could not decode QR image for icon overlay.', 's2j-webinar-source-qr' ),
			array( 'status' => 500 )
		);
	}

	$icon = s2j_wsq_gd_load_icon_from_attachment( $icon_attachment_id );
	if ( is_wp_error( $icon ) ) {
		imagedestroy( $qr );
		return $icon;
	}

	$qr_w = imagesx( $qr );
	$qr_h = imagesy( $qr );
	$box  = (int) max( 8, floor( min( $qr_w, $qr_h ) * 0.22 ) );
	$pad  = (int) max( 4, floor( $box * 0.12 ) );
	$plate = $box + ( 2 * $pad );

	$plate_x = (int) floor( ( $qr_w - $plate ) / 2 );
	$plate_y = (int) floor( ( $qr_h - $plate ) / 2 );

	$white = imagecolorallocate( $qr, 255, 255, 255 );
	imagefilledrectangle( $qr, $plate_x, $plate_y, $plate_x + $plate - 1, $plate_y + $plate - 1, $white );

	$icon_w = imagesx( $icon );
	$icon_h = imagesy( $icon );
	if ( $icon_w > 0 && $icon_h > 0 ) {
		$scale  = min( $box / $icon_w, $box / $icon_h );
		$dest_w = max( 1, (int) floor( $icon_w * $scale ) );
		$dest_h = max( 1, (int) floor( $icon_h * $scale ) );
		$dest_x = $plate_x + $pad + (int) floor( ( $box - $dest_w ) / 2 );
		$dest_y = $plate_y + $pad + (int) floor( ( $box - $dest_h ) / 2 );

		imagealphablending( $qr, true );
		imagecopyresampled( $qr, $icon, $dest_x, $dest_y, 0, 0, $dest_w, $dest_h, $icon_w, $icon_h );
	}

	imagedestroy( $icon );

	ob_start();
	imagepng( $qr );
	$out = ob_get_clean();
	imagedestroy( $qr );

	if ( ! is_string( $out ) || strlen( $out ) < 8 ) {
		return new WP_Error(
			'qr_generation_failed',
			__( 'Could not encode QR image with icon.', 's2j-webinar-source-qr' ),
			array( 'status' => 500 )
		);
	}

	return $out;
}

/**
 * Render registration URL to raw PNG bytes.
 *
 * @param string               $url   Encoded payload.
 * @param array<string, mixed> $style Normalized style block.
 * @return string|WP_Error Binary PNG on success.
 */
function s2j_wsq_render_qr_png_bytes( $url, array $style ) {
	if ( ! s2j_wsq_qr_autoload_available() ) {
		return new WP_Error(
			'qr_generation_failed',
			__( 'QR library is unavailable.', 's2j-webinar-source-qr' ),
			array( 'status' => 500 )
		);
	}

	if ( ! extension_loaded( 'gd' ) ) {
		return new WP_Error(
			'qr_generation_failed',
			__( 'GD extension is required to generate QR images.', 's2j-webinar-source-qr' ),
			array( 'status' => 500 )
		);
	}

	$hex   = isset( $style['foreground_hex'] ) ? (string) $style['foreground_hex'] : '#000000';
	$rgb   = s2j_wsq_hex_to_rgb( $hex );
	$scale = s2j_wsq_qr_scale_for_size_px( isset( $style['size_px'] ) ? (int) $style['size_px'] : 512 );

	$options = new QROptions(
		array(
			'outputType'       => QROutputInterface::GDIMAGE_PNG,
			'outputBase64'     => false,
			'scale'            => $scale,
			'bgColor'          => array( 255, 255, 255 ),
			'imageTransparent' => false,
			'moduleValues'     => array(
				QRMatrix::M_FINDER_DARK     => $rgb,
				QRMatrix::M_DATA_DARK        => $rgb,
				QRMatrix::M_ALIGNMENT_DARK   => $rgb,
				QRMatrix::M_TIMING_DARK      => $rgb,
				QRMatrix::M_FORMAT_DARK      => $rgb,
				QRMatrix::M_VERSION_DARK     => $rgb,
				QRMatrix::M_DARKMODULE       => $rgb,
				QRMatrix::M_FINDER_DOT       => $rgb,
			),
		)
	);

	try {
		$png = ( new QRCode( $options ) )->render( (string) $url );
	} catch ( Throwable $e ) {
		return new WP_Error(
			'qr_generation_failed',
			__( 'Could not generate QR image.', 's2j-webinar-source-qr' ),
			array( 'status' => 500 )
		);
	}

	if ( ! is_string( $png ) || strlen( $png ) < 8 ) {
		return new WP_Error(
			'qr_generation_failed',
			__( 'Could not generate QR image.', 's2j-webinar-source-qr' ),
			array( 'status' => 500 )
		);
	}

	$icon_id = isset( $style['icon_attachment_id'] ) ? (int) $style['icon_attachment_id'] : 0;
	if ( $icon_id > 0 ) {
		$with_icon = s2j_wsq_overlay_center_icon_on_qr_png( $png, $icon_id );
		if ( is_wp_error( $with_icon ) ) {
			return $with_icon;
		}
		$png = $with_icon;
	}

	return $png;
}

/**
 * Store PNG bytes as an attachment on the event.
 *
 * @param int    $post_id  Event post ID.
 * @param string $filename File name (not path).
 * @param string $png      Raw PNG.
 * @return int|WP_Error Attachment ID.
 */
function s2j_wsq_store_qr_png_attachment( $post_id, $filename, $png ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$upload = wp_upload_bits( $filename, null, $png );
	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error(
			'qr_media_failed',
			__( 'Could not upload QR image.', 's2j-webinar-source-qr' ),
			array( 'status' => 500 )
		);
	}

	$filetype = wp_check_filetype( $filename, null );
	$attachment = array(
		'post_mime_type' => $filetype['type'] ? $filetype['type'] : 'image/png',
		'post_title'     => preg_replace( '/\.[^.]+$/', '', sanitize_file_name( $filename ) ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);

	$attach_id = wp_insert_attachment( $attachment, $upload['file'], (int) $post_id );
	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		return new WP_Error(
			'qr_media_failed',
			__( 'Could not save QR media.', 's2j-webinar-source-qr' ),
			array( 'status' => 500 )
		);
	}

	$metadata = wp_generate_attachment_metadata( (int) $attach_id, $upload['file'] );
	if ( is_array( $metadata ) ) {
		wp_update_attachment_metadata( (int) $attach_id, $metadata );
	}

	return (int) $attach_id;
}

/**
 * Generate PNGs, attach media, set ready status (Apply pipeline).
 *
 * @param int                  $post_id  Event post ID.
 * @param array<string, mixed> $document Normalized document (draft status, aligned channels).
 * @return array<string, mixed>|WP_Error
 */
function s2j_wsq_apply_document_with_qr( $post_id, array $document ) {
	if ( empty( $document['channels'] ) || ! is_array( $document['channels'] ) ) {
		return new WP_Error(
			'invalid_registration_url',
			__( 'One or more registration URLs are invalid.', 's2j-webinar-source-qr' ),
			array( 'status' => 400 )
		);
	}

	$style = isset( $document['style'] ) && is_array( $document['style'] )
		? $document['style']
		: s2j_wsq_default_style();

	foreach ( $document['channels'] as $index => $row ) {
		if ( empty( $row['registration_url'] ) ) {
			return new WP_Error(
				'invalid_registration_url',
				__( 'One or more registration URLs are invalid.', 's2j-webinar-source-qr' ),
				array( 'status' => 400 )
			);
		}

		$channel_id = isset( $row['id'] ) ? sanitize_file_name( (string) $row['id'] ) : 'channel';
		$png        = s2j_wsq_render_qr_png_bytes( (string) $row['registration_url'], $style );
		if ( is_wp_error( $png ) ) {
			return $png;
		}

		$filename = sprintf( 's2j-wsq-%d-%s.png', (int) $post_id, $channel_id );
		$attach_id = s2j_wsq_store_qr_png_attachment( (int) $post_id, $filename, $png );
		if ( is_wp_error( $attach_id ) ) {
			return $attach_id;
		}

		$document['channels'][ $index ]['qr_attachment_id'] = $attach_id;
	}

	$document['status']     = 'ready';
	$document['updated_at'] = gmdate( 'c' );

	return $document;
}
