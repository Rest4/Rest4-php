var CacheWindow=new Class({
	Extends: WebWindow,
	initialize: function(app,options) {
		// Default options
		this.options.filter='';
		// Class
		this.classNames.push('CacheWindow');
		// Initializing the window
		this.parent(app,options);
		// Registering commands	
		this.app.registerCommand('win'+this.id+'-loadContent',
			this.loadContent.bind(this));
		this.app.registerCommand('win'+this.id+'-handleForm',
			this.handleForm.bind(this));
		this.app.registerCommand('win'+this.id+'-empty',
			this.emptyCache.bind(this));
		this.app.registerCommand('win'+this.id+'-delete',
			this.deleteEntry.bind(this));
		this.app.registerCommand('win'+this.id+'-modify',
			this.modifyEntry.bind(this));/*
		this.app.registerCommand('win'+this.id+'-add',
			this.addEntry.bind(this));*/
	},
	// Rendering window
	render : function() {
		// Menu
		this.options.menu.push({
			'label':this.locale.menu_reload,
			'command':'loadContent',
			'title':this.locale.menu_reload_tx
		},{
			'label':this.locale.menu_empty,
			'command':'empty',
			'title':this.locale.menu_empty_tx
		});
		// Creating form
		this.options.forms.push({
			'tpl': 
				'<form id="win' + this.id + '-handleForm" action="#win'
					+ this.id	+ '-loadContent">'
				+'	<label>' + this.locale.form_filter
				+'		<input type="text" name="filter" value="'+this.options.filter+'"'
				+'			placeholder="'+ this.locale.form_filter_placeholder + '" />'
				+'	</label>'
				+'	<input type="submit" value="'+ this.locale.form_submit + '" />'
				+'</form>',
			'label': this.locale.menu_filter,
			'title': this.locale.menu_filter_tx,
			'showAtStart': true
		});
		// Drawing window
		this.parent();
	},
	// Content
	loadContent: function (event, params) {
		this.addReq(this.app.getLoadDatasReq('/cache/xcache' + this.options.filter
			+ '.dat?mode=multiple',
			this.entries = []));
		this.parent();
	},
	renderContent: function () {
		var tpl;
		if(this.entries && this.entries.length) {
			tpl =
					'<div class="box">'
				+ '	<table>'
				+ '		<thead><tr>'
				+ '		<th>' + this.locale.list_name + '</th>'
				+ '		<th>' + this.locale.list_size + '</th>'
				/*+ '		<th></th>'*/
				+	'		<th></th>'
				+ '	</tr></thead>'
				+ '	<tbody>';
			this.entries.forEach(function(entry, index) {
				tpl+=
					'		<tr>'
				+ '			<td>/' + entry.name + '</td>'
				+ '			<td>' + entry.size + '</td>'
				+ '			<td><a href="#win' + this.id + '-modify:' + entry.name + '"'
					+ ' class="modify" title="' + this.locale.modify_link_tx+ '"><span>'
					+ this.locale.modify_link + '</span></a></td>'
				+ '			<td><a href="#win' + this.id + '-delete:' + entry.name + '"'
					+ ' class="delete" title="'	+ this.locale.delete_link_tx + '"><span>'
					+ this.locale.delete_link + '</span></a></td>'
				+ '		</tr>'
			}.bind(this));
			tpl +=
					'		</tbody>'
				+ '	</table>'
				+ '</div>';
		} else {
			tpl = '<div class="box"><p>' + this.locale.list_empty + '</p></div>';
		}
		this.view.innerHTML = tpl;
	},
	// Handle form changes
	handleForm: function (event) {
		if (event.target.get('name') == 'filter'
			&& event.target.value != this.options.filter) {
			this.options.filter = event.target.value;
		}
	},
	// Empty the whole cache system
	emptyCache: function (event, params) {
		var req=new RestRequest({
			'url':'/cache/xcache'+this.options.filter+'.dat?mode=multiple',
			'method':'delete'
		});
		req.addEvent('complete',this.emptiedCache.bind(this));
		req.send();
	},
	emptiedCache: function () {
		this.loadContent();
		this.notice(this.locale.empty_notice);
	},
	// Delete exisiting entry
	deleteEntry: function (event, params) {
		var req=new RestRequest({
			'url':'/cache/xcache/'+params[0],
			'method':'delete'
		});
		req.addEvent('complete',this.deletedEntry.bind(this));
		req.send();
	},
	deletedEntry: function () {
		this.loadContent();
		this.notice(this.locale.delete_notice);
	},
	/*/ Add a new entry
	addEntry: function (event, params) {
		this.app.createWindow('FormWindow', {
			'fields': {
				'name':'test'
			},
			'output': params,
			'onDone': this.addedEntry.bind(this)
		});
	},
	addedEntry: function () {
		this.loadContent();
		this.notice(this.locale.add_notice);
	},*/
	// Modify an entry
	modifyEntry: function (event, params) {
		this.app.createWindow('EditorWindow', {
			'path': '/cache/xcache/'+params[0],
			'onDone': this.modifiedEntry.bind(this)
		});
	},
	modifiedEntry: function () {
		this.loadContent();
		this.notice(this.locale.modify_notice);
	},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-reload');
		this.app.unregisterCommand('win'+this.id+'-handleForm');
		this.app.unregisterCommand('win'+this.id+'-empty');
		this.app.unregisterCommand('win'+this.id+'-deleteEntry');
		/*this.app.unregisterCommand('win'+this.id+'-modifyEntry');
		this.app.unregisterCommand('win'+this.id+'-addEntry');*/
		this.parent();
	}
});
