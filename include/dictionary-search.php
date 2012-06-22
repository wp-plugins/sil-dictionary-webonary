<?php
/**
 * Search
 *
 * Search functions for SIL Dictionaries.
 *
 * PHP version 5.2
 *
 * LICENSE GPL v2
 *
 * @package WordPress
 * @since 3.1
 */

// This file was originally based upon the Search Custom Fields plugin and template
// (search-custom.php) by Kaf Oseo. http://guff.szub.net/search-custom-fields/.
// The code has since been mangled and evolved beyond recognition from that.

// don't load directly
if ( ! defined('ABSPATH') )
	die( '-1' );

//---------------------------------------------------------------------------//

function sil_dictionary_select_distinct() {
	return "DISTINCTROW";
}

//---------------------------------------------------------------------------//

function sil_dictionary_custom_join($join) {
	global $wp_query, $wpdb;

	/*
	 * The query I'm going for will hopefully end up looking something like this
	 * example:
	 * SELECT id, language_code, relevance, post_title
	 * FROM wp_posts p
	 * JOIN (
	 *	SELECT post_id, language_code, MAX(relevance) AS relevance, search_strings
	 *	FROM sil_multilingual_search
	 *	WHERE search_strings like '%sleeping%'
	 *	GROUP BY post_id, language_code
	 *	ORDER BY relevance DESC
	 *	) sil_multilingual_search ON sil_multilingual_search.post_id = p.id
	 * ORDER BY relevance DESC, post_title;
	 */
	mb_internal_encoding("UTF-8");
	if( !empty($wp_query->query_vars['s'])) {
		$search = trim($wp_query->query_vars['s']);
		$key = $_GET['key'];
		$partialsearch = $_GET['partialsearch'];  
		
		if(strlen($search) == 0 && $_GET['tax'] > 1)
		{
			$partialsearch = 1;
		} 

		$subquery_where = "";
		if( strlen( trim( $key ) ) > 0)
			$subquery_where .= " WHERE sil_multilingual_search.language_code = '$key' ";
		$subquery_where .= empty( $subquery_where ) ? " WHERE " : " AND ";
		if ( is_CJK( $search ) || mb_strlen($search) > 3 || $partialsearch == 1)
			$subquery_where .= " sil_multilingual_search.search_strings LIKE '%" .
				addslashes( $search ) . "%'";
		else
            $subquery_where .= " sil_multilingual_search.search_strings REGEXP '[[:<:]]" .
				addslashes( $search ) . "[[:>:]]'";

		$subquery =
			" (SELECT post_id, language_code, MAX(relevance) AS relevance, search_strings " .
			"FROM sil_multilingual_search " .
			$subquery_where .
			"GROUP BY post_id, language_code " .
			"ORDER BY relevance DESC) ";
		$join = " JOIN " . $subquery . " sil_multilingual_search ON $wpdb->posts.ID = sil_multilingual_search.post_id ";
	}
	if( $_GET['tax'] > 1) {
		$join .= " LEFT JOIN wp_term_relationships ON wp_posts.ID = wp_term_relationships.object_id ";
		$join .= " INNER JOIN wp_term_taxonomy ON wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id ";
	}
	return $join;
}

function sil_dictionary_custom_message()
{		
	mb_internal_encoding("UTF-8");
	if(!is_CJK($_GET['s']) && mb_strlen($_GET['s']) <= 3 && $_GET['partialsearch'] != 1)
	{
		//echo getstring("partial-search-omitted");
		_e('Because of the brevity of your search term, partial search was omitted.', 'sil_dictionary');
		echo "<br>";
		echo '<a href="?' . $_SERVER["QUERY_STRING"] . '&partialsearch=1">'; _e('Click here to include searching through partial words.', 'sil_dictionary'); echo '</a>';		 
	}
}

//---------------------------------------------------------------------------//

function sil_dictionary_custom_where($where) {
	global $wp_query, $wp_version, $wpdb;
	if( !empty($wp_query->query_vars['s'])) {
		$search = $wp_query->query_vars['s'];
		$key = $_GET['key'];
		$where = ($wp_version >= 2.1) ? ' AND post_type = \'post\' AND post_status = \'publish\'' : ' AND post_status = \'publish\'';
	}

	if($_GET['tax'] > 1)
	{
	$wp_query->is_search = true;
	$where .= " AND wp_term_taxonomy.term_id = " . $_GET['tax'];
	}

	return $where;
}

//---------------------------------------------------------------------------//

function sil_dictionary_custom_order_by($orderby) {
	global $wp_query, $wp_version, $wpdb;

	$orderby = "";
	if(  !empty($wp_query->query_vars['s'])) {
		$orderby = "sil_multilingual_search.relevance DESC, CHAR_LENGTH(sil_multilingual_search.search_strings) ASC, ";
	}

	$orderby .= " wp_posts.post_title ASC";

	return $orderby;
}

//---------------------------------------------------------------------------//

/**
 * Does the string have Chinese, Japanese, or Korean characters?
 * @param <string> $string = string to check
 * @return <boolean> = whether the string has Chinese/Japanese/Korean characters.
 */
function is_CJK( $string ) {
    $regex = '/' . implode( '|', get_CJK_unicode_ranges() ) . '/u';
    return preg_match( $regex, $string );
}

//---------------------------------------------------------------------------//

/**
 * A function that returns Chinese/Japanese/Korean (CJK) Unicode code points
 * Slightly adapted from an answer by "simon" found at:
 * @link http://stackoverflow.com/questions/5074161/what-is-the-most-efficient-way-to-whitelist-utf-8-characters-in-php
 * @return array
 */
function get_CJK_unicode_ranges() {
    return array(
		"[\x{2E80}-\x{2EFF}]",      # CJK Radicals Supplement
		"[\x{2F00}-\x{2FDF}]",      # Kangxi Radicals
		"[\x{2FF0}-\x{2FFF}]",      # Ideographic Description Characters
		"[\x{3000}-\x{303F}]",      # CJK Symbols and Punctuation
		"[\x{3040}-\x{309F}]",      # Hiragana
		"[\x{30A0}-\x{30FF}]",      # Katakana
		"[\x{3100}-\x{312F}]",      # Bopomofo
		"[\x{3130}-\x{318F}]",      # Hangul Compatibility Jamo
		"[\x{3190}-\x{319F}]",      # Kanbun
		"[\x{31A0}-\x{31BF}]",      # Bopomofo Extended
		"[\x{31F0}-\x{31FF}]",      # Katakana Phonetic Extensions
		"[\x{3200}-\x{32FF}]",      # Enclosed CJK Letters and Months
		"[\x{3300}-\x{33FF}]",      # CJK Compatibility
		"[\x{3400}-\x{4DBF}]",      # CJK Unified Ideographs Extension A
		"[\x{4DC0}-\x{4DFF}]",      # Yijing Hexagram Symbols
		"[\x{4E00}-\x{9FFF}]",      # CJK Unified Ideographs
		"[\x{A000}-\x{A48F}]",      # Yi Syllables
		"[\x{A490}-\x{A4CF}]",      # Yi Radicals
		"[\x{AC00}-\x{D7AF}]",      # Hangul Syllables
		"[\x{F900}-\x{FAFF}]",      # CJK Compatibility Ideographs
		"[\x{FE30}-\x{FE4F}]",      # CJK Compatibility Forms
		"[\x{1D300}-\x{1D35F}]",    # Tai Xuan Jing Symbols
		"[\x{20000}-\x{2A6DF}]",    # CJK Unified Ideographs Extension B
		"[\x{2F800}-\x{2FA1F}]"     # CJK Compatibility Ideographs Supplement
    );
}

//---------------------------------------------------------------------------//

// I'm not sure this is being used.

function no_standard_sort($k) {
	if(!empty($wp_query->query_vars['s'])) {
		$k->query_vars['orderby'] = 'none';
		$k->query_vars['order'] = 'none';
	}
}

?>