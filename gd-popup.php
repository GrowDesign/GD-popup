<?php

/*
Plugin Name: GD Popup
Plugin URI: http://seismosoc.org/
Description: Used by millions, to make WP POP.
Version: 1.0.2
Author: Bradford Knowlton
Author URI: http://bradknowlton.com/
GitHub Plugin URI: https://github.com/GrowDesign/GD-popup
*/

add_action( 'wp_enqueue_scripts', 'gd_enqueue_awesome' );
/**
 * Register and load font awesome CSS files using a CDN.
 *
 * @link   http://www.bootstrapcdn.com/#fontawesome
 * @author FAT Media
 */
function gd_enqueue_awesome() {
	wp_enqueue_style( 'gd-font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css' );
    wp_enqueue_style( 'gd-popup-style', plugins_url('css/style.css', __FILE__) );
	
	// wp_enqueue_script( 'gd-popup-script', plugins_url( '/js/script.js' , __FILE__ ) );

	
    if(!is_admin()){
	    wp_enqueue_script('jquery');
		wp_enqueue_script('thickbox',null,array('jquery'));
		wp_enqueue_style('thickbox.css', '/'.WPINC.'/js/thickbox/thickbox.css', null, '1.0');
	    wp_enqueue_style( 'dashicons' );
    }
}

add_action( 'init', 'register_cpt_popup' );

function register_cpt_popup() {

    $labels = array( 
        'name' => _x( 'Popups', 'popup' ),
        'singular_name' => _x( 'Popup', 'popup' ),
        'add_new' => _x( 'Add New', 'popup' ),
        'add_new_item' => _x( 'Add New Popup', 'popup' ),
        'edit_item' => _x( 'Edit Popup', 'popup' ),
        'new_item' => _x( 'New Popup', 'popup' ),
        'view_item' => _x( 'View Popup', 'popup' ),
        'search_items' => _x( 'Search Popups', 'popup' ),
        'not_found' => _x( 'No popups found', 'popup' ),
        'not_found_in_trash' => _x( 'No popups found in Trash', 'popup' ),
        'parent_item_colon' => _x( 'Parent Popup:', 'popup' ),
        'menu_name' => _x( 'Popups', 'popup' ),
    );

    $args = array( 
        'labels' => $labels,
        'hierarchical' => false,
        
        'supports' => array( 'title', 'editor' ),
        
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-format-chat',
        
        
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post'
    );

    register_post_type( 'popup', $args );
}

// [bartag foo="foo-value"]
function popup_func( $atts, $content = null ) {
	global $popups;
    $a = shortcode_atts( array(
        'id' => '1',
    ), $atts );
    
    $popups[] = $a['id'];
    
    $width = get_post_meta( $a['id'], '_gd_width', true );
    
    $height = get_post_meta( $a['id'], '_gd_height', true );
    
	// <a href="http://feedburner.google.com/fb/a/mailverify?uri=manchumahara&amp;loc=en_US&amp;KeepThis=true&amp;height=450&amp;width=600&amp;TB_iframe=true" class="thickbox">Subscribe to my blog via Email</a>
    return "<a href='#TB_inline?width=".$width."&height=".$height."&inlineId=popup-id-{$a['id']}' title='".get_the_title($a['id'])."' class='popup thickbox'>{$content} <i class='fa fa-comment'></i></a>";
}
add_shortcode( 'popup', 'popup_func' );


function gd_footer() {
	global $popups;
	
	$popups = array_unique( $popups );
	
	foreach($popups as $popup){
	
		$content_post = get_post($popup);
		$content = $content_post->post_content;
		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);
		?>   
			<div id='popup-id-<?php echo $popup; ?>' class='popup-wrapper'><div class='popup-content'><?php echo $content; ?></div></div>  
		<?php		
	}
}

add_action( 'wp_footer', 'gd_footer', 100 );



/**
 * Adds a box to the main column on the Post and Page edit screens.
 */
function gd_add_meta_box() {

	add_meta_box(
		'gd_sectionid',
		__( 'Popup Dimensions', 'gd_textdomain' ),
		'gd_meta_box_callback',
		'popup',
		'side'
		
	);
}
add_action( 'add_meta_boxes', 'gd_add_meta_box' );

/**
 * Prints the box content.
 * 
 * @param WP_Post $post The object for the current post/page.
 */
function gd_meta_box_callback( $post ) {

	// Add a nonce field so we can check for it later.
	wp_nonce_field( 'gd_save_meta_box_data', 'gd_meta_box_nonce' );

	/*
	 * Use get_post_meta() to retrieve an existing value
	 * from the database and use the value for the form.
	 */
	
	$width = get_post_meta( $post->ID, '_gd_width', true );
	$height = get_post_meta( $post->ID, '_gd_height', true );

	echo '<p>';
	echo '<label for="myplugin_new_field">';
	_e( 'Width of Popup', 'myplugin_textdomain' );
	echo '</label> ';
	echo '<input type="text" id="gd_width" name="gd_width" value="' . esc_attr( $width ) . '" size="5" /> pixels';
	echo '</p>';
	echo '<p>';
	echo '<label for="myplugin_new_field">';
	_e( 'Height of Popup', 'myplugin_textdomain' );
	echo '</label> ';
	echo '<input type="text" id="gd_height" name="gd_height" value="' . esc_attr( $height ) . '" size="5" /> pixels';
	echo '</p>';
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function gd_save_meta_box_data( $post_id ) {

	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */

	// Check if our nonce is set.
	if ( ! isset( $_POST['gd_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['gd_meta_box_nonce'], 'gd_save_meta_box_data' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	/* OK, it's safe for us to save the data now. */
	
	// Make sure that it is set.
	if ( ! isset( $_POST['gd_width'] ) && ! isset( $_POST['gd_height'] ) ) {
		return;
	}

	// Sanitize user input.
	$width = sanitize_key( $_POST['gd_width'] );
	$height = sanitize_key( $_POST['gd_height'] );

	// Update the meta field in the database.
	update_post_meta( $post_id, '_gd_width', $width );
	update_post_meta( $post_id, '_gd_height', $height );
}
add_action( 'save_post', 'gd_save_meta_box_data' );