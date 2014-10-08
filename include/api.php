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
		//array (for Collections) or an object (for Entities).
		if(isset($_SERVER['PHP_AUTH_USER'])){

			$arrDirectory = wp_upload_dir();
			$uploadPath = $arrDirectory['path'];

			$this->unzip($_FILES['filedata'], $uploadPath);

			$zipPath = $uploadPath . "/" . str_replace(".zip", "", $_FILES['filedata']['name']);
			$fileConfigured = $zipPath . "/configured.xhtml";
			$xhtmlConfigured = file_get_contents($fileConfigured);

			$import = new sil_pathway_xhtml_Import();
			$import->import_xhtml($xhtmlConfigured, true);
			$import->index_searchstrings();

			//$rettr = add_action( 'init', 'import_xhtml' );

		}else{$rettr = "You are not logged in.";}
		return array('returnedData'=>$rettr);
	}

	public function unzip($zipfile, $uploadPath)
	{
		$overrides = array( 'test_form' => false, 'test_type' => false );
		$file = wp_handle_upload($zipfile, $overrides);

		$zip = new ZipArchive;
		$res = $zip->open($uploadPath . "/" . $zipfile['name']);
		echo $res . "|";
		if ($res === TRUE) {
		  $unzip_success = $zip->extractTo($uploadPath);
		  $zip->close();
		  if($unzip_success)
		  {
			echo "zip file extracted successfully";
		  }
		  else
		  {
			echo "couldn't extract zip file to " . $uploadPath;
		  }
		} else {
		  echo $zipfile['name'] . " isn't a valid zip file";
		}

		unlink($uploadPath . "/" . $zipfile['name']);
	}

}
