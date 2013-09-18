var DbEntryWindow=new Class({
	Extends: DbWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.database='';
		this.options.table='';
		this.options.entryId='';
		// Locale/Class name
		this.classNames.push('DbEntryWindow');
		// Required options
		this.requiredOptions.push('entryId');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-modifyEntry',
			this.modifyEntry.bind(this));
		this.app.registerCommand('win'+this.id+'-addJoinField',
			this.addJoinField.bind(this));
		this.app.registerCommand('win'+this.id+'-deleteJoinField',
			this.deleteJoinField.bind(this));
		this.app.registerCommand('win'+this.id+'-addReferField',
			this.addReferField.bind(this));
		this.app.registerCommand('win'+this.id+'-deleteReferField',
			this.deleteReferField.bind(this));
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
		this.options.menu[0]={'label':this.locale.menu_modify,
			'command':'modifyEntry','title':this.locale.menu_modify_tx};
		// Rendering window
		this.parent();
		// Registering locale dependant commands
		if(this.dbLocale['field_attached_files']) {
			this.app.registerCommand('win'+this.id+'-addJoinedFile',
				this.addJoinedFile.bind(this));
			this.app.registerCommand('win'+this.id+'-deleteJoinedFile',
				this.deleteJoinedFile.bind(this));
		}
	},
	// Content
	loadContent: function()	{
		this.db.entry=null;
		this.entries=null;
		this.db.linkedEntries=[];
		this.db.linkedTablesEntries=[];
		var uri='/db/'+this.options.database+'/'+this.options.table
			+'/'+this.options.entryId+'.dat?field=*';
		var linkNames=[];
		if(this.db.table.constraintFields) {
			for(var i=this.db.table.constraintFields.length-1; i>=0; i--) {
				if(this.db.table.constraintFields[i].linkTo) {
					linkNames.push(this.db.table.constraintFields[i].linkTo.name);
				}
			}
		}
		if(linkNames.length) {
			linkNames.sort(function(a,b) {
				return (a===b?0:(a<b?-1:1));
			});
			uri+='&field='+linkNames.join('.*&field=')+'.*';
		}
		uri+='&files=list';
		this.addReq(this.app.getLoadDatasReq(uri,this.db));
		this.parent();
	},
	renderContent: function() {
		// Content template chunks
		var tpl='<div class="box"><ul>',
			tabMenuTpl='',
			tabContentTpl='';
		// Iterating over fields
		this.db.table.fields.forEach(function(field) {
			// Main content
			if(field.name!='password'
				&&(typeof this.db.entry[field.name])!=='undefined') {
				if(field.linkTo) {
					if(this.db.entry[field.name]) {
						tpl+='	<li><b>'+(this.dbLocale['field_'+field.name]?
							this.dbLocale['field_'+field.name]:field.name)
							+' :</b> <a href="#openWindow:DbEntry'
							+':database:'+this.options.database+':table:'+field.linkTo.table
							+':entryId:'+this.db.entry[field.name].id+'">'
							+(this.db.entry[field.name].label?this.db.entry[field.name].label:
								this.db.entry[field.name].id)
							+'</a></li>';
					}
				} else if(field.options&&field.multiple) {
						tpl+='	<li><b>'+(this.dbLocale['field_'+field.name]?
							this.dbLocale['field_'+field.name]:field.name)+' :</b> ';
						if(this.db.entry[field.name]&&this.db.entry[field.name].length)
						for(var k=0, l=this.db.entry[field.name].length; k<l; k++)
							{
							tpl+=' '+(k>0?',':'')+(this.dbLocale['field_'+field.name+'_options_'
								+this.db.entry[field.name][k]]?
									this.dbLocale['field_'+field.name
										+'_options_'+this.db.entry[field.name][k]]:
									this.db.entry[field.name][k]);
							}
				} else if (field.options) {
						tpl+='	<li><b>'+(this.dbLocale['field_'+field.name]?
								this.dbLocale['field_'+field.name]:field.name)
							+' :</b> '+(this.dbLocale['field_'+field.name
								+'_options_'+this.db.entry[field.name]]?
								this.dbLocale['field_'+field.name+'_options_'
									+this.db.entry[field.name]]:
								this.db.entry[field.name])+'</li>';
				} else {
					tpl+='	<li><b>'+(this.dbLocale['field_'+field.name]?
						this.dbLocale['field_'+field.name]:field.name)
						+' :</b> '+this.db.entry[field.name]+'</li>';
				}
			}
			// Joined fields
			if(field.joins)	{
				field.joins.forEach(function(join) {
					// Tab menu
					tabMenuTpl+='		<li><a href="#win'+this.id+'-tab-'+join.name
						+':'+join.name+'" class="button">'
						+(this.dbLocale['field_'+join.name]?
							this.dbLocale['field_'+join.name]:
							this.locale.join_tab+' '+join.table)
						+'</a></li>';
					this.app.registerCommand('win'+this.id+'-tab-'+join.name,
						this.loadJoinedField.bind(this));
					// Tab content
					tabContentTpl+='	<div class="tab vbox" id="win'+this.id+'-tab-'+join.name+'">';
					tabContentTpl+=
						'		<ul class="toolbar small">'
						+'			<li><a class="button" id="joinedIds" href="#win'+this.id+'-addJoinField:'
							+join.name+'" title="'+this.locale.add_join_link_tx+'">'
							+this.locale.add_join_link+'</a></li>'
						+'		</ul>'
						+'		<div class="box">'
						+'			<p><b>'+(this.dbLocale['field_'+field.name+'_desc']?
							this.dbLocale['field_'+field.name+'_desc']:
							this.locale.join_desc+' '+join.table+'.'+join.field)
						+'			</b></p>'
						+'			<div id="win'+this.id+'-content-'+join.name+'">'
						+'					<p>'+this.locale.loading+'</p>'
						+'			</div>'
						+'		</div>'
						+'	</div>';
				}.bind(this));
			}
			// Referring fields
			if(field.references)	{
				field.references.forEach(function(ref) {
					// Tab menu
					tabMenuTpl+=
						'		<li><a href="#win'+this.id+'-tab-'+ref.name+':'+ref.name
						+'" class="button">'+(this.dbLocale['field_'+ref.name]?
							this.dbLocale['field_'+ref.name]:
							this.locale.refer_tab+' '+ref.table)
						+'</a></li>';
					this.app.registerCommand('win'+this.id+'-tab-'+ref.name,
						this.loadJoinedField.bind(this));
					// Tab content
					tabContentTpl+=
						'<div class="tab vbox" id="win'+this.id+'-tab-'+ref.name+'">'
						+'	<ul class="toolbar small">'
						+'		<li><a class="button" href="#win'+this.id+'-addReferField:'
							+ref.name+'"'+'title="'+this.locale.add_refer_link+'">'
							+this.locale.add_refer_link+'</a></li>'
						+'	</ul>'
						+'	<div class="box">'
						+'		<p><b>'+(this.dbLocale['field_'+field.name+'_desc']?
							this.dbLocale['field_'+field.name+'_desc']:
							this.locale.refer_desc+' '+ref.table+'.'+ref.field)
						+'		</b></p>'
						+'		<div id="win'+this.id+'-content-'+ref.name+'">'
						+'				<p>'+this.locale.loading+'</p>'
						+'		</div>'
						+'	</div>'
						+'</div>';
				}.bind(this));
			}
		}.bind(this));
		// Attached files
		if(this.dbLocale['field_attached_files']) {
			// Tab menu
			tabMenuTpl+='		<li><a href="#win'+this.id+'-selectTab:win'+this.id
				+'-tab-attachedFiles"'+' class="button">'
				+this.dbLocale.field_attached_files+'</a></li>';
			// Tab content
			tabContentTpl+=
				'<div class="tab vbox" id="win'+this.id+'-tab-attachedFiles">'
				+'	<ul class="toolbar small">'
				+'		<li><a class="button" id="joinedIds" href="#win'+this.id
				+'-addJoinedFile" title="'+this.locale.add_file_link_tx+'">'
				+this.locale.add_file_link+'</a></li>'
				+'	</ul>'
				+'	<div class="box">'
				+'		<p><b>'+this.dbLocale.field_attached_files_desc+'</b></p>'
				+'<div id="win'+this.id+'-content-attachedFiles">';
			if(this.db.entry.attachedFiles&&this.db.entry.attachedFiles.length) {
				tabContentTpl+='<table><tbody>';
				this.db.entry.attachedFiles.forEach(function(file) {
					tabContentTpl+='<tr><td><a href="'
						+'/fs/db/'+this.options.database+'/'+this.options.table+'/'
						+this.options.entryId+'/files/'+file.name+'">'
						+file.name+'</a></td>'
						+'<td>'+file.mime+'</td><td>'+file.size+' octets</td>'
						+'<td>'+new Date(file.lastModified*1000)+'</td>'
						+'<td><a href="#win'+this.id+'-deleteJoinedFile:'+file.name
						+'" title="'+this.locale.delete_file_link_tx
						+'" class="delete"><span>'+this.locale.delete_file_link
						+'</span></a></td></tr>';
				}.bind(this));
				tabContentTpl+='</tbody></table>';
			} else {
				tabContentTpl+='<p>'+this.locale.file_empty+'</p>';
			}
			tabContentTpl+='</div></div></div>';
		}
		
		tpl+='</ul></div>'
			+'<div class="tabbox vbox">'
			+'	<ul class="toolbar small">'+tabMenuTpl+'</ul>'
			+tabContentTpl
			+'</div>';
		this.view.innerHTML=tpl;
	},
	// Joined
	loadJoinedField: function(event,params) {
		this.app.selectTab(event,new Array('win'+this.id+'-tab-'+params[0]));
		var field=params[0], req;
		this.db.linkedTablesEntries[params[0]]={};
		// Iterating over fields
		this.db.table.fields.forEach(function(field) {
			// Joined fields
			if(field.joins)	{
				field.joins.some(function(join) {
					if(join.name!=params[0]) {
						return false;
					}
					var revJoinName=join.field+'Joins'+this.options.table[0].toUpperCase()
							+this.options.table.substring(1)+field.name[0].toUpperCase()
							+field.name.substring(1);
					req=this.app.loadDatas('/db/'+this.options.database+'/'
						+join.table+'/list.dat?field=*&field='+revJoinName+'.'+field.name
						+'&limit=0&fieldsearch='+revJoinName+'.'+field.name
						+'&fieldsearchval='+this.options.entryId+'&fieldsearchop=eq',
						this.db.linkedTablesEntries[params[0]],
						this.joinedFieldLoaded.bind(this));
					return true;
				}.bind(this));
			}
			// Referring fields
			if(field.references)	{
				field.references.some(function(ref) {
					if(ref.name!=params[0]) {
						return false;
					}
					req=this.app.loadDatas('/db/'+this.options.database+'/'+ref.table
						+'/list.dat?field=label&limit=0&fieldsearch='+ref.field
						+'&fieldsearchval='+this.options.entryId+'&fieldsearchop=eq',
						this.db.linkedTablesEntries[params[0]],
						this.joinedFieldLoaded.bind(this));
					return true;
				}.bind(this));
			}
		}.bind(this));
		req.joinName=params[0];
		return true;
	},
	joinedFieldLoaded: function(req) {
		var joinName=req.joinName, tpl='';
		// Iterating over fields
		this.db.table.fields.forEach(function(field) {
			// Joined fields
			if(field.joins)	{
				field.joins.some(function(join) {
					if(join.name!==joinName) {
						return false;
					}
					if(this.db.linkedTablesEntries[joinName].entries
						&&this.db.linkedTablesEntries[joinName].entries.length) {
						tpl+='<table><tbody>';
						this.db.linkedTablesEntries[joinName].entries.forEach(function(entry) {
							tpl+='<tr><td><a href="#openWindow:DbEntry'
								+':database:'+this.options.database+':table:'+join.table
								+':entryId:'+entry.id+'">'+(entry.label?entry.label:entry.name)
								+'</a></td>'+'<td><a href="#win'+this.id+'-deleteJoinField:'
								+join.name+':'+entry.id+'" title="'
								+this.locale.delete_join_link_tx+'" class="delete"><span>'
								+this.locale.delete_join_link+'</span></a></td></tr>';
						}.bind(this));
						tpl+='</tbody></table>';
					} else {
						tpl='<p>'+this.locale.join_empty+'</p>';
					}
					return true;
				}.bind(this));
			}
			// Referring fields
			if(field.references) {
				field.references.forEach(function(ref) {
					if(ref.name!==joinName) {
						return false;
					}
					if(this.db.linkedTablesEntries[joinName].entries
						&&this.db.linkedTablesEntries[joinName].entries.length) {
						tpl+='<table><tbody>';
						this.db.linkedTablesEntries[joinName].entries.forEach(function(entry) {
							tpl+='<tr><td><a href="#openWindow:DbEntry:database:'
								+this.options.database+':table:'+ref.table+':entryId:'
								+entry.id+'">'+(entry.label?entry.label:entry.name)+'</a></td>'
								+'<td><a href="#win'+this.id+'-deleteReferField:'+ref.name
								+':'+entry.id+'" title="'+this.locale.delete_refer_link_tx
								+'" class="delete"><span>'+this.locale.delete_refer_link
								+'</span></a></td></tr>';
						}.bind(this));
						tpl+='</tbody></table>';
					} else {
						tpl='<p>'+this.locale.refer_empty+'</p>';
					}
				}.bind(this));
			}
		}.bind(this));
		document.getElementById('win'+this.id+'-content-'+joinName).innerHTML=tpl;
	},
	// Modify an Entry
	modifyEntry: function() {
		this.app.createWindow('DbEntryFormWindow', {
			'database':this.options.database,
			'table':this.options.table,
			'entryId':this.options.entryId,
			'onDone':this.entryModified.bind(this)
		});
	},
	entryModified: function() {
		this.notice(this.dbLocale['modify_notice']);
		this.loadContent();
	},
	// Add joined field
	addJoinField : function(event,params) {
		// Iterating over fields
		this.db.table.fields.forEach(function(field) {
			// Joined fields
			if(field.joins)	{
				field.joins.some(function(join) {
					if(join.name!==params[0]) {
						return false;
					}
					var values=[];
					if(this.db.linkedTablesEntries[join.name]
						&&this.db.linkedTablesEntries[join.name].entries) {
						this.db.linkedTablesEntries[join.name].entries.forEach(function(entry) {
							values.push(entry.id);
						}.bind(this));
					}
					this.app.createWindow('DbEntriesWindow', {
						'database':this.options.database,
						'table':join.table,
						'prompt':true,
						'multiple':true,
						'output':{'values':values,'joinName':params[0]},
						'onValidate':this.joinFieldChoosed.bind(this)
					});
				}.bind(this));
			}
		}.bind(this));
	},
	joinFieldChoosed : function(event,output) {
		// Iterating over fields
		this.db.table.fields.forEach(function(field) {
			// Joined fields
			if(field.joins)	{
				field.joins.some(function(join) {
					if(join.name!==output.joinName) {
						return false;
					}
					var revJoinName=join.field+'Joins'+this.options.table[0].toUpperCase()
							+this.options.table.substring(1)+field.name[0].toUpperCase()
							+field.name.substring(1), joinId;
					// Deleting obsolete joins
					if(this.db.linkedTablesEntries[join.name]
						&&this.db.linkedTablesEntries[join.name].entries) {
						this.db.linkedTablesEntries[join.name].entries.forEach(function(entry) {
							if(-1===output.values.indexOf(entry.id)&&entry[revJoinName]&&entry[revJoinName].some(function(entry) {
								if(entry.id===this.options.entryId) {
									joinId=entry.joinId;
									return true;
								}
								return false;
							}.bind(this))) {
								var req=this.app.createRestRequest({
									'path':'db/'+this.options.database+'/'+join.bridge+'/'
										+joinId+'.dat',
									'method':'delete'
								});
								req.joinName=output.joinName;
								this.addReq(req);
							}
						}.bind(this));
					}
					// Adding newly joined values
					output.values.forEach(function (value) {
						if((!this.db.linkedTablesEntries[join.name])
							||(!this.db.linkedTablesEntries[join.name].entries)
							||!this.db.linkedTablesEntries[join.name].entries.some(function(entry) {
								return entry.id==value;
							}.bind(this))) {
							var req=this.app.createRestRequest({
								'path':'db/'+this.options.database+'/'+join.bridge+'.dat',
								'method':'post'});
							req.setHeader('Content-Type','text/varstream');
							req.options.data='#text/varstream\n'
								+'entry.'+this.options.table+'_id='+this.options.entryId+'\n'
								+'entry.'+join.table+'_id='+value;
							req.joinName=output.joinName;
							this.addReq(req);
						}
					}.bind(this));
				}.bind(this));
			}
		}.bind(this));
		this.sendReqs(function() {
			this.joinFieldAdded(output.joinName);
		}.bind(this));
	},
	joinFieldAdded : function(joinName) {
		// Iterating over fields
		this.db.table.fields.forEach(function(field) {
			// Joined fields
			if(field.joins)	{
				field.joins.some(function(join) {
					if(join.name!==joinName) {
						return false;
					}
					this.notice(this.locale.add_join_notice);
					this.db.linkedTablesEntries[join.name]=null;
					this.loadJoinedField(null,new Array(join.name));
				}.bind(this));
			}
		}.bind(this));
	},
	// Delete joined field
	deleteJoinField : function(event,params) {
		// Iterating over fields
		this.db.table.fields.forEach(function(field) {
			// Joined fields
			if(field.joins)	{
				field.joins.some(function(join) {
					if(join.name!==params[0]) {
						return false;
					}
					var revJoinName=join.field+'Joins'+this.options.table[0].toUpperCase()
							+this.options.table.substring(1)+field.name[0].toUpperCase()
							+field.name.substring(1), joinId;
					// Finding the joinId
					if(this.db.linkedTablesEntries[join.name]
						&&this.db.linkedTablesEntries[join.name].entries
						&&this.db.linkedTablesEntries[join.name].entries.some(function(entry) {
							if(entry.id==params[1]&&entry[revJoinName]&&entry[revJoinName].some(function(entry) {
								if(entry.id===this.options.entryId) {
									joinId=entry.joinId;
									return true;
								}
								return false;
							}.bind(this))) {
								return true;
							}
							return false;
						}.bind(this))) {
							// Sending the request
							this.app.createWindow('DbEntryDeleteWindow', {
								'database':this.options.database,
								'table':join.bridge,
								'entryId':joinId,
								'onDone':this.joinFieldDeleted.bind(this),
								'onError':this.joinFieldDeleteError.bind(this),
								'output':{joinName:params[0]}
							});
						}
				}.bind(this));
			}
		}.bind(this));
	},
	joinFieldDeleted : function(event, output) {
			this.notice(this.locale.delete_join_notice);
			this.db.linkedTablesEntries[output.joinName]=null;
			this.loadJoinedField(null,new Array(output.joinName));
	},
	joinFieldDeleteError : function() {
			this.notice(this.locale.delete_join_error);
	},
	// Add refered field
	addReferField : function(event,params) {
		// Iterating over fields
		this.db.table.fields.forEach(function(field) {
			// Referred fields
			if(field.references)	{
				field.references.some(function(ref) {
					if(ref.name!==params[0]) {
						return false;
					}
					var output={'refName':params[0]};
					this.app.createWindow('DbEntryFormWindow',{
						'onValidate':this.referFieldAdded.bind(this),
						'database':this.options.database,'table':ref.table,
						'output':output
					});
				}.bind(this));
			}
		}.bind(this));
	},
	referFieldAdded : function(event,output) {
		this.notice(this.locale.add_refer_notice);
		this.db.linkedTablesEntries[output.refName]=null;
		this.loadJoinedField(null,new Array(output.refName));
	},
	// Delete refered field
	deleteReferField : function(event,params) {
		// Iterating over fields
		this.db.table.fields.forEach(function(field) {
			// Referred fields
			if(field.references)	{
				field.references.some(function(ref) {
					if(ref.name!==params[0]) {
						return false;
					}
				var req=this.app.createRestRequest({
					'path':'db/'+this.options.database+'/'+ref.table+'/'+params[1]+'.dat',
					'method':'patch'});
				req.setHeader('Content-Type','text/varstream');
				req.addEvent('complete',this.referFieldDeleted.bind(this));
				req.refName=params[0];
				req.send('#text/varstream\n'
					+'entry.'+ref.field+'=null');
				}.bind(this));
			}
		}.bind(this));
	},
	referFieldDeleted : function(req)
		{
		if(201==req.status) {
			this.notice(this.locale.delete_refer_notice);
			this.db.linkedTablesEntries[req.refName]=null;
			this.loadJoinedField(null,new Array(req.refName));
		} else {
			this.notice(this.locale.delete_refer_error);
		}
	},
	// Add joined file
	addJoinedFile : function() {
		this.app.createWindow('FilesAddWindow', {
			'folder':'fs/db/'+this.options.database+'/'+this.options.table
				+'/'+this.options.entryId+'/files/',
			'onDone':this.joinedFileAdded.bind(this),
			'onError':this.joinedFileAddError.bind(this)
		});
	},
	joinedFileAdded : function() {
		this.notice(this.locale.add_file_notice);
		this.loadContent();
	},
	joinedFileAddError : function() {
		this.notice(this.locale.add_file_error);
	},
	// Delete joined file
	deleteJoinedFile: function(event,params) {
		this.app.createWindow('FilesDeleteWindow', {
			'uri':'fs/db/'+this.options.database+'/'+this.options.table
				+'/'+this.options.entryId+'/files/'+params[0],
			'onDone':this.joinedFileDeleted.bind(this),
			'onError':this.joinedFileDeleteError.bind(this)
		});
	},
	joinedFileDeleted: function() {
		this.notice(this.locale.delete_file_notice);
		this.loadContent();
	},
	joinedFileDeleteError: function() {
		this.notice(this.locale.delete_file_error);
	},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-modifyEntry');
		this.app.unregisterCommand('win'+this.id+'-addJoinField');
		this.app.unregisterCommand('win'+this.id+'-deleteJoinField');
		this.app.unregisterCommand('win'+this.id+'-addReferField');
		this.app.unregisterCommand('win'+this.id+'-deleteReferField');
		this.app.unregisterCommand('win'+this.id+'-selectTab');
		if(this.dbLocale['field_attached_files']) {
			this.app.unregisterCommand('win'+this.id+'-addJoinedFile');
			this.app.unregisterCommand('win'+this.id+'-deleteJoinedFile');
		}
		for(var i=0, j=this.db.table.fields.length; i<j; i++) {
			if(this.db.table.fields[i].joinTable
				||this.db.table.fields[i].referedField) {
				this.app.unregisterCommand('win'+this.id+'-tab-'
					+this.db.table.fields[i].name);
			}
		}
		this.parent();
	}
});
// papa maman 
