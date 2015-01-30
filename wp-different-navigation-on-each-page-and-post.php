<?php 
/**
 * Plugin Name: WP Different Navigation on Each Page And Post
 * Plugin URI: https://wordpress.org/plugins/wp-different-navigation-on-each-page-and-post/
 * Description: This plugin are display different-different navigation on each page and post.
 * Version: 1.0.0
 * Author: Rajesh Kumawat
 * Author URI: https://profiles.wordpress.org/rajeshkumawat78/
 */
if (!function_exists('is_admin')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}
if ( ! defined( 'WP_DIFF_NAVI_EACH_PAGE_VERSION' ) )
define( 'WP_DIFF_NAVI_EACH_PAGE_VERSION', '1.0.0' );

/*= Proper way to enqueue scripts and styles
---------------------------------------*/
function wpdnepp_different_navigation_scripts() {
	wp_enqueue_style( 'wp-different-navigation-css', plugins_url('assets/wp-different-navigation-css.css', __FILE__) );
}
add_action( 'wp_enqueue_scripts', 'wpdnepp_different_navigation_scripts' );
add_action('admin_enqueue_scripts', 'wpdnepp_different_navigation_scripts');

/*-------------------------Custom metabox-----------------------*/
add_action( 'add_meta_boxes', 'wpdnepp_diffnavieachpage_meta_box_add' );  
function wpdnepp_diffnavieachpage_meta_box_add()  
{ 
$options = get_option( 'theme_settings' );
$wpdiffnaviposttypes = $options['wpdiffnaviposttypes']; 
if(!empty($wpdiffnaviposttypes)){
foreach($wpdiffnaviposttypes as $wpdiffnaviposttype){
    add_meta_box( 'wpdnepp_diffnavieachpage-meta-box-id', 'Different Navigation Box', 'wpdnepp_diffnavieachpage_meta_box_cb', $wpdiffnaviposttype, 'side', 'high' ); 
}}
}  

function wpdnepp_diffnavieachpage_meta_box_cb( $post )  
{  
$values = get_post_custom( $post->ID );  
$nav_menu = isset( $values['_diffnavieachpage_nav_menu_id'] ) ? esc_attr( $values['_diffnavieachpage_nav_menu_id'][0] ) : ''; 

    wp_nonce_field( 'diffnavieachpage_meta_box_nonce', 'meta_box_nonce' );?>  
<?php   
$selected1 = isset( $values['show_header'] ) ? esc_attr( $values['show_header'][0]) : ''; 
$show_header = get_post_meta($post->ID, 'show_header', true);
 $menus = wp_get_nav_menus( array( 'orderby' => 'name' ) );
 
 // If no menus exists, direct the user to go and create some.
		if ( !$menus ) {
			echo '<p>'. sprintf( __('No menus have been created yet. <a href="%s">Create some</a>.'), admin_url('nav-menus.php') ) .'</p>';
			return;
		}
  
	?>
     <p>
			<label for="nav_menu"><?php _e('Select Menu:'); ?></label>
			<select id="nav_menu" name="_diffnavieachpage_nav_menu_id" style="width:100%">
				<option value="0"><?php _e( '&mdash; Select &mdash;' ) ?></option>
		<?php
			foreach ( $menus as $menu ) {
				echo '<option value="' . $menu->term_id . '"'
					. selected( $nav_menu, $menu->term_id, false )
					. '>'. esc_html( $menu->name ) . '</option>';
			}
		?>
			</select>
            <small>Select Menu for this page here</small>
		</p>
    
    <?php  
}  
 

add_action( 'save_post', 'wpdnepp_diffnavieachpage_cd_meta_box_save' );  
function wpdnepp_diffnavieachpage_cd_meta_box_save( $post_id )  
{  
    // Bail if we're doing an auto save  
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return; 
    // if our nonce isn't there, or we can't verify it, bail 
    if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'diffnavieachpage_meta_box_nonce' ) ) return; 
    // if our current user can't edit this post, bail  
    if( !current_user_can( 'edit_post' ) ) return;  
    // now we can actually save the data  
    $allowed = array(  
        'a' => array( // on allow a tags  
            'href' => array() // and those anchors can only have href attribute  
        )  
    );  
    // Make sure your data is set before trying to save it  
   		if( isset( $_POST['_diffnavieachpage_nav_menu_id'] ) )  
        update_post_meta( $post_id, '_diffnavieachpage_nav_menu_id', wp_kses( $_POST['_diffnavieachpage_nav_menu_id'], $allowed ) ); 
		
}

/*= Add Widget Code for Menu
------------------------------------------------*/
// Creating the widget
class wpdnepp_wpdiffnavieachpage_widget extends WP_Widget {
function __construct() {
parent::__construct(
// Base ID of your widget
'wpdiffnavieachpage_widget',
// Widget name will appear in UI
__('WP Different Navigation Widget', 'wpdiffnavieachpage_widget_domain'),
// Widget description
array( 'description' => __( 'WP Different Navigation On Each Page And Post Widget', 'wpdiffnavieachpage_widget_domain' ), )
);
}
// Creating widget front-end
// This is where the action happens
public function widget( $args, $instance ) {
global $post;
$title = apply_filters( 'widget_title', $instance['title'] );
// before and after widget arguments are defined by themes
echo $args['before_widget'];
if ( ! empty( $title ) )
echo $args['before_title'] . $title . $args['after_title'];
// This is where you run the code and display the output
if(wpdnepp_is_blog()){
$navid = $instance['default_menu'];	
}else{
$navid = get_post_meta($post->ID, '_diffnavieachpage_nav_menu_id', true); 
}
if($navid){
$navmenuid = $navid;
}else{
$navmenuid = $instance['default_menu'];	
}
if($navmenuid){
$nav_menu =  wp_get_nav_menu_object($navmenuid) ;
wp_nav_menu( array( 'fallback_cb' => '', 'menu' => $nav_menu ) );
}
echo $args['after_widget'];
}
         
// Widget Backend
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'Widget Nav', 'wpdiffnavieachpage_widget_domain' );
}

$defaultmenu = $instance[ 'default_menu' ];
$menus = wp_get_nav_menus( array( 'orderby' => 'name' ) );
// Widget admin form

// If no menus exists, direct the user to go and create some.
		if ( !$menus ) {
			echo '<p>'. sprintf( __('No menus have been created yet. <a href="%s">Create some</a>.'), admin_url('nav-menus.php') ) .'</p>';
			return;
		}
?>
<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>
<p>
<label for="<?php echo $this->get_field_id( 'default_menu' ); ?>"><?php _e( 'Select Default Menu:' ); ?></label>
<select class="widefat" id="<?php echo $this->get_field_id( 'default_menu' ); ?>" name="<?php echo $this->get_field_name( 'default_menu' ); ?>" >
<option value="0"><?php _e( '&mdash; Select &mdash;' ) ?></option>
		<?php
			foreach ( $menus as $menu ) {
				echo '<option value="' . $menu->term_id . '"'
					. selected( $defaultmenu, $menu->term_id, false )
					. '>'. esc_html( $menu->name ) . '</option>';
			}
		?>
</select>
</p>

<?php
}
     
// Updating widget replacing old instances with new
public function update( $new_instance, $old_instance ) {
$instance = array();
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
$instance['default_menu'] = ( ! empty( $new_instance['default_menu'] ) ) ? strip_tags( $new_instance['default_menu'] ) : '';
return $instance;
}
} // Class wpdiffnavieachpage_widget ends here
// Register and load the widget
function wpdnepp_wpdiffnavieachpage_load_widget() {
    register_widget( 'wpdnepp_wpdiffnavieachpage_widget' );
}
add_action( 'widgets_init', 'wpdnepp_wpdiffnavieachpage_load_widget' );

/*= Add option page 
-----------------------------------------------------*/
//register settings
function wpdnepp_theme_settings_init(){
    register_setting( 'theme_settings', 'theme_settings' );
}
//add settings page to menu
function wpdnepp_add_settings_page() {
add_submenu_page( 'options-general.php', 'WP Different Navigation Box Settings', 'WP Different Navigation Box Settings', 'manage_options', 'wp-different-navi-page-post-settings', 'wpdnepp_theme_settings_page' );
}
//add actions
add_action( 'admin_init', 'wpdnepp_theme_settings_init' );
add_action( 'admin_menu', 'wpdnepp_add_settings_page' );
//start settings page
function wpdnepp_theme_settings_page() {
if ( ! isset( $_REQUEST['updated'] ) )
$_REQUEST['updated'] = false;
//get variables outside scope
global $color_scheme;
?>
<div>
  <div id="icon-options-general"></div>
  <h2>
    <?php _e( 'WP Different Navigation Box Settings' ) //your admin panel title ?>
  </h2>
  <?php
//show saved options message
if ( false !== $_REQUEST['updated'] ) : ?>
  <div>
    <p><strong>
      <?php _e( 'Options saved' ); ?>
      </strong></p>
  </div>
  <?php endif; ?>
  <form method="post" action="options.php">
    <?php settings_fields( 'theme_settings' ); ?>
    <?php $options = get_option( 'theme_settings' );
	$posttypes = get_post_types( '', 'names' );
	$post_types = array_diff($posttypes, array('attachment', 'revision', 'nav_menu_item'));
	$blogmenus = wp_get_nav_menus( array( 'orderby' => 'name' ) );
	 ?>
<table cellpadding="0" cellspacing="0" class="wpdiffnavipostoptiontable">
   <!-- Option 1: -->
   <tr><td><label><?php _e( 'Multiple Select Posttypes : ' ); ?></label></td></tr>
  <tr><td>
            <select id="theme_settings[wpdiffnaviposttypes]" type="checkbox"  name="theme_settings[wpdiffnaviposttypes][]" multiple="multiple">
        	<option value="0"> -- multiple select posttypes -- </option>
             <?php foreach ( $post_types as $key=>$keyvalue ) { ?>
             <option <?php if (!empty($options['wpdiffnaviposttypes']) && in_array($key, $options['wpdiffnaviposttypes'])) { echo 'selected="selected"';}?>  value="<?php echo $key; ?>"><?php echo $key; ?></option>
             <?php } ?>
        	</select><br />
            <small for="theme_settings[wpdiffnaviposttypes]"><?php _e( 'Select Posttypes for display "WP Different Navigation Box" here' );; ?></small>
   </td>
   </tr>
</table>   
<p><input name="submit" id="submit" value="Save Changes" type="submit"></p>
  </form>
</div>
<!-- END wrap -->

<?php
}
//sanitize and validate
function wpdnepp_options_validate( $input ) {
    global $select_options, $radio_options;
    if ( ! isset( $input['option1'] ) )
    $input['option1'] = null;
    $input['option1'] = ( $input['option1'] == 1 ? 1 : 0 );
    $input['sometext'] = wp_filter_nohtml_kses( $input['sometext'] );

    if ( ! isset( $input['radioinput'] ) )
        $input['radioinput'] = null;
    if ( ! array_key_exists( $input['radioinput'], $radio_options ) )
        $input['radioinput'] = null;
    $input['sometextarea'] = wp_filter_post_kses( $input['sometextarea'] );
    return $input;
}
// is blog page function
function wpdnepp_is_blog() {
	global  $post;
	$posttype = get_post_type($post );
	return ( ((is_archive()) || (is_author()) || (is_category()) || (is_home()) || (is_tag())) && ( $posttype == 'post')  ) ? true : false ;
}