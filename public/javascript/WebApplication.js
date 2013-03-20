var WebApplication=new Class({
	initialize: function(rootElement)
		{
		// Root element
		this.root=rootElement;
		if(this.root.hasAttribute('data-app-database'))
			this.database=this.root.getAttribute('data-app-database');
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
		// Loading user profile
		this.loadDatas('/users/me.dat?type=restricted',this,this.loaded.bind(this));
		},
	loaded: function()
		{
		if(this.locales['WebApplication']&&this.user&&this.user.groupName)
			{
			// Loading profile script
			if(!window[this.user.groupName.substring(0,1).toUpperCase()+this.user.groupName.substring(1)+'Profile'])
				this.loadScript('/mpfs/public/javascript/profiles/'+this.user.groupName.substring(0,1).toUpperCase()+this.user.groupName.substring(1)+'Profile.js');
			// Running profile script
			if(window[this.user.groupName.substring(0,1).toUpperCase()+this.user.groupName.substring(1)+'Profile'])
				this.profile=new window[this.user.groupName.substring(0,1).toUpperCase()+this.user.groupName.substring(1)+'Profile'](this);
			else
				{
				this.start();
				this.createWindow('AlertWindow',{'content':this.locales['WebApplication']['profile_error']});
				}
			}
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
		// Nothing else to load
		this.root.removeClass('loading');
		// Trying to revive requests
		if(window.localStorage&&window.localStorage['requests'])
			this.reviveRestRequests();
		},
	// Interface elements
	renderInterface: function()
		{
		this.root.addClass('mono');
		if(this.screenType=='small')
			{
			this.menu1rstClass='right';
			var tpl='<div class="loadingbox">'
				+'<h1>'+this.locales['WebApplication'].loading_title+'</h1>'
				+'</div>'
				+'<div class="toolbox">'
				+'		<ul class="toolbar small">'
				+'		<li class="menu"><a href="#showMenu" class="button">'+this.locales['WebApplication'].menu+'</a><ul class="menupopup small mainmenu"></ul></li>'
				+'		<li class="menu"><a href="#showMenu" class="button">'+this.locales['WebApplication'].info+'</a><ul class="menupopup small reverse">'
				+'			<li class="menu flex">'
				+'				<a href="#showMenu" class="button">'+this.locales['WebApplication'].user+' <span class="userlogin">'+this.user.login+'</span> / '+this.locales['WebApplication'].group+' <span class="usergroup">'+this.user.groupName+'</span></a>'
				+'				<ul class="menupopup">'
				+'					<li><a href="#logout" class="button">'+this.locales['WebApplication'].logout+'</a></li>'
				+'				</ul>'
				+'			</li>'
				+'			<li class="menu topright">'
				+'				<a href="#showMenu" class="button">'+this.locales['WebApplication'].mode+'</a>'
				+'				<ul class="menupopup">'
				+'					<li><a href="#switchMode:mono" class="button">'+this.locales['WebApplication'].mode_mono+'</a></li>'
				+'					<li><a href="#switchMode:fullscreen" class="button">'+this.locales['WebApplication'].mode_fullscreen+'</a></li>'
				+'				</ul>'
				+'			</li>'
				+'			<li><a href="#switchStatus" class="button status-indicator">'+this.locales['WebApplication'].status_offline+'</a></li>'
				+'			<li><a href="#switchGps" class="button gps-indicator">'+this.locales['WebApplication'].gps_on+'</a></li>'
				+'		</ul></li>'
				+'		<li class="menu">'
				+'			<a href="#showMenu" class="button">'+this.locales['WebApplication'].windows_menu+'</a>'
				+'			<ul class="menupopup windows-indicator"></ul>'
				+'		</li>'
				+'		<li class="menu">'
				+'			<a href="#showMenu" class="button">'+this.locales['WebApplication'].queue_menu+'</a>'
				+'			<ul class="menupopup queue-indicator"></ul>'
				+'		</li>'
				+'	</ul>'
				+'</div>'
				+'<div class="winbox">'
				+'</div>';
			this.root.innerHTML=tpl;
			// Declaring interface elements
			this.statusIndicator=this.root.getElements('a.status-indicator')[0];
			this.gpsIndicator=this.root.getElements('a.gps-indicator')[0];
			this.winbox=this.root.getElements('.winbox')[0];
			this.windowsMenu=this.root.getElements('ul.windows-indicator')[0];
			this.queueMenu=this.root.getElements('ul.queue-indicator')[0];
			}
		else
			{
			var tpl='<div class="loadingbox">'
				+'<h1>'+this.locales['WebApplication']['loading_title']+'</h1>'
				+'</div>'
				+'<div class="toolbox">'
				+'	<ul class="toolbar small mainmenu">'
				+'	</ul>'
				+'</div>'
				+'<div class="winbox">'
				+'</div>'
				+'<div class="toolbox">'
				+'	<ul class="toolbar small reverse">'
				//+'		<li class="flex"><span class="label">'+this.locales['WebApplication'].user+' <span class="userlogin">'+this.user.login+'</span> / '+this.locales['WebApplication'].group+' <span class="usergroup">'+this.user.groupName+'</span></span></li>'
				+'		<li class="menu topright">'
				+'			<a href="#showMenu" class="button">'+this.locales['WebApplication'].user+' <span class="userlogin">'+this.user.login+'</span> / '+this.locales['WebApplication'].group+' <span class="usergroup">'+this.user.groupName+'</span></a>'
				+'			<ul class="menupopup">'
				+'				<li><a href="#logout" class="button">'+this.locales['WebApplication'].logout+'</a></li>'
				+'			</ul>'
				+'		</li>'
				+'		<li class="menu topright">'
				+'			<a href="#showMenu" class="button">'+this.locales['WebApplication'].mode+'</a>'
				+'			<ul class="menupopup">'
				+'				<li><a href="#switchMode:multi" class="button">'+this.locales['WebApplication'].mode_multi+'</a></li>'
				+'				<li><a href="#switchMode:mono" class="button">'+this.locales['WebApplication'].mode_mono+'</a></li>'
				+'				<li><a href="#switchMode:fullscreen" class="button">'+this.locales['WebApplication'].mode_fullscreen+'</a></li>'
				+'			</ul>'
				+'		</li>'
				+'		<li><a href="#switchStatus" class="button status-indicator">'+this.locales['WebApplication'].status_offline+'</a></li>'
				+'		<li><a href="#switchGps" class="button gps-indicator">'+this.locales['WebApplication'].gps_on+'</a></li>'
				+'		<li class="menu topright">'
				+'			<a href="#showMenu" class="button">'+this.locales['WebApplication'].windows_menu+'</a>'
				+'			<ul class="menupopup windows-indicator"></ul>'
				+'		</li>'
				+'		<li class="menu topright">'
				+'			<a href="#showMenu" class="button">'+this.locales['WebApplication'].queue_menu+'</a>'
				+'			<ul class="menupopup queue-indicator"></ul>'
				+'		</li>'
				+'	</ul>'
				+'</div>';
			this.root.innerHTML=tpl;
			// Declaring interface elements
			this.statusIndicator=this.root.getElements('a.status-indicator')[0];
			this.gpsIndicator=this.root.getElements('a.gps-indicator')[0];
			this.winbox=this.root.getElements('.winbox')[0];
			this.windowsMenu=this.root.getElements('ul.windows-indicator')[0];
			this.queueMenu=this.root.getElements('ul.queue-indicator')[0];
			}
		},
	renderMenu : function ()
		{
		if(this.menu.length)
			{
			var mLevel=1, mNodes=[];
			var tpl='';
			for(var i=0, j=this.menu.length; i<j; i++)
				{
				tpl+='<li class="menu '+this.menu1rstClass+'">'
					+'<a href="#'+(this.menu[i].command?this.menu[i].command:'showMenu')+'" title="'+this.menu[i].title+'" class="button">'
					+this.menu[i].label
					+'</a>';
				if(this.menu[i].childs&&this.menu[i].childs.length)
					{
					tpl+='<ul class="menupopup">';
					for(var k=0, l=this.menu[i].childs.length; k<l; k++)
						{
						tpl+='<li class="menu '+this.menu2ndClass+'">'
							+'<a href="#'+(this.menu[i].childs[k].command?this.menu[i].childs[k].command:'showMenu')+'" title="'+this.menu[i].childs[k].title+'" class="button">'
							+this.menu[i].childs[k].label
							+'</a>';
						if(this.menu[i].childs[k].childs&&this.menu[i].childs[k].childs.length)
							{
							tpl+='<ul class="menupopup">';
							for(var m=0, n=this.menu[i].childs[k].childs.length; m<n; m++)
								{
								tpl+='<li class="menu">'
									+'<a href="#'+(this.menu[i].childs[k].childs[m].command?this.menu[i].childs[k].childs[m].command:'showMenu')+'" title="'+this.menu[i].childs[k].childs[m].title+'" class="button">'
									+this.menu[i].childs[k].childs[m].label
									+'</a>'
									+'</li>';
								}
							tpl+='</ul>';
							}
						tpl+='</li>';
						}
					tpl+='</ul>';
					}
				tpl+='</li>';
				}
			this.root.getElements('ul.mainmenu')[0].innerHTML=tpl;
			}
		else
			{
			this.root.getElements('ul.mainmenu')[0].parentNode.removeChild(this.root.getElements('ul.mainmenu')[0]);
			}
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
		this.debug('command:'+event.target.nodeName);
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
				'url':
					(name.indexOf('Db')!==0||name.indexOf('Table')!==name.length-5?
					'/mpfs/public/lang/fr/'+name+'.lang?mode=merge':
					'/mpfs/db/default,'+this.database+(name.substring(2,3).toLowerCase()+name.substring(3,name.length-5)?'/'+name.substring(2,3).toLowerCase()+name.substring(3,name.length-5):'')+'/fr.lang?mode=merge'),
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
				this.debug('commandTruncated:'+command);
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
			this.debug('Try to do command: '+command);
			// Do command
			return this.doCommand(command,event,params);
			}
		return false;
		},
	doCommand: function(command,event,params)
		{
		if(this.commands[command])
			return this.commands[command](event,params);
		this.debug('Command "'+command+'" does not exist.');
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
	// WINDOWS
	getWindowFromRoot: function(element)
		{
		for(var i=this.windows.length-1; i>=0; i--)
			{
			if(this.windows[i].root==element)
				{
				return this.windows[i];
				}
			}
		return null;
		},
	getParentWindow: function(element)
		{
		// Determining the window in wich the element is
		while(element&&element!=this.root)
			{
			if(element.hasClass('window'))
				return this.getWindowFromRoot(element);
			element=element.parentNode;
			}
		return null;
		},
	createWindow: function(windowType,options)
		{
		if((!this.selectedWindow)||!this.selectedWindow.synchronize)
			{
			if(!windowType)
				windowType='WebWindow';
			if(!options)
				options={};
			this.windowsCounter++;
			if(!window[windowType])
				this.loadScript('/mpfs/public/javascript/widgets/'+windowType+'.js');
			if(window[windowType])
				{
				options.id=this.windowsCounter;
				return new window[windowType](this,options);
				}
			}
		return null;
		},
	addWindow: function(dWindow)
		{
		if(dWindow&&this.windows.indexOf(dWindow)===-1)
			{
			this.windows.push(dWindow);
			this.selectWindow(dWindow);
			this.winbox.appendChild(this.windows[this.windows.length-1].root);
			if(!this.winbox.hasClass('mono'))
				{
				var pos=this.winbox.getPosition();
				dWindow.root.setPosition({'x':pos.x+((this.windowsCounter-1)*20%(document.body.getSize().x-250)),'y':pos.y+((this.windowsCounter-1)*20%(document.body.getSize().y-250))});
				}
			return dWindow;
			}
		return null;
		},
	closeWindow: function(dWindow)
		{
		this.debug('closingWindow:'+dWindow.id);
		/*if(dWindow==this.selectedWindow||(!this.selectedWindow)||!this.selectedWindow.synchronize)
			{*/
			dWindow.root.parentNode.removeChild(dWindow.root);
			this.windows.splice(this.windows.indexOf(dWindow),1);
			if(this.selectedWindow.destruct)
				this.selectedWindow.destruct();
			this.selectedWindow=null;
			this.selectWindow((this.windows.length?this.windows[this.windows.length-1]:null));
		/*	}*/
		this.drawWindowMenu();
		},
	selectWindow: function(dWindow)
		{
		if((!this.selectedWindow)||!this.selectedWindow.synchronize)
			{
			// Unselect selected windows
			for(var i=this.windows.length-1; i>=0; i--)
				{
				if(this.windows[i].root.hasClass('selected'))
					{
					this.windows[i].fireEvent('unselected', this.windows[i]);
					this.windows[i].root.removeClass('selected');
					}
				}
			// Select the window if exists
			if(dWindow)
				{
				dWindow.root.addClass('selected');
				this.windows.splice(this.windows.indexOf(dWindow),1);
				this.windows.push(dWindow);
				this.selectedWindow=dWindow;
				}
			else
				this.selectedWindow=null;
			// Setting z-indexes of windows
			for(var i=this.windows.length-1; i>=0; i--)
				{
				this.windows[i].root.setStyle('z-index',i);
				}
			if(this.selectedWindow)
				this.selectedWindow.fireEvent('selected', this.selectedWindow);
			}
		},
	unSelectWindow: function()
		{
		var w;
		for(var i=this.windows.length-2; i>=0; i--)
			{
			if(!this.windows[i].root.hasClass('reduced'))
				break;
			}
		this.selectWindow(i>=0?this.windows[i]:null);
		},
	drawWindowMenu: function(dWindow)
		{
		// Emptying menu
		while(this.windowsMenu.firstChild)
			this.windowsMenu.removeChild(this.windowsMenu.firstChild);
		// Filling the menu
		for(var i=this.windows.length-1; i>=0; i--)
			{
			var item=document.createElement('li');
			item.appendChild(document.createElement('a'));
			item.firstChild.setAttribute('href','#win'+this.windows[i].id);
			item.firstChild.setAttribute('class','button');
			item.firstChild.textContent=this.windows[i].options.name?this.windows[i].options.name:'#win'+this.windows[i].id;
			this.windowsMenu.appendChild(item);
			}
		},
	// TABS
	selectTab: function(event,params)
		{
		var tab=$(params[0]);
		if(tab)
			{
			for(var i=0, j=tab.parentNode.childNodes.length; i<j; i++)
				{
				if(tab.parentNode.childNodes[i].nodeName=='DIV')
					tab.parentNode.childNodes[i].removeClass('selected');
				}
			tab.addClass('selected');
			}
		},
	// LOGOUT
	logout: function()
		{
		var request=new Request({
			'url':document.location.protocol+'//logout:logout@'+document.location.host+'/app/fr-FR/index.html',
			'async':false,
			'method':'get'});
		request.send();
		document.location.href=document.location.protocol+'//'+document.location.host+'/app/fr-FR/index.html';
		},
	// MODE
	switchMode: function(event, params)
		{
		if(this.screenType!='small'&&params[0]=='multi'&&!this.root.hasClass('multi'))
			{
			this.root.addClass('multi');
			this.root.removeClass('mono');
			this.root.removeClass('fullscreen');
			}
		else if(params[0]=='mono'&&(this.root.hasClass('fullscreen')||!this.root.hasClass('mono')))
			{
			this.root.addClass('mono');
			this.root.removeClass('multi');
			this.root.removeClass('fullscreen');
			}
		else if(params[0]=='fullscreen'&&!this.root.hasClass('fullscreen'))
			{
			this.root.addClass('mono');
			this.root.addClass('fullscreen');
			this.root.removeClass('multi');
			}
		this.windows.each(function(w)
			{
			w.fireEvent('resized', w);
			});
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
	gpsMaximumAge: 0,
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
	// QUEUE
	queuedRestRequests: [],
	restRequestsNum: 0,
	createRestRequest: function(params,revived) {
		var req=new RestRequest({
			'method':params.method,
			'url':(params.path?window.location.protocol+'//'+window.location.host+'/'+params.path:params.url),
			'data':(params.data?params.data:'')});
		// Should add headers here !!
		req.addEvent('retry',this.queueRestRequest.bind(this));
		if(window.localStorage)
			{
			if(!revived)
				req.addEvent('sent',this.storeRestRequest.bind(this));
			else
				{
				req.addEvent('done',this.unstoreRestRequest.bind(this));
				req.addEvent('error',this.unstoreRestRequest.bind(this));
				}
			}
		req.app=this;
		return req;
	},
	queueRestRequest: function(req) {
		req.addEvent('done',this.unqueueRestRequest.bind(this));
		req.addEvent('error',this.unqueueRestRequest.bind(this));
		this.switchStatus(false);
		req.removeEvents('retry');
		if(!this.queuedRestRequests.length)
			this.createWindow('AlertWindow',{'name':this.locales['WebApplication'].retry_title,'content':this.locales['WebApplication'].retry_content});
		this.queuedRestRequests.push(req);
		this.drawQueueMenu();
	},
	unqueueRestRequest: function(req) {
		this.unstoreRestRequest(req);
		this.queuedRestRequests.splice(this.queuedRestRequests.indexOf(req),1);
		this.drawQueueMenu();
	},
	storeRestRequest: function(req) {
		req.removeEvents('sent');
		req.num=++this.restRequestsNum;
		try
			{
			window.localStorage['requests']=(window.localStorage['requests']&&window.localStorage['requests']!=undefined?window.localStorage['requests']:'')+req.num+',';
			window.localStorage['request'+req.num+'url']=req.options.url;
			window.localStorage['request'+req.num+'method']=req.options.method;
			window.localStorage['request'+req.num+'data']=req.options.data;
			window.localStorage['request'+req.num+'headers']=JSON.stringify(req.options.headers);
			}
		catch(e)
			{
			// Maybe add a message
			}
		req.addEvent('done',this.unstoreRestRequest.bind(this));
		req.addEvent('error',this.unstoreRestRequest.bind(this));
	},
	unstoreRestRequest: function(req) {
		window.localStorage['requests']=(window.localStorage['requests']&&window.localStorage['requests']!=undefined?window.localStorage['requests'].replace(new RegExp('(,|^)'+req.num+'(,|$)'),',').replace('([,]+)',','):'');
		delete window.localStorage['request'+req.num+'url'];
		delete window.localStorage['request'+req.num+'method'];
		delete window.localStorage['request'+req.num+'data'];
		delete window.localStorage['request'+req.num+'headers'];
	},
	reviveRestRequests: function() {
		var reqs=window.localStorage['requests'].split(',');
		for(var i=reqs.length-1; i>=0; i--)
			{
			if(reqs[i])
				{
				if(reqs[i]>this.restRequestsNum)
					this.restRequestsNum=reqs[i];
				var req=this.createRestRequest({'url':window.localStorage['request'+reqs[i]+'url'],
					'method':window.localStorage['request'+reqs[i]+'method'],
					'data':(window.localStorage['request'+reqs[i]+'data']?window.localStorage['request'+reqs[i]+'data']:''),
					'headers':(window.localStorage['request'+reqs[i]+'headers']?JSON.parse(window.localStorage['request'+reqs[i]+'headers']):null)
					},true);
				req.num=reqs[i];
				req.send();
				}
			}
	},
	retryRestRequests: function() {
		if(this.onLine&&this.queuedRestRequests.length)
			{
			this.queuedRestRequests.each(function(req) {
				req.send();
				});
			}
	},
	unqueueRequest: function(event, params)
		{
		this.unqueueRestRequest(this.queuedRestRequests[params[0]]);
		},
	drawQueueMenu: function()
		{
		// Emptying menu
		while(this.queueMenu.firstChild)
			this.queueMenu.removeChild(this.queueMenu.firstChild);
		// Filling the menu
		for(var i=this.queuedRestRequests.length-1; i>=0; i--)
			{
			var item=document.createElement('li');
			item.appendChild(document.createElement('a'));
			item.firstChild.setAttribute('href','#unqueueRequest:'+i);
			item.firstChild.setAttribute('class','button');
			item.firstChild.innerHTML=(this.queuedRestRequests[i].label?this.queuedRestRequests[i].label:this.queuedRestRequests[i].options.method+':'+this.queuedRestRequests[i].options.url);
			this.queueMenu.appendChild(item);
			}
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
		if(!(window['AlertWindow']&&this.locales['AlertWindow']))
			alert(message);
		else
			this.createWindow('AlertWindow',{'name':this.locales['WebApplication'].error_title,'content':message});
		},
	// DEBUG
	debug: function(message)
		{
		if(this.debugWindow)
			{
			this.debugWindow.append(message);
			}
		}
});

var ApplicationDetector=new Class({
	initialize: function() {
	var applications=$$('.application');
	applications.each(function (application)
		{
		var appClass=(application.hasAttribute('id')?application.getAttribute('id'):'web');
		appClass=appClass.charAt(0).toUpperCase() + appClass.slice(1);
		new window[appClass+'Application'](application);
		}, this);
	}
});

window.addEvent('domready', function()
	{
    new ApplicationDetector();
	});