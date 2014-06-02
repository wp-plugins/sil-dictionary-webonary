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

	public function import($data, $_headers){
		//array (for Collections) or an object (for Entities).
		if(isset($_SERVER['PHP_AUTH_USER'])){
			//$rettr = $data;
			//sil_pathway_xhtml_Import::import_xhtml($data, true);
			$import = new sil_pathway_xhtml_Import();
			$import->import_xhtml($data, true);
			$import->index_searchstrings();
			
			//$rettr = add_action( 'init', 'import_xhtml' );
								
			//$rettr = "This will eventually display some useful information. Your credentials are USR: " . $_SERVER['PHP_AUTH_USER'] . " PASS: " . $_SERVER['PHP_AUTH_PW'];
		}else{$rettr = "You are not logged in.";}
		return array('returnedData'=>$rettr);
	}
}