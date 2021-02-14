<?php
/*
Plugin Name: Create Post
Plugin URI: https://
Description: Create post from url
Version: 1.0
Author: User1
License: GPLv2 or later
Text Domain: create-post
*/
    
function make_custom_post_type() {
    register_post_type('awpost',[
            'labels'=>[
                'name'=>'Awesome post',
                'singular_name'=>'awpost'
            ],
            'public'=>true
        ]
    );
}

function insert_fn() {
    
    $request = wp_remote_get( 'https://jsonplaceholder.typicode.com/posts' );
    if( is_wp_error( $request ) ) {
        return false;
    }
    
    $body = wp_remote_retrieve_body( $request );
    
    $data = json_decode( $body );
    foreach($data as $d) {
        $post_id = wp_insert_post(array (
            'post_type' => 'awpost',
            'post_title' => $d->title,
            'post_content' => $d->body,
            'post_author' => $d->userId,            
            'post_status' => 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
        ));
    }

    global $table_prefix, $wpdb;

    $tblname = 'history';
    $wp_track_table = $table_prefix . "$tblname";

    #Check to see if the table exists already, if not, then create it

    if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table) 
    {

        $sql = "CREATE TABLE `". $wp_track_table . "` ( ";
        $sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
        $sql .= "  `url`  text, ";
        $sql .= "  `cnt`  int(11), ";
        $sql .= "  `created_at`  datetime, ";
        $sql .= "  PRIMARY KEY (`id`) "; 
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ; ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

    $wpdb->insert('wp_history', array(
        'url' => 'https://jsonplaceholder.typicode.com/posts',
        'cnt' => count($data),
        'created_at' => date("Y-m-d H:i:s"),
    ));
}

add_action('init', 'make_custom_post_type');
register_activation_hook( __FILE__, 'insert_fn' );

function history_menu() {
    add_menu_page('History', 'History', 'manage_options', 'slug-history', 'history_fn');
}

function history_fn() {
    global $wpdb;
    $result = $wpdb->get_results('SELECT * FROM wp_history');
    echo "<table border='1' id='example'>";
    echo "<tr><th>url</th><th>Count</th><th>Date</th>";

    foreach($result as $wp_formmaker_submits) {
        echo '<tr/>';
        echo "<td>".$wp_formmaker_submits->url."</td>";
        echo "<td>".$wp_formmaker_submits->cnt."</td>";
        echo "<td>".$wp_formmaker_submits->created_at."</td>";
        echo '<tr/>';
    }
    echo "</table>";
}

add_action('admin_menu', 'history_menu');

function callback_for_setting_up_scripts() {
    wp_enqueue_style( 'datatables-css', 'https://cdn.datatables.net/1.10.23/css/jquery.dataTables.min.css', false );
    wp_register_script( 'jQuery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js', null, null, true );
    wp_enqueue_script('jQuery');
    wp_register_script( 'datatables-js', 'https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js', null, null, true );
    wp_enqueue_script('datatables-js');
    //wp_enqueue_script( 'datatables-js', 'https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js', array( 'jquery' ) );
}
//add_action('wp_enqueue_scripts', 'callback_for_setting_up_scripts');

add_action('admin_enqueue_scripts', 'callback_for_setting_up_scripts');

function custom_internal_javascript() {
	echo '<script>
			//alert( \'hello\' );
            jQuery(document).ready(function() {
                $("#example").DataTable();                
            } );
	</script>';
}

add_action( 'admin_footer', 'custom_internal_javascript' );

function get_20_latest_posts() {
    $args=[
        'post_type'=>'awpost',
        'orderby'=>'post_date',
        'order'=>'DESC',
        'posts_per_page'=>'20'
    ];
    $postloop=new WP_Query($args);
    if($postloop->have_posts()) {
        while($postloop->have_posts()) {
            $postloop->the_post();
            ?>
                <h3><?php the_title(); ?></h3>
            <?php
        }
    }
}

function get_20_posts() {
    add_shortcode('tpost', 'get_20_latest_posts');
}
add_action('init', 'get_20_posts');

?>