var PromptDateWindow=new Class({
  Extends: WebWindow,
  initialize: function(app,options) {
    // Setting options
    this.options.synchronize=true;
    this.options.mutiple=false;
    this.options.required=false;
    this.options.output={'dates':[]};
    this.classNames.push('PromptDateWindow');
    // Initializing window
    this.parent(app,options);
    //Default setting
    var date=new Date();
    if((!this.options.multiple)&&this.options.output.dates.length
      &&this.options.output.dates[0]!='0000-00-00') {
      this.currentMonth=parseInt(this.options.output.dates[0].split('-')[1]-1);
      this.currentDay=parseInt(this.options.output.dates[0].split('-')[2]);
      this.currentYear=parseInt(this.options.output.dates[0].split('-')[0]);
    } else {
      this.currentMonth=date.getMonth();
      this.currentDay=date.getDate();
      this.currentYear=date.getFullYear();
    }
    this.actualYear=date.getFullYear();
    // Registering commands
    this.app.registerCommand('win'+this.id+'-handleForm',this.handleForm.bind(this));
    this.app.registerCommand('win'+this.id+'-prev',this.previous.bind(this));
    this.app.registerCommand('win'+this.id+'-next',this.next.bind(this));
    this.app.registerCommand('win'+this.id+'-addDay',this.addDay.bind(this));
    this.app.registerCommand('win'+this.id+'-validate',this.validate.bind(this));
  },
  render : function() {
    // Setting title
    this.options.name=this.locale.title;
    // Creating form
    var tpl=
      '<form id="win'+this.id+'-handleForm">'
    + ' <select name="month" id="win'+this.id+'-month">';
    for(var i=0; i<12; i++) {
      tpl+=
      '   <option value="'+i+'" '+(this.currentMonth==i
    ? '     selected="selected"':'')+'>'
    + '     ' + this.locale.months[i]
    + '   </option>';
    }
    tpl+=
      ' </select>'
    + ' <input type="number" name="year" value="'+this.currentYear+'" pace="1"'
    + '   min="0" id="win'+this.id+'-year">'
    + '</form>';
    // Filling menu
    this.options.menu = [{
      'label':this.locale.menu_previousyear,
      'command':'prev:year',
      'title':this.locale.menu_previousyear_tx
    }, {
      'label':this.locale.menu_previousmonth,
      'command':'prev:month',
      'title':this.locale.menu_previousmonth_tx
    },{
      'tpl':tpl
    }, {
      'label':this.locale.menu_nextmonth,
      'command':'next:month',
      'title':this.locale.menu_nextmonth_tx
    }, {
      'label':this.locale.menu_nextyear,
      'command':'next:year',
      'title':this.locale.menu_nextyear_tx
    }];
    // Drawing window
    this.parent();
  },
  renderContent : function() {
    $('win'+this.id+'-month').value=this.currentMonth;
    $('win'+this.id+'-year').value=this.currentYear;
    // Putting window content
    var day;
    if(this.currentMonth==0||this.currentMonth==2||this.currentMonth==4
      ||this.currentMonth==6||this.currentMonth==7||this.currentMonth==9
      ||this.currentMonth==11) {
      day=31;
    } else if(this.currentMonth==3||this.currentMonth==5
      ||this.currentMonth==8||this.currentMonth==10) {
      day=30;
    } else {
      if((this.currentYear%4==0&&this.currentYear%100!=0)
        ||this.currentYear%400==0) {
        day=29;
      } else {
        day=28;
      }
    }
    var tpl=
      '<div class="box">'
    + '  <form action="#win'+this.id+'-validate">'
    + '    <fieldset><table class="date">'
    + '    <thead><tr>'
    + '      <th>'+this.locale.week+'</th>';
    for(var i=0; i<7; i++) {
      tpl+=
      '      <th>'+this.locale.days[(i+parseInt(7-this.locale.day_gap))%7]+'</th>';
    }
    tpl+=
      '    </tr></thead><tbody>';
    var firstDay=new Date(this.currentYear, this.currentMonth, 1);
    var firstDayDay=firstDay.getDay();
    var gap=(firstDayDay+parseInt(this.locale.day_gap))%7;
    var week=this.getWeekNumber();
    for(var i=0,j=0; i<42; i++) {
      if(Math.ceil((i+1)/7)>Math.ceil((gap+day)/7)) {
        break;
      }
      if(i%7==0) {
        tpl+=
      '    <tr>'
    + '      <th>'+(week?week:'')+'</th>';
        week++
      }
      if(i<gap||i>=(gap+day)) {
        tpl+=
      '      <td class="disabled"></td>';
      } else {
        j++;
        var curDate= this.currentYear
          + '-' +(((this.currentMonth+1)+'').length<2?'0':'')
          + (this.currentMonth+1)
          + '-' + ((j+'').length<2?'0':'') + j;
        tpl+=
      '      <td'+(this.options.output.dates.indexOf(curDate)>=0
    ? '        class="checked"':'')+'>'
    + '        <a href="#win'+this.id+'-addDay:'+curDate+'">'+j+'</a>'
    + '      </td>';
      }
      if(i%7==6) {
        tpl+=
      '    </tr>';
      }
    }
    tpl+=
      '  </tbody>'
    + '  </table></fieldset>'
    + '  <fieldset>'
    + '    <p class="fieldrow">'
    + '      <input type="submit" title="'+this.locale.validate_tx+'"'
    + '        name="validate" value="'+this.locale.validate+'" />'
    + '    </p>'
    + '  </fieldset>'
    + '</form>'
    + '</div>';
    this.view.innerHTML=tpl;
  },
  // Handle form for month
  handleForm: function(event) {
    if(event.target.nodeName=='SELECT') {
      if(event.type=='change'&&event.target.value!=this.currentMonth) {
        this.currentMonth=parseInt(event.target.value);
      }
      this.renderContent();
    } else {
      if(event.target.value!=this.currentYear) {
        this.currentYear=parseInt(event.target.value);
      }
      this.renderContent();
    }
  },
  // Handle navigation
  previous: function(event,params) {
    if(params[0]=='year') {
      this.currentYear--;
    } else if(this.currentMonth>0) {
      this.currentMonth--;
    } else {
      this.currentYear--;
      this.currentMonth=11;
    }
    this.renderContent();
  },
  next: function(event,params) {
    if(params[0]=='year') {
      this.currentYear++;
    } else if(this.currentMonth<11) {
      this.currentMonth++;
    } else {
      this.currentYear++;
      this.currentMonth=0;
    }
    this.renderContent();
  },
  // Get the number of the week
  isBisectile : function() {
    if((this.currentYear%4==0 && this.currentYear%100!=0)
      || this.currentYear%400==0) {
      return true;
    }
    return false;
  },
  // Get the number of the week
  getWeekNumber : function() {
    //aDate=new Date(yyyy,mm,dd);
    //firstDate=new Date(this.currentYear,0,1);
    //return parseInt( (firstDate.getDay()+(aDate-firstDate)/24/3600000) / 7) + 1;

    var monthArray = [31, this.isBisectile() ? 29 : 28, 31, 30, 31, 30, 31, 31,
      30, 31, 30, 31];
    var dayGap = parseInt(this.locale.day_gap, 10);
    var countDays = 0;
    var firstDay = new Date(this.currentYear,0,1).getDay();
    var localFirstDay = (firstDay + dayGap) % 7;
    var firstWeekDayCount = 7 - localFirstDay;
    var numWeeks;
    // If the first week has less then 4 days, we discard those days
    if(firstWeekDayCount < 4) {
      countDays -= firstWeekDayCount;
    // Otherwise, we add the full week
    } else {
      countDays += 7 - firstWeekDayCount;
    }
    // Adding days until we are in the current month
    for(var i=0; i<this.currentMonth; i++) {
      countDays += monthArray[i];
    }
    numWeeks = countDays / 7;

    return numWeeks < 0 ? 0 : Math.floor(numWeeks) + 1;
  },
  // Add a date for this planning
  addDay : function(event,params) {
    var exist=this.options.output.dates.indexOf(params[0]);
    if(exist>=0) {
      this.options.output.dates.splice(exist,1);
    } else {
      if(!this.options.multiple)
        this.options.output.dates=[];
      this.options.output.dates.push(params[0]);
    }
    this.renderContent();
  },
  //Send date at this planning
  validate : function(event) {
    if(this.options.output.dates.length||!this.options.required) {
      this.close();
      this.fireEvent('validate', [event, this.options.output]);
    }
  },
  // Window destruction
  destruct : function() {
    this.app.unregisterCommand('win'+this.id+'-handleForm');
    this.app.unregisterCommand('win'+this.id+'-prev');
    this.app.unregisterCommand('win'+this.id+'-next');
    this.app.unregisterCommand('win'+this.id+'-addDay');
    this.app.unregisterCommand('win'+this.id+'-validate');
    this.parent();
  }
});
