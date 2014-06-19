$(function(){
		// --- Initialize sample trees
		$("#tree").fancytree({
//			autoFocus: true,
			minExpandLevel: 1,
			clickFolderMode: 3, // expand with single click
			autoActivate: false,
			autoCollapse: true,
			autoScroll: true,
			tabbable: false, // we don't want the focus frame
			focus: function(event, data) {
				var node = data.node;
				// Auto-activate focused node after 1 second
				if(node.data.href){
					node.scheduleAction("activate", 1000);
				}
			},
			blur: function(event, data) {
				data.node.scheduleAction("cancel");
			},
			activate: function(event, data){
				var node = data.node;
				if(node.data.href){
					window.open(node.data.href, node.data.target);
				}
			},
			click: function(event, data){ // allow re-loads
				var node = data.node;
				if(node.isActive() && node.data.href){
					data.tree.reactivate();
				}
			}
		});
	});