<?php
/*
  Plugin Name: Wikify
  Plugin URI: https://github.com/johappel/wikify/archive/master.zip
  Description: An easy-to-use plugin that adds the functionality of a wiki known from MediaWiki. It handles [[word]] and [[word|link label]] .
  Version: 0.3
  Author: Joachim Happel
  Author URI: https://github.com/johappel/

  Features:
  * generate an auto-link to word in double brackets to a Post, with this link
  * handles [[word]] and [[word|label]] 
  * 
  
  **ATTENTION** 
  
  Works only with this permalink settings:  /%postname%/
  
    
    
 */
 
class wp_wikify {

	public $forbiddenSlugs = array("login");
	public $urlPrefix;
	

	function __construct() {
		ini_set("display_errors", 'on');
		ini_set('display_errors',1);
		ini_set('display_startup_errors',1);
		ini_set('error_reporting', E_ERROR);
		//error_reporting(-1);

		$this->urlPrefix = get_home_url() . '/';

		add_filter('the_content', array($this, 'wikifyContent'));
	
		add_action('init',array($this,'wikify_button_init') );
		
		
	}

	
	//Add a wikfy Button to the TinyMCE Editor
	function wikify_button_init() {

		//Abort early if the user will never see TinyMCE
		if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') && get_user_option('rich_editing') == 'true')
		   return;

		//Add a callback to regiser our tinymce plugin   
		add_filter("mce_external_plugins",array($this, "wikify_register_tinymce_plugin") ); 

		// Add a callback to add our button to the TinyMCE toolbar
		add_filter('mce_buttons', array($this,'wikify_add_tinymce_button') );

		// Add a callback to add our stylesheet to mark the brackets
		add_filter( 'tiny_mce_before_init',  array($this,'wikify_add_tinymce_settings') );
		
	}

	//a callback to add a button to the toolbar
	function wikify_add_tinymce_button($buttons) {
		//Add the button ID to the $button array
		$buttons[] = "wikify_button";
		$buttons[] = "wikify_split_button";
		return $buttons;
	}	
	
	//a callback to register the wikify plugin
	function wikify_register_tinymce_plugin($plugin_array) {
		$plugin_array['wikify_button'] = plugins_url('js/wikify.js', __FILE__) ;
		return $plugin_array;
	}
	
	//a callback to add a editor stylesheet
	function wikify_add_tinymce_settings( $initSettings )
	{
			$initSettings['content_css'] .= ','.plugins_url('css/editor-style.css', __FILE__);
			
			$initSettings['wikify_lang_prompt'] = __('Which term should be linked?', 'wikify');
			
			return $initSettings;
	}

	/**
	* find [[ ]] enclosed words
	* either [[word]] or [[word|label]] 
	* and send matches to wikifyBracketedElement
	*/
	function wikifyContent($theContent) {
		
		return preg_replace_callback("/\[\[([^\]]+)\]\]/", array($this, 'wikifyBracketedElement'), $theContent);
		
	}

	// split word|label and sends it to wikiLink
	function wikifyBracketedElement($matches) {
		
		$splitted = explode('|',$matches[1]);
		
		$wikiword = $label = trim($splitted[0]);
				
		if (isset($splitted[1])){
			$label = trim($splitted[1]);
		}
		
		return $this->wikiLink($wikiword, $label);
		
	}

	// returns a link from a wiki word (wikiword|label)
	// if post not exists, the wikiword will be marked as undefined and
	// linked to a new post to define the wikiword
	function wikiLink($wikiword, $label) {
	
		$class = $href = '';
		$slug = sanitize_title(strip_tags($wikiword));
		
		if(in_array($slug, $this->forbiddenSlugs)) return $label; ;
	
		if(!self::postExists($slug)){
			return  '<a title="'.__('Do explain', 'wikify').' '.$wikiword.'" href="' . $this->urlPrefix . "wp-admin/post-new.php?post_title=".urlencode($wikiword) . '" style="color:inherit">'.
					'<span style="border-bottom: .5px dashed;">' . $label . '</span></a>';
		}
		
		return '<a title="relilex: '.$label.'" href="' . $this->urlPrefix .$slug. '">' . $label . '</a>';
	}
	
	

	static function postExists($slug){
		global $wpdb;
		$rs = $wpdb->get_var( 
			$wpdb->prepare( 
				"SELECT count(ID) FROM $wpdb->posts
				 WHERE post_name = %s
				", $slug 
			)
		);
		return ( $rs>0) ? true : false;
	}
}
$wikify_instance = new wp_wikify();



function wikify_post_exists(){
	global $wpdb;
	$contents = explode(',',$_REQUEST['contents']);
	$wikiwords = array(); $data=$result= array();
	
	foreach($contents as $c){
		$slug = esc_sql( sanitize_title(strip_tags($c)) );
		$wikiwords[] = $slug;
		$data[$slug]['label'] = $c;
	}
	$in ="'". implode("','", $wikiwords) ."'";
	$rs = $wpdb->get_results( 
		
			"SELECT post_name, post_title FROM $wpdb->posts
			 WHERE post_name in ($in )
			" 
		
	);
	if($rs ){
		foreach($rs as $ds){
			$data[$ds->post_name]['title'] = $ds->post_title;
			$data[$ds->post_name]['slug']	= $ds->post_name;
		}
		foreach($data as $d){
			$result[]=$d;
		}
	}
	echo json_encode(array('result' =>  $result) );
	die();
	$slug = sanitize_title(strip_tags($_REQUEST['slug']));
	if(wp_wikify::postExists($slug)){
		echo json_encode(array('exists'=> true , 'slug'=> $slug ,'label' => $_REQUEST['slug'] ));
	}else{
		echo json_encode(  array('exists'=> false,
								 'slug'=>$slug,
								 'label' => $_REQUEST['slug'] ));
	}
	die();
}
add_action( 'wp_ajax_wikify_post_exists', 'wikify_post_exists');

?>
