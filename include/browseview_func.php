<?php
function categories_func( $atts )
{
	$display = "";
	
	$postsperpage = 25;
	
	$qTransLang = "en";
	
	if (function_exists('qtrans_init'))
	{
		if(qtrans_getLanguage() != "en")
		{
			$qTransLang = qtrans_getLanguage();
			if(!file_exists($_SERVER['DOCUMENT_ROOT'] . "/wp-content/plugins/sil-dictionary-webonary/js/categoryNodes_" . $qTransLang . ".js"))
			{
				$qTransLang = "en";
			}
		}
	}
?>
	<style>
	   TD {font-size: 9pt; font-family: arial,helvetica; text-decoration: none; font-weight: bold;}
	   a.categorylink {text-decoration: none; color: navy; font-size: 15px;}
	   #domRoot {
	   	float:left; width:250px; margin-left: 20px; margin-top: 5px;
	   }
	   #searchresults {
			width:70%;
			min-width: 270px;
			text-align:left;
			float: right;
			margin-top: 20px;
		}
	</style>
	<script src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/sil-dictionary-webonary/js/ua.js" type="text/javascript"></script>
	
	<!-- Infrastructure code for the tree -->
	<script src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/sil-dictionary-webonary/js/ftiens4.js" type="text/javascript"></script>

	<!-- Execution of the code that actually builds the specific tree.
     The variable foldersTree creates its structure with calls to gFld, insFld, and insDoc -->
	<script src="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/sil-dictionary-webonary/js/categoryNodes_<?php echo $qTransLang; ?>.js" type="text/javascript"></script>

	<!-- Build the browser's objects and display default view of the tree. -->
	<script language="JavaScript">
		initializeDocument();
	</script>
<?php
	$display .= "<div id=searchresults>";
	
	$semnumber = rtrim(str_replace(".", "-", $_REQUEST["semnumber"]), "-");
	$arrPosts = null;
	if(isset($_REQUEST["semnumber"]))
	{
		$arrPosts = query_posts("semdomain=" . $_REQUEST["semdomain"] . "&semnumber=" . $semnumber . "&posts_per_page=" . $postsperpage . "&paged=" . $_REQUEST['pagenr']);
	}
?>
<?php
	if(count($arrPosts) == 0)
	{
		if(strlen(trim($_REQUEST["semdomain"])) > 0)
		{
			$display .= __('No entries exist for', 'sil_dictionary') . ' "' . $_REQUEST["semdomain"] . '"';
		}
	}
	else
	{
		foreach($arrPosts as $mypost)
		{
				$display .= "<div class=post>" . $mypost->post_content . "</div>";
		}
	}
	
	global $wp_query;
	$totalEntries = $wp_query->found_posts;
	$display .= displayPagenumbers($semnumber, $totalEntries, $postsperpage,  $_REQUEST["semdomain"] , "semnumber", $_REQUEST['pagenr']);

	$display .= "</div>";
	
 	wp_reset_query();
	return $display;
	
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
	$display .= "<div style=\"text-align:center;\"><div style=\"display:inline-block;\">";
	foreach($alphas as $letter)
	{
    	$display .= "<div class=\"lpTitleLetterCell\"><span class=lpTitleLetter><a href=\"?letter=" . stripslashes($letter) . "&key=" . $languagecode . "\">" . stripslashes($letter) . "</a></span></div>";
	}
	$display .= "</div></div>";
	$display .=  "<div style=clear:both></div>";
	
	return $display;
	
}

function displayPagenumbers($chosenLetter, $totalEntries, $entriesPerPage, $languagecode, $requestname = null, $currentPage = null)
{
?>
	<link rel="stylesheet" href="<?php echo get_bloginfo('wpurl'); ?>/wp-content/plugins/wp-page-numbers/classic/wp-page-numbers.css" />
	
<?php
	if(!isset($requestname))
	{
		$requestname = "letter";
	}
	 
	if(!$currentPage)
	{
		$currentPage = $_GET['pagenr'];
		if(!isset($currentPage))
		{
			$currentPage = 1;
		}
	}
	$totalPages = round($totalEntries / $entriesPerPage, 0);
	if(($totalEntries / $entriesPerPage) > $totalPages)
	{
		$totalPages++;
	}
	if($totalPages > 1)
	{
		$display .= "<div style=\"text-align:center;\"><div style=\"display:inline-block;\">";
		$display .= "<div  id='wp_page_numbers'><ul>";
		$nextpage = "&gt;";
		$prevpage = "&lt;";
		$url = "?" . $requestname . "=" . $chosenLetter . "&key=" . $languagecode . "&totalEntries=" . $totalEntries;

		$limit_pages = 10;
		$display .= "<li class=page_info>" . gettext("Page") . " " . $currentPage . " " . gettext("of") . " " . $totalPages . "</li>";
		if( $totalPages > 1 && $currentPage > 1 )
		{
			if($requestname == "semnumber")
			{
				$display .= "<li><a href=\"#\" onclick=\"displayEntry('-', '" . $chosenLetter . "', " . ($currentPage - 1) . ");\">" . $prevpage . "</a></li> ";
			}
			else
			{
				$display .= "<li><a href=\"" . $url . "&pagenr=" . ($currentPage - 1) . "\">" .$prevpage . "</a></li>";
			}
		}

		$start = 1;
		if($currentPage > ($limit_pages - 5))
		{
			if($requestname == "semnumber")
			{
				$display .= "<li " . $class . "><a href=\"#\" onclick=\"displayEntry('-', '" . $chosenLetter . "', 1);\">" . 1 . "</a></li> ";
			}
			else
			{
				$display .= "<li><a href=\"" . $url . "&pagenr=" . 1 . "\">" . 1 . "</a></li> ";
			}
			$display .= "<li class=space>...</li>";
			$start = $currentPage - 5;
			if($currentPage == 6)
			{
				$start = 2;
			}
		}
		
		for($page = $start; $page <= $totalPages; $page++)
		{
			$class = "";
			if($currentPage == $page || ($page == 1 && !isset($currentPage)))
			{
				$class="class=active_page";
			}
			if($requestname == "semnumber")
			{
				$display .= "<li " . $class . "><a href=\"?semdomain=" . $languagecode . "&semnumber=" . $chosenLetter . "&pagenr=" . $page . "\">" . $page . "</a></li> ";
			}
			else
			{
				$display .= "<li " . $class . "><a href=\"" . $url . "&pagenr=" . $page . "\">" . $page . "</a></li> ";
			}
			$minusPages = 5;
			if($currentPage < 5)
			{
				$minusPages = $currentPage;
			}
			if(($currentPage + $limit_pages - $minusPages) == $page && ($currentPage + $limit_pages) < $totalPages)
			{
				$display .= "<li class=space>...</li>";
				$display .= "<li " . $class . "><a href=\"" . $url . "&pagenr=" . $totalPages . "\">" . $totalPages . "</a></li> ";
				break;
			}
		}
		if( $currentPage != "" && $currentPage < $totalPages)
		{
			if($requestname == "semnumber")
			{
				$display .= "<li><a href=\"#\" onclick=\"displayEntry('-', '" . $chosenLetter . "', " . ($currentPage + 1) . ");\">" . $nextpage . "</a></li> ";
			}
			else
			{
				$display .= "<li><a href=\"" . $url . "&pagenr=" . ($currentPage + 1) . "\">" .$nextpage . "</a></li>";
			}
		}
		$display .= "</ul></div>";
		$display .= "</div></div>";
	}
	return $display;
}

function englishalphabet_func( $atts, $content, $tag ) {
	
	if(strlen(trim(get_option('reversal1_alphabet'))) == 0)
	{
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
	}
	else
	{
		$display = reversalalphabet_func(null, "", "reversalindex1");
	}
		
 return $display;
}

add_shortcode( 'englishalphabet', 'englishalphabet_func');
 
function getReversalEntries($letter, $page, $reversalLangcode)
{
	global $wpdb;
	
	$sql = "SELECT a.search_strings AS English, b.search_strings AS Vernacular " .
	" FROM " . SEARCHTABLE . " a " .
	" INNER JOIN " . SEARCHTABLE. " b ON a.post_id = b.post_id AND a.subid = b.subid " .
	" AND a.language_code =  '" . $reversalLangcode . "' " .
	" AND b.language_code = '" . get_option('languagecode') . "' " .
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

add_shortcode( 'reversalindex1', 'reversalalphabet_func' );
add_shortcode( 'reversalindex2', 'reversalalphabet_func' );

function reversalalphabet_func($atts, $content, $tag)
{
	$reversalnr = 1;
	if($tag != "reversalindex1")
	{
		$reversalnr = 2;
	}
	
	$alphas = explode(",",  get_option('reversal'. $reversalnr . '_alphabet'));
	
	if(isset($_GET['letter']))
	{
		$chosenLetter = stripslashes($_GET['letter']);
	}
	else {
		$chosenLetter = stripslashes($alphas[0]);
	}
		
	$alphas = explode(",",  get_option('reversal' . $reversalnr . '_alphabet'));
	$display = displayAlphabet($alphas, get_option('reversal' . $reversalnr . '_langcode'));
	
	$display = reversalindex($display, $chosenLetter, get_option('reversal' . $reversalnr . '_langcode'));
		
	return $display;
}

add_shortcode( 'reversalindex2', 'reversalalphabet_func' );

function reversalindex($display, $chosenLetter, $langcode)
{
?>
	<style type="text/css">
	#searchresult {
		width:70%;
		min-width: 270px;
		text-align:left;
	}
	#englishcol {
		float:left;
		margin: 1px;
		padding-left: 2px;
		width:50%;
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
	
	$display .=  "<div align=center id=searchresults>";
	$display .= "<h1>" . $chosenLetter . "</h1><br>";

	$background = "even";
	$count = 0;
	foreach($arrAlphabet as $alphabet)
	{
		$display .=  "<div id=searchresult class=" . $background . " style=\"clear:both;\">";
			$display .=  "<div id=englishcol>";

			if($alphabet->English != $englishWord)
			{
				$display .=  $alphabet->English;
			}
			$englishWord = $alphabet->English;
			 $display .=  "</div>";
			$display .=  "<div id=vernacularcol><a href=\"?s=" . trim($alphabet->Vernacular)  . "&search=Search&tax=-1&partialsearch=1\">" . $alphabet->Vernacular . "</a></div>";
		$display .=  "</div>";
		//$display .=  "<div style=clear:both></div>";
		
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

	$display .=  "<div style=clear:both></div>";
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
	
	$alphas = explode(",",  get_option('vernacular_alphabet'));
	
	if(isset($_GET['letter']))
	{
		$chosenLetter = stripslashes($_GET['letter']);
	}
	else {
		$chosenLetter = stripslashes($alphas[0]);
	}
		
	
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
		$display .= __('No entries exist starting with this letter.', 'sil_dictionary');
	}
	$displaySubentriesAsMinorEntries = true;
	if(get_option('DisplaySubentriesAsMainEntries') == 'no')
	{
		$displaySubentriesAsMinorEntries = false;
	}
	if(get_option('DisplaySubentriesAsMainEntries') == 1)
	{
		$displaySubentriesAsMinorEntries = true;
	}
	
	
	foreach($arrPosts as $mypost)
	{
		if(trim($mypost->post_title) != trim($mypost->search_strings) && $displaySubentriesAsMinorEntries == true)
		{
			$headword = getVernacularHeadword($mypost->ID, $languagecode);
			$display .= "<div class=entry><span class=headword>" . $mypost->search_strings . "</span> ";
			$display .= "<span class=lpMiniHeading>See main entry:</span> <a href=\"/?s=" . $headword . "&partialsearch=1\">" . $headword . "</a></div>";
		}
		else if(trim($mypost->post_title) == trim($mypost->search_strings) )
		{
			$display .= "<div class=post>" . $mypost->post_content . "</div>";
			/*
			if( comments_open($mypost->ID) ) {
				$display .= "<a href=\"/" . $mypost->post_name. "\" rel=bookmark><u>Comments (" . get_comments_number($mypost->ID) . ")</u></a>";
			}
			*/
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