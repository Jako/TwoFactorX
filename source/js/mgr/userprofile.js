Ext.onReady(function () {
    var profilePanel = Ext.getCmp('modx-panel-profile-update');
    profilePanel.add({
        columnWidth: 1,
        xtype: 'panel',
        layout: 'form',
        anchor: '100%',
        id: 'twofactorx-qrcode',
        cls: 'x-form-item',
        style: 'margin-left: 0',
        items: [{
            html: '<div id="qrcode"><img id="qrimg" src="" alt="' + _('twofactorx.lbl_qrcode') + '"/></div>',
            fieldLabel: _('twofactorx.lbl_qrcode'),
            anchor: '50%'
        }, {
            xtype: 'field',
            fieldLabel: _('twofactorx.lbl_secret'),
            id: 'twofactorx-secret',
            submitValue: false,
            readOnly: true,
            anchor: '50%'
        }],
        listeners: {
            afterlayout: {
                fn: TwoFactorX.util.getUserQRCode,
                scope: this
            }
        }
    });
});
