var PromptGpsWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.synchronize=true;
		this.options.output={};
		this.options.output.lat=50.2475;
		this.options.output.lng=13.11928;
		this.options.output.zoom=5;
		this.options.output.address={'country':'France', 'street_address':'10, rue Antoine DEQUEANT','postal_code':'62860','locality':'Oisy le Verger'};
		this.options.output.address='10, rue Antoine DEQUEANT 62860 Oisy le Verger France';
		this.classNames.push('PromptGpsWindow');
		// Initializing window
		this.parent(desktop,options);
		// Need Google Maps
		this.app.getMaps(this);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-submit',this.submit.bind(this));
		this.app.registerCommand('win'+this.id+'-usegps',this.useGps.bind(this));
		this.app.registerCommand('win'+this.id+'-gps',this.updateGps.bind(this));
	},
	// Rendering window
	render : function() {
		// Unmodifiable options
		this.options.bottomToolbox=true;
		// Drawing window
		this.parent();
		// Creating the marker
		var latLng=new google.maps.LatLng(this.options.output.lat, this.options.output.lng);
		this.marker=new google.maps.Marker({
			position: latLng,
			draggable: true,
			title:'Curseur'});
		/*/ Geocodign if addres given
		if(this.options.output.address)
			{
			this.geocoder = new google.maps.Geocoder();
			this.geocoder.geocode(this.options.output.address, function(results, status) {
				if (status == google.maps.GeocoderStatus.OK&&results[0])
					{
					this.map.setCenter(results[0].geometry.location);
					this.marker.setPosition(results[0].geometry.location);
					}
				}.bind(this));
			}*/
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
				zoom: this.options.output.zoom,
				center: latLng,
				mapTypeId: google.maps.MapTypeId.ROADMAP
				});
		google.maps.event.addListener(this.marker, 'dragend', this.dragEnd.bind(this));
		google.maps.event.addListener(this.map, 'rightclick', this.markerToCenter.bind(this));
		this.marker.setMap(this.map);
		// Drawing bottom toolbar
		var ul=document.createElement('ul');
		ul.addClass('toolbar');
		ul.addClass('small');
		ul.addClass('reverse');
		ul.innerHTML='<li><form id="win'+this.id+'-gps">'
							+'	<label>Lat: <input name="lat" type="number" min="-90" max="90" value="'+this.options.output.lat+'" id="win'+this.id+'-lat" /></label>'
							+'	<label>Lng: <input name="lng" type="number" min="-180" max="180" value="'+this.options.output.lng+'" id="win'+this.id+'-lng" /></label>'
							+'</form></li>'
							+'<li><a class="button" href="#win'+this.id+'-usegps" title="'+this.locale.usegps_tx+'">'+this.locale.usegps+'</a></li>'
							+'<li><a class="button" href="#win'+this.id+'-submit" title="'+this.locale.validate_tx+'">'+this.locale.validate+'</a></li>';
		this.bottomToolbox.appendChild(ul);
		},
	// Retrieve position
	useGps: function(event)
		{
		this.app.getGpsPosition(this.trackPosition.bind(this));
		},
	trackPosition: function(position) {
		this.options.output.lat=position.coords.latitude;
		this.options.output.lng=position.coords.longitude;
		this.options.output.alt=position.coords.altitude;
		$('win'+this.id+'-lat').value=this.options.output.lat;
		$('win'+this.id+'-lng').value=this.options.output.lng;
		this.updateGps();
		},
	// Update position
	updateGps: function(event)
		{
		if($('win'+this.id+'-lat').value!=this.options.output.lat)
			this.options.output.lat=$('win'+this.id+'-lat').value;
		if($('win'+this.id+'-lng').value!=this.options.output.lng)
			this.options.output.lng=$('win'+this.id+'-lng').value;
		var latLng=new google.maps.LatLng(this.options.output.lat, this.options.output.lng);
		this.marker.setPosition(latLng);
		this.map.setCenter(latLng);
		},
	dragEnd: function(event)
		{
		var latLng=this.marker.getPosition();
		this.options.output.lat=latLng.lat();
		$('win'+this.id+'-lat').value=this.options.output.lat;
		this.options.output.lng=latLng.lng();
		$('win'+this.id+'-lng').value=this.options.output.lng;
		this.map.setCenter(latLng);
		},
	markerToCenter: function(event)
		{
		this.marker.setPosition(this.map.getCenter());
		},
	// Form validation
	submit: function(event)
		{
		this.fireEvent('validate', [event, this.options.output]);
		this.close();
		},
	// handle win resize
	update: function()
		{
		this.parent();
		google.maps.event.trigger(this.map, 'resize');
		},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-submit');
		this.app.unregisterCommand('win'+this.id+'-gps');
		this.parent();
		}
});