<?php

/**
 * Slimdown - A very basic regex-based Markdown parser. Supports the
 * following elements (and can be extended via Slimdown::add_rule()):
 *
 * - Headers
 * - Links
 * - Bold
 * - Emphasis
 * - Deletions
 * - Quotes
 * - Blockquotes
 * - Ordered/unordered lists
 */
class Slimdown {
	public static $rules = array (
		'/(#+)(.*)/e' => 'self::header (\'\\1\', \'\\2\')',       // headers
		'/\[([^\[]+)\]\(([^\)]+)\)/' => '<a href=\'\2\'>\1</a>',  // links
		'/(\*\*|__)(.*?)\1/' => '<strong>\2</strong>',            // bold
		'/(\*|_)(.*?)\1/' => '<em>\2</em>',                       // emphasis
		'/\~\~(.*?)\~\~/' => '<del>\1</del>',                     // del
		'/\:\"(.*?)\"\:/' => '<q>\1</q>',                         // quote
		'/\n\*(.*)/e' => 'self::ul_list (\'\\1\')',               // ul lists
		'/\n[0-9]+\.(.*)/e' => 'self::ol_list (\'\\1\')',         // ol lists
		'/\n&gt;(.*)/e' => 'self::blockquote (\'\\1\')',          // blockquotes
		'/\n([^\n]+)\n/e' => 'self::para (\'\\1\')',              // add paragraphs
		'/<\/ul><ul>/' => '',                                     // fix extra ul
		'/<\/ol><ol>/' => '',                                     // fix extra ol
		'/<\/blockquote><blockquote>/' => "\n"                    // fix extra blockquote
	);

	private static function para ($line) {
		$trimmed = trim ($line);
		if (strpos ($trimmed, '<') === 0) {
			return $line;
		}
		return sprintf ("\n<p>%s</p>\n", $trimmed);
	}

	private static function ul_list ($item) {
		return sprintf ("\n<ul>\n\t<li>%s</li>\n</ul>", trim ($item));
	}

	private static function ol_list ($item) {
		return sprintf ("\n<ol>\n\t<li>%s</li>\n</ol>", trim ($item));
	}

	private static function blockquote ($item) {
		return sprintf ("\n<blockquote>%s</blockquote>", trim ($item));
	}

	private static function header ($chars, $header) {
		$level = strlen ($chars);
		return sprintf ('<h%d>%s</h%d>', $level, trim ($header), $level);
	}

	/**
	 * Add a rule.
	 */
	public static function add_rule ($regex, $replacement) {
		self::$rules[$regex] = $replacement;
	}

	/**
	 * Render some Markdown into HTML.
	 */
	public static function render ($text) {
		$text = "\n" . $text . "\n";
		foreach (self::$rules as $regex => $replacement) {
			$text = preg_replace ($regex, $replacement, $text);
		}
		return trim ($text);
	}
}