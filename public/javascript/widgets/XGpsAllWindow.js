var XGpsAllWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		this.classNames.push('XGpsAllWindow');
		// Need Google Maps
		this.needMaps=true;
		// Initializing window
		this.parent(desktop,options);
		// Registering window
		this.app.registerCommand('win'+this.id+'-refresh',
			this.loadContent.bind(this));
		this.app.registerCommand('win'+this.id+'-view',
			this.view.bind(this));
	},
	// Window
	load : function() {
		this.addReq(this.app.getLoadDatasReq('/db/vigisystem/vehicles/list.dat?'
			+'field=*&field=userLinkUsersId.label&limit=0'
			+'&orderby=userLinkUsersId.firstname&dir=asc',this.users={}));
		this.parent();
	},
	render : function() {
		this.options.name=this.locale.title;
		// Filling menu
		this.options.menu.push({
			'label':this.locale.menu_refresh,
			'command':'refresh',
			'title':this.locale.menu_refresh_tx
		});
		this.parent();
		// Rendering content
		var div=document.createElement('div');
		div.addClass('map');
		div.addClass('box');
		div.set('id','win'+this.id+'-map');
		var div2=document.createElement('div');
		div2.addClass('vbox');
		div2.addClass('mapbox');
		div2.appendChild(div);
		if(this.view.firstChild)
		while(this.view.firstChild)
			this.view.removeChild(this.view.firstChild);
		this.view.appendChild(div2);
	},
	// Content
	loadContent: function()	{
		if(this.markers)
		for(var i=this.markers.length-1; i>=0; i--)
			{
			this.markers[i].setMap(null);
			}
		this.app.loadDatas('/xgps/all.dat',this,this.renderMap.bind(this));
	},
	renderMap: function(req)	{
		if(!this.markers) {
			// Drawing map
			this.map = new google.maps.Map($('win'+this.id+'-map'), {
				zoom: 8,
				center: new google.maps.LatLng(50.2475, 3.11928),
				mapTypeId: google.maps.MapTypeId.ROADMAP
			});
			this.markers=[];
		}
		this.markers=[];
		var latLng;
		var marker;
		this.bounds=new google.maps.LatLngBounds();
		var lines=req.xhr.responseText.split('\n');
		for(var i=0, j=this.entries.length; i<j; i++) {
			// Server time,UnitID,Device time,Longitude,Latitude, Speed, Heading (0-360°), Altitude, Satellite, Report ID, Inputs, Outputs
			this.entries[i].fields=(this.entries[i].gps?
				this.entries[i].gps.split(','):[]);
			if(this.entries[i].fields[2]&&this.entries[i].fields[3]) {
				for(var k=0, l=this.users.entries.length; k<l; k++) {
					if(this.users.entries[k]==this.entries[i].fields[1]) {
						this.entries[i].fields[8]=this.users.entries[k].user.login;
						this.entries[i].fields[9]=this.users.entries[k].user.label;
					}
				}
				latLng=new google.maps.LatLng(this.entries[i].fields[3],
					this.entries[i].fields[2]);
				this.bounds.extend(latLng);
				marker=new google.maps.Marker({
					position: latLng,
					title:this.entries[i].fields[1],
					icon:'/mpfs/public/images/map/map_truck.png'
				});
				marker.entry=this.entries[i];
				marker.window=this;
				marker.setMap(this.map);
				this.markers.push(marker);
				google.maps.event.addListener(marker, 'click',
					this.showInfo.bind(marker));
			}
		}
		google.maps.event.trigger(this.map, 'resize');
		this.map.fitBounds(this.bounds);
	},
	// Info
	showInfo : function () {
		for(var i=this.window.markers.length-1; i>=0; i--) {
			if(this.window.markers[i].infowindow) {
				this.window.markers[i].infowindow.close();
				this.window.markers[i].infowindow=null;
			}
		}
		this.infowindow = new google.maps.InfoWindow({
			content: '<h2><a href="#win'+this.window.id+'-view:'+this.entry.login
					+'">'+this.entry.label+'</a></h2>'
				+'<p><strong>'+this.window.locale.map_date+'</strong> '+this.entry.date
				+'<br />'
				+'<strong>'+this.window.locale.map_hour+'</strong> '
					+this.window.secondsToTime(this.window.timeToSeconds(
						this.entry.fields[0])+3600)+'<br />'
				+'<strong>'+this.window.locale.map_device+'</strong> '
					+this.entry.fields[1]+'<br />'
				+'<strong>'+this.window.locale.map_coords+'</strong> '
					+this.entry.fields[3]+','+this.entry.fields[2]+'<br />'
				+'<strong>'+this.window.locale.map_speed+'</strong> '
					+this.entry.fields[4]+this.window.locale.map_speed_unit+'<br />'
				+'<strong>'+this.window.locale.map_heading+'</strong> '
					+this.entry.fields[5]+this.window.locale.map_heading_unit+'<br />'
				+'<strong>'+this.window.locale.map_altitude+'</strong> '
					+this.entry.fields[6]+this.window.locale.map_altitude_unit+'<br />'
				+'<strong>'+this.window.locale.map_satellite+'</strong> '
					+this.entry.fields[7]+'</p>'
		});
		this.infowindow.open(this.getMap(),this);
	},
	// handle win resize
	update: function() {
		this.parent();
		if(this.map&&!this.reqs.length) {
			google.maps.event.trigger(this.map, 'resize');
			this.map.fitBounds(this.bounds);
		}
	},
	// Show target
	view: function(event,params) {
		this.app.createWindow('XGpsWindow',{'user':params[0]});
	},
	// Time management
	secondsToTime: function(seconds) {
		var h=(Math.floor(seconds/3600)%24),
			m=(Math.floor(seconds/60)%60),
			s=(seconds%60);
		return ((h+'').length==1?'0':'')+h+':'+((m+'').length==1?'0':'')+m+':'
			+((s+'').length==1?'0':'')+s;
	},
	timeToSeconds: function(time) {
		var values=time.split(':');
		return (parseInt(values[0],10)*3600)+(parseInt(values[1],10)*60)
			+parseInt(values[2],10);
	},
	//Destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-refresh');
		this.app.unregisterCommand('win'+this.id+'-view');
		this.parent();
	}
});
