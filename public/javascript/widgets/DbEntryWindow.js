var DbEntryWindow=new Class({
	Extends: DbWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.database='';
		this.options.table='';
		this.options.entryId='';
		this.classNames.push('DbEntryWindow');
		// Required options
		this.requiredOptions.push('entryId');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-modifyEntry',this.modifyEntry.bind(this));
		this.app.registerCommand('win'+this.id+'-openJoinField',this.openJoinField.bind(this));
		this.app.registerCommand('win'+this.id+'-addJoinField',this.addJoinField.bind(this));
		this.app.registerCommand('win'+this.id+'-deleteJoinField',this.deleteJoinField.bind(this));
		this.app.registerCommand('win'+this.id+'-selectTab',this.app.selectTab);
		// Setting vars
		this.db.linkedEntries=[];
		this.db.linkedTablesEntries=[];
		},
	// Window
	render : function() {
		this.options.name=this.dbLocale['entry_title'];
		// Menu
		this.options.menu=[];
		this.options.menu[0]={'label':this.locale.menu_modify,'command':'modifyEntry','title':this.locale.menu_modify_tx};
		// Rendering window
		this.parent();
		// Registering locale dependant commands
		if(this.dbLocale['field_attached_files'])
			{
			this.app.registerCommand('win'+this.id+'-addJoinedFile',this.addJoinedFile.bind(this));
			this.app.registerCommand('win'+this.id+'-deleteJoinedFile',this.deleteJoinedFile.bind(this));
			}
		},
	// Content
	loadContent: function()	{
		this.db.entry=null;
		this.entries=null;
		this.db.linkedEntries=[];
		this.db.linkedTablesEntries=[];
		this.addReq(this.app.getLoadDatasReq('/db/'+this.options.database+'/'+this.options.table+'/'+this.options.entryId+'.dat?mode=extend',this.db));
		this.parent();
		},
	renderContent: function() {
		var db=this.db;
		var tpl='<div class="box"><ul>';
		for(var i=0, j=db.table.fields.length; i<j; i++)
			{
			if(db.table.fields[i].name!='password'&&typeof(db.entry[db.table.fields[i].name])!=='undefined'&&!(db.table.fields[i].joinTable||db.table.fields[i].referedField))
				{
				if(db.table.fields[i].linkedTable)
					{
					if(typeof(db.entry[db.table.fields[i].name+'_id'])!=='undefined')
						tpl+='	<li><b>'+(this.dbLocale['field_'+db.table.fields[i].name]?this.dbLocale['field_'+db.table.fields[i].name]:db.table.fields[i].name)+' :</b> <a href="#win'+this.id+'-openJoinField:'+db.table.fields[i].linkedTable+':'+db.entry[db.table.fields[i].name+'_id']+'">'+(db.entry[db.table.fields[i].name+'_label']?db.entry[db.table.fields[i].name+'_label']:db.entry[db.table.fields[i].name+'_id'])+'</a></li>';
					}
				else if(db.table.fields[i].options)
					{
					if(db.table.fields[i].multiple)
						{
						tpl+='	<li><b>'+(this.dbLocale['field_'+db.table.fields[i].name]?this.dbLocale['field_'+db.table.fields[i].name]:db.table.fields[i].name)+' :</b> ';
						if(db.entry[db.table.fields[i].name]&&db.entry[db.table.fields[i].name].length)
						for(var k=0, l=db.entry[db.table.fields[i].name].length; k<l; k++)
							{
							tpl+=' '+(k>0?',':'')+(this.dbLocale['field_'+db.table.fields[i].name+'_options_'+db.entry[db.table.fields[i].name][k]]?this.dbLocale['field_'+db.table.fields[i].name+'_options_'+db.entry[db.table.fields[i].name][k]]:db.entry[db.table.fields[i].name][k]);
							}
						}
					else
						tpl+='	<li><b>'+(this.dbLocale['field_'+db.table.fields[i].name]?this.dbLocale['field_'+db.table.fields[i].name]:db.table.fields[i].name)+' :</b> '+(this.dbLocale['field_'+db.table.fields[i].name+'_options_'+db.entry[db.table.fields[i].name]]?this.dbLocale['field_'+db.table.fields[i].name+'_options_'+db.entry[db.table.fields[i].name]]:db.entry[db.table.fields[i].name])+'</li>';
					}
				else
					tpl+='	<li><b>'+(this.dbLocale['field_'+db.table.fields[i].name]?this.dbLocale['field_'+db.table.fields[i].name]:db.table.fields[i].name)+' :</b> '+db.entry[db.table.fields[i].name]+'</li>';
				}
			}
		tpl+='</ul></div>'
			+'<div class="tabbox vbox">'
			+'	<ul class="toolbar small">';
		for(var i=0, j=db.table.fields.length; i<j; i++)
			{
			if(db.table.fields[i].joinTable||db.table.fields[i].referedField)
				{
				tpl+='		<li><a href="#win'+this.id+'-tab-'+db.table.fields[i].name+':'+db.table.fields[i].name+'" class="button">'+(this.dbLocale['field_'+db.table.fields[i].name]?this.dbLocale['field_'+db.table.fields[i].name]:(db.table.fields[i].referedField?this.dbLocale['field_refered']:this.dbLocale['field_joined'])+' '+db.table.fields[i].linkedTable)+'</a></li>';
				this.app.registerCommand('win'+this.id+'-tab-'+db.table.fields[i].name,this.loadJoinedField.bind(this));
				}
			}
		if(this.dbLocale['field_attached_files'])
			{
			tpl+='		<li><a href="#win'+this.id+'-selectTab:win'+this.id+'-tab-attachedFiles" class="button">'+this.dbLocale['field_attached_files']+'</a></li>';
			}
		tpl+='	</ul>';
		for(var i=0, j=db.table.fields.length; i<j; i++)
			{
			if(db.table.fields[i].joinTable||db.table.fields[i].referedField)
				{
				tpl+='	<div class="tab vbox" id="win'+this.id+'-tab-'+db.table.fields[i].name+'">';
				if(!db.table.fields[i].referedField)
					{
					var jVals='';
					if(db.entry[db.table.fields[i].name]&&db.entry[db.table.fields[i].name].length)
					for(var k=0, l=db.entry[db.table.fields[i].name].length; k<l; k++)
						jVals+=(jVals?',':'')+db.entry[db.table.fields[i].name][k].id;
					tpl+='		<ul class="toolbar small">'
						+'			<li><a class="button" id="joinedIds" href="#win'+this.id+'-addJoinField:'+db.table.fields[i].linkedTable+'">'+this.locale.add+'</a></li>'
						+'		</ul>';
					}
				tpl+='		<div class="box">'
					+'			<p><b>'+(this.dbLocale['field_'+db.table.fields[i].name+'_desc']?this.dbLocale['field_'+db.table.fields[i].name+'_desc']:(db.table.fields[i].referedField?this.dbLocale['field_refered_desc']:this.dbLocale['field_joined_desc'])+' '+db.table.fields[i].linkedTable)+'</b></p>';
					tpl+='			<div id="win'+this.id+'-content-'+db.table.fields[i].name+'"><p>'+this.locale.loading+'</p></div>';
				tpl+='				</div>';
				tpl+='		</div>';
				}
			}
		if(this.dbLocale['field_attached_files'])
			{
			tpl+='<div class="tab vbox" id="win'+this.id+'-tab-attachedFiles">';
			tpl+='	<ul class="toolbar small">'
				+'		<li><a class="button" id="joinedIds" href="#win'+this.id+'-addJoinedFile">'+this.locale.add+'</a></li>'
				+'	</ul>';
			tpl+='	<div class="box">'
				+'		<p><b>'+this.dbLocale['field_attached_files_desc']+'</b></p>';
			tpl+='<div id="win'+this.id+'-content-attachedFiles">';
			if(db.entry['attached_files']&&db.entry['attached_files'].length)
				{
				tpl+='<ul>';
				for (var i=0, j=db.entry['attached_files'].length; i<j; i++)
					tpl+='<li><a href="/fs/db/'+this.options.database+'/'+this.options.table+'/'+this.options.entryId+'/files/'+db.entry['attached_files'][i].name+'">'
					+db.entry['attached_files'][i].name+'</a>, '
					+db.entry['attached_files'][i].mime+', '
					+db.entry['attached_files'][i].size+' octets, '
					+new Date(db.entry['attached_files'][i].lastModified*1000)
					+'<a href="#win'+this.id+'-deleteJoinedFile:'+i+'" class="delete"><span>X</span></a>'
					+'</li>';
				tpl+='</ul>';
				}
			tpl+='</div>';					
			tpl+='	</div>';
			tpl+='</div>';
			}
		tpl+='</div>';
		this.view.innerHTML=tpl;
		},
	// Joined
	loadJoinedField: function(event,params)
		{
		this.app.selectTab(event,new Array('win'+this.id+'-tab-'+params[0]));
		var db=this.db;
		var field=params[0];
		var tpl='';
		for(var i=0, j=this.db.table.fields.length; i<j; i++)
			{
			if(this.db.table.fields[i].name==field)
				{
				if(!this.db.linkedTablesEntries[this.db.table.fields[i].linkedTable])
					{
					this.db.linkedTablesEntries[this.db.table.fields[i].linkedTable]={};
					if(this.db.table.fields[i].referedField)
						var req=this.app.loadDatas('/db/'+this.options.database+'/'+this.db.table.fields[i].linkedTable+'/list.dat?limit=0&fieldsearch='+this.db.table.fields[i].linkedField+'&fieldsearchval='+this.options.entryId+'&fieldsearchop=eq',this.db.linkedTablesEntries[this.db.table.fields[i].linkedTable],this.joinedFieldLoaded.bind(this));
					else
						var req=this.app.loadDatas('/db/'+this.options.database+'/'+this.db.table.fields[i].linkedTable+'/list.dat?mode=join&joinField=joined_'+this.options.table+'&limit=0&fieldsearch=joined_'+this.options.table+'&fieldsearchval='+this.options.entryId+'&fieldsearchop=eq',this.db.linkedTablesEntries[this.db.table.fields[i].linkedTable],this.joinedFieldLoaded.bind(this));
					req.field=field;
					}
				else
					{
					this.joinedFieldLoaded({'field':field});
					}
				break;
				}
			}
		return true;
		},
	joinedFieldLoaded: function(req)
		{
		var db=this.db;
		var field=req.field;
		var tpl='';
		for(var i=0, j=db.table.fields.length; i<j; i++)
			{
			if(db.table.fields[i].name==field)
				{
				if(db.linkedTablesEntries[db.table.fields[i].linkedTable]&&db.linkedTablesEntries[db.table.fields[i].linkedTable].entries&&db.linkedTablesEntries[db.table.fields[i].linkedTable].entries.length)
					{
					tpl+='<table><tbody>';
					for(var m=0, n=db.linkedTablesEntries[db.table.fields[i].linkedTable].entries.length; m<n; m++)
						{
						if(!db.table.fields[i].referedField)
							{
							var join_id=0;
							for(var k=0, l=this.db.linkedTablesEntries[db.table.fields[i].linkedTable].entries[m]['joined_'+this.options.table].length; k<l; k++)
								{
								if(this.db.linkedTablesEntries[db.table.fields[i].linkedTable].entries[m]['joined_'+this.options.table][k].id==this.options.entryId)
									{
									join_id=this.db.linkedTablesEntries[db.table.fields[i].linkedTable].entries[m]['joined_'+this.options.table][k].join_id;
									break;
									}
								}
							tpl+='<tr><td><a href="#win'+this.id+'-openJoinField:'+db.table.fields[i].linkedTable+':'+db.linkedTablesEntries[db.table.fields[i].linkedTable].entries[m].id+'">'+(db.linkedTablesEntries[db.table.fields[i].linkedTable].entries[m].label?db.linkedTablesEntries[db.table.fields[i].linkedTable].entries[m].label:db.linkedTablesEntries[db.table.fields[i].linkedTable].entries[m].name)+'</a></td>'
							+'<td><a href="#win'+this.id+'-deleteJoinField:'+db.table.fields[i].linkedTable+':'+join_id+'" class="delete"><span>X</span></a></td></tr>';
							}
						else
							{
							tpl+='<tr><td><a href="#win'+this.id+'-openJoinField:'+db.table.fields[i].linkedTable+':'+db.linkedTablesEntries[db.table.fields[i].linkedTable].entries[m].id+'">'+(db.linkedTablesEntries[db.table.fields[i].linkedTable].entries[m].label?db.linkedTablesEntries[db.table.fields[i].linkedTable].entries[m].label:db.linkedTablesEntries[db.table.fields[i].linkedTable].entries[m].name)+'</a></td>'
							+'<td><a href="#win'+this.id+'-deleteJoinField:'+db.table.fields[i].linkedTable+':'+join_id+'" class="delete"><span>X</span></a></td></tr>';
							}
						}
					tpl+='</tbody></table>';
					}
				else
					{
					tpl='<p>'+this.locale.empty+'</p>';
					}
				break;
				}
			}
		var content=document.getElementById('win'+this.id+'-content-'+field);
		content.innerHTML=tpl;
		},
	// Modify an Entry
	modifyEntry: function()
		{
		this.app.createWindow('DbEntryFormWindow',{'database':this.options.database,'table':this.options.table,'entryId':this.options.entryId,'onDone':this.entryModified.bind(this)});
		},
	entryModified: function()
		{
		this.notice(this.dbLocale['modify_title']);
		this.loadContent();
		},
	// Add joined field
	addJoinField : function(event,params)
		{
		var values=[];
		if(this.db.linkedTablesEntries[params[0]]&&this.db.linkedTablesEntries[params[0]].entries)
		for(var i=0, j=this.db.linkedTablesEntries[params[0]].entries.length; i<j; i++)
			{
			values.push(this.db.linkedTablesEntries[params[0]].entries[i].id);
			}
		this.app.createWindow('DbEntriesWindow',{
			'database':this.options.database,
			'table':params[0],
			'prompt':true,
			'multiple':true,
			'output':{'values':values,'table':params[0]},
			'onValidate':this.joinFieldChoosed.bind(this)
			});
		},
	joinFieldChoosed : function(event,output)
		{
		var table, req, content;
		if(output.table<this.options.table)
			table=output.table+'_'+this.options.table;
		else table=this.options.table+'_'+output.table;
		if(this.db.linkedTablesEntries[output.table]&&this.db.linkedTablesEntries[output.table].entries)
		for(var i=0, j=this.db.linkedTablesEntries[output.table].entries.length; i<j; i++)
			{
			if(output.values.indexOf(this.db.linkedTablesEntries[output.table].entries[i].id)<0)
				{
				var join_id=0;
				for(var k=0, l=this.db.linkedTablesEntries[output.table].entries[i]['joined_'+this.options.table].length; k<l; k++)
					{
					if(this.db.linkedTablesEntries[output.table].entries[i]['joined_'+this.options.table][k].id==this.options.entryId)
						{
						join_id=this.db.linkedTablesEntries[output.table].entries[i]['joined_'+this.options.table][k].join_id;
						break;
						}
					}
				req=this.app.createRestRequest({
					'path':'db/'+this.options.database+'/'+table+'/'+join_id+'.dat',
					'method':'delete'});
				req.addEvent('done',this.joinFieldAdded.bind(this));
				req.table=output.table;
				this.addReq(req);
				req.send(content);
				}
			}
		for(var k=0, l=output.values.length; k<l; k++)
			{
			var inside=false;
			for(var i=0; i<j; i++)
				{
				if(output.values[k]==this.db.linkedTablesEntries[output.table].entries[i].id)
					{
					inside=true;
					break;
					}
				}
			if(!inside)
				{
				content='#application/internal\nentry.'+this.options.table+'_id='+this.options.entryId+'\nentry.'+output.table+'_id='+output.values[k];
				var req=this.app.createRestRequest({
					'path':'db/'+this.options.database+'/'+table+'.dat',
					'method':'post'});
				req.addEvent('done',this.joinFieldAdded.bind(this));
				req.table=output.table;
				this.addReq(req);
				req.send(content);
				}
			}
		},
	joinFieldAdded : function(req)
		{
		this.removeReq(req);
		if(!this.reqs.length)
			{
			//this.app.createWindow('AlertWindow',{'name':this.locale.joinFieldAdded_title,'content':this.locale.joinFieldAdded_content});
			this.db.linkedTablesEntries[req.table]=null;
			this.loadJoinedField(null,new Array('joined_'+req.table));
			}
		},
	// Delete joined field
	deleteJoinField : function(event,params)
		{
		var table;
		if(params[0]<this.options.table)
			table=params[0]+'_'+this.options.table;
		else table=this.options.table+'_'+params[0];
		var req=this.app.createRestRequest({
			'path':'db/'+this.options.database+'/'+table+'/'+params[1]+'.dat',
			'method':'delete'});
		req.send();
		this.db.linkedTablesEntries[params[0]]=null;
		this.loadJoinedField(null,new Array('joined_'+params[0]));
		},
	joinFielddeleted : function(event, output)
		{
		},
	//Open join field
	openJoinField : function(event,params)
		{
		this.app.createWindow('DbEntryWindow',{'database':this.options.database,'table':params[0],'entryId':params[1]});
		},
	// Add joined file
	addJoinedFile : function()
		{
		this.app.createWindow(
			'PromptUserFileWindow',
			{'onValidate':this.joinedFileChoosed.bind(this)});
		},
	joinedFileChoosed : function (event, output)
		{
		var req=this.app.createRestRequest({
			'path':'fs/db/'+this.options.database+'/'+this.options.table+'/'+this.options.entryId+'/files/'+output.files[0].name+'?force=yes',
			'method':'put'});
		req.addEvent('complete',this.joinedFileAdded.bind(this));
		req.setHeader('Content-Type','text/base64url');
		req.send(output.files[0].content);
		},
	joinedFileAdded : function(req)
		{
		if(req.status==201)
			{
			this.notice(this.locale.file_added);
			this.loadContent(this);
			}
		else
			this.notice(this.locale.file_not_added);
		},
	deleteJoinedFile : function(event, params)
		{
		var p=document.createElement('p');
		p.innerHTML=this.db.entry['attached_files'][params[0]].name;
		var req=this.app.createRestRequest({
			'path':'fs/db/'+this.options.database+'/'+this.options.table+'/'+this.options.entryId+'/files/'+p.textContent,
			'method':'delete'});
		req.addEvent('complete',this.joinedFileDeleted.bind(this));
		req.send();
		},
	joinedFileDeleted : function(req)
		{
		if(req.status==410)
			{
			this.notice(this.locale.file_deleted);
			this.loadContent(this);
			}
		else
			this.notice(this.locale.file_not_deleted);
		},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-modifyEntry');
		this.app.unregisterCommand('win'+this.id+'-openJoinField');
		this.app.unregisterCommand('win'+this.id+'-addJoinField');
		this.app.unregisterCommand('win'+this.id+'-deleteJoinField');
		this.app.unregisterCommand('win'+this.id+'-selectTab');
		if(this.dbLocale['field_attached_files'])
			{
			this.app.unregisterCommand('win'+this.id+'-addJoinedFile');
			this.app.unregisterCommand('win'+this.id+'-deleteJoinedFile');
			}
		for(var i=0, j=this.db.table.fields.length; i<j; i++)
			{
			if(this.db.table.fields[i].joinTable||this.db.table.fields[i].referedField)
				{
				this.app.unregisterCommand('win'+this.id+'-tab-'+this.db.table.fields[i].name);
				}
			}
		this.parent();
		}	
});
