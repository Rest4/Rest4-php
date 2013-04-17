var PromptWindow=new Class(
	{
	Extends: FormWindow,
	initialize: function(app,options)
		{
		// Default options
		this.options.synchronize=true;
		this.options.output={};
		this.options.type='text';
		// Locale file
		this.classNames.push('PromptWindow');
		// Initializing window
		this.parent(app,options);
		},
	// Rendering window content
	renderContent: function()
		{
		this.options.fieldsets=[{'name':'entry', 'label':(this.options.legend?this.options.legend:this.locale.form_entry_legend),'fields':
			[
			{'name':'value','label':(this.options.label?this.options.label:this.locale.field_value),'input':'input',
				'type':this.options.type,'defaultValue':(this.options.output.entry&&this.options.output.entry.value?
					this.options.output.entry.value:''),
				'required':true,'placeholder':(this.options.placeholder?this.options.placeholder:this.locale.field_value_placeholder)}
			]}];
		this.parent();
		}
	});