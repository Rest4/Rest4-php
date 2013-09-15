var FilesAddWindow=new Class({
	Extends: PromptUserFileWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.folder='';
		// Locale/Class name
		this.classNames.push('FilesAddWindow');
		// Required options
		this.requiredOptions.push('folder');
		// Initializing window
		this.parent(desktop,options);
	},
	// Files send
	filesLoaded: function() {
		this.options.output.files.forEach(function(file) {
			var req=this.app.createRestRequest({
				'path':this.options.folder+file.name+'?force=yes',
				'method':'put'
			});
			req.setHeader('Content-Type','text/base64url');
			req.options.data=file.content;
			this.addReq(req);
		}.bind(this));
		this.sendReqs(this.filesSent.bind(this));
	},
	filesSent: function(req) {
		this.fireEvent('done', [event, this.options.output]);
		this.close();
	},
	filesNotSent: function(req) {
		this.fireEvent('error', [event, this.options.output]);
		this.close();
	}
});
