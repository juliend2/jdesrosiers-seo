<?php
/* 
Plugin Name: JDesrosiers SEO
Description: A WordPress plugin with simple SEO features
Author: Julien Desrosiers
Version: 1.0
Author URI: http://www.juliendesrosiers.com
*/

$jdseo_post_types = array(
  'post',
  'page'
);


// Meta box
// -----------------------------------------------------------------

// Add the language metabox on every registered custom post type
function jdseo_add_language_metaboxe() {
  global $jdseo_post_types;
  foreach ($jdseo_post_types as $post_type) {
    add_meta_box('jdseo_meta_box', __('Search Engine Optimization', 'jdseo'), 'jdseo_meta_custom_box', $post_type, 'normal', 'default');
  }
}

// The Post's meta fields Metabox
function jdseo_meta_custom_box() {
  global $post;
  echo '<input type="hidden" name="jdseometa_noncename" '
   . 'id="jdseometa_noncename" value="'
   . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

  // Get the meta fields data if its already been entered
  $meta_title = get_post_meta($post->ID, '_jdseo_meta_title', true);
  $meta_description = get_post_meta($post->ID, '_jdseo_meta_description', true);

  // Echo out the field
  $html = '<p><label for="jdseo_meta_title"><strong>'. __('Title', 'jdseo') .'</strong></label></p>';
  $html .= '<p><input type="text" class="regular-text" name="_jdseo_meta_title" id="jdseo_meta_title" value="'. $meta_title .'" /></p>';
  $html .= '<p><label for="jdseo_meta_description"><strong>'. __('Description', 'jdseo') .'</strong></label></p>';
  $html .= '<p><textarea class="large-text" name="_jdseo_meta_description" id="jdseo_meta_description">'. $meta_description .'</textarea></p>';

  echo $html;
}

// Save the metabox data
function jdseo_save_post_meta($post_id, $post) {
  global $jdseo_post_types;
  $key_title = '_jdseo_meta_title';
  $key_description = '_jdseo_meta_description';

  // if we're not in a jdseo-enabled post type, skip.
  if (in_array($post->post_type, $jdseo_post_types)) 
    return $post;

  // verify this came from our screen and with proper authorization,
  // because save_post can be triggered at other times
  if (
    (empty($_POST[$key_title]) && empty($_POST[$key_description])) 
    || empty($_POST['jdseometa_noncename']) 
    || !wp_verify_nonce($_POST['jdseometa_noncename'], plugin_basename(__FILE__))
    || !current_user_can('edit_post', $post->ID)
  ) {
    return $post->ID;
  }

  // OK, we're authenticated: we need to find and save the data
  $title = $_POST[$key_title];
  $description = $_POST[$key_description];

  // set the post's meta title:
  $updated_title = update_post_meta($post->ID, $key_title, $title);
  $updated_description = update_post_meta($post->ID, $key_description, $description);

  // Delete if blank:
  if (!$title) delete_post_meta($post->ID, $key_title);
  if (!$description) delete_post_meta($post->ID, $key_description);
}

// Filters
function jdseo_wp_title_filter($title) {
  global $post;
  $seo_title = get_post_meta($post->ID, '_jdseo_meta_title', true);
  if (!empty($seo_title)) 
    return $seo_title;
  return $title;
}

function jdseo_wp_head_action() {
  global $post;
  $seo_description = get_post_meta($post->ID, '_jdseo_meta_description', true);
  if (!empty($seo_description)) {
    echo '<!-- JDesrosiers SEO -->
<meta name="description" content="'. esc_attr($seo_description) .'" />
<!-- END JDesrosiers SEO -->
';
  }
}

// Helpers


// Filters and Hooks
add_action('admin_init', 'jdseo_add_language_metaboxe');
add_action('save_post', 'jdseo_save_post_meta', 1, 2);

add_filter('wp_title', 'jdseo_wp_title_filter');
add_action('wp_head', 'jdseo_wp_head_action');

