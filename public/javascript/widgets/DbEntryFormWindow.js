var DbEntryFormWindow=new Class({
	Extends: FormWindow,
	initialize: function(desktop, options) {
		// Default options
		this.options.database = '';
		this.options.table = '';
		this.options.entryId = '';
		this.options.selectLimit = 50;
		this.options.light = false;
		this.classNames.push('DbEntryFormWindow');
		this.classNames.push('DbWindow');
		// Required options
		this.requiredOptions.push('database', 'table');
		// Initializing window
		this.parent(desktop, options);
		// Setting vars
		this.linkEntries = [];
	},
	// Window
	load : function() {
		// Trying to load table locale
		var req=this.app.getLoadLocaleReq(
			'Db'+this.options.table.substring(0,1).toUpperCase()
			+this.options.table.substring(1)+'Table',null,false,true
		);
		if(req) {
			req.canFail=true;
			this.addReq(req);
		}
		// Getting table schema
		this.addReq(this.app.getLoadDatasReq('/db/'+this.options.database+'/'
			+this.options.table+'.dat',this.db={}));
		this.parent();
	},
	loaded: function() {
		// Choosing the right locale
		if(this.app.locales['Db'+this.options.table.substring(0,1).toUpperCase()
			+this.options.table.substring(1)+'Table']) {
			this.dbLocale=this.app.locales[
				'Db'+this.options.table.substring(0,1).toUpperCase()
				+this.options.table.substring(1)+'Table'
			];	
		} else {
			this.dbLocale=this.app.locales['DbWindow'];
		}
		// Setting window name
		this.options.name=this.dbLocale[
			''+(this.options.entryId?'modify':'add')+'_title'
		];
		this.parent();
	},
	// Content
	loadContent: function()	{
		this.linkEntries = [];
		// Loading linked tables values
		this.db.table.fields.forEach(function(field) {
		  if(this.options[field.name] && this.options[field.name].selectLimit) {
		    field.selectLimit = this.options[field.name].limit;
		  } else {
		    field.selectLimit = this.options.selectLimit;
		  }
			if(field.linkTo) {
				if(!this.linkEntries[field.linkTo.name]) {
					this.addReq(this.app.getLoadDatasReq(
						'/db/' + this.options.database + '/' + field.linkTo.table
							+ '/list.dat?field=label&limit='
							+ (field.selectLimit < Infinity ? field.selectLimit : 0),
						this.linkEntries[field.linkTo.name] = {}
					));
				}
			}
			if((!this.options.entryId) && (!this.options.light)
			  && field.joins && field.joins.length) {
				field.joins.forEach(function(join){
					if(!this.linkEntries[join.name]) {
						this.addReq(this.app.getLoadDatasReq(
							'/db/' + this.options.database + '/' + join.table
								+ '/list.dat?field=label&limit='
								+ (field.selectLimit < Infinity ? field.selectLimit : 0),
							this.linkEntries[join.name] = {}
						));
					}
				}.bind(this));
			}
		}.bind(this));
		// Loading current values
		if(this.options.entryId) {
			this.loadEntryContent();
		}
		this.parent();
	},
	loadEntryContent: function() {
		var uri='/db/'+this.options.database+'/'+this.options.table
			+'/'+this.options.entryId+'.dat?field=*';
		if((!this.options.entryId)&&(!this.options.light)) {
			var joinNames=[];
			if(this.db.table.constraintFields) {
				this.db.table.constraintFields.forEach(function(field) {
					if(field.joins) {
						field.joins.forEach(function(join) {
							joinNames.push(join.name);
						});
					}
				}.bind(this));
			}
			if(joinNames.length) {
				joinNames.sort(function(a,b) {
					return (a===b?0:(a<b?-1:1));
				});
				uri+='&field='+joinNames.join('.*&field=')+'.id';
			}
		}
		uri+='&files=list';
		this.addReq(this.app.getLoadDatasReq(uri,this.db));
	},
	renderContent: function() {
		this.prepareForm();
		this.parent();
	},
	prepareForm: function() {
		var field;
		this.options.fieldsets=[{
			'name':'entry',
			'label':this.dbLocale['fieldset'],
			'fields':[]
		}];
		this.db.table.fields.forEach(function(origField) {
			if(origField.name!='id') {
				field = {
				  name: origField.name,
				  label: this.dbLocale['field_'+origField.name] || origField.name,
				  required: (origField.name != 'password' || !this.options.entryId ?
					  origField.required : false),
				  title: this.dbLocale['field_'+origField.name+'_title'] || '',
				  placeholder:
				    this.dbLocale['field_'+origField.name+'_placeholder'] || '',
				  pattern: origField.pattern || '',
				  multiple: !!origField.multiple
				};
				if(origField.input=='select') {
					if(origField.options) {
						field.input='select';
						field.options = origField.options.map(function (option) {
							var newOption = {
							  value: option.value,
							  name : ( this.dbLocale['field_' + origField.name
							    + '_options_' + option.value] ?
								  this.dbLocale['field_' + origField.name
									  + '_options_' + option.value] :
									option.value)
							};
							if(this.options.entryId && this.db.entry[origField.name]
								&& -1 !== this.db.entry[origField.name].indexOf(option.value)) {
								newOption.selected = true;
							} else if(this.options.output
								&& this.options.output[origField.name] == option.value) {
								newOption.selected = true;
							} else if(origField.defaultValue !== undefined
								&& origField.defaultValue == option.value) {
								newOption.selected = true;
							}
							return newOption;
						}.bind(this));
					} else {
						if(this.linkEntries[origField.linkTo.name].entries
						  && this.linkEntries[origField.linkTo.name].entries.length
						    < origField.selectLimit) {
							field.input='select';
							field.options=[];
							this.linkEntries[origField.linkTo.name].entries
							  .forEach(function (entry) {
								var option = {
								  value: entry.id,
								  name: entry.label || entry.name
								};
								if(this.options.entryId
									&&((field.multiple&&this.db.entry[origField.name]
									&&this.db.entry[origField.name].indexOf(entry.id)>-1)
										||((!field.multiple)&&this.db.entry[origField.name]
										&&this.db.entry[origField.name]==entry.id))) {
									option.selected=true;
								} else if(this.options.output
								  &&this.options.output[origField.name]==entry.id) {
									option.selected=true;
								} else if(origField.defaultValue!==undefined
									&&origField.defaultValue==entry.id) {
									option.selected=true;
								}
								field.options.push(option);
							}.bind(this));
						} else {
							field.input='picker';
							field.window='DbEntriesWindow';
							field.options={
								'database':this.options.database,
								'table':origField.linkTo.table,
								'prompt':true
							};
							if(this.options.entryId&&this.db.entry[origField.name]) {
								field.defaultValue=[this.db.entry[origField.name]];
							} else if(this.options.output&&this.options.output[origField.name]) {
								field.defaultValue=[this.options.output[origField.name]];
							} else if(origField.defaultValue!==undefined) {
								field.defaultValue=[origField.defaultValue];
							}
						}
					}
				} else {
					field.input=origField.input;
					if(this.options.entryId&&this.db.entry[origField.name]) {
						field.defaultValue=this.db.entry[origField.name];
					} else if(this.options.output&&this.options.output[origField.name]) {
						field.defaultValue=this.options.output[origField.name];
					} else if(origField.defaultValue!==undefined) {
						field.defaultValue=origField.defaultValue;
					}
					if(['email','tel','date','time','text','number']
						.indexOf(origField.type)!==-1) {
						field.type=origField.type;
					} else if(origField.type=='datetime') {
						field.type='datetime-local';
					}
					// Setting min/max attributes
					if(undefined!==origField.max&&
						['number','date','datetime','time','text']
							.indexOf(origField.type)!==-1) {
						field.max=origField.max;
					}
					if(undefined!==origField.min&&
						['number','date','datetime','time'].indexOf(origField.type)!==-1) {
						field.min=origField.min;
					}
					// Setting float numbers pace
					if(origField.type=='number') {
						if(origField.filter=='float') {
							if(origField.decimals) {
								field.step='0.';
								for(var k=origField.decimals; k>1; k--) {
									field.step+='0';
								}
								field.step+='1';
							} else {
								field.step='0.0000001';
							}
						} else {
							field.step='1';
						}
					}
				}
				this.options.fieldsets[0].fields.push(field);
			}
		}.bind(this));
		// Joined fields
		if((!this.options.light)&&!this.options.entryId) {
			this.db.table.fields.forEach(function(origField) {
				if(origField.joins&&origField.joins.length) {
					origField.joins.forEach(function(join) {
						// Preparing the field
						field={};
						field.label=(this.dbLocale['field_'+join.name]?
							this.dbLocale['field_'+join.name]:join.name);
						field.title=(this.dbLocale['field_'+join.name+'_title']?
							this.dbLocale['field_'+join.name]:'');
						field.placeholder=(this.dbLocale['field_'+join.name+'_placeholder']?
							this.dbLocale['field_'+join.name+'_placeholder']:'');
						field.name=join.name;
						field.multiple=true;
						field.input='picker';
						field.window='DbEntriesWindow';
						field.options={
							'database':this.options.database,
							'table':join.table,
							'prompt':true
						};
						// Setting the current value
						field.defaultValue=[];
						if(this.db.entry&&this.db.entry[join.name]
							&&this.db.entry[join.name].length) {
							this.db.entry[join.name].forEach(function(entry) {
								field.defaultValue.push(entry.id);
							});
						} else if(this.options.output[join.name]) {
								if(!(this.options.output[join.name] instanceof Array))
									throw Error('Joined fields defaultValue must be an array'
										+'('+join.name+').');
								field.defaultValue=this.options.output[join.name];
						} else if('undefined' != typeof origField.defaultValue) {
							field.defaultValue=[origField.defaultValue];
						}
						// Adding the field
						this.options.fieldsets[0].fields.push(field);
					}.bind(this));
				}
			}.bind(this));
		}
		// Files
		var i=0;
		while(this.dbLocale['field_file'+(i?i:'')]) {
			this.options.fieldsets[0].fields.push({
				'name':'file'+i,
				'label':this.dbLocale['field_file'+(i?i:'')],
				'input':'picker','type':'file',
				'defaultValue':(this.db.entry&&this.db.entry.attached_files
					&&this.db.entry.attached_files[i]?
					this.db.entry.attached_files[i].name:''),
				'defaultUri':(
					this.db.entry&&this.db.entry.attached_files
					&&this.db.entry.attached_files[i]?
					'/fs/db/'+this.options.database+'/'+this.options.table+'/'
					+this.options.entryId+'/files/'+this.db.entry.attached_files[i].name:
					''
				),
				'options':{'filter':(this.dbLocale['field_file'+(i?i:'')+'_mime']?
					this.dbLocale['field_file'+(i?i:'')+'_mime']:'')}});
			i++;
		}
	},
	submit: function(event) {
		if(this.parseOutput()) {
			this.sendEntry();
		}
	},
	sendEntry: function(uri) {
		var req;
		uri = uri || 'db/' + this.options.database + '/' + this.options.table
			  + (this.options.entryId ? '/'+this.options.entryId : '') + '.dat';
		req=this.app.createRestRequest({
			'path': uri,
			'method': (this.options.entryId ? 'put' : 'post')
		});
		req.setHeader('Content-Type', 'text/varstream');
		if(this.dbLocale.field_file) {
			req.addEvent('done',this.sendFiles.bind(this));
		} else {
			req.addEvent('done',this.done.bind(this));
		}
		var cnt='#text/varstream'+"\n";
		// Fields
		this.db.table.fields.forEach(function(field) {
			if(field.name!='id') {
				if(field.multiple && this.options.output.entry[field.name]) {
					this.options.output.entry[field.name].forEach(function(value) {
						if(value || parseInt(value) === 0) {
							cnt += 'entry.' + field.name + '.+.value=' + value + "\n";
						}
					});
				} else if(field.name!='password'
					||this.options.output.entry[field.name]) {
					cnt += 'entry.' + field.name + '='
						+ (this.options.output.entry[field.name]+'')
							.replace(/(\r?\n)/g,'\\\n')+"\n";
				}
			}
		}.bind(this));
		// Joined fields
		this.db.table.fields.forEach(function(origField) {
			if(origField.joins&&origField.joins.length) {
				origField.joins.forEach(function(join) {
					if(this.options.output.entry[join.name]) {
  					this.options.output.entry[join.name].forEach(function(value) {
							if(value || parseInt(value) === 0) {
								cnt += 'entry.' + join.name + '.+.id=' + value + "\n";
							}
						});
					}
				}.bind(this));
			}
		}.bind(this));
		req.send(cnt);
	},
  getEntryId: function(req) {
    return req.getHeader('Location')
      .substring(req.getHeader('Location').lastIndexOf("/")+1)
      .split(".",1)[0];
  },
	sendFiles: function(req) {
		if(!this.options.entryId) {
			this.options.entryId = this.getEntryId(req);
		}
		var i=0;
		while(this.dbLocale['field_file'+(i?i:'')]) {
			if(this.options.output.entry['file'+i]
				&&this.options.output.entry['file'+i].length
				&&this.options.output.entry['file'+i][0]) {
				var req=this.app.createRestRequest({
					'path':'fs/db/'+this.options.database+'/'+this.options.table+'/'
						+this.options.entryId+'/files/'+i+'-'
						+this.options.output.entry['file'+i][0].name+'?force=yes',
					'method':'put'
				});
				req.setHeader('Content-Type','text/base64url');
				req.options.data=this.options.output.entry['file'+i][0].content;
				this.addReq(req);
			}
			i++;
		}
		if(this.reqs.length) {
			this.view.innerHTML='<div class="box><p>'
				+this.locales['FormWindow'].files_uploading+' ('+this.reqs.length+').'
				+'</p></div>';
		}
		this.sendReqs(this.done.bind(this));
	},
	done: function(req) {
		this.close();
		if(!this.options.entryId) {
			this.options.entryId = this.getEntryId(req);
		}
		this.options.output.entryId = this.options.entryId;
		this.fireEvent('done', [this.options.output]);
	}
});
