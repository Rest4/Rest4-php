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
		var req=this.app.getLoadLocaleReq('Db'+this.options.table.substring(0,1).toUpperCase()+this.options.table.substring(1)+'Table',null,false,true);
		if(req)
			{
			req.canFail=true;
			this.addReq(req);
			}
		// Getting table schema
		this.addReq(this.app.getLoadDatasReq('/db/'+this.options.database+'/'+this.options.table+'.dat',this.db={}));
		this.parent();
		},
	loaded: function()
		{
		// Choosing the right locale
		if(this.app.locales['Db'+this.options.table.substring(0,1).toUpperCase()+this.options.table.substring(1)+'Table'])
			this.dbLocale=this.app.locales['Db'+this.options.table.substring(0,1).toUpperCase()+this.options.table.substring(1)+'Table'];
		else
			this.dbLocale=this.app.locales['DbWindow'];
		// Setting window name
		this.options.name=this.dbLocale[''+(this.options.entryId?'modify':'add')+'_title'];
		this.parent();
		},
	// Content
	loadContent: function()	{
		this.db.linkedTablesEntries=[];
		// Loading linked tables values
		for(var i=0, j=0, m=this.db.table.fields.length; j<m; j++)
			{
			if(this.db.table.fields[j].name!='id')
				{
				if(this.db.table.fields[j].input=='select')
					{
					if(!this.db.table.fields[j].options)
						{
						if(!this.db.linkedTablesEntries[this.db.table.fields[j]])
							{
							this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable]={};
							this.addReq(this.app.getLoadDatasReq('/db/'+this.options.database+'/'+this.db.table.fields[j].linkedTable+'/list.dat?mode=light&limit=21',this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable]));
							}
						}
					}
				}
			}
		// Loading current values
		if(this.options.entryId)
			this.loadEntryContent();
		this.parent();
		},
	loadEntryContent: function() {
		this.addReq(this.app.getLoadDatasReq('/db/'+this.options.database+'/'+this.options.table+'/'+this.options.entryId+'.dat?mode=extend',this.db));
		},
	renderContent: function() {
		this.prepareForm();
		this.parent();
		},
	prepareForm: function() {
		var field;
		this.options.fieldsets=[{'name':'entry', 'label':this.dbLocale['fieldset'],'fields':[]}];
		for(var j=0, m=this.db.table.fields.length; j<m; j++)
			{
			if(this.db.table.fields[j].name!='id')
				{
				field={};
				field.name=this.db.table.fields[j].name;
				field.label=(this.dbLocale['field_'+this.db.table.fields[j].name]?
					this.dbLocale['field_'+this.db.table.fields[j].name]:this.db.table.fields[j].name);
				field.required=(this.db.table.fields[j].name!='password'||(!this.options.entryId)?
					this.db.table.fields[j].required:false);
				field.title=(this.dbLocale['field_'+this.db.table.fields[j].name+'_title']?
					this.dbLocale['field_'+this.db.table.fields[j].name]:'');
				field.placeholder=(this.dbLocale['field_'+this.db.table.fields[j].name+'_placeholder']?
					this.dbLocale['field_'+this.db.table.fields[j].name+'_placeholder']:'');
				field.pattern=(this.db.table.fields[j].pattern?this.db.table.fields[j].pattern:'');
				field.multiple=(this.db.table.fields[j].multiple?true:false);
				if(this.db.table.fields[j].input=='select')
					{
					if(this.db.table.fields[j].options)
						{
						field.input='select';
						field.options=[];
						for(var k=0, l=this.db.table.fields[j].options.length; k<l; k++)
							{
							field.options[k]={};
							field.options[k].value=this.db.table.fields[j].options[k].value;
							field.options[k].name=(this.dbLocale['field_'+this.db.table.fields[j].name+'_options_'+this.db.table.fields[j].options[k].value]?this.dbLocale['field_'+this.db.table.fields[j].name+'_options_'+this.db.table.fields[j].options[k].value]:this.db.table.fields[j].options[k].value);
							if(this.options.entryId&&this.db.entry[this.db.table.fields[j].name]&&this.db.entry[this.db.table.fields[j].name].indexOf(this.db.table.fields[j].options[k].value)>-1)
								{
								field.options[k].selected=true;
								}
							else if(this.db.table.fields[j].defaultValue!==undefined&&this.db.table.fields[j].defaultValue==this.db.table.fields[j].options[k].value)
								field.options[k].selected=true;
							}
						}
					else if(!this.db.table.fields[j].referedField)
						{
						if((this.options.light||this.options.entryId)&&field.multiple)
							{ continue; }
						if(this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable].entries&&this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable].entries.length<18)
							{
							field.input='select';
							field.options=[];
							if(this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable].entries&&this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable].entries.length)
							for(var k=0, l=this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable].entries.length; k<l; k++)
								{
								field.options[k]={};
								field.options[k].value=this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable].entries[k].id;
								if(this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable].entries[k].label)
									field.options[k].name=this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable].entries[k].label;
								else
									field.options[k].name=this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable].entries[k].name;
								if(this.options.entryId
									&&((field.multiple&&this.db.entry[this.db.table.fields[j].name]&&this.db.entry[this.db.table.fields[j].name].indexOf(this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable].entries[k].id)>-1)
										||((!field.multiple)&&this.db.entry[this.db.table.fields[j].name]&&this.db.entry[this.db.table.fields[j].name]==this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable].entries[k].id)))
									{
									field.options[k].selected=true;
									}
								else if(this.db.table.fields[j].defaultValue!==undefined&&this.db.table.fields[j].defaultValue==this.db.linkedTablesEntries[this.db.table.fields[j].linkedTable].entries[k].id)
									field.options[k].selected=true;
								}
							}
						else
							{
							field.input='picker';
							field.window='DbEntriesWindow';
							field.options={'database':this.options.database,'table':this.db.table.fields[j].linkedTable,'prompt':true};
							if(field.multiple)
								{
								field.multiple=true;
								if(this.options.entryId&&this.db.entry[this.db.table.fields[j].name])
									field.defaultValue=this.db.entry[this.db.table.fields[j].name];
								else if(this.options.output&&this.options.output[this.db.table.fields[j].name])
									field.defaultValue=(this.options.output[this.db.table.fields[j].name] instanceof Array?
										this.options.output[this.db.table.fields[j].name]:new Array(this.options.output[this.db.table.fields[j].name]));
								else if(this.db.table.fields[j].defaultValue!==undefined)
									field.defaultValue=new Array(this.db.table.fields[j].defaultValue);
								}
							else
								{
								if(this.options.entryId&&this.db.entry[this.db.table.fields[j].name])
									field.defaultValue=new Array(this.db.entry[this.db.table.fields[j].name]);
								else if(this.options.output&&this.options.output[this.db.table.fields[j].name])
									field.defaultValue=new Array(this.options.output[this.db.table.fields[j].name]);
								else if(this.db.table.fields[j].defaultValue!==undefined)
									field.defaultValue=new Array(this.db.table.fields[j].defaultValue);
								}
							}
						}
					else
						continue;
					}
				else
					{
					field.input=this.db.table.fields[j].input;
					if(this.options.entryId&&this.db.entry[this.db.table.fields[j].name])
						field.defaultValue=this.db.entry[this.db.table.fields[j].name];
					else if(this.options.output&&this.options.output[this.db.table.fields[j].name])
						field.defaultValue=this.options.output[this.db.table.fields[j].name];
					else if(this.db.table.fields[j].defaultValue!==undefined)
						field.defaultValue=this.db.table.fields[j].defaultValue;
					if(['email','tel','date','time','text','number'].indexOf(this.db.table.fields[j].type)!==-1)
						field.type=this.db.table.fields[j].type;
					else if(this.db.table.fields[j].type=='datetime')
						field.type='datetime-local';
					// Setting min/max attributes
					if(undefined!==this.db.table.fields[j].max&&
						['number','date','datetime','time','text'].indexOf(this.db.table.fields[j].type)!==-1)
						{
						field.max=this.db.table.fields[j].max;
						}
					if(undefined!==this.db.table.fields[j].min&&
						['number','date','datetime','time'].indexOf(this.db.table.fields[j].type)!==-1)
						{
						field.min=this.db.table.fields[j].min;
						}
					// Setting float numbers pace
					if(this.db.table.fields[j].type=='number')
						{
						if(this.db.table.fields[j].filter=='float')
							{
							if(this.db.table.fields[j].decimals)
								{
								field.step='0.';
								for(var k=this.db.table.fields[j].decimals; k>1; k--)
									field.step+='0';
								field.step+='1';
								}
							else
								field.step='0.0000001';
							}
						else
							field.step='1';
						}
					}
				this.options.fieldsets[0].fields.push(field);
				}
			}
		var i=0;
		while(this.dbLocale['field_file'+(i?i:'')])
			{
			this.options.fieldsets[0].fields.push({'name':'file'+i,'label':this.dbLocale['field_file'+(i?i:'')],'input':'picker','type':'file',
				'defaultValue':(this.db.entry&&this.db.entry.attached_files&&this.db.entry.attached_files[i]?this.db.entry.attached_files[i].name:''),
				'defaultUri':(this.db.entry&&this.db.entry.attached_files&&this.db.entry.attached_files[i]?'/fs/db/'+this.options.database+'/'+this.options.table+'/'+this.options.entryId+'/files/'+this.db.entry.attached_files[i].name:''),
				'options':{'filter':(this.dbLocale['field_file'+(i?i:'')+'_mime']?this.dbLocale['field_file'+(i?i:'')+'_mime']:'')}});
			i++;
			}
		},
	submit: function(event)
		{
		if(this.parseOutput())
			{
			this.sendEntry();
			}
		},
	sendEntry: function(req)
		{
		var req=this.app.createRestRequest({
			'path':'db/'+this.options.database+'/'+this.options.table+(this.options.entryId?'/'+this.options.entryId:'')+'.dat',
			'method':(this.options.entryId?'put':'post')});
		req.setHeader('Content-Type','text/varstream');
		if(this.dbLocale.field_file)
			req.addEvent('done',this.sendFiles.bind(this));
		else
			req.addEvent('done',this.done.bind(this));
		var cnt='#text/varstream'+"\n";
		for(var i=0, j=this.db.table.fields.length; i<j; i++)
			{
			if(this.db.table.fields[i].name!='id')//&&(type=='add'||this.db.table.fields[i].name!='password'))
				{
				if(this.db.table.fields[i].multiple)
					{
					if(this.options.output.entry[this.db.table.fields[i].name])
						{
						for(var k=0, l=this.options.output.entry[this.db.table.fields[i].name].length; k<l; k++)
							{
							if(this.options.output.entry[this.db.table.fields[i].name][k]||parseInt(this.options.output.entry[this.db.table.fields[i].name][k])===0)
								cnt+='entry.'+this.db.table.fields[i].name+'.+.value='+this.options.output.entry[this.db.table.fields[i].name][k]+"\n";
							}
						}
					}
				else if(this.db.table.fields[i].name!='password'||this.options.output.entry[this.db.table.fields[i].name])
					cnt+='entry.'+this.db.table.fields[i].name+'='+(this.options.output.entry[this.db.table.fields[i].name]+'').replace(/(\r?\n)/g,'\\\n')+"\n";
				}
			}
		req.send(cnt);
		},
	sendFiles: function(req)
		{
		if(!this.options.entryId)
			this.options.entryId=req.getHeader('Location').substring(req.getHeader('Location').lastIndexOf("/")+1).split(".",1)[0];
		var i=0;
		while(this.dbLocale['field_file'+(i?i:'')])
			{
			if(this.options.output.entry['file'+i]&&this.options.output.entry['file'+i].length&&this.options.output.entry['file'+i][0])
				{
				var req=this.app.createRestRequest({
					'path':'fs/db/'+this.options.database+'/'+this.options.table+'/'+this.options.entryId+'/files/'+i+'-'+this.options.output.entry['file'+i][0].name+'?force=yes',
					'method':'put'});
				req.setHeader('Content-Type','text/base64url');
				req.options.data=this.options.output.entry['file'+i][0].content;
				this.addReq(req);
				}
			i++;
			}
		if(this.reqs.length)
			this.view.innerHTML='<div class="box><p>'+this.locales['FormWindow'].files_uploading+' ('+this.reqs.length+').</p></div>';
		this.sendReqs(this.done.bind(this));
		},
	done: function(event)
		{
		this.close();
		this.options.output.entryId=this.options.entryId;
		this.fireEvent('done', [event, this.options.output]);
		}
});
