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
add_action('admin_menu', 'find_posts_by_title_admin_menu');

function find_posts_by_title_admin_menu() {
	$hook = add_submenu_page(
		'edit.php', // parent slug (Posts menu)
		'Find Posts by Title', // page title
		'Find by Title',       // menu title
		'edit_posts',          // capability
		'find-by-title',       // menu slug
		'find_posts_by_title_render_page' // callback
	);
	
	// Enqueue styles only on this page
	add_action('admin_print_styles-' . $hook, 'find_posts_by_title_admin_styles');
}

// Enqueue admin styles
function find_posts_by_title_admin_styles() {
	?>
	<style>
		.find-posts-search-form {
			margin: 20px 0;
			padding: 20px;
			background: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 5px;
		}
		.find-posts-search-input {
			width: 350px;
			padding: 8px 12px;
			border: 1px solid #ddd;
			border-radius: 3px;
			font-size: 14px;
		}
		.find-posts-results {
			margin-top: 20px;
		}
		.find-posts-result-item {
			background: #fff;
			border: 1px solid #e1e1e1;
			border-radius: 4px;
			padding: 15px;
			margin-bottom: 12px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			transition: box-shadow 0.2s ease;
		}
		.find-posts-result-item:hover {
			box-shadow: 0 2px 8px rgba(0,0,0,0.15);
		}
		.find-posts-post-title {
			color: #0073aa;
			font-size: 16px;
			margin-bottom: 8px;
			line-height: 1.4;
		}
		.find-posts-edit-links {
			display: block;
			margin-top: 8px;
		}
		.find-posts-edit-links a {
			display: inline-block;
			padding: 6px 12px;
			background: #0073aa;
			color: #fff;
			text-decoration: none;
			border-radius: 3px;
			font-size: 13px;
			margin-right: 8px;
			transition: background-color 0.2s ease;
		}
		.find-posts-edit-links a:hover {
			background: #005a87;
			color: #fff;
		}
		.find-posts-edit-links a:focus {
			box-shadow: 0 0 0 2px #005a87;
			outline: none;
		}
		.find-posts-no-results {
			background: #fff2cc;
			border: 1px solid #d4af37;
			padding: 15px;
			border-radius: 4px;
			color: #8a6914;
		}
	</style>
	<?php
}

// Render the custom admin page
function find_posts_by_title_render_page() {
	if (!current_user_can('edit_posts')) {
		return;
	}

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
	echo '<form method="get" class="find-posts-search-form">';
	echo '<input type="hidden" name="page" value="find-by-title" />';
	wp_nonce_field('find_posts_by_title_action', 'find_posts_by_title_nonce');
	echo '<input type="text" name="s" value="' . esc_attr($search_term) . '" placeholder="Enter title keyword..." class="find-posts-search-input" />';
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
			echo '<h2>Results</h2><div class="find-posts-results">';
			foreach ($results as $post) {
				// Standard WordPress edit URL (uses user's default editor)
				$edit_url = admin_url('post.php?post=' . $post->ID . '&action=edit');
				
				// Force Gutenberg editor URL
				$gutenberg_url = admin_url('post.php?post=' . $post->ID . '&action=edit');
				
				// Classic Editor URL (if Classic Editor plugin is active)
				$classic_url = admin_url('post.php?post=' . $post->ID . '&action=edit&classic-editor');

				echo '<div class="find-posts-result-item">';
				echo '<div class="find-posts-post-title">' . esc_html($post->post_title) . '</div>';
				echo '<div class="find-posts-edit-links">';
				
				// Always show Gutenberg option
				echo '<a href="' . esc_url($gutenberg_url) . '" target="_blank">Edit in Gutenberg</a>';
				
				// Show Classic Editor option if the plugin is active or if it's available
				if (is_plugin_active('classic-editor/classic-editor.php') || function_exists('the_gutenberg_project')) {
					echo '<a href="' . esc_url($classic_url) . '" target="_blank">Edit in Classic</a>';
				}
				
				echo '</div>';
				echo '</div>';
			}
			echo '</div>';
		} else {
			echo '<div class="find-posts-no-results">No posts found matching that title.</div>';
		}
	}

	echo '</div>';
}
