var Application=new Class({
	Implements: [Events],
	initialize: function(rootElement)
		{
		// Root element
		this.root=rootElement;
		this.root.addClass('loading');
		// Detecting device specs
		if(window.matchMedia&&(window.matchMedia('(max-width: 650px)').matches
			||window.matchMedia&&window.matchMedia('(handheld)').matches))
			{
			this.screenType='small';
			}
		else
			this.screenType='normal';
		if(('ontouchstart' in window) || (window.DocumentTouch && document instanceof DocumentTouch))
			{
			this.navType='touch';
			}
		else
			this.navType='normal';
		if(navigator.userAgent.match(/android|iphone|ipod|fennec|opera mobi/i))
			{
			this.navType='touch';
			this.screenType='small';
			}
		if(navigator.userAgent.match(/ipad/i))
			{
			this.navType='touch';
			}
		// Adding custom css
		if(this.screenType=='small')
			this.root.addClass('handheld');
		// Menu
		this.menu=[];
		this.menu1rstClass='';
		this.menu2ndClass='right';
		this.overMenuMode=(this.navType!='touch'?true:false);
		this.obstrusiveMenuMode=(this.navType!='touch'?false:true);
		// Launching rest request regular attempts
		this.retryRestRequests.periodical(5000,this);
		// Loading resources (lang, vars ..)
		this.loadLocale('WebApplication',this.loaded.bind(this));
		},
	loaded: function()
		{
		this.start();
		},
	start: function()
		{
		// Initializing necessary elements
		this.renderInterface();
		// Initializing status
		this.switchStatus(this.onLine);
		// Windows
		this.windows=[];
		this.selectedWindow=null;
		this.windowsCounter=0;
		// Start event listeners
		this.initEvents();
		this.renderMenu();
		// Doing post profile changes
		if(this.profile.postChanges)
			this.profile.postChanges();
		// Making hash command
		if(document.location.hash)
			{
			var commands=document.location.hash.substr(1).split('|');
			for(var i=0, j=commands.length; i<j;i++)
				this.goDoCommand(commands[i]);
			}
		// Trying to revive requests
		if(window.localStorage&&window.localStorage['requests'])
			this.reviveRestRequests();
		// Nothing else to load
		this.root.removeClass('loading');
		},
	initEvents: function()
		{
		// Initializing drag n' drop events
		this.moveStartPoint=null;
		this.moveStartSize=null;
		this.root.addEvent('mousedown', this.handleDrag.bind(this), false);
		this.root.addEvent('mousemove', this.handleDragMove.bind(this), false);
		this.root.addEvent('mouseup', this.handleDrop.bind(this), false);
		// Initializing context menu events
		this.root.addEvent('rightclick', this.handleContextualDemand.bind(this), false);
		// Initializing menu and bubble events
		this.root.addEvent('mouseover', this.handleFocus.bind(this), false);
		this.root.addEvent('focus', this.handleFocus.bind(this), true);
		this.root.addEvent('mouseout', this.handleBlur.bind(this), false);
		this.root.addEvent('blur', this.handleBlur.bind(this), true);
		// Status events
		document.addEventListener('online', this.handleStatus.bind(this), false);
		document.addEventListener('offline', this.handleStatus.bind(this), false);
		// Initializing command events
		this.root.addEvent('click', this.handleCommand.bind(this), false);
		this.root.addEvent('doubleclick', this.handleSpecialCommand.bind(this), false);
		this.root.addEvent('submit', this.handleSubmit.bind(this), false);
		// Initializing form fill events
		this.root.addEvent('keyup', this.handleFormInput.bind(this), false);
		this.root.addEvent('input', this.handleFormInput.bind(this), false);
		this.root.addEvent('change', this.handleFormInput.bind(this), false);
		//this.root.addEvent('mouseup', this.handleFormInput.bind(this), false);
		// Initializing resize events
		window.addEvent('resize', this.handleResize.bind(this), false);
		// Initializing exit events
		//window.addEvent('beforeunload', this.handleUnload.bind(this), false);
		window.onbeforeunload=this.handleUnload.bind(this);
		// Initializing application cache events
		if(window.applicationCache)
			window.applicationCache.addEventListener('updateready', this.updateReady.bind(this), false);
		// Initializing commands
		this.registerCommand('logout',this.logout.bind(this));
		this.registerCommand('switchMode',this.switchMode.bind(this));
		this.registerCommand('switchStatus',this.switchStatus.bind(this));
		this.registerCommand('switchGps',this.switchGps.bind(this));
		this.registerCommand('showMenu',this.showMenu.bind(this));
		this.registerCommand('hideMenuItem',this.hideMenuItem.bind(this));
		this.registerCommand('unqueueRequest',this.unqueueRequest.bind(this));
		},
	// EVENTS
	// Status unload
	handleUnload: function(e)
		{
		if (e)
			e.returnValue =this.locales['WebApplication'].back_message;
		// For Safari
		return this.locales['WebApplication'].back_message;
		},
	// Status events
	handleStatus: function()
		{
		this.switchStatus(navigator.onLine);
		},
	// Menu events
	selectedMenuItems:[],
	handleFocus: function(event)
		{
		if(this.overMenuMode)
			{
			this.handleHideMenuEvent(event);
			this.handleShowMenuEvent(event);
			}
		// temporary show the window selected and transparent if on another window than selected
		},
	handleBlur: function(event)
		{
		if(event.target==this.root)
			this.handleDragCancel(event);
		if(this.overMenuMode)
			this.handleHideMenuEvent(event);
		},
	showMenu: function(event)
		{
		this.handleShowMenuEvent(event);
		},
	handleShowMenuEvent: function(event)
		{
		var targetElement=event.target;
		while(targetElement!=this.root&&targetElement.nodeName!='LI'&&!targetElement.hasClass('menu'))
			{
			targetElement=targetElement.parentNode;
			}
		if(targetElement.nodeName=='LI'&&targetElement.hasClass('menu')&&!targetElement.hasClass('selected'))
			{
			var menus;
			menus=targetElement.getElements('ul.menupopup');
			if(menus[0]&&menus[0].childNodes.length)
				{
				targetElement.addClass('selected');
				if(this.obstrusiveMenuMode)
					{
					var li=document.createElement('li');
					var a=document.createElement('a');
					a.addClass('button');
					a.innerHTML=this.locales['WebApplication'].menu_back;
					a.set('title',this.locales['WebApplication'].menu_back_tx);
					a.set('href','#hideMenuItem');
					li.appendChild(a);
					menus[0].appendChild(li);
					menus[0].setPosition({x:0,y:0});
					menus[0].setStyle('width',document.getSize().x+'px');
					menus[0].setStyle('height',document.getSize().y+'px');
					}
				else if(targetElement.hasClass('topright'))
					{
					menus[0].setAnchoredPosition(targetElement,{aHPos:'right',aVPos:'top',hPos:'right',vPos:'bottom'});
					}
				else if(targetElement.hasClass('right'))
					{
					menus[0].setAnchoredPosition(targetElement,{aHPos:'right',aVPos:'top',hPos:'left',vPos:'top'});
					}
				this.selectedMenuItems.push(targetElement);
				}
			}
		},
	handleHideMenuEvent: function(event)
		{
		var targetElement=event.target;
		// Finding if element or one of it's ancestors is inside selected menu items
		var index=this.selectedMenuItems.indexOf(targetElement);
		while(targetElement!=this.root&&index<0)
			{
			targetElement=targetElement.parentNode;
			index=this.selectedMenuItems.indexOf(targetElement);
			}
		// The target element must be deleted only if it's the original event target
		if(targetElement!=event.target)
			{
			if(this.selectedMenuItems[index+1])
				this.hideMenu(this.selectedMenuItems[index+1]);
			}
		},
	hideMenuItem: function()
		{
		this.hideMenu(this.selectedMenuItems[this.selectedMenuItems.length-1]);
		},
	hideMenu: function(targetElement)
		{
		// Removing obsolete menu item
		for(var i=this.selectedMenuItems.length-1; i>=0; i--)
			{
			var item=this.selectedMenuItems.pop();
			if(this.obstrusiveMenuMode)
				{
				var menus;
				menus=item.getElements('ul.menupopup');
				if(menus[0]&&menus[0].lastChild)
					{
					menus[0].removeChild(menus[0].lastChild);
					}
				}
			item.removeClass('selected');
			if(targetElement==item)
				{
				break;
				}
			}
		/*/ Delete last element menu item if it's the initial targetted element
		if(targetElement==event.target&&this.selectedMenuItems.length)
			{
			var item=this.selectedMenuItems.pop();
			if(this.obstrusiveMenuMode)
				{
				var menus;
				menus=item.getElements('ul.menupopup');
				if(menus[0]&&menus[0].lastChild)
					{
					menus[0].removeChild(menus[0].lastChild);
					}
				}
			item.removeClass('selected');
			}*/
		},
	// Drag n' drop events
	handleDragMove: function(event)
		{
		if(this.mousemoveStartPoint)
			{
			if(this.mousemoveFollowCallback)
				this.mousemoveFollowCallback({'x':event.client.x,'y':event.client.y});
			event.preventDefault();
			event.stop();
			}
		},
	handleDrag: function(event)
		{
		// Detecting the concerned window
		var dWindow=this.getParentWindow(event.target);
		if(dWindow)
			{
			// Selecting the window if not
			if(this.selectedWindow!=dWindow)
				this.selectWindow(dWindow);
			var targetElement=event.target;
			while(targetElement!=this.root&&targetElement.nodeName!='A')
				{
				targetElement=targetElement.parentNode;
				}
			if(targetElement.nodeName=='A'&&targetElement.hasAttribute('href')&&targetElement.getAttribute('href').indexOf('#win')===0)
				{
				var command=targetElement.getAttribute('href').substring(1);
				if(command.test(/^win([0-9]+)-move$/))
					{
					;
					if(!this.root.hasClass('multi'))
						{
						this.switchMode(null,new Array('multi'));
						pos=this.winbox.getPosition();
						}
					else
						pos=dWindow.root.getPosition();
					this.mousemoveStartPoint={'x':event.client.x-pos.x,'y':event.client.y-pos.y};
					this.mousemoveFollowCallback=dWindow.move.bind(dWindow);
					this.mousemoveEndCallback=dWindow.moved.bind(dWindow);
					event.preventDefault();
					event.stop();
					}
				// Drag window if on a window label
				// Resize window if on any resize button
				else if(command.test(/^win([0-9]+)-resize-(h|w|hw)$/)&&!this.winbox.hasClass('mono'))
					{
					this.mousemoveStartPoint={'x':event.client.x,'y':event.client.y};
					this.mousemoveFollowCallback=dWindow.resize.bind(dWindow);
					this.mousemoveEndCallback=dWindow.resized.bind(dWindow);
					this.resizeStartSize=dWindow.root.getSize();
					this.resizeType=command.replace(/^win(?:[0-9]+)-resize-(h|w|hw)$/,'$1');
					event.preventDefault();
					event.stop();
					}
				}
			}
		},
	handleDrop: function(event)
		{
		if(this.mousemoveStartPoint)
			{
			if(this.mousemoveEndCallback)
				this.mousemoveEndCallback({'x':event.client.x,'y':event.client.y});
			this.mousemoveStartPoint=null;
			this.mousemoveFollowCallback=null;
			this.mousemoveEndCallback=null;
			event.preventDefault();
			event.stop();
			}
		},
	handleDragCancel: function(event)
		{
		if(this.mousemoveStartPoint)
			{
			if(this.mousemoveEndCallback)
				this.mousemoveEndCallback(this.mousemoveStartPoint);
			this.mousemoveStartPoint=null;
			this.mousemoveFollowCallback=null;
			this.mousemoveEndCallback=null;
			event.preventDefault();
			event.stop();
			}
		},
	// Submit events
	handleSubmit: function(event)
		{
		targetElement=event.target;
		while(targetElement&&targetElement!=this.root&&targetElement.nodeName!='FORM')
			{
			targetElement=targetElement.parentNode;
			}
		if(targetElement.nodeName=='FORM'&&targetElement.hasAttribute('action')&&targetElement.getAttribute('action').indexOf('#')===0)
			{
			event.stop();
			if(targetElement.checkValidity())
				{
				command=targetElement.getAttribute('action').substring(1);
				this.goDoCommand(command,event);
				}
			}
		},
	// Command events
	handleCommand: function(event)
		{
		var targetElement=event.target;
		var command='';
		// Getting the activated command if on an element with a href
		if(targetElement&&targetElement.nodeName=='INPUT'&&targetElement.hasAttribute('type')&&targetElement.getAttribute('type')=='submit'&&targetElement.hasAttribute('formaction')&&targetElement.getAttribute('formaction').indexOf('#')===0)
			{
			command=targetElement.getAttribute('formaction').substring(1);
			event.stop();
			}
		else
			{
			while(targetElement&&targetElement!=this.root&&targetElement.nodeName!='A')
				{
				targetElement=targetElement.parentNode;
				}
			if(targetElement&&targetElement.nodeName=='A'&&targetElement.hasAttribute('href'))
				{
				if(!targetElement.hasClass('disabled'))
					{
					if(targetElement.getAttribute('href').indexOf('#')===0)
						command=targetElement.getAttribute('href').substring(1);
					else
						window.open(targetElement.getAttribute('href'));
					}
				event.target=targetElement;
				event.stop();
				}
			}
		// Getting the form command if on a submit button
		if(!command)
			{
			targetElement=event.target;
			/* Validation should be done when form is submited
			if(targetElement.nodeName=='INPUT'&&targetElement.hasAttribute('type')&&targetElement.getAttribute('type')=='submit'&&!targetElement.disabled)
				{
				while(targetElement&&targetElement!=this.root&&targetElement.nodeName!='FORM')
					{
					targetElement=targetElement.parentNode;
					}
				if(targetElement.nodeName=='FORM'&&targetElement.hasAttribute('action')&&targetElement.getAttribute('action').indexOf('#')===0)
					{
					event.stop();
					if(targetElement.checkValidity())
						{
						command=targetElement.getAttribute('action').substring(1);
						}
					}
				}
			else */
			if(targetElement.nodeName=='INPUT'||targetElement.nodeName=='TEXTAREA'||targetElement.nodeName=='SELECT')
				{
				while(targetElement&&targetElement!=this.root&&targetElement.nodeName!='FORM')
					{
					targetElement=targetElement.parentNode;
					}
				if(targetElement.nodeName=='FORM'&&targetElement.hasAttribute('id'))
					{
					command=targetElement.getAttribute('id');
					}
				}
			}
		if(command)
			this.goDoCommand(command,event);
		},
	handleSpecialCommand: function(event)
		{
		// Mouse middle click
		},
	// Context menu events
	handleContextualDemand: function(event)
		{
		this.handleDragCancel(event);
		// show context menu if exists
		},
	// form input events
	handleFormInput: function(event)
		{
		// Listening to any form input
		var targetElement=event.target;
		var dWindow=this.getParentWindow(targetElement);
		var command='';
		if((targetElement.nodeName=='INPUT'&&((!targetElement.hasAttribute('type'))||targetElement.getAttribute('type')!='submit'))||targetElement.nodeName=='TEXTAREA'||targetElement.nodeName=='SELECT')
				{
				while(targetElement&&targetElement!=this.root&&targetElement.nodeName!='FORM')
					{
					targetElement=targetElement.parentNode;
					}
				if(targetElement.nodeName=='FORM'&&targetElement.hasAttribute('id')&&targetElement.getAttribute('id'))
					{
					command=targetElement.getAttribute('id');
					}
				}
		this.goDoCommand(command,event);
		},
	// Resize events
	handleResize: function(event)
		{
		for(var i=this.windows.length-1; i>=0; i--)
			{
			this.windows[i].fireEvent('resized', this.windows[i]);
			}
		},
	// Application cache events
	updateReady: function(event)
		{
		if (window.applicationCache.status == window.applicationCache.UPDATEREADY)
			{
			window.applicationCache.swapCache();
			this.createWindow('ConfirmWindow',{'name':this.locales['WebApplication'].cache_title,'content':this.locales['WebApplication'].cache_content,'onValidate':function() { window.location.reload(); }});
			}
		},
	// Scripts
	scriptRequest:null,
	loadScript : function(url)
		{
		var request=new Request({
			'url':url,
			'async':false,
			'method':'get'});
		request.send();
		if(request.xhr.status>=200&&this.status<300)
			this.error('Unable to load this script : '+url);
		},
	// Lang
	locales:[],
	getLoadLocaleReq : function(name,callback,sync,noerror)
		{
		if(!this.locales[name])
			{
			var req=new RestRequest({
				'url':'/mpfs/public/lang/fr/'+name+'.lang?mode=merge',
				'async':(!sync?true:false),
				'method':'get'});
			req.addEvent('done',this.localeLoaded.bind(this));
			if(!noerror)
				{
				req.addEvent('error',this.localeLoadError.bind(this));
				req.addEvent('retry',this.localeLoadError.bind(this));
				}
			if(callback)
				req.addEvent('done',callback);
			req.localeName=name;
			return req;
			}
		else if(callback)
			callback({'localeName':name});
		return null;
		},
	loadLocale : function(name,callback,sync,noerror)
		{
		if(!this.locales[name])
			{
			var req=this.getLoadLocaleReq(name,callback,sync,noerror);
			req.send();
			return req;
			}
		else
			{
			if(callback)
				callback({'localeName':name});
			return null;
			}
		},
	localeLoaded : function(req)
		{
		this.locales[req.localeName]={};
		this.loadVars(req.xhr.responseText,this.locales[req.localeName]);
		},
	localeLoadError : function(req)
		{
		this.error('Unable to load this locale : '+req.options.url);
		},
	// Datas
	getLoadDatasReq : function(url,scope,callback,sync)
		{
		var req=new RestRequest({
			'url':url,
			'async':(!sync?true:false),
			'method':'get'});
		req.addEvent('done',this.datasLoaded.bind(this));
		if(callback)
			req.addEvent('done',callback);
		req.scope=scope;
		return req;
		},
	loadDatas : function(url,scope,callback,sync)
		{
		var req=this.getLoadDatasReq(url,scope,callback,sync);
		req.addEvent('error',this.datasLoadError.bind(this));
		req.addEvent('retry',this.datasLoadError.bind(this));
		req.send();
		return req;
		},
	datasLoaded : function(req)
		{
		this.loadVars(req.xhr.responseText,req.scope);
		},
	datasLoadError : function(req)
		{
		this.error('Unable to load those datas : '+req.options.url);
		},
	loadVars : function(cnt,scope)
		{
		var scopes;
		var line, cVar, cNode, cVar2, cNode2, cVal, i, j, x, log='';
		// Parsing content
		for(var i=0,x=cnt.length; i<x; i++)
			{
			// Checking for comment or empty/malformed line
			if(cnt[i]=='#'||cnt[i]=="&"||cnt[i]=="=")
				{
				while(i<x&&cnt[i]!="\n"&&cnt[i]!="\r")
					i++;
				}
			else if(cnt[i]=="\n"||cnt[i]=="\r")
				{
				continue;
				}
			// Beginning new line scan
			log+='New line'+"\n";
			line='';
			cVar=scope;
			cNode='';
			// Scanning left side
			log+='-New var'+"\n";
			if(cnt[i]=='"')
				{
				log+='-New scope back reference';
				if(cnt[i+1]=='.')
					{
					log+=' (immediat)';
					if(scopes.length)
						cVar=scopes[scopes.length-1];
					else
						log+=' (unavailable)';
					i=i+2;
					}
				else if(cnt[i+1]=='-')
					{
						log+=' (-'+parseInt(cnt[i+2])+')';
					if(scopes.length==parseInt(cnt[i+2]))
						{
						for(var j=parseInt(cnt[i+2]); j>0; j--)
							{
							cVar=scopes.pop();
							}
						i=i+4;
						scopes.push(cVar);
						}
					else
						log+=' (unavailable)';
					}
				log+="\n";
				}
			else
				scopes=new Array();
			for(i; i<x; i++)
				{
				if(cnt[i]!='.'&&cnt[i]!='='&&cnt[i]!='&'&&cnt[i]!="\n"&&cnt[i]!="\r")
					cNode+=cnt[i];
				else
					{
					log+='--New node:'+cNode+"\n";
					if(cNode==='')
						{
						// Malformed var name
						log+='---Bad var name'+"\n";
						break;
						}
					else if(cNode=='!')
						{
						log+='---New Array'+"\n";
						if(cVar instanceof Array)
							cVar.length=0;
						else if(cVar instanceof Object)
							for (prop in cVar) { if (cVar.hasOwnProperty(prop)) { delete cVar[prop]; } }
						cNode=0;
						}
					else if(cNode=='+')
						{
						log+='---New Array Element'+"\n";
						cNode=(cVar instanceof Object?cVar.length:0);
						}
					else if(cNode=='*')
						{
						log+='---Current Array Element'+"\n";
						cNode=(cVar instanceof Object?cVar.length-1:0);
						}
					if(cnt[i]=='='||cnt[i]=='&'||cnt[i]=="\n"||cnt[i]=="\r")
						break;
					if(!(cVar[cNode] instanceof Object))
						cVar[cNode]=new Array();
					cVar=cVar[cNode];
					scopes.push(cVar);
					cNode='';
					}
				}
			log+='Scan right side'+"\n";
			if(cNode!=='')
				{
				// Scanning right side
				if(i<x&&cnt[i]=='&'&&cnt[i+1]=='=')
					{
					// Getting linked var
					cVar2=scope;
					cNode2='';
					log+='-Linked var'+"\n";
					for(i=i+2; i<x; i++)
						{
						if(cnt[i]!='.'&&cnt[i]!='='&&cnt[i]!='&'&&cnt[i]!="\n"&&cnt[i]!="\r")
							cNode2+=cnt[i];
						else
							{
							log+='--New node:'+cNode2+"\n";
							if(cNode2==='')
								{
								// Malformed var name
								log+='---Bad var name'+"\n";
								break;
								}
							else if(cNode2=='!')
								{
								log+='---New Array'+"\n";
								cVar2=new Array();
								cNode2=0;
								}
							else if(cNode2=='+')
								{
								log+='---New Array Element'+"\n";
								cNode2=(cVar2 instanceof Object?cVar2.length:0);
								}
							else if(cNode2=='*')
								{
								log+='---Current Array Element'+"\n";
								cNode2=(cVar2 instanceof Object?cVar2.length-1:0);
								}
							if(cnt[i]=='='||cnt[i]=='&'||cnt[i]=="\n"||cnt[i]=="\r")
								break;
							if(!(cVar2[cNode2] instanceof Object))
								cVar2[cNode2]=new Array();
							cVar2=cVar2[cNode2];
							cNode2='';
							}
						if(cNode2)
							cVar[cNode]=cVar2[cNode2];
						}
					}
				else if(i<x&&cnt[i]=='=')
					{
					// Getting var value
					log+='-Valued var'+"\n";
					cVal='';
					for(i=i+1; i<x; i++)
						{
						if(cnt[i]=='\\'&&(cnt[i+1]=="\n"||cnt[i+1]=="\r"))
							{
							log+='--Value continues on the next line'+"\n";
							cVal+="\n";
							i=i+1;
							}
						else if(cnt[i]!="\n"&&cnt[i]!="\r")
							cVal+=cnt[i];
						else
							break;
						}
					log+='--Var value: '+cVal+"\n";
					if(cVal==='false'||cVal==='null')
						{
						cVar[cNode]=false;
						}
					else if(cVal==='true')
						{
						cVar[cNode]=true;
						}
					else
						{
						cVar[cNode]=cVal;
						}
					}
				}
			}
		//console.log(log);
		return log;
		},
	// COMMANDS management
	commands:[],
	commandsNames:[],
	goDoCommand: function(command,event)
		{
		if(command)
			{
			// If is inside a window, ensure the window has been selected before
			var dWindow=this.getParentWindow((event?event.target:null));
			if(dWindow&&((!this.selectedWindow)||this.selectedWindow!=dWindow))
				{
				command='win'+dWindow.id;
				}
			// Getting command params
			var params=command.split(':').splice(1);
			command=command.split(':')[0];
			// Hidding menu before doing command
			if(command!='showMenu'&&command!='hideMenuItem')
				{
				this.hideMenu();
				}
			// Do command
			return this.doCommand(command,event,params);
			}
		return false;
		},
	doCommand: function(command,event,params)
		{
		if(this.commands[command])
			return this.commands[command](event,params);
		return false;
		},
	registerCommand: function(command, commandFunction)
		{
		if(this.commandsNames.indexOf(command)<0)
			this.commandsNames.push(command);
		this.commands[command]=commandFunction;
		},
	unregisterCommand: function(command)
		{
		var c=this.commandsNames.indexOf(command);
		if(c>=0)
			{
			this.commandsNames.splice(c,1);
			this.commands[command]=null;
			}
		},
	unregisterCommands: function()
		{
		for(var i=0; i=arguments.length; i++)
			{
			this.unregisterCommand(arguments[i]);
			}
		},
	// STATUS
	switchStatus: function(online)
		{
		if(online!==false&&online!==true)
			{
			this.onLine=!this.onLine;
			 }
		else if(online)
			this.onLine=true;
		else
			this.onLine=false;
		this.statusIndicator.innerHTML=(this.onLine?this.locales['WebApplication'].status_online:this.locales['WebApplication'].status_offline);
		},
	// GPS
	gpsActivated: true,
	gpsTimeout: 10000,
	gpsMaximumAge: 60000,
	gpsHighAccuracy: true,
	switchGps: function(activated)
		{
		if(activated!==false&&activated!==true)
			{
			this.gpsActivated=!this.gpsActivated;
			 }
		else if(activated)
			this.gpsActivated=false;
		else
			this.gpsActivated=true;
		this.gpsIndicator.innerHTML=(this.gpsActivated?this.locales['WebApplication'].gps_on:this.locales['WebApplication'].gps_off);
		},
	getGpsPosition: function(doneCallback,errorCallback)
		{
		if(this.gpsActivated)
			{
			if(navigator.geolocation)
				{
				navigator.geolocation.getCurrentPosition(doneCallback, (errorCallback?errorCallback:this.gpsError.bind(this)),{'enableHighAccuracy':this.gpsHighAccuracy,'maximumAge':this.gpsMaximumAge, 'timeout':this.gpsTimeout});
				}
			else
				{
				if(errorCallback)
					errorCallback(this.locales['WebApplication'].gps_error_noapi)
				else
					this.error(this.locales['WebApplication'].gps_error_noapi);
				this.switchGps(false);
				}
			}
		},
	gpsError: function(error)
		{
		switch(error.code)
			{
			case error.TIMEOUT:
				this.error(this.locales['WebApplication'].gps_error_timeout);
			break;
			case error.PERMISSION_DENIED:
				this.error(this.locales['WebApplication'].gps_error_permission_denied);
			break;
			case error.POSITION_UNAVAILABLE:
				this.error(this.locales['WebApplication'].gps_error_position_unavailable);
			break;
			case error.UNKNOWN_ERROR:
				this.error(this.locales['WebApplication'].gps_error_unknown_error);
			break;
			}
		this.switchGps(false);
		},
	// MAPS
	mapsEnabled: false,
	loadingMaps: false,
	windowsNeedingMapsCallbacks: [],
	getMaps: function(w,callback)
		{
		if(!(window.google&&window.google.maps&&window.google.maps.Map))
			{
			w.waitBeforeLoad=true;
			if(!callback)
				callback=w.load.bind(w);
			this.windowsNeedingMapsCallbacks.push(callback);
			if(!this.loadingMaps)
				{
				this.loadingMaps=true;
				window.gmapLoaded=this.mapsLoaded.bind(this);
				var s=document.createElement('script');
				s.setAttribute('src','https://maps-api-ssl.google.com/maps/api/js?v=3&sensor=false&callback=gmapLoaded');
				s.setAttribute('type','text/javascript');
				document.body.appendChild(s);
				}
			}
		else
			{
			this.mapsEnabled=true;
			if(callback)
				callback();
			}
		},
	mapsLoaded: function(message)
		{
		this.loadingMaps=false;
		this.mapsEnabled=true;
		while(this.windowsNeedingMapsCallbacks.length)
			{
			(this.windowsNeedingMapsCallbacks.pop())();
			}
		},
	// ERRORS
	error: function(message)
		{
		alert(message);
		}
});