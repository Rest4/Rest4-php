var RestQuickTesterWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Setting options
		this.classNames.push('RestQuickTesterWindow');
		// Initializing Window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-request',this.sendRequest.bind(this));
		},
	// Rendering Window
	render : function() {
		if(!this.options.content)
			this.options.content=this.locale.content;
		// Drawing window
		this.parent();
		// Putting window content
		var tpl='<div class="box">'
			+'	<form action="#win'+this.id+'-request" id="tester" enctype="multipart/form-data">'
			+'		<fieldset><legend>'+this.locale.request_title+'</legend>'
			+'			<p class="fieldrow"><label>'+this.locale.request_uri+'</label><input type="text" id="win'+this.id+'-uri"></p>'
			+'			<p class="fieldrow"><label>'+this.locale.request_method+'</label>'
			+'				<select id="win'+this.id+'-method">'
			+'					<option>OPTIONS</option>'
			+'					<option>HEAD</option>'
			+'					<option>GET</option>'
			+'					<option>PUT</option>'
			+'					<option>POST</option>'
			+'					<option>DELETE</option>'
			+'				</select></p>'
			+'			<p class="fieldrow"><label>'+this.locale.request_headers+'</label>'
			+'				<textarea id="win'+this.id+'-headers">Accept:*/*\n'
								+'Accept-Charset:*\n'
								+'Content-Type:text/plain'
			+'				</textarea>'
			+'			</p>'
			+'			<p class="fieldrow">'
			+'				<label> '+this.locale.request_content+'</label>'
			+'				<textarea id="win'+this.id+'-content"></textarea>'
			+'			</p>'
			+'		</fieldset>'
			+'			<p class="fieldrow">'
			+'				<input type="submit" name="pcsend" value="'+this.locale.request_submit+'" />'
			+'			</p>'		
			+'	</form>'
			+'	</div>';
		this.view.innerHTML=tpl;
		},
	sendRequest: function(event)
		{
		var req=this.app.createRestRequest({
			'path':($('win'+this.id+'-uri').value.indexOf('http')!==0?$('win'+this.id+'-uri').value:null),
			'url':($('win'+this.id+'-uri').value.indexOf('http')===0?$('win'+this.id+'-uri').value:null),
			'method':$('win'+this.id+'-method').value,
			'evalScripts':false});
		var headers=$('win'+this.id+'-headers').value.split('\n');
		for(var i=0; i<headers.length; i++)
			{
			req.setHeader(headers[i].split(':')[0].trim(),headers[i].split(':')[1].trim());
			}
		req.addEvent('complete',this.viewResult.bind(this));
		req.send($('win'+this.id+'-content').value);
		},
	viewResult: function(req)
		{
		var headers='';
		if(req.getHeader('Content-Length'))
			headers+='<br />Content-Length: '+req.getHeader('Content-Length');
		if(req.getHeader('Location'))
			headers+='<br />Location: '+req.getHeader('Location');
		if(req.getHeader('Content-Encoding'))
			headers+='<br />Content-Encoding: '+req.getHeader('Content-Encoding');
		if(req.getHeader('Vary'))
			headers+='<br />Vary: '+req.getHeader('Vary');
		var rHeaders='';
		for(var i in req.headers)
			rHeaders+=i+': '+req.headers[i]+'<br />';;
		var tpl='<h1>'+this.locale.request_title+'</h1>';
		tpl+='<p><strong>'+this.locale.request_uri+'</strong>'+req.options.url+'<br />';
		tpl+='<p><strong>'+this.locale.request_method+'</strong>'+req.options.method+'<br />';
		tpl+='<strong>'+this.locale.request_headers+'</strong><br />'+rHeaders+'<br />';
		tpl+='<strong>'+this.locale.request_content+'</strong><br />'+req.options.data+'</p>';
		tpl+='<h1>'+this.locale.result_title+'</h1>';
		tpl+='<p><strong>'+this.locale.result_code+'</strong>'+req.xhr.status+'<br />';
		tpl+='<strong>'+this.locale.result_headers+'</strong>'+headers+'<br />';
		tpl+='<strong>'+this.locale.result_content+'</strong></p><pre>'+(req.xhr.responseText.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'))+'</pre>';
		this.app.createWindow('AlertWindow',{
			'name':this.locale.result_title+' '+req.xhr.status,
			'content': tpl,
			'synchronize':false});
		},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-request');
		this.parent();
		}
});