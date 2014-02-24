var DbTableWindow=new Class({
	Extends: DbWindow,
	initialize: function(desktop,options) {
		// Default options
		this.classNames.push('DbTableWindow');
		// Initializing window
		this.parent(desktop,options);
	},
	// Window
	render : function() {
		this.options.name=this.locale.title
		  +' ('+this.options.database+'.'+this.options.table+')';
		// Drawing window
		this.parent();
	},
	// Content
	renderContent: function(req) {
		var tpl=
		    '<div class="box"><table><thead>'
		  + '	<tr>'
		  + '   <th>' + this.locale.list_th_field + '</th>'
		  + '   <th>' + this.locale.list_th_type + '</th>'
		  + '   <th>' + this.locale.list_th_filter + '</th>'
		  + '   <th>links</th>'
		  + '   <th>joins</th>'
		  + '   <th>refss</th>'
		  + ' </tr>'
		  + ' </thead><tbody>'
		  + (this.db.table.fields.map(function(field) {
return  ' <tr>'
      + '   <td>' + (this.dbLocale['fields_' + field.name] || field.name) + ' </td>'
			+ '   <td>' + field.type + '</td>'
			+ '   <td>' + field.filter + '</td>' + (field.linkTo
			? '   <td><a href="#openWindow:DbTable:database:' + this.options.database
			          + ':table:' + field.linkTo.table + '">'
			+ '     ' + field.linkTo.table
			+ '   </a></td>' : '<td></td>') + (field.joins
			? '   <td>' + (field.joins.map(function(join,i) {
return  ''
      + '     <a href="#openWindow:DbTable:database:' + this.options.database
			          + ':table:' + join.table + '">'
			+ '       ' + join.table + '.' + join.name
			+ '     </a>';
    		}.bind(this)).join(''))
    	+ '   </td>' : '<td></td>') + (field.references
			? '   <td>' + (field.references.map(function(ref,i) {
return  ''
      + '     <a href="#openWindow:DbTable:database:' + this.options.database
			          + ':table:' + ref.table + '">'
			+ '       ' + ref.table + '.' + ref.name
			+ '     </a>';
    		}.bind(this)).join(''))
    	+ '   </td>' : '<td></td>')
			+ ' </tr>';
		    }.bind(this)).join(''))
		  + '</tbody></table></div>';
	    console.log(tpl);
		  this.view.innerHTML= tpl;
	},
	// Window destruction
	destruct : function() {
		this.parent();
	}
});
