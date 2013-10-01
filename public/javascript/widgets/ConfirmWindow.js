var ConfirmWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.output={};
		this.options.synchronize=true;
		this.options.pack=true;
		this.options.disabled=false;
		// Locale/Class name
		this.classNames.push('ConfirmWindow');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-cancel',
			this.cancelDocument.bind(this));
		this.app.registerCommand('win'+this.id+'-validate',
			this.validateDocument.bind(this));
	},
	// Rendering Window
	render : function() {
		// Unmodifiable options
		this.options.bottomToolbox=true;
		// Drawing window
		this.parent();
		tpl='<ul class="toolbar reverse">'
			+'	<li><a href="#win'+this.id+'-validate" class="button"'
			+'		'+(this.options.disabled?'disabled="disabled" ':'')+'title="'
			+(this.locale.validate_tx||this.locales['ConfirmWindow'].validate_tx)+'">'
			+(this.locale.validate||this.locales['ConfirmWindow'].validate)+'</a></li>'
			+'	<li><a href="#win'+this.id+'-cancel" class="button" title="'
			+(this.locale.cancel_tx||this.locales['ConfirmWindow'].cancel_tx)+'">'
			+(this.locale.cancel||this.locales['ConfirmWindow'].cancel)+'</a></li>'
			+'</ul>';
		this.bottomToolbox.innerHTML=tpl;
	},
	// Rendering content
	renderContent : function() {
		var tpl ='<div class="box"><p>'+this.options.content+'</p></div>';
		this.view.innerHTML=tpl;
	},
	// Enable/disabled the validation button
	setValidationState : function(enabled) {
		if(this.options.enabled=enabled) {
			$('#win'+this.id+'-validate').removeAttribute('disabled');
		} else {
			$('#win'+this.id+'-validate').setAttribute('disabled','disabled');
		}
	},
	// Commands
	cancelDocument: function(event) {
		this.fireEvent('cancel', [event, this.options.output]);
		this.close();
	},	
	validateDocument: function(event) {
		this.fireEvent('validate', [event, this.options.output]);
		this.close();
	},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-cancel');
		this.app.unregisterCommand('win'+this.id+'-validate');
		this.parent();
	}
});
