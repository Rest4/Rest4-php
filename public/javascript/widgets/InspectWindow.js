var InspectWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		this.classNames.push('InspectWindow');
		this.options.windowId=0;
		// Initializing the window
		this.parent(desktop,options);
		// Registering commands	
		this.app.registerCommand('win'+this.id+'-reload',this.renderContent.bind(this));
		this.app.registerCommand('win'+this.id+'-inspect',this.inspect.bind(this));
		},
	// Rendering window
	render : function() {
		// Menu
		this.options.menu[0]={'label':this.locale.reload,'command':'reload','title':this.locale.reload_tx};
		// Drawing window
		this.parent();
		},
	renderContent : function() {
		// Putting window content
		var tpl='<div class="box">';
		for(var i=0; i<this.app.windows.length; i++)
			{
			tpl+='<li><a href="#win'+this.id+'-inspect:'+this.app.windows[i].id+'">'+(this.app.windows[i].options.name?this.app.windows[i].options.name:'win'+this.app.windows[i].id)+'</a></li>';
			}
		if(this.options.windowId)
			{
			var theWindow=this.app.getWindowFromRoot($('win'+this.options.windowId));
			tpl+='<h3>'+(theWindow.options.name?theWindow.options.name:'win'+theWindow.id)+'</h3>'
				+'<p>'
				+'<strong>Id:</strong> '+theWindow.id+'<br />'
				+'<strong>Pending requests:</strong> '+theWindow.reqs.length+'<br />';
			for(var i=theWindow.reqs.length-1; i>=0; i--)
				tpl+='- '+theWindow.reqs[i].options.method+'-'+theWindow.reqs[i].options.url+'<br />';
			tpl+='<strong>Error requests:</strong> '+theWindow.errReqs.length+'<br />';
			for(var i=theWindow.errReqs.length-1; i>=0; i--)
				tpl+='- '+theWindow.errReqs[i].options.method+'-'+theWindow.errReqs[i].options.url+'<br />';
			tpl+='</p>';
			}
		tpl+='</div>';
		this.view.innerHTML=tpl;
		},
	inspect: function(event,params)
		{
		this.options.windowId=params[0];
		this.renderContent();
		},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-reload');
		this.app.unregisterCommand('win'+this.id+'-inspect');
		this.parent();
		}
});