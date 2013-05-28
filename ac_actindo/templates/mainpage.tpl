<div id="actindomainpagepanel" style="height: 100%; width: 100%;"></div> 
<script type="text/javascript">
Ext.onReady(function()
{ldelim}
  new Ext.Panel({ldelim}
    layout: 'fit',
    border: false,
    bodyBorder: false,
    renderTo: Ext.get('actindomainpagepanel'),
    id: 'IFRPanelContainer',
    style: 'width: 100%; height: 100%;',
    tbar: [
      {ldelim}
        id: 'actindostart',
        text: 'actindo Shop-ERP starten',
        cls: 'x-btn-text-icon',
        icon: '../plugins/ac_actindo/images/actindo-16x16.png',
        handler: login_clicked
      {rdelim},
      '->',
      {ldelim}
        id: 'settings',
        text: 'Einstellungen',
        cls: 'x-btn-text-icon',
        icon: '../plugins/ac_actindo/images/settings.gif',
        handler: function() {ldelim} actindopanel.do_load( 'config' ); {rdelim}
      {rdelim}
    ],
    items: [
      new Ext.Panel({ldelim}
        border: false,
        id: 'IFRPanel',
        bodyBorder: false,
        style: 'border-width: 0px;',
        html: '<iframe src="{$actindo_baseurl}?USESESSION={$actindosession}&page=newfakt/shopwidgetsmain&_display_login=0" width="100%" height="100%" frameborder="no" id="IFR"></iframe>'
      {rdelim})
    ]
  {rdelim});

   var h = Ext.getCmp('IFRPanelContainer').getInnerHeight();
   Ext.getCmp('IFRPanel').setHeight( h );
   document.getElementById('IFR').style.height =  h+'px';
{rdelim});

function login_clicked( )
{ldelim}
  login_win = window.open( '{$actindo_baseurl}?USESESSION={$actindosession}', 'actindo', 'width=1014, height=705, screenX=0, screenY=0,resizable=yes' );
  login_win.focus( );
{rdelim}
</script>
