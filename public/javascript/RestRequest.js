var RestRequest=new Class({
	Extends: Request,
	initialize: function(options) {
		options.timeout=5000000;
		options.emulation=false;
		this.options.data='';
		this.parent(options);
		this.addEvent('complete',this.ressourceCompleted.bind(this));
		this.addEvent('timeout',this.ressourceTimeout.bind(this));
		//this.setHeader('Accept','*.*'); // Win some bytes for each requests
	},
	send: function(data) {
		if(data)
			this.options.data=data;
		else
			data=this.options.data;
		this.fireEvent('sent', this);
		if(this.app&&!this.app.onLine)
			{
			this.fireEvent('retry', this);
			return;
			}
		try
			{
			this.parent(data);
			}
		catch(err)
			{
			this.fireEvent('retry', this);
			}
	},
	ressourceCompleted: function() {
		this.removeEvents('timeout');
		if(this.status>=200&&this.status<=300)
			{
			this.fireEvent('done', this);
			}
		else if(this.status==0)
			{
			this.fireEvent('retry', this);
			}
		else
			{
			this.fireEvent('error', this);
			}
	},
	ressourceTimeout: function() {
		this.cancel();
		this.fireEvent('retry', this);
	},
	onSuccess: function() {
		this.fireEvent('complete', this).fireEvent('success', arguments).callChain();
	},
	onFailure: function() {
		this.fireEvent('complete', this).fireEvent('failure', this.xhr);
	}
});

var RestRequests=new Class({
	initialize: function(options) {
		this.reqs=[];
	},
	cancelReqs: function()
		{
		for(var i=this.reqs.length-1; i>=0; i--)
			{
			this.reqs[i].cancel();
			}
		this.reqs=[];
		this.loading(false);
		return true;
		},
	addReq: function(req)
		{
		if(req)
			{
			this.reqs.push(req);
			}
		},
	removeReq: function(req)
		{
		var r=this.reqs.indexOf(req);
		if(r>=0)
			{
			this.reqs.splice(r,1);
			}
		},
	sendReqs: function(doneCallback,errorCallback)
		{
		this.doneCallback=doneCallback;
		if(!this.reqs.length)
			{
			this.doneCallback();
			}
		else
			{
			for(var i=this.reqs.length-1; i>=0; i--)
				{
				this.reqs[i].addEvent('done',this.reqSent.bind());
				this.reqs[i].send();
				}
			this.loading(true);
			}
		},
	reqsSent: function(req)
		{
		this.removeReq(req);
		if(!this.reqs.length)
			{
			this.loading(false);
			this.doneCallback();
			}
		}
});