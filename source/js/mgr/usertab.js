Ext.onReady(function () {
    MODx.addTab('modx-user-tabs', {
        id: 'twofactorx-user-tab',
        title: _('twofactorx.usertab'),
        bodyStyle: '',
        items: [{
            html: _('twofactorx.usertab_desc'),
            border: false,
            bodyCssClass: 'panel-desc',
            anchor: '100%'
        }, {
            xtype: 'panel',
            cls: 'main-wrapper',
            anchor: '100%',
            items: [{
                layout: 'column',
                items: [{
                    columnWidth: 1,
                    layout: 'form',
                    items: [{
                        xtype: 'panel',
                        cls: 'x-panel-btns',
                        items: [{
                            xtype: 'button',
                            cls: 'primary-button',
                            text: _('twofactorx.btn_changestatus'),
                            id: 'twofactorx-btn-changestatus',
                            handler: TwoFactorX.util.changeStatus
                        }, {
                            xtype: 'button',
                            cls: 'primary-button',
                            text: _('twofactorx.btn_resetsecret'),
                            id: 'twofactorx-btn-resetsecret',
                            handler: TwoFactorX.util.resetSecret
                        }, {
                            xtype: 'button',
                            cls: 'primary-button',
                            text: _('twofactorx.btn_emailinstructions'),
                            id: 'twofactorx-btn-emailinstructions',
                            handler: TwoFactorX.util.emailInstructions
                        }, {
                            xtype: 'button',
                            cls: 'primary-button',
                            text: _('twofactorx.btn_emailqr'),
                            id: 'twofactorx-btn-emailqr',
                            handler: TwoFactorX.util.emailQR
                        }],
                        anchor: '100%'
                    }, {
                        xtype: 'panel',
                        layout: 'form',
                        labelAlign: 'left',
                        labelSeparator: ':',
                        labelWidth: 80,
                        anchor: '100%',
                        items: [{
                            xtype: 'field',
                            fieldLabel: _('twofactorx.lbl_status'),
                            id: 'twofactorx-status',
                            submitValue: false,
                            readOnly: true,
                            anchor: '100%'
                        }, {
                            xtype: 'field',
                            fieldLabel: _('twofactorx.lbl_secret'),
                            id: 'twofactorx-secret',
                            submitValue: false,
                            readOnly: true,
                            anchor: '100%'
                        }, {
                            xtype: 'field',
                            fieldLabel: _('twofactorx.lbl_uri'),
                            id: 'twofactorx-uri',
                            submitValue: false,
                            readOnly: true,
                            anchor: '100%'
                        }]
                    }]
                }, {
                    width: 215,
                    layout: 'form',
                    items: [{
                        xtype: 'panel',
                        anchor: '100%',
                        id: 'twofactorx-qrcode',
                        cls: 'x-form-item',
                        style: 'padding-left: 15px',
                        items: [{
                            xtype: 'label',
                            cls: 'x-form-item-label',
                            text: _('twofactorx.lbl_qrcode') + ':',
                            style: 'padding-top: 0',
                            anchor: '100%'
                        }, {
                            html: '<div id="qrcode"><img id="qrimg" src="" alt="' + _('twofactorx.lbl_qrcode') + '"/></div>'
                        }]
                    }]
                }]
            }]
        }],
        listeners: {
            afterlayout: {
                fn: TwoFactorX.util.getUserSettings,
                scope: this
            }
        }
    });
});
