  <div style="width: 800px; height: 278px; background-image: URL(../plugins/ac_actindo/images/actindo_header2.jpg); background-repeat: no-repeat;"></div>
  
  <div style="width: 800px; height: auto;" id="upper_selector"> 
      <div style="width: 800px; height: 19px; background-image: URL(../plugins/ac_actindo/images/actindo_upper_aurora.jpg); background-repeat: repeat-x;"></div>
      <div style="width: 800px; height: 57px; background-image: URL(../plugins/ac_actindo/images/actindo_selector_bg.jpg); background-repeat: repeat-x; color: white;" id="subscribe0"></div>
      <div id="intro" style="background-image: URL(../plugins/ac_actindo/images/actindo_doublearrow.jpg); background-repeat: no-repeat; padding: 16px 0px 29px 44px; font-size: 10px; font-weight: bold; line-height: 14px;">Bitte w&auml;hlen<br/>Sie ein Paket</div>
      <div id="checkboxes0" style="color: #e3ab39; font-size: 11px; font-weight: bold; line-height: 20px; padding: 8px 0px 9px 5px; background-image: URL(../plugins/ac_actindo/images/actindo_gradient.jpg); background-repeat: no-repeat;">
         <input name="r[want_add_feat_local][]" type="radio" value="ACTINDOPWR" id="want_feat_pwr"><label for="want_feat_pwr">actindo Power</label><br/>
         <input name="r[want_add_feat_local][]" type="radio" value="ACTINDOPRO" id="want_feat_pro"><label for="want_feat_pro">actindo Pro</label>
      </div>
      <div id="checkboxes1" style="color: #e3ab39; font-size: 11px; font-weight: bold; line-height: 20px; padding: 8px 0px 29px 5px; background-image: URL(../plugins/ac_actindo/images/actindo_gradient.jpg); background-repeat: no-repeat;">
         <input name="r[want_add_feat_local][]" type="radio" value="ACTINDOBUS" id="want_feat_bus"><label for="want_feat_bus">actindo Business</label><br/>
         <input name="r[want_add_feat_local][]" type="radio" value="ACTINDOENT" id="want_feat_ent"><label for="want_feat_ent">actindo Enterprise</label>
      </div>
      <div style="width: 800px; height: 19px; background-image: URL(../plugins/ac_actindo/images/actindo_lower_aurora.jpg); background-repeat: repeat-x;"></div>
  </div>
    
  <div style="margin-top: 15px;">
    <iframe src="{$address}&fromXT=true" style="width: 800px; height: 800px;" frameborder="0" id="order_frame"></iframe>
  </div>
 
  <div style="width: 800px; height: auto;" id="lower_selector">   
      <div style="width: 800px; height: 19px; background-image: URL(../plugins/ac_actindo/images/actindo_upper_aurora.jpg); background-repeat: repeat-x;"></div>
      <div style="width: 800px; height: 57px; background-image: URL(../plugins/ac_actindo/images/actindo_selector_bg.jpg); background-repeat: repeat-x; color: white;" id="subscribe1"></div>
      <div id="intro1" style="background-image: URL(../plugins/ac_actindo/images/actindo_doublearrow.jpg); background-repeat: no-repeat; padding: 16px 0px 29px 44px; font-size: 10px; font-weight: bold; line-height: 14px;">Bitte w&auml;hlen<br/>Sie eine Option</div>
      <div id="checkboxes01" style="color: #e3ab39; font-size: 11px; font-weight: bold; line-height: 20px; padding: 8px 0px 9px 5px; background-image: URL(../plugins/ac_actindo/images/actindo_gradient.jpg); background-repeat: no-repeat;">
         <input name="r[want_add_feat_local][]" type="radio" value="ACTINDOPWR" id="want_feat_pwr1"><label for="want_feat_pwr1">actindo Power</label><br/>
         <input name="r[want_add_feat_local][]" type="radio" value="ACTINDOPRO" id="want_feat_pro1"><label for="want_feat_pro1">actindo Pro</label>
      </div>
      <div id="checkboxes11" style="color: #e3ab39; font-size: 11px; font-weight: bold; line-height: 20px; padding: 8px 0px 29px 5px; background-image: URL(../plugins/ac_actindo/images/actindo_gradient.jpg); background-repeat: no-repeat;">
         <input name="r[want_add_feat_local][]" type="radio" value="ACTINDOBUS" id="want_feat_bus1"><label for="want_feat_bus1">actindo Business</label><br/>
         <input name="r[want_add_feat_local][]" type="radio" value="ACTINDOENT" id="want_feat_ent1"><label for="want_feat_ent1">actindo Enterprise</label>
      </div>
      <div style="width: 800px; height: 19px; background-image: URL(../plugins/ac_actindo/images/actindo_lower_aurora.jpg); background-repeat: repeat-x;"></div>
  </div>
  <div style="margin-bottom: 50px; width: 800px; height: 242px; background-image: URL(../plugins/ac_actindo/images/actindo_footer.jpg); background-repeat: repeat-x;"></div>

<script type="text/javascript">
{literal}

function do_submit()
{
  var checkboxes = document.getElementsByName('r[want_add_feat_local][]');
  for( var i = 0; i < checkboxes.length; i++ )
  {
    if( checkboxes[i].checked ) {
      var package = checkboxes[i].value;
    }
  }
{/literal}
  var URI = '{$actindo_baseurl}subscribe.php/step2?submit_step=1&__is_test=1&'+encodeURIComponent('r[want_add_feat_local][]')+'='+encodeURIComponent(package)+'&'+encodeURIComponent('r[package_wg_id]')+'=1753069433&'+encodeURIComponent('r[wg_id]')+'=1902484084&fromXT=true';
{literal}
  document.getElementById('order_frame').src=URI;
  document.getElementById('order_frame').style.height='1240px';
  document.getElementById('lower_selector').style.display='none';
  document.getElementById('upper_selector').style.display='none';
}


Ext.onReady(function()
{
  new Ext.Panel({
    layout: 'form',
    cls: '',
    baseCls: '',
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
        //        Ext.dump( actindopanel.getForm() );
                  do_submit();
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
        //        Ext.dump( actindopanel.getForm() );
                  do_submit();
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
