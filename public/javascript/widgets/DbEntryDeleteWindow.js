var DbEntryDeleteWindow=new Class({
	Extends: ConfirmWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.database='';
		this.options.table='';
		this.options.entryId='';
		// Locale/Class name
		this.classNames.push('DbEntryDeleteWindow');
		// Required options
		this.requiredOptions.push('database','table','entryId');
		// Initializing window
		this.parent(desktop,options);
		},
	// Content
	loadContent: function()	{
		var uri='/db/'+this.options.database+'/'+this.options.table
			+'/'+this.options.entryId+'.dat?mode=light';
		this.addReq(this.app.getLoadDatasReq(uri,this));
		this.parent();
		},
	renderContent : function() {
		var tpl ='<div class="box"><p>'+this.locale.content
			+' ('+(this.entry.label||this.entry.id)+').</p></div>';
		this.view.innerHTML=tpl;
	},
	// Commands
	validateDocument: function(event) {
		var req=this.app.createRestRequest({
			'path':'db/'+this.options.database+'/'+this.options.table
				+'/'+this.options.entryId+'.dat',
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
