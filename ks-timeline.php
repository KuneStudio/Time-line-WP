<?php
/*
Plugin Name: Timeline
Plugin URI: http://www.kune.fr
Description: Timeline plugin
Version: 0.2
Author: Mat_
Author Email: mathieu@kune.fr
License:

  Copyright 2011 Mat_ (mathieu@kune.fr)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  
*/

class Timeline {

	/*--------------------------------------------*
	 * Constants
	 *--------------------------------------------*/
	const name = 'Timeline';
	const slug = 'ks_timeline';
	
	/**
	 * Constructor
	 */
	function __construct() {
		//register an activation hook for the plugin
		register_activation_hook( __FILE__, array( &$this, 'install_ks_timeline' ) );

		//Hook up to the init action
		add_action( 'init', array( &$this, 'init_ks_timeline' ) );
		add_action( 'init', array( &$this, 'register_cpt_timeline_entry') );
		add_action( 'init', array( &$this, 'register_taxonomy_timeline_family') );


	}
  
	/**
	 * Runs when the plugin is activated
	 */  
	function install_ks_timeline() {
		// do not generate any output here
	}
  
	/**
	 * Runs when the plugin is initialized
	 */
	function init_ks_timeline() {
		// Setup localization
		load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		// Load JavaScript and stylesheets
		$this->register_scripts_and_styles();

		add_shortcode( 'kstimeline', array( &$this, 'render_shortcode' ) );
	
		if ( is_admin() ) {
			//this will run when in the WordPress admin
		} else {
			//this will run when on the frontend
		}

		/*
		 * TODO: Define custom functionality for your plugin here
		 *
		 * For more information: 
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( 'your_action_here', array( &$this, 'action_callback_method_name' ) );
		add_filter( 'your_filter_here', array( &$this, 'filter_callback_method_name' ) ); 

	}

	function action_callback_method_name() {
		// TODO define your action method here
	}

	function filter_callback_method_name() {
		// TODO define your filter method here
	}

	function render_shortcode($atts) {
		// Extract the attributes
		extract(shortcode_atts(array(
			'family' => '', 
			
			), $atts));
		// you can now access the attribute values using $attr1 and $attr2

		
		$term = get_term_by('slug', $atts['family'], 'timeline_family');
		$infos = get_option("taxonomy_metadata_ks_timeline_family_" . $term->term_id);
		
		$json = $this->generateJSONTL($term, $infos);

		echo "
		<div class='timeline-wrapper'>
			<div id='my-timeline'></div>
		<script>
		var dataObject = ".json_encode($json).";
	    createStoryJS({
	        type:       'timeline',
	        width:      '100%',
	        height:     '400',
	        source:     dataObject,
	        embed_id:   'my-timeline',
	        font:		'Roboto',
	        lang:		'".$infos['lang']."',
	    });
		</script>
		</div>



		";
	}

	function generateJSONTL($cat, $infos) {

		$args = array (
			'post_type'              => 'timeline_entry',
			'post_status'            => 'publish',
			'timeline_family'		 => $cat->slug
		);

		
		// The Query
		$query_tl = new WP_Query( $args );

		$json['timeline']['headline']	= stripslashes($infos['title']);
		$json['timeline']['type'] 		= 'default';
		$json['timeline']['text'] 		= apply_filters('the_content', stripslashes($infos['content']));
		$json['timeline']['asset']['media'] 		= $infos['image'];
		$json['timeline']['asset']['thumbnail'] 		= $infos['image'];
		
		// The Loop
		if ( $query_tl->have_posts() ) {
			while ( $query_tl->have_posts() ) {
				$query_tl->the_post();
				$start = get_post_meta(get_the_ID(), '_kstl_start_date');
				$end   = get_post_meta(get_the_ID(), '_kstl_end_date');

				
				$start = $this->reverse_array($start[0]);

				
				if($end) $end = $this->reverse_array($end[0]);
				
				$date['startDate']	= $start;
				if($end) $date['endDate']	= $end;
				$date['headline']	= get_the_title();
				$date['text']		= apply_filters('the_content', get_the_content());
				
				
				$media = get_post_meta(get_the_ID(), '_kstl_media_url');
				if($media) var_dump($media);
				if($media) {
					$date['asset']['media']	= $media[0];
				}
				elseif(has_post_thumbnail()){
					$url =  wp_get_attachment_image_src( get_post_thumbnail_id(get_the_ID()), 'full' );
					$caption = wp_get_attachment_image_src( get_post_thumbnail_id(get_the_ID()) );
					$date['asset']['media'] = $url[0];
					$date['asset']['thumbnail'] = $caption[0];
				} 
				$json['timeline']['date'][] = $date;
				$date = NULL;

			}
		}
		wp_reset_postdata();
		return $json;

	}

	
	function reverse_array($a) {
		$a = explode("/", $a);
		$a = array_reverse($a);
		return implode(",", $a);
	}

	function register_cpt_timeline_entry() {

	    $labels = array( 
	        'name' => _x( 'TimelineEntries', 'timeline_entry' ),
	        'singular_name' => _x( 'Timeline Entry', 'timeline_entry' ),
	        'add_new' => _x( 'Add New', 'timeline_entry' ),
	        'add_new_item' => _x( 'Add New Timeline Entry', 'timeline_entry' ),
	        'edit_item' => _x( 'Edit Timeline Entry', 'timeline_entry' ),
	        'new_item' => _x( 'New Timeline Entry', 'timeline_entry' ),
	        'view_item' => _x( 'View Timeline Entry', 'timeline_entry' ),
	        'search_items' => _x( 'Search TimelineEntries', 'timeline_entry' ),
	        'not_found' => _x( 'No timelineentries found', 'timeline_entry' ),
	        'not_found_in_trash' => _x( 'No timelineentries found in Trash', 'timeline_entry' ),
	        'parent_item_colon' => _x( 'Parent Timeline Entry:', 'timeline_entry' ),
	        'menu_name' => _x( 'TimelineEntries', 'timeline_entry' ),
	    );

	    $args = array( 
	        'labels' => $labels,
	        'hierarchical' => false,
	        'description' => 'Entries for the timeline',
	        'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
	        'taxonomies' => array( 'timeline_family' ),
	        'public' => false,
	        'show_ui' => true,
	        'show_in_menu' => true,
	        'menu_position' => 20,
	        
	        'show_in_nav_menus' => true,
	        'publicly_queryable' => true,
	        'exclude_from_search' => true,
	        'has_archive' => true,
	        'query_var' => true,
	        'can_export' => true,
	        'rewrite' => true,
	        'capability_type' => 'post'
	    );

	    register_post_type( 'timeline_entry', $args );


	}

	function register_taxonomy_timeline_family() {

	    $labels = array( 
	        'name' => _x( 'TimelineFamily', 'timeline_family' ),
	        'singular_name' => _x( 'TimelineFamily', 'timeline_family' ),
	        'search_items' => _x( 'Search TimelineFamily', 'timeline_family' ),
	        'popular_items' => _x( 'Popular TimelineFamily', 'timeline_family' ),
	        'all_items' => _x( 'All TimelineFamily', 'timeline_family' ),
	        'parent_item' => _x( 'Parent TimelineFamily', 'timeline_family' ),
	        'parent_item_colon' => _x( 'Parent TimelineFamily:', 'timeline_family' ),
	        'edit_item' => _x( 'Edit TimelineFamily', 'timeline_family' ),
	        'update_item' => _x( 'Update TimelineFamily', 'timeline_family' ),
	        'add_new_item' => _x( 'Add New TimelineFamily', 'timeline_family' ),
	        'new_item_name' => _x( 'New TimelineFamily', 'timeline_family' ),
	        'separate_items_with_commas' => _x( 'Separate timelinefamily with commas', 'timeline_family' ),
	        'add_or_remove_items' => _x( 'Add or remove timelinefamily', 'timeline_family' ),
	        'choose_from_most_used' => _x( 'Choose from the most used timelinefamily', 'timeline_family' ),
	        'menu_name' => _x( 'TimelineFamily', 'timeline_family' ),
	    );

	    $args = array( 
	        'labels' => $labels,
	        'public' => true,
	        'show_in_nav_menus' => true,
	        'show_ui' => true,
	        'show_tagcloud' => true,
	        'show_admin_column' => false,
	        'hierarchical' => true,

	        'rewrite' => true,
	        'query_var' => true
	    );

	    register_taxonomy( 'timeline_family', array('timeline_entry'), $args );
	}
  
	/**
	 * Registers and enqueues stylesheets for the administration panel and the
	 * public facing site.
	 */
	private function register_scripts_and_styles() {
		if ( is_admin() ) {

		} else {
			wp_enqueue_script( self::slug . '-script', plugins_url('js/storyjs-embed.js', __FILE__), array('jquery'), true );
			wp_enqueue_style( self::slug . '-style', plugins_url('css/timeline.css', __FILE__) );
		} // end if/else
	} // end register_scripts_and_styles
	
	/**
	 * Helper function for registering and enqueueing scripts and styles.
	 *
	 * @name	The 	ID to register with WordPress
	 * @file_path		The path to the actual file
	 * @is_script		Optional argument for if the incoming file_path is a JavaScript source file.
	 */
	private function load_file( $name, $file_path, $is_script = false ) {

		$url = plugins_url('css/timeline.css', __FILE__);
		echo $file = plugin_dir_path(__FILE__) . $file_path;

		if( file_exists( $file ) ) {
			if( $is_script ) {
				echo 'toto';
				wp_register_script( $name, $url, array('jquery') ); //depends on jquery
				wp_enqueue_script( $name );
			} else {
				wp_register_style( $name, $url );
				wp_enqueue_style( $name );
			} // end if
		} // end if

	} // end load_file
  
} // end class
new Timeline();


if ( file_exists(  __DIR__ .'/cmb2/init.php' ) ) {
	require_once  __DIR__ .'/cmb2/init.php';
} elseif ( file_exists(  __DIR__ .'/CMB2/init.php' ) ) {
	require_once  __DIR__ .'/CMB2/init.php';
}

/**
 * Conditionally displays a field when used as a callback in the 'show_on_cb' field parameter
 *
 * @param  CMB2_Field object $field Field object
 *
 * @return bool                     True if metabox should show
 */
function cmb2_hide_if_no_cats( $field ) {
	// Don't show this field if not in the cats category
	if ( ! has_tag( 'cats', $field->object_id ) ) {
		return false;
	}
	return true;
}
add_filter( 'cmb2_meta_boxes', 'cmb2_sample_metaboxes' );
/**
 * Define the metabox and field configurations.
 *
 * @param  array $meta_boxes
 * @return array
 */
function cmb2_sample_metaboxes( array $meta_boxes ) {
	// Start with an underscore to hide fields from custom fields list
	$prefix = '_kstl_';
	/**
	 * Sample metabox to demonstrate each field type included
	 */
	$meta_boxes['test_metabox'] = array(
		'id'            => 'timeline_metabox',
		'title'         => __( 'Timeline info', 'ks_timeline_domain' ),
		'object_types'  => array( 'timeline_entry' ), // Post type
		'context'       => 'normal',
		'priority'      => 'high',
		'show_names'    => true, // Show field names on the left
		// 'cmb_styles' => false, // false to disable the CMB stylesheet
		'fields'        => array(

			array(
				'name' => __( 'Start date', 'ks_timeline_domain' ),
				'desc' => __( 'This is when the event starts', 'ks_timeline_domain' ),
				'id'   => $prefix . 'start_date',
				'type' => 'text_date',
			),
			array(
				'name' => __( 'End date (optionnal)', 'ks_timeline_domain' ),
				'desc' => __( 'This is when the event ends', 'ks_timeline_domain' ),
				'id'   => $prefix . 'end_date',
				'type' => 'text_date',
			),
			array(
				'name' => __( 'Media URL', 'ks_timeline_domain' ),
				'desc' => __( 'Can be Youtube, Vimeo, Instagram, ... (optional)', 'ks_timeline_domain' ),
				'id'   => $prefix . 'media',
				'type' => 'text',
				// 'protocols' => array('http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet'), // Array of allowed protocols
				// 'repeatable' => true,
			)
		),
	);
	
	
	
	
	
	
	// Add other metaboxes as needed
	return $meta_boxes;
}

function cmb2_taxonomy_meta_initiate() {

    require_once( 'cmb2/init.php' );
    require_once( 'Taxonomy_MetaData/Taxonomy_MetaData_CMB2.php' );

    /**
     * Semi-standard CMB2 metabox/fields array
     */
    $meta_box = array(
        'id'         => 'timeline_options',
        // 'key' and 'value' should be exactly as follows
        'show_on'    => array( 'key' => 'options-page', 'value' => array( 'unknown', ), ),
        'show_names' => true, // Show field names on the left
        'fields'     => array(
            
            array(
                'name' => __( 'Title cover', 'taxonomy-metadata' ),
                'id'   => 'title',
                'type' => 'text',
                // 'repeatable' => true,
            ),
            array(
                'name'    => __( 'Content cover', 'taxonomy-metadata' ),
                'id'      => 'content',
                'type'    => 'wysiwyg',
                'options' => array( 'textarea_rows' => 5, ),
            ),
            array(
				'name' => __( 'Image cover', 'ks_timeline_domain' ),
				'desc' => __( 'Upload an image or enter a URL.', 'ks_timeline_domain' ),
				'id'   => 'image',
				'type' => 'file',
			),
			array(
				'name'    => __( 'Language', 'ks_timeline_domain' ),
				'desc'    => __( 'Language used', 'ks_timeline_domain' ),
				'id'      => 'lang',
				'type'    => 'select',
				'options' => array(
					'fr' => __( 'FR', 'ks_timeline_domain' ),
					'en-24hr'   => __( 'EN', 'ks_timeline_domain' ),
				),
			),
        )
    );

    /**
     * Instantiate our taxonomy meta class
     */
    $cats = new Taxonomy_MetaData_CMB2( 'timeline_family', $meta_box, __( 'Timeline Settings', 'taxonomy-metadata' ) );
}
cmb2_taxonomy_meta_initiate();

?>