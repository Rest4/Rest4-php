var FilesDeleteWindow=new Class({
	Extends: ConfirmWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.uri='';
		// Locale/Class name
		this.classNames.push('FilesDeleteWindow');
		// Required options
		this.requiredOptions.push('uri');
		// Initializing window
		this.parent(desktop,options);
		},
	// Content
	renderContent : function() {
		var tpl ='<div class="box"><p>'+this.locale.content
			+' ('+(this.options.uri)+').</p></div>';
		this.view.innerHTML=tpl;
	},
	// Commands
	validateDocument: function(event) {
		var req=this.app.createRestRequest({
			'path':this.options.uri,
			'method':'delete'});
		req.addEvent('complete',this.deleteCompleted.bind(this));
		req.send();
	},
	deleteCompleted: function(req) {
		if(req.status==410) {
			this.fireEvent('done', [event, this.options.output]);
		} else {
			this.fireEvent('error', [event, this.options.output]);
		}
		this.close();
	}
});
