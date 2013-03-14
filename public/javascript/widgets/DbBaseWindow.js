var DbBaseWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Default options
		this.classNames.push('DbBaseWindow');
		// Required options
		this.requiredOptions.push('database');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-viewTable',this.viewTable.bind(this));
		this.app.registerCommand('win'+this.id+'-viewEntries',this.viewEntries.bind(this));
		this.app.registerCommand('win'+this.id+'-deleteTable',this.deleteTable.bind(this));
		},
	// Window
	render : function() {
		this.options.name=this.locale.title+' ('+this.options.database+')';
		// Drawing window
		this.parent();
		},
	// Content
	loadContent: function(dontSync)	{
		if(!dontSync)
			this.syncWindows('loadContent');
		this.tables=null;
		this.addReq(this.app.getLoadDatasReq('/db/'+this.options.database+'.dat',this));
		this.parent();
		},
	contentLoaded: function(req)
		{
		if(this.view)
			{
			this.renderContent();
			}
		else
			this.loaded(req);
		},
	renderContent: function(req)
		{
		var tpl='<div class="box"><table><thead>'
			+'	<tr><th>'+this.locale.list_th_table+'</th><th>'+this.locale.list_th_count+'</th><th></th></tr>'
			+'</thead><tbody>';
			for(var i=0, j=this.tables.length; i<j; i++)
				tpl+='	<tr><td><a href="#win'+this.id+'-viewTable:'+this.tables[i].name+'" title="'
					+this.locale.list_view_table_link_tx+'">'
					+(!this.locale[this.tables[i].name]?this.tables[i].name:this.locale['table_'+this.tables[i].name]+' ('+this.tables[i].name+')')
					+'</a></td><td><a href="#win'+this.id+'-viewEntries:'+this.tables[i].name+'" title="'
					+this.locale.list_view_entries_link_tx+'">'+this.tables[i].count+'</a></td><td>'
					+' <a href="#win'+this.id+'-deleteTable:'+this.tables[i].name+'" title="'+this.locale.list_delete_link_tx
					+' : '+this.tables[i].name+'" class="delete"><span>'+this.locale.list_delete_link+'</span></a></td></tr>';
		tpl+='</tbody></table></div>';
		this.view.innerHTML=tpl;
		},
	// Select table
	viewTable: function(event,params)
		{
		this.app.createWindow('DbTableWindow',{'database':this.options.database,'table':params[0]});
		},
	viewEntries: function(event,params)
		{
		this.app.createWindow('DbEntriesWindow',{'database':this.options.database,'table':params[0]});
		},
	// Delete table
	deleteTable: function(event,params)
		{
		this.app.createWindow('ConfirmWindow',{
			'name':this.locale.del_title,
			'content':this.locale.del_content,
			'onValidate':this.delConfirm.bind(this),
			'output':{'table':params[0]}
			});
		},
	delConfirm: function(event,output)
		{
		var req=this.app.createRestRequest({
			'path':'db/'+this.options.database+'/'+output.table+'.txt',
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
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-viewTable');
		this.app.unregisterCommand('win'+this.id+'-viewEntries');
		this.app.unregisterCommand('win'+this.id+'-deleteTable');
		this.parent();
		}
});