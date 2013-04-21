var DbServerWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Default options
		this.classNames.push('DbServerWindow');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-delete',this.deleteDb.bind(this));
		this.app.registerCommand('win'+this.id+'-add',this.addDb.bind(this));
	},
	// Window
	render : function() {
		this.options.name=this.locale.title;
		// Menu
		this.options.menu=[];
		this.options.menu[0]={'label':this.locale.menu_add,'command':'add','title':this.locale.menu_add_tx};
		// Drawing window
		this.parent();
		},
	// Content
	loadContent: function(dontSync)
		{
		if(!dontSync)
			this.syncWindows('loadContent');
		this.databases=null;
		this.addReq(this.app.getLoadDatasReq('/db.dat',this));
		this.parent();
		},
	contentLoaded: function(req)
		{
		if(this.view)
			{
			if(this.databases)
				{
				this.removeReq(req);
				this.renderContent();
				}
			}
		else
			this.loaded(req);
		},
	renderContent: function(req)
		{
		var tpl='<div class="box">';
		if(this.databases&&this.databases.length)
			{
			tpl+='<table><thead><tr>'
				+'	<th>'+this.locale.list_th_database+'</th>'
				+'<th>'+this.locale.list_th_tables+'</th>'
				+'<th>'+this.locale.list_th_characterSet+'</th>'
				+'<th>'+this.locale.list_th_collation+'</th>'
				+'	<th></th>'
				+'</tr></thead><tbody>';
			for(var i=0, j=this.databases.length; i<j; i++)
				{
				tpl+='<tr>'
					+'	<td><a href="#openWindow:DbBase:database:'+this.databases[i].database
						+'" title="'+this.locale.list_more_link_tx+'">'
						+this.databases[i].database+'</a></td>'
					+'	<td>'+this.databases[i].tables+'</td>'
					+'	<td>'+this.databases[i].characterSet+'</td>'
					+'	<td>'+this.databases[i].collation+'</td>'
					+'	<td><a href="#win'+this.id+'-delete:'+this.databases[i].database
						+'" title="'+this.locale.delete_link_tx+'" class="delete"><span>'
						+this.locale.delete_link+'</span></a></td>'
					+'</tr>';
				}
			tpl+='</tbody></table>';
			}
		else
			tpl+='<p>'+this.locale.list_empty+'</p>';
		tpl+='</div>';
		this.view.innerHTML=tpl;
		},
	// Add
	addDb: function()
		{
		this.app.createWindow('PromptWindow',{'name':this.locale.add_title,'legend':this.locale.add_legend,
			'label':this.locale.add_label,'placeholder':this.locale.add_placehoder,'onSubmit':this.addingDb.bind(this)});
		},
	addingDb: function(database, output)
		{
		var req=this.app.createRestRequest({
			'path':'db/'+output.entry.value+'.dat',
			'method':'put'});
		req.addEvent('done',this.addLoaded.bind(this));
		req.addEvent('error',this.addError.bind(this));
		req.send();
		},
	addLoaded: function()
		{
		this.notice(this.locale.add_done);
		this.loadContent();
		},
	addError: function()
		{
		this.notice(this.locale.add_error);
		},
	// Delete
	deleteDb: function(event,params)
		{
		this.app.createWindow('ConfirmWindow',{
			'name':this.locale.delete_title,
			'content':this.locale.delete_content+' : "'+params[0]+'" ?',
			'onValidate':this.deleteConfirmed.bind(this),
			'output':{'deletedDb':params[0]}
			});
		},
	deleteConfirmed: function(event,output)
		{
		var req=this.app.createRestRequest({
			'path':'db/'+output.deletedDb+'.dat',
			'method':'delete'});
		req.addEvent('complete',this.deleteCompleted.bind(this));
		req.send();
		},
	deleteCompleted: function(req)
		{
		if(410==req.status)
			{
			this.notice(this.locale.delete_done);
			this.loadContent();
			}
		else
			this.notice(this.locale.delete_error);
		},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-delete');
		this.app.unregisterCommand('win'+this.id+'-add');
		this.parent();
		}
});