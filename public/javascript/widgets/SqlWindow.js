var SqlWindow=new Class({
  Extends: WebWindow,
  initialize: function(desktop,options) {
    // Default options
    this.options.query = '';
    this.options.content = '';
    this.classNames.push('SqlWindow');
    // Initializing the window
    this.parent(desktop,options);
    // Registering commands
    this.app.registerCommand('win'+this.id+'-execute',
      this.execute.bind(this));
    this.app.registerCommand('win'+this.id+'-reset',
      this.renderContent.bind(this));
		this.app.registerCommand('win'+this.id+'-loadContent',
		  this.loadContent.bind(this));
		this.app.registerCommand('win'+this.id+'-handleForm',
		  this.handleForm.bind(this));
  },
  // Loading queries template
  load : function() {
		this.addReq(this.app.getLoadDatasReq(
		  '/mpfsi/documents/queries.dat?mode=light',
		  this));
		this.parent();
  },
  // Rendering window
  render : function() {
		// Creating form
		tpl='<form id="win'+this.id+'-handleForm" action="#win'+this.id
				+'-loadContent">'
			+'	<label>'+this.locale.form_query+'<select name="query">'
			+'		<option value="">'+this.locale.form_query_default+'</option>';
		for(var i=0, j=this.files.length; i<j; i++) {
			tpl+='		<option value="'+this.files[i].name+'"'
				+(this.files[i].name==this.options.query?
					' selected="selected"':'')
				+'>'+this.files[i].name+'</option>';
		}
		tpl+='	</select></label>'
			+'	<input type="submit" value="'+this.locale.form_submit+'" />';
			+'</form>';
		this.options.forms.push({
			'tpl':tpl,
			'label':this.locale.menu_queries,
			'command':'loadContent',
			'title':this.locale.menu_queries_tx
		});
    // Menu
    this.options.menu.push({
      'label': this.locale.menu_execute,
      'command': 'execute',
      'title': this.locale.menu_execute_tx
    },{
      'label': this.locale.menu_reset,
      'command': 'reset',
      'title': this.locale.menu_reset_tx
    });
    // Drawing window
    this.parent();
    // Putting window content
    var tpl='<form class="vbox"><textarea id="win'+this.id+'-textarea">'
      +'</textarea></form>';
    this.view.innerHTML=tpl;
  },
  loadContent: function() {
    if(this.options.query) {
      var req=new RestRequest({
        'url': '/mpfs/documents/queries/' + this.options.query,
        'method': 'get'
      });
      req.addEvent('done',this.loadedQuery.bind(this));
      req.send();
      this.addReq(req);
    }
		this.parent();
  },
  loadedQuery: function(req) {
    this.options.content = req.xhr.responseText;
  },
  renderContent: function() {
    $('win'+this.id+'-textarea').value = this.options.content;
  },
  execute: function() {
    if($('win'+this.id+'-textarea').value) {
      var req=this.app.createRestRequest({
        'path':'sql.dat',
        'method':'post'
      });
      req.addEvent('done',this.requestDone.bind(this));
      req.addEvent('error',this.requestError.bind(this));
      req.send($('win'+this.id+'-textarea').value);
    }
  },
  requestDone: function(req) {
    var tpl='';
    this.results=null;
    this.app.loadVars(req.xhr.responseText,this);
    if(this.results) {
      tpl +=
          '<table class="border">'
        + '  <thead>'
        + '    <tr>';
      for(var k=0, l=this.results[0].length; k<l; k++) {
        tpl +=
          '      <td><b>'+this.results[0][k].name+'</b></td>';
      }
      tpl +=
          '    </tr>'
        + '  </thead>'
        + '  <tbody>';
      for(var i=0, j=this.results.length; i<j; i++) {
        tpl +=
          '    <tr>';
        for(var k=0, l=this.results[i].length; k<l; k++) {
          tpl +=
          '      <td>'+this.results[i][k].value+'</td>';
        }
        tpl +=
          '    </tr>';
      }
      tpl +=
        '  </tbody>'
      + '</table><br />'
      + '<u>'+this.locale.success_count+'</u> ' + this.rows + '<br />'
    } else {
      tpl +=
        '<u>'+this.locale.success_empty+'</u> ' + this.rows + '<br />';
    }
    tpl +=
        '<u>'+this.locale.success_request+'</u>'
      +$('win'+this.id+'-textarea').value;
    this.app.createWindow('AlertWindow', {
      'name':this.locale.requestDone_name,
      'content':tpl,
      'synchronize':false
    });
  },
  requestError: function(req) {
    this.app.createWindow('AlertWindow', {
      'name': this.locale.error_title,
      'content': '<strong>' + this.locale.error_content + '</strong><br />'
        + req.xhr.responseText
    });
  },
	// Handle form changes
	handleForm: function(event) {
		if(event.target.get('name')=='query') {
			if(event.target.value&&event.target.value!=this.options.query) {
				this.options.query=event.target.value;
			}
		}
	},
  // Window destruction
  destruct : function() {
    this.app.unregisterCommand('win'+this.id+'-execute');
    this.app.unregisterCommand('win'+this.id+'-reset');
		this.app.unregisterCommand('win'+this.id+'-loadContent');
		this.app.unregisterCommand('win'+this.id+'-handleForm');
    this.parent();
  }
});
