var UsersFormWindow=new Class({
	Extends: DbEntryFormWindow,
	initialize: function(app,options) {
		this.classNames.push('UsersFormWindow');
		this.classNames.push('DbUsersTable');
		this.classNames.push('DbPlacesTable');
		this.classNames.push('DbContactsTable');
		// Default options
		if(!options) {
			options={};
		}
		options.database=app.database;
		options.table='users';
		options.light=true;
		// Initializing window
		this.parent(app,options);
	},
	// Content
	loadEntryContent: function() {
		this.addReq(this.app.getLoadDatasReq('/db/'+this.app.database+'/users/'
			+this.options.entryId+'.dat?field=*&field=idJoinsContactsId.*'
				+'&field=idJoinsPlacesId.*',this.db));
		},
	// Form creation
	prepareForm: function() {
		if(this.db.entry) {
			var entry=this.db.entry;
			if(entry.idJoinsPlacesId) {
				this.options.output.place={};
				this.place_id=entry.idJoinsPlacesId[0].id;
				this.options.output.place.address=entry.idJoinsPlacesId[0].address;
				this.options.output.place.address2=entry.idJoinsPlacesId[0].address2;
				this.options.output.place.postalCode=entry.idJoinsPlacesId[0].postalCode;
				this.options.output.place.city=entry.idJoinsPlacesId[0].city;
				this.options.output.place.lng=entry.idJoinsPlacesId[0].lat;
				this.options.output.place.lat=entry.idJoinsPlacesId[0].lng;
			}
			if(entry.idJoinsContactsId) {
				this.options.output.contact={};
				for(var i=0, j=entry.idJoinsContactsId.length; i<j; i++) {
					switch(entry.idJoinsContactsId[i].type) {
						case '1':
								this.phone_id=entry.idJoinsContactsId[i].id;
								this.options.output.contact.phone=entry.idJoinsContactsId[i].value;
							break;
						case '2':
							this.mail_id=entry.idJoinsContactsId[i].id;
							this.options.output.contact.mail=entry.idJoinsContactsId[i].value;
							break;
						case '5':
							this.web_id=entry.idJoinsContactsId[i].id;
							this.options.output.contact.web=entry.idJoinsContactsId[i].value;
							break;
						case '6':
								this.gsm_id=entry.idJoinsContactsId[i].id;
								this.options.output.contact.gsm=entry.idJoinsContactsId[i].value;
							break;
					}
				}
			}
		}
		// Prepare main fieldset
		this.parent();
		// Adding suplementar fieldsets
		this.options.fieldsets.push({
			'name':'place',
			'label':this.locales['UsersFormWindow'].fieldset_place,
			'fields':[{
				'name':'address',
				'label':this.locales['DbPlacesTable'].field_address,
				'input':'input',
				'type':'text',
				'defaultValue':(this.options.output.place
					&&this.options.output.place.address?
					this.options.output.place.address:'')
			},{
				'name':'address2',
				'label':this.locales['DbPlacesTable'].field_address2,
				'input':'input',
				'type':'text',
				'defaultValue':(this.options.output.place
					&&this.options.output.place.address2?
					this.options.output.place.address2:'')
			},{
				'name':'postalCode',
				'label':this.locales['DbPlacesTable'].field_postalCode,
				'input':'input',
				'type':'text',
				'defaultValue':(this.options.output.place
					&&this.options.output.place.postalCode?
					this.options.output.place.postalCode:'')
			},{
				'name':'city',
				'label':this.locales['DbPlacesTable'].field_city,
				'input':'input',
				'type':'text',
				'defaultValue':(this.options.output.place
					&&this.options.output.place.city?
					this.options.output.place.city:'')
			},{
				'name':'lat',
				'label':this.locales['DbPlacesTable'].field_lat,
				'input':'input',
				'type':'text',
				'defaultValue':(this.options.output.place
					&&this.options.output.place.lat?
					this.options.output.place.lat:'')
			},{
				'name':'lng',
				'label':this.locales['DbPlacesTable'].field_lng,
				'input':'input',
				'type':'text',
				'defaultValue':(this.options.output.place
					&&this.options.output.place.lng?
					this.options.output.place.lng:'')
			}]
		},{
			'name':'contact',
			'label':this.locales['UsersFormWindow'].fieldset_contact,
			'fields':[{
				'name':'gsm',
				'label':this.locales['UsersFormWindow'].field_gsm,
				'input':'completer',
				'completeField':'value',
				'completeUri':'/db/'+this.app.database+'/contacts/list.dat'
					+'?field=value&fieldsearch=value&fieldsearchval=$&fieldsearchop=slike',
				'defaultValue':(this.options.output.contact
					&&this.options.output.contact.gsm?
					this.options.output.contact.gsm:'')
			},{
				'name':'phone',
				'label':this.locales['UsersFormWindow'].field_phone,
				'input':'completer',
				'completeField':'value',
				'completeUri':'/db/'+this.app.database+'/contacts/list.dat'
					+'?field=value&fieldsearch=value&fieldsearchval=$&fieldsearchop=slike',
				'defaultValue':(this.options.output.contact
					&&this.options.output.contact.phone?
					this.options.output.contact.phone:'')
			},{
				'name':'mail',
				'label':this.locales['UsersFormWindow'].field_mail,
				'input':'completer',
				'completeField':'value',
				'completeUri':'/db/'+this.app.database+'/contacts/list.dat'
					+'?field=value&fieldsearch=value&fieldsearchval=$&fieldsearchop=slike',
				'defaultValue':(this.options.output.contact
					&&this.options.output.contact.mail?
					this.options.output.contact.mail:'')
			},{
				'name':'web',
				'label':this.locales['UsersFormWindow'].field_web,
				'input':'completer',
				'completeField':'value',
				'completeUri':'/db/'+this.app.database+'/contacts/list.dat'
					+'?field=value&fieldsearch=value&fieldsearchval=$&fieldsearchop=slike',
				'defaultValue':(this.options.output.contact
					&&this.options.output.contact.web?
					this.options.output.contact.web:''),
				'placeholder':'www.elitwork.com'
			}]
		});
	},
	// Form validation
	submit: function(event) {
		if(this.parseOutput()) {
			this.sendLinkedEntries();
		}
	},
	// Form validation
	saveEntryId: function(req) {
		this[req.entryName+'_id']=req.getHeader('Location')
			.substring(req.getHeader('Location').lastIndexOf("/")+1)
			.split(".",1)[0];
	},
	sendLinkedEntries: function() {
		if(this.options.output.contact.phone) {
			if(this.options.links['contactphone']) {
				if(this.phone_id) {
					// Delete contact or link if more than 1 links to this contact
				}
				this.phone_id=this.options.links['contactphone'];
			}
			if(this.phone_id) {
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts/'+this.phone_id+'.dat',
					'method':'put'
				});
			} else {
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts.dat',
					'method':'post'
				});
				req.addEvent('done',this.saveEntryId.bind(this));
				req.entryName='phone';
			}
			req.setHeader('Content-Type','text/varstream');
			req.options.data='#text/varstream'+"\n"
				+'entry.value='+this.options.output.contact.phone+"\n"
				+'entry.type=1';
			this.addReq(req);
		}
		if(this.options.output.contact.gsm) {
			if(this.options.links['contactgsm']) {
				if(this.gsm_id) {
					// Delete contact or link if more than 1 links to this contact
				}
				this.gsm_id=this.options.links['contactgsm'];
			}
			if(this.gsm_id) {
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts/'+this.gsm_id+'.dat',
					'method':'put'
				});
			} else {
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts.dat',
					'method':'post'
				});
				req.addEvent('done',this.saveEntryId.bind(this));
				req.entryName='gsm';
			}
			req.setHeader('Content-Type','text/varstream');
			req.options.data='#text/varstream'+"\n"
				+'entry.value='+this.options.output.contact.gsm+"\n"
				+'entry.type=6';
			this.addReq(req);
		}
		if(this.options.output.contact.mail) {
			if(this.options.links['contactmail']) {
				if(this.mail_id) {
					// Delete contact or link if more than 1 links to this contact
				}
				this.mail_id=this.options.links['contactmail'];
			}
			if(this.mail_id) {
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts/'+this.mail_id+'.dat',
					'method':'put'
				});
			} else {
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts.dat',
					'method':'post'
				});
				req.addEvent('done',this.saveEntryId.bind(this));
				req.entryName='mail';
			}
			req.setHeader('Content-Type','text/varstream');
			req.options.data='#text/varstream'+"\n"
				+'entry.value='+this.options.output.contact.mail+"\n"
				+'entry.type=2';
			this.addReq(req);
		}
		if(this.options.output.contact.web) {
			if(this.options.links['contactweb']) {
				if(this.web_id) {
					// Delete contact or link if more than 1 links to this contact
				}
				this.web_id=this.options.links['contactweb'];
			}
			if(this.web_id) {
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts/'+this.web_id+'.dat',
					'method':'put'
				});
			} else {
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/contacts.dat',
					'method':'post'
				});
				req.addEvent('done',this.saveEntryId.bind(this));
				req.entryName='web';
			}
			req.setHeader('Content-Type','text/varstream');
			req.options.data='#text/varstream'+"\n"
				+'entry.value='+this.options.output.contact.web.replace(/http(s?):\/\//i,' ')+"\n"
				+'entry.type=5';
			this.addReq(req);
		}
		if(this.options.output.place.lat||this.options.output.place.city) {
			if(this.place_id) {
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/places/'+this.place_id+'.dat',
					'method':'put'
				});
			} else {
				var req=this.app.createRestRequest({
					'path':'db/'+this.app.database+'/places.dat',
					'method':'post'
				});
				req.addEvent('done',this.saveEntryId.bind(this));
			}
			req.setHeader('Content-Type','text/varstream');
			req.options.data='#text/varstream'+"\n"
				+'entry.label='+this.locales['UsersFormWindow'].fieldset_place+' '
					+this.options.output.entry.firstname
					+this.options.output.entry.lastname+"\n"
				+(this.options.output.place.address?'entry.address='
					+this.options.output.place.address+"\n":'')
				+(this.options.output.place.address2?'entry.address2='
					+this.options.output.place.address2+"\n":'')
				+(this.options.output.place.postalCode?'entry.postalCode='
					+this.options.output.place.postalCode+"\n":'')
				+(this.options.output.place.city?'entry.city='
					+this.options.output.place.city+"\n":'')
				+(this.options.output.place.lat?'entry.lat='
					+this.options.output.place.lng+"\n":'')
				+(this.options.output.place.lng?'entry.lng='
					+this.options.output.place.lat+"\n":'');
			req.entryName='place';
			this.addReq(req);
		}
		this.sendReqs(this.sendEntry.bind(this));
	},
	sendEntry: function() {
		if(this.place_id) {
			this.options.output.entry['idJoinsPlacesId']=[];
			this.options.output.entry['idJoinsPlacesId'].push(this.place_id);
		}
		if(this.phone_id||this.gsm_id||this.mail_id||this.web_id) {
			this.options.output.entry['idJoinsContactsId']=[];
			if(this.phone_id)
				this.options.output.entry['idJoinsContactsId'].push(this.phone_id);
			if(this.gsm_id)
				this.options.output.entry['idJoinsContactsId'].push(this.gsm_id);
			if(this.mail_id)
				this.options.output.entry['idJoinsContactsId'].push(this.mail_id);
			if(this.web_id)
				this.options.output.entry['idJoinsContactsId'].push(this.web_id);
		}
		this.parent();
	}
});
