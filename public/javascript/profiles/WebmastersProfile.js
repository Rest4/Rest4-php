var WebmastersProfile = new Class({
	Extends: AdministratorsProfile,
	initialize: function (app) {
		this.classNames.push('WebmastersProfile');
		// Initializing parent
		this.parent(app);
	},
	prepare: function () {
		this.parent();
		// Menu
		var locale = this.locales['WebmastersProfile'];
		this.app.menu[this.app.menu.length - 1].childs.push({
			'label': locale.db_server_label,
			'command': 'openWindow:DbServer',
			'title': locale.db_server_label_tx
		}, {
			'label': locale.rest_label,
			'command': 'openWindow:RestQuickTester',
			'title': locale.rest_label_tx
		}, {
			'label': locale.command_label,
			'command': 'webmasterCommands:command',
			'title': locale.command_label_tx
		}, {
			'label': locale.debug_label,
			'command': 'webmasterCommands:debug',
			'title': this.locale.debug_label_tx
		}, {
			'label': locale.code_label,
			'command': 'openWindow:Code',
			'title': locale.code_label_tx
		}, {
			'label': locale.db_label,
			'command': 'openWindow:DbBase:database:' + this.app.database,
			'title': locale.db_label_tx
		}, {
			'label': locale.sql_label,
			'command': 'openWindow:Sql',
			'title': locale.sql_label_tx
		}, {
			'label': locale.inspect_label,
			'command': 'openWindow:Inspect',
			'title': locale.inspect_label_tx
		}, {
			'label': locale.cache_label,
			'command': 'openWindow:Cache',
			'title': locale.cache_label_tx
		}, {
			'label': locale.locales_label,
			'command': 'webmasterCommands:locales',
			'title': locale.locales_label_tx
		}, {
			'label': locale.useragent_label,
			'command': 'webmasterCommands:useragent',
			'title': locale.useragent_label_tx
		}, {
			'label': locale.doc_label,
			'command': 'openWindow:Browse:pathname:/doc/fr-FR/root.html',
			'title': locale.doc_label_tx
		});
		this.app.menu.push({
			'label': locale.games,
			'title': locale.games,
			'childs': [{
			  //  'label':locale.gamemaker,
			  //  'command':'openWindow:GameMaker',
			  //  'title':locale.gamemaker_tx
			  //},{
					'label': locale.belote,
					'command': 'openWindow:Belote',
					'title': locale.belote_tx
				}, {
					'label': locale.karaoke,
					'command': 'openWindow:Browse:protocol:http:hostname:'
						+ 'midiwebkaraoke.com:pathname:/index.html',
					'title': locale.karaoke_tx
				}, {
					'label': locale.memory,
					'command': 'openWindow:Browse:protocol:http:hostname:'
						+ 'memory.elitwork.com:pathname:/index.html',
					'title': locale.memory_tx
				}, {
					'label': locale.breakit,
					'command': 'openWindow:Browse:protocol:http:hostname:'
						+ 'breakit.elitwork.com:pathname:/index.html',
					'title': locale.breakit_tx
				}, {
					'label': locale.sumuray,
					'command': 'openWindow:Browse:protocol:http:hostname:'
						+ 'sumuray.com:pathname:/index.html',
					'title': locale.sumuray_tx
				}, {
					'label': locale.pirat,
					'command': 'openWindow:Browse:protocol:http:hostname:'
						+ 'www.pirateslovedaisies.com:pathname:/',
					'title': locale.pirat_tx
				}, {
					'label': locale.agent,
					'command': 'openWindow:Browse:protocol:http:hostname:'
						+ 'agent8ball.com:pathname:/',
					'title': locale.agent_tx
				}, {
					'label': locale.front,
					'command': 'openWindow:Browse:protocol:http:hostname:'
						+ 'end3r.com:pathname:/games/frontinvaders/',
					'title': locale.front_tx
				}, {
					'label': locale.tank,
					'command': 'openWindow:Browse:protocol:http:hostname:'
						+ 'tank.elitwork.com:pathname:/index.html',
					'title': locale.tank_tx
				}
			]
		});
		// Registering commands
		this.app.registerCommand('webmasterCommands',
			this.webmasterCommands.bind(this));
	},
	postChanges: function () {
		this.app.createWindow('EditorWindow', {
			'path': '/fs/todo.txt'
		});
	},
	webmasterCommands: function (event, params) {
		switch (params[0]) {
		case 'command':
			var tpl = '<ul>';
			for (prop in this.app.commands)
				tpl += '<li>' + prop + '</li>';
			tpl += '</ul>';
			this.app.createWindow('AlertWindow', {
				'name': 'Currently Registered Commands',
				'content': tpl,
				'synchronize': false
			});
			break;
		case 'locales':
			this.app.locales = {};
			this.app.createWindow('AlertWindow', {
				'content': this.locale.locales_alert
			});
			break;
		case 'useragent':
			this.app.createWindow('AlertWindow', {
				'content': window.navigator.userAgent
			});
			break;
		case 'debug':
			(function (logFn) {
				var debugWindow = this.app.createWindow('LogWindow', {
					'name': 'Debug'
				});
				// Replace the log function
				console.log = function () {
					for (var i = 0; i < arguments.length; i++)
						debugWindow.append(typeof arguments[i] == 'string' ?
							arguments[i] : JSON.stringify(arguments[i]));
				};
				// Reassign the initial value 
				debugWindow.addEvent('close', function () {
					console.log = logFn;
				});
			}).bind(this)(console.log);
			break;
		}
	}
});
