var DbServerWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Default options
		this.classNames.push('DbServerWindow');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-selectDb',this.selectDb.bind(this));
		this.app.registerCommand('win'+this.id+'-deleteDb',this.deleteDb.bind(this));
		this.app.registerCommand('win'+this.id+'-addDb',this.addDb.bind(this));
	},
	// Window
	render : function() {
		this.options.name=this.locale.title;
		// Menu
		this.options.menu=[];
		this.options.menu[0]={'label':this.locale.list_add_link,'command':'addDb','title':this.locale.list_add_link_tx};
		// Drawing window
		this.parent();
		},
	// Content
	loadContent: function(dontSync)
		{
		if(!dontSync)
			this.syncWindows('loadContent');
		this.databases=null;
		this.addReq(this.app.getLoadDatasReq('/db.txt',this));
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
		var tpl='<div class="box"><table><thead>'
			+'	<tr><th>'+this.locale.list_th_database+'</th><th>'+this.locale.list_th_count+'</th><th></th></tr>'
			+'</thead><tbody>';
		for(var i=0, j=this.databases.length; i<j; i++)
			tpl+='	<tr><td><a href="#win'+this.id+'-selectDb:'+this.databases[i].database+'" title="'+this.locale.list_more_link_tx+'">'+this.databases[i].database+'</a></td><td>0</td><td><a href="#win'+this.id+'-deleteDb:'+this.databases[i].database+'" title="'+this.locale.list_delete_link_tx+'" class="delete"><span>'+this.locale.list_delete_link+'</span></a></td></tr>';
		tpl+='</tbody></table></div>';
		this.view.innerHTML=tpl;
		},
	// Add callbacks
	addDb: function()
		{
		this.app.createWindow('PromptWindow',{'name':this.locale.add_name,'legend':this.locale.add_legend,
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
		this.app.createWindow('AlertWindow',{'name':this.locale.add_done_title,'content':this.locale.add_done_content});
		this.loadContent();
		},
	addError: function()
		{
		this.app.createWindow('AlertWindow',{'name':this.locale.add_error_title,'content':this.locale.add_error_content});
		},
	// Delete
	deleteDb: function(event,params)
		{
		this.app.createWindow('ConfirmWindow',{
			'name':this.locale.del_title,
			'content':this.locale.del_content+' : "'+params[0]+'" ?',
			'onValidate':this.delConfirm.bind(this),
			'output':{'deletedDb':params[0]}
			});
		},
	delConfirm: function(event,output)
		{
		var req=this.app.createRestRequest({
			'path':'db/'+output.deletedDb+'.txt',
			'method':'delete'});
		req.addEvent('done',this.delLoaded.bind(this));
		req.addEvent('error',this.delError.bind(this));
		req.send();
		},
	delLoaded: function(req)
		{
		this.app.createWindow('AlertWindow',{'name':this.locale.del_done_title,'content':this.locale.del_done_content});
		this.loadContent();
		},
	delError: function(event,database)
		{
		this.app.createWindow('AlertWindow',{'name':this.locale.del_error_title,'content':this.locale.del_error_content});
		},
	// Select
	selectDb: function(event,params)
		{
		this.app.createWindow('DbBaseWindow',{'database':params[0]});
		},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-selectDb');
		this.app.unregisterCommand('win'+this.id+'-deleteDb');
		this.app.unregisterCommand('win'+this.id+'-addDb');
		this.parent();
		}
});