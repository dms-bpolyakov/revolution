/**
 * @class MODx.panel.ContentType
 * @extends MODx.FormPanel
 * @param {Object} config An object of options.
 * @xtype modx-panel-content-type
 */
MODx.panel.ContentType = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        id: 'modx-panel-content-type'
		,cls: 'container'
        ,url: MODx.config.connector_url
        ,defaults: { collapsible: false ,autoHeight: true }
        ,items: [{
            html: '<h2>'+_('content_types')+'</h2>'
            ,cls: 'modx-page-header'
            ,itemId: 'header'
            ,border: false
        },{
            layout: 'form'
            ,itemId: 'form'
            ,items: [{
                html: '<p>'+_('content_type_desc')+'</p>'
				,bodyCssClass: 'panel-desc'
                ,itemId: 'description'
                ,border: false
            },{
                xtype: 'modx-grid-content-type'
                ,itemId: 'grid'
				,cls:'main-wrapper'
                ,preventRender: true
            }]
        }]
    });
    MODx.panel.ContentType.superclass.constructor.call(this,config);
};
Ext.extend(MODx.panel.ContentType,MODx.FormPanel,{});
Ext.reg('modx-panel-content-type',MODx.panel.ContentType);

/**
 * Loads a grid of content types
 *
 * @class MODx.grid.ContentType
 * @extends MODx.grid.Grid
 * @param {Object} config An object of options.
 * @xtype modx-grid-contenttype
 */
MODx.grid.ContentType = function(config) {
    config = config || {};
    var binaryColumn = new Ext.ux.grid.CheckColumn({
        header: _('binary')
        ,dataIndex: 'binary'
        ,width: 40
        ,sortable: true
    });

    Ext.applyIf(config,{
        url: MODx.config.connector_url
        ,baseParams: {
            action: 'system/contenttype/getlist'
        }
        ,autosave: true
        ,save_action: 'system/contenttype/updatefromgrid'
        ,fields: ['id','name','mime_type','file_extensions','headers','binary','description']
        ,paging: true
        ,remoteSort: true
        ,plugins: binaryColumn
        ,columns: [{
            header: _('id')
            ,dataIndex: 'id'
            ,width: 50
            ,sortable: true
        },{
            header: _('name')
            ,dataIndex: 'name'
            ,sortable: true
            ,editor: { xtype: 'textfield' }
        },{
            header: _('description')
            ,dataIndex: 'description'
            ,editor: { xtype: 'textfield' }
            ,width: 200
        },{
            header: _('mime_type')
            ,dataIndex: 'mime_type'
            ,sortable: true
            ,editor: { xtype: 'textfield' }
            ,width: 80
        },{
            header: _('file_extensions')
            ,dataIndex: 'file_extensions'
            ,sortable: true
            ,editor: { xtype: 'textfield' }
        },binaryColumn]
        ,tbar: [{
            text: _('content_type_new')
            ,handler: { xtype: 'modx-window-content-type-create' ,blankValues: true }
        }]
    });
    MODx.grid.ContentType.superclass.constructor.call(this,config);
};
Ext.extend(MODx.grid.ContentType,MODx.grid.Grid,{
    getMenu: function() {
        var m = [];
        m.push({
            text: _('content_type_remove')
            ,handler: this.confirm.createDelegate(this,['system/contenttype/remove',_('content_type_remove_confirm')])
        });
        this.menu.record.menu = m;
    }
});
Ext.reg('modx-grid-content-type',MODx.grid.ContentType);


/**
 * Generates the ContentType window.
 *
 * @class MODx.window.ContentType
 * @extends MODx.Window
 * @param {Object} config An object of options.
 * @xtype modx-window-contenttype-create
 */
MODx.window.CreateContentType = function(config) {
    config = config || {};
    this.ident = config.ident || 'modx-cct'+Ext.id();
    Ext.applyIf(config,{
        title: _('content_type_new')
        ,width: 550
        ,url: MODx.config.connector_url
        ,action: 'system/contenttype/create'
        ,fields: [{
            xtype: 'modx-tabs'
            ,items: [{
                title: 'Main'
                ,layout: 'form'
                ,items: [{
                    layout: 'column'
                    ,border: false
                    ,defaults: {
                        layout: 'form'
                        ,labelAlign: 'top'
                        ,anchor: '100%'
                        ,border: false
                    }
                    ,items: [{
                        columnWidth: .6
                        ,defaults: {
                            msgTarget: 'under'
                        }
                        ,items: [{
                            fieldLabel: _('name')
                            ,name: 'name'
                            ,id: this.ident+'-name'
                            ,xtype: 'textfield'
                            ,anchor: '100%'
                            ,allowBlank: false
                        },{
                            xtype: MODx.expandHelp ? 'label' : 'hidden'
                            ,forId: this.ident+'-name'
                            ,html: _('name_desc')
                            ,cls: 'desc-under'
                        },{
                            fieldLabel: _('mime_type')
                            ,description: MODx.expandHelp ? '' : _('mime_type_desc')
                            ,name: 'mime_type'
                            ,id: this.ident+'-mime-type'
                            ,xtype: 'textfield'
                            ,anchor: '100%'
                            ,allowBlank: false
                        },{
                            xtype: MODx.expandHelp ? 'label' : 'hidden'
                            ,forId: this.ident+'-mime-type'
                            ,html: _('mime_type_desc')
                            ,cls: 'desc-under'
                        }]
                    },{
                        columnWidth: .4
                        ,defaults: {
                            msgTarget: 'under'
                        }
                        ,items: [{
                            fieldLabel: _('file_extensions')
                            ,description: MODx.expandHelp ? '' : _('file_extensions_desc')
                            ,name: 'file_extensions'
                            ,id: this.ident+'-file-extensions'
                            ,xtype: 'textfield'
                            ,anchor: '100%'
                            ,allowBlank: true
                        },{
                            xtype: MODx.expandHelp ? 'label' : 'hidden'
                            ,forId: this.ident+'-file-extensions'
                            ,html: _('file_extensions_desc')
                            ,cls: 'desc-under'
                        },{
                            xtype: 'combo-boolean'
                            ,fieldLabel: _('binary')
                            ,description: MODx.expandHelp ? '' : _('binary_desc')
                            ,name: 'binary'
                            ,hiddenName: 'binary'
                            ,id: this.ident+'-binary'
                            ,width: 100
                            ,inputValue: 0
                            ,value: 0

                        },{
                            xtype: MODx.expandHelp ? 'label' : 'hidden'
                            ,forId: this.ident+'-binary'
                            ,html: _('binary_desc')
                            ,cls: 'desc-under'
                        }]
                    }]
                },{
                    fieldLabel: _('description')
                    ,name: 'description'
                    ,id: 'modx-'+this.ident+'-description'
                    ,xtype: 'textarea'
                    ,anchor: '100%'
                    ,grow: true
                }]
            }, {
                title: 'Custom headers'
                ,items: [{
                    xtype: 'modx-grid-local'
                }]
            }]
        }]
        ,keys: []
    });
    MODx.window.CreateContentType.superclass.constructor.call(this,config);
};
Ext.extend(MODx.window.CreateContentType,MODx.Window);
Ext.reg('modx-window-content-type-create',MODx.window.CreateContentType);

MODx.ContentTypeHeaderGrid = function(config) {
    config = config || {};

    Ext.apply(config, {
        fields: ['id', 'name']
        ,store: new Ext.ArrayStore
        ,data: []
    });
    MODx.ContentTypeHeaderGrid.superclass.constructor.call(this, config);
};
Ext.extend(MODx.ContentTypeHeaderGrid, MODx.grid.LocalGrid, {

});
Ext.reg('modx-content-type-headers-grid', MODx.grid.LocalGrid);
