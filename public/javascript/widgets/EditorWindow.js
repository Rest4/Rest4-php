var EditorWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Setting options
		this.classNames.push('EditorWindow');
		// Default options
		this.options.path='';
		this.options.content='';
		this.temp_content='';
		// Initializing the window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-new',this.newDocument.bind(this));
		this.app.registerCommand('win'+this.id+'-close',this.closeDocument.bind(this));
		this.app.registerCommand('win'+this.id+'-open',this.openDocument.bind(this));
		this.app.registerCommand('win'+this.id+'-save',this.saveDocument.bind(this));
		this.app.registerCommand('win'+this.id+'-saveAs',this.saveAsDocument.bind(this));
		},
	// Rendering window
	render : function() {
		if(!this.options.name)
			this.options.name=this.locale.title_default;
		// Menu
		this.options.menu=[];
		this.options.menu[0]={'label':this.locale.menu_file,'childs':[],'title':this.locale.menu_file_tx};
		this.options.menu[0].childs[0]={'label':this.locale.menu_new,'command':'new','title':this.locale.menu_new_tx};
		this.options.menu[0].childs[1]={'label':this.locale.menu_open,'command':'open','title':this.locale.menu_open_tx};
		this.options.menu[0].childs[2]={'label':this.locale.menu_save,'command':'save','title':this.locale.menu_save_tx};
		this.options.menu[0].childs[3]={'label':this.locale.menu_save_as,'command':'saveAs','title':this.locale.menu_save_as_tx};
		this.options.menu[0].childs[4]={'label':this.locale.menu_close,'command':'close','title':this.locale.menu_close_tx};
		// Drawing window
		this.parent();
		// Putting window content
		var tpl='<form class="vbox"><textarea id="win'+this.id+'-textarea">'+this.options.content+'</textarea></form>';
		this.view.innerHTML=tpl;
		},
	loadContent: function()
		{
		if(this.options.path)
			{
			var req=new RestRequest({'url':'/fs'+this.options.path,'method':'get'});
			req.addEvent('done',this.loadedDocument.bind(this));
			req.addEvent('error',this.loadError.bind(this));
			req.send();
			}
		},
	loadedDocument: function(req)
		{
		$('win'+this.id+'-textarea').value=req.xhr.responseText;
		this.temp_content=$('win'+this.id+'-textarea').value;
		this.mover.innerHTML=this.locale.title+' '+this.options.path;
		},
	// New doccuments
	newDocument: function(event)
		{
		if(this.temp_content != $('win'+this.id+'-textarea').value)
			this.app.createWindow('ConfirmWindow',{'name':this.locale.confirm_new_title,'content':this.locale.confirm_new_content,'onValidate':this.confirmNewOk.bind(this),'onCancel':this.confirmNewCancel.bind(this)});
		else
			{
			$('win'+this.id+'-textarea').value='';
			this.options.path='';
			this.temp_content='';
			this.mover.innerHTML=this.locale.title_default;
			}
		},
	// Open document
	openDocument: function(event)
		{
		if(this.temp_content != $('win'+this.id+'-textarea').value)
			this.app.createWindow('ConfirmWindow',{'name':this.locale.confirm_open_title,'content':this.locale.confirm_open_content,'onValidate':this.confirmOpenOk.bind(this),'onCancel':this.confirmOpenCancel.bind(this)});
		else
			{
			var index=this.options.path.lastIndexOf('/');
			this.app.createWindow('PromptFileWindow',{'onValidate':this.documentOpened.bind(this),'mime':'(text\/(.*)|application\/internal)','path':(index>=0?this.options.path.substr(0,index+1):'/')});
			}
		},
	documentOpened: function(event, output)
		{
		if(!output)
			this.app.createWindow('AlertWindow',{'name':this.locale.content_error_title_loading,'content':this.locale.content_error_loading});
		else
			{
			this.options.path=output;
			this.loadContent();
			}
		},
	// Save document
	saveDocument: function(event)
		{
		if(!this.options.path)
			this.saveAsDocument();
		else
			{
			var req=this.app.createRestRequest({'path':'fs'+this.options.path,'method':'put'});
			req.addEvent('done',this.saveDone.bind(this));
			req.addEvent('error',this.saveError.bind(this));
			req.send($('win'+this.id+'-textarea').value);
			}
		},
	saveDone: function()
		{
		this.app.createWindow('AlertWindow',{'name':this.locale.save_as_done_title,'content':this.locale.save_as_done_content});
		this.temp_content=$('win'+this.id+'-textarea').value;
		this.syncWindows('loadContent',[{'option':'path','value':this.options.path}]);
		},
	saveError: function(req)
		{
		this.app.createWindow('AlertWindow',{'name':this.locale.save_as_error_title,'content':this.locale.save_as_error_content});
		},
	// Save as document
	saveAsDocument: function(event)
		{
		var nameFile=this.options.path.substring(this.options.path.lastIndexOf("/")+1);
		this.app.createWindow('PromptFileWindow',{'type':'save','path':(this.options.path.substring(0,this.options.path.lastIndexOf('/')+1)||'/'),'nameFile':nameFile,'onValidate':this.documentAsSaved.bind(this)});
		},
	documentAsSaved: function(event, output)
		{
		this.options.path=output;
		if(!this.options.path)
			this.app.createWindow('AlertWindow',{'name':this.locale.content_error_title_loading,'content':this.locale.content_error_loading});
		else
			{
			var req=this.app.createRestRequest({'path':'fs'+this.options.path,'method':'put'});
			req.addEvent('done',this.saveAsDone.bind(this));
			req.addEvent('error',this.saveAsError.bind(this));
			req.send($('win'+this.id+'-textarea').value);
			this.mover.innerHTML=this.locale.title+' '+this.options.path;
			}
		},
	saveAsDone: function()
		{
		this.app.createWindow('AlertWindow',{'name':this.locale.save_as_done_title,'content':this.locale.save_as_done_content});
		this.temp_content=$('win'+this.id+'-textarea').value;
		this.syncWindows('loadContent',[{'option':'path','value':this.options.path}]);
		},
	saveAsError: function(req)
		{
		this.app.createWindow('AlertWindow',{'name':this.locale.save_as_error_title,'content':this.locale.save_as_error_content});
		},
	// Close document
	closeDocument: function(event)
		{
		if($('win'+this.id+'-textarea')&&this.temp_content != $('win'+this.id+'-textarea').value)
			this.app.createWindow('ConfirmWindow',{'name':this.locale.confirm_close_title,'content':this.locale.confirm_close_content,'onValidate':this.confirmCloseOk.bind(this),'onCancel':this.confirmCloseCancel.bind(this)});
		else
			this.close();
		},
	//Confirm
	confirmCloseOk: function(event)
		{
		this.close();
		},
	confirmCloseCancel: function(event)
		{
		},
	confirmNewOk: function(event)
		{
		$('win'+this.id+'-textarea').value='';
		this.options.path='';
		this.temp_content='';
		this.mover.innerHTML=this.locale.title_default;
		},
	confirmNewCancel: function(event)
		{
		},
	confirmOpenOk: function(event)
		{
		this.app.createWindow('PromptFileWindow',{'onValidate':this.documentOpened.bind(this)});
		},
	confirmOpenCancel: function(event)
		{
		},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-new');
		this.app.unregisterCommand('win'+this.id+'-open');
		this.app.unregisterCommand('win'+this.id+'-save');
		this.app.unregisterCommand('win'+this.id+'-saveAs');
		this.parent();
		}
});