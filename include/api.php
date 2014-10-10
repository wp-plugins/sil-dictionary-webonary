<?php
function webonary_api_init() {
	global $webonary_api_mytype;

	$webonary_api_mytype = new Webonary_API_MyType();
	add_filter( 'json_endpoints', array( $webonary_api_mytype, 'register_routes' ) );
}

add_action( 'wp_json_server_before_serve', 'webonary_api_init' );

class Webonary_API_MyType {
    public function register_routes( $routes ) {
        $routes['/webonary/import'] = array(
        	array( array( $this, 'import'), WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_RAW ),
        );

        return $routes;
    }

	public function import($_headers){

		$authenticated = $this->authenticate();

		if($authenticated){

			$arrDirectory = wp_upload_dir();
			$uploadPath = $arrDirectory['path'];

			$unzipped = $this->unzip($_FILES['file'], $uploadPath);

			if($unzipped)
			{
				$zipPath = $uploadPath . "/" . str_replace(".zip", "", $_FILES['file']['name']);
				$fileConfigured = $zipPath . "/configured.xhtml";
				$xhtmlConfigured = file_get_contents($fileConfigured);

				//moving style sheet file
				copy($zipPath . "/imported-with-xhtml.css", $uploadPath . "/imported-with-xhtml.css");
				echo "Moved imported-with-xhtml.css to " . $uploadPath . "\n";
			}

			$import = new sil_pathway_xhtml_Import();
			if(isset($xhtmlConfigured))
			{
				$import->import_xhtml($xhtmlConfigured, true);
			}
			$import->index_searchstrings();

		}else{$rettr = "authentication failed";}
		return array('returnedData'=>$rettr);
	}

	public function unzip($zipfile, $uploadPath)
	{
		$overrides = array( 'test_form' => false, 'test_type' => false );
		$file = wp_handle_upload($zipfile, $overrides);

		$zip = new ZipArchive;
		$res = $zip->open($uploadPath . "/" . $zipfile['name']);
		if ($res === TRUE) {
		  $unzip_success = $zip->extractTo($uploadPath);
		  $zip->close();
		  if($unzip_success)
		  {
			echo "zip file extracted successfully\n";
		  }
		  else
		  {
			echo "couldn't extract zip file to " . $uploadPath;
		  }
		} else {
		  echo $zipfile['name'] . " isn't a valid zip file\n";
		  return false;
		}

		unlink($uploadPath . "/" . $zipfile['name']);
		return true;
	}

	public function authenticate()
	{
		global $wpdb;

		$user = wp_authenticate( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );

		if(isset($user->ID))
		{
			$sql = "SELECT meta_value AS userrole FROM wp_usermeta " .
				   " WHERE user_id = " . $user->ID . " AND meta_key = 'wp_" . get_current_blog_id()  . "_capabilities'";


			$roleSerialized = $wpdb->get_var($sql);
			$userrole = unserialize($roleSerialized);

			if($userrole['administrator'] == true)
			{
				return true;
			}
			else
			{
				echo "User doesn't have permission to import data to this Webonary site\n";
				return false;
			}

		}
		else
		{
			echo "Wrong username or password.\n";
			return false;
		}
	}
}
