var GameSpritesWindow=new Class({
	Extends: WebWindow,
	initialize: function(app,options)
		{
		// Default options
		this.options.path='/public/games/tank/sprites';
		this.options.file='animations';
		this.options.tileSizeX=33;
		this.options.tileSizeY=33;
		this.selectedSprite=null;
		this.classNames.push('GameSpritesWindow');
		// Initializing window
		this.parent(app,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-submitForm',this.submitForm.bind(this));
		this.app.registerCommand('win'+this.id+'-handleForm',this.handleForm.bind(this));
		this.app.registerCommand('win'+this.id+'-delete',this.deleteSprite.bind(this));
		this.app.registerCommand('win'+this.id+'-add',this.addSprite.bind(this));
		},
	// Window
	load : function()
		{
		var req=this.app.getLoadDatasReq('/mpfs'+this.options.path+'/'+this.options.file+'.dat',this.datas={});
		req.canFail=true;
		this.addReq(req);
		this.parent();
		},
	render : function()
		{
		if(this.datas.name)
			this.options.name=this.datas.name;
		this.parent();
		},
	// Window content
	loadContent : function()
		{
		this.img=new Image();
		this.img.addEvent('load',this.renderContent.bind(this));
		this.img.setAttribute('src','/mpfs'+this.options.path+'/'+this.options.file+'.png');
		},
	renderContent : function()
		{
		// Drawing window
		if(!this.canvas)
			{
			if(this.datas&&this.datas.sprites&&this.datas.sprites.length)
				this.selectedSprite=this.datas.sprites[this.datas.sprites.length-1];
			else
				this.datas.sprites=[];
			if(!this.datas.tileSizeX)
				this.datas.tileSizeX=this.options.tileSizeX;
			if(!this.datas.tileSizeY)
				this.datas.tileSizeY=this.options.tileSizeY;
			var tpl ='<div class="hbox">'
				+'	<div class="vbox" style="-webkit-box-flex:2;">'
				+'		<div class="box"><form id="win'+this.id+'-handleForm" action="#win'+this.id+'-submitForm"><fieldset>'
				+'			<legend>'+this.locale.form_main_legend+'</legend>'
				+'			<p class="fieldrow">'
				+'				<label for="win'+this.id+'-fname">'+this.locale.form_main_name+'*</label>'
				+'				<input type="text" id="win'+this.id+'-fname" name="name" value="'+(this.datas.name?this.datas.name:'')+'" required="required" />'
				+'			</p>'
				+'			<p class="fieldrow">'
				+'				<label for="win'+this.id+'-fdescription">'+this.locale.form_main_description+'*</label>'
				+'				<input type="text" id="win'+this.id+'-fdescription" name="description" value="'+(this.datas.description?this.datas.description:'')+'" required="required" />'
				+'			</p>'
				+'		</fieldset>'
				+'		<fieldset>'
				+'			<legend>'+this.locale.form_sprites_legend+'</legend>'
				+'			<p class="fieldrow">'
				+'				<label for="win'+this.id+'-fsprite">'+this.locale.form_sprites_sprite+'*</label>'
				+'				<select type="text" id="win'+this.id+'-fsprite" name="sprite" value="'+(this.datas.name?this.datas.name:'')+'" required="required">'
				+'				</select>'
				+'				<input type="submit" formaction="#win'+this.id+'-add" value="+" />'
				+'				<input type="submit" formaction="#win'+this.id+'-delete" value="X" />'
				+'			</p>'
				+'			<p><canvas /></p>'
				+'			<p class="fieldrow">'
				+'				<label for="win'+this.id+'-fspritename">'+this.locale.form_sprites_name+'*</label>'
				+'				<input type="text" id="win'+this.id+'-fspritename" name="spritename" value="'+(this.selectedSprite&&this.selectedSprite.name?this.selectedSprite.name:'')+'" required="required" />'
				+'			</p>'
				+'			<p class="fieldrow">'
				+'				<label for="win'+this.id+'-ftype">'+this.locale.form_sprites_type+'*</label>'
				+'				<select id="win'+this.id+'-ftype" name="type" required="required">'
				+'					<option value="static">'+this.locale.form_sprites_type_options_static+'</option>'
				+'					<option value="animation">'+this.locale.form_sprites_type_options_animation+'</option>'
				+'				</select>'
				+'			</p>'
				+'			<p class="fieldrow">'
				+'				<label for="win'+this.id+'-fsizex">'+this.locale.form_sprites_sizex+'*</label>'
				+'				<input type="number" pace="1" min="0" id="win'+this.id+'-fsizex" name="sizex" value="'+(this.selectedSprite&&this.selectedSprite.sizex?this.selectedSprite.sizex:'1')+'" required="required" />'
				+'			</p>'
				+'			<p class="fieldrow">'
				+'				<label for="win'+this.id+'-fsizey">'+this.locale.form_sprites_sizey+'*</label>'
				+'				<input type="number" pace="1" min="0" id="win'+this.id+'-fsizey" name="sizey" value="'+(this.selectedSprite&&this.selectedSprite.sizey?this.selectedSprite.sizey:'1')+'" required="required" />'
				+'			</p>'
				+'			<p class="fieldrow">'
				+'				<label for="win'+this.id+'-frow">'+this.locale.form_sprites_row+'*</label>'
				+'				<input type="number" pace="1" min="0" id="win'+this.id+'-frow" name="row" value="'+(this.selectedSprite&&this.selectedSprite.row?this.selectedSprite.row:'0')+'" required="required" />'
				+'			</p>'
				+'			<p class="fieldrow">'
				+'				<label for="win'+this.id+'-fpos">'+this.locale.form_sprites_position+'*</label>'
				+'				<input type="number" pace="1" min="0" id="win'+this.id+'-fpos" name="pos" value="'+(this.selectedSprite&&this.selectedSprite.pos?this.selectedSprite.pos:'0')+'" required="required" />'
				+'			</p>'
				+'			<p class="fieldrow">'
				+'				<label for="win'+this.id+'-flength">'+this.locale.form_sprites_length+'*</label>'
				+'				<input type="number" pace="1" min="1" id="win'+this.id+'-flength" name="length" value="'+(this.selectedSprite&&this.selectedSprite.length?this.selectedSprite.length:'1')+'" required="required" />'
				+'			</p>'
				+'			<p class="fieldrow">'
				+'				<label for="win'+this.id+'-fspeed">'+this.locale.form_sprites_speed+'</label>'
				+'				<input type="number" pace="1" min="0" id="win'+this.id+'-fspeed" name="speed" value="'+(this.selectedSprite&&this.selectedSprite.speed?this.selectedSprite.speed:'')+'" />'
				+'			</p>'
				+'		</fieldset>'
				+'		<fieldset>'
				+'			<p class="fieldrow">'
				+'				<input type="submit" value="'+this.locale.form_submit+'" id="win'+this.id+'-submit-button" />'
				+'			</p>'
				+'		</fieldset></form></div>'
				+'	</div><div class="vbox"><div class="box"><p><canvas /></p></div></div></div>';
			this.view.innerHTML=tpl;
			this.canvas=this.view.getElementsByTagName('canvas')[1];
			this.canvas.width=this.img.width;
			this.canvas.height=this.img.height;
			this.context=this.canvas.getContext('2d');
			this.previewCanvas=this.view.getElementsByTagName('canvas')[0];
			this.previewCanvas.width=this.options.tileSizeX;
			this.previewCanvas.height=this.options.tileSizeY;
			this.previewContext=this.previewCanvas.getContext('2d');
			}
		var select=$$('#win'+this.id+'-handleForm select')[0];
		while(select.firstChild!=select.lastChild)
			select.removeChild(select.lastChild);
		if(this.datas&&this.datas.sprites&&this.datas.sprites.length)
			{
			for(var i=0, j=this.datas.sprites.length; i<j; i++)
				{
				var option=document.createElement('option');
				option.setAttribute('value',i);
				option.innerHTML=this.datas.sprites[i].name;
				if(this.selectedSprite==this.datas.sprites[i])
					option.setAttribute('selected','selected');
				select.appendChild(option);
				}
			}
		this.context.clearRect(0,0,this.img.width, this.img.height);
		this.context.drawImage(this.img, 0, 0, this.img.width, this.img.height, 0, 0, this.img.width, this.img.height);
		this.context.fillStyle = 'rgba(0,0,0,0.3)';
		for(var i=1, j=Math.ceil(this.img.width/this.options.tileSizeY); i<j; i++)
			{
			this.context.fillRect((i*this.options.tileSizeY)-1, 0, 2, this.img.height);
			}
		for(var i=1, j=Math.ceil(this.img.height/this.options.tileSizeX); i<j; i++)
			{
			this.context.fillRect(0,(i*this.options.tileSizeX)-1, this.img.width, 2);
			}
		if(this.datas&&this.datas.sprites&&this.datas.sprites.length)
			{
			for(var i=0, j=this.datas.sprites.length; i<j; i++)
				{
				if(this.selectedSprite==this.datas.sprites[i])
					this.context.fillStyle = 'rgba(0,255,0,0.4)';
				else
					this.context.fillStyle = 'rgba(0,0,0,0.2)';
				this.context.fillRect((this.datas.sprites[i].pos*this.options.tileSizeX),(this.datas.sprites[i].row*this.options.tileSizeY), (this.datas.sprites[i].length*this.options.tileSizeX*(this.datas.sprites[i].sizex?this.datas.sprites[i].sizex:1)), this.options.tileSizeY*(this.datas.sprites[i].sizey?this.datas.sprites[i].sizey:1));
				}
			}
		},
	// Form
	submitForm : function(event, params)
		{
		var content='#text/varstream'+"\n"
			+'name='+this.datas.name+"\n"
			+'description='+this.datas.description+"\n"
			+'tileSizeX='+this.datas.tileSizeX+"\n"
			+'tileSizeY='+this.datas.tileSizeY+"\n";
		if(this.datas&&this.datas.sprites&&this.datas.sprites.length)
			{
			for(var i=0, j=this.datas.sprites.length; i<j; i++)
				{
				content+='sprites.'+(i==0?'!':'+')+'.name='+this.datas.sprites[i].name+"\n"
					+'sprites.*.type='+this.datas.sprites[i].type+"\n"
					+'".row='+this.datas.sprites[i].row+"\n"
					+'".pos='+this.datas.sprites[i].pos+"\n"
					+'".length='+this.datas.sprites[i].length+"\n"
					+(this.datas.sprites[i].speed?'".speed='+this.datas.sprites[i].speed+"\n":'')
					+(this.datas.sprites[i].sizex?'".sizex='+this.datas.sprites[i].sizex+"\n":'')
					+(this.datas.sprites[i].sizey?'".sizey='+this.datas.sprites[i].sizey+"\n":'');
				}
			}
		var req=this.app.createRestRequest({
			'path':'fs'+this.options.path+'/'+this.options.file+'.dat?force=yes',
			'method':'put'});
		req.addEvent('done',this.close.bind(this));
		req.send(content);
	},
	handleForm : function(event, params) {
		if(event&&event.target&&event.target.get('name')=='name'&&this.datas.name!=event.target.value)
			{
			this.datas.name=event.target.value;
			}
		else if(event&&event.target&&event.target.get('name')=='description'&&this.datas.description!=event.target.value)
			{
			this.datas.description=event.target.value;
			}
		else if(event&&event.target&&event.target.get('name')=='sprite'&&this.selectedSprite!=this.datas.sprites[event.target.value])
			{
			this.selectSprite(parseInt(event.target.value));
			}
		else if(event&&event.target&&event.target.get('name')=='spritename'&&this.selectedSprite&&this.selectedSprite.name!=event.target.value)
			{
			this.selectedSprite.name=event.target.value;
			this.renderContent();
			}
		else if(event&&event.target&&event.target.get('name')=='type'&&this.selectedSprite&&this.selectedSprite.type!=event.target.value)
			{
			this.selectedSprite.type=event.target.value;
			this.animateSprite();
			this.renderContent();
			}
		else if(event&&event.target&&event.target.get('name')=='sizex'&&this.selectedSprite&&this.selectedSprite.sizex!=event.target.value)
			{
			this.selectedSprite.sizex=event.target.value;
			this.renderContent();
			}
		else if(event&&event.target&&event.target.get('name')=='sizey'&&this.selectedSprite&&this.selectedSprite.sizey!=event.target.value)
			{
			this.selectedSprite.sizey=event.target.value;
			this.renderContent();
			}
		else if(event&&event.target&&event.target.get('name')=='pos'&&this.selectedSprite&&this.selectedSprite.pos!=event.target.value)
			{
			this.selectedSprite.pos=event.target.value;
			this.renderContent();
			}
		else if(event&&event.target&&event.target.get('name')=='row'&&this.selectedSprite&&this.selectedSprite.row!=event.target.value)
			{
			this.selectedSprite.row=event.target.value;
			this.renderContent();
			}
		else if(event&&event.target&&event.target.get('name')=='length'&&this.selectedSprite&&this.selectedSprite.length!=event.target.value)
			{
			this.selectedSprite.length=event.target.value;
			this.renderContent();
			}
		else if(event&&event.target&&event.target.get('name')=='speed'&&this.selectedSprite&&this.selectedSprite.speed!=event.target.value)
			{
			this.selectedSprite.speed=event.target.value;
			this.animateSprite();
			this.renderContent();
			}
		},
	// Sprite animation
	addSprite : function()
		{
		this.datas.sprites.push({'pos':0,'type':'static','length':1,'row':0,'name':this.locale.form_sprites_sprite_new});
		this.selectSprite(this.datas.sprites.length-1);
		},
	deleteSprite : function()
		{
		if(this.selectedSprite)
			{
			this.datas.sprites.splice(this.datas.sprites.indexOf(this.selectedSprite),1);
			this.selectSprite(-1);
			}
		},
	selectSprite : function(i)
		{
		this.selectedSprite=(i>=0?this.datas.sprites[i]:null);
		$('win'+this.id+'-fspritename').value=(this.selectedSprite?this.selectedSprite.name:'new');
		$('win'+this.id+'-ftype').value=(this.selectedSprite?this.selectedSprite.type:'static');
		$('win'+this.id+'-fsizex').value=(this.selectedSprite?this.selectedSprite.sizex:1);
		$('win'+this.id+'-fsizey').value=(this.selectedSprite?this.selectedSprite.sizey:1);
		$('win'+this.id+'-fpos').value=(this.selectedSprite?this.selectedSprite.pos:0);
		$('win'+this.id+'-flength').value=(this.selectedSprite?this.selectedSprite.length:1);
		$('win'+this.id+'-frow').value=(this.selectedSprite?this.selectedSprite.row:0);
		$('win'+this.id+'-fspeed').value=(this.selectedSprite?this.selectedSprite.speed:0);
		this.step=0;
		this.animateSprite();
		this.renderContent();
		},
	animateSprite : function()
		{
		if(this.timer)
			clearTimeout(this.timer);
		this.previewCanvas.width=this.options.tileSizeX*(this.selectedSprite.sizex?this.selectedSprite.sizex:1);
		this.previewCanvas.height=this.options.tileSizeY*(this.selectedSprite.sizey?this.selectedSprite.sizey:1);
//		this.previewContext=this.previewCanvas.getContext('2d');
		this.previewContext.clearRect(0,0,this.options.tileSizeX*(this.selectedSprite.sizex?this.selectedSprite.sizex:1), this.options.tileSizeY*(this.selectedSprite.sizey?this.selectedSprite.sizey:1));
		if(this.selectedSprite)
			{
			this.previewContext.drawImage(this.img,
				(parseInt(this.selectedSprite.pos,10)+(this.step*(this.selectedSprite.sizex?parseInt(this.selectedSprite.sizex,10):1)))*this.options.tileSizeX,
				parseInt(this.selectedSprite.row,10)*this.options.tileSizeY,
				this.options.tileSizeX*(this.selectedSprite.sizex?parseInt(this.selectedSprite.sizex,10):1),
				this.options.tileSizeY*(this.selectedSprite.sizey?parseInt(this.selectedSprite.sizey,10):1),
				0, 0,
				this.options.tileSizeX*(this.selectedSprite.sizex?parseInt(this.selectedSprite.sizex,10):1),
				this.options.tileSizeY*(this.selectedSprite.sizey?parseInt(this.selectedSprite.sizey,10):1));
			if(this.selectedSprite.type=='animation')
				{
				this.step=(this.step+1)%parseInt(this.selectedSprite.length,10);
				this.timer=this.animateSprite.delay(1000/(this.selectedSprite.speed?parseInt(this.selectedSprite.speed):1), this);
				}
			}
		},
	// Window destruction
	destruct : function()
		{
		this.app.unregisterCommand('win'+this.id+'-submitForm');
		this.app.unregisterCommand('win'+this.id+'-handleForm');
		this.app.unregisterCommand('win'+this.id+'-delete');
		this.app.unregisterCommand('win'+this.id+'-add');
		if(this.timer)
			clearTimeout(this.timer);
		this.parent();
		}
});