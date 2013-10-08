var AdministratorsProfile = new Class({
	Extends: UsersProfile,
	initialize: function (app) {
		this.classNames.push('AdministratorsProfile');
		// Initializing parent
		this.parent(app);
	},
	prepare: function () {
		this.parent();
		// Menu
		var locale = this.locales['AdministratorsProfile'];
		this.app.menu.push({
			'label': locale.actors,
			'title': locale.actors_tx,
			'childs': [{
				'label': locale.addressBook,
				'command': 'openWindow:AddressBook',
				'title': locale.addressBook_tx
			}, {
				'label': locale.users,
				'command': 'openWindow:DbEntries:database:' + this.app.database
					+ ':table:users',
				'title': locale.users_tx
			}, {
				'label': locale.groups,
				'command': 'openWindow:DbEntries:database:' + this.app.database
					+ ':table:groups',
				'title': locale.groups_tx
			}, {
				'label': locale.rights,
				'command': 'openWindow:DbEntries:database:' + this.app.database
					+ ':table:rights',
				'title': locale.rights_tx
			}, {
				'label': locale.organizations,
				'command': 'openWindow:DbEntries:database:' + this.app.database
					+ ':table:organizations',
				'title': locale.organizations_tx
			}, {
				'label': locale.organizationTypes,
				'command': 'openWindow:DbEntries:database:' + this.app.database
					+ ':table:organizationTypes',
				'title': locale.organizationTypes_tx
			}, {
				'label': locale.contacts,
				'command': 'openWindow:DbEntries:database:' + this.app.database
					+ ':table:contacts',
				'title': locale.contacts_tx
			}, {
				'label': locale.places,
				'command': 'openWindow:DbEntries:database:' + this.app.database
					+ ':table:places',
				'title': locale.places_tx
			}]
		},{
			'label': locale.dictionaries,
			'title': locale.dictionaries_tx,
			'childs': [{
				'label': locale.organizationTypes,
				'command': 'openWindow:DbEntries:database:' + this.app.database
					+ ':table:organizationTypes',
				'title': locale.organizationTypes_tx
			}, {
				'label': locale.contactTypes,
				'command': 'openWindow:DbEntries:database:' + this.app.database
					+ ':table:contactTypes',
				'title': locale.contactTypes_tx
			}]
		},{
			'label': locale.utils,
			'title': locale.utils,
			'childs': [{
				'label': locale.editor,
				'command': 'openWindow:Editor',
				'title': locale.editor_tx
			}, {
				'label': locale.storage,
				'command': 'adminCommands:cleanStorage',
				'title': locale.storage_tx
			}]
		});
	}
});
