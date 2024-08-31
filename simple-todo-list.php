<?php
/**
 * Plugin Name: Simple To-Do List
 * Description: A simple WordPress plugin to manage a to-do list with enhanced features, including categories.
 * Version: 1.5
 * Author: Your Name
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Register the custom post type 'todo'
function todo_register_post_type() {
    register_post_type('todo', array(
        'labels' => array(
            'name' => __('To-Do List'),
            'singular_name' => __('To-Do'),
            'add_new' => __('Add New To-Do'),
            'add_new_item' => __('Add New To-Do Item'),
            'edit_item' => __('Edit To-Do Item'),
            'new_item' => __('New To-Do Item'),
            'view_item' => __('View To-Do Item'),
            'search_items' => __('Search To-Do Items'),
            'not_found' => __('No To-Do Items found'),
            'not_found_in_trash' => __('No To-Do Items found in Trash'),
            'all_items' => __('All To-Do Items'),
        ),
        'public' => false,
        'show_ui' => true,  // Display in admin dashboard
        'supports' => array('title'),
        'menu_icon' => 'dashicons-list-view',
        'has_archive' => false,
        'rewrite' => false,
    ));
    
    // Register custom taxonomy for Categories
    register_taxonomy('todo_category', 'todo', array(
        'labels' => array(
            'name' => __('To-Do Categories'),
            'singular_name' => __('To-Do Category'),
            'search_items' => __('Search To-Do Categories'),
            'all_items' => __('All To-Do Categories'),
            'parent_item' => __('Parent To-Do Category'),
            'parent_item_colon' => __('Parent To-Do Category:'),
            'edit_item' => __('Edit To-Do Category'),
            'update_item' => __('Update To-Do Category'),
            'add_new_item' => __('Add New To-Do Category'),
            'new_item_name' => __('New To-Do Category Name'),
            'menu_name' => __('Categories'),
        ),
        'hierarchical' => true, // Set to true for category-like behavior
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'todo-category'),
    ));
    
    // Register custom taxonomy for Tags
    register_taxonomy('todo_tag', 'todo', array(
        'labels' => array(
            'name' => __('To-Do Tags'),
            'singular_name' => __('To-Do Tag'),
            'search_items' => __('Search To-Do Tags'),
            'popular_items' => __('Popular To-Do Tags'),
            'all_items' => __('All To-Do Tags'),
            'edit_item' => __('Edit To-Do Tag'),
            'update_item' => __('Update To-Do Tag'),
            'add_new_item' => __('Add New To-Do Tag'),
            'new_item_name' => __('New To-Do Tag Name'),
            'separate_items_with_commas' => __('Separate to-do tags with commas'),
            'add_or_remove_items' => __('Add or remove to-do tags'),
            'choose_from_most_used' => __('Choose from the most used to-do tags'),
            'not_found' => __('No to-do tags found'),
            'menu_name' => __('Tags'),
        ),
        'hierarchical' => false, // Set to false for tag-like behavior
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'todo-tag'),
    ));
}
add_action('init', 'todo_register_post_type');

// Enqueue scripts and styles for the admin area
function todo_enqueue_admin_scripts($hook) {
    if ('edit.php?post_type=todo' != $hook && 'post-new.php?post_type=todo' != $hook && 'post.php' != $hook && $hook != 'todo_page_todo-categories') {
        return;
    }
    wp_enqueue_script('jquery-ui-datepicker'); // Enqueue jQuery UI Datepicker
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css'); // Enqueue jQuery UI CSS
    wp_enqueue_script('todo-admin-js', plugin_dir_url(__FILE__) . 'admin/js/todo-admin.js', array('jquery', 'jquery-ui-datepicker'), '1.0', true);
    wp_enqueue_style('todo-admin-css', plugin_dir_url(__FILE__) . 'admin/css/todo-admin.css');

    // Add JavaScript to handle adding new categories dynamically
    wp_enqueue_script('todo-category-js', plugin_dir_url(__FILE__) . 'admin/js/todo-category.js', array('jquery'), '1.0', true);
    wp_localize_script('todo-category-js', 'todoCategory', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('add_new_category')
    ));
}
add_action('admin_enqueue_scripts', 'todo_enqueue_admin_scripts');

// Add Meta Boxes for Due Dates, Priorities, Categories, and Descriptions
function todo_add_meta_boxes() {
    add_meta_box('todo_due_date', __('Due Date', 'simple-todo-list'), 'todo_due_date_callback', 'todo', 'side');
    add_meta_box('todo_priority', __('Priority', 'simple-todo-list'), 'todo_priority_callback', 'todo', 'side');
    add_meta_box('todo_category', __('Status', 'simple-todo-list'), 'todo_category_callback', 'todo', 'side');
    add_meta_box('todo_description', __('Description', 'simple-todo-list'), 'todo_description_callback', 'todo', 'normal');
}
add_action('add_meta_boxes', 'todo_add_meta_boxes');

function todo_due_date_callback($post) {
    $due_date = get_post_meta($post->ID, '_todo_due_date', true);
    echo '<input type="text" name="todo_due_date" id="todo_due_date" value="' . esc_attr($due_date) . '" class="widefat datepicker">';
}

function todo_priority_callback($post) {
    $priority = get_post_meta($post->ID, '_todo_priority', true);
    $options = array('Low', 'Medium', 'High');
    echo '<select name="todo_priority" id="todo_priority" class="widefat">';
    foreach ($options as $option) {
        $selected = ($priority === $option) ? 'selected' : '';
        echo '<option value="' . esc_attr($option) . '" ' . $selected . '>' . esc_html($option) . '</option>';
    }
    echo '</select>';
}

function todo_category_callback($post) {
    // Fetch all existing categories from the options
    $categories = get_option('todo_categories', array());
    $current_category = get_post_meta($post->ID, '_todo_category', true);

    // Display the dropdown and input to add new categories
    echo '<select name="todo_category" id="todo_category" class="widefat">';
    echo '<option value="">' . __('Select a status', 'simple-todo-list') . '</option>';
    foreach ($categories as $category) {
        $selected = ($current_category === $category) ? 'selected' : '';
        echo '<option value="' . esc_attr($category) . '" ' . $selected . '>' . esc_html($category) . '</option>';
    }
    echo '</select>';

}

// Add Meta Box Callback for Short Description
function todo_description_callback($post) {
    $description = get_post_meta($post->ID, '_todo_description', true);
    echo '<textarea name="todo_description" id="todo_description" rows="5" class="widefat">' . esc_textarea($description) . '</textarea>';
}

// Save Meta Box Data
function todo_save_meta_boxes($post_id) {
    if (array_key_exists('todo_due_date', $_POST)) {
        update_post_meta($post_id, '_todo_due_date', sanitize_text_field($_POST['todo_due_date']));
    }
    if (array_key_exists('todo_priority', $_POST)) {
        update_post_meta($post_id, '_todo_priority', sanitize_text_field($_POST['todo_priority']));
    }
    if (array_key_exists('todo_category', $_POST)) {
        update_post_meta($post_id, '_todo_category', sanitize_text_field($_POST['todo_category']));
    }
    if (array_key_exists('todo_description', $_POST)) {
        update_post_meta($post_id, '_todo_description', sanitize_textarea_field($_POST['todo_description']));
    }
}
add_action('save_post', 'todo_save_meta_boxes');

// Add custom columns to the To-Do List admin table
function todo_custom_columns($columns) {
    $columns['todo_category'] = __('Status', 'simple-todo-list');
    return $columns;
}
add_filter('manage_todo_posts_columns', 'todo_custom_columns');

// Populate custom columns in the To-Do List admin table
function todo_custom_column_content($column, $post_id) {
    if ($column == 'todo_category') {
        $category = get_post_meta($post_id, '_todo_category', true);
        echo esc_html($category);
    }
}
add_action('manage_todo_posts_custom_column', 'todo_custom_column_content', 10, 2);

// Add a submenu page for Categories under the To-Do List menu
function todo_add_categories_submenu() {
    add_submenu_page(
        'edit.php?post_type=todo', // Parent slug
        __('Status', 'simple-todo-list'), // Page title
        __('Status', 'simple-todo-list'), // Menu title
        'manage_options', // Capability
        'todo-categories', // Menu slug
        'todo_categories_page_callback' // Callback function
    );
}
add_action('admin_menu', 'todo_add_categories_submenu');

// Callback function for the Categories admin page
function todo_categories_page_callback() {
    // Check if user has permission to manage options
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('To-Do Categories', 'simple-todo-list'); ?></h1>

        <form method="post" action="">
            <?php wp_nonce_field('add_category_nonce_action', 'add_category_nonce'); ?>
            <input type="text" name="new_category" placeholder="<?php esc_attr_e('Enter new category...', 'simple-todo-list'); ?>" required>
            <input type="submit" name="add_category" class="button button-primary" value="<?php esc_attr_e('Add Category', 'simple-todo-list'); ?>">
        </form>

        <?php
        // Handle adding a new category
        if (isset($_POST['add_category']) && isset($_POST['new_category'])) {
            // Verify nonce for security
            if (!isset($_POST['add_category_nonce']) || !wp_verify_nonce($_POST['add_category_nonce'], 'add_category_nonce_action')) {
                wp_die(__('Security check failed.', 'simple-todo-list'));
            }
            
            $new_category = sanitize_text_field($_POST['new_category']);
            $categories = get_option('todo_categories', array());
            
            if (!in_array($new_category, $categories)) {
                $categories[] = $new_category;
                update_option('todo_categories', $categories);
                echo '<div class="updated notice"><p>' . esc_html__('Category added successfully.', 'simple-todo-list') . '</p></div>';
            } else {
                echo '<div class="error notice"><p>' . esc_html__('Category already exists.', 'simple-todo-list') . '</p></div>';
            }
        }

        // Display existing categories as clickable links
        $categories = get_option('todo_categories', array());

        if (!empty($categories)) {
            echo '<h2>' . esc_html__('Existing Categories', 'simple-todo-list') . '</h2>';
            echo '<ul>';
            foreach ($categories as $category) {
                $category_link = add_query_arg(array(
                    'post_type' => 'todo',
                    'todo_category_filter' => urlencode($category)
                ), admin_url('edit.php'));
                
                // Add nonce and capabilities check to delete link
                $delete_link = wp_nonce_url(
                    add_query_arg(
                        array(
                            'page' => 'todo-categories',
                            'delete_category' => urlencode($category)
                        ), 
                        admin_url('admin.php')
                    ),
                    'delete_category_nonce_action',
                    'delete_category_nonce'
                );
                
                echo '<li><a href="' . esc_url($category_link) . '">' . esc_html($category) . '</a> 
                <a href="' . esc_url($delete_link) . '" class="button button-secondary">' . esc_html__('Delete', 'simple-todo-list') . '</a></li>';
            }
            echo '</ul>';
        }

        // Handle deleting categories
        if (isset($_GET['delete_category']) && isset($_GET['delete_category_nonce'])) {
            // Check permission and verify nonce
            if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['delete_category_nonce'], 'delete_category_nonce_action')) {
                wp_die(__('Sorry, you are not allowed to access this page.', 'simple-todo-list'));
            }

            $delete_category = sanitize_text_field(urldecode($_GET['delete_category']));
            $categories = array_filter($categories, function($cat) use ($delete_category) {
                return $cat !== $delete_category;
            });
            update_option('todo_categories', $categories);
            echo '<div class="updated notice"><p>' . esc_html__('Category deleted successfully.', 'simple-todo-list') . '</p></div>';
        }
        ?>
    </div>
    <?php
}

// AJAX Handler for Adding New Category
function todo_add_new_category() {
    check_ajax_referer('add_new_category', 'nonce');

    if (isset($_POST['new_category'])) {
        $new_category = sanitize_text_field($_POST['new_category']);
        $categories = get_option('todo_categories', array());

        if (!in_array($new_category, $categories)) {
            $categories[] = $new_category;
            update_option('todo_categories', $categories);
            wp_send_json_success($new_category);
        } else {
            wp_send_json_error('Category already exists.');
        }
    }

    wp_send_json_error('Invalid category.');
}
add_action('wp_ajax_todo_add_new_category', 'todo_add_new_category');


// Add custom filters to the admin list table
function todo_add_filters_to_admin() {
    global $typenow;

    if ($typenow == 'todo') {
        // Filter by Priority
        $selected_priority = isset($_GET['todo_priority_filter']) ? $_GET['todo_priority_filter'] : '';
        $priorities = array('Low', 'Medium', 'High');
        echo '<select name="todo_priority_filter" id="todo_priority_filter">';
        echo '<option value="">' . __('All Priorities', 'simple-todo-list') . '</option>';
        foreach ($priorities as $priority) {
            echo '<option value="' . esc_attr($priority) . '" ' . selected($selected_priority, $priority, false) . '>' . esc_html($priority) . '</option>';
        }
        echo '</select>';

        // Filter by Category
        $selected_category = isset($_GET['todo_category_filter']) ? $_GET['todo_category_filter'] : '';
        $categories = get_posts(array('post_type' => 'todo', 'posts_per_page' => -1, 'fields' => 'ids'));
        $unique_categories = array_unique(array_map(function ($post_id) {
            return get_post_meta($post_id, '_todo_category', true);
        }, $categories));

        echo '<select name="todo_category_filter" id="todo_category_filter">';
        echo '<option value="">' . __('All Categories', 'simple-todo-list') . '</option>';
        foreach ($unique_categories as $category) {
            if (!empty($category)) {
                echo '<option value="' . esc_attr($category) . '" ' . selected($selected_category, $category, false) . '>' . esc_html($category) . '</option>';
            }
        }
        echo '</select>';
    }
}
add_action('restrict_manage_posts', 'todo_add_filters_to_admin');

// Modify the query to filter by category and priority
function todo_filter_posts_by_meta($query) {
    global $pagenow;
    $typenow = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';

    if ($typenow == 'todo' && is_admin() && $pagenow == 'edit.php') {
        if (!empty($_GET['todo_priority_filter'])) {
            $query->query_vars['meta_key'] = '_todo_priority';
            $query->query_vars['meta_value'] = sanitize_text_field($_GET['todo_priority_filter']);
        }

        if (!empty($_GET['todo_category_filter'])) {
            $query->query_vars['meta_key'] = '_todo_category';
            $query->query_vars['meta_value'] = sanitize_text_field($_GET['todo_category_filter']);
        }
    }
}
add_action('pre_get_posts', 'todo_filter_posts_by_meta');



// Create a shortcode to display the to-do list
function todo_shortcode() {
    ob_start();
    ?>
    <div id="todo-list-app">
        <h2><?php esc_html_e('My To-Do List', 'simple-todo-list'); ?></h2>
        <input type="text" id="new-todo-item" placeholder="<?php esc_attr_e('Add new item...', 'simple-todo-list'); ?>">
        <button id="add-todo"><?php esc_html_e('Add', 'simple-todo-list'); ?></button>
        <ul id="todo-items">
            <?php
            $todos = get_posts(array('post_type' => 'todo', 'numberposts' => -1));
            foreach ($todos as $todo) {
                echo '<li data-id="' . esc_attr($todo->ID) . '">' . esc_html($todo->post_title) . ' <button class="edit-todo">Edit</button> <button class="remove-todo">Remove</button></li>';
            }
            ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('simple_todo_list', 'todo_shortcode');