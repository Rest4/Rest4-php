var LogWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		this.classNames.push('LogWindow');
		// Default options
		this.options.name='';
		this.options.appendOnTop=true;
		// Required options
		this.requiredOptions.push('name');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands	
		this.app.registerCommand('win'+this.id+'-clear',this.clear.bind(this));
		this.app.registerCommand('win'+this.id+'-reverse',this.reverse.bind(this));
	},
	// Rendering window
	render : function() {
		this.options.name=this.locale.title+' '+this.options.name;
		// Menu
		this.options.menu[0]={'label':this.locale.clear_label,'command':'clear','title':this.locale.clear_tx};
		this.options.menu[1]={'label':this.locale.reverse_label,'command':'reverse','title':this.locale.reverse_tx};
		// Drawing window
		this.parent();
		// Putting window content
		var panel=document.createElement('div');
		panel.addClass('box');
		this.log=document.createElement('p');
		panel.appendChild(this.log);
		this.view.appendChild(panel);
	},
	// Commands
	append : function(line) {
		if(this.log)
			{
			if(this.options.appendOnTop)
				this.log.innerHTML=line+'<br />'+this.log.innerHTML;
			else
				this.log.innerHTML+=line+'<br />';
			}
	},
	clear : function(line) {
		if(this.log)
			this.log.innerHTML='';
	},
	reverse : function(line) {
		if(!this.options.appendOnTop)
			this.options.appendOnTop=true;
		else
			this.options.appendOnTop=false;
	},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-clear');
		this.app.unregisterCommand('win'+this.id+'-reverse');
		this.parent();
		}
});