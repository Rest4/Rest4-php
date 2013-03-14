var DbWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.database='';
		this.options.table='';
		// Required options
		this.requiredOptions.push('database','table');
		// Setting db container
		this.db={};
		// Initializing window
		this.parent(desktop,options);
	},
	// Window
	load: function()
		{
		// Setting table locale
		this.classNames.push('DbWindow');
		var req=this.app.getLoadLocaleReq('Db'+this.options.table.substring(0,1).toUpperCase()+this.options.table.substring(1)+'Table',null,false,true);
		if(req)
			{
			req.canFail=true;
			this.addReq(req);
			}
		// Getting table schema
		this.addReq(this.app.getLoadDatasReq('/db/'+this.options.database+'/'+this.options.table+'.dat',this.db));
		this.parent();
		},
	loaded: function(req)
		{
		if(this.app.locales['Db'+this.options.table.substring(0,1).toUpperCase()+this.options.table.substring(1)+'Table'])
			this.dbLocale=this.app.locales['Db'+this.options.table.substring(0,1).toUpperCase()+this.options.table.substring(1)+'Table'];
		else
			this.dbLocale=this.app.locales['DbWindow'];
		this.options.menu=[];
		this.parent();
		},
	// Destruction
	destruct : function() {
		this.parent();
		}
});