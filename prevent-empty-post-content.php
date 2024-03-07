<?php
/*
Plugin Name: Prevent Empty Post Content
Description: Prevents posts from being published if they have no content.
Version: 1.0
Author: Abdullah Gamal
Author URI: https://abdullah-g.com
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Hook into the save_post action
add_action('save_post', 'prevent_empty_post_content');

function prevent_empty_post_content($post_id) {
    // Check if this is an autosave, a revision, or an AJAX request
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }

    // Check if it's a new post or an existing one being updated
    $is_new_post = get_post_status($post_id) === 'auto-draft';

    // Get the post object
    $post = get_post($post_id);

    // Check if the post type is 'post' and the current user can edit posts
    if ($post->post_type === 'post' && current_user_can('edit_post', $post_id)) {
        // Check if the post is being published
        if ($post->post_status === 'publish') {
            // Check if the post content is empty
            if (empty($post->post_content)) {
                // Set the post status to draft
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_status' => 'draft'
                ));

                // Display an error message only if it's not a new post
                if (!$is_new_post) {
                    add_filter('redirect_post_location', function($location) {
                        return add_query_arg('prevent_empty_content_error', '1', $location);
                    });
                }
            }
        }
    }
}

// Add admin notice if post content is empty
add_action('admin_notices', 'prevent_empty_post_content_admin_notice');

function prevent_empty_post_content_admin_notice() {
    if (isset($_GET['prevent_empty_content_error']) && $_GET['prevent_empty_content_error'] === '1') {
        ?>
        <div class="error">
            <p><?php _e('Post content cannot be empty. The post has been saved as draft.', 'prevent-empty-post-content'); ?></p>
        </div>
        <?php
    }
}
