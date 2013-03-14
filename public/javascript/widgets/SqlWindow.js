var SqlWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.content='';
		this.classNames.push('SqlWindow');
		// Initializing the window
		this.parent(desktop,options);
		// Registering commands	
		this.app.registerCommand('win'+this.id+'-execute',this.execute.bind(this));
		},
	// Rendering window
	render : function() {
		// Menu
		this.options.menu[0]={'label':this.locale.execute,'command':'execute','title':this.locale.execute_tx};
		// Drawing window
		this.parent();
		// Putting window content
		//var tpl='<form class="vbox"><textarea id="win'+this.id+'-textarea">'+this.options.content+'</textarea></form>';
		var tpl='<form class="vbox"><textarea id="win'+this.id+'-textarea">'
			+'# Requete de modification des dates de controles\n'
			+'# \n'
			+'# UPDATE controls, equipments, installations SET controls.controlDate="nouvelle date de controle" WHERE controls.equipment=equipments.id AND equipments.installation=installations.id AND (installations.id="id de l installation" OR installations.id="id d une autre installation") AND controls.planDate="date de planif"\n'
			+'# ex :\n'
			+'UPDATE controls, equipments, installations SET controls.controlDate="2012-03-23 12:12:12" WHERE controls.equipment=equipments.id AND equipments.installation=installations.id AND (installations.id=999999 OR installations.id=999999) AND controls.planDate="2012-01-01";'
			+'</textarea></form>';
		this.view.innerHTML=tpl;
		},
	execute: function()
		{
		if($('win'+this.id+'-textarea').value)
			{
			var req=this.app.createRestRequest({'path':'sql.txt','method':'post'});
			req.addEvent('done',this.requestDone.bind(this));
			req.addEvent('error',this.requestError.bind(this));
			req.send($('win'+this.id+'-textarea').value);
			}
		}, 
	requestDone: function(req)
		{
		this.results=null;
		this.app.loadVars(req.xhr.responseText,this);
		var tpl='';
		if(this.results)
			{
			tpl='<table class="border">'
			+'	<tbody>'
			+'		<tr>';
			for(var k=0, l=this.results[0].length; k<l; k++)
				{
				tpl+='			<td><b>'+this.results[0][k].name+'</b></td>';
				}
			tpl+='		</tr>';
			for(var i=0, j=this.results.length; i<j; i++)
				{
				tpl+='		<tr>';
				for(var k=0, l=this.results[i].length; k<l; k++)
					{
					tpl+='			<td>'+this.results[i][k].value+'</td>';
					}
				tpl+='		</tr>';
				}
			tpl+='	</tbody>'
			+'</table><br />'
			+'<u>'+this.locale.count+'</u> '+this.affectedRows+'<br /><u>'+this.locale.request+'</u> '+$('win'+this.id+'-textarea').value;
			}
		else 
			tpl+='<u>'+this.locale.request_empty+'</u> '+this.affectedRows+'<br /><u>'+this.locale.request+'</u>'+$('win'+this.id+'-textarea').value;
		this.app.createWindow('AlertWindow',{'name':this.locale.requestDone_name,'content':tpl,'synchronize':false});
		},
	requestError: function()
		{
		this.app.createWindow('AlertWindow',{'name':this.locale.requestError_name,'content':this.locale.requestError_content+' '+$('win'+this.id+'-textarea').value});
		},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-execute');
		this.parent();
		}
});