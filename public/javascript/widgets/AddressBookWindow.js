var AddressBookWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Default options
		this.classNames.push('AddressBookWindow');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-search',this.search.bind(this));
		this.app.registerCommand('win'+this.id+'-show',this.show.bind(this));
		this.app.registerCommand('win'+this.id+'-selectTab',this.selectTab.bind(this));
		if(this.app.user.groupName=='webmasters'
			||this.app.user.groupName=='administrators') {
			this.app.registerCommand('win'+this.id+'-add',this.add.bind(this));
			this.app.registerCommand('win'+this.id+'-modify',this.modify.bind(this));
		}
	},
	// Rendering window
	load : function() {
		this.addReq(this.app.getLoadDatasReq('/db/'+this.app.database
			+'/contactTypes/list.dat?field=*',this.contactTypes={}));
		this.parent();
	},
	render : function() {
		// Creating search form
		var tpl='<form action="#win'+this.id+'-search">'
			+'	<label>'+this.locale.form_search_label
			+'	 <input name="search" type="text" id="win'+this.id+'-search"'
			+' placeholder="'+this.locale.form_search_placeholder+'" /></label>'
			+'	<input type="submit" value="'+this.locale.form_search+'" />'
			+'</form>';
		this.options.forms.push({
			'tpl':tpl,
			'label':this.locale.menu_search,
			'command':'loadContent',
			'title':this.locale.menu_search_tx,
			'showAtStart':true
		});
		// Filling menu
		if(this.app.user.groupName=='webmasters'
			||this.app.user.groupName=='administrators') {
			this.options.menu.push({
				'label':this.locale.menu_add_org,
				'command':'add:organizations',
				'title':this.locale.menu_add_org_tx
			}, {
				'label':this.locale.menu_add_user,
				'command':'add:users',
				'title':this.locale.menu_add_user_tx
			});
		}
		// Drawing window
		this.parent();
	},
	// Rendering content
	renderContent : function() {
		var tpl;
		tpl='<div class="box"><p>'+this.locale.content+'</p></div>';
		this.view.innerHTML=tpl;
	},
	// Search
	search: function(event) {
		var search=$('win'+this.id+'-search').value;
		if(search) {
			this.addReq(this.app.getLoadDatasReq(
				'/db/'+this.app.database+'/organizations/list.dat?field=*&limit=0'
					+'&orderby=label&dir=asc&fieldsearch=label&fieldsearchval='+search
					+'&fieldsearchop=like',
				this.orgs={}));
			this.addReq(this.app.getLoadDatasReq(
				'/db/'+this.app.database+'/users/list.dat?field=*&limit=0'
					+'&orderby=firstname&dir=asc&fieldsearch=firstname'
					+'&fieldsearch=lastname&fieldsearchval='+search
					+'&fieldsearchval='+search+'&fieldsearchop=like&fieldsearchop=like'
					+'&fieldsearchor=true',
				this.users={}));
			this.sendReqs(this.renderSearch.bind(this),this.loadError.bind(this));
		}
	},
	renderSearch : function() {
		var tpl;
		if(this.users.entries||this.orgs.entries) {
			tpl='<div class="'+(this.app.screenType=='small'?'':'h')+'box">'
				+'	<div class="box"'+(this.app.screenType!='small'?
					' style="overflow-y:scroll;"':'')+'><dl>';
			if(this.users.entries&&this.users.entries.length) {
				tpl+='		<dt><strong>'+this.locale.users+'</strong></dt>';
				for(var i=0, j=this.users.entries.length; i<j; i++) {
					tpl+='		<dd><a href="#win'+this.id+'-show:users:'
						+this.users.entries[i].id+'" title="">'
						+this.users.entries[i].label+'</a></dd>';
				}
			}
			if(this.orgs.entries&&this.orgs.entries.length)
				{
				tpl+='		<dt><strong>'+this.locale.organizations+'</strong></dt>';
				for(var i=0, j=this.orgs.entries.length; i<j; i++) {
					tpl+='		<dd><a href="#win'+this.id+'-show:organizations:'
						+this.orgs.entries[i].id+'" title="">'+this.orgs.entries[i].label
						+'</a></dd>';
				}
			}
			tpl+='	</dl></div>'
				+'	<div'+(this.app.screenType=='small'?
					' class="box"':' class="vbox xlarge"')+' id="win'+this.id+'-show">'
					+'<div class="box"><p>'+this.locale.show_content+'</p></div></div>'
				+'</div>';
			}
		else
			{
			tpl='<div class="box"><p>'+this.locale.empty+'</p></div>';
			}
		this.view.innerHTML=tpl;
	},
	// Show
	show: function(event,params)
		{
		this.app.getLoadDatasReq('/db/'+this.app.database+'/'+params[0]+'/'
			+params[1]+'.dat?field=*&field=idJoinsContactsId.*&field=idJoinsPlacesId.*',
			this.result={'entityType':params[0]},this.renderShow.bind(this)).send();
		},
	renderShow : function(event, params) {
		var tpl='<div class="box">';
		tpl+='<h2>'+this.result.entry.label+'</h2>'
			+'<p>'
			+(this.result.entry.joined_organizationTypes
				&&this.result.entry.joined_organizationTypes[0]?
				this.locale.type+' '+this.result.entry.joined_organizationTypes[0].label
				+'<br />':'')
			+(this.result.entry.group_label?
				this.locale.group+' '+this.result.entry.group_label+'<br />':'')
			+(this.app.user.groupName=='webmasters'
				||this.app.user.groupName=='administrators'?
				'	<a href="#win'+this.id+'-modify:'+this.result.entityType+':'
					+this.result.entry.id+'" title="'+this.locale.modify_tx+'">'
					+this.locale.modify+'</a>':'')
			+'</p>';
		tpl+='</div>';
		tpl+='<div class="tabbox vbox">';
		tpl+='	<ul class="toolbar">';
			tpl+='		<li><a href="#win'+this.id+'-selectTab:win'+this.id
				+'-tab-places" class="button" title="'+this.locale.places_link_tx
				+'">'+this.locale.places_link+'</a></li>';
			tpl+='		<li><a href="#win'+this.id+'-selectTab:win'+this.id
				+'-tab-contact" class="button" title="'+this.locale.contact_link_tx
				+'">'+this.locale.contact_link+'</a></li>';
		if(this.app.user.groupName=='webmasters'
			||this.app.user.groupName=='administrators') {
			if(this.result.entityType=='organizations'&&this.result.entry.type_id==3)
				tpl+='		<li><a href="#win'+this.id+'-selectTab:win'+this.id
					+'-tab-cost" class="button" title="'+this.locale.cost_link_tx+'">'
					+this.locale.cost_link+'</a></li>';
		}
		tpl+='	</ul>';
		tpl+='<div class="tab vbox" id="win'+this.id+'-tab-places">'
			+'<div class="box">';
		if(this.result.entry.idJoinsPlacesId
			&&this.result.entry.idJoinsPlacesId.length) {
			tpl+='<ul>';
			for(var i=this.result.entry.idJoinsPlacesId.length-1; i>=0; i--) {
				tpl+='<li>'+(this.result.entry.idJoinsPlacesId[i].city?
						'<br />'+this.result.entry.idJoinsPlacesId[i].address+'<br />'
					+(this.result.entry.idJoinsPlacesId[i].address2?
						this.result.entry.idJoinsPlacesId[i].address2+'<br />':'')
					+this.result.entry.idJoinsPlacesId[i].postalCode+'<br />'
					+this.result.entry.idJoinsPlacesId[i].city:'')
					+(this.result.entry.idJoinsPlacesId[i].lat?
						'<br /><a href="geo:'+this.result.entry.idJoinsPlacesId[i].lat
							+','+this.result.entry.idJoinsPlacesId[i].lng
							+'" title="'+this.locale.places_geo_link_tx+'">'
							+this.locale.places_geo_link+'</a>':'')
					+'</li>';
			}
			tpl+='</ul>';
		} else {
			tpl+='<p>'+this.locale.places_empty+'</p>';
		}
		tpl+='</div></div>';
		tpl+='<div class="tab vbox" id="win'+this.id+'-tab-contact">'
			+'<div class="box">';
		if(this.result.entry.idJoinsContactsId
			&&this.result.entry.idJoinsContactsId.length) {
			tpl+='<ul>';
			for(var i=this.result.entry.idJoinsContactsId.length-1; i>=0; i--) {
				for(var j=this.contactTypes.entries.length-1; j>=0; j--) {
					if(this.result.entry.idJoinsContactsId[i].type==
						this.contactTypes.entries[j].id) {
						tpl+='<li><strong>'
							+this.locale['contact_'+this.contactTypes.entries[j].name]
							+' :</strong> '
							+'<br />'
							+(this.contactTypes.entries[j].protocol?
								'<a href="'+this.contactTypes.entries[j].protocol
								+this.result.entry.idJoinsContactsId[i].value+'" title="'
								+this.locale['contact_'+this.contactTypes.entries[j].name
									+'_link_tx']
								+'">':'')
							+this.result.entry.idJoinsContactsId[i].value
							+(this.contactTypes.entries[j].protocol?'</a>':'')
							+'</li>';
					}
				}
			}
			tpl+='</ul>';
		} else {
			tpl+='<p>'+this.locale.contact_empty+'</p>'
		}
		tpl+='</div></div>';
		
		if(this.app.user.groupName=='webmasters'
			||this.app.user.groupName=='administrators') {
			if(this.result.entityType=='organizations'
				&&this.result.entry.type_id==3) {
				tpl+='<div class="tab vbox" id="win'+this.id+'-tab-cost">'
					+'	<div class="box">'
					+'		<p>'+this.locale.cost_empty+'</p>'
					+'	</div>'
					+'</div>';
			}
		}
		tpl+='</div>';
		$('win'+this.id+'-show').innerHTML=tpl;
	},
	// CRUD
	add: function(event,params) {
		this.app.createWindow(params[0][0].toUpperCase()
			+params[0].substr(1)+'FormWindow');
	},
	modify: function(event,params) {
		this.app.createWindow(params[0][0].toUpperCase()
			+params[0].substr(1)+'FormWindow',{'entryId':params[1]});
	},
	// Tab
	selectTab: function(event,params) {
		if(params[0]=='win'+this.id+'-tab-cost') {
			var req=this.app.getLoadDatasReq('/interventions.dat?mode=total'
					+'&filter=organization&value='+this.result.entry.id+'',
				this.cost={},this.costLoaded.bind(this));
			req.send();
		} else {
			this.app.selectTab(event,params);
		}
	},
	costLoaded: function(event,params) {
		if(this.cost&&this.cost.total) {
			var tpl='<div class="box"><p>'+this.cost.total+'&euro;</p>';
			tpl+='<ul>';
			for(var i=this.cost.entries.length-1; i>=0; i--) {
				tpl+='<li><strong>'+this.cost.entries[i].total+' :</strong> '
					+this.cost.entries[i].year
					+'</li>';
			}
			tpl+='</ul>';
			'</div>';
			$('win'+this.id+'-tab-cost').innerHTML=tpl;
		}
		this.app.selectTab(null,new Array('win'+this.id+'-tab-cost'));
	},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-search');
		this.app.unregisterCommand('win'+this.id+'-show');
		this.app.unregisterCommand('win'+this.id+'-selectTab');
		if(this.app.user.groupName=='webmasters'
			||this.app.user.groupName=='administrators') {
			this.app.unregisterCommand('win'+this.id+'-add');
			this.app.unregisterCommand('win'+this.id+'-modify');
		}
		this.parent();
	}
});
