var WebWindow=new Class({
	Implements: [Events, Options],
	options: {
		name: '',
		synchronize: false,
		noToolbox: false,
		notClosable: false,
		notResizable: false
	},
	requiredOptions:[],
	classNames:[],
	initialize: function(app,options) {
		this.classNames.push('WebWindow');
		this.app=app;
		// Retrieving options
		for(var i=this.requiredOptions.length-1; i>=0; i--) {
			if(!options[this.requiredOptions[i]]) {
				this.app.error('Cannot find this required option: '
				  + this.requiredOptions[i]);
				return false;
			}
		}
		this.setOptions(options);
		// Properties
		this.id=this.options.id;
		this.root=$(document.createElement('div'));
		this.root.addClass('window');
		this.root.addClass(this.classNames[0]);
		this.root.set('id','win'+options.id);
		this.root.appendChild(document.createElement('div'));
		// Adding events
		this.addEvent('close',this.cancelReqs.bind(this));
		this.addEvent('resized',this.update.bind(this));
		this.addEvent('moved',this.update.bind(this));
		this.addEvent('reduced',this.update.bind(this));
		this.addEvent('maximized',this.update.bind(this));
		// Registering commands
		this.app.registerCommand('win'+this.id,this.pop.bind(this));
		this.app.registerCommand('win'+this.id+'-close', this.close.bind(this));
		this.app.registerCommand('win'+this.id+'-reduce', this.reduce.bind(this));
		this.app.registerCommand('win'+this.id+'-maximize',
		  this.maximize.bind(this));
		// Maps
		if(this.needMaps) {
			this.app.getMaps(this);
		}
		// Loading
		if(!this.waitBeforeLoad) {
			this.load();
		}
	},
	// Load window
	load: function() {
		// Locales
		this.locales={};
		for(var i=this.classNames.length-1; i>=0; i--) {
			this.addReq(this.app.getLoadLocaleReq(this.classNames[i],
				this.localesLoaded.bind(this),false,true));
		}
		this.sendReqs(this.loaded.bind(this),this.loadError.bind(this));
	},
	localesLoaded: function(req) {
		this.locales[req.localeName]=this.app.locales[req.localeName];
	},
	loaded: function(req) {
		this.locale=this.app.locales[this.classNames[0]];
		this.options.menu=[];
		this.options.forms=[];
		this.render();
	},
	// Render window
	render: function() {
		if(!this.options.name) {
			for(var i=0, j=this.classNames.length; i<j; i++) {
				if(this.app.locales[this.classNames[i]].title) {
					this.options.name=this.app.locales[this.classNames[i]].title;
					break;
				}
			}
		}
		// Rendering window
		if(this.options.pack) {
			this.root.addClass('pack');
		}
		var tpl='';
		if(!this.options.noToolbox) {
			tpl+='<div class="toolbox">'
				+'	<ul class="toolbar reverse small main">'
				+'		<li class="flex"><h1><a class="mover" href="#win'+this.id+'-move">'
				+this.options.name+' '+this.id+'</a></h1></li>';
			if(this.options.synchronize) {
				this.synchronize=true;
			} else {
				tpl+='		<li><a href="#win'+this.id+'-reduce" class="button"><span>&#8863;</span></a></li>'
					+'		<li><a href="#win'+this.id+'-maximize" class="button maximize"><span>&#8862;</span></a></li>';
			}
			if(!this.options.notClosable) {
				tpl+='		<li><a href="#win'+this.id+'-close" class="button"><span>&#8864;</span></a></li>';
			}
			if(!this.options.notResizable) {
				tpl+='		<li class="winkit">'
					+'			<a href="#win'+this.id+'-resize-w" class="resize-w"><span></span></a>'
					+'			<a href="#win'+this.id+'-resize-h" class="resize-h"><span></span></a>'
					+'			<a href="#win'+this.id+'-resize-hw" class="resize-hw"><span></span></a>'
					+'		</li>';
			}
			tpl+='	</ul>';
			tpl+='</div>';
		}
		tpl+='<div class="view">'
			+'</div>';
		if(this.options.bottomToolbox) {
			tpl+='<div class="toolbox"></div>'
		}
		this.root.firstChild.innerHTML=tpl;
		if(!this.options.noToolbox)
			this.toolbox=this.root.getElements('div.toolbox')[0];
		if(this.options.bottomToolbox)
			this.bottomToolbox=this.root.getElements('div.toolbox')[(this.options.noToolbox?0:1)];
		this.view=this.root.getElements('div.view')[0];
		this.mover=this.root.getElements('a.mover')[0];
		this.wResizer=this.root.getElements('a.resize-w')[0];
		this.hResizer=this.root.getElements('a.resize-h')[0];
		this.hwResizer=this.root.getElements('a.resize-hw')[0];
		if(this.options.name) {
			this.mover.innerHTML=this.options.name;
		}
		if(this.options.forms&&this.options.forms.length) {
			this.form=document.createElement('div');
			this.form.addClass('formbar');
			this.app.registerCommand('win'+this.id+'-showForm',this.showForm.bind(this));
			for(var i=0, j=this.options.forms.length; i<j; i++) {
				this.options.menu.push({
				  'label':this.options.forms[i].label,
				  'command':'showForm:'+i,
				  'title':this.options.forms[i].title
				});
			}
			if(this.options.forms[0].showAtStart) {
				this.showForm(null,[0,0]);
			}
		}
		if(this.locale.help_content) {
			this.options.menu[this.options.menu.length] = {
			  'label':this.locales['WebWindow'].help_link,
			  'command':'help',
			  'title':this.locales['WebWindow'].help_link_tx
			};
			this.app.registerCommand('win'+this.id+'-help',this.showHelp.bind(this));
		}
		if(this.options.menu&&this.options.menu.length) {
			this.menu=document.createElement('ul');
			this.menu.addClass('toolbar');
			this.menu.addClass('small');
			this.menu.addClass('mainmenu');
			var mLevel=1, mNodes=[];
			var tpl='';
			for(var i=0, j=this.options.menu.length; i<j; i++) {
				tpl+='<li class="menu">';
				if(this.options.menu[i].tpl) {
					tpl+='	<span class="button">'+this.options.menu[i].tpl+'</span>';
				} else {
					tpl+='	<a href="#'+(
					  this.options.menu[i].command
					    ? 'win'+this.id+'-'+this.options.menu[i].command
					    : 'showMenu')
					  +'" title="'+this.options.menu[i].title+'" class="button">'
					  +this.options.menu[i].label+'</a>';
				}
				if(this.options.menu[i].childs&&this.options.menu[i].childs.length) {
					tpl+='	<ul class="menupopup">';
					for(var k=0, l=this.options.menu[i].childs.length; k<l; k++) {
						tpl+='		<li class="menu right">'
							+'			<a href="#'+(this.options.menu[i].childs[k].command
							  ? 'win'+this.id+'-'+this.options.menu[i].childs[k].command
							  : 'showMenu')
							 +'" title="'+this.options.menu[i].childs[k].title
							 +'" class="button">'+this.options.menu[i].childs[k].label+'</a>';
						if(this.options.menu[i].childs[k].childs&&this.options.menu[i].childs[k].childs.length) {
							tpl+='			<ul class="menupopup">';
							for(var m=0, n=this.options.menu[i].childs[k].childs.length; m<n; m++) {
								tpl+='				<li class="menu"><a href="#'+(this.options.menu[i].childs[k].childs[m].command?'win'+this.id+'-'+this.options.menu[i].childs[k].childs[m].command:'showMenu')+'" title="'+this.options.menu[i].childs[k].childs[m].title+'" class="button">'+this.options.menu[i].childs[k].childs[m].label+'</a></li>';
							}
							tpl+='			</ul>';
						}
						tpl+='		</li>';
					}
					tpl+='	</ul>';
				}
				tpl+='</li>';
			}
			this.menu.innerHTML=tpl;
			if(this.options.menu.length>3&&this.app.screenType=='small') {
				var li=document.createElement('li');
				li.addClass('menu');
				li.innerHTML='<a href="#showMenu" class="button">M</a>';
				this.menu.addClass('menupopup');
				li.appendChild(this.menu);
				this.toolbox.getElements('ul')[0].insertBefore(li,this.toolbox.getElements('ul')[0].firstChild)
			} else {
				this.toolbox.appendChild(this.menu);
			}
		}
		this.app.addWindow(this);
		this.loadContent();
		this.app.drawWindowMenu();
	},
	// Load window content
	loadContent: function(req) {
		if(this.renderContent) {
			this.sendReqs(this.renderContent.bind(this),this.loadError.bind(this));
		}
	},
	// Window manipulation
	pop: function() {
		if(this.app.selectedWindow!=this) {
			this.app.selectWindow(this);
			this.root.removeClass('reduced');
			this.update();
		}
	},
	close: function() {
		if(this.app.root.hasClass('fullscreen')) {
			this.app.switchMode(null,new Array('mono'));
		}
		this.fireEvent('close');
		this.app.closeWindow(this);
	},
	maximize: function() {
		if(this.root.hasClass('reduced')) {
			this.root.removeClass('reduced');
			this.root.setStyle('height','');
		} else if(!this.app.root.hasClass('mono')) {
			this.app.switchMode(null,new Array('mono'));
		} else if(!this.app.root.hasClass('fullscreen')) {
			this.app.switchMode(null,new Array('fullscreen'));
		}
		this.fireEvent('maximized', this);
	},
	reduce: function() {
		this.root.addClass('reduced');
		if(this.app.root.hasClass('fullscreen')) {
			this.app.switchMode(null,new Array('mono'));
		} else if(this.app.root.hasClass('mono')) {
			this.app.unSelectWindow(this);
		}
		this.fireEvent('reduced', this);
	},
	update: function() {
		var size=this.root.getSize();
		this.wResizer.setStyle('height',(size.y-10)+'px');
		this.hResizer.setStyle('width',(size.x-10)+'px');
		if(this.noticeElement) {
			this.noticeElement.setAnchoredPosition(this.view,{
			  aHPos:'center',
			  aVPos:15,
			  hPos:'center',
			  vPos:'top'
			});
		}
	},
	move: function(point) {
		point.x=point.x-this.app.mousemoveStartPoint.x;
		point.y=point.y-this.app.mousemoveStartPoint.y;
		this.root.setPosition(point);
	},
	moved: function(point) {
		this.move(point);
		this.fireEvent('moved', this);
	},
	resize: function(point) {
		if(this.app.resizeType.indexOf('w')>=0)
			this.root.setStyle('width',(this.app.resizeStartSize.x+point.x-this.app.mousemoveStartPoint.x)+'px');
		if(this.app.resizeType.indexOf('h')>=0)
			this.root.setStyle('height',(this.app.resizeStartSize.y+point.y-this.app.mousemoveStartPoint.y)+'px');
	},
	resized: function(point) {
		this.resize(point);
		this.fireEvent('resized', this);
		this.app.resizeStartSize=null;
		this.app.resizeType=null;
	},
	// Loadings
	loading: function(state) {
		if(state) {
			this.root.addClass('loading');
		} else {
			this.root.removeClass('loading');
		}
	},
	// Notices
	notice: function(message, options) {
	  options = options || {};
		if(this.hideNoticeDelay) {
			clearTimeout(this.hideNoticeDelay);
		}
		if(!this.noticeElement) {
			this.noticeElement=document.createElement('div');
			this.noticeElement.addClass('notice');
			this.view.parentNode.appendChild(this.noticeElement);
		}
		var p=document.createElement('p');
		p.innerHTML=message;
		this.noticeElement.insertBefore(p, this.noticeElement.firstChild);
		this.noticeElement.addClass('active');
		this.noticeElement.setAnchoredPosition(this.view, {
		  aHPos:'center',
		  aVPos:15,
		  hPos:'center',
		  vPos:'top'
		});
		this.hideNoticeDelay = this.hideNotice.bind(this)
		  .delay(options.delay || 3000);
	},
	hideNotice: function() {
		this.noticeElement.removeClass('active');
		while(this.noticeElement.firstChild) {
			this.noticeElement.removeChild(this.noticeElement.firstChild);
		}
	},
	// Errors
	loadError: function(event) {
		if(this.view) {
			this.view.innerHTML='<div class="box">'
			  +this.locales['WebWindow'].load_error+'</div>';
		} else {
			this.app.error(this.locales['WebWindow'].load_error);
		}
	},
	// Forms
	showForm: function(event, params) {
		if(params[0]!=this.selectedForm) {
			this.selectedForm=params[0];
			this.form.innerHTML=this.options.forms[params[0]].tpl;
			if(!this.form.parentNode) {
				this.root.firstChild.insertBefore(this.form,this.view);
			}
		} else if(this.form.parentNode) {
			this.selectedForm=-1;
			this.root.firstChild.removeChild(this.form);
		}
	},
	// Help
	showHelp: function(event) {
		this.app.createWindow('AlertWindow', {
		  'synchronize':false,
		  'name':this.locales['WebWindow'].help_name,
		  'content':this.locale.help_content
		});
	},
	// Window function synchronization
	syncWindows: function(syncFunction,syncTests) {
		for(var i=this.app.windows.length-1; i>=0; i--) {
			if(this.app.windows[i]!=this
			  && this.app.windows[i].classNames[0]==this.classNames[0]) {
				var syncIt=true;
				if(syncTests) {
					for(var j=syncTests.length-1; j>=0; j--) {
						if(this.app.windows[i].options[syncTests[j].option] != syncTests[j].value) {
							syncIt=false;
						}
					}
				}
				if(syncIt) {
					this.app.windows[i][syncFunction](true);
				}
			}
		}
	},
	// Async loads
	reqs:[],
	errReqs:[],
	cancelReqs: function() {
		for(var i=this.reqs.length-1; i>=0; i--) {
			this.reqs[i].cancel();
		}
		this.reqs=[];
		this.errReqs=[];
		this.loading(false);
		return true;
	},
	addReq: function(req) {
		if(req) {
			this.reqs.push(req);
			return this.reqs.length-1;
		}
		return -1;
	},
	removeReq: function(req) {
		var r=this.reqs.indexOf(req);
		if(r>=0) {
			this.reqs.splice(r,1);
		}
	},
	sendReqs: function(doneCallback,errorCallback,data) {
		this.doneCallback=doneCallback;
		this.errorCallback=errorCallback;
		if(!this.reqs.length) {
			this.doneCallback.delay(1,this);
			this.loading(false);
		} else {
			for(var i=0, j=this.reqs.length; i<j; i++) {
				this.reqs[i].addEvent('complete',this.reqSent.bind(this));
				this.reqs[i].addEvent('error',this.reqError.bind(this));
				this.loading(true);
				if(!this.reqs[i].temporize) {
					this.reqs[i].send(data);
				}
			}
		}
	},
	sendTemporizedReqs: function() {
		for(var i=this.reqs.length-1; i>=0; i--) {
			if(this.reqs[i].temporize) {
  			this.reqs[i].send();
			}
		}
	},
	reqError: function(req) {
		if(!req.canFail) {
			this.errReqs.push(req);
		}
	},
	reqSent: function(req) {
		this.removeReq(req);
		if(!this.reqs.length) {
			this.loading(false);
			if(this.errorCallback&&this.errReqs.length) {
				this.errorCallback();
			} else if(this.doneCallback) {
				this.doneCallback();
			}
		}
	},
	// Destruction
	destruct: function() {
		this.cancelReqs();
		this.app.unregisterCommand('win'+this.id);
		this.app.unregisterCommand('win'+this.id+'-help');
		this.app.unregisterCommand('win'+this.id+'-showForm');
		this.app.unregisterCommand('win'+this.id+'-reduce');
		this.app.unregisterCommand('win'+this.id+'-maximize');
		this.app.unregisterCommand('win'+this.id+'-close');
	}
});
