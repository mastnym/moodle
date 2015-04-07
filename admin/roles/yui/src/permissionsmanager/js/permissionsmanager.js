var SELECTORS = {
    ADDROLE: 'a.allowlink, a.prohibitlink',
    REMOVEROLE: 'a.preventlink, a.unprohibitlink',
    UNPROHIBIT: 'a.unprohibitlink'

};

/**
 * This file contains module for managing roles with capabilities
 *
 * @module moodle-core_role-permissionsmanager
 */

/**
 * Constructs new permissions manager and
 * initializes capabilities for given table
 *
 * @namespace M.core_role
 * @class Manager
 * @constructor
 * @extends Base
 */
function Manager() {
    Manager.superclass.constructor.apply(this, arguments);
}
Manager.NAME = NAME;
Manager.ATTRS = {
    /**
     * Id of current moodle context
     *
     * @property contextid
     * @type number
     */
    contextid: {
        setter: function(contextid) {
            if (!(/^\d+$/.test(contextid))) {
                Y.fail('Moodle permissions manager: Invalid context id specified');
            }
            return contextid;
        }
    },
    /**
     * Human readeable context name
     *
     * @property contextname
     * @type string
     */
    contextname: {
        validator: Y.Lang.isString
    },
    /**
     * URL to /admin in Moodle
     *
     * @property adminurl
     * @type string
     */
    adminurl: {
        validator: Y.Lang.isString
    },
    /**
     * Array of roles which could be assigned to capabilities
     *
     * @property overideableRoles
     * @type array
     */
    overideableRoles: {
        value: null
    }
};

Y.extend(Manager, Y.Base, {
    panel: null,
    /**
     * Initializer.
     * Initialize event delagation on clicks.
     *
     * @method initializer
     */
    initializer: function() {
        this.container = Y.one(Y.config.doc.body);
        this.container.delegate('click', this.handleAddRole, SELECTORS.ADDROLE, this);
        this.container.delegate('click', this.handleRemoveRole, SELECTORS.REMOVEROLE, this);
    },
    /**
     * handleAddRole.
     * Create panel for role to be added to prohibited/prevented.
     *
     * @method handleAddRole
     */
    handleAddRole: function(e) {
        e.preventDefault();
        this.once('overideableRolesLoaded', function() {
            var action = e.currentTarget.getData('action');
            var row = e.currentTarget.ancestor('tr.rolecap');
            var confirmationDetails = {
                cap: row.getData('humanname'),
                context: this.get('contextname')
            };
            var confirmation = M.util.get_string('role' + action + 'info', 'core_role', confirmationDetails);
            if (this.panel === null){
                this.panel = new M.core.dialogue ({
                    draggable: true,
                    modal: true,
                    closeButton: true,
                    width: '450px'
                });
            }
            this.panel.set('headerContent', M.util.get_string('role' + action + 'header', 'core_role'));
            // Create buttons.
            var template = Y.Handlebars.compile(
                '<div class="popup_content">' +
                    '{{CONFIRMATION}}' +
                    "<hr/>" +
                    '<div class="role_buttons"/>' +
                '</div>');
            var content = Y.Node.create(template({CONFIRMATION:confirmation}));
            content.setStyle('text-align','center');

            var buttonContainer = content.one('.role_buttons');
            // Eliminate roles that are assigned already and create buttons.
            var i, setSelector, roles = this.get('overideableRoles');

            var setRoles = [];
            switch (action){
                case 'allow':
                    setSelector = SELECTORS.REMOVEROLE;
                    break;
                case 'prohibit':
                    setSelector = SELECTORS.UNPROHIBIT;
                    break;
            }
            if (setSelector){
                row.all(setSelector).each(function(link) {
                    setRoles[link.getAttribute('data-role-id')] = true;
                }, this);
            }
            for (i in roles) {
                var buttonTemplate = Y.Handlebars.compile(
                    '<input type="button" value="{{ROLENAME}}" data-role-id="{{ROLEID}}"/>'
                 );
                var button = Y.Node.create(buttonTemplate({
                    ROLENAME: roles[i],
                    ROLEID: i
                }));
                // Disable if already assigned.
                if (setRoles[i] === true) {
                    button.setAttribute('disabled', 'disabled');
                }
                buttonContainer.append(button);
            }
            // Add callback to change permissions.
            content.delegate('click',function(e){
                var roleId = e.currentTarget.getData("role-id");
                this.changePermissions(this, row, roleId, action);
            },"input",this);
            this.panel.set('bodyContent', content);
            this.panel.show();
        }, this);
        this._loadOverideableRoles();

    },
    /**
     * handleRemoveRole.
     * Create panel for role to be removed prohibited/prevented.
     *
     * @method handleRemoveRoles
     */
    handleRemoveRole: function(e) {
       e.preventDefault();
       this.once('overideableRolesLoaded', function() {
           var action = e.currentTarget.getData('action');
           var roleId = e.currentTarget.getData('role-id');
           var row = e.currentTarget.ancestor('tr.rolecap');
           var questionDetails = {
               role: this.get('overideableRoles')[roleId],
               cap: row.getData('humanname'),
               context: this.get('contextname')
           };
           var config = {
               modal: true,
               visible: false,
               centered: true,
               title: M.util.get_string('confirmunassigntitle', 'core_role'),
               question: M.util.get_string('confirmrole' + action, 'core_role',questionDetails),
               yesLabel: M.util.get_string('confirmunassignyes', 'core_role'),
               noLabel: M.util.get_string('confirmunassignno', 'core_role')
           };
           var dialogue = new M.core.confirm(config);
           dialogue.on('complete-yes', this.changePermissions, this, row, roleId, action);
        }, this);
        this._loadOverideableRoles();
    },
    /**
     * changePermissions.
     * Perform a server call and change specified permission for capability.
     *
     * @method changePermissions
     * @param {Y_node} row capability table row.
     * @param {number} roleId id of a role to be changed
     * @param {string} action on of four permission change strings
     */
    changePermissions: function(e, row, roleId, action) {
        var params = {
            contextid: this.get('contextid'),
            roleid: roleId,
            sesskey: M.cfg.sesskey,
            action: action,
            capability: row.getData('name')
        };
        Y.io(this.get('adminurl') + 'roles/ajax.php', {
            method: 'POST',
            data: params,
            on: {
                complete: function(tid, outcome) {
                    try {
                        if (outcome.status != 200) {
                            new M.core.ajaxException(outcome);
                        } else {
                            var action = Y.JSON.parse(outcome.responseText);
                            // Update ui according to action performed.
                            var roles = this.get('overideableRoles');
                            var roleTemplate = Y.Handlebars.compile(
                                '<span>' +
                                    '{{ROLE}} ' +
                                    '<a data-role-id="{{ROLEID}}">' +
                                        '<img src="{{IMAGEURL}}" alt="" />' +
                                    '</a>' +
                                '</span>');
                            var role = Y.Node.create(roleTemplate({
                                ROLE: roles[roleId],
                                ROLEID: roleId,
                                IMAGEURL: M.util.image_url('t/delete', 'moodle'),
                                ACTION: action
                            }));
                            role.setStyle('display', 'inline-block');
                            var link = role.one('a');
                            link.setAttribute('href', this.get('adminurl') + 'roles/permissions.php');

                            switch (action) {
                                case 'allow':
                                    role.setAttribute('class', 'allowed');
                                    link.setAttribute('class', 'preventlink');
                                    link.setData('action', 'prevent');
                                    row.one('.allowmore').insert(role, 'before');
                                    this.panel.hide();
                                    break;
                                case 'prohibit':
                                    role.setAttribute('class', 'forbidden');
                                    link.setAttribute('class', 'unprohibitlink');
                                    link.setData('action', 'unprohibit');
                                    row.one('.prohibitmore').insert(role, 'before');
                                    // Remove the role from allowed.
                                    var allowedLink = row.one('.allowedroles').one('a[data-role-id="' + roleId + '"]');
                                    if (allowedLink) {
                                        allowedLink.ancestor('.allowed').remove();
                                    }
                                    this.panel.hide();
                                    break;
                                case 'prevent':
                                    row.one('a[data-role-id="' + roleId + '"]').ancestor('.allowed').remove();
                                    break;
                                case 'unprohibit':
                                    row.one('a[data-role-id="' + roleId + '"]').ancestor('.forbidden').remove();
                                    break;
                                default:
                                    break;
                            }
                        }
                    } catch (e) {
                        new M.core.exception(e);
                    }
                }
            },
            context: this
        });

    },
    /**
     * _loadOverideableRoles.
     * Load all possible roles, which could be assigned from server
     *
     * @method _loadOverideableRoles
     */
    _loadOverideableRoles: function() {
        var params = {
            contextid: this.get('contextid'),
            getroles: 1,
            sesskey: M.cfg.sesskey
        };
        Y.io(this.get('adminurl') + "roles/ajax.php", {
            method: 'POST',
            data: params,
            on: {
                complete: function(tid, outcome) {
                    try {
                        var roles = Y.JSON.parse(outcome.responseText);
                        this.set('overideableRoles', roles);
                    } catch (e) {
                        new M.core.exception(e);
                    }
                    this._loadOverideableRoles = function() {
                        this.fire('overideableRolesLoaded');
                    };
                    this._loadOverideableRoles();
                }
            },
            context: this
        });
    }
});

Y.namespace('M.core_role.permissionsmanager').Manager = Manager;
Y.namespace('M.core_role.permissionsmanager').instance = null;
Y.namespace('M.core_role.permissionsmanager').init = function(config) {
    Y.M.core_role.permissionsmanager.instance = Y.M.core_role.permissionsmanager.instance || new Manager(config);
    return Y.M.core_role.permissionsmanager.instance;
};