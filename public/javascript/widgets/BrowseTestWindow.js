var BrowseTestWindow = new Class({
  Extends: BrowseWindow,
  initialize: function(app,options) {
    this.classNames.push('BrowseTestWindow');
    // Default options
    this.options.organizationType = '';

    // Initializing the window
    this.parent(app, options);

    // Registering window commands
    this.app.registerCommand('win'+this.id+'-handleForm',
      this.handleForm.bind(this));
    this.app.registerCommand('win'+this.id+'-testSite',
      this.testSite.bind(this));
  },
  load : function() {
    var url = '/db/' + this.app.database + '/organizations/list.dat'
      + '?field=label&limit=0&orderby=label&dir=asc';
    if(this.options.organizationType) {
      url += '&fieldsearch=idJoinsOrganizationTypesId.id&fieldsearchval='
        + this.options.organizationType + '&fieldsearchop=eq';
    }
    this.addReq(this.app.getLoadDatasReq(url, this.organizations={}));
		this.addReq(this.app.getLoadDatasReq('/db/' + this.app.database + '/users/'
			+ this.app.user.userId + '.dat?field=*', this.user={}));
    this.parent();
  },
  render : function() {
    // Creating form
    var tpl =
        '<form id="win' + this.id + '-handleForm"'
      + '  action="#win' + this.id + '-testSite">';
    if(this.options.mode!='single') {
      tpl+=
        '  <label>' + this.locale.form_organization
      + '      <select name="organization">'
      + '    <option value="-1">' + this.locale.form_organization_default
      + '   </option>';
      for(var i=0, j=this.organizations.entries.length; i<j; i++) {
        tpl+=
        '    <option value="' + this.organizations.entries[i].id + '"'
          +(this.organizations.entries[i].id == this.options.organization?
        '       selected="selected">':'>') + this.organizations.entries[i].label
      + '</option>';
      }
      tpl+=
        '  </select></label>'
    }
    tpl+=
        '  <input type="submit" value="' + this.locale.form_submit + '" />';
      + '</form>';
    this.options.forms.push({
      'tpl':tpl,
      'label':this.locale.menu_filter,
      'title':this.locale.menu_filter_tx,
      'showAtStart':true
    });
    this.parent();
  },
  // Rendering window
  renderContent : function() {
    $('win'+this.id+'-frame').set('src',this.options.url);
  },
  // handleForm for client
  handleForm: function(event) {
    if(event.target.get('name')=='organization') {
      if(event.target.value&&event.target.value!=this.options.organization) {
        this.options.organization=event.target.value;
      }
    }
  },
  // Rendering window
  testSite : function() {
    if(this.options.organization>-1&&this.options.mode!='single') {
      var req=this.app.createRestRequest({
        'path':'db/'+this.app.database+'/users/'+this.app.user.userId+'.dat',
        'method':'patch'
      });
      req.setHeader('Content-Type','text/varstream');
      req.addEvent('done',this.renderContent.bind(this));
      req.send('#text/varstream'+"\n"
        +'entry.organization='+this.options.organization+"\n");
    } else {
      this.loadContent();
    }
  },
  // Destruction
  destruct : function() {
    this.app.unregisterCommand('win'+this.id+'-handleForm');
    this.app.unregisterCommand('win'+this.id+'-testSite');
    var req=this.app.createRestRequest({
      'path':'db/'+this.app.database+'/users/'+this.app.user.userId+'.dat',
      'method':'patch'
    });
    req.setHeader('Content-Type','text/varstream');
    req.send('#text/varstream'+"\n"
      +'entry.organization='+this.user.entry.organization+"\n");
    this.parent();
  }
});
