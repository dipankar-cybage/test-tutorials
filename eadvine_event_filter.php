<?php
/**
 * Plugin Name: eAdvine Event Filter .
 * Description: Filter event by Year, Month, Publication, Industry
 * Version: 1.0.0
 * Author: Dipankar Biswas
 */

 define('PLUGIN_FOLDER','eadvine_event_filter');
 
function wpse_modify_taxonomy() {
    // get the arguments of the already-registered taxonomy
    $people_category_args = get_taxonomy( 'event-venue' ); // returns an object

    // make changes to the args
    // in this example there are three changes
    // again, note that it's an object
    $people_category_args->show_admin_column = true;
    $people_category_args->rewrite['slug'] = 'eventnew';
    $people_category_args->rewrite['with_front'] = false;
	
	
	/*if ($_GET['cat']) {	
		$term = get_term( $_GET['cat'] );
		echo "<title>".$term->name." | ".get_bloginfo( 'name' )."</title>";
		add_filter('the_title','title_callback');
	}*/
    // re-register the taxonomy
    register_taxonomy( 'event-venue', 'event', (array) $people_category_args );
	 $args = wp_parse_args( $args, array('hide_empty'=>0, 'fields'=>'all','eo_update_venue_cache'=>true) );
    $venues = get_terms('event-venue',$args);
	
}
// hook it up to 11 so that it overrides the original register_taxonomy function


add_action( 'init', 'wpse_modify_taxonomy', 11);

	/*
		Set page title
	*/
function title_callback($data){
	
	$term = get_term( get_query_var( 'cat' ) );
	
	// where $data would be string(#) "current title"
	// Example:
	// (you would want to change $post->ID to however you are getting the book order #,
	// but you can see how it works this way with global $post;)
	return $term->name;
}

/* 
[event_category] shortcode and callback method define
*/
function event_category_shortcode($atts) {
	ob_start();
	//Register a new short code for youtube lightbox: [event_filter] . This is a WP default function.
	 

	if (get_query_var( 'cat' )) {
		$catArray = array(
								 'taxonomy'=>'event-category',
								 'operator' => 'IN',
								 'field'=>'id',
								 'terms'=>array(get_query_var( 'cat' ))
								 );
		$template = 'event-view';	//created template file name for event listing page
		
		$yrmo = ''; //initialize  year month empty variable
		$PUBARRAY = ''; //initialize  publication agrs array empty variable
		$INARRAY = '';  //initialize  industry agrs array empty variable

		/*
		Set agrs for year and month parameter
		*/
		if (get_query_var( 'yr' ) && !get_query_var( 'mo' )) {
			$start_date = get_query_var( 'yr' ).'-01-01';
			$end_date = get_query_var( 'yr' ).'-12-31';
			$yrmo[0] = "event_start_after";
			$yrmo[1] = $start_date;
			$yrmo[2] = "event_start_before";
			$yrmo[3] = $end_date;
		} elseif (get_query_var( 'mo' ) && !get_query_var( 'yr' )) {
			$number = cal_days_in_month(CAL_GREGORIAN, get_query_var( 'mo' ), date('Y'));
			$start_date = date('Y').'-'.get_query_var( 'mo' ).'-01';
			$end_date = date('Y').'-'.get_query_var( 'mo' ).'-'.$number;
			
			$yrmo[0] = "event_start_after";
			$yrmo[1] = $start_date;
			$yrmo[2] = "event_start_before";
			$yrmo[3] = $end_date;
		}
		elseif (get_query_var( 'mo' ) && get_query_var( 'yr' )) {
			$number = cal_days_in_month(CAL_GREGORIAN, get_query_var( 'mo' ), get_query_var( 'yr' ));
			$start_date = get_query_var( 'yr' ).'-'.get_query_var( 'mo' ).'-01';
			$end_date = get_query_var( 'yr' ).'-'.get_query_var( 'mo' ).'-'.$number;
			
			$yrmo[0] = "event_start_after";
			$yrmo[1] = $start_date;
			$yrmo[2] = "event_start_before";
			$yrmo[3] = $end_date;
		}
		
		/*
		End agrs for year and month parameter
		*/

		
		/*
		Set agrs array for publication term ID
		*/
		if (get_query_var( 'publication' )) {
			$PUBARRAY = array(
								 'taxonomy'=>'publication',
								 'operator' => 'IN',
								 'field'=>'id',
								 'terms'=>array(get_query_var( 'publication' ))
								 );
		}
		/*
		Set agrs array for Industry term ID
		*/
		if (get_query_var( 'industry' )) {
			$INARRAY = array(
								 'taxonomy'=>'industry',
								 'operator' => 'IN',
								 'field'=>'id',
								 'terms'=>array(get_query_var( 'industry' ))
								 );
		}
		
		/*
			Fetch event data and store in $events varibale
		*/
		
		print_r(array(
							'numberposts'=> -1,
							$yrmo[0] => $yrmo[1],
							$yrmo[2] => $yrmo[3],
							'tax_query'=>array(
									$catArray,
									$PUBARRAY,
									$INARRAY
								 )
							 ));
		$events = eo_get_events(array(
							'numberposts'=> -1,
							$yrmo[0] => $yrmo[1],
							$yrmo[2] => $yrmo[3],
							'tax_query'=>array(
									$catArray,
									$PUBARRAY,
									$INARRAY
								 )
							 ));	 
		
		//Args for which terms to retrieve
		$args = array('type'=> 'event', 'order' => 'ASC', 'hide_empty' => 0 );

		/*
			Array of taxonomies from which to collect the terms		
			//Get the All publications	
		*/		
		$publications = get_terms( 'publication', $args);
		
		//Get the all industries		
		$industries = get_terms( 'industry', $args);
		
		$filename = plugin_dir_path( __FILE__ ) . "views/" . $template . '.php';
		if( file_exists( $filename ) ){
			ob_start();
			include $filename;
			return ob_get_clean();
		} else {
			echo __( sprintf( 'Template not found<br>%s' , $filename), 'eAdvine event filter' );
		}
	} elseif (get_query_var( 'view' )) {
		$template = 'calendar-view';	//created template file name for calendar view page
		$calendar = do_shortcode( "[eo_fullcalendar headerLeft='prev,next' headerCenter='title' headerRight='month,agendaWeek,agendaDay']" );
		$filename = plugin_dir_path( __FILE__ ) . "views/" . $template . '.php';
		if( file_exists( $filename ) ){
			ob_start();
			include $filename;
			return ob_get_clean();
		} else {
			echo __( sprintf( 'Template not found<br>%s' , $filename), 'eAdvine event filter' );
		}
	} else {
		$template = 'cat-view'; //created template file name for category listing page

		 //Args for which terms to retrieve
		$args = array('type'=> 'event', 'order' => 'ASC', 'hide_empty' => 1 );
		
		/*
			//Array of taxonomies from which to collect the terms
			//Get the Event categories
		*/
		$categories = get_terms( 'event-category', $args);

		
		
		
		$filename = plugin_dir_path( __FILE__ ) . "views/" . $template . '.php';
		if( file_exists( $filename ) ){
			ob_start();
			include $filename;
			return ob_get_clean();
		} else {
			echo __( sprintf( 'Template not found<br>%s' , $filename), 'eAdvine event filter' );
		}	

	}
	return ob_get_clean();	
}

// Add [event_category] shortcode and callback event_category_shortcode
add_shortcode( 'event_category', 'event_category_shortcode' ); 


/**
 * Create two taxonomies, Publications and Industry for the post type "Event".
 *
 * @see register_post_type() for registering custom post types.
 */
function wp_create_event_taxonomies() {
    // Add new taxonomy, make it hierarchical (like categories)
    $labels = array(
        'name'              => _x( 'Publications', 'taxonomy general name', 'textdomain' ),
        'singular_name'     => _x( 'Publication', 'taxonomy singular name', 'textdomain' ),
        'search_items'      => __( 'Search Publications', 'textdomain' ),
        'all_items'         => __( 'All Publications', 'textdomain' ),
        'parent_item'       => __( 'Parent Publication', 'textdomain' ),
        'parent_item_colon' => __( 'Parent Publication:', 'textdomain' ),
        'edit_item'         => __( 'Edit Publication', 'textdomain' ),
        'update_item'       => __( 'Update Publication', 'textdomain' ),
        'add_new_item'      => __( 'Add New Publication', 'textdomain' ),
        'new_item_name'     => __( 'New Publication Name', 'textdomain' ),
        'menu_name'         => __( 'Publication', 'textdomain' ),
    );
 
    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'publication' ),
    );
 
    register_taxonomy( 'publication', array( 'event' ), $args );
 
    unset( $args );
    unset( $labels );
 
    // Add new taxonomy, NOT hierarchical (like tags)
    $labels = array(
        'name'                       => _x( 'Industry', 'taxonomy general name', 'textdomain' ),
        'singular_name'              => _x( 'Industry', 'taxonomy singular name', 'textdomain' ),
        'search_items'               => __( 'Search Industry ', 'textdomain' ),
        'popular_items'              => __( 'Popular Industry ', 'textdomain' ),
        'all_items'                  => __( 'All Industry ', 'textdomain' ),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __( 'Edit Industry ', 'textdomain' ),
        'update_item'                => __( 'Update Industry ', 'textdomain' ),
        'add_new_item'               => __( 'Add New Industry ', 'textdomain' ),
        'new_item_name'              => __( 'New Industry  Name', 'textdomain' ),
        'separate_items_with_commas' => __( 'Separate industry with commas', 'textdomain' ),
        'add_or_remove_items'        => __( 'Add or remove industry ', 'textdomain' ),
        'choose_from_most_used'      => __( 'Choose from the most used industry', 'textdomain' ),
        'not_found'                  => __( 'No industry found.', 'textdomain' ),
        'menu_name'                  => __( 'Industry', 'textdomain' ),
    );
 
    $args = array(
        'hierarchical'          => true,
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'industry' ),
    );
 
    register_taxonomy( 'industry', 'event', $args );
	
	
	    $labels = array(
        'name'              => _x( 'dipankar', 'taxonomy general name', 'textdomain' ),
        'singular_name'     => _x( 'dipankar', 'taxonomy singular name', 'textdomain' ),
        'search_items'      => __( 'Search dipankar', 'textdomain' ),
        'all_items'         => __( 'All dipankar', 'textdomain' ),
        'parent_item'       => __( 'Parent dipankar', 'textdomain' ),
        'parent_item_colon' => __( 'Parent dipankar:', 'textdomain' ),
        'edit_item'         => __( 'Edit dipankar', 'textdomain' ),
        'update_item'       => __( 'Update dipankar', 'textdomain' ),
        'add_new_item'      => __( 'Add New dipankar', 'textdomain' ),
        'new_item_name'     => __( 'New dipankar Name', 'textdomain' ),
        'menu_name'         => __( 'dipankar', 'textdomain' ),
    );
 
    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'dipankar' ),
    );
 
    register_taxonomy( 'Dipankar', array( 'event' ), $args );
}
// hook into the init action and call create_event_taxonomies when it fires
add_action( 'init', 'wp_create_event_taxonomies', 0 );


/*
Assembling the list of query variables, this function will add 'cat','yr','mo', 'publication' and 'industry' to the list.
*/
function add_query_vars_filter( $vars ){
	$vars[] = "cat";
	$vars[] = "view";
	$vars[] = "yr";
	$vars[] = "mo";
	$vars[] = "publication";
	$vars[] = "industry";
	return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );

/*
Enqueue script and style for event details lightbox popup
*/
add_action( 'wp_enqueue_scripts', 'plugin_scripts' ); 
define('EADVINE_EVENT_VERSION', '1');
define('EADVINE_EVENT_URL', plugins_url().'/eadvine_event_filter');
function plugin_scripts() {
	global $post;			
	$content = $post->post_content;           
	if (!is_admin() and has_shortcode( $content, 'event_category' ) == true) 
	{
		wp_enqueue_script('jquery');
		wp_register_script('event', EADVINE_EVENT_URL.'/js/jquery.event.js', array('jquery'), EADVINE_EVENT_VERSION);
		wp_enqueue_script('event');
		wp_register_script('eadvine_event_filter', EADVINE_EVENT_URL.'/js/eadvine-event.js.js', array('event'), EADVINE_EVENT_VERSION);
		wp_enqueue_script('eadvine_event_filter');
		wp_register_style('event', EADVINE_EVENT_URL.'/css/event.css');
		wp_enqueue_style('event');
		
		
	}
	
	if (!is_admin() and get_query_var( 'view' )) {
	
		wp_register_style('eadvine_event_filter', EADVINE_EVENT_URL.'/css/eventorganiser-front-end.min.css');
		wp_register_style('eadvine_event_filter1', EADVINE_EVENT_URL.'/css/fullcalendar.min.css');
		wp_enqueue_style('eadvine_event_filter');
		wp_enqueue_style('eadvine_event_filter1');
	}
 }
 
 /*
Created Event details page template for lightbox popup
*/
add_filter( 'template_include', 'event_plugin_templates' );
function event_plugin_templates( $template ) {
    $post_types = array( 'event' );
	$dir = plugin_dir_path( __FILE__ );
    ob_start();
	if ( is_singular( $post_types ) )
         $template = $dir.'views/single-event.php';
ob_get_clean();	
    return $template;
}

function codex_custom_init() {
    $args = array(
      'public' => true,
      'label'  => 'Books'
    );
    register_post_type( 'book', $args );
}
add_action( 'init', 'codex_custom_init' );


function wp_create_book_taxonomies() {
   // Add new taxonomy, make it hierarchical (like categories)
   $labels = array(
       'name'              => _x( 'biswas', 'taxonomy general name', 'textdomain' ),
       'singular_name'     => _x( 'biswas', 'taxonomy singular name', 'textdomain' ),
       'search_items'      => __( 'Search biswas', 'textdomain' ),
       'all_items'         => __( 'All biswas', 'textdomain' ),
       'parent_item'       => __( 'Parent biswas', 'textdomain' ),
       'parent_item_colon' => __( 'Parent biswas:', 'textdomain' ),
       'edit_item'         => __( 'Edit biswas', 'textdomain' ),
       'update_item'       => __( 'Update biswas', 'textdomain' ),
       'add_new_item'      => __( 'Add New biswas', 'textdomain' ),
       'new_item_name'     => __( 'New biswas Name', 'textdomain' ),
       'menu_name'         => __( 'biswas', 'textdomain' ),
   );

   $args = array(
       'hierarchical'      => false,
       'labels'            => $labels,
       'show_ui'           => true,
       'show_admin_column' => true,
       'query_var'         => true,
       'rewrite'           => array( 'slug' => 'biswas' ),
   );

   register_taxonomy( 'biswas', array( FOOGALLERY_CPT_GALLERY ), $args );
   
/*$dd = new FooGallery_Admin_Gallery_MetaBoxes();
$rr = $dd->whitelist_metaboxes();
print_r($rr);*/
}

// hook into the init action and call create_event_taxonomies when it fires
add_action( 'init', 'wp_create_book_taxonomies', 0 );
Dipankar
