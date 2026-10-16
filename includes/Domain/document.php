<?php

/**
 * Pure document helpers (no WordPress I/O except esc_url_raw in normalize).
 *
 * @package S2J_Webinar_Source_Qr
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Default style block for a new document.
 *
 * @return array{icon_attachment_id: int, foreground_hex: string, size_px: int}
 */
function s2j_wsq_default_style()
{
    return array(
        'icon_attachment_id' => 0,
        'foreground_hex'     => '#000000',
        'size_px'            => 512,
    );
}

/**
 * Fresh meta document (`draft`, empty channels until UI merges definitions).
 *
 * @return array<string, mixed>
 */
function s2j_wsq_default_document()
{
    return array(
        'status'     => 'draft',
        'provider'   => '',
        'channels'   => array(),
        'style'      => s2j_wsq_default_style(),
        'updated_at' => '',
    );
}

/**
 * Merge persisted or panel input onto defaults (does not validate URLs or catalog).
 *
 * @param mixed $input Raw meta or REST body.
 * @return array<string, mixed>
 */
function s2j_wsq_merge_document($input)
{
    $base   = s2j_wsq_default_document();
    $input  = is_array($input) ? $input : array();
    $merged = array_merge($base, array_intersect_key($input, $base));

    if (isset($input['channels']) && is_array($input['channels'])) {
        $merged['channels'] = s2j_wsq_sanitize_channel_rows($input['channels']);
    }

    if (isset($input['style']) && is_array($input['style'])) {
        $merged['style'] = array_merge(s2j_wsq_default_style(), $input['style']);
    }

    return $merged;
}

/**
 * Normalize channel row shape (preserve dynamic ids).
 *
 * @param array<int, mixed> $rows Incoming rows.
 * @return array<int, array<string, mixed>>
 */
function s2j_wsq_sanitize_channel_rows(array $rows)
{
    $out = array();
    foreach ($rows as $row) {
        if (! is_array($row) || empty($row['id'])) {
            continue;
        }
        $out[] = array(
            'id'               => (string) $row['id'],
            'label'            => isset($row['label']) ? (string) $row['label'] : '',
            'registration_url' => isset($row['registration_url']) ? (string) $row['registration_url'] : '',
            'qr_attachment_id' => isset($row['qr_attachment_id']) ? (int) $row['qr_attachment_id'] : 0,
        );
    }
    return $out;
}

/**
 * Index channel rows by id.
 *
 * @param array<int, array<string, mixed>> $rows Channel rows.
 * @return array<string, array<string, mixed>>
 */
function s2j_wsq_index_channels_by_id(array $rows)
{
    $by_id = array();
    foreach ($rows as $row) {
        if (! empty($row['id'])) {
            $by_id[ (string) $row['id'] ] = $row;
        }
    }
    return $by_id;
}

/**
 * Build channel rows from Webinar catalog + incoming panel/meta rows (definition order).
 *
 * @param array<int, array{source_name: string, label: string}> $catalog Source definitions.
 * @param array<int, array<string, mixed>>                     $incoming Existing panel/meta rows.
 * @return array<int, array<string, mixed>>
 */
function s2j_wsq_align_channels_to_catalog(array $catalog, array $incoming)
{
    $by_id = s2j_wsq_index_channels_by_id(s2j_wsq_sanitize_channel_rows($incoming));
    $out   = array();

    foreach ($catalog as $def) {
        $id      = (string) $def['source_name'];
        $existing = isset($by_id[ $id ]) ? $by_id[ $id ] : array();
        $out[]   = array(
            'id'               => $id,
            'label'            => (string) $def['label'],
            'registration_url' => isset($existing['registration_url']) ? (string) $existing['registration_url'] : '',
            'qr_attachment_id' => isset($existing['qr_attachment_id']) ? (int) $existing['qr_attachment_id'] : 0,
        );
    }

    return $out;
}

/**
 * Validate a registration URL for draft/apply.
 *
 * @param string $url Raw URL.
 * @return array{ok: true, url: string}|array{ok: false, error: array{code: string, message: string}}
 */
function s2j_wsq_validate_registration_url($url)
{
    $url = trim((string) $url);
    if ('' === $url) {
        return array( 'ok' => true, 'url' => '' );
    }

    $sanitized = esc_url_raw($url);
    if ('' === $sanitized || ! preg_match('#^https?://#i', $sanitized)) {
        return array(
            'ok'    => false,
            'error' => array(
                'code'    => 'invalid_registration_url',
                'message' => 'One or more registration URLs are invalid.',
            ),
        );
    }

    if (strlen($sanitized) > 2048) {
        return array(
            'ok'    => false,
            'error' => array(
                'code'    => 'invalid_registration_url',
                'message' => 'One or more registration URLs are invalid.',
            ),
        );
    }

    return array( 'ok' => true, 'url' => $sanitized );
}

/**
 * Normalize and validate the document style block.
 *
 * @param array<string, mixed> $style Style block.
 * @return array{ok: true, style: array<string, mixed>}|array{ok: false, error: array{code: string, message: string}}
 */
function s2j_wsq_normalize_style_block(array $style)
{
    $hex = isset($style['foreground_hex']) ? (string) $style['foreground_hex'] : '#000000';
    if (! preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
        return array(
            'ok'    => false,
            'error' => array(
                'code'    => 'invalid_document',
                'message' => 'Document style or attachments are invalid.',
            ),
        );
    }

    $size = isset($style['size_px']) ? (int) $style['size_px'] : 512;
    if ($size < 256) {
        $size = 256;
    }
    if ($size > 2048) {
        $size = 2048;
    }

    $icon_id = isset($style['icon_attachment_id']) ? (int) $style['icon_attachment_id'] : 0;
    if ($icon_id < 0) {
        $icon_id = 0;
    }

    return array(
        'ok'    => true,
        'style' => array(
            'icon_attachment_id' => $icon_id,
            'foreground_hex'     => $hex,
            'size_px'            => $size,
        ),
    );
}

/**
 * Invalidate qr_attachment_id when URL or shared style changed (persistence_spec).
 *
 * @param array<int, array<string, mixed>> $channels New channel rows.
 * @param array<string, mixed>|null          $previous Previous stored document (null = first save).
 * @param array<string, mixed>               $new_style Normalized style.
 * @return array<int, array<string, mixed>>
 */
function s2j_wsq_apply_qr_invalidation(array $channels, $previous, array $new_style)
{
    if (null === $previous) {
        return $channels;
    }

    $prev_style = isset($previous['style']) && is_array($previous['style']) ? $previous['style'] : array();
    $style_changed =
        (string) ( $prev_style['foreground_hex'] ?? '' ) !== (string) $new_style['foreground_hex'] ||
        (int) ( $prev_style['size_px'] ?? 0 ) !== (int) $new_style['size_px'] ||
        (int) ( $prev_style['icon_attachment_id'] ?? 0 ) !== (int) $new_style['icon_attachment_id'];

    $prev_by_id = s2j_wsq_index_channels_by_id(
        isset($previous['channels']) && is_array($previous['channels']) ? $previous['channels'] : array()
    );

    foreach ($channels as $index => $row) {
        $id   = (string) $row['id'];
        $prev = isset($prev_by_id[ $id ]) ? $prev_by_id[ $id ] : null;
        if (null === $prev) {
            continue;
        }
        $url_changed = (string) ( $prev['registration_url'] ?? '' ) !== (string) $row['registration_url'];
        if ($style_changed || $url_changed) {
            $channels[ $index ]['qr_attachment_id'] = 0;
        }
    }

    return $channels;
}

/**
 * Normalize document for draft save (catalog required).
 *
 * @param array<string, mixed>                             $document Panel document.
 * @param array<int, array{source_name: string, label: string}> $catalog Definitions.
 * @param array<string, mixed>|null                          $previous Previous meta (for QR invalidation).
 * @return array{ok: bool, document?: array<string, mixed>, error?: array{code: string, message: string}}
 */
function s2j_wsq_normalize_for_draft(array $document, array $catalog, $previous = null)
{
    $style_result = s2j_wsq_normalize_style_block(
        isset($document['style']) && is_array($document['style']) ? $document['style'] : array()
    );
    if (empty($style_result['ok'])) {
        return array(
            'ok'    => false,
            'error' => $style_result['error'],
        );
    }

    $document['style']    = $style_result['style'];
    $document['channels'] = s2j_wsq_align_channels_to_catalog(
        $catalog,
        isset($document['channels']) && is_array($document['channels']) ? $document['channels'] : array()
    );

    foreach ($document['channels'] as $index => $row) {
        $check = s2j_wsq_validate_registration_url($row['registration_url']);
        if (empty($check['ok'])) {
            return array(
                'ok'    => false,
                'error' => $check['error'],
            );
        }
        $document['channels'][ $index ]['registration_url'] = $check['url'];
    }

    $document['channels'] = s2j_wsq_apply_qr_invalidation($document['channels'], $previous, $document['style']);
    $document['status']     = 'draft';
    $document['updated_at'] = gmdate('c');

    return array(
        'ok'       => true,
        'document' => $document,
    );
}

/**
 * Whether every catalog row has a non-empty registration URL (apply precondition).
 *
 * @param array<string, mixed> $document Document with aligned channels.
 * @return bool
 */
function s2j_wsq_all_channels_have_url(array $document)
{
    if (empty($document['channels']) || ! is_array($document['channels'])) {
        return false;
    }
    foreach ($document['channels'] as $row) {
        if (empty($row['registration_url'])) {
            return false;
        }
    }
    return true;
}

/**
 * Merge Webinar tracking import items into a panel document (pure).
 *
 * @param array<string, mixed>               $document Panel document.
 * @param array<int, mixed> $items Mapped items from Webinar REST.
 * @return array{document: array<string, mixed>, unassigned: array<int, array<string, mixed>>}
 */
function s2j_wsq_merge_tracking_items(array $document, array $items)
{
    $document   = s2j_wsq_merge_document($document);
    $unassigned = array();
    $by_id      = s2j_wsq_index_channels_by_id($document['channels']);

    foreach ($items as $item) {
        if (! is_array($item)) {
            continue;
        }
        $name = isset($item['source_name']) ? (string) $item['source_name'] : '';
        $url  = isset($item['registration_url']) ? (string) $item['registration_url'] : '';
        if ('' === $name || '' === $url) {
            continue;
        }

        if (isset($by_id[ $name ])) {
            foreach ($document['channels'] as $index => $row) {
                if ($row['id'] === $name) {
                    $document['channels'][ $index ]['registration_url'] = $url;
                    break;
                }
            }
        } else {
            $unassigned[] = array(
                'source_name'      => $name,
                'registration_url' => $url,
                'external_id'      => isset($item['external_id']) ? (string) $item['external_id'] : '',
            );
        }
    }

    return array(
        'document'   => $document,
        'unassigned' => $unassigned,
    );
}
