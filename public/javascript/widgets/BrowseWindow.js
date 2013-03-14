var BrowseWindow=new Class({
	Extends: WebWindow,
	initialize: function(app,options)
		{
		this.classNames.push('BrowseWindow');
		this.options.protocol=document.location.protocol.substring(0,document.location.protocol.length-1);
		this.options.hostname=document.location.hostname;
		this.options.port='';
		this.options.pathname=document.location.pathname;
		this.options.search='';
		this.options.hash='';
		this.options.url='';
		// Initializing the window
		this.parent(app,options);
		// Building url
		if(this.options.url=='')
			this.options.url=this.options.protocol+'://'+this.options.hostname+(this.options.port?':'+this.options.port:'')
				+this.options.pathname+this.options.search
				+this.options.hash;
			//else
				//parse url and fill options or get them with iframe.location ?
		},
	// Rendering window
	render : function()
		{
		// Drawing window
		this.parent();
		// Putting window content
		this.view.innerHTML='<div class="vbox"><iframe id="win'+this.id+'-frame" src="'+this.options.url+'" /></div>';
		}
	});