var twofactorx = function (config) {
    config = config || {};
    twofactorx.superclass.constructor.call(this, config);
};
Ext.extend(twofactorx, Ext.Component, {
    initComponent: function () {
        this.stores = {};
        this.ajax = new Ext.data.Connection({
            disableCaching: true,
        });
    }, page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, util: {}, form: {}
});
Ext.reg('twofactorx', twofactorx);

var TwoFactorX = new twofactorx();

TwoFactorX.util = {
    urlTpl: new Ext.XTemplate('<tpl for=".">'
        + '{connector_url}?action={action}&accountname={accountname}&secret={secret}&issuer={issuer}&HTTP_MODAUTH={site_id}'
        + '</tpl>', {
        compiled: true
    }),
    getUserSettings: function () {
        MODx.Ajax.request({
            url: TwoFactorX.config.connectorUrl,
            params: {
                action: 'mgr/user/getsettings',
                id: MODx.request.id,
                admin: MODx.user.id
            },
            listeners: {
                success: {
                    fn: function (r) {
                        var changestatus = Ext.getCmp('twofactorx-btn-changestatus');
                        var status = Ext.getCmp('twofactorx-status');
                        var secret = Ext.getCmp('twofactorx-secret');
                        var uri = Ext.getCmp('twofactorx-uri');
                        var qrcode = document.getElementById('twofactorx-qrcode');
                        secret.setValue(r.object.secret);
                        uri.setValue(decodeURIComponent(r.object.uri));
                        document.getElementById('twofactorx-qrcode').src = TwoFactorX.util.urlTpl.apply({
                            connector_url: TwoFactorX.config.connectorUrl,
                            action: 'mgr/qrcode/get',
                            accountname: r.object.accountname,
                            secret: r.object.secret,
                            issuer: r.object.issuer,
                            site_id: MODx.siteId
                        });
                        if (r.object.totp_disabled === true) {
                            changestatus.setText(_('twofactorx.enable'));
                            status.setValue(_('twofactorx.disabled'));
                            status.addClass('red');
                        }
                        if (r.object.totp_disabled === false) {
                            changestatus.setText(_('twofactorx.disable'));
                            status.setValue(_('twofactorx.enabled'));
                            status.removeClass('red');
                        }
                    },
                    scope: this
                }
            }
        });
    },
    getUserQRCode: function (field = 'twofactorx-secret', image = 'twofactorx-qrcode') {
        MODx.Ajax.request({
            url: TwoFactorX.config.connectorUrl,
            params: {
                action: 'mgr/user/getqrcode'
            },
            listeners: {
                success: {
                    fn: function (r) {
                        var secret = Ext.getCmp(field);
                        if (secret) {
                            secret.setValue(r.object.secret);
                        }
                        document.getElementById(image).src = TwoFactorX.util.urlTpl.apply({
                            connector_url: TwoFactorX.config.connectorUrl,
                            action: 'mgr/qrcode/get',
                            accountname: r.object.accountname,
                            secret: r.object.secret,
                            issuer: r.object.issuer,
                            site_id: MODx.siteId
                        });
                    },
                    scope: this
                }
            }
        });
    },
    changeStatus: function (btn) {
        if (btn === 'yes') {
            var current = ' - ';
            var status = Ext.getCmp('twofactorx-status');
            if (status.getValue() === _('twofactorx.enabled')) {
                current = 'ENABLED'
            } else if (status.getValue() === _('twofactorx.disabled')) {
                current = 'DISABLED'
            }
            MODx.Ajax.request({
                url: TwoFactorX.config.connectorUrl,
                params: {
                    action: 'mgr/status/change',
                    id: MODx.request.id,
                    admin: MODx.user.id,
                    status: current
                },
                listeners: {
                    success: {
                        fn: TwoFactorX.util.getUserSettings
                    }
                }
            });
        } else if (btn !== 'no') {
            TwoFactorX.util.confirm(_('twofactorx.changestatus_confirm'), TwoFactorX.util.changeStatus);
        }
    },
    resetSecret: function (btn) {
        if (btn === 'yes') {
            MODx.Ajax.request({
                url: TwoFactorX.config.connectorUrl,
                params: {
                    action: 'mgr/secret/reset',
                    id: MODx.request.id,
                    admin: MODx.user.id
                },
                listeners: {
                    success: {
                        fn: TwoFactorX.util.getUserSettings
                    }
                }
            });
        } else if (btn !== 'no') {
            TwoFactorX.util.confirm(_('twofactorx.resetsecret_confirm'), TwoFactorX.util.resetSecret);
        }
    },
    emailInstructions: function (btn) {
        if (btn === 'yes') {
            MODx.Ajax.request({
                url: TwoFactorX.config.connectorUrl,
                params: {
                    action: 'mgr/email/instructions',
                    id: MODx.request.id,
                    admin: MODx.user.id
                },
                listeners: {
                    success: {
                        fn: function (r) {
                            TwoFactorX.util.alert(r.message)
                        }
                    }
                }
            });
        } else if (btn !== 'no') {
            TwoFactorX.util.confirm(_('twofactorx.emailinstructions_confirm'), TwoFactorX.util.emailInstructions);
        }
    },
    emailQR: function (btn) {
        if (btn === 'yes') {
            MODx.Ajax.request({
                url: TwoFactorX.config.connectorUrl,
                params: {
                    action: 'mgr/email/secret',
                    id: MODx.request.id,
                    admin: MODx.user.id
                },
                listeners: {
                    success: {
                        fn: function (r) {
                            TwoFactorX.util.alert(r.message)
                        }
                    }
                }
            });
        } else if (btn !== 'no') {
            TwoFactorX.util.confirm(_('twofactorx.emailqr_confirm'), TwoFactorX.util.emailQR);
        }
    },
    confirm: function (msg, f) {
        Ext.MessageBox.show({
            title: _('twofactorx'),
            msg: msg,
            width: 500,
            buttons: Ext.MessageBox.YESNO,
            fn: f,
            icon: Ext.MessageBox.QUESTION
        });
    },
    alert: function (msg) {
        Ext.MessageBox.show({
            title: _('twofactorx'),
            msg: msg,
            width: 400,
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.INFO
        });
    }
};
