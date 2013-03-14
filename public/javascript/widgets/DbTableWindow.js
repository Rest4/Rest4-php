var DbTableWindow=new Class({
	Extends: DbWindow,
	initialize: function(desktop,options) {
		// Default options
		this.classNames.push('DbTableWindow');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		// this.app.registerCommand('win'+this.id+'-selectTable',this.selectTable.bind(this));
		// this.app.registerCommand('win'+this.id+'-deleteTable',this.deleteTable.bind(this));
		},
	// Window
	render : function() {
		this.options.name=this.locale.title+' '+this.options.database+'&gt;'+this.options.table;
		// Drawing window
		this.parent();
		},
	// Content
	renderContent: function(req)
		{
		var tpl='<div class="box"><table><thead>'
			+'	<tr><th>'+this.locale.list_th_field+'</th><th>'+this.locale.list_th_type+'</th><th>'+this.locale.list_th_filter+'</th><th></th></tr>'
			+'</thead><tbody>';
			for(var i=0, j=this.db.table.fields.length; i<j; i++)
				tpl+='	<tr><td>'
					+(!this.dbLocale['fields_'+this.db.table.fields[i].name]?this.db.table.fields[i].name:this.dbLocale['fields_'+this.db.table.fields[i].name])
					+'</a></td><td>'+this.db.table.fields[i].type+'</td><td>'+this.db.table.fields[i].filter+'</td>'
					+'<td><a href="#win'+this.id+'-deleteField:'+this.db.table.fields.name+'" title="'+this.locale.list_delete_link_tx+' : '+this.db.table.fields[i].name
					+'" class="delete"><span>'+this.locale.list_delete_link+'</span></a></td></tr>';
		tpl+='</tbody></table></div>';
		this.view.innerHTML=tpl;
		},
	// Window destruction
	destruct : function() {
		// this.app.unregisterCommand('win'+this.id+'-selectTable');
		// this.app.unregisterCommand('win'+this.id+'-deleteTable');
		this.parent();
		}
});