<?php
/**
 * Plugin Name: Simple To-Do List
 * Description: A simple WordPress plugin to manage a to-do list with enhanced features, including statuses, categories, and tags.
 * Version: 1.9
 * Author: SitesByYogi
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Register the custom post type 'todo'
function todo_register_post_type() {
    register_post_type('todo', array(
        'labels' => array(
            'name' => __('Tasks List'),
            'singular_name' => __('Task'),
            'add_new' => __('Add New Task'),
            'add_new_item' => __('Add New Task'),
            'edit_item' => __('Edit Task'),
            'new_item' => __('New Task'),
            'view_item' => __('View Task'),
            'search_items' => __('Search Tasks'),
            'not_found' => __('No Tasks found'),
            'not_found_in_trash' => __('No To-Do Items found in Trash'),
            'all_items' => __('All Tasks'),
        ),
        'public' => false,
        'show_ui' => true,  // Display in admin dashboard
        'supports' => array('title'),
        'menu_icon' => 'dashicons-list-view',
        'has_archive' => false,
        'rewrite' => false,
    ));
}
add_action('init', 'todo_register_post_type');

// Register custom taxonomies for Categories and Tags
function todo_register_taxonomies() {
    // Register Categories
    register_taxonomy('todo_category', 'todo', array(
        'labels' => array(
            'name' => __('Task Categories'),
            'singular_name' => __('Task Category'),
            'search_items' => __('Search Task Categories'),
            'all_items' => __('All Task Categories'),
            'edit_item' => __('Edit Task Category'),
            'update_item' => __('Update Task Category'),
            'add_new_item' => __('Add New Task Category'),
            'new_item_name' => __('New Task Category Name'),
            'menu_name' => __('Categories'),
        ),
        'hierarchical' => true, // Category-like behavior
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'task-category'),
    ));

    // Register Tags
    register_taxonomy('todo_tag', 'todo', array(
        'labels' => array(
            'name' => __('Task Tags'),
            'singular_name' => __('Task Tag'),
            'search_items' => __('Search Task Tags'),
            'popular_items' => __('Popular Task Tags'),
            'all_items' => __('All Task Tags'),
            'edit_item' => __('Edit Task Tag'),
            'update_item' => __('Update Task Tag'),
            'add_new_item' => __('Add New Task Tag'),
            'new_item_name' => __('New Task Tag Name'),
            'separate_items_with_commas' => __('Separate task tags with commas'),
            'add_or_remove_items' => __('Add or remove task tags'),
            'choose_from_most_used' => __('Choose from the most used task tags'),
            'not_found' => __('No task tags found'),
            'menu_name' => __('Tags'),
        ),
        'hierarchical' => false, // Tag-like behavior
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'task-tag'),
    ));
}
add_action('init', 'todo_register_taxonomies');

// Enqueue scripts and styles for the admin area
function todo_enqueue_admin_scripts($hook) {
    if ('edit.php?post_type=todo' != $hook && 'post-new.php?post_type=todo' != $hook && 'post.php' != $hook) {
        return;
    }
    wp_enqueue_script('jquery-ui-datepicker'); // Enqueue jQuery UI Datepicker
    wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css'); // Enqueue jQuery UI CSS
    wp_enqueue_script('todo-admin-js', plugin_dir_url(__FILE__) . 'admin/js/todo-admin.js', array('jquery', 'jquery-ui-datepicker'), '1.0', true);
    wp_enqueue_style('todo-admin-css', plugin_dir_url(__FILE__) . 'admin/css/todo-admin.css');
}
add_action('admin_enqueue_scripts', 'todo_enqueue_admin_scripts');

// Add Meta Boxes for Due Dates, Priorities, Status, Categories, Tags, and Descriptions
function todo_add_meta_boxes() {
    add_meta_box('todo_due_date', __('Due Date', 'simple-todo-list'), 'todo_due_date_callback', 'todo', 'side');
    add_meta_box('todo_priority', __('Priority', 'simple-todo-list'), 'todo_priority_callback', 'todo', 'side');
    add_meta_box('todo_status', __('Status', 'simple-todo-list'), 'todo_status_callback', 'todo', 'side');
    add_meta_box('todo_description', __('Short Description', 'simple-todo-list'), 'todo_description_callback', 'todo', 'normal');
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

function todo_status_callback($post) {
    // Define the fixed status options
    $statuses = array('Pending', 'Waiting', 'Complete', 'Abandoned');
    $current_status = get_post_meta($post->ID, '_todo_status', true);

    echo '<select name="todo_status" id="todo_status" class="widefat">';
    foreach ($statuses as $status) {
        $selected = ($current_status === $status) ? 'selected' : '';
        echo '<option value="' . esc_attr($status) . '" ' . $selected . '>' . esc_html($status) . '</option>';
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
    if (array_key_exists('todo_status', $_POST)) { // Save Status
        update_post_meta($post_id, '_todo_status', sanitize_text_field($_POST['todo_status']));
    }
    if (array_key_exists('todo_description', $_POST)) {
        update_post_meta($post_id, '_todo_description', sanitize_textarea_field($_POST['todo_description']));
    }
}
add_action('save_post', 'todo_save_meta_boxes');

// Add custom columns to the To-Do List admin table
function todo_custom_columns($columns) {
    $columns['todo_status'] = __('Status', 'simple-todo-list');
    return $columns;
}
add_filter('manage_todo_posts_columns', 'todo_custom_columns');

// Populate custom columns in the To-Do List admin table
function todo_custom_column_content($column, $post_id) {
    if ($column == 'todo_status') {
        $status = get_post_meta($post_id, '_todo_status', true);
        echo esc_html($status);
    }
}
add_action('manage_todo_posts_custom_column', 'todo_custom_column_content', 10, 2);

// Add custom filters by status and priority to the admin list table
function todo_add_filters() {
    global $typenow;
    if ($typenow == 'todo') {
        // Filter by Status
        $selected_status = isset($_GET['todo_status_filter']) ? $_GET['todo_status_filter'] : '';
        $statuses = array('Pending', 'Waiting', 'Complete', 'Abandoned');
        
        echo '<select name="todo_status_filter" id="todo_status_filter">';
        echo '<option value="">' . __('All Statuses', 'simple-todo-list') . '</option>';
        foreach ($statuses as $status) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($status),
                selected($selected_status, $status, false),
                esc_html($status)
            );
        }
        echo '</select>';

        // Filter by Priority
        $selected_priority = isset($_GET['todo_priority_filter']) ? $_GET['todo_priority_filter'] : '';
        $priorities = array('Low', 'Medium', 'High');
        
        echo '<select name="todo_priority_filter" id="todo_priority_filter">';
        echo '<option value="">' . __('All Priorities', 'simple-todo-list') . '</option>';
        foreach ($priorities as $priority) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($priority),
                selected($selected_priority, $priority, false),
                esc_html($priority)
            );
        }
        echo '</select>';
    }
}
add_action('restrict_manage_posts', 'todo_add_filters');

// Modify the query to filter by status and priority
function todo_filter_posts($query) {
    global $pagenow, $typenow;
    if ($typenow == 'todo' && $pagenow == 'edit.php') {
        // Filter by Status
        if (isset($_GET['todo_status_filter']) && $_GET['todo_status_filter'] != '') {
            $query->query_vars['meta_key'] = '_todo_status';
            $query->query_vars['meta_value'] = sanitize_text_field($_GET['todo_status_filter']);
        }
        // Filter by Priority
        if (isset($_GET['todo_priority_filter']) && $_GET['todo_priority_filter'] != '') {
            $query->query_vars['meta_key'] = '_todo_priority';
            $query->query_vars['meta_value'] = sanitize_text_field($_GET['todo_priority_filter']);
        }
    }
}
add_action('pre_get_posts', 'todo_filter_posts');

// Add a submenu page for Status under the To-Do List menu
function todo_add_status_submenu() {
    add_submenu_page(
        'edit.php?post_type=todo', // Parent slug
        __('Status Dashboard', 'simple-todo-list'), // Page title
        __('Status', 'simple-todo-list'), // Menu title
        'manage_options', // Capability
        'todo-status', // Menu slug
        'todo_status_page_callback' // Callback function
    );
}
add_action('admin_menu', 'todo_add_status_submenu');

// Add submenu item for Pending tasks
function todo_add_pending_submenu() {
    add_submenu_page(
        'edit.php?post_type=todo', // Parent slug
        __('Pending Tasks', 'simple-todo-list'), // Page title
        __('Pending Tasks', 'simple-todo-list'), // Menu title
        'manage_options', // Capability
        'edit.php?post_type=todo&todo_status_filter=Pending', // Menu slug (direct link to filtered view)
        null, // Callback function not needed for direct link
        2 // Position
    );
}
add_action('admin_menu', 'todo_add_pending_submenu');

// Callback function for the Status admin page
function todo_status_page_callback() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('To-Do Status Dashboard', 'simple-todo-list'); ?></h1>

        <?php
        // Display existing statuses (fixed, not deletable)
        $statuses = array('Pending', 'Waiting', 'Complete', 'Abandoned');

        if (!empty($statuses)) {
            echo '<h2>' . esc_html__('Current Statuses', 'simple-todo-list') . '</h2>';
            echo '<ul>';
            foreach ($statuses as $status) {
                echo '<li>' . esc_html($status) . '</li>';
            }
            echo '</ul>';
        }
        ?>
    </div>
    <?php
}
?>
