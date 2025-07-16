<?php
/*
Plugin Name: Find Posts by Title
Description: Adds a clean admin interface to search posts by title with options to edit in Gutenberg or Classic editor.
Version: 1.2
Author: Tim Coe
Requires at least: 5.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Register the submenu under "Posts"
add_action('admin_menu', function() {
	add_submenu_page(
		'edit.php', // parent slug (Posts menu)
		'Find Posts by Title', // page title
		'Find by Title',       // menu title
		'edit_posts',          // capability
		'find-by-title',       // menu slug
		'find_posts_by_title_render_page' // callback
	);
});

// Render the custom admin page
function find_posts_by_title_render_page() {
	if (!current_user_can('edit_posts')) return;

	global $wpdb;

	$search_term = '';
	if (isset($_GET['s']) && isset($_GET['find_posts_by_title_nonce'])) {
		if (!wp_verify_nonce($_GET['find_posts_by_title_nonce'], 'find_posts_by_title_action')) {
			wp_die('Security check failed');
		}
		$search_term = sanitize_text_field(wp_unslash($_GET['s']));
	}

	echo '<div class="wrap">';
	echo '<h1>Find Posts by Title</h1>';
	echo '<form method="get" style="margin-top: 20px; margin-bottom: 20px;">';
	echo '<input type="hidden" name="page" value="find-by-title" />';
	wp_nonce_field('find_posts_by_title_action', 'find_posts_by_title_nonce');
	echo '<input type="text" name="s" value="' . esc_attr($search_term) . '" placeholder="Enter title keyword..." style="width: 300px;" />';
	echo ' <input type="submit" class="button button-primary" value="Search">';
	echo '</form>';

	if ($search_term) {
		$like = '%' . $wpdb->esc_like($search_term) . '%';
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' AND post_title LIKE %s ORDER BY post_date DESC",
				$like
			)
		);

		if ($results) {
			echo '<h2>Results</h2><ul style="margin-top: 15px;">';
			foreach ($results as $post) {
				$gutenberg_url = admin_url('post.php?post=' . $post->ID . '&action=edit&gutenberg-editor');
				$classic_url   = admin_url('post.php?post=' . $post->ID . '&action=edit&classic-editor');

				echo '<li style="margin-bottom: 10px;">';
				echo '<strong>' . esc_html($post->post_title) . '</strong><br>';
				echo '<span style="margin-left: 10px; font-size: 13px;">';
				echo '<a href="' . esc_url($gutenberg_url) . '" target="_blank">Edit in Gutenberg</a> &nbsp;|&nbsp; ';
				echo '<a href="' . esc_url($classic_url) . '" target="_blank">Edit in Classic</a>';
				echo '</span>';
				echo '</li>';
			}
			echo '</ul>';
		} else {
			echo '<p>No posts found matching that title.</p>';
		}
	}

	echo '</div>';
}
