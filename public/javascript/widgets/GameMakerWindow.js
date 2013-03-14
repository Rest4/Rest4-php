var GameMakerWindow=new Class({
	Extends: WebWindow,
	initialize: function(app,options) {
		// Default options
		this.options.path='/public/games/tank';
		this.classNames.push('GameMakerWindow');
		// Don't load now
		this.waitBeforeLoad=true;
		// Initializing window
		this.parent(app,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-command',this.doCommand.bind(this));
		this.preload();
	},
	// Rendering window
	preload : function() {
		// Getting script list
		this.addReq(this.app.getLoadDatasReq('/mpfsi'+this.options.path+'/javascript.dat?mode=light',this.scripts={}));
		// Getting locale list
		this.addReq(this.app.getLoadDatasReq('/mpfsi'+this.options.path+'/lang/fr.dat?mode=light',this.locales={}));
		// Getting datas list
		this.addReq(this.app.getLoadDatasReq('/mpfsi'+this.options.path+'/datas.dat?mode=light',this.datas={}));
		this.sendReqs(this.load.bind(this));
	},
	load : function() {
		// Loading scripts
		for(var i=this.scripts.files.length-1; i>=0; i--)
			this.app.loadScript('/mpfs'+this.options.path+'/javascript/'+this.scripts.files[i].name);
		// Loading locales
		this.gameLocale={};
		if(this.locales.files)
			{
			for(var i=this.locales.files.length-1; i>=0; i--)
				this.addReq(this.app.getLoadDatasReq('/mpfs'+this.options.path+'/lang/fr/'+this.locales.files[i].name, this.gameLocale));
			}
		// Registering commands
		this.gameDatas={};
		if(this.datas.files)
			{
			for(var i=this.datas.files.length-1; i>=0; i--)
				this.addReq(this.app.getLoadDatasReq('/mpfs'+this.options.path+'/datas/'+this.datas.files[i].name, this.gameDatas));
			}
		this.parent();
	},
	// Rendering window
	render : function() {
		if(this.gameLocale.title)
			this.options.name=this.gameLocale.title;
		// Menu
		if(this.gameDatas.menu&&this.gameDatas.menu.length)
			{
			for(var i=0, j=this.gameDatas.menu.length; i<j; i++)
				{
				this.options.menu.push({'label':this.gameLocale['menu_'+this.gameDatas.menu[i].name],
					'command':'command:'+this.gameDatas.menu[i].command,
					'title':(this.gameLocale['menu_'+this.gameDatas.menu[i].name+'_tooltip']?
						this.gameLocale['menu_'+this.gameDatas.menu[i].name+'_tooltip']:''),
					'childs':[]});
				if(this.gameDatas.menu[i].childs&&this.gameDatas.menu[i].childs.length)
					{
					for(var k=0, l=this.gameDatas.menu[i].childs.length; k<l; k++)
						{
						this.options.menu[i].childs.push({'label':this.gameLocale['menu_'+this.gameDatas.menu[i].childs[k].name],
							'command':'command:'+this.gameDatas.menu[i].childs[k].command,
							'title':(this.gameLocale['menu_'+this.gameDatas.menu[i].childs[k].name+'_tooltip']?
								this.gameLocale['menu_'+this.gameDatas.menu[i].childs[k].name+'_tooltip']:'')});
						}
					}
				}
			}
		// Drawing window
		this.parent();
		// Hooking window menu
		var tpl ='<div class="box"><p>Loading...</p></div>';
		this.view.innerHTML=tpl;
		this.game=new Game(this.view,'/mpfs'+this.options.path+'/',this.localize.bind(this),this.notice.bind(this));
		// Adding events
		if(this.game&&this.game.resize)
			this.addEvent('resized',this.game.resize.bind(this.game));
		if(this.game&&this.game.pause)
			this.addEvent('unselected',this.game.pause.bind(this.game));
		if(this.game&&this.game.resume)
			this.addEvent('selected',this.game.resume.bind(this.game));
	},
	// Window content
	loadContent : function() {
	},
	renderContent : function() {
		// Drawing window
		this.parent();
		// Hooking window content
		var tpl ='<div class="box"><p>'+this.options.content+'</p></div>';
		this.view.innerHTML=tpl;
	},
	// UI
	doCommand: function(event,params)
		{
		if(this.game[params[0]])
			this.game[params[0]]();
		},
	localize: function() // localeName, replacement, values[]
		{
		var locale=(this.gameLocale[arguments[0]]?this.gameLocale[arguments[0]]:arguments[1]);
		for(var i=arguments.length-1; i>1; i--)
			{
			var index=locale.lastIndexOf('$');
			if(index!==false)
				locale=locale.substring(0,index)+arguments[i]+locale.substring(index+1);
			}
		return locale;
		},
	// Window destruction
	destruct : function() {
		if(this.game.close)
			this.game.close();
		this.app.unregisterCommand('win'+this.id+'-command');
		this.parent();
		}
});