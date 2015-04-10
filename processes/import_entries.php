<?php
if(file_exists($argv[1] . "db-config.php"))
{
	define('WP_INSTALLING', true);
	//define('ABSPATH', $argv[1]);
	//define( 'WPINC', 'wp-includes');
	//require($argv[1] . "db-config.php");
	require($argv[1] . "wp-load.php");
	switch_to_blog(7);
	require($argv[1] . "wp-content/plugins/sil-dictionary-webonary/include/xhtml-importer.php");
	//require($argv[1] . "wp-includes/plugin.php");
	//require($argv[1] . "wp-includes/functions.php");
	//define('WPPREFIX', $argv[2]);
}
else
{
	define('WPPREFIX', $wpdb->prefix);
}

function dbConnection()
{
	$connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	
	return $connection;
}
function get_WP_option($option)
{
	$stmt = dbConnection()->prepare("SELECT option_value FROM " . WPPREFIX . "options WHERE option_name LIKE :optionname");

	$stmt->bindParam(':optionname', $option, PDO::PARAM_STR);
	
	$stmt->execute();
			
	return $stmt->fetchColumn();
}
function set_WP_option($optionname, $value)
{
	$stmt = dbConnection()->prepare("INSERT INTO " . WPPREFIX . "options (option_name, option_value) VALUES(:optionname, :optionvalue) ON DUPLICATE KEY UPDATE option_value=:optionvalue");

	$stmt->bindParam(':optionname', $optionname, PDO::PARAM_STR);
	$stmt->bindParam(':optionvalue', $value, PDO::PARAM_STR);
	
	$stmt->execute();
			
	return;
}
/*
class ImportEntries
{
	public $dbConnection;
	public $dom;
	public $dom_xpath;
	
	function convert_fieldworks_links_to_wordpress ($pinged = "-")
	{
		// link example:
		//		<a href="#hvo14216">

		$arrPosts = $this->get_posts($pinged);
		
		$entrycount = 0;
		foreach($arrPosts as $post)
		{
			$doc = new DomDocument();
			$doc->preserveWhiteSpace = false;
			$doc->loadXML($post['post_content']);

			$xpath = new DOMXPath($doc);

			$links = $xpath->query('//a//span[contains(@class,"crossref")]|//a//*[contains(@class,"HeadWordRef")]');

			$totalLinks = $links->length;
			if($totalLinks > 0)
			{
				foreach ( $links as $link ) {
					if(strtolower($link->getAttribute("class")) != "audiobutton" && strtolower($link->getAttribute("class")) != "image")
					{
						// Get the target hvo link to replace
						//$href = $link->getAttribute( "href" );
						$href = $link->parentNode->getAttribute( "href" );
						$hvo = substr($href, 4);

						// Now get the cross reference. Should only be one, but written to
						// handle more if they come along.

						//$cross_refs = $xpath->query( '//span[contains(@class,"crossref")]|.//*[contains(@class,"HeadWordRef")]', $link );

						$sensenumbers = $xpath->query('//span[@class="xsensenumber"]', $link);
						//$sensenumbers = $this->dom_xpath->query('//xhtml:span[@class="xsensenumber"]', $cross_ref);
						foreach($sensenumbers as $sensenumber)
						{
							$sensenumber->parentNode->removeChild($sensenumber);
						}
						// Get the WordPress post ID for the link.
						$flexid = str_replace("#", "", $href);
						$post_id = (string) $this->get_post_id( $flexid );

						if ( empty( $post_id ) && $pinged == "-")
						{
							//if pinged = "-" that could mean that the xhtml file was split and we import more later
							//in this case the user can click on the button at the end to convert to a search link later
							//in which case pinged will be either indexed or linksconverted
						}
						else if(substr($href, 0,3) == "?p=")
						{
							//link has already been converted
						}
						else
						{
							// Now replace the link to hvo wherever it appears with a link to
							// WordPress ID The update command should look like this:
							// UPDATE `nuosu`.`wp_posts` SET post_content =
							//	REPLACE(post_content, 'href="#hvo14216"', 'href="index.php?p=61151"');
							//if ( empty( $post_id ) )
								//$post_id = 'id-not-found';
							$sql = "UPDATE " . WPPREFIX . "posts SET post_content = ";
							$sql = $sql . "REPLACE(post_content, 'href=";
							$sql = $sql . '"' . $href . '"';
							$sql = $sql . "', 'href=";
							$sql = $sql . '"';
							if ( empty( $post_id ))
							{
								$sql = $sql . "?s=" . addslashes($link->textContent) . "&amp;partialsearch=1";
							}
							else
							{
								$sql = $sql . "?p=" . $post_id;
							}
							$sql = $sql . '"';
							$sql = $sql . "') " .
							" WHERE ID = :postid";
							
							$stmt = $this->dbConnection->prepare($sql);
			
							$stmt->bindParam(':postid', $post['ID'], PDO::PARAM_INT);
							
							$stmt->execute();
							
						}
					}
				} // foreach ( $links as $link )
			}
			$entrycount++;
			//##$this->import_xhtml_show_progress($entrycount, count($arrPosts), "", "Step 1 of 2: Please wait... converting FLEx links for Wordpress.");
			
		} //foreach $arrPosts as $post

		//set pinged = flexlinks for all posts
		
		$sql = "UPDATE " .  WPPREFIX . "posts
			   INNER JOIN " . WPPREFIX. "term_relationships ON object_id = ID
			   SET pinged = 'flexlinks'
			   WHERE " . WPPREFIX . "term_relationships.term_taxonomy_id = :categoryid
			   AND post_status = 'publish' AND pinged = ''";

		$stmt = $this->dbConnection->prepare($sql);
		
		$categoryid = $this->get_category_id();
		$stmt->bindParam(':categoryid', $categoryid, PDO::PARAM_INT);
		
		$stmt->execute();
		
	} // function convert_fieldworks_links_to_wordpress()
	
	function convert_homographs($entry, $classname)
	{
		$arrHomographs = $this->dom_xpath->query( './/xhtml:span[@class="' . $classname . '"]', $entry );
		foreach($arrHomographs as $homograph)
		{
			$numbers = array("1", "2", "3", "4", "5");
			$homographs = array("₁", "₂", "₃", "₄", "₅");

			$newHomograph = str_replace($numbers, $homographs, $homograph->textContent);

			$newNode = $this->dom->createElement('span', $newHomograph);
			$newNode->setAttribute('class', 'xhomographnumber');

			// fetch and replace the old element
			//$oldNode = $dom->getElementById('old_div');
			$parent = $homograph->parentNode;
			$parent->replaceChild($newNode, $homograph);
		}

		return $entry;
	}
	
	function convert_fieldworks_images_to_wordpress ($entry)
	{
		// image example (with link):
		//<a href="javascript:openImage('mouse.png')"><img src="wp-content/uploads/images/thumbnail/mouse.png" /></a>
	
		$images = $this->dom_xpath->query('//xhtml:img', $entry);
	
		foreach ( $images as $image ) {
	
			$src = $image->getAttribute( "src" );
			$upload_dir = wp_upload_dir();
			$replaced_src = str_ireplace("pictures/", $upload_dir['baseurl'] . "/images/thumbnail/", $src);
			$pic = str_ireplace("pictures/", "", $src);
	
			$newimage = $this->dom->createElement('img');
			$newimage->setAttribute("src", $replaced_src);
	
			$newelement = $this->dom->createElement('a');
			$newelement->appendChild($newimage);
			$newelement->setAttribute("class", "image");
			$newelement->setAttribute("href",  $upload_dir['baseurl'] . "/images/original/" . $pic);
			$parent = $image->parentNode;
			$parent->replaceChild($newelement, $image);
	
			//error_log("IMAGE: " . $replaced_src);
	
		} // foreach ( $images as $image )

		return $entry;
	} // function convert_fieldworks_images_to_wordpress()
		
	function getArrFieldQueries($step = 0)
	{
		//##if($_GET['step'] >= 2 || $step >= 2)
		if($step >= 2)
		{
			$querystart = "//span";
		}
		else
		{
			$querystart = ".//xhtml:span";
		}

		//$arrFieldQueries[0] = $querystart . '[@class="headword"]|//*[@class="headword_L2"]|//*[@class="headword-minor"]';
		$arrFieldQueries[0] = $querystart . '[@class="headword"]|./*[@class="headword_L2"]|./*[@class="headword-minor"]';
		$arrFieldQueries[1] = $querystart . '[@class = "headword-sub"]';
		$arrFieldQueries[2] = $querystart . '[contains(@class, "LexemeForm")]';
		//$arrFieldQueries[3] = $querystart . '[@class = "definition"]|//*[@class = "definition_L2"]|//*[@class = "definition-minor"]';
		$arrFieldQueries[3] = $querystart . '[starts-with(@class,"definition")]/span|' . $querystart . '[starts-with(@class,"LexSense")]';
		//$arrFieldQueries[4] = $querystart . '[@class = "definition-sub"]';
		$arrFieldQueries[4] = $querystart . '[starts-with(@class,"definition-sub")]';
		$arrFieldQueries[5] = $querystart . '[@class = "example"]';
		$arrFieldQueries[6] = $querystart . '[starts-with(@class,"translation")]';
		$arrFieldQueries[7] = $querystart . '[starts-with(@class,"LexEntry-") and not(contains(@class, "LexEntry-publishRoot-DefinitionPub_L2"))]/span';
		$arrFieldQueries[8] = $querystart . '[@class = "variantref-form"]';
		$arrFieldQueries[9] = $querystart . '[@class = "variantref-form-sub"]';
		$arrFieldQueries[10] = $querystart . '[@class = "sense-crossref"]';

		return $arrFieldQueries;
	}
	

	function get_category_id() {
	
		$stmt = dbConnection()->prepare("SELECT term_id FROM " . WPPREFIX . "terms WHERE name LIKE 'webonary'");
		
		$stmt->execute();
				
		return $stmt->fetchColumn();
	}
	
	function get_latest_xhtmlfile(){

		$sql = "SELECT ID, post_content AS url
			FROM " . WPPREFIX . "posts
			WHERE post_content LIKE '%.xhtml' AND post_type LIKE 'attachment'
			ORDER BY post_date DESC
			LIMIT 0,1";

		$stmt = $this->dbConnection->prepare($sql);
		
		$stmt->execute();
								
		$arrLastFile = $stmt->fetchAll();
		
		if(count($arrLastFile) > 0)
		{
			return $arrLastFile[0];
		}
		else
		{
			return null;
		}
	}
		
	function get_post_id( $flexid ){
		
		$postname = trim($flexid);
		
		$sql = "SELECT id
			FROM " . WPPREFIX . "posts
			WHERE post_name = :postname	collate utf8_bin AND post_status = 'publish'";

		$stmt = $this->dbConnection->prepare($sql);
				
		$stmt->bindParam(':postname', $postname, PDO::PARAM_STR);
		
		$stmt->execute();
		
		return $stmt->fetchColumn();
	}
	
	function get_posts($index = ""){

		$categoryid = $this->get_category_id();
		
		$sql = "SELECT ID, post_title, post_content, post_parent, menu_order
			FROM " . WPPREFIX . "posts
			INNER JOIN " . WPPREFIX . "term_relationships ON object_id = ID
			WHERE " . WPPREFIX . "term_relationships.term_taxonomy_id = :categoryid ";
		//using pinged field for not yet indexed
		$sql .= " AND post_status = 'publish'";
		if(strlen($index) > 0 && $index != "-")
		{
		 $sql .= " AND pinged = :pinged";
		}
		if($index == "-")
		{
		 $sql .= " AND pinged = ''";
		}
		$sql .= " ORDER BY menu_order ASC";

		$stmt = $this->dbConnection->prepare($sql);

		$stmt->bindParam(':categoryid', $categoryid, PDO::PARAM_INT);
		if(strlen($index) > 0 && $index != "-")
		{
		 $stmt->bindParam(':pinged', $index, PDO::PARAM_STR);
		}
		
		$stmt->execute();
								
		return $stmt->fetchAll();
	}
	
	function import_xhtml_entries ($dom, $dom_xpath) {
		
		$this->dom = $dom;
		$this->dom_xpath = $dom_xpath;
	
		//Loop through the entries so we can post them to WordPress.
	
		//the query looks for the spans with the headword and returns their parent <div class="entry">
		$entries = $this->dom_xpath->query('//xhtml:span[@class="headword"]/..|//xhtml:span[@class="headword_L2"]/..|//xhtml:span[@class="headword-minor"]/..|//xhtml:span[@class="headword-sub"]/..');
		$entries_count = $entries->length;
			
		$sql = "SELECT menu_order
			FROM " . WPPREFIX . "posts
			INNER JOIN " . WPPREFIX . "term_relationships ON object_id = ID
			ORDER BY menu_order DESC
			LIMIT 0,1";
	
		$stmt = $this->dbConnection->prepare($sql);

		$stmt->execute();
						
		$menu_order = $stmt->fetchColumn();
			
		if($menu_order == NULL)
		{
			$menu_order = 0;
		}
	
		set_WP_option("totalConfiguredEntries", $entries_count);
	
		if($entries->length == 0)
		{
			echo "<div style=color:red>ERROR: No headwords found.</div><br>";
			return;
		}
		$entry_counter = 1;
		foreach ( $entries as $entry ){
			// Find the headword. Should be only 1 headword at most. The
			// $headword->textContent picks up the value of both the headword and
			// the homograph number. This is presumably because the XML DOM
			// textContent property "returns the value of all text nodes
			// within the element node." The XHTML for an entry with homograph
			// number looks like this:
			// <span class="headword" lang="ii">my headword<span class="xhomographnumber">1</span></span>
			$entry = $this->convert_fieldworks_images_to_wordpress($entry);
			//## $entry = $this->convert_fieldworks_audio_to_wordpress($entry);
	
			$entry_xml = $this->dom->saveXML( $entry );
	
			$headwords = $this->dom_xpath->query( './xhtml:span[@class="headword"]|./xhtml:span[@class="headword_L2"]|./xhtml:span[@class="headword-minor"]|./*[@class="headword-sub"]', $entry );
	
			//$headword = $headwords->item( 0 )->nodeValue;
			foreach ( $headwords as $headword ) {
				$headword_language = $headword->getAttribute( "lang" );
	
				if($entry_counter == 1)
				{
					set_WP_option("languagecode", $headword_language);
					
				}
	
				$entry = $this->convert_homographs($entry, "xhomographnumber");
	
				$headword_text = trim($headword->textContent);
					
				$flexid = $entry->getAttribute("id");
	
				if(strlen(trim($flexid)) == 0)
				{
					$flexid = $headword_text;
				}
	
				$entry_xml = $this->dom->saveXML($entry, LIBXML_NOEMPTYTAG);
	
				$entry_xml = addslashes($entry_xml);
				$entry_xml = stripslashes($entry_xml);
				//$entry_xml = str_replace("'","&#39;",$entry_xml);
	
				$post_parent = 0;
				if (!preg_match("/class=\"entry\"/i", $entry_xml) && !preg_match("/class=\"headword-minor\"/i", $entry_xml))
				{
					$post_parent = 1;
					$entry_xml = str_replace("class=\"subentry\"","class=\"entry\"",$entry_xml);
					$entry_xml = str_replace("class=\"headword-sub\"","class=\"headword\"",$entry_xml);
				}
				
				//Insert the new entry into wp_posts
				
				$stmt = $this->dbConnection->prepare("SELECT ID FROM " . WPPREFIX . "posts WHERE post_title = :posttitle collate utf8_bin");

				$stmt->bindParam(':posttitle', $headword_text, PDO::PARAM_STR);
				
				$stmt->execute();
						
				$post_id = $stmt->fetchColumn();
				
				$post_id_exists = $post_id != NULL;
				
				if($post_id == NULL)
				{
					$stmt = $this->dbConnection->prepare("INSERT INTO ". WPPREFIX . "posts (post_date, post_title, post_content, post_status, post_parent, post_name, comment_status, menu_order)
					VALUES (NOW(), :headwordtext, :entryxml, 'publish', :postparent, :flexid, :commentstatus, :menuorder)");
	
					$stmt->bindParam(':headwordtext', $headword_text, PDO::PARAM_STR);
					$stmt->bindParam(':entryxml', $entry_xml, PDO::PARAM_STR);
					$stmt->bindParam(':postparent', $post_parent, PDO::PARAM_INT);
					$stmt->bindParam(':flexid', $flexid, PDO::PARAM_STR);
					$commentstatus = get_WP_option('default_comment_status');
					$stmt->bindParam(':commentstatus', $commentstatus, PDO::PARAM_STR);
					$stmt->bindParam(':menuorder', $menu_order, PDO::PARAM_INT);
					
					$stmt->execute();
	
					$post_id = $this->dbConnection->lastInsertId();
					
					if($post_id == 0)
					{
						//##$post_id = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_title = '" . addslashes(trim($headword_text)) . "'");
					}
	
					$this->setObjectTerms($post_id, "webonary", "category");
					//##wp_set_object_terms( $post_id, "webonary", 'category' );
				}
				else
				{
					$stmt = $this->dbConnection->prepare("UPDATE " . WPPREFIX . "posts SET post_date = NOW(), post_title = :headwordtext, post_content = :entryxml, post_status = 'publish', pinged='', post_parent=:postparent, post_name=:flexid, comment_status=:commentstatus WHERE ID = :postid");
	
					$stmt->bindParam(':headwordtext', $headword_text, PDO::PARAM_STR);
					$stmt->bindParam(':entryxml', $entry_xml, PDO::PARAM_STR);
					$stmt->bindParam(':postparent', $post_parent, PDO::PARAM_INT);
					$stmt->bindParam(':flexid', $flexid, PDO::PARAM_STR);
					$commentstatus = get_WP_option('default_comment_status');
					$stmt->bindParam(':commentstatus', $commentstatus, PDO::PARAM_STR);
					$stmt->bindParam(':postid', $post_id, PDO::PARAM_INT);
					
					$stmt->execute();
				}
				
				 // Show progresss to the user.
				//## $this->import_xhtml_show_progress( $entry_counter, $entries_count, $headword_text, "Step 1 of 2: Importing Post Entries" );
			} // foreach ( $headwords as $headword )
						
			$entry_counter++;
			$menu_order++;
		} // foreach ($entries as $entry){
		
		if($entries->length > 0)
		{
			$this->convert_fieldworks_links_to_wordpress();
			set_WP_option("importStatus", "indexing");
		}
	}
	
	function setObjectTerms($post_id, $term, $taxonomy)
	{
		$stmt = $this->dbConnection->prepare("SELECT tt.term_id, tt.term_taxonomy_id FROM " . WPPREFIX . "terms AS t INNER JOIN " . WPPREFIX . "term_taxonomy as tt ON tt.term_id = t.term_id WHERE t.slug = :term AND tt.taxonomy = :taxonomy ORDER BY t.term_id ASC LIMIT 1");
	
		$stmt->bindParam(':taxonomy', $taxonomy, PDO::PARAM_STR);
		$stmt->bindParam(':term', $term, PDO::PARAM_STR);
		
		$stmt->execute();
				
		$termid = $stmt->fetchColumn();
		
		$stmt = $this->dbConnection->prepare("INSERT INTO " . WPPREFIX . "term_relationships (object_id,term_taxonomy_id) VALUES (:object_id,:termid)");
		
		$stmt->bindParam(':object_id', $post_id, PDO::PARAM_INT);
		$stmt->bindParam(':termid', $termid, PDO::PARAM_INT);
		
		$stmt->execute();
		
		$stmt = $this->dbConnection->prepare("SELECT COUNT(*) AS termCount FROM " . WPPREFIX . "term_relationships, " . WPPREFIX . "posts WHERE " . WPPREFIX . "posts.ID = " . WPPREFIX . "term_relationships.object_id AND post_status = 'publish' AND post_type IN ('post') AND term_taxonomy_id = :termid");
	
		$stmt->bindParam(':termid', $termid, PDO::PARAM_INT);
				
		$stmt->execute();
				
		$termCount = $stmt->fetchColumn();
			
		$stmt = $this->dbConnection->prepare("UPDATE " . WPPREFIX . "term_taxonomy SET count = :termCount WHERE term_taxonomy_id = :termid");
		
		$stmt->bindParam(':termCount', $termCount, PDO::PARAM_INT);
		$stmt->bindParam(':termid', $termid, PDO::PARAM_INT);
		
		$stmt->execute();
	}
}
*/
//$import = new ImportEntries();
$import = new sil_pathway_xhtml_Import();

//$import->dbConnection = dbConnection();

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

set_WP_option("importStatus", $filetype);

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