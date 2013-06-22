var OrganizationsFormWindow=new Class({
	Extends: DbEntryFormWindow,
	initialize: function(app,options) {
		this.classNames.push('OrganizationsFormWindow');
		this.classNames.push('DbOrganizationsTable');
		this.classNames.push('DbPlacesTable');
		this.classNames.push('DbContactsTable');
		// Default options
		if(!options)
			options={};
		options.database=app.database;
		options.table='organizations';
		options.light=true;
		// Initializing window
		this.parent(app,options);
	},
	// Content
	loadEntryContent: function() {
		this.addReq(this.app.getLoadDatasReq('/db/'+this.app.database+'/organizations/'+this.options.entryId+'.dat?mode=fulljoin',this.db));
	},
	// Form creation
	prepareForm : function() {
		if(this.db.entry)
			{
			var entry=this.db.entry;
			this.joined_types=new Array();
			if(entry.joined_organizationTypes)
				{
				for(var i=entry.joined_organizationTypes.length-1; i>=0; i--)
					{
					this.joined_types.push(entry.joined_organizationTypes[i].id);
					}
				}
			if(entry.joined_places)
				{
				this.options.output.place={};
				this.place_id=entry.joined_places[0].id;
				this.options.output.place.address=entry.joined_places[0].address;
				this.options.output.place.address2=entry.joined_places[0].address2;
				this.options.output.place.postalCode=entry.joined_places[0].postalCode;
				this.options.output.place.city=entry.joined_places[0].city;
				this.options.output.place.lng=entry.joined_places[0].lat;
				this.options.output.place.lat=entry.joined_places[0].lng;
				}
			if(entry.joined_contacts)
				{
				this.options.output.contact={};
				for(var i=0, j=entry.joined_contacts.length; i<j; i++)
					{
					switch(entry.joined_contacts[i].type)
						{
						case '1':
							this.phone_id=entry.joined_contacts[i].id;
							this.options.output.contact.phone=entry.joined_contacts[i].value;
							break;
						case '2':
							this.mail_id=entry.joined_contacts[i].id;
							this.options.output.contact.mail=entry.joined_contacts[i].value;
							break;
						case '3':
							this.fax_id=entry.joined_contacts[i].id;
							this.options.output.contact.fax=entry.joined_contacts[i].value;
							break;
						case '5':
							this.web_id=entry.joined_contacts[i].id;
							this.options.output.contact.web=entry.joined_contacts[i].value;
							break;
						}
					}
				}
			}
		// Prepare main fieldset
		this.parent();
		// Adding the type field
		this.options.fieldsets[0].fields.push({'name':'organizationTypes','label':this.locales['OrganizationsFormWindow'].field_organizationTypes,
			'input':'picker','window':'DbEntriesWindow','options':{
				'database':this.app.database,'table':'organizationTypes',
				'multiple':true, 'prompt':true
			},'defaultValue':(entry&&entry.joined_organizationTypes?entry.joined_organizationTypes.map(function(entry){
				return entry.id;
			}):'')});
		console.log(this.options.fieldsets[0].fields,entry);
		// Adding suplementar fieldsets
		this.options.fieldsets.push(
				{'name':'place','label':this.locales['DbPlacesTable'].table_title,'fields':[
						{'name':'address','label':this.locales['DbPlacesTable'].field_address,'input':'input','type':'text','defaultValue':(this.options.output.place&&this.options.output.place.address?this.options.output.place.address:'')},
						{'name':'address2','label':this.locales['DbPlacesTable'].field_address2,'input':'input','type':'text','defaultValue':(this.options.output.place&&this.options.output.place.address2?this.options.output.place.address2:'')},
						{'name':'postalCode','label':this.locales['DbPlacesTable'].field_postalCode,'input':'input','type':'text','defaultValue':(this.options.output.place&&this.options.output.place.postalCode?this.options.output.place.postalCode:'')},
						{'name':'city','label':this.locales['DbPlacesTable'].field_city,'input':'input','type':'text','defaultValue':(this.options.output.place&&this.options.output.place.city?this.options.output.place.city:'')},
						{'name':'lat','label':this.locales['DbPlacesTable'].field_lat,'input':'input','type':'text','defaultValue':(this.options.output.place&&this.options.output.place.lat?this.options.output.place.lat:'')},
						{'name':'lng','label':this.locales['DbPlacesTable'].field_lng,'input':'input','type':'text','defaultValue':(this.options.output.place&&this.options.output.place.lng?this.options.output.place.lng:'')}
					]
				},
				{'name':'contact','label':this.locales['DbContactsTable'].table_title,'fields':[
						{'name':'phone','label':this.locales['OrganizationsFormWindow'].field_phone,'input':'input','type':'text','defaultValue':(this.options.output.contact&&this.options.output.contact.phone?this.options.output.contact.phone:'')},
						{'name':'fax','label':this.locales['OrganizationsFormWindow'].field_fax,'input':'input','type':'text','defaultValue':(this.options.output.contact&&this.options.output.contact.fax?this.options.output.contact.fax:'')},
						{'name':'mail','label':this.locales['OrganizationsFormWindow'].field_mail,'input':'input','type':'text','defaultValue':(this.options.output.contact&&this.options.output.contact.mail?this.options.output.contact.mail:'')},
						{'name':'web','label':this.locales['OrganizationsFormWindow'].field_web,'input':'input','type':'text','defaultValue':(this.options.output.contact&&this.options.output.contact.web?this.options.output.contact.web:'')}
					]
				}
			);
		},
	// Form validation
	submit: function(event)
		{
		if(this.parseOutput())
			{
			this.sendLinkedEntries();
			}
		},
	// Form validation
	saveEntryId: function(req)
		{
		this[req.entryName+'_id']=req.getHeader('Location').substring(req.getHeader('Location').lastIndexOf("/")+1).split(".",1)[0];
		},
	sendLinkedEntries: function()
		{
		if(this.options.output.contact.phone)
			{
			if(this.phone_id)
				{
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts/'+this.phone_id+'.dat',
					'method':'put'});
				}
			else
				{
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts.dat',
					'method':'post'});
				req.addEvent('done',this.saveEntryId.bind(this));
				req.entryName='phone';
				}
			req.options.data='#text/varstream'+"\n"
				+'entry.value='+this.options.output.contact.phone+"\n"
				+'entry.type=1';
			this.addReq(req);
			}
		if(this.options.output.contact.fax)
			{
			if(this.fax_id)
				{
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts/'+this.fax_id+'.dat',
					'method':'put'});
				}
			else
				{
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts.dat',
					'method':'post'});
				req.addEvent('done',this.saveEntryId.bind(this));
				req.entryName='fax';
				}
			req.options.data='#text/varstream'+"\n"
				+'entry.value='+this.options.output.contact.fax+"\n"
				+'entry.type=3';
			this.addReq(req);
			}
		if(this.options.output.contact.mail)
			{
			if(this.mail_id)
				{
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts/'+this.mail_id+'.dat',
					'method':'put'});
				}
			else
				{
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts.dat',
					'method':'post'});
				req.addEvent('done',this.saveEntryId.bind(this));
				req.entryName='mail';
				}
			req.options.data='#text/varstream'+"\n"
				+'entry.value='+this.options.output.contact.mail+"\n"
				+'entry.type=2';
			this.addReq(req);
			}
		if(this.options.output.contact.web)
			{
			if(this.web_id)
				{
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts/'+this.web_id+'.dat',
					'method':'put'});
				}
			else
				{
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts.dat',
					'method':'post'});
				req.addEvent('done',this.saveEntryId.bind(this));
				req.entryName='web';
				}
			req.options.data='#text/varstream'+"\n"
				+'entry.value='+this.options.output.contact.web.replace(/http(s?):\/\//i,' ')+"\n"
				+'entry.type=5';
			this.addReq(req);
			}
		if(this.options.output.place.lat||this.options.output.place.city)
			{
			if(this.place_id)
				{
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/places/'+this.place_id+'.dat',
					'method':'put'});
				}
			else
				{
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/places.dat',
					'method':'post'});
				req.addEvent('done',this.saveEntryId.bind(this));
				}
			req.options.data='#text/varstream'+"\n"
				+'entry.label=Siege '+this.options.output.entry.label+"\n"
				+(this.options.output.place.address?'entry.address='+this.options.output.place.address+"\n":'')
				+(this.options.output.place.address2?'entry.address2='+this.options.output.place.address2+"\n":'')
				+(this.options.output.place.postalCode?'entry.postalCode='+this.options.output.place.postalCode+"\n":'')
				+(this.options.output.place.city?'entry.city='+this.options.output.place.city+"\n":'')
				+(this.options.output.place.city?'entry.lat='+this.options.output.place.lng+"\n":'')
				+(this.options.output.place.city?'entry.lng='+this.options.output.place.lat+"\n":'');
			req.entryName='place';
			this.addReq(req);
			}
		// remove organizationTypes
		if(this.db.entry&&this.db.entry.joined_organizationTypes) {
			this.db.entry.joined_organizationTypes.forEach(function(type){
				if(!(this.options.output.entry.organizationTypes
					&&this.options.output.entry.organizationTypes.split(',').some(function(type2){
					console.log(type2,type.id);
						return (type2==type.id?true:false);
					}))){
					var req=this.app.createRestRequest({
						'path':'db/'+this.app.database+'/organizationTypes_organizations/'+type.join_id+'.dat',
						'method':'delete'});
					this.addReq(req);
				}
			}.bind(this));
		}
		// sending all requests
		this.sendReqs(this.sendEntry.bind(this));
		},
	sendEntry: function()
		{
		if(this.place_id)
			{
			this.options.output.entry['joined_places']=[];
			this.options.output.entry['joined_places'].push(this.place_id);
			}
		if(this.phone_id||this.fax_id||this.mail_id||this.web_id)
			{
			this.options.output.entry['joined_contacts']=[];
			if(this.phone_id)
				this.options.output.entry['joined_contacts'].push(this.phone_id);
			if(this.fax_id)
				this.options.output.entry['joined_contacts'].push(this.fax_id);
			if(this.mail_id)
				this.options.output.entry['joined_contacts'].push(this.mail_id);
			if(this.web_id)
				this.options.output.entry['joined_contacts'].push(this.web_id);
			}
		this.parent();
		},
	done: function(req) {
		if(!this.options.entryId)
			this.options.entryId=req.getHeader('Location').substring(req.getHeader('Location').lastIndexOf("/")+1).split(".",1)[0];
		// add organizationTypes
		if(this.options.output.entry.organizationTypes) {
			this.options.output.entry.organizationTypes.split(',').each(function(type){
				if(!(this.db.entry&&this.db.entry.joined_organizationTypes
						&&this.db.entry.joined_organizationTypes.some(function(type2){
					console.log(type,type2.id);
					return (type==type2.id?true:false);
				}))) {
					var req=this.app.createRestRequest({
						'path':'db/'+this.app.database+'/organizationTypes_organizations.dat',
						'method':'post'});
					req.options.data='#text/varstream'+"\n"
						+'entry.organizations_id='+this.options.entryId+"\n"
						+'entry.organizationTypes_id='+type+"\n";
					this.addReq(req);
				}
			}.bind(this));
		this.sendReqs(DbEntryFormWindow.prototype.done.bind(this));
		} else {
			this.parent();
		}
	}
});
