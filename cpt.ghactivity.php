<?php
/**
 * Importing GHActivity to a new Custom Post Type
 *
 * @since 1.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );


/**
 * Register GitHub Activity Custom Post Type and its taxonomies.
 *
 * @since 1.0
 */
function ghactivity_register_post_type() {

	register_post_type( 'ghactivity_event', array(
		'label'                 => __( 'GitHub Event', 'ghactivity' ),
		'description'           => __( 'GitHub Event', 'ghactivity' ),
		'labels'                => array(
			'name'                  => _x( 'GitHub Events', 'Post Type General Name', 'ghactivity' ),
			'singular_name'         => _x( 'GitHub Event', 'Post Type Singular Name', 'ghactivity' ),
			'menu_name'             => __( 'GitHub Events', 'ghactivity' ),
			'name_admin_bar'        => __( 'GitHub Event', 'ghactivity' ),
			'archives'              => __( 'Event Archives', 'ghactivity' ),
			'all_items'             => __( 'All GitHub Events', 'ghactivity' ),
			'add_new_item'          => __( 'Add New Event', 'ghactivity' ),
			'add_new'               => __( 'Add New', 'ghactivity' ),
			'new_item'              => __( 'New Event', 'ghactivity' ),
			'edit_item'             => __( 'Edit Event', 'ghactivity' ),
			'update_item'           => __( 'Update Event', 'ghactivity' ),
			'view_item'             => __( 'View Event', 'ghactivity' ),
			'search_items'          => __( 'Search Event', 'ghactivity' ),
		),
		'rewrite'               => array(
			'slug'                  => 'github',
			'with_front'            => false,
			'feeds'                 => true,
			'pages'                 => true,
		),
		'supports'              => array( 'title', 'editor', 'wpcom-markdown' ),
		'taxonomies'            => array(
			'ghactivity_event_type',
			'ghactivity_repo',
			'ghactivity_actor',
			'ghactivity_team',
		),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 20,
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
		'menu_icon'             => 'dashicons-chart-line',
		'show_in_rest'          => true,
	) );

	/**
	 * Register Event Type taxonomy.
	 *
	 * @since 1.0
	 */
	register_taxonomy( 'ghactivity_event_type', array( 'ghactivity_event' ), array(
		'labels'                     => array(
			'name'                       => _x( 'Event Types', 'Taxonomy General Name', 'ghactivity' ),
			'singular_name'              => _x( 'Event Type', 'Taxonomy Singular Name', 'ghactivity' ),
			'menu_name'                  => __( 'Event Type', 'ghactivity' ),
			'all_items'                  => __( 'All Event Types', 'ghactivity' ),
			'new_item_name'              => __( 'New Event Type', 'ghactivity' ),
			'add_new_item'               => __( 'Add New Event Type', 'ghactivity' ),
			'edit_item'                  => __( 'Edit Event Type', 'ghactivity' ),
			'update_item'                => __( 'Update Event Type', 'ghactivity' ),
			'view_item'                  => __( 'View Event Type', 'ghactivity' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'ghactivity' ),
			'add_or_remove_items'        => __( 'Add or remove Event Type', 'ghactivity' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'ghactivity' ),
			'popular_items'              => __( 'Popular Event Types', 'ghactivity' ),
			'search_items'               => __( 'Search Event Types', 'ghactivity' ),
			'not_found'                  => __( 'Not Found', 'ghactivity' ),
			'no_terms'                   => __( 'No Event Types', 'ghactivity' ),
			'items_list'                 => __( 'Event Type list', 'ghactivity' ),
			'items_list_navigation'      => __( 'Event Type list navigation', 'ghactivity' ),
		),
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
	) );

	/**
	 * Register Repo taxonomy.
	 *
	 * @since 1.0
	 */
	register_taxonomy( 'ghactivity_repo', array( 'ghactivity_event' ), array(
		'labels'                     => array(
			'name'                       => _x( 'Repositories', 'Taxonomy General Name', 'ghactivity' ),
			'singular_name'              => _x( 'Repo', 'Taxonomy Singular Name', 'ghactivity' ),
			'menu_name'                  => __( 'Repo', 'ghactivity' ),
			'all_items'                  => __( 'All Repositories', 'ghactivity' ),
			'parent_item'                => __( 'Parent Item', 'ghactivity' ),
			'parent_item_colon'          => __( 'Parent Item:', 'ghactivity' ),
			'new_item_name'              => __( 'New Repo Name', 'ghactivity' ),
			'add_new_item'               => __( 'Add New Repo', 'ghactivity' ),
			'edit_item'                  => __( 'Edit Repo', 'ghactivity' ),
			'update_item'                => __( 'Update Repo', 'ghactivity' ),
			'view_item'                  => __( 'View Repo', 'ghactivity' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'ghactivity' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'ghactivity' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'ghactivity' ),
			'popular_items'              => __( 'Popular Items', 'ghactivity' ),
			'search_items'               => __( 'Search Items', 'ghactivity' ),
			'not_found'                  => __( 'Not Found', 'ghactivity' ),
			'no_terms'                   => __( 'No items', 'ghactivity' ),
			'items_list'                 => __( 'Items list', 'ghactivity' ),
			'items_list_navigation'      => __( 'Items list navigation', 'ghactivity' ),
		),
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
	) );

	/**
	 * Register Actor taxonomy.
	 *
	 * @since 1.0
	 */
	register_taxonomy( 'ghactivity_actor', array( 'ghactivity_event' ), array(
		'labels'                     => array(
			'name'                       => _x( 'People', 'Taxonomy General Name', 'ghactivity' ),
			'singular_name'              => _x( 'Person', 'Taxonomy Singular Name', 'ghactivity' ),
			'menu_name'                  => __( 'Person', 'ghactivity' ),
			'all_items'                  => __( 'Everyone', 'ghactivity' ),
			'parent_item'                => __( 'Parent Item', 'ghactivity' ),
			'parent_item_colon'          => __( 'Parent Item:', 'ghactivity' ),
			'new_item_name'              => __( 'New Person Name', 'ghactivity' ),
			'add_new_item'               => __( 'Add New Person', 'ghactivity' ),
			'edit_item'                  => __( 'Edit Person', 'ghactivity' ),
			'update_item'                => __( 'Update Person', 'ghactivity' ),
			'view_item'                  => __( 'View Person', 'ghactivity' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'ghactivity' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'ghactivity' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'ghactivity' ),
			'popular_items'              => __( 'Popular Items', 'ghactivity' ),
			'search_items'               => __( 'Search Items', 'ghactivity' ),
			'not_found'                  => __( 'Not Found', 'ghactivity' ),
			'no_terms'                   => __( 'No items', 'ghactivity' ),
			'items_list'                 => __( 'Items list', 'ghactivity' ),
			'items_list_navigation'      => __( 'Items list navigation', 'ghactivity' ),
		),
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
	) );

	/**
	 * Register Team Taxonomy.
	 *
	 * @since 1.6.0
	 */
	register_taxonomy( 'ghactivity_team', array( 'ghactivity_event' ), array(
		'labels'                     => array(
			'name'                       => _x( 'Team', 'Taxonomy General Name', 'ghactivity' ),
			'singular_name'              => _x( 'Team', 'Taxonomy Singular Name', 'ghactivity' ),
			'menu_name'                  => __( 'Team', 'ghactivity' ),
			'all_items'                  => __( 'All teams', 'ghactivity' ),
			'parent_item'                => __( 'Parent Item', 'ghactivity' ),
			'parent_item_colon'          => __( 'Parent Item:', 'ghactivity' ),
			'new_item_name'              => __( 'New Team Name', 'ghactivity' ),
			'add_new_item'               => __( 'Add New Team', 'ghactivity' ),
			'edit_item'                  => __( 'Edit Team', 'ghactivity' ),
			'update_item'                => __( 'Update Team', 'ghactivity' ),
			'view_item'                  => __( 'View Team', 'ghactivity' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'ghactivity' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'ghactivity' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'ghactivity' ),
			'popular_items'              => __( 'Popular Items', 'ghactivity' ),
			'search_items'               => __( 'Search Items', 'ghactivity' ),
			'not_found'                  => __( 'Not Found', 'ghactivity' ),
			'no_terms'                   => __( 'No items', 'ghactivity' ),
			'items_list'                 => __( 'Items list', 'ghactivity' ),
			'items_list_navigation'      => __( 'Items list navigation', 'ghactivity' ),
		),
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
	) );
}
add_action( 'init', 'ghactivity_register_post_type', 0 );

/**
 * Display the Post Type in the WordPress.com REST API.
 *
 * @since 1.0
 */
function ghactivity_whitelist_post_type_wpcom( $allowed_post_types ) {
	$allowed_post_types[] = 'ghactivity_event';
	return $allowed_post_types;
}
add_filter( 'rest_api_allowed_post_types', 'ghactivity_whitelist_post_type_wpcom' );

/**
 * Add a new field to the repo edit screen, where one can set a repo to be fully monitored.
 *
 * @since 1.6.0
 *
 * @param object $tag Current taxonomy term object.
 * @param string $taxonomy Current taxonomy slug.
 */
function ghactivity_repo_full_reporting_field( $tag, $taxonomy ) {
	$is_reporting_on = get_term_meta( $tag->term_id, 'full_reporting', true );

	if ( ! $is_reporting_on ) {
		$is_reporting_on = false;
	}

	echo '<tr class="form-field ghactivity-repo-full-reporting-wrap">';
	printf(
		'<th scope="row"><label for="ghactivity-repo-full-reporting"></label>%s</th>',
		esc_html__( 'Log all activity for that repo', 'ghactivity' )
	);
	echo '<td>';

	wp_nonce_field( basename( __FILE__ ), 'ghrepo_reporting_nonce' );
	printf(
		'<input type="checkbox" name="full_reporting" id="full_reporting" value="1" %s />',
		checked( (bool) ( $is_reporting_on ), true, false )
	);

	echo '</td></tr>';
}
add_action( 'ghactivity_repo_edit_form_fields', 'ghactivity_repo_full_reporting_field', 10, 2 );

/**
 * Save the term meta when making changes to the repo monitoring field.
 *
 * @since 1.6.0
 *
 * @param int $term_id Term ID.
 */
function ghactivity_repo_full_reporting_save_field( $term_id ) {
	if (
		! isset( $_POST['ghrepo_reporting_nonce'] )
		|| ! wp_verify_nonce( $_POST['ghrepo_reporting_nonce'], basename( __FILE__ ) )
	) {
		return;
	}

	if ( isset( $_POST['full_reporting'] ) && 1 == $_POST['full_reporting'] ) {
		$is_reporting_on = true;
	} else {
		$is_reporting_on = false;
	}

	update_term_meta( $term_id, 'full_reporting', $is_reporting_on );
}
add_action( 'edit_ghactivity_repo', 'ghactivity_repo_full_reporting_save_field' );


/**
 * Customize the columns displayed on the Team admin page.
 *
 * @since 1.6.0
 *
 * @param array $columns Array of columns on the screen we hook into.
 */
function ghactivity_team_columns( $columns ) {
	unset( $columns['slug'] );
	unset( $columns['description'] );
	unset( $columns['posts'] );
	$columns['names']  = esc_html__( 'People', 'ghactivity' );
	return $columns;
}
add_filter( 'manage_edit-ghactivity_team_columns', 'ghactivity_team_columns' );

/**
 * Display the people belonging to each team in the new column we created in the Team admin page.
 *
 * @since 1.6.0
 *
 * @param string $content     Column content.
 * @param string $column_name Column name.
 * @param int    $term_id     Term ID.
 */
function ghactivity_team_display_people_columns( $content, $column_name, $term_id ) {
	global $feature_groups;
	if ( 'names' !== $column_name ) {
		return $content;
	}

	// Get the name of each row.
	$term_id = absint( $term_id );
	$term_id_object = get_term_by( 'term_id', $term_id, 'ghactivity_team', ARRAY_A );
	if ( ! is_array( $term_id_object ) || empty( $term_id_object ) ) {
		return $content;
	}

	$team_members_args = array(
		'taxonomy'   => 'ghactivity_actor',
		'hide_empty' => false,
		'fields'     => 'id=>name',
		'meta_query' => array(
			array(
				'key'     => 'team',
				'value'   => $term_id_object['name'],
				'compare' => 'LIKE',
			),
		),
	);
	$team_members = get_terms( $team_members_args );

	// Build list of teammates for that team.
	$team = array();
	foreach ( $team_members as $id => $teammate ) {
		$edit_link = admin_url(
			sprintf(
				'term.php?taxonomy=ghactivity_actor&tag_ID=%s&post_type=ghactivity_event',
				$id
			)
		);
		$team[] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $edit_link ),
			esc_html( $teammate )
		);
	}

	if ( ! empty( $team ) ) {
		$content = sprintf(
			'%1$s %2$s',
			$content,
			implode( ', ', $team )
		);
	}

	return $content;
}
add_filter( 'manage_ghactivity_team_custom_column', 'ghactivity_team_display_people_columns', 13, 3 );

/**
 * Add a new "Team" column to the Person admin page.
 *
 * @since 1.6.0
 *
 * @param array $columns Array of columns on the screen we hook into.
 */
function ghactivity_person_columns( $columns ) {
	$columns['team']  = esc_html__( 'Team', 'ghactivity' );
	return $columns;
}
add_filter( 'manage_edit-ghactivity_actor_columns', 'ghactivity_person_columns' );

/**
 * Remove the "Team" column in the GitHub Events main screen.
 *
 * @since 2.0.0
 *
 * @param string $columns Columns.
 */
function ghactivity_event_custom_posts_columns( $columns ) {
	unset( $columns['taxonomy-ghactivity_team'] );

	return $columns;
}
add_filter( 'manage_ghactivity_event_posts_columns', 'ghactivity_event_custom_posts_columns' );

/**
 * Display the team in the new column we created in the Person admin page.
 *
 * @since 1.6.0
 *
 * @param string $content     Column content.
 * @param string $column_name Column name.
 * @param int    $term_id     Term ID.
 */
function ghactivity_team_display_in_person_columns( $content, $column_name, $term_id ) {
	global $feature_groups;
	if ( 'team' != $column_name ) {
		return $content;
	}
	$term_id = absint( $term_id );
	$team    = get_term_meta( $term_id, 'team', true );
	if ( ! empty( $team ) ) {
		// backward compatibility after #7.
		if ( is_array( $team ) ) {
			$team = implode( ' ', $team );
		}
		$content = sprintf(
			'%1$s %2$s',
			$content,
			esc_html( $team )
		);
	}
	return $content;
}
add_filter( 'manage_ghactivity_actor_custom_column', 'ghactivity_team_display_in_person_columns', 13, 3 );

/**
 * Add a new field to the person edit screen, where one can assign a person to a team.
 * The list of teams is pulled from a custom taxonomy, `ghactivity_team`,
 * and saved as a term meta for that person, `team`
 *
 * @since 1.6.0
 *
 * @param object $tag Current taxonomy term object.
 * @param string $taxonomy Current taxonomy slug.
 */
function ghactivity_actor_team_field( $tag, $taxonomy ) {
	$existing_teams = get_term_meta( $tag->term_id, 'team', true );

	if ( ! $existing_teams ) {
		$existing_teams = array();
	}

	$available_teams = get_terms( array(
		'taxonomy'   => 'ghactivity_team',
		'hide_empty' => false,
		'fields'     => 'id=>name',
	) );

	echo '<tr class="form-field ghactivity-actor-team-wrap">';
	printf(
		'<th scope="row"><label for="ghactivity-actor-team"></label>%s</th>',
		esc_html__( 'Team', 'ghactivity' )
	);
	echo '<td>';

	wp_nonce_field( basename( __FILE__ ), 'ghactor_team_nonce' );
	foreach ( $available_teams as $id => $team_name ) {
		echo '<div style="display:block">';
		// backward compatibility after #7.
		if ( ! is_array( $existing_teams ) ) {
			$checked = checked( $team_name, $existing_teams, false );
		} else {
			$checked = checked( in_array( $team_name, $existing_teams, true ), true, false );
		}

		printf(
			'<input type="checkbox" class="label" id="%1$s" name="team[]" value="%1$s" class="" %2$s></label>',
			esc_html( $team_name ),
			esc_html( $checked )
		);
		printf(
			'<label for="%1$s" class="label"
			>%1$s</label>',
			esc_html( $team_name )
		);
		echo '</div>';
	}
	echo '</td></tr>';
}
add_action( 'ghactivity_actor_edit_form_fields', 'ghactivity_actor_team_field', 10, 2 );

/**
 * Save the term meta when making changes to the person's team field.
 *
 * @since 1.6.0
 *
 * @param int $term_id Term ID.
 */
function ghactivity_actor_team_save_field( $term_id ) {
	if (
		! isset( $_POST['ghactor_team_nonce'] )
		|| ! wp_verify_nonce( $_POST['ghactor_team_nonce'], basename( __FILE__ ) )
	) {
		return;
	}

	if ( ! empty( $_POST['team'] ) ) {
		update_term_meta( $term_id, 'team', array_map( 'sanitize_text_field', $_POST['team'] ) );
	}
}
add_action( 'edit_ghactivity_actor', 'ghactivity_actor_team_save_field' );

/**
 * Register Issues CPT and its taxonomies to keep track of issues per repo.
 *
 * @since 2.0.0
 */
function ghactivity_register_issues_post_type() {
	register_post_type( 'ghactivity_issue', array(
		'label'                 => __( 'GitHub Issues', 'ghactivity' ),
		'description'           => __( 'GitHub Issue', 'ghactivity' ),
		'labels'                => array(
			'name'                  => _x( 'GitHub Issues', 'Post Type General Name', 'ghactivity' ),
			'singular_name'         => _x( 'GitHub Issue', 'Post Type Singular Name', 'ghactivity' ),
			'menu_name'             => __( 'GitHub Issues', 'ghactivity' ),
			'name_admin_bar'        => __( 'GitHub Issue', 'ghactivity' ),
			'archives'              => __( 'Issues Archives', 'ghactivity' ),
			'all_items'             => __( 'All GitHub Issues', 'ghactivity' ),
			'add_new_item'          => __( 'Add New Issues', 'ghactivity' ),
			'add_new'               => __( 'Add New', 'ghactivity' ),
			'new_item'              => __( 'New Issue', 'ghactivity' ),
			'edit_item'             => __( 'Edit Issue', 'ghactivity' ),
			'update_item'           => __( 'Update Issue', 'ghactivity' ),
			'view_item'             => __( 'View Issue', 'ghactivity' ),
			'search_items'          => __( 'Search Issue', 'ghactivity' ),
		),
		'rewrite'               => array(
			'slug'                  => 'github_issue',
			'with_front'            => false,
			'feeds'                 => true,
			'pages'                 => true,
		),
		'supports'              => array( 'title', 'editor', 'wpcom-markdown' ),
		'taxonomies'            => array(
			'ghactivity_issues_state',
			'ghactivity_issues_labels',
			'ghactivity_repo',
			'ghactivity_actor',
			'ghactivity_issues_type',
		),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 20,
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
		'menu_icon'             => 'dashicons-info',
		'show_in_rest'          => true,
	) );

	/**
	 * Register State Taxonomy.
	 *
	 * @since 2.0.0
	 */
	register_taxonomy( 'ghactivity_issues_state', array( 'ghactivity_issue' ), array(
		'labels'                     => array(
			'name'                       => _x( 'State', 'Taxonomy General Name', 'ghactivity' ),
			'singular_name'              => _x( 'State', 'Taxonomy Singular Name', 'ghactivity' ),
			'menu_name'                  => __( 'State', 'ghactivity' ),
			'all_items'                  => __( 'All states', 'ghactivity' ),
			'parent_item'                => __( 'Parent Item', 'ghactivity' ),
			'parent_item_colon'          => __( 'Parent Item:', 'ghactivity' ),
			'new_item_name'              => __( 'New State Name', 'ghactivity' ),
			'add_new_item'               => __( 'Add New State', 'ghactivity' ),
			'edit_item'                  => __( 'Edit State', 'ghactivity' ),
			'update_item'                => __( 'Update State', 'ghactivity' ),
			'view_item'                  => __( 'View State', 'ghactivity' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'ghactivity' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'ghactivity' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'ghactivity' ),
			'popular_items'              => __( 'Popular Items', 'ghactivity' ),
			'search_items'               => __( 'Search Items', 'ghactivity' ),
			'not_found'                  => __( 'Not Found', 'ghactivity' ),
			'no_terms'                   => __( 'No items', 'ghactivity' ),
			'items_list'                 => __( 'Items list', 'ghactivity' ),
			'items_list_navigation'      => __( 'Items list navigation', 'ghactivity' ),
		),
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
	) );

	/**
	 * Register Labels Taxonomy.
	 *
	 * @since 2.0.0
	 */
	register_taxonomy( 'ghactivity_issues_labels', array( 'ghactivity_issue' ), array(
		'labels'                     => array(
			'name'                       => _x( 'Labels', 'Taxonomy General Name', 'ghactivity' ),
			'singular_name'              => _x( 'Label', 'Taxonomy Singular Name', 'ghactivity' ),
			'menu_name'                  => __( 'Labels', 'ghactivity' ),
			'all_items'                  => __( 'All Labels', 'ghactivity' ),
			'parent_item'                => __( 'Parent Item', 'ghactivity' ),
			'parent_item_colon'          => __( 'Parent Item:', 'ghactivity' ),
			'new_item_name'              => __( 'New Label Name', 'ghactivity' ),
			'add_new_item'               => __( 'Add New Label', 'ghactivity' ),
			'edit_item'                  => __( 'Edit Label', 'ghactivity' ),
			'update_item'                => __( 'Update Label', 'ghactivity' ),
			'view_item'                  => __( 'View Label', 'ghactivity' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'ghactivity' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'ghactivity' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'ghactivity' ),
			'popular_items'              => __( 'Popular Items', 'ghactivity' ),
			'search_items'               => __( 'Search Items', 'ghactivity' ),
			'not_found'                  => __( 'Not Found', 'ghactivity' ),
			'no_terms'                   => __( 'No items', 'ghactivity' ),
			'items_list'                 => __( 'Items list', 'ghactivity' ),
			'items_list_navigation'      => __( 'Items list navigation', 'ghactivity' ),
		),
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
	) );

	/**
	 * Register Type Taxonomy. Issue or Pull Request.
	 *
	 * @since 2.0.0
	 */
	register_taxonomy( 'ghactivity_issues_type', array( 'ghactivity_issue' ), array(
		'labels'                     => array(
			'name'                       => _x( 'Type', 'Taxonomy General Name', 'ghactivity' ),
			'singular_name'              => _x( 'Type', 'Taxonomy Singular Name', 'ghactivity' ),
			'menu_name'                  => __( 'Type', 'ghactivity' ),
			'all_items'                  => __( 'All Types', 'ghactivity' ),
			'parent_item'                => __( 'Parent Item', 'ghactivity' ),
			'parent_item_colon'          => __( 'Parent Item:', 'ghactivity' ),
			'new_item_name'              => __( 'New Type Name', 'ghactivity' ),
			'add_new_item'               => __( 'Add New Type', 'ghactivity' ),
			'edit_item'                  => __( 'Edit Type', 'ghactivity' ),
			'update_item'                => __( 'Update Type', 'ghactivity' ),
			'view_item'                  => __( 'View Type', 'ghactivity' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'ghactivity' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'ghactivity' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'ghactivity' ),
			'popular_items'              => __( 'Popular Items', 'ghactivity' ),
			'search_items'               => __( 'Search Items', 'ghactivity' ),
			'not_found'                  => __( 'Not Found', 'ghactivity' ),
			'no_terms'                   => __( 'No items', 'ghactivity' ),
			'items_list'                 => __( 'Items list', 'ghactivity' ),
			'items_list_navigation'      => __( 'Items list navigation', 'ghactivity' ),
		),
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
	) );
}
add_action( 'init', 'ghactivity_register_issues_post_type', 0 );


/**
 * Register query records CPT and its taxonomies to keep track of different queries.
 *
 * @since 2.0.0
 */
function ghactivity_register_query_records_post_type() {
	register_post_type( 'gh_query_record', array(
		'label'               => __( 'GitHub Query Records', 'ghactivity' ),
		'description'         => __( 'GitHub Query Records', 'ghactivity' ),
		'labels'              => array(
			'name'           => _x( 'GitHub Query Records', 'Post Type General Name', 'ghactivity' ),
			'singular_name'  => _x( 'GitHub Query Record', 'Post Type Singular Name', 'ghactivity' ),
			'menu_name'      => __( 'GitHub Query Records', 'ghactivity' ),
			'name_admin_bar' => __( 'GitHub Issue', 'ghactivity' ),
			'archives'       => __( 'Issues Archives', 'ghactivity' ),
			'all_items'      => __( 'All Query Records', 'ghactivity' ),
			'add_new_item'   => __( 'Add New Query Record', 'ghactivity' ),
			'add_new'        => __( 'Add New', 'ghactivity' ),
			'new_item'       => __( 'New Query Record', 'ghactivity' ),
			'edit_item'      => __( 'Edit Query Record', 'ghactivity' ),
			'update_item'    => __( 'Update Query Record', 'ghactivity' ),
			'view_item'      => __( 'View Query Record', 'ghactivity' ),
			'search_items'   => __( 'Search Query Record', 'ghactivity' ),
		),
		'rewrite'             => array(
			'slug'                  => 'query_record',
			'with_front'            => false,
			'feeds'                 => true,
			'pages'                 => true,
		),
		'supports'            => array( 'title', 'editor', 'wpcom-markdown' ),
		'taxonomies'          => array(
			'ghactivity_query_record_type',
			'ghactivity_repo',
			'ghactivity_query_label_slug',
		),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 20,
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'page',
		'menu_icon'           => 'dashicons-info',
		'show_in_rest'        => true,
	) );

	/**
	 * Register Query Record Type Taxonomy.
	 *
	 * @since 2.1.0
	 */
	register_taxonomy( 'ghactivity_query_record_type', array( 'gh_query_record' ), array(
		'labels'                     => array(
			'name'                       => _x( 'Record Type', 'Taxonomy General Name', 'ghactivity' ),
			'singular_name'              => _x( 'Record Type', 'Taxonomy Singular Name', 'ghactivity' ),
			'menu_name'                  => __( 'Record Type', 'ghactivity' ),
			'all_items'                  => __( 'All Record Types', 'ghactivity' ),
			'parent_item'                => __( 'Parent Item', 'ghactivity' ),
			'parent_item_colon'          => __( 'Parent Item:', 'ghactivity' ),
			'new_item_name'              => __( 'New Type Name', 'ghactivity' ),
			'add_new_item'               => __( 'Add New Record Type', 'ghactivity' ),
			'edit_item'                  => __( 'Edit Record Type', 'ghactivity' ),
			'update_item'                => __( 'Update Record Type', 'ghactivity' ),
			'view_item'                  => __( 'View Record Type', 'ghactivity' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'ghactivity' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'ghactivity' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'ghactivity' ),
			'popular_items'              => __( 'Popular Items', 'ghactivity' ),
			'search_items'               => __( 'Search Items', 'ghactivity' ),
			'not_found'                  => __( 'Not Found', 'ghactivity' ),
			'no_terms'                   => __( 'No items', 'ghactivity' ),
			'items_list'                 => __( 'Items list', 'ghactivity' ),
			'items_list_navigation'      => __( 'Items list navigation', 'ghactivity' ),
		),
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
	) );

	/**
	 * Register Query Record Label Slug
	 *
	 * @since 2.1.0
	 */
	register_taxonomy( 'ghactivity_query_label_slug', array( 'gh_query_record' ), array(
		'labels'                     => array(
			'name'                       => _x( 'Query Label Slug', 'Taxonomy General Name', 'ghactivity' ),
			'singular_name'              => _x( 'Query Label Slugs', 'Taxonomy Singular Name', 'ghactivity' ),
			'menu_name'                  => __( 'Query Label Slug', 'ghactivity' ),
			'all_items'                  => __( 'All Query Label Slugs', 'ghactivity' ),
			'parent_item'                => __( 'Parent Item', 'ghactivity' ),
			'parent_item_colon'          => __( 'Parent Item:', 'ghactivity' ),
			'new_item_name'              => __( 'New Query Label Slug Name', 'ghactivity' ),
			'add_new_item'               => __( 'Add New Query Label Slug', 'ghactivity' ),
			'edit_item'                  => __( 'Edit Query Label Slug', 'ghactivity' ),
			'update_item'                => __( 'Update Query Label Slug', 'ghactivity' ),
			'view_item'                  => __( 'View Query Label Slug', 'ghactivity' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'ghactivity' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'ghactivity' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'ghactivity' ),
			'popular_items'              => __( 'Popular Items', 'ghactivity' ),
			'search_items'               => __( 'Search Items', 'ghactivity' ),
			'not_found'                  => __( 'Not Found', 'ghactivity' ),
			'no_terms'                   => __( 'No items', 'ghactivity' ),
			'items_list'                 => __( 'Items list', 'ghactivity' ),
			'items_list_navigation'      => __( 'Items list navigation', 'ghactivity' ),
		),
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
	) );
}
add_action( 'init', 'ghactivity_register_query_records_post_type', 0 );
