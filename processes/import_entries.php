<?php
if(exec('echo EXEC') == 'EXEC')
{
	define('WP_INSTALLING', true);
	require($argv[1] . "wp-load.php");
	switch_to_blog($argv[2]);
	require($argv[1] . "wp-content/plugins/sil-dictionary-webonary/include/xhtml-importer.php");
}

$import = new sil_pathway_xhtml_Import();

$file = $import->get_latest_xhtmlfile();
$xhtml_file = file_get_contents($file->url);

$filetype = "configured";

$api = false;
$verbose = false;

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
//## if ( taxonomy_exists( $this->writing_system_taxonomy ) )
	//## $this->import_xhtml_writing_systems();
/*
 * Import
 */

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
	$import->import_xhtml_entries($dom, $dom_xpath);
	
}
elseif ( $filetype == 'reversal')
	$this->import_xhtml_reversal_indexes();
elseif ( $filetype == 'stem')
	$this->import_xhtml_stem_indexes();
	
?>