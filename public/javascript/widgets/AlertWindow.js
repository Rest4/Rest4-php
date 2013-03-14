var AlertWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.synchronize=true;
		this.options.pack=true;
		this.options.output={};
		this.classNames.push('AlertWindow');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-close',this.validate.bind(this));
	},
	// Rendering window
	render : function() {
		// Unmodifiable options
		this.options.bottomToolbox=true;
		// Adding default contents
		if(!this.options.content)
			this.options.content=this.locale.content;
		// Drawing window
		this.parent();
		// Putting window content
		var tpl ='<div class="box"><p>'+this.options.content+'</p></div>';
		this.view.innerHTML=tpl;
		var tpl='<ul class="toolbar reverse">'
					+'	<li><a href="#win'+this.id+'-close" class="button">'+this.locale.validate+'</a></li>'
					+'</ul>';
		this.bottomToolbox.innerHTML=tpl;
	},
	// Confirm commands
	validate: function(event)
		{
		this.close();
		this.fireEvent('validate', [event, this.options.output]);
		},
	// Window destruction
	destruct : function() {
		this.parent();
		}
});