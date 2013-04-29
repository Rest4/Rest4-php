var DbEntriesWindow=new Class({
	Extends: DbWindow,
	initialize: function(app,options)
		{
		// Default options
		this.options.database='';
		this.options.table='';
		this.options.limit=(app.screenType=='small'?10:50);
		this.options.page=1;
		this.options.sortby='id';
		this.options.sortdir='asc';
		this.options.filterwith='';
		this.options.filterop=(options&&options.filterwith?'eq':'like');
		this.options.filterval='';
		this.options.mode='';
		this.options.prompt=false;
		this.options.multiple=false;
		this.options.output={};
		this.options.output.values=[];
		this.classNames.push('DbEntriesWindow');
		// Internal vars
		this.start=0;
		this.nbPage=0;
		this.count=0;
		// Initializing window
		this.parent(app,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-openEntry',this.openEntry.bind(this));
		this.app.registerCommand('win'+this.id+'-deleteEntry',this.deleteEntry.bind(this));
		this.app.registerCommand('win'+this.id+'-modifyEntry',this.modifyEntry.bind(this));
		this.app.registerCommand('win'+this.id+'-addEntry',this.addEntry.bind(this));
		this.app.registerCommand('win'+this.id+'-changePage',this.changePage.bind(this));
		this.app.registerCommand('win'+this.id+'-handleForm',this.handleForm.bind(this));
		this.app.registerCommand('win'+this.id+'-updateSelectedEntries',this.updateSelectedEntries.bind(this));	
		this.app.registerCommand('win'+this.id+'-validateSelectedEntries',this.validateSelectedEntries.bind(this));
		this.app.registerCommand('win'+this.id+'-showUtils',this.showUtils.bind(this));
		this.app.registerCommand('win'+this.id+'-pickFilterValue',this.pickFilterValue.bind(this));
		},
	// Window
	render : function()
		{
		if(this.options.mode=='oneForm')
			{
			this.addEntry();
			this.close();
			}
		this.options.name=this.dbLocale['entries_title']+' ('+this.options.database+'.'+this.options.table+')';
		// Menu
		this.options.menu[0]={'label':this.locale.menu_add,'command':'addEntry','title':this.locale.menu_add_tx};
		this.options.menu[1]={'label':this.locale.menu_sort,'command':'showUtils:sort','title':this.locale.menu_sort_tx};
		this.options.menu[2]={'label':this.locale.menu_filter,'command':'showUtils:filter','title':this.locale.menu_filter_tx};
		this.options.menu[3]={'label':this.locale.menu_nav,'command':'showUtils:nav','title':this.locale.menu_nav_tx};
		// Setting sort field
		if(this.db.table.labelFields)
			this.options.sortby=this.db.table.labelFields[0];
		// Drawing window
		this.parent();
		// Adding custom toolbars
		this.sortMenu=document.createElement('ul');
		this.sortMenu.addClass('toolbar');
		this.sortMenu.addClass('small');
		this.sortMenu.addClass('collapsed');
		var tpl='<li><form id="win'+this.id+'-handleForm:sort" action="#win'+this.id+'-handleForm:sort">'
				+'	<label>'+this.locale.sort_by+' <select name="sortby">';
		for(var i=0,j=this.db.table.fields.length; i<j; i++)
			{
			if(this.db.table.fields[i].name.indexOf('joined_')==-1)
				tpl+='		<option value="'+this.db.table.fields[i].name+'"'+
					(this.options.sortby==this.db.table.fields[i].name?' selected="selected"':'')+'>'
					+(this.dbLocale['field_'+this.db.table.fields[i].name]?
						this.dbLocale['field_'+this.db.table.fields[i].name]:
						this.db.table.fields[i].name)
					+'</option>';
			}
		tpl+='	</select></label>'
			+'	<label>'+this.locale.sort_dir+'<select name="sortdir">'
			+'		<option value="asc">'+this.locale.sort_dir_asc+'</option>'
			+'		<option value="desc">'+this.locale.sort_dir_desc+'</option>'
			+'	</select></label>'
			+'</form></li>';
		this.sortMenu.innerHTML=tpl;
		this.toolbox.appendChild(this.sortMenu);
		this.filterMenu=document.createElement('ul');
		this.filterMenu.addClass('toolbar');
		this.filterMenu.addClass('small');
		this.filterMenu.addClass('collapsed');
		tpl='<li><form id="win'+this.id+'-handleForm:filter" action="#win'+this.id+'-handleForm:filter">'
			+'	<label>'+this.locale.filter_with+' <select name="with">'
			+'		<option value="">'+this.locale.filter_field+'</option>';
		for(var i=0,j=this.db.table.fields.length; i<j; i++)
			{
			tpl+='		<option value="'+i+'">'+(this.dbLocale['field_'+this.db.table.fields[i].name]?
				this.dbLocale['field_'+this.db.table.fields[i].name]:this.db.table.fields[i].name)+'</option>';
			}
		tpl+='	</select></label>'
			+'	<select name="op" id="win'+this.id+'-filterOp">'
			+'		<option value="eq" disabled="disabled"'+(this.options.filterop=='eq'?' selected="selected"':'')+'>'
				+this.locale.filter_eq+'</option>'
			+'		<option value="supeq" disabled="disabled"'+(this.options.filterop=='supeq'?' selected="selected"':'')+'>'
				+this.locale.filter_supeq+'</option>'
			+'		<option value="sup" disabled="disabled"'+(this.options.filterop=='sup'?' selected="selected"':'')+'>'
				+this.locale.filter_sup+'</option>'
			+'		<option value="infeq" disabled="disabled"'+(this.options.filterop=='infeq'?' selected="selected"':'')+'>'
				+this.locale.filter_infeq+'</option>'
			+'		<option value="inf" disabled="disabled"'+(this.options.filterop=='inf'?' selected="selected"':'')+'>'
				+this.locale.filter_inf+'</option>'
			+'		<option value="like" disabled="disabled"'+(this.options.filterop=='like'?' selected="selected"':'')+'>'
				+this.locale.filter_like+'</option>'
			+'		<option value="elike" disabled="disabled"'+(this.options.filterop=='elike'?' selected="selected"':'')+'>'
				+this.locale.filter_elike+'</option>'
			+'		<option value="slike" disabled="disabled"'+(this.options.filterop=='slike'?' selected="selected"':'')+'>'
				+this.locale.filter_slike+'</option>'
			+'	</select>'
			+'	<input type="text" name="value" id="win'+this.id+'-filterField" />'
			+'	<input type="submit" formaction="#win'+this.id+'-pickFilterValue" name="picker"'
				+' value="Choisir" id="win'+this.id+'-filterButton" />'
			+'</form></li>';
		this.filterMenu.innerHTML=tpl;
		this.toolbox.appendChild(this.filterMenu);
		this.navMenu=document.createElement('ul');
		this.navMenu.addClass('toolbar');
		this.navMenu.addClass('small');
		this.navMenu.addClass('collapsed');
		this.toolbox.appendChild(this.navMenu);
		tpl='<li class="menu"><a href="#win'+this.id+'-changePage:first" class="button disabled">|&lt;</a></li>'
			+'<li class="menu"><a href="#win'+this.id+'-changePage:-10" class="button disabled">&lt;&lt;</a></li>'
			+'<li class="menu"><a href="#win'+this.id+'-changePage:-1" class="button disabled">&lt;</a></li>'
			+'<li class="menu"><form id="win'+this.id+'-handleForm:page" action="#win'+this.id+'-handleForm:page">'
				+'<input type="number" min="1" value="'+this.options.page+'" /></form></li>'
			+'<li class="menu"><a href="#win'+this.id+'-changePage:1" class="button disabled">&gt;</a></li>'
			+'<li class="menu"><a href="#win'+this.id+'-changePage:10" class="button disabled">&gt;&gt;</a></li>'
			+'<li class="menu"><a href="#win'+this.id+'-changePage:last" class="button disabled">&gt;|</a></li>'
			+'<li class="flex"></li>'
			+'<li class="menu"><form id="win'+this.id+'-handleForm:limit" action="#win'+this.id+'-handleForm:limit">'
			+'	<label>Items par page : <select class="limit">'
			+'		<option value="5"'+(this.options.limit==5?' selected="selected"':'')+'>5</option>'
			+'		<option value="10"'+(this.options.limit==10?' selected="selected"':'')+'>10</option>'
			+'		<option value="20"'+(this.options.limit==20?' selected="selected"':'')+'>20</option>'
			+'		<option value="50"'+(this.options.limit==50?' selected="selected"':'')+'>50</option>'
			+'		<option value="100"'+(this.options.limit==100?' selected="selected"':'')+'>100</option>'
			+'	</select></label></form>'
			+'</li>';
		this.navMenu.innerHTML=tpl;
		},
	// Content
	loadContent: function(dontSync)
		{
		if(!dontSync)
			this.syncWindows('loadContent',[
				{'option':'database','value':this.options.database},
				{'option':'table','value':this.options.table}
				]);
		this.count=null;
		this.entries=null;
		var uri='mode=count';
		uri+=(this.options.filterwith&&this.options.filterval!==''?(uri?'&':'')
			+'fieldsearch='+this.options.filterwith
			+'&fieldsearchval='+this.options.filterval
			+'&fieldsearchop='+this.options.filterop:'');
		uri='/db/'+this.options.database+'/'+this.options.table+'/list.dat'+(uri?'?'+uri:'');
		this.addReq(this.app.getLoadDatasReq(uri,this));
		var uri='mode=light'+(this.start>0?'&start='+this.start:'');
		uri+=(this.options.limit!=10?(uri?'&':'')+'limit='+this.options.limit:'');
		uri+=(this.options.sortby!='id'?(uri?'&':'')+'orderby='+this.options.sortby:'');
		uri+=(this.options.sortdir!='asc'?(uri?'&':'')+'dir='+this.options.sortdir:'');
		uri+=(this.options.filterwith&&this.options.filterval!==''?(uri?'&':'')
			+'fieldsearch='+this.options.filterwith
			+'&fieldsearchval='+this.options.filterval
			+'&fieldsearchop='+this.options.filterop:'');
		uri='/db/'+this.options.database+'/'+this.options.table+'/list.dat'+(uri?'?'+uri:'');
		this.addReq(this.app.getLoadDatasReq(uri,this));
		this.parent();
		},
	renderContent: function()
		{
		// Adding navigation menu
		this.nbPage=null;
		this.nbPage=Math.ceil(this.count/this.options.limit);
		var tpl='<div class="box">';
		if(this.entries&&this.entries.length)
			{
			if(this.options.prompt==true)
				tpl+='<form action="#win'+this.id+'-validateSelectedEntries" id="win'+this.id+'-updateSelectedEntries">';
			tpl+='<table><tbody>';
			for(var i=0, j=this.entries.length; i<j; i++)
				{
				tpl+='<td>'
					+(this.options.prompt==true?'<label><input type="checkbox" name="add" value="'
						+this.entries[i].id+'"'+(this.options.output.values.indexOf(this.entries[i].id)>=0?' checked="checked"':'')
						+' /> '+(this.entries[i].label?this.entries[i].label:this.entries[i].name)+'</label></td><td>':'')
					+'	<a href="#win'+this.id+'-openEntry:'+this.entries[i].id+'" title="'+this.locale.list_view_link_tx+' '
						+(this.entries[i].label?this.entries[i].label:this.entries[i].name)+'">'
						+(this.options.prompt==true?' '+this.locale.list_view_link+'':
							(this.entries[i].label?this.entries[i].label:this.entries[i].name))
					+'</a></td>'
					+'<td><a href="#win'+this.id+'-modifyEntry:'+this.entries[i].id
						+'" title="'+this.locale.modify_link_tx+'" class="modify"><span>'
						+this.locale.modify_link+'</span></a></td>'
					+'<td><a href="#win'+this.id+'-deleteEntry:'+this.entries[i].id
						+'" title="'+this.locale.delete_link_tx+'" class="delete"><span>'
						+this.locale.delete_link+'</span></a></td></tr>';
				}
			tpl+='</tbody></table>';
			if(this.options.prompt==true)
				tpl+='<p class="fieldrow"><input type="submit" class="button" value="ajouter" /></p>'
					+'</form>';
			}
		else
			tpl+='<p>'+this.locale.empty+'</p>'
		tpl+='</div>';
		this.view.innerHTML=tpl;
		// Navigation menu
		if(this.count<=this.options.limit)
			this.navMenu.addClass('collapsed');
		else
			this.navMenu.removeClass('collapsed');
		this.navMenu.getElements('input')[0].setAttribute('max',this.nbPage);
		this.navMenu.getElements('input')[0].value=this.options.page;
		var links=this.navMenu.getElements('a');
		if(this.options.page==1)
			{
			links[0].addClass('disabled');
			links[2].addClass('disabled');
			}
		else
			{
			links[0].removeClass('disabled');
			links[2].removeClass('disabled');
			}
		if(this.options.page<11)
			links[1].addClass('disabled');
		else
			links[1].removeClass('disabled');
		if(this.options.page==this.nbPage)
			{
			links[3].addClass('disabled');
			links[5].addClass('disabled');
			}
		else
			{
			links[3].removeClass('disabled');
			links[5].removeClass('disabled');
			}
		if(this.options.page+10>this.nbPage)
			links[4].addClass('disabled');
		else
			links[4].removeClass('disabled');
		},
	// Open an entry
	openEntry: function(event,params)
		{
		this.app.createWindow('DbEntryWindow',{'database':this.options.database,
			'table':this.options.table,'entryId':params[0]});
		},
	// Add an Entry
	addEntry: function()
		{
		this.app.createWindow('DbEntryFormWindow',{'database':this.options.database,
			'table':this.options.table,'onDone':this.entryAdded.bind(this)});
		},
	entryAdded: function(event, output)
		{
		if(this.options.prompt&&output.entryId)
			{
			if(!this.options.multiple)
				{
				this.options.output.values=new Array(output.entryId);
				this.validateSelectedEntries();
				return;
				}
			else
				{
				this.options.output.values.push(output.entryId);
				}
			}
		this.notice(this.dbLocale['add_notice']);
		this.options.page=1;
		this.start=0;
		this.loadContent();
		},
	// Modify entries
	modifyEntry: function(event,params)
		{
		this.app.createWindow('DbEntryFormWindow',{
			'database':this.options.database,
			'table':this.options.table,
			'entryId':params[0],
			'onDone':this.entryModified.bind(this)
			});
		},
	entryModified: function(req)
		{
		this.notice(this.locale.modify_notice);
		this.loadContent();
		},
	// Delete entries
	deleteEntry: function(event,params)
		{
		this.app.createWindow('ConfirmWindow',{
			'name':this.locale.delete_title,
			'content':this.locale.delete_content,
			'onValidate':this.deleteConfirmed.bind(this),
			'output':{'deletedEntry':params[0]}
			});
		},
	deleteConfirmed: function(event,output)
		{
		if(this.options.prompt)
			{
			var index=this.options.output.values.indexOf(output.deletedEntry);
			if(index>-1)
				this.options.output.values.splice(index,1);
			}
		var req=this.app.createRestRequest({
			'path':'db/'+this.options.database+'/'+this.options.table+'/'+output.deletedEntry+'.dat',
			'method':'delete'});
		req.addEvent('complete',this.deleteCompleted.bind(this));
		req.send();
		},
	deleteCompleted: function(req)
		{
		if(410==req.status)
			{
			this.notice(this.locale.delete_notice);
			this.options.page=1;
			this.start=0;
			this.loadContent();
			}
		else
			this.notice(this.locale.delete_error);
		},
	// Change page
	changePage: function(event,params)
		{
		var target=(params[0]);
		if(target!='first'&&target!='last')
			{
			if((parseInt(this.options.page)+parseInt(target))>0
				&&(parseInt(this.options.page)+parseInt(target))<=(this.nbPage))
				{
				this.options.page=parseInt(this.options.page)+parseInt(target);
				this.start=(this.options.page*this.options.limit)-this.options.limit;
				this.loadContent();
				}
			}
		else if(target=='first')
			{
			this.options.page=1;
			this.start=0;
			this.loadContent();
			}
		else if(target=='last')
			{
			this.options.page=this.nbPage;
			this.start=(this.nbPage*this.options.limit)-this.options.limit;
			this.loadContent();
			}
		},
	// handleForm
	handleForm: function(event, params)
		{
		switch(params[0])
			{
			case 'sort':
				if(event.target.get('name')=='sortby'&&this.options.sortby!=event.target.value)
					{
					this.options.sortby=event.target.value;
					this.loadContent();
					}
				else if(event.target.get('name')=='sortdir'&&this.options.sortdir!=event.target.value)
					{
					this.options.sortdir=event.target.value;
					this.loadContent();
					}
				break;
			case 'filter':
				if(event.target.get('name')=='with'&&this.options.filterwith!=event.target.value)
					{
					$('win'+this.id+'-filterField').setStyle('display','none');
					$('win'+this.id+'-filterButton').setStyle('display','none');
					if(event.target.value&&this.db.table.fields[event.target.value])
						{
						this.options.filterwith=this.db.table.fields[event.target.value].name;
						if(this.db.table.fields[event.target.value].linkedTable)
							{
							$('win'+this.id+'-filterOp').getElements('option').each(function(opt)
								{
								if(opt.value=='like'||opt.value=='elike'||opt.value=='slike')
									opt.set('disabled',true);
								else
									opt.set('disabled',false);
								}, this);
							$('win'+this.id+'-filterButton').setStyle('display','inline');
							$('win'+this.id+'-filterButton').setAttribute('formaction','#win'+this.id+'-pickFilterValue:'
								+this.db.table.fields[event.target.value].linkedTable);
							}
						else
							{
							$('win'+this.id+'-filterOp').getElements('option').each(function(opt)
								{
								opt.set('disabled',false);
								}, this);
							$('win'+this.id+'-filterField').setStyle('display','inline');
							}
						}
					else
						{
						this.options.filterwith='';
						}
					this.options.filterval='';
					this.options.page=1;
					this.start=0;
					$('win'+this.id+'-filterField').value='';
					this.loadContent();
					}
				else if(event.target.get('name')=='op'&&this.options.filterop!=event.target.value)
					{
					this.options.filterop=event.target.value;
					this.options.page=1;
					this.start=0;
					this.loadContent();
					}
				else if(event.target.get('name')=='value'&&this.options.filterval!=event.target.value)
					{
					if(this.reloadTimer)
						clearTimeout(this.reloadTimer);
					this.options.filterval=event.target.value;
					this.options.page=1;
						this.start=0;
					this.reloadTimer=this.loadContent.delay(1500,this);
					}
				break;
			case 'page':
				if(event.target.nodeName=='INPUT')
					{
					if(event.target.value!=this.options.page)
						{
						this.options.page=parseInt(event.target.value);
						this.start=(this.options.page*this.options.limit)-this.options.limit;
						this.loadContent();
						}
					}
				break;
			case 'limit':
				if(event.target.nodeName=='SELECT')
					{
					if(event.target.value!=this.options.limit)
						{
						this.options.page=1;
						this.start=0;
						this.options.limit=parseInt(event.target.value);
						this.loadContent();
						}
					}
				break;
			}
		},
	// pickFilterValue
	pickFilterValue: function(event, params)
		{
		var w=this.app.createWindow('DbEntriesWindow',{
			'database':this.options.database,
			'table':params[0],
			'prompt':true,
			'required':true,
			'output': {'values':(this.options.filterval!==''?new Array(this.options.filterval):[])},
			'onValidate':this.pickedFilterValue.bind(this)
			});
		},
	pickedFilterValue: function(event, output)
		{
		if(this.options.filterval=output.values[0])
			{
			this.options.filterval=output.values[0];
			this.loadContent();
			}
		},
	// Prompt functions
	updateSelectedEntries: function(event)
		{
		if(event.target.nodeName=='INPUT'&&event.target.hasAttribute('type')
			&&event.target.getAttribute('type')=='checkbox')
			{
			if(!this.options.multiple)
				{
				this.options.output.values=[];
				}
			var index=this.options.output.values.indexOf(event.target.value);
			if(event.target.checked==true)
				{
				if(index<0)
					this.options.output.values.push(event.target.value);
				}
			else
				{
				if(index>-1)
					this.options.output.values.splice(index,1);
				}
			var checkboxes=this.view.getElements('input');
			for(var i=checkboxes.length-1; i>=0; i--)
				{
				if(checkboxes[i].get('type')=='checkbox')
					{
					if(this.options.output.values.indexOf(checkboxes[i].value)<0)
						{
						checkboxes[i].removeAttribute('checked');
						checkboxes[i].checked=false;
						}
					else
						{
						checkboxes[i].setAttribute('checked','checked');
						checkboxes[i].checked=true;
						}
					}
				}
			}
		},
	validateSelectedEntries: function(event)
		{
		this.close();
		this.fireEvent('validate', [event, this.options.output]);
		},
	// Tools
	showUtils: function(event,params)
		{
		if(this[params[0]+'Menu'].hasClass('collapsed'))
			this[params[0]+'Menu'].removeClass('collapsed');
		else
			this[params[0]+'Menu'].addClass('collapsed');
		},
	// Window destruction
	destruct : function()
		{
		this.app.unregisterCommand('win'+this.id+'-openEntry');
		this.app.unregisterCommand('win'+this.id+'-deleteEntry');
		this.app.unregisterCommand('win'+this.id+'-modifyEntry');
		this.app.unregisterCommand('win'+this.id+'-addEntry');
		this.app.unregisterCommand('win'+this.id+'-changePage');
		this.app.unregisterCommand('win'+this.id+'-handleForm');
		this.app.unregisterCommand('win'+this.id+'-updateSelectedEntries');
		this.app.unregisterCommand('win'+this.id+'-validateSelectedEntries');
		this.app.unregisterCommand('win'+this.id+'-showUtils');
		this.app.unregisterCommand('win'+this.id+'-pickFilterValue');
		this.parent();
		}
	});
