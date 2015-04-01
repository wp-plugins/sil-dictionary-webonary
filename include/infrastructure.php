<?php
/**
 * Infrastructure
 *
 * Infrastructure for SIL Dictionaries. Includes custom tables and custom taxonomies.
 *
 * PHP version 5.2
 *
 * LICENSE GPL v2
 *
 * @package WordPress
 * @since 3.1
 */

// don't load directly
if ( ! defined('ABSPATH') )
	die( '-1' );

//---------------------------------------------------------------------------//

/**
* Set up the SIL Dictionary in WordPress Dashboard Tools
 */
function add_admin_menu() {
	add_menu_page( "Webonary", "Webonary", true, "webonary", "sil_dictionary_main",  get_bloginfo('wpurl') . "/wp-content/plugins/sil-dictionary-webonary/images/webonary-icon.png", 76 );
}

function ajaxlanguage()
{
	global $wpdb;

	$sql = "SELECT name
		FROM $wpdb->terms
		WHERE slug = '" . $_POST['languagecode'] . "'";

	$languagename = $wpdb->get_var( $sql);


	echo $languagename;
	die();
}

add_action( 'wp_ajax_getAjaxlanguage', 'ajaxlanguage' );
add_action( 'wp_ajax_nopriv_getAjaxlanguage', 'ajaxlanguage' );

//get category id for "webonary", if it doesn't exist, create it.
function get_category_id() {
	global $wpdb;

	$sql = "SELECT term_id
		FROM $wpdb->terms
		WHERE name LIKE 'webonary'";

	$catid = $wpdb->get_var( $sql);

	return $catid;
}

function get_LanguageCodes($languageCode = null) {
	global $wpdb;

	$sql = "SELECT language_code, name
		FROM " . $wpdb->prefix . "sil_search
		LEFT JOIN " . $wpdb->terms . " ON " . $wpdb->terms . ".slug = " . $wpdb->prefix . "sil_search.language_code";
		if(isset($languageCode))
		{
			$sql .= " WHERE language_code = '" . $languageCode . "' ";
		}
		$sql .= " GROUP BY language_code
		ORDER BY language_code";

	return $wpdb->get_results($sql);;
}

//---------------------------------------------------------------------------//

function sil_dictionary_main() {
	run_user_action();
	user_input();
}

//---------------------------------------------------------------------------//

/**
 * User input for the plugin.
 */
function user_input() {

	$arrLanguageCodes = get_LanguageCodes();

	// enctype="multipart/form-data"
	?>
	<script>
	function getLanguageName(selectbox, langname)
	{
		var e = document.getElementById(selectbox);
		var langcode = e.options[e.selectedIndex].value;

		jQuery.ajax({
     		url: '<?php echo admin_url('admin-ajax.php'); ?>',
     		data : {action: "getAjaxlanguage", languagecode : langcode},
     		type:'POST',
     		dataType: 'html',
     		success: function(output_string){
        		jQuery('#' + langname).val(output_string);
     		}
	 })
	}
	</script>

	<div class="wrap">
		<div id="icon-tools" class="icon32"></div>
		<form method="post" action="">
			<h2><?php _e( 'Webonary', 'webonary' ); ?></h2>
			<?php
			/*
			 * Standard UI
			 */
			if ( empty( $_POST['delete_data'] ) ) {
				?>
				<?php _e('Webonary provides the admininstration tools and framework for using WordPress for dictionaries.<br>See <a href="http://www.webonary.org/help" target="_blank">Webonary Support</a> for help.', 'sil_dictionary'); ?>
				<h3><?php _e( 'Import Data', 'sil_dictionary' ); ?></h3>
				<p><?php _e('You can find the <a href="admin.php?import=pathway-xhtml">SIL FLEX XHTML importer</a> by clicking on Import under the Tools menu.', 'sil_dictionary'); ?></p>
				<p><?php _e('Each dictionary entry is stored in a "post." You will find the entries in the Posts menu.', 'sil_dictionary'); ?></p>

				<h3><?php _e( 'Delete Data', 'sil_dictionary' ); ?></h3>
				<p><?php _e('Lists are kept unless you check the following:'); ?><br>
					<label for="delete_taxonomies">
						<input name="delete_taxonomies" type="checkbox" id="delete_taxonomies" value="1"
							<?php checked('1', get_option('delete_taxonomies')); ?> />
						<?php _e('Delete lists such as Part of Speech?') ?>
						<?php /*
						<input name="delete_allposts" type="checkbox" id="delete_allposts" value="1"
							<?php checked('1', get_option('delete_allposts')); ?> />
						<?php _e('Delete all posts, including the ones not in category "webonary" (legacy function)') */ ?>
					</label>
					<label for="delete_pages">
						<!--<input name="delete_pages" type="checkbox" id="delete_pages" value="1"
							<?php checked('1', get_option('delete_pages')); ?> />-->
					</label><br />
					<?php _e('Are you sure you want to delete the dictionary data?', 'sil_dictionary'); ?>
					<input type="submit" name="delete_data" value="<?php _e('Delete', 'sil_dictionary'); ?>">
					<br>
					<?php _e('(deletes all posts in the category "webonary")', 'sil_dictionary'); ?>
				</p>
				<h3><?php _e('Settings');?></h3>
				<p>
				<?php _e('Publication status:'); ?>
				<select name=publicationStatus>
					<option value=0><?php _e('no status set'); ?></option>
					<option value=1 <?php selected(get_option('publicationStatus'), 1); ?>><?php _e('Rough draft'); ?></option>
					<option value=2 <?php selected(get_option('publicationStatus'), 2); ?>><?php _e('Self-reviewed draft'); ?></option>
					<option value=3 <?php selected(get_option('publicationStatus'), 3); ?>><?php _e('Community-reviewed draft'); ?></option>
					<option value=4 <?php selected(get_option('publicationStatus'), 4); ?>><?php _e('Consultant approved'); ?></option>
					<option value=5 <?php selected(get_option('publicationStatus'), 5); ?>><?php _e('Finished (no formal publication)'); ?></option>
					<option value=6 <?php selected(get_option('publicationStatus'), 6); ?>><?php _e('Formally published'); ?></option>
				</select>
				<p>
				<b><?php _e('Search Options:'); ?></b>
				<p>
				<input name="include_partial_words" type="checkbox" value="1"
							<?php checked('1', get_option('include_partial_words')); ?> />
							<?php _e('Always include searching through partial words.'); ?>
				<p>
				<h3>Browse Views</h3>
				<p>
				<?php
				$DisplaySubentriesAsMainEntries = get_option('DisplaySubentriesAsMainEntries');
				if($DisplaySubentriesAsMainEntries != "no")
				{
					$DisplaySubentriesAsMainEntries = 1;
				}
				?>
				<input name="DisplaySubentriesAsMainEntries" type="checkbox" value="1"
							<?php checked('1', $DisplaySubentriesAsMainEntries); ?> />
							<?php _e('Display subentries as main entries'); ?>
				<p>
				<?php
				if(count($arrLanguageCodes) == 0)
				{
				?>
					<span style="color:red">You need to first import your xhtml file before you can select a language code.</span>
					<p>
				<?php
				}
				_e('Vernacular Language Code:'); ?>
				<select id=vernacularLanguagecode name="languagecode" onchange="getLanguageName('vernacularLanguagecode', 'vernacularName');">
					<option value=""></option>
					<?php
					$x = 0;
					foreach($arrLanguageCodes as $languagecode) {?>
						<option value="<?php echo $languagecode->language_code; ?>" <?php if(get_option('languagecode') == $languagecode->language_code) { $i = $x; ?>selected<?php }?>><?php echo $languagecode->language_code; ?></option>
					<?php
					$x++;
					} ?>
				</select>
				<?php _e('Language Name:'); ?> <input  id=vernacularName type="text" name="txtVernacularName" value="<?php if(count($arrLanguageCodes) > 0) { echo $arrLanguageCodes[$i]->name; } ?>">
				<p>
				<?php _e('Vernacular Alphabet:'); ?>
				<input name="vernacular_alphabet" type="text" size=50 value="<?php echo stripslashes(get_option('vernacular_alphabet')); ?>" />
				<?php _e('(Letters separated by comma)'); ?>
				<p>
				<b><?php _e('Reversal Indexes:'); ?></b>
				<p>
				<?php _e('Main reversal index code:'); ?>
				<select id=reversalLangcode name="reversal1_langcode" onchange="getLanguageName('reversalLangcode', 'reversalName');">
					<option value=""></option>
					<?php
						$x = 0;
						foreach($arrLanguageCodes as $languagecode) {?>
						<option value="<?php echo $languagecode->language_code; ?>" <?php if(get_option('reversal1_langcode') == $languagecode->language_code) { $k = $x; ?>selected<?php }?>><?php echo $languagecode->language_code; ?></option>
					<?php
						$x++;
						} ?>
				</select>
				<?php _e('Language Name:'); ?> <input id=reversalName type="text" name="txtReversalName" value="<?php if(count($arrLanguageCodes) > 0) { echo $arrLanguageCodes[$k]->name; } ?>">
				<p>
				<?php
				if(strlen(trim(stripslashes(get_option('reversal1_alphabet')))) == 0)
				{
					$reversal1alphabet = "";
					$alphas = range('a', 'z');
					$i = 1;
					foreach($alphas as $letter)
					{
						$reversal1alphabet .= $letter;
						if($i != count($alphas))
						{
							$reversal1alphabet .= ",";
						}
						$i++;
					}
				}
				else
				{
					$reversal1alphabet = stripslashes(get_option('reversal1_alphabet'));
				}
				?>
				<?php _e('Main Reversal Index Alphabet:'); ?>
				<input name="reversal1_alphabet" type="text" size=50 value="<?php echo $reversal1alphabet; ?>" />
				<?php _e('(Letters separated by comma)'); ?>
				<hr>
				 <i><?php _e('If you have a second reversal index, enter the information here:'); ?></i>
				 <p>
				<?php _e('Secondary reversal index code:'); ?>
				<select id=reversal2Langcode name="reversal2_langcode" onchange="getLanguageName('reversal2Langcode', 'reversal2Name');">
					<option value=""></option>
					<?php
					$x = 0;
					foreach($arrLanguageCodes as $languagecode) {?>
						<option value="<?php echo $languagecode->language_code; ?>" <?php if(get_option('reversal2_langcode') == $languagecode->language_code) { $n = $x; ?>selected<?php }?>><?php echo $languagecode->language_code; ?></option>
					<?php
					$x++;
					} ?>
				</select>
				<?php _e('Language Name:'); ?> <input id=reversal2Name type="text" name="txtReversal2Name" value="<?php if(count($arrLanguageCodes) > 0) { echo $arrLanguageCodes[$n]->name; } ?>">
				<p>
				<?php _e('Secondary Reversal Index Alphabet:'); ?>
				<input name="reversal2_alphabet" type="text" size=50 value="<?php echo stripslashes(get_option('reversal2_alphabet')); ?>" />
				<?php _e('(Letters separated by comma)'); ?>
				<p>
				<input type="submit" name="save_settings" value="<?php _e('Save', 'sil_dictionary'); ?>">
				</p>
				<?php
				/*
				?>
				<h3><?php _e('Comments');?></h3>
				If you have the comments turned on, you need to re-sync your comments after re-importing of your posts.
				<p>
				<a href="admin.php?import=comments-resync">Re-sync comments</a>
				<?php
				*/
				}

			/*
			 * Delete finished
			 */
			else {
				?>
				<p>
					<?php _e('Finished!', 'sil_dictionary'); ?>
					<input type="submit" name="finished_deleting" value="<?php _e('OK', 'sil_dictionary'); ?>">
				</p>
				<?php
			}

		?>
		</form>
	</div>
	<?php
}

//---------------------------------------------------------------------------//

/**
 * Do what the user said to do.
 */
function run_user_action() {
	global $wpdb;

    if ( ! empty( $_POST['delete_data'])) {
        clean_out_dictionary_data();
    }
    if ( ! empty( $_POST['save_settings'])) {
    	update_option("publicationStatus", $_POST['publicationStatus']);
    	update_option("include_partial_words", $_POST['include_partial_words']);
    	$displaySubentriesAsMainEntries = 'no';
    	if(isset($_POST['DisplaySubentriesAsMainEntries']))
    	{
    		$displaySubentriesAsMainEntries = 1;
    	}
    	update_option("DisplaySubentriesAsMainEntries", $displaySubentriesAsMainEntries);
    	update_option("languagecode", $_POST['languagecode']);
    	update_option("vernacular_alphabet", $_POST['vernacular_alphabet']);
    	update_option("reversal1_langcode", $_POST['reversal1_langcode']);
    	update_option("reversal1_alphabet", $_POST['reversal1_alphabet']);
    	update_option("reversal2_alphabet", $_POST['reversal2_alphabet']);
    	update_option("reversal2_langcode", $_POST['reversal2_langcode']);

    	if(trim(strlen($_POST['txtVernacularName'])) == 0)
    	{
    		echo "<br><span style=\"color:red\">Please fill out the textfields for the language names, as they will appear in a dropdown below the searcbhox.</span><br>";
    	}

    	$arrLanguages[0]['name'] = "txtVernacularName";
    	$arrLanguages[0]['code'] = "languagecode";
    	$arrLanguages[1]['name'] = "txtReversalName";
    	$arrLanguages[1]['code'] = "reversal_langcode";
    	$arrLanguages[2]['name'] = "txtReversal2Name";
    	$arrLanguages[2]['code'] = "reversal2_langcode";

    	foreach($arrLanguages as $language)
    	{
    		if(strlen(trim($_POST[$language['code']])) != 0)
    		{
		    	$sql = "INSERT INTO  $wpdb->terms (name,slug) VALUES ('" . $_POST[$language['name']] . "','" . $_POST[$language['code']] . "')
		  		ON DUPLICATE KEY UPDATE name = '" . $_POST[$language['name']]  . "'";

		    	$wpdb->query( $sql );

		    	$lastid = $wpdb->insert_id;

		    	if($lastid != 0)
		    	{
			    	$sql = "INSERT INTO  $wpdb->term_taxonomy (term_id, taxonomy,description,count) VALUES (" . $lastid . ", 'sil_writing_systems', '" . $_POST[$language['name']] . "',999999)
			  		ON DUPLICATE KEY UPDATE description = '" . $_POST[$language['name']]  . "'";

		    		$wpdb->query( $sql );
		    	}
    		}
    	}

    	echo "<br>" . _e('Settings saved');
    }
}

//---------------------------------------------------------------------------//

/**
 * Install the SIL dictionary infrastructure if needed.
 */
function install_sil_dictionary_infrastructure() {
	create_search_tables();
	create_reversal_tables();
	set_options();
	set_field_sortorder();
	//upload_stylesheet();
	register_semantic_domains_taxonomy();
	register_part_of_speech_taxonomy();
	register_language_taxonomy();
	register_webstrings_taxonomy();
}

//---------------------------------------------------------------------------//
function create_reversal_tables () {
	global $wpdb;

	$table = REVERSALTABLE;
	$sql = "CREATE TABLE IF NOT EXISTS " . $table . " (
		`language_code` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci,
		`reversal_string` longtext CHARACTER SET utf8 COLLATE utf8_general_ci,
		`vernacular_string` longtext CHARACTER SET utf8 COLLATE utf8_general_ci, ";
		$sql .= " PRIMARY KEY (`language_code`, `reversal_string` ( 150 ), `vernacular_string` ( 150 ))
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";
		
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

function create_search_tables () {
	global $wpdb;

	$table = SEARCHTABLE;
	$sql = "CREATE TABLE IF NOT EXISTS " . $table . " (
		`post_id` bigint(20) NOT NULL,
		`language_code` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci,
		`relevance` tinyint,
		`search_strings` longtext CHARACTER SET utf8 COLLATE utf8_general_ci,
		`subid` INT NOT NULL DEFAULT  '0',
		`sortorder` INT NOT NULL DEFAULT '0', ";
		$sql .= " PRIMARY KEY (`post_id`, `language_code`, `relevance`, `search_strings` ( 200 )), ";
		$sql .= " INDEX (relevance)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

//---------------------------------------------------------------------------//

/**
 * Provide a taxonomy for semantic domains in an online dictionary.
 */

function register_semantic_domains_taxonomy () {

    $labels = array(
        'name' => _x( 'Semantic Domains', 'taxonomy general name' ),
        'singular_name' => _x( 'Semantic Domain', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Domains' ),
        'all_items' => __( 'All Semantic Domains' ),
        'parent_item' => __( 'Parent Semantic Domain' ),
        'parent_item_colon' => __( 'Parent Semantic Domain:' ),
        'edit_item' => __( 'Edit Semantic Domain' ),
        'update_item' => __( 'Update Semantic Domain' ),
        'add_new_item' => __( 'Add New Semantic Domain' ),
        'new_item_name' => __( 'New Semantic Domain Name' ),
        'menu_name' => __( 'Semantic Domain' ),
    );

    register_taxonomy(
        'sil_semantic_domains',
        'post',
        array(
            'hierarchical' => false,
            'labels' => $labels,
            'update_count_callback' => '_update_post_term_count',
            'query_var' => true,
            'rewrite' => true,
            'public' => true,
            'show_ui' => true
        )
    ) ;
}

//-----------------------------------------------------------------------------//

/**
 * Provide a taxonomy for Part of Speech (POS) in an online dictionary.
 */


function register_part_of_speech_taxonomy () {

    $labels = array(
        'name' =>  _x( 'Part of Speech', 'taxonomy general name' ),
        'singular_name' => _x( 'Part of Speech', 'taxonomy singular name' ),
        'search_items' =>  __( 'Parts of Speech' ),
        'all_items' => __( 'All Parts of Speech' ),
        'parent_item' => __( 'Parent Part of Speech' ),
        'parent_item_colon' => __( 'Parent Part of Speech:' ),
        'edit_item' => __( 'Edit Part of Speech' ),
        'update_item' => __( 'Update Part of Speech' ),
        'add_new_item' => __( 'Add New Part of Speech' ),
        'new_item_name' => __( 'New Part of Speech Name' ),
        'menu_name' => __( "Parts of Speech"),
    );

    register_taxonomy(
        'sil_parts_of_speech',
        'post',
        array(
            'hierarchical' => false,
            'labels' => $labels,
            'update_count_callback' => '_update_post_term_count',
            'query_var' => true,
            'rewrite' => true,
            'public' => true,
            'show_ui' => true
        )
    ) ;
}

//-----------------------------------------------------------------------------//

/**
 * Provide a taxonomy for the Language Selection in an online dictionary.
 */

function register_language_taxonomy () {

    $labels = array(
        'name' => _x( 'Languages', 'taxonomy general name' ),
        'singular_name' => _x( 'Language', 'taxonomy singular name' ),
        'search_items' =>  __( 'Language' ),
        'all_items' => __( 'All Languages' ),
        'parent_item' => __( 'Parent Language' ),
        'parent_item_colon' => __( 'Parent Language:' ),
        'edit_item' => __( 'Edit Language' ),
        'update_item' => __( 'Update Language' ),
        'add_new_item' => __( 'Add New Language' ),
        'new_item_name' => __( 'New Language Name' ),
        'menu_name' => __( 'Language' ),
    );

    register_taxonomy(
        'sil_writing_systems',
        'post',
        array(
            'hierarchical' => false,
            'labels' => $labels,
            'update_count_callback' => '_update_post_term_count',
            'query_var' => true,
            'rewrite' => true,
            'public' => true,
            'show_ui' => true
        )
    ) ;
}

//-----------------------------------------------------------------------------//

/**
 * Provide a taxonomy for strings that need translation
 */

function register_webstrings_taxonomy () {

    $labels = array(
        'name' => _x( 'Website strings', 'taxonomy general name' ),
        'singular_name' => _x( 'Website strings', 'taxonomy singular name' ),
        'search_items' =>  __( 'Website strings' ),
        'all_items' => __( 'All Website strings' ),
        'parent_item' => __( 'Parent Website strings' ),
        'parent_item_colon' => __( 'Parent Website strings:' ),
        'edit_item' => __( 'Edit Website strings' ),
        'update_item' => __( 'Update Website strings' ),
        'add_new_item' => __( 'Add New Website strings' ),
        'new_item_name' => __( 'New Website strings Name' ),
        'menu_name' => __( 'Website strings' ),
    );

    register_taxonomy(
        'sil_webstrings',
        'post',
        array(
            'hierarchical' => false,
            'labels' => $labels,
            'update_count_callback' => '_update_post_term_count',
            'query_var' => true,
            'rewrite' => true,
            'public' => true,
            'show_ui' => true
        )
    ) ;
}

//-----------------------------------------------------------------------------//

/**
 * Uninstall the custom infrastsructure set up here by the plugin
 */

function clean_out_dictionary_data () {

	$delete_taxonomies = $_POST['delete_taxonomies'];

	//deletes the xhtml file, if still there because import didn't get completed
	$import = new sil_pathway_xhtml_Import();
	$file = $import->get_latest_xhtmlfile();
	if(isset($file->ID))
	{
		wp_delete_attachment( $file->ID );
	}

	// Remove all the old dictionary entries.
	remove_entries();

	// Uninstall the custom table(s) and taxonomies.
	if ($delete_taxonomies == 1)
		unregister_custom_taxonomies();
	uninstall_custom_tables();

	// Reinstall custom table(s) and taxonomies.
	create_search_tables();
	if ($delete_taxonomies == 1) {
		register_semantic_domains_taxonomy();
		register_part_of_speech_taxonomy();
		register_language_taxonomy();
	}
 }

//-----------------------------------------------------------------------------//

/**
 * Remove all posts and revisions, leaving other post types
 * @global  $wpdb
 * @return <type>
 */

function remove_entries () {
	global $wpdb;

	$catid = get_category_id();

	//just posts in category "webonary"
	$sql = "DELETE FROM " . $wpdb->prefix . "posts " .
	" WHERE post_type IN ('post', 'revision') AND " .
	" ID IN (SELECT object_id FROM " . $wpdb->prefix . "term_relationships WHERE " . $wpdb->prefix . "term_relationships.term_taxonomy_id = " . $catid .")";

	$wpdb->query( $sql );

	$sql = "DELETE FROM " . $wpdb->prefix . "term_relationships WHERE term_taxonomy_id = " . $catid;

	$return_value = $wpdb->get_var( $sql );
}

//-----------------------------------------------------------------------------//

function set_options () {
	global $wpdb;
	global $blog_id;

	$sql = "UPDATE " . $wpdb->prefix . "options " .
		 " SET option_value = 0 " .
		 " WHERE option_name = 'uploads_use_yearmonth_folders'";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	/*
	 * setting the upload_path to blogs.dir will cause problems with newer versions of Wordpress and is unnessary
	if ( is_multisite() )
	{
		$sql = "UPDATE " . $wpdb->prefix . "options " .
				" SET option_value = 'wp-content/blogs.dir/" . $blog_id . "/files' " .
				" WHERE option_name = 'upload_path'";

		dbDelta( $sql );
	}
	*/
}

function set_field_sortorder() {
	global $wpdb;

	$sql = " SHOW columns FROM " . $wpdb->prefix . "sil_search WHERE Field = 'sortorder'";
	if($wpdb->get_row($sql))
	{
		return false;
	}
	$sql = " ALTER TABLE " . $wpdb->prefix . "sil_search ADD sortorder INT NOT NULL DEFAULT  '0'";
	$wpdb->query( $sql );
}


/**
 * Uninstall custom taxonomies set up here by the plugin.
 */

function unregister_custom_taxonomies () {
	global $wpdb;

	$sql = "UPDATE $wpdb->term_taxonomy SET count = 1 WHERE count = 0";
	$wpdb->query( $sql);

	unregister_custom_taxonomy ( 'sil_semantic_domains' );
	unregister_custom_taxonomy ( 'sil_parts_of_speech' );
	unregister_custom_taxonomy ( 'sil_writing_systems' );
	unregister_custom_taxonomy ( 'sil_webstrings' );

	//delete all relationships
	$del = "DELETE FROM $wpdb->term_relationships WHERE term_taxonomy_id = 1 ";
	$wpdb->query( $del);
}

//-----------------------------------------------------------------------------//

/**
 * Remove a custom (not builtin) taxonomy.
 * @global <type> $wp_taxonomies
 * @param <string> $taxonomy = The taxonomy to remove
 * @link http://core.trac.wordpress.org/ticket/12629
 */

/*
 * This code may well be deprecated soon, as it is currently a feature request.
 * See the link above.
 */

function unregister_custom_taxonomy ( $taxonomy ) {
	global $wp_taxonomies;
	if ( ! $taxonomy->builtin ) {
		$terms = get_terms( $taxonomy );
		foreach ( $terms as $term ) {
			wp_delete_term( $term->term_id, $taxonomy );
		}
	unset( $wp_taxonomies[$taxonomy]);
	}
}

//-----------------------------------------------------------------------------//

/**
 * Uninstall custom tables set up by the plugin.
 */

function uninstall_custom_tables () {
	uninstall_custom_table( SEARCHTABLE );
}

//-----------------------------------------------------------------------------//

/**
 * Uninstall a custom table.
 * @global <type> $wpdb
 * @param <string> $table =
 */

function uninstall_custom_table ( $table ) {
	global $wpdb;
	$sql = "DROP TABLE " . $table . ";";
	$return_value = $wpdb->get_var( $sql );
}

//-----------------------------------------------------------------------------//

/**
 * Unistall custom tables, taxonomies, etc. on plugin uninstall
 */
function uninstall_sil_dictionary_infrastructure () {

	// Remove all the old dictionary entries.
	//DANGEROUS, this would remove all the posts, if somebody doesn't migrate the posts to pages
	//remove_entries();

	// Uninstall the custom table(s) and taxonomies.
	unregister_custom_taxonomies();
	uninstall_custom_tables();
}

/*
function upload_stylesheet()
{
	if ( is_multisite() )
	{
		$upload_dir = wp_upload_dir();
		$from_path = $_SERVER['DOCUMENT_ROOT'] . "/wp-content/themes/webonary-zeedisplay/style.css";
		$target_path = $upload_dir['path'] . "/style.css";

		if(!file_exists($target_path))
		{
			error_reporting(E_ALL);
			if(copy($from_path, $target_path)) {
				//_e('The css file has been uploaded into your upload folder');
			} else{
				_e('There was an error uploading the file style.css, please try again!');
				echo "<br>";
				echo "From Path: " . $from_path . "<br>";
				echo "Target Path: " . $target_path;
			}
		}
	}
}
*/
?>