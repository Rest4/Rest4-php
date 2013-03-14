var CodeWindow=new Class({
	Extends: WebWindow,
	initialize: function(app,options)
		{
		this.classNames.push('CodeWindow');
		this.options.content='ControlWindow=null;\n'
			+'app.createWindow(\'ControlWindow\');';
		// Initializing the window
		this.parent(app,options);
		// Registering commands	
		this.app.registerCommand('win'+this.id+'-execute',this.execute.bind(this));
		},
	// Rendering window
	render : function()
		{
		// Menu
		this.options.menu[0]={'label':this.locale.execute,'command':'execute','title':this.locale.execute_tx};
		// Drawing window
		this.parent();
		// Putting window content
		var tpl='<form class="vbox"><textarea id="win'+this.id+'-textarea">'+this.options.content+'</textarea></form>';
		this.view.innerHTML=tpl;
		},
	execute: function()
		{
		if($('win'+this.id+'-textarea').value)
			{
			(eval('(function(app) { '+ $('win'+this.id+'-textarea').value +' })'))(this.app);
			this.notice(this.locale.done);
			}
		},
	// Window destruction
	destruct : function()
		{
		this.app.unregisterCommand('win'+this.id+'-execute');
		this.parent();
		}
	});