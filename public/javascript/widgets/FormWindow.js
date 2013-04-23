var FormWindow=new Class({
	Extends: WebWindow,
	initialize: function(desktop,options) {
		// Setting options
		this.classNames.push('FormWindow');
		// Default options
		this.options.output={};
		//this.options.synchronize=true;
		// Initializing window
		this.parent(desktop,options);
		// Registering commands
		this.app.registerCommand('win'+this.id+'-submit',this.submit.bind(this));
		this.app.registerCommand('win'+this.id+'-pickValue',this.pickValue.bind(this));
		this.app.registerCommand('win'+this.id+'-pickDate',this.pickDate.bind(this));
		this.app.registerCommand('win'+this.id+'-pickFile',this.pickFile.bind(this));
		this.app.registerCommand('win'+this.id+'-pickPoint',this.pickPoint.bind(this));
		this.app.registerCommand('win'+this.id+'-handleForm',this.handleForm.bind(this));
		
		
		this.completeValue='';
	},
	// Rendering form
	renderContent : function() {
		var tpl='<div class="box">'
				+'<form id="win'+this.id+'-handleForm" action="#win'+this.id+'-submit" method="post">';
			for(var i=0, j=this.options.fieldsets.length; i<j; i++)
				{
				tpl+='<fieldset>'
					+'	<legend>'+(this.options.fieldsets[i].label?this.options.fieldsets[i].label:this.options.fieldsets[i].name)+'</legend>';
				for(var k=0, l=this.options.fieldsets[i].fields.length; k<l; k++)
					{
					tpl+='<p class="fieldrow">'
						+'	<label for="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'">'+this.options.fieldsets[i].fields[k].label+(this.options.fieldsets[i].fields[k].required?'*':'')+'</label>';
						
					if(this.options.fieldsets[i].fields[k].input=='picker')
						{
						if(this.options.fieldsets[i].fields[k].type=='date')
							{ // input date and multiple attribute don't run as expected for now on all browsers
							tpl+='<input type="'+(this.options.fieldsets[i].fields[k].multiple?'text':'date')+'" id="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'" value="'+(this.options.fieldsets[i].fields[k].defaultValue?this.options.fieldsets[i].fields[k].defaultValue:'')+'" />'
								+'<input type="submit" id="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'button"'
									+' formaction="#win'+this.id+'-pickDate:'+i+':'+k+'" name="'+this.options.fieldsets[i].fields[k].name+'"'
									+' value="'+this.locales['FormWindow'].datepicker_button+'" title="'+this.locales['FormWindow'].datepicker_button_tx+'" />';
							}
						else if(this.options.fieldsets[i].fields[k].type=='file')
							{
							tpl+=(this.options.fieldsets[i].fields[k].defaultUri?'<a href="'+this.options.fieldsets[i].fields[k].defaultUri+'">':'')+'<output id="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'out">'
								+(this.options.fieldsets[i].fields[k].defaultValue?this.options.fieldsets[i].fields[k].defaultValue:this.locales['FormWindow'].filepicker_empty)
								+'</output>'+(this.options.fieldsets[i].fields[k].defaultUri?'</a>':'')
								+'<input type="hidden" id="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'"'
									+(this.options.fieldsets[i].fields[k].defaultValue?' value="'+this.options.fieldsets[i].fields[k].defaultValue+'"':'')+' /> '
								+'<input type="submit" id="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'button"'
									+' formaction="#win'+this.id+'-pickFile:'+i+':'+k+'" name="'+this.options.fieldsets[i].fields[k].name+'"'
									+' value="'+this.locales['FormWindow'].filepicker_button+'" title="'+this.locales['FormWindow'].filepicker_button_tx+'" />';
							}
						else
							{
							tpl+='<output id="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'out">'
								+(this.options.fieldsets[i].fields[k].defaultValue&&this.options.fieldsets[i].fields[k].defaultValue.length?
									(this.options.fieldsets[i].fields[k].multiple?this.options.fieldsets[i].fields[k].defaultValue.length+' '+this.locales['FormWindow'].picker_selected:this.options.fieldsets[i].fields[k].defaultValue)
									:this.locales['FormWindow'].picker_empty)
								+'</output>'
								+'<input type="hidden" id="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'"'
									+(this.options.fieldsets[i].fields[k].defaultValue&&this.options.fieldsets[i].fields[k].defaultValue.length?' value="'+(this.options.fieldsets[i].fields[k].multiple?this.options.fieldsets[i].fields[k].defaultValue.join(','):this.options.fieldsets[i].fields[k].defaultValue)+'"':'')+' /> '
								+'<input type="submit" id="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'button"'
									+' formaction="#win'+this.id+'-pickValue:'+i+':'+k+'" name="'+this.options.fieldsets[i].fields[k].name+'"'
									+' value="'+this.locales['FormWindow'].picker_button+'" title="'+this.locales['FormWindow'].picker_button_tx+'" />';
							}
						}
					else if(this.options.fieldsets[i].fields[k].type=='datetime-local')
						{
						tpl+='<input type="date" id="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'"'
							+(this.options.fieldsets[i].fields[k].defaultValue?' value="'+this.options.fieldsets[i].fields[k].defaultValue.split(' ')[0]+'"':'')
							+(this.options.fieldsets[i].fields[k].placeholder?' placeholder="'+this.options.fieldsets[i].fields[k].placeholder+'"':'')
							+' title="'+(this.options.fieldsets[i].fields[k].title?this.options.fieldsets[i].fields[k].title:'')+'"'
							+(this.options.fieldsets[i].fields[k].required?' required="required"':'')+'>'
							+'	<input type="time" step="1" id="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'-time"'
							+(this.options.fieldsets[i].fields[k].defaultValue?' value="'+this.options.fieldsets[i].fields[k].defaultValue.split(' ')[1]+'"':'')
							+' title="'+(this.options.fieldsets[i].fields[k].title?this.options.fieldsets[i].fields[k].title:'')+'"'
							+(this.options.fieldsets[i].fields[k].required?' required="required"':'')+'>';
						}
					else if(this.options.fieldsets[i].fields[k].input=='completer')
						{
						tpl+='<input type="text" id="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'"'
							+' list="win'+this.id+'-l'+this.options.fieldsets[i].name+'-'+this.options.fieldsets[i].fields[k].name+'"'
							+' value="'+(this.options.fieldsets[i].fields[k].defaultValue?this.options.fieldsets[i].fields[k].defaultValue:'')+'" />'
							+'<datalist id="win'+this.id+'-l'+this.options.fieldsets[i].name+'-'+this.options.fieldsets[i].fields[k].name+'"></datalist>';
						}
					else
						{
						tpl+='		<'+this.options.fieldsets[i].fields[k].input
							+(this.options.fieldsets[i].fields[k].input!='select'?' type="'+this.options.fieldsets[i].fields[k].type+'"':'')
							+' id="win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'"'
							+(this.options.fieldsets[i].fields[k].input!='select'&&this.options.fieldsets[i].fields[k].input!='textarea'&&this.options.fieldsets[i].fields[k].defaultValue?' value="'+this.options.fieldsets[i].fields[k].defaultValue+'"':'')
							+(this.options.fieldsets[i].fields[k].placeholder?' placeholder="'+this.options.fieldsets[i].fields[k].placeholder+'"':'')
							+(this.options.fieldsets[i].fields[k].pattern?' pattern="'+this.options.fieldsets[i].fields[k].pattern+'"':'')
							+(this.options.fieldsets[i].fields[k].min?' min="'+this.options.fieldsets[i].fields[k].min+'"':'')
							+(this.options.fieldsets[i].fields[k].max?' max="'+this.options.fieldsets[i].fields[k].max+'"':'')
							+(this.options.fieldsets[i].fields[k].multiple?' size="3" multiple="'+this.options.fieldsets[i].fields[k].multiple+'"':'')
							+(this.options.fieldsets[i].fields[k].step?' step="'+this.options.fieldsets[i].fields[k].step+'"':'')
							+' title="'+(this.options.fieldsets[i].fields[k].title?this.options.fieldsets[i].fields[k].title:'')+'"'
							+(this.options.fieldsets[i].fields[k].required?' required="required"':'')+'>';
						if(this.options.fieldsets[i].fields[k].options)
							{
							for(var m=0, n=this.options.fieldsets[i].fields[k].options.length; m<n; m++)
								{
								tpl+='<option value="'+this.options.fieldsets[i].fields[k].options[m].value+'"'+(this.options.fieldsets[i].fields[k].options[m].selected||this.options.fieldsets[i].fields[k].options[m].value==this.options.fieldsets[i].fields[k].defaultValue?' selected="selected"':'')+'>'+this.options.fieldsets[i].fields[k].options[m].name+'</option>';
								}
							}
						if(this.options.fieldsets[i].fields[k].input=='textarea'&&this.options.fieldsets[i].fields[k].defaultValue)
							{
							tpl+=this.options.fieldsets[i].fields[k].defaultValue;
							}
						tpl+='</'+this.options.fieldsets[i].fields[k].input+'>';
						}
					tpl+='</p>';
					}
				tpl+='</fieldset>';
				}
				tpl+='<fieldset>'
					+'	<p class="fieldrow">'
					+'		<input type="submit" value="'+this.locales['FormWindow'].form_submit+'" id="win'+this.id+'-submit-button" />'
					+'	</p>'
					+'</fieldset>'
					+'</form></div>';
		this.view.innerHTML=tpl;
		},
	// Form handling
	handleForm : function(event,params) {
		if(event.target.hasAttribute('list'))
			{
			var listId=event.target.getAttribute('list');
			var fieldset=listId.split('-')[1].substring(1);
			var field=listId.split('-')[2];
			var list=$(listId);
			for(var i=0, j=this.options.fieldsets.length; i<j; i++)
				{
				if(this.options.fieldsets[i].name==fieldset)
					{
					for(var k=0, l=this.options.fieldsets[i].fields.length; k<l; k++)
						{
						if(this.options.fieldsets[i].fields[k].name==field&&$('win'+this.id+'-f'+fieldset+field).value&&$('win'+this.id+'-f'+fieldset+field).value!=this.completeValue)
							{
							this.completeValue=$('win'+this.id+'-f'+fieldset+field).value;
							var req=this.app.getLoadDatasReq(this.options.fieldsets[i].fields[k].completeUri.replace('$',encodeURIComponent($('win'+this.id+'-f'+fieldset+field).value)),this.completeResults={});
							req.fieldset=fieldset;
							req.field=field;
							req.completeField=this.options.fieldsets[i].fields[k].completeField;
							req.addEvent('done',this.handleListContent.bind(this));
							req.send();
							break;
							}
						}
					}
				}
			}
		},
	handleListContent : function(req) {
		var list=$('win'+this.id+'-l'+req.fieldset+'-'+req.field);
		while(list.firstChild)
			list.removeChild(list.firstChild);
		if(this.completeResults&&this.completeResults.entries&&this.completeResults.entries.length)
			{
			for(var i=this.completeResults.entries.length-1; i>=0; i--)
				{
				var option=document.createElement('option');
				option.innerHTML=this.completeResults.entries[i][req.completeField];
				option.setAttribute('value',option.innerHTML);
				option.innerHTML=this.completeResults.entries[i].id;
				list.appendChild(option);
				}
			}
		},
	// Form animation
	pickValue : function(event,params) {
		var options=this.options.fieldsets[params[0]].fields[params[1]].options;
		options.onValidate=this.pickedValue.bind(this);
		if(this.options.fieldsets[params[0]].fields[params[1]].multiple)
			options.multiple=true;
		options.output={};
		options.output.values=($('win'+this.id+'-f'+this.options.fieldsets[params[0]].name+this.options.fieldsets[params[0]].fields[params[1]].name).value?$('win'+this.id+'-f'+this.options.fieldsets[params[0]].name+this.options.fieldsets[params[0]].fields[params[1]].name).value.split(','):[]);
		options.output.params=params;
		this.app.createWindow(this.options.fieldsets[params[0]].fields[params[1]].window,options);
		},
	pickedValue : function(event,output) {
		$('win'+this.id+'-f'+this.options.fieldsets[output.params[0]].name+this.options.fieldsets[output.params[0]].fields[output.params[1]].name).value=(output.values.length?output.values.join(','):'');
		$('win'+this.id+'-f'+this.options.fieldsets[output.params[0]].name+this.options.fieldsets[output.params[0]].fields[output.params[1]].name+'out').innerHTML=(output.values.length?(this.options.fieldsets[output.params[0]].fields[output.params[1]].multiple?output.values.length+' '+this.locales['FormWindow'].picker_selected:output.values[0]):this.locales['FormWindow'].picker_empty);
		$('win'+this.id+'-f'+this.options.fieldsets[output.params[0]].name+this.options.fieldsets[output.params[0]].fields[output.params[1]].name+'out').value=(output.values.length?(this.options.fieldsets[output.params[0]].fields[output.params[1]].multiple?output.values.length+' '+this.locales['FormWindow'].picker_selected:output.values[0]):this.locales['FormWindow'].picker_empty);
		},
	pickDate : function(event,params) {
		var options=(this.options.fieldsets[params[0]].fields[params[1]].options?this.options.fieldsets[params[0]].fields[params[1]].options:{});
		options.onValidate=this.pickedDate.bind(this);
		if(this.options.fieldsets[params[0]].fields[params[1]].multiple)
			options.multiple=true;
		options.output={};
		options.output.dates=($('win'+this.id+'-f'+this.options.fieldsets[params[0]].name+this.options.fieldsets[params[0]].fields[params[1]].name).value?$('win'+this.id+'-f'+this.options.fieldsets[params[0]].name+this.options.fieldsets[params[0]].fields[params[1]].name).value.split(','):[]);
		options.output.params=params;
		this.app.createWindow('PromptDateWindow',options);
		},
	pickedDate : function(event,output) {
		$('win'+this.id+'-f'+this.options.fieldsets[output.params[0]].name+this.options.fieldsets[output.params[0]].fields[output.params[1]].name).value=(output.dates.length?output.dates.join(','):'');
		},
	pickPoint : function(event,params) {
		var options=this.options.fieldsets[params[0]].fields[params[1]].options;
		options.onValidate=this.pickedDate.bind(this);
		if(this.options.fieldsets[params[0]].fields[params[1]].multiple)
			options.multiple=true;
		options.output={};
		options.output.dates=($('win'+this.id+'-f'+this.options.fieldsets[params[0]].name+this.options.fieldsets[params[0]].fields[params[1]].name).value?$('win'+this.id+'-f'+this.options.fieldsets[params[0]].name+this.options.fieldsets[params[0]].fields[params[1]].name).value.split(','):[]);
		options.output.params=params;
		this.app.createWindow('PromptDateWindow',options);
		},
	pickedPoint : function(event,output) {
		$('win'+this.id+'-f'+this.options.fieldsets[output.params[0]].name+this.options.fieldsets[output.params[0]].fields[output.params[1]].name).value=(output.dates.length?output.dates.join(','):'');
		$('win'+this.id+'-f'+this.options.fieldsets[output.params[0]].name+this.options.fieldsets[output.params[0]].fields[output.params[1]].name+'out').innerHTML=(output.dates.length?(output.dates.length>1?output.dates.length+' '+this.locales['FormWindow'].datepicker_selected:output.dates[0]):this.locales['FormWindow'].datepicker_empty);
		$('win'+this.id+'-f'+this.options.fieldsets[output.params[0]].name+this.options.fieldsets[output.params[0]].fields[output.params[1]].name+'out').value=(output.dates.length?(output.dates.length>1?output.dates.length+' '+this.locales['FormWindow'].datepicker_selected:output.dates[0]):this.locales['FormWindow'].datepicker_empty);
		},
	pickFile : function(event,params) {
		var options=this.options.fieldsets[params[0]].fields[params[1]].options;
		options.onValidate=this.pickedFile.bind(this);
		if(this.options.fieldsets[params[0]].fields[params[1]].multiple)
			options.multiple=true;
		options.output={};
		options.output.params=params;
		this.app.createWindow('PromptUserFileWindow',options);
		},
	pickedFile : function(event,output) {
		if(this.options.fieldsets[output.params[0]].fields[output.params[1]].defaultUri)
			{
			var p=document.createElement('p');
			p.innerHTML=this.options.fieldsets[output.params[0]].fields[output.params[1]].defaultUri.substring(1);
			var req=this.app.createRestRequest({
				'path':p.textContent,
				'method':'delete'});
			req.send();
			}
		this.options.fieldsets[output.params[0]].fields[output.params[1]].defaultUri='';
		if(!this.files)
			this.files=[];
		this.files[output.params[0]+''+output.params[1]]=output.files;
		$('win'+this.id+'-f'+this.options.fieldsets[output.params[0]].name+this.options.fieldsets[output.params[0]].fields[output.params[1]].name).value=(output.files[0]?output.files[0].name:'');
		$('win'+this.id+'-f'+this.options.fieldsets[output.params[0]].name+this.options.fieldsets[output.params[0]].fields[output.params[1]].name+'out').innerHTML=(output.files&&output.files.length?(output.files.length>1?output.files.length+' '+this.locales['FormWindow'].filepicker_selected:output.files[0].name):this.locales['FormWindow'].filepicker_empty);
		$('win'+this.id+'-f'+this.options.fieldsets[output.params[0]].name+this.options.fieldsets[output.params[0]].fields[output.params[1]].name+'out').value=(output.files&&output.files.length?(output.files.length>1?output.files.length+' '+this.locales['FormWindow'].filepicker_selected:output.files[0].name):this.locales['FormWindow'].filepicker_empty);
		},
	// Form validation
	parseOutput : function()
		{
		this.options.links=[];
		$('win'+this.id+'-submit-button').setAttribute('disabled','disabled');
		$('win'+this.id+'-submit-button').setAttribute('value',this.locales['FormWindow'].form_wait);
		var valid=true;
		for(var i=0, j=this.options.fieldsets.length; i<j&&valid; i++)
			{
			if(!this.options.output[this.options.fieldsets[i].name])
				this.options.output[this.options.fieldsets[i].name]=[];
			for(var k=0, l=this.options.fieldsets[i].fields.length; k<l; k++)
				{
				if(this.options.fieldsets[i].fields[k].input=='completer')
					{
					var value=$('win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name).value;
					var list=$('win'+this.id+'-l'+this.options.fieldsets[i].name+'-'+this.options.fieldsets[i].fields[k].name);
					for(var m=list.childNodes.length-1; m>=0; m--)
						{
						if(value=list.childNodes[m].getAttribute('value'))
							{
							this.options.links[this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name]=list.childNodes[m].innerHTML;
							}
						}
					if(this.options.fieldsets[i].fields[k].required&&!value)
						{
						this.app.createWindow('AlertWindow',{content:this.locales['FormWindow'].field_required+' '+(this.options.fieldsets[i].label?this.options.fieldsets[i].label:this.options.fieldsets[i].name)+' > '+(this.options.fieldsets[i].fields[k].label?this.options.fieldsets[i].fields[k].label:this.options.fieldsets[i].fields[k].name)});
						valid=false; break;
						}
					this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name]=value;
					}
				else if(this.options.fieldsets[i].fields[k].input=='picker')
					{
					if(this.options.fieldsets[i].fields[k].type=='file')
						{
						if(this.files&&this.files[i+''+k]&&this.files[i+''+k].length)
							this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name]=this.files[i+''+k];
						else
							this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name]=[];
						}
					else
						{
						var value=$('win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name).value;
						if(this.options.fieldsets[i].fields[k].required&&!value)
							{
							//$('win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name).setCustomValidity(this.locales['FormWindow'].field_required+' '+(this.options.fieldsets[i].label?this.options.fieldsets[i].label:this.options.fieldsets[i].name)+'>'+(this.options.fieldsets[i].fields[k].label?this.options.fieldsets[i].fields[k].label:this.options.fieldsets[i].fields[k].name));
							//$('win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'out').setCustomValidity(this.locales['FormWindow'].field_required+' '+(this.options.fieldsets[i].label?this.options.fieldsets[i].label:this.options.fieldsets[i].name)+'>'+(this.options.fieldsets[i].fields[k].label?this.options.fieldsets[i].fields[k].label:this.options.fieldsets[i].fields[k].name));
							//$('win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'button').setCustomValidity(this.locales['FormWindow'].field_required+' '+(this.options.fieldsets[i].label?this.options.fieldsets[i].label:this.options.fieldsets[i].name)+'>'+(this.options.fieldsets[i].fields[k].label?this.options.fieldsets[i].fields[k].label:this.options.fieldsets[i].fields[k].name));
							this.app.createWindow('AlertWindow',{content:this.locales['FormWindow'].field_required+' '+(this.options.fieldsets[i].label?this.options.fieldsets[i].label:this.options.fieldsets[i].name)+' > '+(this.options.fieldsets[i].fields[k].label?this.options.fieldsets[i].fields[k].label:this.options.fieldsets[i].fields[k].name)});
							valid=false; break;
							}
						if(this.options.fieldsets[i].fields[k].multiple)
							this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name]=value.split(',');
						else
							this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name]=value;
						}
					}
				else if(this.options.fieldsets[i].fields[k].multiple)
					{
					this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name]=[];
					var selOptions=$('win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name).childNodes;
					for(var m=0, n=selOptions.length; m<n; m++)
						{
						if(selOptions[m].selected)
							{
							this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name].push(selOptions[m].value);
							}
						}
					}
				else if(this.options.fieldsets[i].fields[k].type=='datetime-local')
					{
					this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name]=
						$('win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name).value;
					if(!$('win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'-time').value)
						this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name]+=' 00:00:00';
					else
						this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name]+=
							' '+$('win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'-time').value
							+($('win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name+'-time').value.length==5?
								':00':'');
					if(this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name].length>20)
						this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name]=
							this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name].substr(0,20);
					if(this.options.fieldsets[i].fields[k].required&&this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name].indexOf('0000-00-00')===0)
						{
						this.app.createWindow('AlertWindow',{content:this.locales['FormWindow'].field_required+' '+(this.options.fieldsets[i].label?this.options.fieldsets[i].label:this.options.fieldsets[i].name)+' > '+(this.options.fieldsets[i].fields[k].label?this.options.fieldsets[i].fields[k].label:this.options.fieldsets[i].fields[k].name)});
						valid=false;
						}
					}
				else if(this.options.fieldsets[i].fields[k].type!='checkbox'||$('win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name).checked)
					{
					this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name]=
						$('win'+this.id+'-f'+this.options.fieldsets[i].name+this.options.fieldsets[i].fields[k].name).value;
					if(this.options.fieldsets[i].fields[k].required&&this.options.fieldsets[i].fields[k].type=='date'&&this.options.output[this.options.fieldsets[i].name][this.options.fieldsets[i].fields[k].name]=='0000-00-00')
						{
						this.app.createWindow('AlertWindow',{content:this.locales['FormWindow'].field_required+' '+(this.options.fieldsets[i].label?this.options.fieldsets[i].label:this.options.fieldsets[i].name)+' > '+(this.options.fieldsets[i].fields[k].label?this.options.fieldsets[i].fields[k].label:this.options.fieldsets[i].fields[k].name)});
						valid=false;
						}
					}
				}
			}
		if(!valid)
			{
			$('win'+this.id+'-submit-button').removeAttribute('disabled');
			$('win'+this.id+'-submit-button').setAttribute('value',this.locales['FormWindow'].form_submit);
			}
		return valid;
		},
	submit: function(event)
		{
		if(this.parseOutput())
			{
			this.close();
			this.fireEvent('submit', [event, this.options.output]);
			}
		},
	// Window destruction
	destruct : function() {
		this.app.unregisterCommand('win'+this.id+'-submit');
		this.app.unregisterCommand('win'+this.id+'-pickValue');
		this.app.unregisterCommand('win'+this.id+'-pickDate');
		this.app.unregisterCommand('win'+this.id+'-pickFile');
		this.app.unregisterCommand('win'+this.id+'-pickPoint');
		this.app.unregisterCommand('win'+this.id+'-handleForm');
		this.parent();
		}
});