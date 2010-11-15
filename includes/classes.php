<?php

class bpgr_Plugin_Group_Reviews extends BP_Group_Extension {

	function bpgr_plugin_group_reviews() {
		global $bp;
		
		$this->group_id = BP_Groups_Group::group_exists($bp->current_item);
		
		$this->name = __( 'Reviews', 'bp-group-reviews' );
		$this->slug = BP_GROUP_REVIEWS_SLUG;
		
		$this->nav_item_position = 22;
		$this->enable_create_step = false;
		$this->enable_nav_item = true;
		$this->enable_edit_item = false;

		if ( isset( $_POST['review_submit'] ) && (int)$_POST['rating'] ) {
			check_admin_referer( 'review_submit' );

			if ( empty( $_POST['review_content'] ) || !(int)$_POST['rating'] ) {
				bp_core_add_message( "Please make sure you fill in the review, and don't forget to rate the plugin!", 'error' );
			} else {
				/* Auto join this user if they are not yet a member of this group */
				if ( !is_site_admin() && 'public' == $bp->groups->current_group->status && !groups_is_user_member( $bp->loggedin_user->id, $bp->groups->current_group->id ) )
					groups_join_group( $bp->groups->current_group->id, $bp->loggedin_user->id );

				if ( $rating_id = $this->post_review( array( 'content' => $_POST['review_content'], 'rating' => (int)$_POST['rating'] ) ) ) {
					bp_core_add_message( "Your review was posted successfully!" );

					$has_posted = groups_get_groupmeta( $bp->groups->current_group->id, 'posted_review' );
					$has_posted[] = (int)$bp->loggedin_user->id;
					groups_update_groupmeta( $bp->groups->current_group->id, 'posted_review', $has_posted );

					if ( (int)$_POST['rating'] < 0 )
						$_POST['rating'] = 1;

					if ( (int)$_POST['rating'] > 5 )
						$_POST['rating'] = 5;

					bp_activity_update_meta( $rating_id, 'rating', $_POST['rating'] );
				} else
					bp_core_add_message( "There was a problem posting your review, please try again.", 'error' );

				bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . $this->slug );
			}
		}
	}

	function display() {
		global $bp;

		include( apply_filters( 'bpgr_index_template', BP_GROUP_REVIEWS_DIR . 'templates/index.php' ) );
	}
	
	function post_review( $args = '' ) {
		global $bp;
	
		$defaults = array(
			'content' => false,
			'rating' => false,
			'user_id' => $bp->loggedin_user->id,
			'group_id' => $bp->groups->current_group->id
		);
	
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
	
		if ( empty( $content ) || !strlen( trim( $content ) ) || empty( $user_id ) || empty( $group_id ) )
			return false;
	
		/* Be sure the user is a member of the group before posting. */
		if ( !is_site_admin() && !groups_is_user_member( $user_id, $group_id ) )
			return false;
	
		/* Record this in activity streams */
		$activity_action = sprintf( __( '%s reviewed the plugin %s:', 'buddypress'), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . attribute_escape( $bp->groups->current_group->name ) . '</a>' );
	
		$rating_content = false;
		if ( !empty( $rating ) )
			$rating_content = '<span class="p-rating">' . bpgr_get_review_rating_html( $rating ) . '</span>';
	
		$activity_content = $rating_content . $content;
	
		$activity_id = groups_record_activity( array(
			'user_id' => $user_id,
			'action' => $activity_action,
			'content' => $activity_content,
			'type' => 'review',
			'item_id' => $group_id
		) );
	
		groups_update_groupmeta( $group_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );
	
		return $activity_id;
	}
}
bp_register_group_extension( 'bpgr_Plugin_Group_Reviews' );

?>