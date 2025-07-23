<?php
/*
Plugin Name: Find Posts by Title
Description: Adds a clean admin interface to search posts by title with options to edit in Gutenberg or Classic editor.
Version: 1.6
Author: Tim Coe
Requires at least: 5.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Register the submenus under "Posts" and "Pages"
add_action('admin_menu', 'find_posts_by_title_admin_menu');

function find_posts_by_title_admin_menu() {
	// Add submenu under Posts
	$hook_posts = add_submenu_page(
		'edit.php', // parent slug (Posts menu)
		'Find Posts by Title', // page title
		'Find by Title',       // menu title
		'edit_posts',          // capability
		'find-by-title',       // menu slug
		'find_posts_by_title_render_page' // callback
	);
	
	// Add submenu under Pages
	$hook_pages = add_submenu_page(
		'edit.php?post_type=page', // parent slug (Pages menu)
		'Find Pages by Title', // page title
		'Find by Title',       // menu title
		'edit_pages',          // capability
		'find-pages-by-title', // menu slug
		'find_pages_by_title_render_page' // callback
	);
	
	// Enqueue styles on both pages
	add_action('admin_enqueue_scripts', function($hook_suffix) use ($hook_posts, $hook_pages) {
		if ($hook_suffix === $hook_posts || $hook_suffix === $hook_pages) {
			find_posts_by_title_admin_styles();
		}
	});
}

// Enqueue admin styles
function find_posts_by_title_admin_styles() {
	?>
	<style>
		.find-posts-search-form {
			margin: 20px 0;
		}
		.find-posts-search-input {
			width: 350px;
			padding: 8px 12px;
			border: 1px solid #ddd;
			border-radius: 3px;
			font-size: 14px;
		}
		.find-posts-results-table {
			margin-top: 20px;
		}
		.find-posts-results-table .wp-list-table {
			border: 1px solid #c3c4c7;
		}
		.find-posts-results-table .wp-list-table td {
			padding: 9px 10px;
		}
		.find-posts-results-table .column-title {
			width: 100%;
		}
		.find-posts-results-table .wp-list-table .alternate {
			background-color: #f6f7f7;
		}
		.find-posts-results-table .wp-list-table tr:not(.alternate) {
			background-color: #fff;
		}
		.find-posts-title-link {
			font-weight: 600;
			font-size: 14px;
			line-height: 1.4em;
		}
		.find-posts-title-link:hover {
			color: #135e96;
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
		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['find_posts_by_title_nonce'])), 'find_posts_by_title_action')) {
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
			echo '<h2>Results</h2>';
			echo '<div class="find-posts-results-table">';
							echo '<table class="wp-list-table widefat fixed striped table-view-list posts">';
				echo '<thead>';
				echo '<tr>';
				echo '<th scope="col" class="manage-column column-title column-primary">Title</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				
				$row_class = '';
				foreach ($results as $index => $post) {
					// Alternate row colors
					$row_class = ($index % 2 == 0) ? '' : 'alternate';
					
					// Standard edit URLs
					$edit_url = admin_url('post.php?post=' . $post->ID . '&action=edit');
					$gutenberg_url = admin_url('post.php?post=' . $post->ID . '&action=edit&gutenberg-editor');
					$classic_url = admin_url('post.php?post=' . $post->ID . '&action=edit&classic-editor');
					$trash_url = wp_nonce_url(admin_url('post.php?post=' . $post->ID . '&action=trash'), 'trash-post_' . $post->ID);
					$preview_url = get_permalink($post->ID);

					echo '<tr class="' . esc_attr($row_class) . '">';
					echo '<td class="title column-title has-row-actions column-primary" data-colname="Title">';
					echo '<strong>';
					echo '<a class="row-title find-posts-title-link" href="' . esc_url($edit_url) . '" target="_blank" aria-label="Edit "' . esc_attr($post->post_title) . '"">';
					echo esc_html($post->post_title);
					echo '</a>';
					echo '</strong>';
					
										// WordPress-style row actions
					echo '<div class="row-actions">';
					$actions = array();
					
					$actions[] = '<span class="edit"><a href="' . esc_url($edit_url) . '" target="_blank" aria-label="Edit "' . esc_attr($post->post_title) . '"">' . __('Edit', 'find-posts-by-title') . '</a></span>';
					
					$actions[] = '<span class="gutenberg"><a href="' . esc_url($gutenberg_url) . '" target="_blank" aria-label="Edit "' . esc_attr($post->post_title) . '" in the Gutenberg editor">Gutenberg Editor</a></span>';
					
					// Show Classic Editor option if available
					if (is_plugin_active('classic-editor/classic-editor.php') || function_exists('the_gutenberg_project')) {
						$actions[] = '<span class="classic"><a href="' . esc_url($classic_url) . '" target="_blank" aria-label="Edit "' . esc_attr($post->post_title) . '" in Classic Editor">Classic Editor</a></span>';
					}
					
					$actions[] = '<span class="trash"><a href="' . esc_url($trash_url) . '" class="submitdelete" aria-label="Move "' . esc_attr($post->post_title) . '" to the Trash">Trash</a></span>';
					$actions[] = '<span class="view"><a href="' . esc_url($preview_url) . '" target="_blank" rel="bookmark" aria-label="View "' . esc_attr($post->post_title) . '"">View</a></span>';
					
					echo wp_kses_post(implode(' | ', $actions));
					echo '</div>';
					
					echo '</td>';
					echo '</tr>';
				}
				
				echo '</tbody>';
				echo '</table>';
				echo '</div>';
			} else {
				echo '<div class="find-posts-no-results">No posts found matching that title.</div>';
			}
	}

	echo '</div>';
}

// Render the custom admin page for Pages
function find_pages_by_title_render_page() {
	if (!current_user_can('edit_pages')) {
		return;
	}

	global $wpdb;

	$search_term = '';
	if (isset($_GET['s']) && isset($_GET['find_pages_by_title_nonce'])) {
		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['find_pages_by_title_nonce'])), 'find_pages_by_title_action')) {
			wp_die('Security check failed');
		}
		$search_term = sanitize_text_field(wp_unslash($_GET['s']));
	}

	echo '<div class="wrap">';
	echo '<h1>Find Pages by Title</h1>';
	echo '<form method="get" class="find-posts-search-form">';
	echo '<input type="hidden" name="post_type" value="page" />';
	echo '<input type="hidden" name="page" value="find-pages-by-title" />';
	wp_nonce_field('find_pages_by_title_action', 'find_pages_by_title_nonce');
	echo '<input type="text" name="s" value="' . esc_attr($search_term) . '" placeholder="Enter title keyword..." class="find-posts-search-input" />';
	echo ' <input type="submit" class="button button-primary" value="Search">';
	echo '</form>';

	if ($search_term) {
		$like = '%' . $wpdb->esc_like($search_term) . '%';
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'page' AND post_status = 'publish' AND post_title LIKE %s ORDER BY post_date DESC",
				$like
			)
		);

		if ($results) {
			echo '<h2>Results</h2>';
			echo '<div class="find-posts-results-table">';
							echo '<table class="wp-list-table widefat fixed striped table-view-list pages">';
				echo '<thead>';
				echo '<tr>';
				echo '<th scope="col" class="manage-column column-title column-primary">Title</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				
				$row_class = '';
				foreach ($results as $index => $post) {
					// Alternate row colors
					$row_class = ($index % 2 == 0) ? '' : 'alternate';
					
					// Standard edit URLs
					$edit_url = admin_url('post.php?post=' . $post->ID . '&action=edit');
					$gutenberg_url = admin_url('post.php?post=' . $post->ID . '&action=edit&gutenberg-editor');
					$classic_url = admin_url('post.php?post=' . $post->ID . '&action=edit&classic-editor');
					$trash_url = wp_nonce_url(admin_url('post.php?post=' . $post->ID . '&action=trash'), 'trash-post_' . $post->ID);
					$preview_url = get_permalink($post->ID);

					echo '<tr class="' . esc_attr($row_class) . '">';
					echo '<td class="title column-title has-row-actions column-primary" data-colname="Title">';
					echo '<strong>';
					echo '<a class="row-title find-posts-title-link" href="' . esc_url($edit_url) . '" target="_blank" aria-label="Edit "' . esc_attr($post->post_title) . '"">';
					echo esc_html($post->post_title);
					echo '</a>';
					echo '</strong>';
					
										// WordPress-style row actions
					echo '<div class="row-actions">';
					$actions = array();
					
					$actions[] = '<span class="edit"><a href="' . esc_url($edit_url) . '" target="_blank" aria-label="Edit "' . esc_attr($post->post_title) . '"">' . __('Edit', 'find-posts-by-title') . '</a></span>';
					
					$actions[] = '<span class="gutenberg"><a href="' . esc_url($gutenberg_url) . '" target="_blank" aria-label="Edit "' . esc_attr($post->post_title) . '" in the Gutenberg editor">Gutenberg Editor</a></span>';
					
					// Show Classic Editor option if available
					if (is_plugin_active('classic-editor/classic-editor.php') || function_exists('the_gutenberg_project')) {
						$actions[] = '<span class="classic"><a href="' . esc_url($classic_url) . '" target="_blank" aria-label="Edit "' . esc_attr($post->post_title) . '" in Classic Editor">Classic Editor</a></span>';
					}
					
					$actions[] = '<span class="trash"><a href="' . esc_url($trash_url) . '" class="submitdelete" aria-label="Move "' . esc_attr($post->post_title) . '" to the Trash">Trash</a></span>';
					$actions[] = '<span class="view"><a href="' . esc_url($preview_url) . '" target="_blank" rel="bookmark" aria-label="View "' . esc_attr($post->post_title) . '"">View</a></span>';
					
					echo wp_kses_post(implode(' | ', $actions));
					echo '</div>';
					
					echo '</td>';
					echo '</tr>';
				}
				
				echo '</tbody>';
				echo '</table>';
				echo '</div>';
			} else {
				echo '<div class="find-posts-no-results">No pages found matching that title.</div>';
			}
	}

	echo '</div>';
}
