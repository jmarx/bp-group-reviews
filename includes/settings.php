<?php

if ( !class_exists( 'BPGR_Settings' ) ) :

class BPGR_Settings {
	var $is_reviewable;
	
	function bpgr_settings() {
		$this->construct();
	}
	
	function __construct() {
		add_action( 'bp_before_group_settings_admin', array( $this, 'toggle_markup' ) );
		add_action( 'groups_group_settings_edited', array( $this, 'toggle_save' ) );
	}
	
	
	
	function toggle_markup() {
		
		$is_reviewable = BP_Group_Reviews::current_group_is_available();
		
		?>
		
		<div class="checkbox">
			<label for="bpgr_toggle"><input type="checkbox" id="bpgr_toggle" name="bpgr_toggle" value="yes" <?php if ( $is_reviewable ) : ?>checked="checked"<?php endif ?>/> <?php _e( 'Enable group reviews', 'bpgr' ) ?></label>
		</div>
		
		<?php
	}
	
	function toggle_save( $group_id ) {
		
		if ( !empty( $_POST['bpgr_toggle'] ) ) {
			groups_update_groupmeta( $group_id, 'bpgr_is_reviewable', 'yes' );
		} else {
			groups_update_groupmeta( $group_id, 'bpgr_is_reviewable', 'no' );
		}
	}
}

endif;

$bpgr_settings = new BPGR_Settings;

?>