var DbEntryFormWindow=new Class({
	Extends: FormWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.database='';
		this.options.table='';
		this.options.entryId='';
		this.options.light=false;
		this.classNames.push('DbEntryFormWindow');
		this.classNames.push('DbWindow');
		// Required options
		this.requiredOptions.push('database','table');
		// Initializing window
		this.parent(desktop,options);
		// Setting vars
		this.db.linkedEntries=[];
		this.db.linkedTablesEntries=[];
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
		this.db.linkedTablesEntries=[];
		// Loading linked tables values
		this.db.table.fields.forEach(function(field) {
			if(field.linkTo) {
				if(!this.db.linkedTablesEntries[field.linkTo.table]) {
					this.db.linkedTablesEntries[field.linkTo.table]={};
					this.addReq(this.app.getLoadDatasReq(
						'/db/'+this.options.database+'/'+field.linkTo.table
							+'/list.dat?mode=light&limit=21',
						this.db.linkedTablesEntries[field.linkTo.table]
					));
				}
			}
			if(field.joins&&field.joins.length) {
				field.joins.forEach(function(join){
					if(!this.db.linkedTablesEntries[join.table]) {
						this.db.linkedTablesEntries[join.table]={};
						this.addReq(this.app.getLoadDatasReq(
							'/db/'+this.options.database+'/'+join.table
								+'/list.dat?mode=light&limit=21',
							this.db.linkedTablesEntries[join.table]
						));
					}
				}.bind(this));
			}
			if(field.refs&&field.references.length) {
				field.references.forEach(function(ref){
					if(!this.db.linkedTablesEntries[ref.table]) {
						this.db.linkedTablesEntries[ref.table]={};
						this.addReq(this.app.getLoadDatasReq(
							'/db/'+this.options.database+'/'+ref.table
								+'/list.dat?mode=light&limit=21',
							this.db.linkedTablesEntries[ref.table]
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
				field={};
				field.name=origField.name;
				field.label=(this.dbLocale['field_'+origField.name]?
					this.dbLocale['field_'+origField.name]:origField.name);
				field.required=(origField.name!='password'||(!this.options.entryId)?
					origField.required:false);
				field.title=(this.dbLocale['field_'+origField.name+'_title']?
					this.dbLocale['field_'+origField.name]:'');
				field.placeholder=(this.dbLocale['field_'+origField.name+'_placeholder']?
					this.dbLocale['field_'+origField.name+'_placeholder']:'');
				field.pattern=(origField.pattern?origField.pattern:'');
				field.multiple=(origField.multiple?true:false);
				if(origField.input=='select') {
					if(origField.options) {
						field.input='select';
						field.options=[];
						for(var k=0, l=origField.options.length; k<l; k++) {
							field.options[k]={};
							field.options[k].value=origField.options[k].value;
							field.options[k].name=(
								this.dbLocale['field_'+origField.name
									+'_options_'+origField.options[k].value]?
								this.dbLocale['field_'+origField.name
									+'_options_'+origField.options[k].value]:
								origField.options[k].value
							);
							if(this.options.entryId&&this.db.entry[origField.name]
								&&this.db.entry[origField.name].indexOf(origField.options[k].value)>-1) {
								field.options[k].selected=true;
							} else if(this.options.output
								&&this.options.output[origField.name]==origField.options[k].value) {
								field.options[k].selected=true;
							} else if(origField.defaultValue!==undefined
								&&origField.defaultValue==origField.options[k].value) {
								field.options[k].selected=true;
							}
						}
					} else {
						if(this.db.linkedTablesEntries[origField.linkTo.table].entries
							&&this.db.linkedTablesEntries[origField.linkTo.table].entries.length<18) {
							field.input='select';
							field.options=[];
							if(this.db.linkedTablesEntries[origField.linkTo.table].entries
								&&this.db.linkedTablesEntries[origField.linkTo.table].entries.length) {
								for(var k=0, l=this.db.linkedTablesEntries[origField.linkTo.table].entries.length; k<l; k++) {
									field.options[k]={};
									field.options[k].value=this.db.linkedTablesEntries[origField.linkTo.table].entries[k].id;
									if(this.db.linkedTablesEntries[origField.linkTo.table].entries[k].label) {
										field.options[k].name=this.db.linkedTablesEntries[origField.linkTo.table].entries[k].label;
									} else {
										field.options[k].name=this.db.linkedTablesEntries[origField.linkTo.table].entries[k].name;
									}
									if(this.options.entryId
										&&((field.multiple&&this.db.entry[origField.name]
										&&this.db.entry[origField.name].indexOf(this.db.linkedTablesEntries[origField.linkTo.table].entries[k].id)>-1)
											||((!field.multiple)&&this.db.entry[origField.name]
											&&this.db.entry[origField.name]==this.db.linkedTablesEntries[origField.linkTo.table].entries[k].id))) {
										field.options[k].selected=true;
									} else if(this.options.output&&this.options.output[origField.name]==
										this.db.linkedTablesEntries[origField.linkTo.table].entries[k].id) {
										field.options[k].selected=true;
									} else if(origField.defaultValue!==undefined
										&&origField.defaultValue==this.db.linkedTablesEntries[origField.linkTo.table].entries[k].id) {
										field.options[k].selected=true;
									}
								}
							}
						} else {
							field.input='picker';
							field.window='DbEntriesWindow';
							field.options={
								'database':this.options.database,
								'table':origField.linkTo.table,
								'prompt':true
							};
							if(field.multiple) {
								field.multiple=true;
								if(this.options.entryId&&this.db.entry[origField.name]) {
									field.defaultValue=this.db.entry[origField.name];
								} else if(this.options.output&&this.options.output[origField.name]) {
									field.defaultValue=(this.options.output[origField.name] instanceof Array?
										this.options.output[origField.name]:
										[this.options.output[origField.name]]);
								} else if(origField.defaultValue!==undefined) {
									field.defaultValue=[origField.defaultValue];
								}
							} else {
								if(this.options.entryId&&this.db.entry[origField.name]) {
									field.defaultValue=[this.db.entry[origField.name]];
								} else if(this.options.output&&this.options.output[origField.name]) {
									field.defaultValue=[this.options.output[origField.name]];
								} else if(origField.defaultValue!==undefined) {
									field.defaultValue=[origField.defaultValue];
								}
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
	sendEntry: function(req) {
		var req=this.app.createRestRequest({
			'path':'db/'+this.options.database+'/'+this.options.table+(this.options.entryId?'/'+this.options.entryId:'')+'.dat',
			'method':(this.options.entryId?'put':'post')});
		req.setHeader('Content-Type','text/varstream');
		if(this.dbLocale.field_file) {
			req.addEvent('done',this.sendFiles.bind(this));
		} else {
			req.addEvent('done',this.done.bind(this));
		}
		var cnt='#text/varstream'+"\n";
		for(var i=0, j=this.db.table.fields.length; i<j; i++) {
			if(this.db.table.fields[i].name!='id') { //&&(type=='add'||this.db.table.fields[i].name!='password'))
				if(this.db.table.fields[i].multiple) {
					if(this.options.output.entry[this.db.table.fields[i].name]) {
						for(var k=0, l=this.options.output.entry[this.db.table.fields[i].name].length; k<l; k++) {
							if(this.options.output.entry[this.db.table.fields[i].name][k]||parseInt(this.options.output.entry[this.db.table.fields[i].name][k])===0)
								cnt+='entry.'+this.db.table.fields[i].name+'.+.value='+this.options.output.entry[this.db.table.fields[i].name][k]+"\n";
						}
					}
				} else if(this.db.table.fields[i].name!='password'
					||this.options.output.entry[this.db.table.fields[i].name]) {
					cnt+='entry.'+this.db.table.fields[i].name+'='
						+(this.options.output.entry[this.db.table.fields[i].name]+'')
							.replace(/(\r?\n)/g,'\\\n')+"\n";
				}
			}
		}
		req.send(cnt);
	},
	sendFiles: function(req) {
		if(!this.options.entryId) {
			this.options.entryId=req.getHeader('Location')
				.substring(req.getHeader('Location').lastIndexOf("/")+1)
				.split(".",1)[0];
		}
		var i=0;
		while(this.dbLocale['field_file'+(i?i:'')])
			{
			if(this.options.output.entry['file'+i]
				&&this.options.output.entry['file'+i].length
				&&this.options.output.entry['file'+i][0]) {
				var req=this.app.createRestRequest({
					'path':'fs/db/'+this.options.database+'/'+this.options.table+'/'+this.options.entryId+'/files/'+i+'-'+this.options.output.entry['file'+i][0].name+'?force=yes',
					'method':'put'});
				req.setHeader('Content-Type','text/base64url');
				req.options.data=this.options.output.entry['file'+i][0].content;
				this.addReq(req);
			}
			i++;
		}
		if(this.reqs.length) {
			this.view.innerHTML='<div class="box><p>'+this.locales['FormWindow'].files_uploading+' ('+this.reqs.length+').</p></div>';
		}
		this.sendReqs(this.done.bind(this));
	},
	done: function(event) {
		this.close();
		this.options.output.entryId=this.options.entryId;
		this.fireEvent('done', [event, this.options.output]);
	}
});
