
<div id="actindoconfig"></div>
<script type="text/javascript">
Ext.onReady(function()
{ldelim}

  new Ext.Panel({ldelim}
    layout: 'anchor',
    items: [
      new Ext.Panel({ldelim}
        layout: 'form',
        width: 410,
        renderTo: Ext.get('actindoconfig'),
        frame: true,
        title: 'actindo ERP2 Plugin-Einstellungen',
        labelWidth: 200,
        style: 'margin: 10px;',
        items: [
          new Ext.Panel({ldelim}html:'Bitte geben Sie hier Ihre Zugangsdaten zu actindo ein, um Ihren xt:Commerce Veyton Enterprise Shop mit dem actindo ERP2 zu verbinden.', style: 'margin-bottom: 15px;'{rdelim}),
          new Ext.form.Hidden({ldelim}name:'do_action', value:'save_config'{rdelim}),
          new Ext.form.NumberField({ldelim}fieldLabel: '{$smarty.const.ACTINDO_MAND_ID_TITLE}', name:'actindo_mand_id', value:'{$actindo_mand_id}', emptyText: 'z.B. 10000', minLength:5, maxLength:5, minValue: 10000, maxValue: 69999{rdelim}),
          new Ext.form.TextField({ldelim}fieldLabel: '{$smarty.const.ACTINDO_USERNAME_TITLE}', name:'actindo_username', value:'{$actindo_username}', emptyText: 'z.B. test'{rdelim}),
          new Ext.form.TextField({ldelim}fieldLabel: '{$smarty.const.ACTINDO_PASSWORD_TITLE}', name:'actindo_password', value:'', inputType:'password'{rdelim}),
          new Ext.Panel({ldelim}
            layout: 'column',
            columns: 2,
            border: false,
            style: 'margin-top: 15px;',
            items: [
              new Ext.Panel({ldelim}
                columnWidth: 0.60,
                items: [ 
                  new Ext.Button({ldelim}text: "Abbrechen", handler:function()
                    {ldelim} Ext.getCmp('actindoformpanel{$smarty.const.ACTINDOIDPREFIX}').do_load( 'startpage' ); {rdelim}
                  {rdelim})
                ]
              {rdelim}),
              new Ext.Panel({ldelim}
                columnWidth: 0.40,
                items: [ 
                  new Ext.Button({ldelim}text: "Speichern &amp; verbinden", handler:function()
                    {ldelim} Ext.getCmp('actindoformpanel{$smarty.const.ACTINDOIDPREFIX}').getForm().doSubmit(); {rdelim}
                  {rdelim})
                ]
              {rdelim})
            ]
          {rdelim})
        ]
      {rdelim})
    ]
  {rdelim});
  
  
{rdelim});
</script>
