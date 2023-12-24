Ext.onReady(function () {
    var OnetimeWindow = new MODx.Window({
        title: _('twofactorx'),
        labelWidth: 150,
        closable: false,
        collapsible: false,
        maximizable: false,
        modal: true,
        width: 230,
        autoHeight: true,
        fields: [{
            style: 'margin-top: 15px',
            html: '<p>' + _('twofactorx.onetime_notification') + '<br><br></p>'
        }, {
            height: 200,
            html:
                '<p><img id="qrimg" src="" alt="' + _('twofactorx.lbl_qrcode') + '" width="200" height="200"/></p>'
        }, {
            xtype: 'field',
            fieldLabel: _('twofactorx.lbl_secret'),
            id: 'twofactorx-secret',
            submitValue: false,
            readOnly: true,
            anchor: '100%'
        }],
        buttons: [{
            text: _('logout'),
            handler: function () {
                MODx.logout();
            }
        }],
        listeners: {
            afterlayout: {
                fn: TwoFactorX.util.getUserQRCode,
                scope: this
            }
        }
    });

    OnetimeWindow.show(Ext.getBody());
    setTimeout(function () {
        location.href = './';
    }, 60 * 1000);
});
