Ext.onReady(function () {
    var profilePanel = Ext.getCmp('modx-panel-profile-update');
    profilePanel.add({
        anchor: '100%',
        cls: 'x-form-item',
        columnWidth: 1,
        items: [{
            html: '<div><img id="twofactorx-qrcode" class="twofactorx-loading" src="" alt="' + _('twofactorx.qrcode') + '"/></div>',
            fieldLabel: _('twofactorx.qrcode'),
            anchor: '50%'
        }, {
            xtype: 'field',
            fieldLabel: _('twofactorx.secret'),
            id: 'twofactorx-secret',
            submitValue: false,
            readOnly: true,
            anchor: '50%'
        }],
        layout: 'form',
        listeners: {
            afterlayout: function () {
                TwoFactorX.util.getUserQRCode('twofactorx-secret', 'twofactorx-qrcode');
            }
        },
        style: 'margin-left: 0',
        xtype: 'panel'
    });
});
