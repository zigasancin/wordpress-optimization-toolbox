function fCheck() {
	var vCleanPingbacks = document.getElementById('clean-pingbacks');
	var vCleanTrackbacks = document.getElementById('clean-trackbacks');
	var vCleanPostmeta = document.getElementById('clean-postmeta');
	var vCleanCommentmeta = document.getElementById('clean-commentmeta');
	var vCleanOrphandata = document.getElementById('clean-orphandata');
	var vCleanTransient = document.getElementById('clean-transient');
	
	/*	vCleanTransient.onclick = function () {
	 return confirm('<?php _e('Transient options are automatically re-created by WordPress. Select this option, if you have a large number of Transient.', 'wp-optimize'); ?>');	
}

if ( vCleanPingbacks.checked )
	return confirm('<?php _e('This will delete all pingbacks in the database. Are you sure?', 'wp-optimize'); ?>');

if ( vCleanTrackbacks.checked )
	return confirm('<?php _e('This will delete all trackbacks in the database. Are you sure?', 'wp-optimize'); ?>');

if ( vCleanPostmeta.checked )
	return confirm('<?php _e('Cleaning up post meta can have unexpected results on some servers. Are you sure?', 'wp-optimize'); ?>');

if ( vCleanCommentmeta.checked )
	return confirm('<?php _e('Cleaning up comments meta can have unexpected results on some servers. Are you sure?', 'wp-optimize'); ?>');

if ( vCleanOrphandata.checked )
	return confirm('<?php _e('Cleaning up orphaned post relationship data can have unexpected result. Are you sure?', 'wp-optimize'); ?>');*/
	
}

function SetDefaults() {
	document.getElementById("clean-revisions").checked = true;
	document.getElementById("clean-comments").checked = true;
	document.getElementById("clean-autodraft").checked = true;
	document.getElementById("optimize-db").checked = true;
	return false;
}
// SetDefaults();

jQuery(document).ready(function($) {
	function enable_or_disable_schedule_options() {
		var schedule_enabled = $('#enable-schedule').is(':checked');
		if (schedule_enabled) {
			$('#wp-optimize-auto-options').css('opacity', '1');
			//.find('input').prop('disabled', false);
		} else {
			$('#wp-optimize-auto-options').css('opacity', '0.5')
			//.find('input').prop('disabled', true);
		}
	}
	
	enable_or_disable_schedule_options();
	
	$('#enable-schedule').change(function() { enable_or_disable_schedule_options(); });
	
	
});
