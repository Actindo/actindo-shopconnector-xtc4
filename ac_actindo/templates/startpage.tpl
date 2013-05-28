
  <div style="width: 800px; height: 278px; background-image: URL(../plugins/ac_actindo/images/actindo_header.jpg); background-repeat: no-repeat;"></div>
  
  <div style="width: 800px; height: 19px; background-image: URL(../plugins/ac_actindo/images/actindo_upper_aurora.jpg); background-repeat: repeat-x;"></div>
  <div style="width: 800px; height: 57px; background-image: URL(../plugins/ac_actindo/images/actindo_selector_bg.jpg); background-repeat: repeat-x; color: white;" id="subscribe0"></div>
  <div id="intro" style="background-image: URL(../plugins/ac_actindo/images/actindo_doublearrow.jpg); background-repeat: no-repeat; padding: 16px 0px 29px 44px; font-size: 10px; font-weight: bold; line-height: 14px;">Bitte w&auml;hlen<br/>Sie eine Option</div>
  <div id="checkboxes0" style="color: #e3ab39; font-size: 11px; font-weight: bold; line-height: 20px; padding: 8px 0px 9px 5px; background-image: URL(../plugins/ac_actindo/images/actindo_gradient.jpg); background-repeat: no-repeat;">
     <input name="do_action" type="radio" value="order" id="order"><label for="order">Ich m&ouml;chte actindo jetzt aktivieren</label><br/>
     <input name="do_action" type="radio" value="already_customer" id="already_customer"><label for="already_customer">Ich bin bereits actindo Kunde</label>
  </div>
  <div id="checkboxes1" style="color: #e3ab39; font-size: 11px; font-weight: bold; line-height: 20px; padding: 8px 0px 29px 5px; background-image: URL(../plugins/ac_actindo/images/actindo_gradient.jpg); background-repeat: no-repeat;">
     <input name="do_action" type="radio" value="order_test" id="order_test"><label for="order_test">Ich m&ouml;chte 30 Tage kostenlos testen</label>
  </div>
  <div style="width: 800px; height: 19px; background-image: URL(../plugins/ac_actindo/images/actindo_lower_aurora.jpg); background-repeat: repeat-x;"></div>
    
  <div style="width: 800px; height: 409px; background-image: URL(../plugins/ac_actindo/images/actindo_screens.jpg); background-repeat: no-repeat;"></div>
  <div style="width: 800px; height: 450px; background-image: URL(../plugins/ac_actindo/images/actindo_features.jpg); background-repeat: no-repeat;"></div>

  <div id="actindo_power_test" style="float: left; width: 202px; height: 260px; background-position: right bottom; background-image: URL(../plugins/ac_actindo/images/actindo_power_pricing.jpg); background-repeat: no-repeat;"></div>
  <div id="actindo_pro_test" style="float: left; width: 202px; height: 260px; background-position: right bottom; background-image: URL(../plugins/ac_actindo/images/actindo_pro_pricing.jpg); background-repeat: no-repeat;"></div>
  <div id="actindo_business_test" style="float: left; width: 200px; height: 260px; background-position: right bottom; background-image: URL(../plugins/ac_actindo/images/actindo_business_pricing.jpg); background-repeat: no-repeat;"></div>
  <div id="actindo_enterprise_test" style="float: left; width: 196px; height: 260px; background-position: right bottom; background-image: URL(../plugins/ac_actindo/images/actindo_enterprise_pricing.jpg); background-repeat: no-repeat;"></div>

  <div style="clear:both; height: 0%;"></div>

  <div style="width: 800px; height: 19px; background-image: URL(../plugins/ac_actindo/images/actindo_upper_aurora.jpg); background-repeat: repeat-x;"></div>
  <div style="width: 800px; height: 57px; background-image: URL(../plugins/ac_actindo/images/actindo_selector_bg.jpg); background-repeat: repeat-x; color: white;" id="subscribe1"></div>
  <div id="intro1" style="background-image: URL(../plugins/ac_actindo/images/actindo_doublearrow.jpg); background-repeat: no-repeat; padding: 16px 0px 29px 44px; font-size: 10px; font-weight: bold; line-height: 14px;">Bitte w&auml;hlen<br/>Sie eine Option</div>
  <div id="checkboxes01" style="color: #e3ab39; font-size: 11px; font-weight: bold; line-height: 20px; padding: 8px 0px 9px 5px; background-image: URL(../plugins/ac_actindo/images/actindo_gradient.jpg); background-repeat: no-repeat;">
     <input name="do_action" type="radio" value="order" id="order1"><label for="order1">Ich m&ouml;chte actindo jetzt aktivieren</label><br/>
     <input name="do_action" type="radio" value="already_customer" id="already_customer1"><label for="already_customer1">Ich bin bereits actindo Kunde</label>
  </div>
  <div id="checkboxes11" style="color: #e3ab39; font-size: 11px; font-weight: bold; line-height: 20px; padding: 8px 0px 29px 5px; background-image: URL(../plugins/ac_actindo/images/actindo_gradient.jpg); background-repeat: no-repeat;">
     <input name="do_action" type="radio" value="order_test" id="order_test1"><label for="order_test1">Ich m&ouml;chte 30 Tage kostenlos testen</label>
  </div>
  <div style="width: 800px; height: 19px; background-image: URL(../plugins/ac_actindo/images/actindo_lower_aurora.jpg); background-repeat: repeat-x;"></div>

  <div style="margin-bottom: 50px; width: 800px; height: 242px; background-image: URL(../plugins/ac_actindo/images/actindo_footer.jpg); background-repeat: repeat-x;"></div>

<script type="text/javascript">
{literal}
Ext.onReady(function()
{
  new Ext.Panel({
    layout: 'form',
    cls: '',
    baseCls: '',
    border: false,
    renderTo: Ext.get('subscribe0'),
    items: [
      new Ext.Panel({
        layout: 'column',
        columns: 5,
        border: false,
        cls: '',
        baseCls: '',
        anchor: '100% 100%',
        items: [
          new Ext.Panel({
            cls: '',
            baseCls: '',
            contentEl: 'intro',
            columnWidth: 0.18
          }),
          new Ext.Panel({
            cls: '',
            baseCls: '',
            contentEl: 'checkboxes0',
            columnWidth: 0.32
          }),
          new Ext.Panel({
            cls: '',
            baseCls: '',
            columnWidth: 0.008,
            html: '&nbsp'
          }),   
          new Ext.Panel({
            cls: '',
            baseCls: '',
            contentEl: 'checkboxes1',
            columnWidth: 0.332
          }),
          new Ext.Panel({
            cls: '',
            baseCls: '',
            columnWidth: 0.006,
            html: '&nbsp'
          }),   
          new Ext.Panel({
            layout: 'form',
            cls: '',
            baseCls: '',
            columnWidth: 0.154,
            style: 'background-image: URL(../plugins/ac_actindo/images/actindo_doublearrow.jpg); background-repeat: no-repeat; padding: 20px 0px 29px 50px; font-size: 10px; font-weight: bold; line-height: 14px;',
            items: [
              new Ext.Button({ 
                text:'<b>Weiter</b>',
                handler: function()
                {
                  {/literal}
                  Ext.getCmp('actindoformpanel{$smarty.const.ACTINDOIDPREFIX}').getForm().doSubmit();
                  {literal}
                }
              })
            ]
          })
        ]
      })
    ]
  });
  
  new Ext.Panel({
    layout: 'form',
    cls: '',
    baseCls: '',
    renderTo: Ext.get('subscribe1'),
    items: [
      new Ext.Panel({
        layout: 'column',
        columns: 5,
        border: false,
        cls: '',
        baseCls: '',
        anchor: '100% 100%',
        items: [
          new Ext.Panel({
            cls: '',
            baseCls: '',
            contentEl: 'intro1',
            columnWidth: 0.18
          }),
          new Ext.Panel({
            cls: '',
            baseCls: '',
            contentEl: 'checkboxes01',
            columnWidth: 0.32
          }),
          new Ext.Panel({
            cls: '',
            baseCls: '',
            columnWidth: 0.008,
            html: '&nbsp'
          }),   
          new Ext.Panel({
            cls: '',
            baseCls: '',
            contentEl: 'checkboxes11',
            columnWidth: 0.332
          }),
          new Ext.Panel({
            cls: '',
            baseCls: '',
            columnWidth: 0.006,
            html: '&nbsp'
          }),   
          new Ext.Panel({
            layout: 'form',
            cls: '',
            baseCls: '',
            columnWidth: 0.154,
            style: 'background-image: URL(../plugins/ac_actindo/images/actindo_doublearrow.jpg); background-repeat: no-repeat; padding: 20px 0px 29px 50px; font-size: 10px; font-weight: bold; line-height: 14px;',
            items: [
              new Ext.Button({ 
                text:'<b>Weiter</b>',
                handler: function()
                {
                  {/literal}
                  Ext.getCmp('actindoformpanel{$smarty.const.ACTINDOIDPREFIX}').getForm().doSubmit();
                  {literal}
                }
              })
            ]
          })
        ]
      })
    ]
  });
});
{/literal}
</script>