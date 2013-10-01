var DbEntryDeleteWindow=new Class({
	Extends: ConfirmWindow,
	initialize: function(desktop,options) {
		// Default options
		this.options.database='';
		this.options.table='';
		this.options.entryId='';
		// Custom option
		if(!options) {
			options={};
		}
		options.enabled=false;
		// Locale/Class name
		this.classNames.push('DbEntryDeleteWindow');
		// Required options
		this.requiredOptions.push('database','table','entryId');
		// Initializing window
		this.parent(desktop,options);
		},
	// Window
	load : function() {
		// Trying to load table locale
		var req=this.app.getLoadLocaleReq(
			'Db'+this.options.table.substring(0,1).toUpperCase()
			+this.options.table.substring(1)+'Table',null,false,true);
		if(req) {
			req.canFail=true;
			this.addReq(req);
		}
		// Getting table schema
		this.addReq(this.app.getLoadDatasReq('/db/'+this.options.database+'/'
			+this.options.table+'.dat',this.db={}));
		this.parent();
	},
	// Content
	loadContent: function()	{
		var uri='/db/'+this.options.database+'/'+this.options.table
			+'/'+this.options.entryId+'.dat?field=*';
		var joinNames=[];
		if(this.db.table.constraintFields) {
			this.db.table.constraintFields.forEach(function(field) {
				if(field.joins) {
					field.joins.forEach(function(join) {
						joinNames.push(join.name);
					});
				}
				if(field.references) {
					field.references.forEach(function(ref) {
						joinNames.push(ref.name);
					});
				}
			}.bind(this));
		}
		if(joinNames.length) {
			joinNames.sort(function(a,b) {
				return (a===b?0:(a<b?-1:1));
			});
			uri+='&field='+joinNames.join('.*&field=')+'.id';
		}
		this.addReq(this.app.getLoadDatasReq(uri,this.db));
		this.addReq(this.app.getLoadDatasReq(uri,this));
		this.parent();
		},
	renderContent : function() {
		var restricts=[], cascades=[], tpl;
		if(this.db.table.constraintFields) {
			this.db.table.constraintFields.forEach(function(field) {
				if(field.joins) {
					field.joins.forEach(function(join) {
						if('restrict'===join.onDelete) {
							if(this.entry[join.name]&&this.entry[join.name].length) {
								restricts.push({
									'type': 'join',
									'field': field,
									'constraint': join,
									'length': this.entry[join.name].length
								});
							}
						}
					}.bind(this));
				}
				if(field.references) {
					field.references.forEach(function(ref) {
						if('restrict'===ref.onDelete) {
							if(this.entry[ref.name]&&this.entry[ref.name].length) {
								restricts.push({
									'type': 'ref',
									'field': field,
									'constraint': ref,
									'length': this.entry[ref.name].length
								});
							}
						}
					}.bind(this));
				}
			}.bind(this));
		}
		if(restricts.length) {
			tpl =
					'<div class="box">'
				+ '	<p>'+this.locale.restrict_title+'</p>'
				+ '	<ul>';
			restricts.forEach(function(restrict) {
				tpl+=
					'		<li><a href="#openWindow:DbEntries:database:'
						+ this.options.database+':table:'+this.options.table
						+ ':filterwith:'+'">'
						+ restrict.length+' '+this.locale['restrict_'+restrict.type]
						+ ' ' + restrict.constraint.name
					'		</a></li>';
			}.bind(this));
			tpl+=
				+ '</div>';
		} else {
			tpl ='<div class="box"><p>'+this.locale.content
				+' ('+(this.entry.label||this.entry.id)+').</p></div>';
			this.setValidationState(true);
		}
		this.view.innerHTML=tpl;
	},
	// Commands
	validateDocument: function(event) {
		var req=this.app.createRestRequest({
			'path':'db/'+this.options.database+'/'+this.options.table
				+'/'+this.options.entryId+'.dat',
			'method':'delete'});
		req.addEvent('complete',this.deleteCompleted.bind(this));
		req.send();
	},
	deleteCompleted: function(req) {
		if(req.status==410) {
			this.fireEvent('done', [event, this.options.output]);
		} else {
			this.fireEvent('error', [event, this.options.output]);
		}
		this.close();
	}
});
