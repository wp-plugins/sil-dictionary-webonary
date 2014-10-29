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

	public function import($_headers)
	{
		$authenticated = $this->verifyAdminPrivileges();

		if($authenticated){

			$arrDirectory = wp_upload_dir();
			$uploadPath = $arrDirectory['path'];

			$destinationPath = $uploadPath . "/" . str_replace(".zip", "", $_FILES['file']['name']);
			$unzipped = $this->unzip($_FILES['file'], $uploadPath, $destinationPath);

			//program can be closed now, the import will run in the background
			flush();

			if($unzipped)
			{
				$fileConfigured = $destinationPath . "/configured.xhtml";
				$xhtmlConfigured = file_get_contents($fileConfigured);

				$fileReversal1= $destinationPath . "/reversal1.xhtml";
				$xhtmlReversal1 = file_get_contents($fileReversal1);

				$fileReversal2= $destinationPath . "/reversal2.xhtml";
				$xhtmlReversal2 = file_get_contents($fileReversal2);

				//moving style sheet file
				if(file_exists($destinationPath . "/configured.css"))
				{
					copy($destinationPath . "/configured.css", $uploadPath . "/imported-with-xhtml.css");
					error_log("Renamed configured.css to " . $uploadPath . "/imported-with-xhtml.css");
				}
				//copy folder files (which includes audio and image folders and files)
				if(file_exists($destinationPath . "/files"))
				{
					//first delete any existing files
					$this->recursiveRemoveDir($uploadPath . "/images/thumbnail");
					$this->recursiveRemoveDir($uploadPath . "/images/original");
					$this->recursiveRemoveDir($uploadPath . "/audio");
					//then copy everything under files
					$this->recursiveCopy($destinationPath . "/files", $uploadPath);
				}
			}

			if(isset($xhtmlConfigured))
			{
				//we first delete all existing posts (in category Webonary)
				remove_entries();
				//deletes data that comes with the posts, but gets stored separately (e.g. "parts of speech")
				clean_out_dictionary_data();

				$import = new sil_pathway_xhtml_Import();

				//If $verbose is true, it will display progress, but can't run import in the background
				$verbose = false;
				$import->import_xhtml($xhtmlConfigured, true, $verbose, "configured");

				if($verbose)
				{
					echo "Indexing search strings\n";
				}
				$import->index_searchstrings($verbose);
				$import->convert_fields_to_links();
			}

			if(isset($xhtmlReversal1))
			{
				$import->import_xhtml($xhtmlReversal1, true, $verbose, "reversal");
			}

			if(isset($xhtmlReversal2))
			{
				$import->import_xhtml($xhtmlReversal2, true, $verbose, "reversal");
			}

			if(file_exists($destinationPath))
			{
				//deletes the extracted zip folder
				$this->recursiveRemoveDir($destinationPath);
			}
			return "import completed";
		}
		else
		{
			echo "authentication failed\n";
			flush();
		}

		return;
	}

	// Receive upload. Unzip it to uploadPath. Remove upload file.
	public function unzip($zipfile, $uploadPath, $destinationPath)
	{
		$overrides = array( 'test_form' => false, 'test_type' => false );
		$file = wp_handle_upload($zipfile, $overrides);

		if (isset( $file['error']))
		{
			echo "Error: Upload failed: " . $file['error'] . "\n";
			unlink($uploadPath . "/" . $zipfile['name']);
			return false;
		}

		echo "Upload successful\n";

		$zip = new ZipArchive;
		$res = $zip->open($uploadPath . "/" . $zipfile['name']);
		if ($res === FALSE)
		{
			echo "Error: " . $zipfile['name'] . " isn't a valid zip file\n";
			unlink($uploadPath . "/" . $zipfile['name']);
			return false;
		}

		$unzip_success = $zip->extractTo($destinationPath);
		$zip->close();
		if(!$unzip_success)
		{
			echo "Error: couldn't extract zip file to " . $uploadPath;
			unlink($uploadPath . "/" . $zipfile['name']);
			return false;
		}

		echo "zip file extracted successfully\n";
		unlink($uploadPath . "/" . $zipfile['name']);
		return true;
	}

	// Function to remove folders and files
    function recursiveRemoveDir($dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file)
                if ($file != "." && $file != "..") $this->recursiveRemoveDir("$dir/$file");
            rmdir($dir);
        }
        else if (file_exists($dir)) unlink($dir);
    }

	// Function to Copy folders and files
    function recursiveCopy($src, $dst) {
        if (is_dir ( $src )) {
            mkdir ( $dst );
            $files = scandir ( $src );
            foreach ( $files as $file )
                if ($file != "." && $file != "..")
                    $this->recursiveCopy ( "$src/$file", "$dst/$file" );
        } else if (file_exists ( $src ))
            copy ( $src, $dst );
    }

	public function verifyAdminPrivileges()
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
