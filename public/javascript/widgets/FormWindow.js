var FormWindow = new Class({
  Extends: WebWindow,
  initialize: function (desktop, options) {
    // Default options
    this.classNames.push('FormWindow');
    this.options.output = {};
    // Initializing window
    this.parent(desktop, options);
    // Registering commands
    this.app.registerCommand('win' + this.id + '-submit',
      this.submit.bind(this));
    this.app.registerCommand('win' + this.id + '-pickValue',
      this.pickValue.bind(this));
    this.app.registerCommand('win' + this.id + '-pickDate',
      this.pickDate.bind(this));
    this.app.registerCommand('win' + this.id + '-pickFile',
      this.pickFile.bind(this));
    this.app.registerCommand('win' + this.id + '-pickPoint',
      this.pickPoint.bind(this));
    this.app.registerCommand('win' + this.id + '-handleForm',
      this.handleForm.bind(this));
    this.completeValue = '';
  },
  // Rendering form
  renderContent: function () {
    var tpl =
      '<div class="box"><form id="win' + this.id + '-handleForm"'
    + '  action="#win' + this.id + '-submit" method="post">';
    this.options.fieldsets.forEach(function(curFieldset, i) {
      tpl +=
      ' <fieldset>'
    + '	  <legend>' + (curFieldset.label  || curFieldset.name) + '</legend>';
      curFieldset.fields.forEach(function(curField, k) {
        tpl +=
      '   <p class="fieldrow">' + ('hidden' != curField.type ?
      '	    <label for="win' + this.id + '-f' + curFieldset.name + curField.name + '">'
    + '       ' + curField.label + (curField.required ? '*' : '')
    + '     </label>' : '');
        if(curField.type == 'hidden') {
          tpl +=
      '		  <input type="hidden" name="' + curField.name + '"'
    + '       id="win' + this.id + '-f' + curFieldset.name + curField.name + '"'
          + (curField.defaultValue ?
      '       value="' + curField.defaultValue + '"' : '') + ' />'
        } else if(curField.input == 'picker') {
          // input date and multiple attribute don't run as expected for now on all browsers
          if(curField.type == 'date') {
            tpl +=
      '     <input type="' + (curField.multiple ? 'text' : 'date') + '"'
    + '       id="win' + this.id + '-f' + curFieldset.name + curField.name + '"'
    + '       value="' + (curField.defaultValue ? curField.defaultValue : '') + '" />'
    + '     <input type="submit"'
    + '       id="win' + this.id + '-f' + curFieldset.name + curField.name + 'button"'
    + '       formaction="#win' + this.id + '-pickDate:' + i + ':' + k + '"'
    + '       name="' + curField.name + '"'
    + '       value="' + this.locales['FormWindow'].datepicker_button + '"'
    + '       title="' + this.locales['FormWindow'].datepicker_button_tx + '" />';
          } else if(curField.type == 'file') {
            tpl += (curField.defaultUri ?
      '     <a href="' + curField.defaultUri + '">' : '')
    + '     <output id="win' + this.id + '-f' + curFieldset.name + curField.name + 'out">'
    + '       ' + (curField.defaultValue || this.locales['FormWindow'].filepicker_empty)
    + '     </output>' + (curField.defaultUri ?
      '     </a>' : '')
    + '     <input type="hidden"'
    + '       id="win' + this.id + '-f' + curFieldset.name + curField.name + '"'
              + (curField.defaultValue ?
      '       value="' + curField.defaultValue + '"' : '') + ' /> '
    + '     <input type="submit"'
    + '       id="win' + this.id + '-f' + curFieldset.name + curField.name + 'button"'
    + '       formaction="#win' + this.id + '-pickFile:' + i + ':' + k + '"'
    + '       name="' + curField.name + '"'
    + '       value="' + this.locales['FormWindow'].filepicker_button + '"'
    + '       title="' + this.locales['FormWindow'].filepicker_button_tx + '" />';
          } else {
            tpl += 
      '     <input type="' + (curField.multiple ? 'text' : 'number') + '"'
    + '       id="win' + this.id + '-f' + curFieldset.name + curField.name + '"'
              + (curField.defaultValue && curField.defaultValue.length ?
      '       value="' + (curField.multiple ? curField.defaultValue.join(',') : curField.defaultValue) + '"' : '') + ' /> '
    + '     <input type="submit" id="win' + this.id + '-f' + curFieldset.name + curField.name + 'button"'
    + '       formaction="#win' + this.id + '-pickValue:' + i + ':' + k + '"'
    + '       name="' + curField.name + '"'
    + '       value="' + this.locales['FormWindow'].picker_button + '"'
    + '       title="' + this.locales['FormWindow'].picker_button_tx + '" />';
          }
        } else if(curField.type == 'datetime-local') {
          tpl +=
      '     <input type="date"'
    + '       id="win' + this.id + '-f' + curFieldset.name + curField.name + '"'
            + (curField.defaultValue ?
      '       value="' + curField.defaultValue.split(' ')[0] + '"' : '')
            + (curField.placeholder ?
      '       placeholder="' + curField.placeholder + '"' : '')
    + '       title="' + (curField.title ? curField.title : '') + '"'
            + (curField.required ?
      '       required="required"' : '') + '>'
    + '     <input type="time" step="1"'
    + '       id="win' + this.id + '-f' + curFieldset.name + curField.name + '-time"'
            + (curField.defaultValue ?
      '       value="' + curField.defaultValue.split(' ')[1] + '"' : '')
    + '       title="' + (curField.title ? curField.title : '') + '"'
            + (curField.required ?
      '       required="required"' : '') + '>';
        } else if(curField.input == 'completer') {
          tpl +=
      '     <input type="text"'
    + '     id="win' + this.id + '-f' + curFieldset.name + curField.name + '"'
    + '     list="win' + this.id + '-l' + curFieldset.name + '-' + curField.name + '"'
    + '     value="' + (curField.defaultValue ? curField.defaultValue : '') + '" />'
    + '   <datalist id="win' + this.id + '-l' + curFieldset.name + '-' + curField.name + '"></datalist>';
        } else {
          tpl +=
      '		<' + curField.input + (curField.input != 'select' ?
      '     type="' + curField.type + '"' : '')
    + '     id="win' + this.id + '-f' + curFieldset.name + curField.name + '"'
          + (curField.input != 'select' && curField.input != 'textarea' && curField.defaultValue ?
      '     value="' + curField.defaultValue + '"' : '') + (curField.placeholder ?
      '     placeholder="' + curField.placeholder + '"' : '') + (curField.pattern ?
      '     pattern="' + curField.pattern + '"' : '') + (curField.min ?
      '     min="' + curField.min + '"' : '') + (curField.max ?
      '     max="' + curField.max + '"' : '') + (curField.multiple ?
      '     size="3" multiple="' + curField.multiple + '"' : '') + (curField.step ?
      '     step="' + curField.step + '"' : '')
    + '     title="' + (curField.title ? curField.title : '') + '"'
          + (curField.required ?
      '     required="required"' : '') + '>' + (curField.input == 'select' && !curField.required
    ? '     <option value="">'+this.locales['FormWindow'].select_empty+'</option>' : '');
          curField.options && curField.options.forEach(function (option) {
              tpl +=
      '     <option value="' + option.value + '"'
                + (option.selected || option.value == curField.defaultValue ?
      '       selected="selected"' : '') + '>' + option.name + '</option>';
          });
          if(curField.input == 'textarea' && curField.defaultValue) {
            tpl += curField.defaultValue;
          }
          tpl +=
      '</' + curField.input + '>';
        }
        tpl +=
      ' </p>';
      }.bind(this));
      tpl +=
      '</fieldset>';
    }.bind(this));
    tpl +=
      ' <fieldset>'
    + ' 	<p class="fieldrow">'
    + ' 		<input type="submit"'
    + '       value="' + this.locales['FormWindow'].form_submit + '"'
    + '       id="win' + this.id + '-submit-button" />'
    + ' 	</p>'
    + ' </fieldset>'
    + '</form></div>';
    this.view.innerHTML = tpl;
  },
  // Form handling
  handleForm: function (event, params) {
    if(event.target.hasAttribute('list')) {
      var listId = event.target.getAttribute('list');
      var fieldset = listId.split('-')[1].substring(1);
      var field = listId.split('-')[2];
      var list = $(listId);
      this.options.fieldsets.some(function (curFieldset) {
        if(curFieldset.name == fieldset) {
          return curFieldset.fields.some(function (curField) {
            if(curField.name == field
              && $('win' + this.id + '-f' + fieldset + field).value
              && $('win' + this.id + '-f' + fieldset + field).value != this.completeValue) {
              this.completeValue = $('win' + this.id + '-f' + fieldset + field).value;
              var req = this.app.getLoadDatasReq(
                curField.completeUri.replace('$', encodeURIComponent(
                  $('win' + this.id + '-f' + fieldset + field).value)),
                this.completeResults = {});
              req.fieldset = fieldset;
              req.field = field;
              req.completeField = curField.completeField;
              req.addEvent('done', this.handleListContent.bind(this));
              req.send();
              return true;
            }
          }.bind(this));
        }
      }.bind(this));
    }
  },
  handleListContent: function (req) {
    var list = $('win' + this.id + '-l' + req.fieldset + '-' + req.field);
    while(list.firstChild)
      list.removeChild(list.firstChild);
    if(this.completeResults && this.completeResults.entries
      && this.completeResults.entries.length) {
      for (var i = this.completeResults.entries.length - 1; i >= 0; i--) {
        var option = document.createElement('option');
        option.innerHTML = this.completeResults.entries[i][req.completeField];
        option.setAttribute('value', option.innerHTML);
        option.innerHTML = this.completeResults.entries[i].id;
        list.appendChild(option);
      }
    }
  },
  // Form animation
  pickValue: function (event, params) {
    var curFieldset = this.options.fieldsets[params[0]]
      , curField = curFieldset.fields[params[1]]
      , options = Object.clone(curField.options)
    ;
    options.onValidate = this.pickedValue.bind(this);
    if(curField.multiple) {
      options.multiple = true;
    }
    options.output = {};
    options.output.values = 
      $('win' + this.id + '-f' + curFieldset.name + curField.name).value ?
      $('win' + this.id + '-f' + curFieldset.name + curField.name).value.split(',') :
      [];
    options.output.params = params;
    this.app.createWindow(curField.window, options);
  },
  pickedValue: function (event, output) {
    var curFieldset = this.options.fieldsets[output.params[0]]
      , curField = curFieldset.fields[output.params[1]]
    ;
    $('win' + this.id + '-f' + curFieldset.name + curField.name).value =
      (output.values.length ? output.values.join(',') : '');
  },
  pickDate: function (event, params) {
    var curFieldset = this.options.fieldsets[params[0]]
      , curField = curFieldset.fields[params[1]]
      , options = Object.clone(curField.options)
    ;
    options.onValidate = this.pickedDate.bind(this);
    if(curField.multiple) {
      options.multiple = true;
    }
    options.output = {};
    options.output.dates =
      $('win' + this.id + '-f' + curFieldset.name + curField.name).value ?
      $('win' + this.id + '-f' + curFieldset.name + curField.name).value.split(',') :
      [];
    options.output.params = params;
    this.app.createWindow('PromptDateWindow', options);
  },
  pickedDate: function (event, output) {
    var curFieldset = this.options.fieldsets[output.params[0]]
      , curField = curFieldset.fields[output.params[1]]
    ;
    $('win' + this.id + '-f' + curFieldset.name + curField.name).value =
      output.dates.length
      ? output.dates.join(',')
      : '';
  },
  pickPoint: function (event, params) {
    var curFieldset = this.options.fieldsets[params[0]]
      , curField = curFieldset.fields[params[1]]
      , options = Object.clone(curField.options)
    ;
    options.onValidate = this.pickedDate.bind(this);
    if(curField.multiple)
      options.multiple = true;
    options.output = {};
    options.output.dates =
      $('win' + this.id + '-f' + curFieldset.name + curField.name).value ?
      $('win' + this.id + '-f' + curFieldset.name + curField.name).value.split(',') :
      [];
    options.output.params = params;
    this.app.createWindow('PromptDateWindow', options);
  },
  pickedPoint: function (event, output) {
    var curFieldset = this.options.fieldsets[output.params[0]]
      , curField = curFieldset.fields[output.params[1]]
    ;
    $('win' + this.id + '-f' + curFieldset.name + curField.name).value =
      output.dates.length ? output.dates.join(',') : '';
    $('win' + this.id + '-f' + curFieldset.name + curField.name + 'out').innerHTML =
      output.dates.length ?
        output.dates.length > 1 ?
          output.dates.length + ' ' + this.locales['FormWindow'].datepicker_selected :
          output.dates[0] :
        this.locales['FormWindow'].datepicker_empty;
    $('win' + this.id + '-f' + curFieldset.name + curField.name + 'out').value =
      output.dates.length ?
        output.dates.length > 1 ?
          output.dates.length + ' ' + this.locales['FormWindow'].datepicker_selected :
          output.dates[0] :
        this.locales['FormWindow'].datepicker_empty;
  },
  pickFile: function (event, params) {
    var curFieldset = this.options.fieldsets[params[0]]
      , curField = curFieldset.fields[params[1]]
      , options = Object.clone(curField.options)
    ;
    options.onValidate = this.pickedFile.bind(this);
    if(curField.multiple)
      options.multiple = true;
    options.output = {};
    options.output.params = params;
    this.app.createWindow('PromptUserFileWindow', options);
  },
  pickedFile: function (event, output) {
    var curFieldset = this.options.fieldsets[output.params[0]]
      , curField = curFieldset.fields[output.params[1]]
    ;
    if(curField.defaultUri) {
      var p = document.createElement('p');
      p.innerHTML = curField.defaultUri.substring(1);
      var req = this.app.createRestRequest({
        'path': p.textContent,
        'method': 'delete'
      });
      req.send();
    }
    curField.defaultUri = '';
    if(!this.files) {
      this.files = [];
    }
    this.files[output.params[0] + '' + output.params[1]] = output.files;
    $('win' + this.id + '-f' + curFieldset.name + curField.name).value =
      output.files[0]
      ? output.files[0].name
      : '';
    $('win' + this.id + '-f' + curFieldset.name + curField.name + 'out').innerHTML =
      output.files && output.files.length
      ? output.files.length > 1
        ? output.files.length + ' ' + this.locales['FormWindow'].filepicker_selected
        : output.files[0].name
      : this.locales['FormWindow'].filepicker_empty;
    $('win' + this.id + '-f' + curFieldset.name + curField.name + 'out').value =
      output.files && output.files.length
      ? output.files.length > 1
        ? output.files.length + ' ' + this.locales['FormWindow'].filepicker_selected
        : output.files[0].name
      : this.locales['FormWindow'].filepicker_empty;
  },
  // Form validation
  parseOutput: function () {
    this.options.links = [];
    $('win' + this.id + '-submit-button').setAttribute('disabled', 'disabled');
    $('win' + this.id + '-submit-button').setAttribute('value',
      this.locales['FormWindow'].form_wait);
    if(this.options.fieldsets.some(function(curFieldset, i) {
      if(!this.options.output[curFieldset.name]) {
        this.options.output[curFieldset.name] = {};
      }
      return curFieldset.fields.some(function (curField, k) {
        if(curField.input == 'completer') {
          var value = $('win' + this.id + '-f' + curFieldset.name + curField.name).value;
          var list = $('win' + this.id + '-l' + curFieldset.name + '-' + curField.name);
          for (var m = list.childNodes.length - 1; m >= 0; m--) {
            if(value = list.childNodes[m].getAttribute('value')) {
              this.options.links[curFieldset.name + curField.name] =
                list.childNodes[m].innerHTML;
            }
          }
          if(curField.required && !value) {
            this.app.createWindow('AlertWindow', {
              content: this.locales['FormWindow'].field_required + ' '
                + (curFieldset.label ? curFieldset.label : curFieldset.name)
                + ' > ' + (curField.label ? curField.label : curField.name)
            });
            return true;
          }
          this.options.output[curFieldset.name][curField.name] = value;
        } else if(curField.input == 'picker') {
          if(curField.type == 'file') {
            if(this.files && this.files[i + '' + k]
              && this.files[i + '' + k].length) {
              this.options.output[curFieldset.name][curField.name] =
                this.files[i + '' + k];
            } else {
              this.options.output[curFieldset.name][curField.name] = [];
            }
          } else {
            var value = $('win' + this.id + '-f' + curFieldset.name + curField.name).value;
            if(curField.required && !value) {
              this.app.createWindow('AlertWindow', {
                content: this.locales['FormWindow'].field_required + ' '
                  + (curFieldset.label ? curFieldset.label : curFieldset.name)
                  + ' > ' + (curField.label ? curField.label : curField.name)
              });
              return true;
            }
            if(curField.multiple) {
              this.options.output[curFieldset.name][curField.name] = value.split(',');
            } else {
              this.options.output[curFieldset.name][curField.name] = value;
            }
          }
        } else if(curField.multiple) {
          this.options.output[curFieldset.name][curField.name] = [];
          var selOptions = $('win' + this.id + '-f' + curFieldset.name + curField.name).childNodes;
          for (var m = 0, n = selOptions.length; m < n; m++) {
            if(selOptions[m].selected) {
              this.options.output[curFieldset.name][curField.name].push(selOptions[m].value);
            }
          }
        } else if(curField.type == 'datetime-local') {
          this.options.output[curFieldset.name][curField.name] =
            $('win' + this.id + '-f' + curFieldset.name + curField.name).value;
          if(!$('win' + this.id + '-f' + curFieldset.name + curField.name + '-time').value)
            this.options.output[curFieldset.name][curField.name] += ' 00:00:00';
          else
            this.options.output[curFieldset.name][curField.name] +=
              ' ' + $('win' + this.id + '-f' + curFieldset.name + curField.name + '-time').value
              + (
                $('win' + this.id + '-f' + curFieldset.name + curField.name + '-time').value.length == 5
                ? ':00'
                : ''
              );
          if(this.options.output[curFieldset.name][curField.name].length > 20)
            this.options.output[curFieldset.name][curField.name] =
              this.options.output[curFieldset.name][curField.name].substr(0, 20);
          if(curField.required
            && this.options.output[curFieldset.name][curField.name].indexOf('0000-00-00') === 0) {
            this.app.createWindow('AlertWindow', {
              content: this.locales['FormWindow'].field_required + ' '
                + (curFieldset.label ? curFieldset.label : curFieldset.name)
                + ' > ' + (curField.label ? curField.label : curField.name)
            });
            return true;
          }
        } else if(curField.type != 'checkbox'
          || $('win' + this.id + '-f' + curFieldset.name + curField.name).checked) {
          this.options.output[curFieldset.name][curField.name] =
            $('win' + this.id + '-f' + curFieldset.name + curField.name).value;
          if(curField.required && curField.type == 'date'
            && this.options.output[curFieldset.name][curField.name] == '0000-00-00') {
            this.app.createWindow('AlertWindow', {
              content: this.locales['FormWindow'].field_required + ' '
                + (curFieldset.label ? curFieldset.label : curFieldset.name)
                + ' > ' + (curField.label ? curField.label : curField.name)
            });
            return true;
          }
        }
      }.bind(this));
    }.bind(this))) {
      $('win' + this.id + '-submit-button').removeAttribute('disabled');
      $('win' + this.id + '-submit-button').setAttribute('value',
        this.locales['FormWindow'].form_submit);
      return false;
    }
    return true;
  },
  submit: function (event) {
    if(this.parseOutput()) {
      this.close();
      this.fireEvent('submit', [event, this.options.output]);
    }
  },
  // Window destruction
  destruct: function () {
    this.app.unregisterCommand('win' + this.id + '-submit');
    this.app.unregisterCommand('win' + this.id + '-pickValue');
    this.app.unregisterCommand('win' + this.id + '-pickDate');
    this.app.unregisterCommand('win' + this.id + '-pickFile');
    this.app.unregisterCommand('win' + this.id + '-pickPoint');
    this.app.unregisterCommand('win' + this.id + '-handleForm');
    this.parent();
  }
});
