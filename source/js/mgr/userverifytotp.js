Ext.onReady(function () {
    var VerifyTotpWindow = new MODx.Window({
        title: _('twofactorx'),
        url: TwoFactorX.config.connectorUrl,
        action: 'mgr/user/verifytotp',
        saveBtnText: _('twofactorx.verify'),
        collapsible: false,
        maximizable: false,
        modal: true,
        width: 430,
        autoHeight: true,
        fields: [{
            style: 'margin-top: 15px',
            html: '<p>' + _('twofactorx.verifytotp_notification') + '</p>'
        }, {
            layout: 'column',
            items: [{
                columnWidth: .5,
                layout: 'form',
                items: [{
                    style: 'margin-top: 15px; width: 200px; height: 200px;',
                    html:
                        '<img id="verifytotp-qrcode" class="twofactorx-loading" src="" alt="' + _('twofactorx.qrcode') + '" width="200" height="200"/>'
                }]
            }, {
                columnWidth: .5,
                layout: 'form',
                items: [{
                    xtype: 'field',
                    fieldLabel: _('twofactorx.secret'),
                    id: 'verifytotp-secret',
                    submitValue: false,
                    readOnly: true,
                    anchor: '100%'
                }]
            }
            ]
        }, {
            style: 'margin-top: 15px',
            html: '<p>' + _('twofactorx.verifytotp_instruction') + '</p>'
        }, {
            xtype: 'field',
            fieldLabel: _('twofactorx.key'),
            name: 'key',
            id: 'verifytotp-key',
            anchor: '100%'
        }, {
            xtype: 'hidden',
            name: 'userid',
            value: MODx.request.id,
        }],
        listeners: {
            afterrender: function () {
                TwoFactorX.util.getUserQRCode('verifytotp-secret', 'verifytotp-qrcode');
            }
        },
    });

    VerifyTotpWindow.show(Ext.getBody());
});
