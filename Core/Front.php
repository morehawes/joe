<?php

class Joe_Front {
	function __construct() {
		//Front only
		if(is_admin()) {
			return;
		}
		
		add_action('wp_head', array($this, 'wp_head'));			
	}

	function wp_head() {
		echo '<meta name="' . Waymark_Config::get_name(true, true) . ' Version" content="' . Waymark_Config::get_version() . '" />' . "\n";	
	}
}