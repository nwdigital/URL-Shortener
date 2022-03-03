<?php
/** Plugin Name: Short URLs by NWDigital
* Plugin URI: https://nwdigital.cloud
* Author: Mathew Moore
* Author URI: https://nwdigital.cloud
* Version: 0.0.1
* Description: Generate short URLs from ugly ones. Shortcode: [shorturlform] [shorturltable]
* Update URI: https://nwdigital.cloud/plugins/tiny-url/
*/

// https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/

function register_short_url_posttype()
{
  $labels = array(
      'name' => 'Short URLs',
  );

  $args = array(
      'labels'          => $labels,
      'show_ui'         => true,
      'public'          => true,
      'publicly_queryable'          => true,
      'has_archive'     => true,
      'capability_type' => 'post',
      'rewrite'         => array( 'slug' => 'i' ),
      'supports'        => array('custom-fields','title')
  );

  register_post_type('short_url', $args);
}

add_action('init', 'register_short_url_posttype');

add_shortcode('shorturlform', 'shorturl_form');
function shorturl_form()
{
  ob_start(); ?>

  <style>
  .original_url{
    cursor:pointer;
    padding: 5px;
    border-radius: 5px;
  }
  .original_url:hover{
    background-color:rgb(11, 100, 157);
    color:white;
  }
  </style>

  <?php if( isset($_GET['url']) AND ($_GET['url'] != NULL) ) : ?>
    <?php $url = wp_strip_all_tags($_GET['url']); ?>
    <form id="shorturl_newform" action="" method="POST">
      <input style="width:100%;max-width:600px;" type="url" name="shorturl_url_input" value="<?php esc_attr_e($url); ?>" autocomplete="off" required />
      <button>Shorten</button>
    </form>
  <?php else : ?>
    <form id="shorturl_newform" action="" method="POST">
      <input style="width:100%;max-width:600px;" type="url" name="shorturl_url_input" autocomplete="off" required />
      <button>Shorten</button>
    </form>
  <?php endif; ?>

  <div style="margin-top:50px;" id="shorturlform_response"></div>

  <?php if(isset($_POST['shorturl_url_input'])) {
    // add_new_url($_POST['shorturl_url_input']);
  } ?>

  <?php return ob_get_clean();
}

// function for adding new url using the shorturl_form() function
function add_new_url($url)
{
  $secret = bin2hex(random_bytes(5));
  $args = array(
    'post_type' => 'short_url',
    'post_title' => $secret,
    'post_slug' => $secret,
    'post_content' => '',
    'post_status' => 'publish',
    'meta_input' => array(
      'shorturl_original_url' => wp_strip_all_tags($url),
      'shorturl_secret' => $secret
    ),
  );
  $new_url_id = wp_insert_post( $args );
  wp_update_post(array('ID' => $new_url_id, 'post_title' => $new_url_id));
  return $new_url_id;
}

add_action('plugins_loaded', 'process_url_query');
function process_url_query()
{
  if(isset($_GET['suid'])) {
    // nocache_headers();
    $url = get_post_meta( absint($_GET['suid']), 'shorturl_original_url', true );
    if ( wp_redirect( $url ) ) {
        exit;
    }
  } else {
      $url = $_SERVER['REQUEST_URI'];
      $url = pathinfo($url, PATHINFO_BASENAME);
      $post_id = absint($url);
      $url = get_post_meta( absint($post_id), 'shorturl_original_url', true );
      $k = wp_strip_all_tags($_GET['k']);
      $secret = get_post_meta(absint($post_id), 'shorturl_secret', true);
      if($k === $secret) {
          if ( wp_redirect( $url ) ) {
              exit;
          }
      } else {
          return false;
      }


  }

}

// add_action('plugins_loaded', 'process_short_url_redirect');
function process_short_url_redirect($content) {
    if( is_singular('short_url') ) {
        //echo "Hello, how are you!";
        $url = get_post_meta( absint(get_the_ID()), 'shorturl_original_url', true );
        if ( wp_redirect( $url ) ) {
            exit;
        }

    } return $content;
}
add_filter('the_content', 'process_short_url_redirect', 1);

function get_new_url($id) {
    $args = array(
        'p' => $id,
        'post_type'   => 'short_url',
        'post_status' => 'publish',
      );
    $latest_short_urls = new WP_Query( $args ); ?>

    <figure class="wp-block-table">
      <table style="width:100%">
        <tbody>
          <tr><td><b>Short URL</b></td><td><b>Original URL</b></td></tr>
          <?php while ( $latest_short_urls->have_posts() ) : $latest_short_urls->the_post() ?>
            <?php $id = get_the_ID(); ?>
            <?php $secret = get_post_field( 'post_name', $id ); ?>
            <?php $link = site_url('/i/' . $secret); ?>
            <?php $orig_url = esc_url(get_post_meta($id, 'shorturl_original_url', true)); ?>
            <?php $trun_orig_url = origUrlTruncate($orig_url); ?>
            <tr><td style="width:50%"><a href="<?php echo esc_url( $link ); ?>" target="_blank"><?php echo esc_url( $link ); ?> &rarr;</a></td><td><span class="original_url" title="<?php echo $orig_url; ?>"><?php echo $trun_orig_url; ?></span></td></tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </figure>

  <?php wp_reset_postdata(); ?>
  <?php

}

add_shortcode('shorturltable', 'short_url_table');
function short_url_table($per_page = 10)
{
    if ( !is_user_logged_in() && !wp_doing_ajax() ) { return false; }
    if ( !current_user_can( 'administrator' ) && !wp_doing_ajax() ) { return false; }

  ob_start();

  if( $per_page === 1 || $per_page === 0) {
      $args = array(
        'post_type'   => 'short_url',
        'posts_per_page' => $per_page,
        'post_status' => 'publish',
      );
  } else {
      $paged = ( get_query_var('page') ) ? get_query_var('page') : 1;

      $args = array(
        'post_type'   => 'short_url',
        'posts_per_page' => $per_page,
        'post_status' => 'publish',
        'paged' => $paged,
      );
  }


  $latest_short_urls = new WP_Query( $args ); ?>

  <figure class="wp-block-table">
    <table style="width:100%">
      <tbody>
        <tr><td><b>Short URL</b></td><td><b>Original URL</b></td></tr>
        <?php while ( $latest_short_urls->have_posts() ) : $latest_short_urls->the_post() ?>
          <?php $id = get_the_ID(); ?>
          <?php $secret = get_post_field( 'post_name', $id ); ?>
          <?php $link = site_url('/i/' . $secret); ?>
          <?php $orig_url = esc_url(get_post_meta($id, 'shorturl_original_url', true)); ?>
          <?php $trun_orig_url = origUrlTruncate($orig_url); ?>
          <tr><td style="width:50%"><a href="<?php echo esc_url( $link ); ?>" target="_blank"><?php echo esc_url( $link ); ?> &rarr;</a></td><td><span class="original_url" title="<?php echo $orig_url; ?>"><?php echo $trun_orig_url; ?></span></td></tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </figure>

  <?php if( $per_page === 1 || $per_page === 0) : ?>

  <?php else : ?>

  <div class="pagination">
      <?php
          echo paginate_links( array(
              'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
              'total'        => $latest_short_urls->max_num_pages,
              'current'      => max( 1, get_query_var( 'page' ) ),
              'format'       => '?page=%#%',
              'show_all'     => false,
              'type'         => 'plain',
              'end_size'     => 2,
              'mid_size'     => 1,
              'prev_next'    => true,
              'prev_text'    => sprintf( '<i></i> %1$s', __( '&laquo Previous Page &nbsp;&nbsp;&nbsp;', 'text-domain' ) ),
              'next_text'    => sprintf( '%1$s <i></i>', __( '&nbsp;&nbsp;&nbsp; Next Page &raquo', 'text-domain' ) ),
              'add_args'     => false,
              'add_fragment' => '',
          ) );
      ?>
  </div>

  <?php endif; ?>

  <?php wp_reset_postdata(); ?>

  <?php return $output . ob_get_clean();
}

function origUrlTruncate($string, $length = 43, $dots = "...") {
    return (strlen($string) > $length) ? substr($string, 0, $length - strlen($dots)) . $dots : $string;
}

add_action('wp_enqueue_scripts', 'shorturl_enqueue_scripts');
function shorturl_enqueue_scripts() {
    wp_register_script('shorturl-script', plugin_dir_url(__FILE__) . 'shorturl.js', array('jquery'), false, false);
    wp_enqueue_script('shorturl-script');

    wp_localize_script('shorturl-script', 'shorturlObject', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'plugin_url' => plugin_dir_url(__FILE__),
            'security' => wp_create_nonce( 'short_url_form_nonce' )
        )
    );
}

add_action('wp_ajax_on_submit_show_user_shorturl', 'on_submit_show_user_shorturl');
add_action('wp_ajax_nopriv_on_submit_show_user_shorturl', 'on_submit_show_user_shorturl');
function on_submit_show_user_shorturl() {
    $nonce = wp_strip_all_tags($_POST['security']);
    if (!wp_verify_nonce($nonce, 'short_url_form_nonce')) {
        die( __( 'Security check', 'text-domain' ) );
    } else {
        $url = wp_strip_all_tags($_POST['url']);
        $id = add_new_url($url);
        echo get_new_url($id);
    }
    wp_die();
}


// Add short_url slug, secret and url column after product name
function add_short_url_slug_column_heading( $columns ) {
    $columns['short_url_slug'] = 'Slug';
    $columns['shorturl_secret'] = 'Secret';
    $columns['shorturl_original_url'] = 'Original URL';
    // move Date column to end of array()
    $date = $columns['date'];
    unset($columns['date']);
    $columns['date'] = $date;
    return $columns;
}
add_filter('manage_edit-short_url_columns','add_short_url_slug_column_heading');

// Display product slug
function add_short_url_slug_column_value( $column_name, $id ) {
    if ( 'short_url_slug' == $column_name ) {
        echo get_post_field( 'post_name', $id, 'raw' );
    }
    if ( 'shorturl_secret' == $column_name ) {
        echo get_post_field( 'shorturl_secret', $id, 'raw' );
    }
    if ( 'shorturl_original_url' == $column_name ) {
        echo get_post_field( 'shorturl_original_url', $id, 'raw' );
    }
    if ( 'date' == $column_name ) {
        echo get_post_field( 'date', $id, 'raw' );
    }
}
add_action( "manage_short_url_posts_custom_column", 'add_short_url_slug_column_value', 10, 2 );


/**
* Disable WordPress RSS Feeds
*/

function short_url_disable_feed() {
 wp_die( __( 'No feed available, please visit the <a href="'. esc_url( home_url( '/' ) ) .'">homepage</a>!' ) );
}

add_action('do_feed', 'short_url_disable_feed', 1);
add_action('do_feed_rdf', 'short_url_disable_feed', 1);
add_action('do_feed_rss', 'short_url_disable_feed', 1);
add_action('do_feed_rss2', 'short_url_disable_feed', 1);
add_action('do_feed_atom', 'short_url_disable_feed', 1);
add_action('do_feed_rss2_comments', 'short_url_disable_feed', 1);
add_action('do_feed_atom_comments', 'short_url_disable_feed', 1);
