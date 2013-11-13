var XGpsWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		this.classNames.push('XGpsWindow');
		// Setting options
		this.options.user='pconil';
		this.options.date=(new Date).toISOString().substr(0,10);
		// Need Google Maps
		this.needMaps=true;
		// Initializing window
		this.parent(desktop,options);
		// Registering window
		this.app.registerCommand('win'+this.id+'-handleForm',
			this.handleForm.bind(this));
		this.app.registerCommand('win'+this.id+'-loadContent',
			this.loadContent.bind(this));
	},
	// Window
	load : function() {
		this.addReq(this.app.getLoadDatasReq('/db/vigisystem/vehicles/list.dat?'
			+'field=*&field=userLinkUsersId.label&field=userLinkUsersId.login&limit=0'
			+'&orderby=userLinkUsersId.firstname&dir=asc',this));
		this.parent();
	},
	render : function() {
		this.options.name=this.locale.title;
		// Creating form
		tpl='<form id="win'+this.id+'-handleForm" action="#win'+this.id
				+'-loadContent">'
			+'	<label>'+this.locale.form_user_label+'<select name="user">'
			+'		<option value="">'+this.locale.form_user_default_value+'</option>';
		for(var i=0, j=this.entries.length; i<j; i++) {
			tpl+='		<option value="'+this.entries[i].user.login+'"'
				+(this.entries[i].user.login==this.options.user?
					' selected="selected"':'')
				+'>'+this.entries[i].user.firstname+' '+this.entries[i].user.lastname
				+'</option>';
		}
		tpl+='	</select></label>'
			+'	<label>'+this.locale.form_date_label+' <input type="date" name="date"'
				+' value="'+this.options.date+'" /></label>'
			+'	<input type="submit" value="'+this.locale.form_submit+'" />';
			+'</form>';
		this.options.forms.push({
			'tpl':tpl,
			'label':this.locale.menu_filter,
			'command':'loadContent',
			'title':this.locale.menu_filter_tx
		});
		// Filling menu
		this.options.menu.push({
			'label':this.locale.menu_refresh,
			'command':'loadContent',
			'title':this.locale.menu_refresh_tx,
			'showAtStart':true
		});
		this.parent();
	},
	// Content
	loadContent: function()	{
		if(this.markers) {
			for(var i=this.markers.length-1; i>=0; i--) {
				this.markers[i].setMap(null);
			}
		}
		if(this.polyline) {
			this.polyline.setMap(null);
		}
		var vals=this.options.date.split('-');
		this.gps=null;
		this.addReq(this.app.getLoadDatasReq('/xgps/'+this.options.user
			+'/directions.dat?day='+this.options.date,this));
		this.parent();
	},
	renderContent: function(req)	{
		if(this.gps) {
			if(!this.map) {
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
				// Drawing map
				this.map = new google.maps.Map($('win'+this.id+'-map'), {
					mapTypeId: google.maps.MapTypeId.ROADMAP
				});
				this.markers=[];
			}
			this.markers=[];
			var latLng;
			var marker;
			this.bounds=new google.maps.LatLngBounds();
			this.polyline=new google.maps.Polyline();
			this.latLngs=[];
			for(i=0, j=this.gps.length; i<j; i++) {
				latLng=new google.maps.LatLng(this.gps[i].lat,this.gps[i].lng);
				if(j<200||this.gps[i].type!='dot'||i%30==0)
					this.bounds.extend(latLng);
				if(j<200||this.gps[i].type!='dot'||i%30==0)
					this.latLngs.push(latLng);
				if(j<150||this.gps[i].type!='dot') {
					marker=new google.maps.Marker({
						position: latLng,
						title:this.secondsToTime(this.timeToSeconds(this.gps[i].h)+(3600*2)),
						icon:'/mpfs/public/images/map/map_'+this.gps[i].type+'.png'});
					this.gps[i].i=i;
					marker.entry=this.gps[i];
					marker.window=this;
					marker.setMap(this.map);
					this.markers.push(marker);
					google.maps.event.addListener(marker, 'click',
						this.showInfo.bind(marker));
				}
			}
			this.polyline.setPath(this.latLngs);
			this.polyline.setMap(this.map);
			google.maps.event.trigger(this.map, 'resize');
			this.map.fitBounds(this.bounds);
		} else {
			this.map=null;
			this.view.innerHTML='<div class="box"><p>'
				+this.locale.map_empty+'</p></div>';
		}
	},
	// Info
	showInfo : function () {
	  var d = new Date(this.entry.date + ' ' + this.entry.h + ' UTC');
		for(var i=this.window.markers.length-1; i>=0; i--) {
			if(this.window.markers[i].infowindow) {
				this.window.markers[i].infowindow.close();
				this.window.markers[i].infowindow=null;
			}
		}
		this.infowindow = new google.maps.InfoWindow({
			content: '<h2>'+d.getHours()+':'+(d.getMinutes())+':'+d.getSeconds()+'</h2>'
				+'<strong>'+this.window.locale.map_coords+'</strong> '
					+this.entry.lat+','+this.entry.lng+'<br />'
				+'<strong>'+this.window.locale.map_speed+'</strong> '
					+this.entry.speed+this.window.locale.map_speed_unit+'<br />'
				+'<strong>'+this.window.locale.map_heading+'</strong> '
					+this.entry.head+this.window.locale.map_heading_unit+'<br />'
				+'<strong>'+this.window.locale.map_altitude+'</strong> '
					+this.entry.alt+this.window.locale.map_altitude_unit+'<br />'
				+'<strong>'+this.window.locale.map_satellite+'</strong> '
					+this.entry.sat
				+(this.entry.type=='stop'?'<br /><strong>'+this.window.locale.map_stop
					+'</strong> '+Math.floor(parseInt(this.entry.d/60))+' '
					+this.window.locale.map_stop_unit:'')
				+'</p>'
		});
		this.infowindow.open(this.getMap(),this);
	},
	// handleForm for client
	handleForm: function(event) {
		if(event.target.get('name')=='user') {
			if(event.target.value&&event.target.value!=this.options.user) {
				this.options.user=event.target.value;
			}
		} else if(event.target.get('name')=='date'
			&&event.target.value&&event.target.value!=this.options.date) {
			this.options.date=event.target.value;
		}
	},
	// handle win resize
	update: function() {
		this.parent();
		if(this.map&&!this.reqs.length) {
			google.maps.event.trigger(this.map, 'resize');
			this.map.fitBounds(this.bounds);
		}
	},
	// Time management
	secondsToTime: function(seconds) {
		var h=(Math.floor(seconds/3600)%24),
			m=(Math.floor(seconds/60)%60),
			s=(seconds%60);
		return ((h+'').length==1?'0':'')+h
			+':'+((m+'').length==1?'0':'')+m
			+':'+((s+'').length==1?'0':'')+s;
	},
	timeToSeconds: function(time) {
		var values=time.split(':');
		return (parseInt(values[0],10)*3600)
			+(parseInt(values[1],10)*60)+parseInt(values[2],10);
	},
	//Destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-handleForm');
		this.app.unregisterCommand('win'+this.id+'-loadContent');
		this.parent();
	}
});
