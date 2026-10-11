<?php
/**
 * Pure document helpers (no WordPress I/O).
 *
 * @package S2J_Webinar_Source_Qr
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default style block for a new document.
 *
 * @return array{icon_attachment_id: int, foreground_hex: string, size_px: int}
 */
function s2j_wsq_default_style() {
	return array(
		'icon_attachment_id' => 0,
		'foreground_hex'     => '#000000',
		'size_px'            => 512,
	);
}

/**
 * Empty channel rows for all fixed ids.
 *
 * @return array<int, array{id: string, label: string, registration_url: string, qr_attachment_id: int}>
 */
function s2j_wsq_default_channels() {
	$rows = array();
	foreach ( S2J_Webinar_Source_Qr_Constants::channel_ids() as $id ) {
		$rows[] = array(
			'id'                => $id,
			'label'             => '',
			'registration_url'  => '',
			'qr_attachment_id'  => 0,
		);
	}
	return $rows;
}

/**
 * Fresh meta document (`draft`).
 *
 * @return array<string, mixed>
 */
function s2j_wsq_default_document() {
	return array(
		'status'     => 'draft',
		'provider'   => '',
		'channels'   => s2j_wsq_default_channels(),
		'style'      => s2j_wsq_default_style(),
		'updated_at' => '',
	);
}

/**
 * Merge persisted or panel input onto defaults (does not validate URLs).
 *
 * @param mixed $input Raw meta or REST body.
 * @return array<string, mixed>
 */
function s2j_wsq_merge_document( $input ) {
	$base   = s2j_wsq_default_document();
	$input  = is_array( $input ) ? $input : array();
	$merged = array_merge( $base, array_intersect_key( $input, $base ) );

	if ( isset( $input['channels'] ) && is_array( $input['channels'] ) ) {
		$merged['channels'] = s2j_wsq_merge_channels( $input['channels'] );
	}

	if ( isset( $input['style'] ) && is_array( $input['style'] ) ) {
		$merged['style'] = array_merge( s2j_wsq_default_style(), $input['style'] );
	}

	return $merged;
}

/**
 * Keep exactly three channel rows in canonical order.
 *
 * @param array<int, mixed> $rows Incoming rows.
 * @return array<int, array<string, mixed>>
 */
function s2j_wsq_merge_channels( array $rows ) {
	$by_id = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) || empty( $row['id'] ) ) {
			continue;
		}
		$id = (string) $row['id'];
		if ( in_array( $id, S2J_Webinar_Source_Qr_Constants::channel_ids(), true ) ) {
			$by_id[ $id ] = $row;
		}
	}

	$out = array();
	foreach ( S2J_Webinar_Source_Qr_Constants::channel_ids() as $id ) {
		$row = isset( $by_id[ $id ] ) ? $by_id[ $id ] : array();
		$out[] = array(
			'id'               => $id,
			'label'            => isset( $row['label'] ) ? (string) $row['label'] : '',
			'registration_url' => isset( $row['registration_url'] ) ? (string) $row['registration_url'] : '',
			'qr_attachment_id' => isset( $row['qr_attachment_id'] ) ? (int) $row['qr_attachment_id'] : 0,
		);
	}

	return $out;
}

/**
 * Normalize for draft save (partial URLs allowed).
 *
 * @param array<string, mixed> $document Merged document.
 * @return array{ok: bool, document?: array<string, mixed>, error?: array{code: string, message: string}}
 */
function s2j_wsq_normalize_for_draft( array $document ) {
	$document['status'] = 'draft';

	$style = $document['style'];
	if ( ! is_array( $style ) ) {
		$style = s2j_wsq_default_style();
	}

	$hex = isset( $style['foreground_hex'] ) ? (string) $style['foreground_hex'] : '#000000';
	if ( ! preg_match( '/^#[0-9A-Fa-f]{6}$/', $hex ) ) {
		return array(
			'ok'    => false,
			'error' => array(
				'code'    => 'invalid_foreground_hex',
				'message' => 'Invalid foreground color.',
			),
		);
	}

	$size = isset( $style['size_px'] ) ? (int) $style['size_px'] : 512;
	if ( $size < 256 ) {
		$size = 256;
	}
	if ( $size > 2048 ) {
		$size = 2048;
	}

	$icon_id = isset( $style['icon_attachment_id'] ) ? (int) $style['icon_attachment_id'] : 0;
	if ( $icon_id < 0 ) {
		$icon_id = 0;
	}

	$document['style'] = array(
		'icon_attachment_id' => $icon_id,
		'foreground_hex'     => $hex,
		'size_px'            => $size,
	);

	$channels = s2j_wsq_merge_channels( $document['channels'] );
	foreach ( $channels as $index => $row ) {
		$url = trim( (string) $row['registration_url'] );
		if ( '' !== $url ) {
			$sanitized = esc_url_raw( $url );
			if ( '' === $sanitized || ! preg_match( '#^https?://#i', $sanitized ) ) {
				return array(
					'ok'    => false,
					'error' => array(
						'code'    => 'invalid_registration_url',
						'message' => 'Invalid registration URL.',
					),
				);
			}
			if ( strlen( $sanitized ) > 2048 ) {
				return array(
					'ok'    => false,
					'error' => array(
						'code'    => 'registration_url_too_long',
						'message' => 'Registration URL is too long.',
					),
				);
			}
			$channels[ $index ]['registration_url'] = $sanitized;
		} else {
			$channels[ $index ]['registration_url'] = '';
		}
	}

	$document['channels'] = $channels;
	$document['updated_at'] = gmdate( 'c' );

	return array(
		'ok'       => true,
		'document' => $document,
	);
}

/**
 * Check all channels have URLs (apply precondition).
 *
 * @param array<string, mixed> $document Normalized document.
 * @return bool
 */
function s2j_wsq_all_channels_have_url( array $document ) {
	if ( empty( $document['channels'] ) || ! is_array( $document['channels'] ) ) {
		return false;
	}
	foreach ( $document['channels'] as $row ) {
		if ( empty( $row['registration_url'] ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Merge Webinar tracking import items into a panel document (pure).
 *
 * @param array<string, mixed> $document Panel document.
 * @param array<int, array<string, mixed>> $items Mapped items from Webinar REST.
 * @return array{document: array<string, mixed>, unassigned: array<int, array<string, mixed>>}
 */
function s2j_wsq_merge_tracking_items( array $document, array $items ) {
	$document = s2j_wsq_merge_document( $document );
	$unassigned = array();

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$name = isset( $item['source_name'] ) ? (string) $item['source_name'] : '';
		$url  = isset( $item['registration_url'] ) ? (string) $item['registration_url'] : '';
		if ( '' === $name || '' === $url ) {
			continue;
		}

		$matched = false;
		foreach ( $document['channels'] as $index => $row ) {
			if ( $row['id'] === $name ) {
				$document['channels'][ $index ]['registration_url'] = $url;
				$matched = true;
				break;
			}
		}

		if ( ! $matched ) {
			$unassigned[] = array(
				'source_name'      => $name,
				'registration_url' => $url,
				'external_id'      => isset( $item['external_id'] ) ? (string) $item['external_id'] : '',
			);
		}
	}

	return array(
		'document'   => $document,
		'unassigned' => $unassigned,
	);
}
