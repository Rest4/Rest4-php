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
		this.app.registerCommand('win'+this.id+'-add',this.addTable.bind(this));
		this.app.registerCommand('win'+this.id+'-delete',this.deleteTable.bind(this));
		},
	// Window
	render : function() {
		this.options.name=this.locale.title+' ('+this.options.database+')';
		// Menu
		this.options.menu=[];
		this.options.menu[0]={'label':this.locale.menu_add,'command':'add','title':this.locale.menu_add_tx};
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
		var tpl='<div class="box">';
		if(this.tables&&this.tables.length)
			{
			tpl+='<table><thead><tr>'
				+'	<th>'+this.locale.list_th_table+'</th>'
				+'	<th>'+this.locale.list_th_rows+'</th>'
				+'	<th>'+this.locale.list_th_engine+'</th>'
				+'	<th></th>'
				+'	</tr></thead>'
				+'<tbody>';
				for(var i=0, j=this.tables.length; i<j; i++)
					{
					tpl+='<tr><td>'
						+'	<a href="#openWindow:DbTable:database:'+this.options.database
							+':table:'+this.tables[i].name+'" title="'+this.locale.list_view_table_link_tx+'">'
							+(!this.locale[this.tables[i].name]?this.tables[i].name:
								this.locale['table_'+this.tables[i].name]+' ('+this.tables[i].name+')')
						+'	</a>'
						+'</td>'
						+'	<td>'
						+'	<a href="#openWindow:DbEntries:database:'+this.options.database
							+':table:'+this.tables[i].name+'" title="'+this.locale.list_view_entries_link_tx+'">'
							+this.tables[i].rows
						+'</a>'
						+'</td>'
						+'	<td>'
							+this.tables[i].engine
						+'</a>'
						+'</td>'
						+'<td>'
						+'	<a href="#win'+this.id+'-delete:'+this.tables[i].name+'" title="'
							+this.locale.delete_link_tx+'" class="delete"><span>'
							+this.locale.delete_link+'</span></a>'
						+'</td></tr>';
					}
			tpl+='</tbody></table>';
			}
		else
			tpl+='<p>'+this.locale.list_empty+'</p>';
		tpl+='</div>';
		this.view.innerHTML=tpl;
		},
	// Add
	addTable: function()
		{
		this.app.createWindow('PromptWindow',{'name':this.locale.add_title,'legend':this.locale.add_legend,
			'label':this.locale.add_label,'placeholder':this.locale.add_placehoder,'onSubmit':this.addConfirmed.bind(this)});
		},
	addConfirmed: function(database, output)
		{
		var req=this.app.createRestRequest({
			'path':'db/'+this.options.database+'/'+output.entry.value+'.dat',
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
	deleteTable: function(event,params)
		{
		this.app.createWindow('ConfirmWindow',{
			'name':this.locale.delete_title,
			'content':this.locale.delete_content,
			'onValidate':this.delConfirm.bind(this),
			'output':{'table':params[0]}
			});
		},
	delConfirm: function(event,output)
		{
		var req=this.app.createRestRequest({
			'path':'db/'+this.options.database+'/'+output.table+'.txt',
			'method':'delete'});
		req.addEvent('complete',this.deleteLoaded.bind(this));
		req.send();
		},
	deleteLoaded: function(req)
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
		this.app.unregisterCommand('win'+this.id+'-add');
		this.app.unregisterCommand('win'+this.id+'-delete');
		this.parent();
		}
});