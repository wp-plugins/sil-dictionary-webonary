<?php
function englishalphabet_func( $atts ){
	$alphas = range('a', 'z');
	$alphabet = "<table class=lpTitleTable width=100%>";
	$alphabet .= "<tr height=20>";
	foreach($alphas as $letter)
	{
    	$alphabet .= "<td class=\"lpTitleLetterCell\"><span class=lpTitleLetter>" . $letter . "</span></td>";
	}
	$alphabet .= "</tr>";
	$alphabet .= "</table>";
	
 return $alphabet;
}
add_shortcode( 'englishalphabet', 'englishalphabet_func' );
 
?>