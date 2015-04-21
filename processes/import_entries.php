<?php
if(exec('echo EXEC') == 'EXEC' && file_exists($argv[1] . "exec-configured.txt"))
{
	define('WP_INSTALLING', true);
	require($argv[1] . "wp-load.php");
	switch_to_blog($argv[2]);
	
	require($argv[1] . "wp-content/plugins/sil-dictionary-webonary/include/infrastructure.php");
	install_sil_dictionary_infrastructure();
	
	require($argv[1] . "wp-content/plugins/sil-dictionary-webonary/include/xhtml-importer.php");
	
	//it isn't actually from the api, but saves us renaming the variable to "background" or something like that...
	$api = true;
	$verbose = true;
	$filetype = $argv[3];
	$xhtmlFileURL = $argv[4];
}
else
{
	$api = false;
	$verbose = false;
}
global $wpdb;

$import = new sil_pathway_xhtml_Import();

$import->api = $api;
$import->verbose = $verbose;

$xhtml_file = file_get_contents($xhtmlFileURL);

if($xhtml_file == null)
{
	echo "<div style=color:red>ERROR: XHTML file empty. Try uploading again.</div><br>";
	return;
}

update_option("importStatus", $filetype);

// Some of these variables could eventually become user options.
$dom = new DOMDocument('1.0', 'utf-8');
$dom->preserveWhiteSpace = false;
$dom->loadXML($xhtml_file);

$dom_xpath = new DOMXPath($dom);
$dom_xpath->registerNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

/*
 *
 * Load the Writing Systems (Languages)
 */
//if ( taxonomy_exists( $import->writing_system_taxonomy ) )
//{
	$import->import_xhtml_writing_systems($dom, $dom_xpath);
//}
/*
 * Import
 */

$import->search_table_name = $wpdb->prefix . 'sil_search';
	
global $current_user;
get_currentuserinfo();

if ( $filetype== 'configured') {
	//  Make sure we're not working on a reversal file.
	$reversals = $dom_xpath->query( '(//xhtml:span[contains(@class, "reversal-form")])[1]' );
	if ( $reversals->length > 0 )
		return;
	//inform the user about which fields are available
	$arrFieldQueries = $import->getArrFieldQueries();
	foreach($arrFieldQueries as $fieldQuery)
	{
		$fields = $dom_xpath->query($fieldQuery);
		if($fields->length == 0 && isset($_POST['chkShowDebug']))
		{
			echo "No entries found for the query " . $fieldQuery . "<br>";
		}
	}
	echo "Starting Import\n";
	
	$import->import_xhtml_entries($dom, $dom_xpath);
	
	$import->index_searchstrings();
	
	$import->convert_fields_to_links();

	$message = "The import of the vernacular (configured) xhtml export is completed.\n";
	$message .= "Go here to configure more settings: " . get_site_url() . "/wp-admin/admin.php?page=webonary";

	wp_mail( $current_user->user_email, 'Import complete', $message);
	
	echo "Import finished\n";
}
elseif ( $filetype == 'reversal')
{
	$import->reversal_table_name = $wpdb->prefix . 'sil_reversal';
	$import->import_xhtml_reversal_indexes($dom, $dom_xpath);
	
	$import->index_reversals();
	
	$message = "The reversal import is completed.\n";
	$message .= "Go here to configure more settings: " . get_site_url() . "/wp-admin/admin.php?page=webonary";
	wp_mail( $current_user->user_email, 'Reversal Import complete', $message);
}
elseif ( $filetype == 'stem')
{
	$import->import_xhtml_stem_indexes($dom, $dom_xpath);
}

$file = $import->get_latest_xhtmlfile();

if(substr($file->url, strlen($file->url) - 5, 5) == "xhtml")
{
	wp_delete_attachment( $file->ID );
}
else
{
	//file is inside extracted zip directory
	unlink($xhtmlFileURL);
}

?>