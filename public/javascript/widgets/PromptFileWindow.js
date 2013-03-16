var PromptFileWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Setting options
		this.classNames.push('PromptFileWindow');
		// Default options
		this.options.synchronize=false;
		// Required options
		this.requiredOptions.push('path');
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-newFolder',this.newFolderCommand.bind(this));	
		this.app.registerCommand('win'+this.id+'-selectFolderOpen',this.selectFolderOpen.bind(this));
		this.app.registerCommand('win'+this.id+'-selectFolderSave',this.selectFolderSave.bind(this));
		this.app.registerCommand('win'+this.id+'-validateSave',this.validateSave.bind(this));		
		this.app.registerCommand('win'+this.id+'-validateOpen',this.validateOpen.bind(this));		
		this.app.registerCommand('win'+this.id+'-handleForm',this.handleForm.bind(this));		
		this.app.registerCommand('win'+this.id+'-backFolder',this.backFolder.bind(this));	
	},
	//Rendering window
	render : function() {
		if(!this.options.name)
			this.options.name=this.locale.title+' '+this.options.path;
		if(!this.options.content)
			this.options.content=this.locale.content;
		// Menu
		if(this.options.type=='save')
			{
			this.options.menu=[];
			this.options.menu[0]={'label':this.locale.new_folder,'command':'newFolder','title':this.locale.new_folder_tx};
			}
		// Drawing window
		this.parent();
		},
	// Load callbacks
	loadContent: function()
		{
		this.files=null;
		this.addReq(this.app.getLoadDatasReq('/fsi'+this.options.path.substring(0,this.options.path.length-1)+'.dat',this));
		this.parent();
		},
	renderContent: function()
		{
		var tpl='<div class="box">';
		if(this.options.type=='save')
			tpl+='<form action="#win'+this.id+'-validateSave" id="win'+this.id+'-handleForm">';
		else
			tpl+='<form action="#win'+this.id+'-validateOpen">';
		tpl+='<ul>';
		for(var i=0, j=this.files.length; i<j; i++)
			{
			if(this.files[i].isDir&&this.files[i].name != '.')
				{
				if(this.files[i].name == '..')
					tpl+='	 <li><a href="#win'+this.id+'-backFolder:'+this.options.path+'" title="">..</a></li>';
				else if(this.options.type=='save')
					tpl+='		<li><a href="#win'+this.id+'-selectFolderSave:'+this.files[i].name+'" title="'+this.files[i].name+'">'+this.files[i].name+'</a></li>';					
				else
					tpl+=		'<li><a href="#win'+this.id+'-selectFolderOpen:'+this.files[i].name+'" title="'+this.files[i].name+'">'+this.files[i].name+'</a></li>';
				}
			}
		for(var i=0; i<j; i++)
			{
			if(this.files[i].mime)
				if(this.files[i].mime.test(new RegExp(this.options.mime)))
					tpl+='		<li><label><input type="radio" name="win'+this.id+'folder" class="file" value="'+this.files[i].name+'" />'+this.files[i].name+'</label></li>';
			}
		tpl+='</ul><p>';
		if(this.options.type=='save')
			{
			tpl+='<input type="text" id="win'+this.id+'-text" value="'+(this.options.nameFile||this.nameFile||'')+'" />';
			tpl+='<input type="submit" class="submit" value="'+this.locale.save+'" title="'+this.locale.save_tx+'" />';
			}
		else
			tpl+='<input type="submit" class="submit" value="'+this.locale.open+'" id="test" title="'+this.locale.open_tx+'" />';
		tpl+='</p></form></div>';
		this.view.innerHTML=tpl;
		},
	//new folder
	newFolderCommand: function(event)
		{
		this.app.createWindow('PromptWindow',{'name':this.locale.prompt_folder_name,'label':this.locale.prompt_folder_label,'placeholder':this.locale.prompt_folder_placeholder,'legend':this.locale.prompt_folder_legend,'onValidate':this.folderCreated.bind(this)});
		},
	folderCreated: function(event, output)
		{
		var req=new RestRequest({'url':'/fs'+(this.options.path!='/'?this.options.path+'/':'')+output+'/','method':'put'});
		req.addEvent('done',this.loadContent.bind(this));
		req.addEvent('error',this.errorCreateFolder.bind(this));
		req.send();
		this.app.registerCommand('win'+this.id+'-close',req.cancel.bind(req));
		},
	errorCreateFolder: function(event)
		{
		this.app.createWindow('AlertWindow',{'name':this.locale.error_folder_title,'content':this.locale.error_folder_content});
		},
	// SelectOpen
	selectFolderOpen: function(event,params)
		{
		this.options.path=this.options.path+params[0]+'/';
		this.mover.innerHTML=this.locale.title+this.options.path;
		this.loadContent();
		},
	//Open
	validateOpen: function(event)
		{
		var ul=this.view.getElements('ul')[0];
		for(var i=ul.childNodes.length-1; i>=0; i--)
			{
			if(ul.childNodes[i]&&ul.childNodes[i].nodeName=='LI'&&ul.childNodes[i].getElements('input')[0]&&ul.childNodes[i].getElements('input')[0].checked)
				{ this.output=ul.childNodes[i].getElements('input')[0].value; break; }
			}
		if(this.output)
			{
			this.output=this.options.path+this.output;
			this.close();
			this.fireEvent('validate', [event, this.output]);
			}
		else
			this.app.createWindow('AlertWindow',{'name':this.locale.error_open_title,'content':this.locale.error_open_content});
		},
	// SelectSave
	selectFolderSave: function(event,params)
		{
		this.options.path=this.options.path+(params[0])+'/';
		this.mover.innerHTML=this.locale.title+this.options.path;
		this.nameFile=$('win'+this.id+'-text').value;
		this.loadContent();
		$('win'+this.id+'-text').value=this.nameFile;
		},	
	//Save
	handleForm: function(event)
		{
		if(event.target.type=='radio')
			$('win'+this.id+'-text').value=event.target.value;	
		if(event.target.type=='text')
			{
			var ul=this.view.getElements('ul')[0];
			for(var i=ul.childNodes.length-1; i>=0; i--)
				{
				if(ul.childNodes[i]&&ul.childNodes[i].nodeName=='LI'&&ul.childNodes[i].getElements('input')[0])
					{
					if(ul.childNodes[i].getElements('input')[0].value==$('win'+this.id+'-text').value)
						ul.childNodes[i].getElements('input')[0].checked=true;
					else
						ul.childNodes[i].getElements('input')[0].checked=false;
					}
				}
			}
		},
	validateSave: function(event)
		{
		if($('win'+this.id+'-text').value)
			{
			this.output=this.options.path+$('win'+this.id+'-text').value;
			this.close();
			this.fireEvent('validate', [event, this.output]);
			}
		else
			this.app.createWindow('AlertWindow',{'name':this.locale.error_empty_title,'content':this.locale.error_empty_content});
		},		
	//Return
	backFolder: function(event)
		{
		this.options.path=this.options.path.substring(0,this.options.path.substring(0,this.options.path.length-1).lastIndexOf('/')+1);
		this.mover.innerHTML=this.locale.title+this.options.path;
		this.loadContent();
		},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-newFolder');
		this.app.unregisterCommand('win'+this.id+'-selectFolderOpen');
		this.app.unregisterCommand('win'+this.id+'-selectFolderSave');
		this.app.unregisterCommand('win'+this.id+'-validateSave');
		this.app.unregisterCommand('win'+this.id+'-validateOpen');
		this.app.unregisterCommand('win'+this.id+'-handleForm');
		this.app.unregisterCommand('win'+this.id+'-backFolder');
		this.parent();
		}
});
		