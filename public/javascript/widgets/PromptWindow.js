var PromptWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.synchronize=true;
		this.options.output={};
		this.classNames.push('PromptWindow');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-validate',this.validateDocument.bind(this));
	},
	// Rendering window
	render : function() {
		// Adding default contents
		if(!this.options.content)
			this.options.content=this.locale.content;
		//Drawing window
		this.parent();
		// Putting window content
		var tpl='<div class="box">'
			+'<form action="#win'+this.id+'-validate">'
			+'	<fieldset>'
			+'		<legend>'+(!this.options.legend?this.locale.content:this.options.legend)+'</legend>'
			+'		<p class="fieldrow">'
			+'			<label for="win'+this.id+'-prompt">'+(!this.options.label?this.locale.content_label:this.options.label)+'</label>'
			+'			<input type="text" name="value" id="win'+this.id+'-prompt" class="parameter" size="80" value="'+(!this.options.value?this.locale.content_value:this.options.value)+'" placeholder="'+(!this.options.placeholder?this.locale.content_form_value:this.options.placeholder)+'" required="required" />'
			+'		</p>'
			+'	</fieldset>'
			+'	<fieldset>'
			+'		<p class="fieldrow">'
			+'			<input type="submit" title="'+this.locale.validate_tx+'" name="validate" value="'+this.locale.validate+'" />'
			+'		</p>'
			+'	</fieldset>'
			+'</form></div>';
		this.view.innerHTML=tpl;
		},
	// Confirm commands
	validateDocument: function(event)
		{
		this.options.output=$('win'+this.id+'-prompt').value;
		this.close();
		this.fireEvent('validate', [event, this.options.output]);
		},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-validate');
		this.parent();
		}
});