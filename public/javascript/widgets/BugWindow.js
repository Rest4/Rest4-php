var BugWindow=new Class({
	Extends: FormWindow,
	initialize: function(desktop,options) {
		// Default options
		this.classNames.push('BugWindow');
		// Initializing the window
		this.parent(desktop,options);
		// Registering commands	
		this.app.registerCommand('win'+this.id+'-send',this.send.bind(this));
	},
	// Rendering window
	render : function() {
		// Menu
		this.options.menu.push({
		  'label':this.locale.send,
		  'command':'send',
		  'title':this.locale.send_tx
		});
		// Drawing window
		this.parent();
		// Putting window content
		var tpl='<div class="vbox"><img src="'+this.options.capture+'" /></div>';
		this.view.innerHTML=tpl;
	},
	send: function() {
		if($('win'+this.id+'-textarea').value) {
			var req=this.app.createRestRequest({'path':'sql.txt','method':'post'});
			req.addEvent('done',this.requestDone.bind(this));
			req.addEvent('error',this.requestError.bind(this));
			req.send($('win'+this.id+'-textarea').value);
		}
	}, 
	requestDone: function(req) {
		this.results=null;
		this.app.loadVars(req.xhr.responseText,this);
		var tpl='';
		if(this.results) {
			tpl='<table class="border">'
			+'	<tbody>'
			+'		<tr>';
			for(var k=0, l=this.results[0].length; k<l; k++) {
				tpl+='			<td><b>'+this.results[0][k].name+'</b></td>';
			}
			tpl+='		</tr>';
			for(var i=0, j=this.results.length; i<j; i++) {
				tpl+='		<tr>';
				for(var k=0, l=this.results[i].length; k<l; k++) {
					tpl+='			<td>'+this.results[i][k].value+'</td>';
				}
				tpl+='		</tr>';
			}
			tpl+='	</tbody>'
			+'</table><br />'
			+'<u>'+this.locale.count+'</u> '+this.affectedRows+'<br /><u>'+this.locale.request+'</u> '+$('win'+this.id+'-textarea').value;
		} else {
			tpl+='<u>'+this.locale.request_empty+'</u> '+this.affectedRows+'<br /><u>'+this.locale.request+'</u>'+$('win'+this.id+'-textarea').value;
		}
		this.app.createWindow('AlertWindow',{
		  'name':this.locale.requestDone_name,
		  'content':tpl,
		  'synchronize':false
		});
	},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-send');
		this.parent();
	}
});
