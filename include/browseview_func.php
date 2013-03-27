<?php

function englishalphabet_func( $atts ) {
	if(isset($_GET['letter']))
	{
		$chosenLetter = $_GET['letter']; 
	}
	else {
		$chosenLetter = "a"; 
	}
	
	?>
	<style type="text/css">
	#searchresult { 
		width:50%; 
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
	table.lpTitleTable {border-collapse:collapse;border:1px solid silver;}
	td.lpTitleLetterCell {max-width:2px;text-align:center;background-color: #EEEEEE;cursor:pointer;cursor:hand;border:1px solid silver;}
	.odd { background: #CCCCCC; }; 
	.even { background: #FFF; }; 		
	</style>	
	<?php 		
	$alphas = range('a', 'z');
	$display = "<table class=lpTitleTable width=100%>";
	$display .= "<tr height=20>";
	foreach($alphas as $letter)
	{
    	$display .= "<td class=\"lpTitleLetterCell\"><span class=lpTitleLetter><a href=\"?letter=" . $letter . "\">" . $letter . "</span></td>";
	}
	$display .= "</tr>";
	$display .= "</table>";
	

	$page = $_GET['pagenr'];
	if(!isset($_GET['pagenr']))
	{
		$page = 1;
	}
	$arrAlphabet = getEnglishAlphabet($chosenLetter, $page);
	
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
	$totalPages = $totalEntries / 50;
	for($page = 1; $page <= $totalPages; $page++)
	{
		if($_GET['pagenr'] == $page || ($page == 1 && !isset($_GET['pagenr'])))
		{
			$display .= $page . " ";
		}
		else
		{
			$display .= "<a href=\"?letter=" . $chosenLetter . "&pagenr=" . $page . "&totalEntries=" . $totalEntries . "\">" . $page . "</a> ";
		}		 
	}

	$display .=  "</div>";
	
 return $display;
}

add_shortcode( 'englishalphabet', 'englishalphabet_func' );
 
function getEnglishAlphabet($letter, $page)
{
	global $wpdb;
	
	$sql = "SELECT a.search_strings AS English, b.search_strings AS Vernacular " .
	" FROM wp_2_sil_search a " .
	" INNER JOIN wp_2_sil_search b ON a.post_id = b.post_id AND a.subid = b.subid " .
	" AND a.language_code =  'en' " .
	" AND a.relevance >=95 " .
	" AND a.search_strings LIKE  '" . $letter . "%' " .
	" GROUP BY a.post_id " .
	" ORDER BY a.search_strings ";
	if($page > 1)
	{
		$startFrom = $page * 50;
		$sql .= " LIMIT " . $startFrom .", 50";
	}
	$arrAlphabet = $wpdb->get_results($sql);
	
	return $arrAlphabet;
}
?>