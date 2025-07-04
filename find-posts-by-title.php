<?php
/*
Plugin Name: Find Posts by Title
Description: Search blog posts by title from the admin, and open them in a new tab.
Version: 1.2
Author: Tim Coe
*/

// Add submenu under "Posts"
add_action('admin_menu', function () {
	add_submenu_page(
		'edit.php',              // Parent: Posts menu
		'Find Posts by Title',   // Page title
		'Find by Title',         // Menu label
		'edit_posts',            // Capability
		'find-by-title',         // Slug
		'find_posts_by_title_page' // Callback
	);
});

// Render page content
function find_posts_by_title_page() {
	if (!current_user_can('edit_posts')) return;

	global $wpdb;
	$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

	echo '<div class="wrap">';
	echo '<h1>Find Posts by Title</h1>';
	echo '<form method="get" style="margin: 20px 0;">';
	echo '<input type="hidden" name="page" value="find-by-title">';
	echo '<input type="text" name="s" value="' . esc_attr($search) . '" placeholder="Search by title..." style="width:300px;"> ';
	echo '<input type="submit" class="button button-primary" value="Search">';
	echo '</form>';

	if ($search) {
		$like = '%' . $wpdb->esc_like($search) . '%';
		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' AND post_title LIKE %s ORDER BY post_date DESC",
				$like
			)
		);

		if ($posts) {
			echo '<h2>Results</h2><ul style="margin-top:15px;">';
			foreach ($posts as $post) {
				$edit_url = admin_url("post.php?post={$post->ID}&action=edit");
				echo "<li style='margin-bottom:10px;'>
						<strong>" . esc_html($post->post_title) . "</strong><br>
						<a href='" . esc_url($edit_url) . "' target='_blank' style='font-size:13px;'>✏️ Edit Post</a>
					  </li>";
			}
			echo '</ul>';
		} else {
			echo '<p>No posts found.</p>';
		}
	}

	echo '</div>';
}
