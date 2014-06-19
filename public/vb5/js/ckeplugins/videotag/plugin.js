CKEDITOR.plugins.add('videotag', {
	requires: 'dialog',
	init: function( editor ) {
		editor.addCommand( 'videotag', new CKEDITOR.dialogCommand( 'videotag' ) );
		editor.ui.addButton && editor.ui.addButton( 'Video', {
			label: vBulletin.phrase.get('insert_video'),
			command:  'videotag',
			icon: window.pageData.baseurl + '/core/images/editor/video.png'
		});
		CKEDITOR.dialog.add( 'videotag', vBulletin.ckeditor.config.pluginPath + 'videotag/dialogs/videotag.js' );
	}
});