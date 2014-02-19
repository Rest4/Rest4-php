var AddressBookWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Default options
		this.classNames.push('AddressBookWindow');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win' + this.id+ '-search',
		  this.search.bind(this));
		this.app.registerCommand('win' + this.id + '-show',
		  this.show.bind(this));
		this.app.registerCommand('win' + this.id + '-selectTab',
		  this.selectTab.bind(this));
		if(this.app.user.groupName=='webmasters'
			||this.app.user.groupName=='administrators') {
			this.app.registerCommand('win' + this.id + '-add', this.add.bind(this));
			this.app.registerCommand('win' + this.id + '-modify',
			  this.modify.bind(this));
		}
	},
	// Rendering window
	load : function() {
		this.addReq(this.app.getLoadDatasReq('/db/' + this.app.database
			+ '/contactTypes/list.dat?field=*',
			this.contactTypes = {}));
		this.parent();
	},
	render : function() {
		// Creating search form
		var tpl=
		    '<form action="#win' + this.id + '-search">'
			+ '	<label>' + this.locale.form_search_label
			+ '	 <input name="search" type="text" id="win' + this.id + '-search"'
			+ ' placeholder="' + this.locale.form_search_placeholder + '" /></label>'
			+ '	<input type="submit" value="' + this.locale.form_search + '" />'
			+ '</form>';
		this.options.forms.push({
			'tpl': tpl,
			'label': this.locale.menu_search,
			'command': 'loadContent',
			'title': this.locale.menu_search_tx,
			'showAtStart': true
		});
		// Filling menu
		if(this.app.user.groupName == 'webmasters'
			||this.app.user.groupName == 'administrators') {
			this.options.menu.push({
				'label': this.locale.menu_add_org,
				'command': 'add:organizations',
				'title': this.locale.menu_add_org_tx
			}, {
				'label': this.locale.menu_add_user,
				'command': 'add:users',
				'title': this.locale.menu_add_user_tx
			});
		}
		// Drawing window
		this.parent();
	},
	// Rendering content
	renderContent : function() {
	  this.view.innerHTML =
	    '<div class="box"><p>' + this.locale.content + '</p></div>';
	},
	// Search
	search: function(event) {
		var search = $('win' + this.id + '-search').value;
		if(search) {
			this.addReq(this.app.getLoadDatasReq(
				'/db/' + this.app.database + '/organizations/list.dat?field=*&limit=0'
					+ '&orderby=label&dir=asc&fieldsearch=label&fieldsearchval=' + search
					+ '&fieldsearchop=like',
				this.orgs={}));
			this.addReq(this.app.getLoadDatasReq(
				'/db/' + this.app.database + '/users/list.dat?field=*&limit=0'
					+ '&orderby=firstname&dir=asc&fieldsearch=firstname'
					+ '&fieldsearch=lastname&fieldsearchval=' + search
					+ '&fieldsearchval=' + search + '&fieldsearchop=like&fieldsearchop=like'
					+ '&fieldsearchor=true',
				this.users={}));
			this.sendReqs(this.renderSearch.bind(this), this.loadError.bind(this));
		}
	},
	renderSearch : function() {
		var tpl;
		if(this.users.entries || this.orgs.entries) {
			tpl = 
			  '<div class="' + (this.app.screenType == 'small' ? '' : 'h') + 'box">'
			+ '	<div class="box"'+(this.app.screenType != 'small'
			? ' style="overflow-y:scroll;"' : '') + '>'
			+ '   <dl>';
			if(this.users.entries && this.users.entries.length) {
				tpl +=
				'		  <dt><strong>'+this.locale.users+'</strong></dt>';
				for(var i=0, j=this.users.entries.length; i<j; i++) {
					tpl +=
				'		  <dd><a href="#win' + this.id + '-show:users:' + this.users.entries[i].id+'"'
			+ '       title="">' + this.users.entries[i].label + '</a></dd>';
				}
			}
			if(this.orgs.entries&&this.orgs.entries.length) {
				tpl +=
				'		  <dt><strong>'+this.locale.organizations+'</strong></dt>';
				for(var i=0, j=this.orgs.entries.length; i<j; i++) {
					tpl+=
				'		  <dd><a href="#win'+this.id+'-show:organizations:'
						+ this.orgs.entries[i].id +'" title="">' + this.orgs.entries[i].label
			+ '     </a></dd>';
				}
			}
			tpl+=
		    '	  </dl>'
		  + ' </div>'
			+ '	<div'+(this.app.screenType == 'small'
			? '   class="box"'
			: '   class="vbox xlarge"')
			+ '   id="win'+this.id+'-show">'
			+ '   <div class="box"><p>'+this.locale.show_content+'</p></div>'
			+ ' </div>'
			+ '</div>';
		} else {
			tpl = '<div class="box"><p>' + this.locale.empty + '</p></div>';
		}
		this.view.innerHTML=tpl;
	},
	// Show
	show: function(event,params) {
		this.app.getLoadDatasReq('/db/' + this.app.database + '/' + params[0] + '/'
			+ params[1] + '.dat?field=*&field=idJoinsContactsId.*&field=idJoinsPlacesId.*'
			+ ('organizations' == params[0] ? '&field=placeLinkPlacesId.*' : ''),
			this.result = {
				'entityType':params[0]
			},
			this.renderShow.bind(this)).send();
	},
	renderShow : function(event, params) {
		$('win'+this.id+'-show').innerHTML =
		  '<div class="box">'
		+ ' <h2>'+this.result.entry.label+'</h2>'
		+ ' <p>' + (this.result.entry.typeJoinsOrganizationTypesId
				          &&this.result.entry.typeJoinsOrganizationTypesId[0]
		? '     ' +	this.locale.type + ' ' + this.result.entry.typeJoinsOrganizationTypesId[0].label
		+ '   <br />' : '') + (this.result.entry.group_label
		? '   ' + this.locale.group + ' ' + this.result.entry.group_label
		+ '   <br />' : '') + (this.app.user.groupName=='webmasters'
				                    ||this.app.user.groupName=='administrators'
		? '   <a href="#win' + this.id + '-modify:' + this.result.entityType + ':' + this.result.entry.id + '"'
		+	'     title="'+this.locale.modify_tx+'">'
		+ '     ' + this.locale.modify
		+ '   </a>' : '')
		+ ' </p>'
		+ '</div>'
		+ '<div class="tabbox vbox">'
		+ ' <ul class="toolbar">'
		+ '   <li><a href="#win' + this.id + '-selectTab:win' + this.id + '-tab-places"'
		+ '     class="button" title="' + this.locale.places_link_tx + '">'
		+ '     ' + this.locale.places_link
		+ '   </a></li>'
		+ '		<li><a href="#win' + this.id + '-selectTab:win' + this.id + '-tab-contact"'
		+ '     class="button" title="' + this.locale.contact_link_tx + '">'
		+ '     ' + this.locale.contact_link
		+ '   </a></li>' + ((this.app.user.groupName=='webmasters'
			                    ||this.app.user.groupName=='administrators')
			                    && this.result.entityType=='organizations'
			                    &&this.result.entry.type_id==3
		? '   <li><a href="#win' + this.id + '-selectTab:win' + this.id + '-tab-cost"'
		+ '     class="button" title="'+this.locale.cost_link_tx+'">'
		+ '     ' + this.locale.cost_link
		+ '   </a></li>' : '')
		+ '	</ul>'
		+ ' <div class="tab vbox" id="win' + this.id + '-tab-places">'
		+ ' <div class="box">' + (this.result.entry.place
		? '   <p>' + (this.result.entry.place.city
		? '     ' + this.result.entry.place.address
		+ '     <br />' + (this.result.entry.place.address2 ?
		+ '     ' + this.result.entry.place.address2
		+ '     <br />' : '') + this.result.entry.place.postalCode
		+ '     <br />' + this.result.entry.place.city : '') + (this.result.entry.place.lat
		? '     <br /><a href="geo:' + this.result.entry.place.lat + ',' + this.result.entry.place.lng + '"'
		+ '       title="' + this.locale.places_geo_link_tx + '">'
		+ '       ' + this.locale.places_geo_link
	  + '     </a>' : '')
		+ '   </p>' : '') + (this.result.entry.idJoinsPlacesId
                  			&&this.result.entry.idJoinsPlacesId.length
		? '   <ul>' + this.result.entry.idJoinsPlacesId.map(function(place) {
			  return ''
		+ '     <li>' + (place.city
		? '     ' + place.address
		+ '     <br />' + (place.address2 ? place.address2
		+ '     <br />' : '') + place.postalCode
		+ '     <br />' + place.city : '') + (place.lat
		? '     <br /><a href="geo:' + place.lat + ',' + place.lng + '"'
		+ '       title="' + this.locale.places_geo_link_tx + '">'
		+ '       ' + this.locale.places_geo_link
	  + '     </a>' : '')
		+ '   </li>';
			}.bind(this)).join('')
		+ '   </ul>' : (this.result.entry.place ? ''
		: '   <p>' + this.locale.places_empty + '</p>'))
		+ ' </div>'
		+ '</div>'
		+ '<div class="tab vbox" id="win' + this.id + '-tab-contact">'
		+ ' <div class="box">' + (this.result.entry.idJoinsContactsId
			                          &&this.result.entry.idJoinsContactsId.length
		? '   <ul>' + this.result.entry.idJoinsContactsId.map(function(contact) {
		  return this.contactTypes.entries.map(function(type) {
		    return (type.id === contact.type
		? '     <li><strong>' + this.locale['contact_'+type.name] + ' :</strong> '
		+ '       <br />' + (type.protocol
		? '       <a href="' + type.protocol + contact.value+'"'
		+ '         title="'+ this.locale['contact_'+type.name +'_link_tx'] + '">':'')
		+ '         ' + contact.value + (type.protocol
		? '       </a>' : '')
		+ '      </li>' : '');
		  }.bind(this)).join('');
		}.bind(this)).join('')
		+ '   </ul>'
		: '   <p>'+this.locale.contact_empty+'</p>')
		+ ' </div>'
		+ '</div>' + ((this.app.user.groupName=='webmasters'
			              || this.app.user.groupName=='administrators')
			              && this.result.entityType=='organizations'
				            && this.result.entry.type_id==3
		? '<div class="tab vbox" id="win'+this.id+'-tab-cost">'
		+ '	<div class="box">'
		+ '		<p>'+this.locale.cost_empty+'</p>'
		+ '	</div>'
		+ '</div>' : '')
		+ '</div>';
	},
	// CRUD
	add: function(event,params) {
		this.app.createWindow(params[0][0].toUpperCase()
			+params[0].substr(1)+'FormWindow');
	},
	modify: function(event,params) {
		this.app.createWindow(
		  params[0][0].toUpperCase() + params[0].substr(1) + 'FormWindow', {
			  'entryId':params[1]
			}
		);
	},
	// Tab
	selectTab: function(event,params) {
		if(params[0] == 'win' + this.id + '-tab-cost') {
			var req=this.app.getLoadDatasReq('/interventions.dat?mode=total'
					+ '&filter=organization&value=' + this.result.entry.id,
				this.cost = {},
				this.costLoaded.bind(this));
			req.send();
		} else {
			this.app.selectTab(event,params);
		}
	},
	costLoaded: function(event,params) {
		if(this.cost&&this.cost.total) {
		  $('win'+this.id+'-tab-cost').innerHTML =
		  '<div class="box"><p>' + this.cost.total + '&euro;</p>'
		+ ' <ul>' + this.cost.entries.forEach(function(cost) {
		  return ''
		+ '   <li><strong>' + cost.total + ' :</strong> ' + cost.year + '</li>';
			}).join('')
		+ ' </ul>'
			'</div>'
		}
		this.app.selectTab(null,new Array('win' + this.id + '-tab-cost'));
	},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win' + this.id + '-search');
		this.app.unregisterCommand('win' + this.id + '-show');
		this.app.unregisterCommand('win' + this.id + '-selectTab');
		if(this.app.user.groupName == 'webmasters'
			|| this.app.user.groupName == 'administrators') {
			this.app.unregisterCommand('win' + this.id + '-add');
			this.app.unregisterCommand('win' + this.id + '-modify');
		}
		this.parent();
	}
});
