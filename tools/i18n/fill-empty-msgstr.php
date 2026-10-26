<?php
/**
 * Fill empty msgstr entries from a msgid => translation map (CLI).
 *
 * Usage: php tools/i18n/fill-empty-msgstr.php languages/s2j-webinar-ja.po path/to/map.php
 *
 * @package S2J_Webinar
 */

$argc = isset($_SERVER['argc']) ? (int) $_SERVER['argc'] : 0;
$argv = isset($_SERVER['argv']) && is_array($_SERVER['argv']) ? $_SERVER['argv'] : array();

if ($argc < 3) {
	fwrite(STDERR, "Usage: php fill-empty-msgstr.php <po-file> <map.php>\n");
	exit(1);
}

$po_file = $argv[1];
$map     = require $argv[2];
if (! is_array($map)) {
	fwrite(STDERR, "Map must return an array.\n");
	exit(1);
}

$lines   = file($po_file, FILE_IGNORE_NEW_LINES);
$changed = 0;
$i       = 0;
$n       = count($lines);

while ($i < $n) {
	if (! str_starts_with($lines[$i], 'msgid ')) {
		++$i;
		continue;
	}
	$msgid_line = $lines[$i];
	$msgid      = parse_po_string($msgid_line);
	++$i;
	if ($i >= $n || ! str_starts_with($lines[$i], 'msgstr ')) {
		continue;
	}
	if ($lines[$i] !== 'msgstr ""') {
		++$i;
		continue;
	}
	if ($msgid === '' || ! isset($map[ $msgid ])) {
		++$i;
		continue;
	}
	$lines[$i] = 'msgstr ' . po_quote($map[ $msgid ]);
	++$changed;
	++$i;
}

file_put_contents($po_file, implode("\n", $lines) . "\n");
fwrite(STDOUT, "Updated {$changed} entries in {$po_file}\n");

/**
 * @param string $line msgid "..." line.
 */
function parse_po_string(string $line): string {
	$line = trim($line);
	if (! str_starts_with($line, 'msgid "') || ! str_ends_with($line, '"')) {
		return '';
	}
	$quoted = substr($line, 7, -1);

	return stripcslashes($quoted);
}

function po_quote(string $text): string {
	return '"' . addcslashes($text, "\\\"\n\t\r") . '"';
}
