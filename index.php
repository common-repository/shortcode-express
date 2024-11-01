<?php
/*
Plugin Name: Shortcode Express
Plugin URI: http://wordpress.org/extend/plugins/shortcode-express/
Description: Easily use shortcodes in your posts, pages and widgets.
Version: 1.0
Author: Ariel Elyah
Author URI: http://www.gasymail.com/
License: GPLv3
*/

$sce = new Shortcode_Express();


class Shortcode_Express
{

    /*
     * Class constructor
     */
    function __construct() {

        define( 'SHORTCODE_EXPRESS_VERSION', '1.0' );
        define( 'SHORTCODE_EXPRESS_DIR', dirname( __FILE__ ) );
        define( 'SHORTCODE_EXPRESS_URL', plugins_url( basename( __DIR__ ) ) );
        $this->init();
    }


    /**
     * Get started once WP fully loads
     */
    function init() {
        global $wpdb;

        // Update the wp_option value
        $db_version = get_option( 'sce_version' );

        if ( false === $db_version ) {
            $wpdb->query( "
            CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shortcodeexpress (
                `id` int unsigned AUTO_INCREMENT PRIMARY KEY,
                `name` varchar(128),
                `content` mediumtext)" );

            // Add the version
            add_option( 'sce_version', SHORTCODE_EXPRESS_VERSION );
        }
        elseif ( version_compare( $db_version, SHORTCODE_EXPRESS_VERSION, '<' ) ) {
            update_option( 'sce_version', SHORTCODE_EXPRESS_VERSION );
        }

        // Add hooks
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_shortcode( 'sce', array( $this, 'shortcode' ) );
        add_action( 'wp_ajax_shortcode_express', array( $this, 'handle_ajax' ) );
        add_filter( 'widget_text', 'do_shortcode' );
    }


    /**
     * Shortcode handler
     */
    function shortcode( $atts ) {
        global $wpdb;

        $atts = (object) $atts;
        if (false === empty($atts->name)) {
            $row = $wpdb->get_row( "SELECT content FROM `{$wpdb->prefix}shortcodeexpress` WHERE name = '$atts->name' LIMIT 1" );
            ob_start();
            echo '<div class="shortcode-express">' . eval( '?>' . $row->content ) . '</div>';
            return ob_get_clean();
        }
    }


    /**
     * Create the "Tools > Shortcodes" admin menu
     */
    function admin_menu() {
        add_submenu_page( 'tools.php', 'Shortcode Express', 'Shortcode Express', 'manage_options', 'shortcode-express', array( $this, 'admin_page' ) );
    }


    /**
     * Format the AJAX response
     */
    function json_response( $status = 'ok', $status_message = null, $shortcodename = null, $data = null ) {
        if ( empty( $status_message ) ) {
            $status_message = '<p>' . $status_message . '</p>';
        }
        return json_encode(
            array(
                'status' => $status,
                'status_message' => $status_message,
                'shortcode_name' => $shortcodename,
                'data' => $data,
            )
        );
    }


    /**
     * Admin AJAX handler
     */
    function handle_ajax() {

        global $wpdb;

        $post = stripslashes_deep( $_POST );
        $id = isset( $post['id'] ) ? (int) $post['id'] : 0;
        $method = isset( $post['method'] ) ? $post['method'] : '';

        // load
        if ( 'load' == $method ) {
            $shortcodename = $wpdb->get_var( "SELECT name FROM `{$wpdb->prefix}shortcodeexpress` WHERE id = '$id' LIMIT 1" );
            $content = $wpdb->get_var( "SELECT content FROM `{$wpdb->prefix}shortcodeexpress` WHERE id = '$id' LIMIT 1" );
            echo $this->json_response( 'ok', null, $shortcodename, $content );
        }
        
        // view shortcode
        elseif ('view' == $method ) {
            $shortcodename = $wpdb->get_var( "SELECT name FROM `{$wpdb->prefix}shortcodeexpress` WHERE id = '$id' LIMIT 1" );
            $content = $wpdb->get_var( "SELECT content FROM `{$wpdb->prefix}shortcodeexpress` WHERE id = '$id' LIMIT 1" );
            echo $this->json_response( 'ok', null, $shortcodename, $content );
        }
        
        // add 
        elseif ( 'add' == $method ) {
            $name = trim( $post['name'] );

            if ( !preg_match( '/^[A-Za-z0-9\-_]+$/', $name ) ) {
                echo $this->json_response( 'error', 'ERROR - Please use only alphanumeric characters, hyphens, and underscores.' );
            }
            else {
                $wpdb->insert( $wpdb->prefix . 'shortcodeexpress', array( 'name' => $name ) );
                echo $this->json_response( 'ok', 'Shortcode added', $name, array( 'id' => (int) $wpdb->insert_id ) );
            }
        }

        // edit
        elseif ( 'edit' == $method ) {
            $sql = $wpdb->prepare( "UPDATE {$wpdb->prefix}shortcodeexpress SET content = %s WHERE id = %d LIMIT 1", $post['content'], $id );
            $wpdb->query( $sql );
            $shortcodename = $wpdb->get_var( "SELECT name FROM `{$wpdb->prefix}shortcodeexpress` WHERE id = '$id' LIMIT 1" );
            echo $this->json_response( 'ok', 'Shortcode saved', $shortcodename );
        }

        // delete
        elseif ( 'delete' == $method ) {
            $wpdb->query( "DELETE FROM `{$wpdb->prefix}shortcodeexpress` WHERE id = '$id' LIMIT 1" );
            echo $this->json_response( 'ok', 'Shortcode deleted.' );
        }

        exit;
    }


    /**
     * Settings page HTML
     */
    function admin_page() {
        global $wpdb;
?>
<link href="<?php echo SHORTCODE_EXPRESS_URL; ?>/style.css" rel="stylesheet" />
<link href="<?php echo SHORTCODE_EXPRESS_URL; ?>/js/codemirror/lib/codemirror.css" rel="stylesheet" />
<link href="<?php echo SHORTCODE_EXPRESS_URL; ?>/js/select2/select2.css" rel="stylesheet" />
<script src="<?php echo SHORTCODE_EXPRESS_URL; ?>/js/select2/select2.min.js"></script>
<script src="<?php echo SHORTCODE_EXPRESS_URL; ?>/js/codemirror/lib/codemirror.js"></script>
<script src="<?php echo SHORTCODE_EXPRESS_URL; ?>/js/codemirror/mode/xml/xml.js"></script>
<script src="<?php echo SHORTCODE_EXPRESS_URL; ?>/js/codemirror/mode/javascript/javascript.js"></script>
<script src="<?php echo SHORTCODE_EXPRESS_URL; ?>/js/codemirror/mode/css/css.js"></script>
<script src="<?php echo SHORTCODE_EXPRESS_URL; ?>/js/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script src="<?php echo SHORTCODE_EXPRESS_URL; ?>/js/codemirror/mode/clike/clike.js"></script>
<script src="<?php echo SHORTCODE_EXPRESS_URL; ?>/js/codemirror/mode/php/php.js"></script>
<script src="<?php echo SHORTCODE_EXPRESS_URL; ?>/js/admin.js"></script>
<div class="wrap">
    <h2>Shortcode Express</h2>

    <div id="shortcode-response" class="updated"></div>

    <div style="margin:15px 0">
        <select id="sce-select">
            <option value="">Choose a shortcode</option>
            <?php $results = $wpdb->get_results( "SELECT id, name FROM `{$wpdb->prefix}shortcodeexpress` ORDER BY name ASC" ); ?>
            <?php foreach ( $results as $result ) : ?>
            <option value="<?php echo $result->id; ?>"><?php echo $result->name; ?></option>
            <?php endforeach; ?>
        </select>
        <input type="submit" class="button-primary" id="view-shortcode" value="View it" />
        - or -
        <input id="shortcode-name" type="text" placeholder="Type shortcode name" value="" />
        <a id="add-shortcode" class="button">Add New</a>
    </div>
    <div id="shortcode-area" class="hidden">
    <div>Shortcode content :</div>
        <div><textarea id="shortcode-content"></textarea></div>
        <div id="save-area">
            <input type="submit" class="button-primary" id="edit-shortcode" value="Save Changes" />
            or <a id="delete-shortcode" href="javascript:;">Delete</a>
        </div>
        <div id="loading-area" class="hidden">
            <span id="loading"></span> Loading, please wait...
        </div>
    </div>
</div>
<?php
    }
}

     /* ------------------------------------- */
     /* Shortcode Express into pages and posts */
     /* ------------------------------------- */

add_action('media_buttons','add_sce_select',11);
function add_sce_select(){
            
            global $wpdb;
            echo '<select id="sce_select">';
            echo '<option value="">Shortcode Express</option>';
            $results = $wpdb->get_results( "SELECT id, name FROM `{$wpdb->prefix}shortcodeexpress` ORDER BY id ASC" ); 
            foreach ( $results as $result ) : 
            echo '<option value=\'[sce name="'.$result->name.'"]\'>ID'.$result->id.'- '.$result->name.'</option>';
            endforeach;
            echo '</select>';

}

add_action('admin_head', 'shortcode_express_button_js');
function shortcode_express_button_js() {
        echo '<script type="text/javascript">
        jQuery(document).ready(function(){
           jQuery("#sce_select").change(function() {
                          send_to_editor(jQuery("#sce_select :selected").val());
                          return false;
                });
        });
        </script>';
}

     /* ------------------------------------- */
     /* Shortcode Express Widget */
     /* ------------------------------------- */

class shortcode_express_widget extends WP_Widget {
    
    // Constructor
    function shortcode_express_widget() {
        parent::WP_Widget(false, $name=__('Shortcode Express', 'shortcode_express_widget'));
    }
    
    // Widget creation
    function form($instance) {
        /* .... */
        // Check value
        if ($instance) {
            $title = esc_attr($instance['title']);
            $select = esc_attr($instance['select']);
        } else {
            $title = '';
            $select = '';
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'shortcode_express_widget'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('select'); ?>"><?php _e('Select shortcode', 'shortcode_express_widget'); ?></label>
            <select name="<?php echo $this->get_field_name('select'); ?>" id="<?php echo $this->get_field_id('select'); ?>" class="widefat">
                <?php                               
            
                global $wpdb;
                echo '<option value="">Shortcode Express</option>';
                $options = $wpdb->get_results( "SELECT id, name FROM `{$wpdb->prefix}shortcodeexpress` ORDER BY id ASC" ); 
                foreach ( $options as $option ) : 
                echo '<option value=\'[sce name="'.$option->name.'"]\' ', $select == esc_attr('[sce name="'.$option->name.'"]') ? ' selected="selected"' : '', '>ID'.$option->id.'- '.$option->name.'</option>';
                endforeach;                       
                
                ?>
            </select>
        </p>
        <?php
    }
    
    // Widget update
    function update($new_instance, $old_instance) {
        /* .... */
        $instance = $old_instance;
        // Fields
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['select'] = strip_tags($new_instance['select']);
        return $instance;
    }
    
    // Widget display
    function widget($args, $instance) {
        /* .... */
        extract($args);
        
        // these are the widget options
        $title = apply_filters( 'widget_title', $instance['title'] );
        $select = apply_filters( 'shortcode_express_widget', $instance['select'] );
        echo $before_widget;
        
        // Display the widget
        echo '<div class="widget_text wp_widget_plugin_box">';
        
        // Check if title is set
        if ($title) {
            echo $before_title . $title . $after_title; 
        }
        
        // Check if select is set
        if ($select) {
            echo '<div class="wp_widget_plugin_textarea">'.$select.'</div>';
        }        
        
        echo '</div>';
        echo $after_widget;
        
    }
}

// Register and load the widget
function shortcode_express_load_widget() {
    register_widget( 'shortcode_express_widget' );
 
// Allow to execute shortcodes on shortcode_express_widget
    add_filter('shortcode_express_widget', 'do_shortcode');
}
    add_action( 'widgets_init', 'shortcode_express_load_widget' );
