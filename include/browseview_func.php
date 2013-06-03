<?php
function ajaxsearch()
{
	$languagecode = get_option('languagecode');
	
	$display = "";
	
	if(strlen($_REQUEST["semdomain"]) > 0)
	{
		$arrPosts = query_posts("semdomain=" . $_REQUEST["semdomain"] . "&showposts=100");
		$searchquery = $_REQUEST["semdomain"];				
	}

	if(count($arrPosts) == 0)
	{
		$display .= "No entries exist for '" . $searchquery . "'."; 
	}
	else
	{
		foreach($arrPosts as $mypost)
		{
			/* if($mypost->post_title != $mypost->search_strings)
			{
				$headword = getVernacularHeadword($mypost->ID, $languagecode);
				$display .= "<div class=entry><span class=headword>" . $mypost->search_strings . "</span> ";
				$display .= "<span class=lpMiniHeading>See main entry:</span> <a href=\?s=" . $headword . "\">" . $headword . "</a></div>";
			}
			else 
			{
			*/
				$display .= "<div class=post>" . $mypost->post_content . "</div>";
				if( comments_open($mypost->ID) ) {
					$display .= "<a href=\"" . $mypost->post_name. "\" rel=bookmark><u>Comments (" . get_comments_number($mypost->ID) . ")</u></a>"; 
				}			
			//}
		}
	}
		
	echo $display;
	die();
}

add_action( 'wp_ajax_getAjaxsearch', 'ajaxsearch' );
add_action( 'wp_ajax_nopriv_getAjaxsearch', 'ajaxsearch' );

function categories_func( $atts ) 
{
?>	
	<style>
	   TD {font-size: 9pt; font-family: arial,helvetica; text-decoration: none; font-weight: bold; white-space:nowrap; }
	   A  {text-decoration: none; color: navy; font-size: 15px;}
	</style>

	<script>
	function displayEntry(word)
	{
		jQuery.ajax({
     		url: '<?php echo admin_url('admin-ajax.php'); ?>',
     		data : {action: "getAjaxsearch", semdomain : word}, 		
     		type:'POST',
     		dataType: 'html',
     		success: function(output_string){ 
        		jQuery('#searchresult').html(output_string);
     		}
	 })
	}	
	</script>
	
	<script src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/sil-dictionary-webonary/js/ua.js" type="text/javascript"></script>
	
	<!-- Infrastructure code for the tree -->
	<script src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/sil-dictionary-webonary/js/ftiens4.js" type="text/javascript"></script>

	<!-- Execution of the code that actually builds the specific tree.
     The variable foldersTree creates its structure with calls to gFld, insFld, and insDoc -->
	<script src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/sil-dictionary-webonary/js/categoryNodes.js" type="text/javascript"></script>

	<!-- Build the browser's objects and display default view of the tree. -->
	<script language="JavaScript">initializeDocument()</script>
<?php 	
}
add_shortcode( 'categories', 'categories_func' );


function displayAlphabet($alphas, $languagecode)
{
?>
	<style type="text/css">
	.lpTitleLetterCell {min-width:31px; height: 23x; padding-top: 3px; padding-bottom: 2px; text-bottom; text-align:center;background-color: #EEEEEE;cursor:pointer;cursor:hand;border:1px solid silver; float:left; position: relative;}
	</style>	
<?php 
	$display = "<br>"; 
	$display .= "<div style=\"text-align:center;\"><div class=buttons style=\"display:inline-block;\">";
	foreach($alphas as $letter)
	{
    	$display .= "<div class=\"lpTitleLetterCell\"><span class=lpTitleLetter><a href=\"?letter=" . stripslashes($letter) . "&key=" . $languagecode . "\">" . stripslashes($letter) . "</a></span></div>";
	}
	$display .= "</div></div>";
	$display .=  "<div style=clear:both></div>";
	
	return $display;
	
}

function displayPagenumbers($chosenLetter, $totalEntries, $entriesPerPage, $languagecode)
{
	$totalPages = round($totalEntries / $entriesPerPage, 0);
	if(($totalEntries / $entriesPerPage) > $totalPages)
	{
		$totalPages++;
	}
	if($totalPages > 1)
	{
		for($page = 1; $page <= $totalPages; $page++)
		{
			if($_GET['pagenr'] == $page || ($page == 1 && !isset($_GET['pagenr'])))
			{
				$display .= $page . " ";
			}
			else
			{
				$display .= "<a href=\"?letter=" . $chosenLetter . "&key=" . $languagecode . "&pagenr=" . $page . "&totalEntries=" . $totalEntries . "\">" . $page . "</a> ";
			}		 
		}
	}		
	return $display;
}

function englishalphabet_func( $atts ) {
	
	$languagecode = "en";
	
	if(isset($_GET['letter']))
	{
		$chosenLetter = $_GET['letter']; 
	}
	else {
		$chosenLetter = "a"; 
	}
	
	$alphas = range('a', 'z');
	$display = displayAlphabet($alphas, $languagecode);
	
	$display = reversalindex($display, $chosenLetter, $languagecode);
		
 return $display;
}

add_shortcode( 'englishalphabet', 'englishalphabet_func' );
 
function getReversalEntries($letter, $page, $langcode)
{
	global $wpdb;
	
	$sql = "SELECT a.search_strings AS English, b.search_strings AS Vernacular " .
	" FROM " . SEARCHTABLE . " a " .
	" INNER JOIN " . SEARCHTABLE. " b ON a.post_id = b.post_id AND a.subid = b.subid " .
	" AND a.language_code =  '" . $langcode . "' " .
	" AND a.relevance >=95 " .
	" AND a.search_strings LIKE  '" . $letter . "%' " .
	" GROUP BY a.post_id, a.search_strings " .
	" ORDER BY a.search_strings ";
	if($page > 1)
	{
		$startFrom = ($page - 1) * 50;
		$sql .= " LIMIT " . $startFrom .", 50";
	}
	
	$arrAlphabet = $wpdb->get_results($sql);
	
	return $arrAlphabet;
}

add_shortcode( 'reversalindex2', 'reversalalphabet_func' );

function reversalalphabet_func($atts)
{
	if(isset($_GET['letter']))
	{
		$chosenLetter = stripslashes($_GET['letter']); 
	}
	else {
		$chosenLetter = "a"; 
	}
		
	$alphas = explode(",",  get_option('reversal2_alphabet'));
	$display = displayAlphabet($alphas, get_option('reversal2_langcode'));
	
	$display = reversalindex($display, $chosenLetter, get_option('reversal2_langcode'));
		
	return $display;
} 

add_shortcode( 'reversalindex2', 'reversalalphabet_func' );

function reversalindex($display, $chosenLetter, $langcode)
{
?>
	<style type="text/css">
	#searchresult { 
		width:50%; 
		min-width: 270px;
		text-align:left; 
	}
	#englishcol { 
		float:left; 
		margin: 1px; 
		padding-left: 2px;
		width:60%; 
		text-align:left; 
	}
	#vernacularcol {
		text-align:left;
	}
	.odd { background: #CCCCCC; }; 
	.even { background: #FFF; }; 		
	</style>		
<?php
	$page = $_GET['pagenr'];
	if(!isset($_GET['pagenr']))
	{
		$page = 1;
	}
	$arrAlphabet = getReversalEntries($chosenLetter, $page, $langcode);
	
	$display .=  "<div align=center>";
	$display .= "<h1>" . $chosenLetter . "</h1><br>";

	$background = "even";
	$count = 0;
	foreach($arrAlphabet as $alphabet)
	{
		$display .=  "<div id=searchresult class=" . $background . ">";	
			$display .=  "<div id=englishcol>";

			if($alphabet->English != $englishWord) 
			{
				$display .=  $alphabet->English; 
			}
			$englishWord = $alphabet->English;
			 $display .=  "</div>";
			$display .=  "<div id=vernacularcol><a href=\"/?s=" . trim($alphabet->Vernacular)  . "&search=Search&tax=-1&partialsearch=1\">" . $alphabet->Vernacular . "</a></div>";
		$display .=  "</div>";
		$display .=  "<div style=clear:both></div>";
		
		if($background == "even")
		{
			$background = "odd";
		}
		else 
		{
			$background = "even";
		}
		
    	$count++;
    	if($count == 50)
    	{
    		break;
    	}
	}
	
	if(!isset($_GET['totalEntries']))
	{
		$totalEntries = count($arrAlphabet);
	}
	else
	{
		$totalEntries = $_GET['totalEntries'];
	}

	$display .= displayPagenumbers($chosenLetter, $totalEntries, 50, $languagecode);

	$display .=  "</div><br>";

	return $display;
}

function getVernacularHeadword($postid, $languagecode)
{
	global $wpdb;
	
	$sql = "SELECT search_strings " .
	" FROM " . SEARCHTABLE . 
	" WHERE post_id = " . $postid . " AND relevance = 100 AND language_code = '" . $languagecode . "'";

	return $wpdb->get_var($sql);
	
}

function vernacularalphabet_func( $atts ) 
{
	$languagecode = get_option('languagecode');
	
	if(isset($_GET['letter']))
	{
		$chosenLetter = stripslashes($_GET['letter']); 
	}
	else {
		$chosenLetter = "a"; 
	}
		
	$alphas = explode(",",  get_option('vernacular_alphabet'));
	$display = displayAlphabet($alphas, $languagecode);
	$display .= "<div align=center><h1>" . $chosenLetter . "</h1></div><br>";

	if(empty($languagecode))
	{
		$display .=  "No language code provided. Please set in the Webonary settings.";
		return $display;
	}
	
	//if for example somebody searches for "k", but there is also a letter 'kp' in the alphabet then
	//words starting with kp should not appear 
	$noLetters = "";
	foreach($alphas as $alpha)
	{
		//$alpha = stripslashes($alpha);
		if(preg_match("/" . $chosenLetter . "/i", $alpha) && $chosenLetter != stripslashes($alpha))
		{
			if(strlen($noLetters) > 0)
			{
				$noLetters .= ",";
			}
			$noLetters .= $alpha;
		}
	}

	
	$display .= "<div id=searchresults>";
    
	$arrPosts = query_posts("s=a&letter=" . $chosenLetter . "&noletters=" . $noLetters . "&langcode=" . $languagecode . "&posts_per_page=25&paged=" . $_GET['pagenr']);
	
	if(count($arrPosts) == 0)
	{
		$display .= "No entries exist starting with this letter."; 
	}
	foreach($arrPosts as $mypost)
	{
		if(trim($mypost->post_title) != trim($mypost->search_strings))
		{
			$headword = getVernacularHeadword($mypost->ID, $languagecode);
			$display .= "<div class=entry><span class=headword>" . $mypost->search_strings . "</span> ";
			$display .= "<span class=lpMiniHeading>See main entry:</span> <a href=\"/?s=" . $headword . "&partialsearch=1\">" . $headword . "</a></div>";
		}
		else 
		{
			$display .= "<div class=post>" . $mypost->post_content . "</div>";
			if( comments_open($mypost->ID) ) {
				$display .= "<a href=\"" . $mypost->post_name. "\" rel=bookmark><u>Comments (" . get_comments_number($mypost->ID) . ")</u></a>"; 
			}			
		}
	}
	
	$display .= "</div>";

	if(!isset($_GET['totalEntries']))
	{
		global $wp_query;		
		$totalEntries = $wp_query->found_posts;
	}
	else
	{
		$totalEntries = $_GET['totalEntries'];
	}
		
	$display .= "<div align=center><br>";
	$display .= displayPagenumbers($chosenLetter, $totalEntries, 25, $languagecode);
	$display .= "</div><br>";
	
 	wp_reset_query();
	return $display;
}

add_shortcode( 'vernacularalphabet', 'vernacularalphabet_func' );
?>