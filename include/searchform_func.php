<?php
/**
 * A replacement for search box for dictionaries. To use, create searchform.php
 * in the theme, and make a call to this function, like so:
 */
function searchform_init() {
	/*
	 * Load the translated strings for the plugin.
	 */
    load_plugin_textdomain('sil_dictionary', false, dirname(plugin_basename(__FILE__ )).'/lang/');
}

function webonary_searchform() {
?>
		 <form name="searchform" id="searchform" method="get" action="<?php bloginfo('url'); ?>">
			<div class="normalSearch">
				<!-- search text box -->
				<input type="text" name="s" id="s" value="<?php the_search_query(); ?>" size=40>
	
				<!-- I'm not sure why qtrans_getLanguage() is here. It doesn't seem to do anything. -->
				<?php if (function_exists('qtrans_getLanguage')) {?>
					<input type="hidden" id="lang" name="lang" value="<?php echo qtrans_getLanguage(); ?>"/>
				<?php }?>
	
				<!-- search button -->
				<input type="submit" id="searchsubmit" name="search" value="<?php _e('Search', 'sil_dictionary'); ?>" />
				<br>
				<?php
				$key = $_POST['key'];
				if(!isset($_POST['key']))
				{
					$key = $_GET['key'];
				}
	
				$catalog_terms = get_terms('sil_writing_systems');
	
				/*
				 * Set up language options. The first option is for all
				 * languages. Then the list is retrieved.
				 */
				if ($catalog_terms) {
					?>
					<!-- If you need to control the width of the dropdown, use the
					class webonary_searchform_language_select in your theme .css -->
					<select name="key" class="webonary_searchform_language_select">
					<option value="">
						<?php _e('All Languages','sil_dictionary'); ?>
					</option>
					<?php
					foreach ($catalog_terms as $catalog_term)
					{ ?>
						<option value="<?php echo $catalog_term->slug; ?>"
							<?php if($key == $catalog_term->slug) {?>selected<?php }?>>
							<?php echo $catalog_term->name; ?>
						</option>
						<?php
					}
					?>
					</select>
					<?php
				}
	
				/*
				 * Set up the Parts of Speech
				 */
				wp_dropdown_categories("show_option_none=" .
					__('All Parts of Speech','sil_dictionary') .
					"&show_count=1&selected=" . $_GET['tax'] .
					"&orderby=name&echo=1&name=tax&taxonomy=sil_parts_of_speech");
						
				?>
			</div>
		</form>
<?php
}

add_action('init', 'searchform_init');
?>