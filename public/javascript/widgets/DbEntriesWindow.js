var DbEntriesWindow=new Class({
	Extends: DbWindow,
	initialize: function(app,options) {
		// Default options
		this.options.database='';
		this.options.table='';
		this.options.limit=(app.screenType=='small'?10:50);
		this.options.page=1;
		this.options.sortby='id';
		this.options.sortdir='asc';
		this.options.filterwith='';
		this.options.filterop='eq';
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
		this.app.registerCommand('win' + this.id + '-loadContent',
			this.askReload.bind(this));
		this.app.registerCommand('win' + this.id + '-openEntry',
			this.openEntry.bind(this));
		this.app.registerCommand('win' + this.id + '-deleteEntry',
			this.deleteEntry.bind(this));
		this.app.registerCommand('win' + this.id + '-modifyEntry',
			this.modifyEntry.bind(this));
		this.app.registerCommand('win' + this.id + '-addEntry',
			this.addEntry.bind(this));
		this.app.registerCommand('win' + this.id + '-changePage',
			this.changePage.bind(this));
		this.app.registerCommand('win' + this.id + '-handleForm',
			this.handleForm.bind(this));
		this.app.registerCommand('win' + this.id + '-updateSelectedEntries',
			this.updateSelectedEntries.bind(this));	
		this.app.registerCommand('win' + this.id + '-validateSelectedEntries',
			this.validateSelectedEntries.bind(this));
		this.app.registerCommand('win' + this.id + '-showUtils',
			this.showUtils.bind(this));
		this.app.registerCommand('win' + this.id + '-pickFilterValue',
			this.pickFilterValue.bind(this));
	},
	// Window
	render : function() {
		if(this.options.mode=='oneForm') {
			this.addEntry();
			this.close();
		}
		this.options.name=this.dbLocale['entries_title']
			+' ('+this.options.database+'.'+this.options.table+')';
		// Menu
		this.options.menu.push({
			'label':this.locale.menu_add,
			'command':'addEntry',
			'title':this.locale.menu_add_tx
		},{
			'label':this.locale.menu_nav,
			'command':'showUtils:nav',
			'title':this.locale.menu_nav_tx
		});
		// Setting sort field
		if(this.db.table.labelFields) {
			this.options.sortby=this.db.table.labelFields[0];
		}
		// Setting filter field
		if(!this.options.filterwith) {
		  if(this.db.table.labelFields) {
		    this.options.filterwith=this.db.table.labelFields[0];
		    this.options.filterop='like';
		  } else {
		    this.options.filterwith='id';
		    this.options.filterop='eq';
		  }
		}
		// Adding forms
		this.options.forms.push({
			'tpl':
				  '<form id="win' + this.id + '-handleForm:sort"'
				+ ' action="#win' + this.id + '-loadContent">'
				+ '	<label>' + this.locale.sort_by + ' <select name="sortby">'
				+ this.db.table.fields.map(function(field,i) {
					return (
					'		<option value="' + field.name + '"' + (this.options.sortby == field
				? '     selected="selected"' : '') + '>'
				+ '       ' + (this.dbLocale['field_'+field.name] || field.name)
				+ '   </option>');
				  }.bind(this)).join('')
				+ '	</select></label>'
				+ '	<label>'+this.locale.sort_dir+' <select name="sortdir">'
				+ '		<option value="asc"' + ('asc' === this.options.sortdir
				? '     selected="selected"' : '') + '>'
				+ '     ' + this.locale.sort_dir_asc
				+ '   </option>'
				+ '		<option value="desc"' + ('asc' === this.options.sortdir
				? '     selected="selected"' : '') + '>'
				+ '     ' + this.locale.sort_dir_desc
				+ '   </option>'
				+ '	</select></label>'
				+ '	<input type="submit" value="' + this.locale.form_sort_submit + '" />'
				+ '</form>',
			'label':this.locale.menu_sort,
			'title':this.locale.menu_sort_tx
		},{
			'tpl':
				  '<form id="win' + this.id + '-handleForm:filter"'
				+ '	action="#win' + this.id + '-loadContent">'
				+ '	<label>'+this.locale.filter_with + ' <select name="with">'
				+ this.db.table.fields.map(function(field,i) {
					return ''
				+ '   <option'
				+ '     value="' + field.name + '"'	+ (this.options.filterwith==field.name
				? '     selected="selected"':'')+'>'
				+ '     ' + (this.dbLocale['field_'+field.name] || field.name)
				+ '</option>';
				}.bind(this)).join('')
				+ '	</select></label>'
				+ '	<select name="op" id="win' + this.id + '-filterOp">'
				+ ['eq','supeq','sup','infeq','inf','like','elike','slike'].map(
					function(sort, index) {
						return ' <option value="'+sort+'"'
						  + (index> 4 ? ' disabled="disabled"' : '')
							+ (this.options.filterop==sort?' selected="selected"':'') + '>'
							+ this.locale['filter_'+sort] + '</option>';
					}.bind(this)
				).join('')
				+ '	</select>'
				+ '	<input type="text" name="value" id="win' + this.id + '-filterField" />'
				+ '	<input type="submit" formaction="#win' + this.id + '-pickFilterValue"'
				+ '		name="picker" value="Choisir" id="win' + this.id + '-filterButton"'
				+ '   style="display: none;" />'
				+ '	<input type="submit" value="'+this.locale.form_filter_submit+'" />'
				+ '</form>',
			'label':this.locale.menu_filter,
			'title':this.locale.menu_filter_tx
		});
		// Drawing window
		this.parent();
		// Adding navigation toolbar
		this.navMenu=document.createElement('ul');
		this.navMenu.addClass('toolbar');
		this.navMenu.addClass('small');
		this.navMenu.addClass('collapsed');
		this.toolbox.appendChild(this.navMenu);
		this.navMenu.innerHTML=
		      '<li class="menu"><a href="#win' + this.id + '-changePage:first"'
		    + ' class="button disabled">|&lt;</a></li>'
		  	+ '<li class="menu"><a href="#win' + this.id + '-changePage:-10"'
		  	+ ' class="button disabled">&lt;&lt;</a></li>'
			  + '<li class="menu"><a href="#win' + this.id + '-changePage:-1"'
			  + ' class="button disabled">&lt;</a></li>'
			  + '<li class="menu"><form id="win' + this.id + '-handleForm:page"'
			  + ' action="#win' + this.id + '-handleForm:page">'
				+ ' <input type="number" min="1" value="' + this.options.page + '" />'
				+ '</form></li>'
			  + '<li class="menu"><a href="#win' + this.id + '-changePage:1"'
			  + ' class="button disabled">&gt;</a></li>'
			  + '<li class="menu"><a href="#win' + this.id + '-changePage:10"'
			  + ' class="button disabled">&gt;&gt;</a></li>'
			  + '<li class="menu"><a href="#win' + this.id + '-changePage:last"'
			  + ' class="button disabled">&gt;|</a></li>'
			  + '<li class="flex"></li>'
			  + '<li class="menu"><form id="win' + this.id + '-handleForm:limit"'
			  + ' action="#win' + this.id + '-handleForm:limit">'
			  + '	<label>Items par page : <select class="limit">'
			  + '		<option value="5"' + (this.options.limit == 5
			  ? '     selected="selected"' : '') + '>5</option>'
			  + '		<option value="10"' + (this.options.limit == 10
			  ? '     selected="selected"' : '') + '>10</option>'
			  + '		<option value="20"' + (this.options.limit == 20
			  ? '     selected="selected"' : '') + '>20</option>'
			  + '		<option value="50"' + (this.options.limit == 50
			  ? '     selected="selected"' : '') + '>50</option>'
			  + '		<option value="100"' + (this.options.limit == 100
			  ? '     selected="selected"' : '') + '>100</option>'
			  + '	</select></label></form>'
			  + '</li>';
	},
	// Content
	loadContent: function(dontSync) {
		if(!dontSync) {
			this.syncWindows('loadContent', [{
			  'option': 'database',
			  'value': this.options.database
			}, {
			  'option': 'table',
			  'value': this.options.table
			}]);
		}
		this.count=null;
		this.entries=null;
		var uri='mode=count';
		uri+=(this.options.filterwith&&this.options.filterval!==''?(uri?'&':'')
			+'fieldsearch='+this.options.filterwith
			+'&fieldsearchval='+this.options.filterval
			+'&fieldsearchop='+this.options.filterop:'');
		uri='/db/'+this.options.database+'/'+this.options.table+'/list.dat'+(uri?'?'+uri:'');
		this.addReq(this.app.getLoadDatasReq(uri,this));
		uri='field='+(this.db.table.labelFields?'label':'*')
			+(this.start>0?'&start='+this.start:'')
			+(this.options.limit!=10?(uri?'&':'')+'limit='+this.options.limit:'')
			+(this.options.sortby!=''?(uri?'&':'')+'orderby='+this.options.sortby
			+'&dir='+this.options.sortdir:'')
			+(this.options.filterwith&&this.options.filterval!==''?(uri?'&':'')
				+'fieldsearch='+this.options.filterwith
				+'&fieldsearchval='+this.options.filterval
				+'&fieldsearchop='+this.options.filterop:'');
		uri='/db/'+this.options.database+'/'+this.options.table+'/list.dat'+(uri?'?'+uri:'');
		this.addReq(this.app.getLoadDatasReq(uri,this));
		this.parent();
	},
	renderContent: function() {
		// Adding navigation menu
		this.nbPage=null;
		this.nbPage=Math.ceil(this.count/this.options.limit);
		this.view.innerHTML=
		    '<div class="box">' + (this.entries && this.entries.length 
		  ? (this.options.prompt == true
		  ? ' <form action="#win' + this.id + '-validateSelectedEntries"'
		  + '   id="win' + this.id + '-updateSelectedEntries">' : '')
			+ '   <table><tbody>' + this.entries.map(function(entry) {
return  '     <tr>'
      + '     <td>' + entry.id + '</td>'
      + '     <td>' + (this.options.prompt == true
      ? '       <label><input type="checkbox" name="add"'
      + '         value="' + entry.id + '"' + (-1 != this.options.output.values.indexOf(entry.id)
      ? '         checked="checked"' : '') +' /> '
      + '         ' + (entry.label || entry.name)
      + '       </label>'
      + '     </td>'
      + '     <td>' : '')
			+ '       <a href="#win' + this.id + '-openEntry:' + entry.id + '"'
			+ '         title="' + this.locale.list_view_link_tx + ' ' + (entry.label || entry.name) + '">'
						+(this.options.prompt==true
			? '         ' + this.locale.list_view_link : (entry.label || entry.name))
			+ '       </a>'
			+ '     </td>'
			+ '     <td><a href="#win' + this.id + '-modifyEntry:' + entry.id + '"'
			+ '       title="'+this.locale.modify_link_tx+'" class="modify"><span>'
			+ '       ' + this.locale.modify_link
			+ '     </span></a></td>'
			+ '     <td><a href="#win' + this.id + '-deleteEntry:' + entry.id + '"'
			+ '       title="' + this.locale.delete_link_tx + '" class="delete"><span>'
			+ '       ' + this.locale.delete_link
			+ '     </span></a></td>'
			+ '     </tr>';
			}.bind(this)).join('')
			+ '   </tbody></table>' + (this.options.prompt == true
			? '   <p class="fieldrow">'
			+ '     <input type="submit" class="button" value="ajouter" />'
			+ '   </p>' : '')
			+ ' </form>'
		  : ' <p>' + this.locale.list_empty + '</p>')
		  + '</div>';
		// Navigation menu
		if(this.count <= this.options.limit) {
			this.navMenu.addClass('collapsed');
		} else {
			this.navMenu.removeClass('collapsed');
		}
		this.navMenu.getElements('input')[0].setAttribute('max', this.nbPage);
		this.navMenu.getElements('input')[0].value = this.options.page;
		var links = this.navMenu.getElements('a');
		if(this.options.page == 1) {
			links[0].addClass('disabled');
			links[2].addClass('disabled');
		} else {
			links[0].removeClass('disabled');
			links[2].removeClass('disabled');
		}
		if(this.options.page < 11) {
			links[1].addClass('disabled');
		} else {
			links[1].removeClass('disabled');
		}
		if(this.options.page == this.nbPage) {
			links[3].addClass('disabled');
			links[5].addClass('disabled');
		} else {
			links[3].removeClass('disabled');
			links[5].removeClass('disabled');
		}
		if(this.options.page + 10 > this.nbPage) {
			links[4].addClass('disabled');
		} else {
			links[4].removeClass('disabled');
		}
	},
	// Open an entry
	openEntry: function(event,params) {
		this.app.createWindow('DbEntryWindow', {
		  'database': this.options.database,
			'table': this.options.table,
			'entryId': params[0]
		});
	},
	// Add an Entry
	addEntry: function() {
		this.app.createWindow('DbEntryFormWindow', {
		  'database': this.options.database,
			'table': this.options.table,
			'onDone': this.entryAdded.bind(this)
		});
	},
	entryAdded: function(output) {
		if(this.options.prompt&&output.entryId) {
			if(!this.options.multiple) {
				this.options.output.values = new Array(output.entryId);
				this.validateSelectedEntries();
				return;
			} else {
				this.options.output.values.push(output.entryId);
			}
		}
		this.notice(this.dbLocale['add_notice']);
		this.options.page = 1;
		this.start = 0;
		this.loadContent();
	},
	// Modify entries
	modifyEntry: function(event,params) {
		this.app.createWindow('DbEntryFormWindow', {
			'database':this.options.database,
			'table':this.options.table,
			'entryId':params[0],
			'onDone':this.entryModified.bind(this)
		});
	},
	entryModified: function(req) {
		this.notice(this.locale.modify_notice);
		this.loadContent();
	},
	// Delete entries
	deleteEntry: function(event,params) {
		this.app.createWindow('DbEntryDeleteWindow', {
			'database':this.options.database,
			'table':this.options.table,
			'entryId':params[0],
			'onDone':this.entryDeleted.bind(this),
			'onError':this.entryDeleteError.bind(this)
		});
	},
	entryDeleted: function() {
		this.notice(this.locale.delete_notice);
		this.loadContent();
	},
	entryDeleteError: function() {
		this.notice(this.locale.delete_error);
	},
	// Change page
	changePage: function(event,params) {
		var target=(params[0]);
		if(target!='first'&&target!='last') {
			if((parseInt(this.options.page)+parseInt(target))>0
				&&(parseInt(this.options.page)+parseInt(target))<=(this.nbPage)) {
				this.options.page=parseInt(this.options.page)+parseInt(target);
				this.start=(this.options.page*this.options.limit)-this.options.limit;
				this.loadContent();
			}
		} else if(target=='first') {
			this.options.page=1;
			this.start=0;
			this.loadContent();
		} else if(target=='last') {
			this.options.page=this.nbPage;
			this.start=(this.nbPage*this.options.limit)-this.options.limit;
			this.loadContent();
		}
	},
	askReload: function() {
		// Cancelling previous reload timeout
		if(this.reloadTimeout)
			clearTimeout(this.reloadTimeout);
		// Prepare the reload
		this.reloadTimeout=this.loadContent.delay(1500,this);
	},
	// handleForm
	handleForm: function(event, params) {
		switch(params[0]) {
			case 'sort':
				if(event.target.get('name')=='sortby'
					&&this.options.sortby!=event.target.value) {
					this.options.sortby=event.target.value;
					this.askReload();
				} else if(event.target.get('name')=='sortdir'
					&&this.options.sortdir!=event.target.value) {
					this.options.sortdir=event.target.value;
					this.askReload();
				}
				break;
			case 'filter':
				if(event.target.get('name')=='with'
					&&this.options.filterwith!=event.target.value) {
					$('win' + this.id + '-filterField').setStyle('display','none');
					$('win' + this.id + '-filterButton').setStyle('display','none');
					if(!(event.target.value&&this.db.table.fields.some(function(field) {
						if(field.name===event.target.value) {
							this.options.filterwith=field.name;
							if(field.linkTo) {
								$('win' + this.id + '-filterOp').getElements('option').each(
									function(opt) {
										if(opt.value=='like'||opt.value=='elike'||opt.value=='slike')
											opt.set('disabled',true);
										else
											opt.set('disabled',false);
									}, this);
								$('win' + this.id + '-filterButton').setStyle('display','inline');
								$('win' + this.id + '-filterButton').setAttribute('formaction',
									'#win' + this.id + '-pickFilterValue:'+field.linkTo.table);
							} else {
								$('win' + this.id + '-filterOp').getElements('option').each(function(opt) {
									opt.set('disabled',false);
								}, this);
								$('win' + this.id + '-filterField').setStyle('display','inline');
							}
							return true;
						}
					}.bind(this)))) {
						this.options.filterwith='';
					}
					this.options.filterval='';
					this.options.page=1;
					this.start=0;
					$('win' + this.id + '-filterField').value='';
					this.askReload();
				} else if(event.target.get('name')=='op'
					&&this.options.filterop!=event.target.value) {
					this.options.filterop=event.target.value;
					this.options.page=1;
					this.start=0;
					this.askReload();
				} else if(event.target.get('name')=='value'
					&&this.options.filterval!=event.target.value) {
					this.options.filterval=event.target.value;
					this.options.page=1;
					this.start=0;
					this.askReload();
				}
				break;
			case 'page':
				if(event.target.nodeName=='INPUT') {
					if(event.target.value!=this.options.page) {
						this.options.page=parseInt(event.target.value);
						this.start=(this.options.page*this.options.limit)-this.options.limit;
						this.askReload();
					}
				}
				break;
			case 'limit':
				if(event.target.nodeName=='SELECT') {
					if(event.target.value!=this.options.limit) {
						this.options.page=1;
						this.start=0;
						this.options.limit=parseInt(event.target.value);
						this.askReload();
					}
				}
				break;
		}
	},
	// pickFilterValue
	pickFilterValue: function(event, params) {
		this.app.createWindow('DbEntriesWindow', {
			'database':this.options.database,
			'table':params[0],
			'prompt':true,
			'required':true,
			'output': {
				'values':(this.options.filterval!==''?
					new Array(this.options.filterval):[])
			},
			'onValidate':this.pickedFilterValue.bind(this)
		});
	},
	pickedFilterValue: function(event, output) {
		if(this.options.filterval=output.values[0]) {
			// Cancelling previous reload timeout
			if(this.reloadTimeout)
				clearTimeout(this.reloadTimeout);
			// Saving the new filter value
			this.options.filterval=output.values[0];
			// Reloading content
			this.askReload();
		}
	},
	// Prompt functions
	updateSelectedEntries: function(event) {
		if(event.target.nodeName=='INPUT'&&event.target.hasAttribute('type')
			&&event.target.getAttribute('type')=='checkbox') {
			if(!this.options.multiple) {
				this.options.output.values=[];
			}
			var index=this.options.output.values.indexOf(event.target.value);
			if(event.target.checked==true) {
				if(index<0)
					this.options.output.values.push(event.target.value);
			} else {
				if(index>-1)
					this.options.output.values.splice(index,1);
			}
			var checkboxes=this.view.getElements('input');
			for(var i=checkboxes.length-1; i>=0; i--) {
				if(checkboxes[i].get('type')=='checkbox') {
					if(this.options.output.values.indexOf(checkboxes[i].value)<0) {
						checkboxes[i].removeAttribute('checked');
						checkboxes[i].checked=false;
					} else {
						checkboxes[i].setAttribute('checked','checked');
						checkboxes[i].checked=true;
					}
				}
			}
		}
	},
	validateSelectedEntries: function(event) {
		this.close();
		this.fireEvent('validate', [event, this.options.output]);
	},
	// Tools
	showUtils: function(event,params) {
		if(this[params[0]+'Menu'].hasClass('collapsed')) {
			this[params[0]+'Menu'].removeClass('collapsed');
		} else {
			this[params[0]+'Menu'].addClass('collapsed');
		}
	},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win' + this.id + '-loadContent');
		this.app.unregisterCommand('win' + this.id + '-openEntry');
		this.app.unregisterCommand('win' + this.id + '-deleteEntry');
		this.app.unregisterCommand('win' + this.id + '-modifyEntry');
		this.app.unregisterCommand('win' + this.id + '-addEntry');
		this.app.unregisterCommand('win' + this.id + '-changePage');
		this.app.unregisterCommand('win' + this.id + '-handleForm');
		this.app.unregisterCommand('win' + this.id + '-updateSelectedEntries');
		this.app.unregisterCommand('win' + this.id + '-validateSelectedEntries');
		this.app.unregisterCommand('win' + this.id + '-showUtils');
		this.app.unregisterCommand('win' + this.id + '-pickFilterValue');
		this.parent();
	}
});
