var RestQueue=new Class({
	Implements: [Events, Options],
	options: {
		prefix: '', // For multiple queues on localstorage
		period: 60 // Seconds to wait for request retries (0 never)
	},
	initialize: function(options) {
		this.setOptions(options);
		// Launching rest request regular attempts
		if(this.options.period)
			this.retryInterval=this.retryRestRequests.periodical(this.options.period*1000,this);
	},
	queuedRestRequests: [],
	restRequestsNum: 0,
	createRestRequest: function(params,revived) {
		var req=new RestRequest({
			'method':params.method,
			'url':(params.path?window.location.protocol+'//'+window.location.host+'/'+params.path:params.url),
			'data':(params.data?params.data:'')});
		// Should add headers here !!
		req.addEvent('retry',this.queueRestRequest.bind(this));
		if(window.localStorage)
			{
			if(!revived)
				req.addEvent('sent',this.storeRestRequest.bind(this));
			else
				{
				req.addEvent('done',this.unstoreRestRequest.bind(this));
				req.addEvent('error',this.unstoreRestRequest.bind(this));
				}
			}
		console.log('this.options.app')
		console.log(this.options.app)
		if(this.options.app)
			req.app=this.options.app;
		console.log(req);
		return req;
	},
	queueRestRequest: function(req) {
		req.addEvent('done',this.unqueueRestRequest.bind(this));
		req.addEvent('error',this.unqueueRestRequest.bind(this));
		req.removeEvents('retry');
		this.queuedRestRequests.push(req);
		this.fireEvent('queued', req);
	},
	unqueueRestRequest: function(req) {
		this.unstoreRestRequest(req);
		this.queuedRestRequests.splice(this.queuedRestRequests.indexOf(req),1);
		this.fireEvent('unqueued', req);
	},
	storeRestRequest: function(req) {
		req.removeEvents('sent');
		req.num=++this.restRequestsNum;
		try
			{
			window.localStorage['requests']=(window.localStorage['requests']&&window.localStorage['requests']!=undefined?window.localStorage['requests']:'')+req.num+',';
			window.localStorage['request'+req.num+'url']=req.options.url;
			window.localStorage['request'+req.num+'method']=req.options.method;
			window.localStorage['request'+req.num+'data']=req.options.data;
			window.localStorage['request'+req.num+'headers']=JSON.stringify(req.options.headers);
			}
		catch(e)
			{
			this.fireEvent('storeerror', this);
			}
		req.addEvent('done',this.unstoreRestRequest.bind(this));
		req.addEvent('error',this.unstoreRestRequest.bind(this));
	},
	unstoreRestRequest: function(req) {
		window.localStorage['requests']=(window.localStorage['requests']&&window.localStorage['requests']!=undefined?window.localStorage['requests'].replace(new RegExp('(,|^)'+req.num+'(,|$)'),',').replace('([,]+)',','):'');
		delete window.localStorage['request'+req.num+'url'];
		delete window.localStorage['request'+req.num+'method'];
		delete window.localStorage['request'+req.num+'data'];
		delete window.localStorage['request'+req.num+'headers'];
	},
	reviveRestRequests: function() {
		if(window.localStorage&&window.localStorage['requests'])
			{
			var reqs=window.localStorage['requests'].split(',');
			for(var i=reqs.length-1; i>=0; i--)
				{
				if(reqs[i])
					{
					if(reqs[i]>this.restRequestsNum)
						this.restRequestsNum=reqs[i];
					var req=this.createRestRequest({'url':window.localStorage['request'+reqs[i]+'url'],
						'method':window.localStorage['request'+reqs[i]+'method'],
						'data':(window.localStorage['request'+reqs[i]+'data']?window.localStorage['request'+reqs[i]+'data']:''),
						'headers':(window.localStorage['request'+reqs[i]+'headers']?JSON.parse(window.localStorage['request'+reqs[i]+'headers']):null)
						},true);
					req.num=reqs[i];
					req.send();
					}
				}
			}
	},
	retryRestRequests: function() {
		if(navigator.onLine&&this.queuedRestRequests.length)
			{
			this.queuedRestRequests.each(function(req) {
				req.send();
				});
			}
	},
	// Destruction
	destruct: function()
		{
		if(this.retryInterval)
			clearInterval(this.retryInterval);
		}
});