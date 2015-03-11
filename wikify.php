<?php
/*
  Plugin Name: Wikify
  Plugin URI: https://github.com/johappel/wikify/blob/master/README.md
  Plugin SRC: https://github.com/johappel/wikify/archive/master.zip
  Description: An easy-to-use plugin that adds the functionality of a wiki known from MediaWiki. It handles [[word]] and [[word|link label]] .
  Version: 0.4
  Author: Joachim Happel
  Author URI: https://github.com/johappel/

  Features:
  * generate an auto-link to word in double brackets to a Post, with this link
  * handles [[word]] and [[word|label]] 
  * can alternative link to external wikis
  
  **ATTENTION** 
  
  Does not work with standard permalink setting:  ?p=ID
  
    
    
 */
 
class wp_wikify {

	public $forbiddenSlugs = array("login");
	public $urlPrefix;
	public $is_external = false;
	public $external_url = '';

	function __construct() {
		
		ini_set("display_errors", 'on');
		ini_set('display_errors',1);
		ini_set('display_startup_errors',1);
		ini_set('error_reporting', E_ERROR);
		//error_reporting(-1);

		$this->urlPrefix = get_home_url() . '/';

		add_filter('the_content', array($this, 'wikifyContent'));
	
		add_action('init',array($this,'init') );
		add_action('init',array($this,'wikify_button_init') );
		add_action('wp_print_styles',array($this,'wikify_add_style') );
		
		add_action( 'admin_menu', array($this,'wf_add_admin_menu') );
		add_action( 'admin_init', array($this,'wf_option_forms') );
	
	}

	function init(){
		
		$options = get_option('wf_settings');
		
		$this->is_external = isset( $options['wf_checkbox_external_usage'] ) ;
		$this->external_url = isset($options['wf_text_external_url']) ? $options['wf_text_external_url'] : '';
		$this->title_prefix = isset($options['wf_text_title_prefix']) ? $options['wf_text_title_prefix'].' ' : '';
	}

	function wf_add_admin_menu(  ) { 

		add_options_page( 'wikify', 'Wikify', 'manage_options', 'wikify', array($this, 'wf_options_page') );

	}


	function wf_option_forms(  ) { 

		function wf_checkbox_external_usage_draw(  ) { 

			$options = get_option( 'wf_settings' );
			?>
			<input type='checkbox' name='wf_settings[wf_checkbox_external_usage]' <?php checked( $options['wf_checkbox_external_usage'], 1 ); ?> value='1'>
			<?php

		}

		function wf_text_title_prefix_draw(  ) { 
			$options = get_option( 'wf_settings' );
			if(!isset($options['wf_text_title_prefix'])){
				$options['wf_text_title_prefix'] = 'wiki:';
			}
			?>
			<input style="width:300px" type='text' name='wf_settings[wf_text_title_prefix]' value='<?php echo $options['wf_text_title_prefix']; ?>'>
			<?php
		}
		
		function wf_text_undefinded_style_draw(  ) { 
			$options = get_option( 'wf_settings' );
			if(!isset($options['wf_text_undefinded_style']) ||  empty($options['wf_text_undefinded_style']) ){
				$options['wf_text_undefinded_style'] = 'border-bottom:1px dashed black';
			}
			
			?>
			<input style="width:300px" type='text' name='wf_settings[wf_text_undefinded_style]' value='<?php echo $options['wf_text_undefinded_style']; ?>'>
			<?php
		}
		
		function wf_text_external_url_draw(  ) { 
			$options = get_option( 'wf_settings' );
			
			if( ( !isset( $options['wf_text_external_url'] ) || empty( $options['wf_text_external_url'] ) ) && isset($options['wf_checkbox_external_usage'])){
				$options['wf_text_external_url'] = 'http://wikipedia.org/wiki/';
				update_option('wf_settings',$options);
			}
			?>
			<input style="width:300px" type='text' name='wf_settings[wf_text_external_url]' value='<?php echo $options['wf_text_external_url']; ?>'>
			<?php
		}


		function wf_settings_section_callback(  ) { 

			_e( 'You can set a prefix to the title of each wikiword like <i>wiki:</i> or <i>relilex:</i> . The title will be display on mouse over the wiki word link', 'wikify' );
			echo '<br>';
			_e( 'Define a link style for the undefined WikiWords','wikify');
		}
		
		function wf_external_section_callback(  ) { 

			echo __( 'If you only want to point the WikiWords to another page, where the terms are defined, activate the following option.', 'wikify' );

		}
		
		register_setting( 'pluginPage', 'wf_settings' );
		
		add_settings_section(
			'wf_pluginPage_section', 
			__('Settings'), 
			'wf_settings_section_callback', 
			'pluginPage'
		);
		
		add_settings_field( 
			'wf_text_title_prefix', 
			__( 'Link title prefix:', 'wikify' ), 
			'wf_text_title_prefix_draw', 
			'pluginPage', 
			'wf_pluginPage_section' 
		);
		
		add_settings_field( 
			'wf_text_undefinded_style', 
			__( 'Link style:', 'wikify' ), 
			'wf_text_undefinded_style_draw', 
			'pluginPage', 
			'wf_pluginPage_section' 
		);

		register_setting( 'externalPage', 'wf_settings' );
				
		add_settings_section(
			'wf_pluginPage_section', 
			__( 'Link to extern wiki', 'wikify' ), 
			'wf_external_section_callback', 
			'externalPage'
		);

		add_settings_field( 
			'wf_checkbox_external_usage', 
			__( 'Use extern wiki', 'wikify' ), 
			'wf_checkbox_external_usage_draw', 
			'externalPage', 
			'wf_pluginPage_section' 
		);

		add_settings_field( 
			'wf_text_external_url', 
			__( 'External url:', 'wikify' ), 
			'wf_text_external_url_draw', 
			'externalPage', 
			'wf_pluginPage_section' 
		);
		

	}
	
		
	function wf_options_page(  ) { 

		?>
		<form action='options.php' method='post'>
			
			<h2><?php _e('Settings'); ?> > Wikify </h2>
			
			
			<?php
			_e('Wikify is an easy-to-use plugin that adds the functionality of a wiki known from MediaWiki.<br> 
			It handles shortcodes like [[word]] and [[word|link label]] in your post content.<hr>','wikify');
			echo '<br>';
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			echo '<br>';
			echo '<hr>';
			echo '<br>';
			settings_fields( 'externalPage' );
			do_settings_sections( 'externalPage' );
			submit_button();
			?>
			
		</form>
		<hr>
		wikify is designed by Joachim Happel
		<?php

	}
	
	
	function wikify_add_style(){
		
		$options = get_option( 'wf_settings' );
		if ( empty($options['wf_text_undefinded_style']) ){
			$options['wf_text_undefinded_style'] = 'border-bottom: 1px dashed black';
		}
		
		$style = '<style>.entry-content a.wikiword-undefined, .entry-summary a.wikiword-undefined {' . $options['wf_text_undefinded_style']. '}</style>';
		
		echo $style;
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
	
		if($this->is_external){
			return '<a title="'.$this->title_prefix.$label.'" href="' . $this->external_url .$slug. '">' . $label . '</a>';
		}
	
		if(!self::postExists($slug)){
			return  '<a title="'.__('Do explain', 'wikify').' '.$wikiword.'" href="' . $this->urlPrefix . "wp-admin/post-new.php?post_title=".urlencode($wikiword) . '" class="wikiword-undefined">'.
					'' . $label . '</a>';
		}
		
		return '<a title="'.$label.'" href="' . $this->urlPrefix .$slug. '">' . $label . '</a>';
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
	function ajax_check_external_usage(){
		if($this->is_external){
			echo json_encode(array('external'=>true) ) ;
		}
		die();
	}
	
	function ajax_wikify_post_exists(){
	
		if($this->is_external){
			echo json_encode(array('result' =>  array(), 'external'=>true, 'prefix'=>$this->title_prefix) );
			die();
		}
		
		
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
		echo json_encode(array('result' =>  $result, 'prefix'=>$this->title_prefix) );
		die();
	}
}
$wikify_instance = new wp_wikify();

add_action( 'wp_ajax_wikify_post_exists', array($wikify_instance, 'ajax_wikify_post_exists') );
add_action( 'wp_ajax_check_external_usage', array($wikify_instance, 'ajax_check_external_usage') );

?>
