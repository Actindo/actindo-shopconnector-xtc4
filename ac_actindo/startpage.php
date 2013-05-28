<?php

include( '../../xtFramework/admin/main.php' );
require_once( 'classes/ac_actindo.php' );

$actindo = new actindo();

if( isset($_REQUEST['__actindoidprefix']) )
  $id = (int)$_REQUEST['__actindoidprefix'];
else
  $id = rand( 0, getrandmax() );
$GLOBALS['__actindoidprefix'] = $id;
define( 'ACTINDOIDPREFIX', $id );


if( isset($_REQUEST['page']) )
  echo $actindo->page( $_REQUEST['page'] );
else
{
?>
<script type="text/javascript">
var actindopanel;
var autoloadpanel;
Ext.onReady( function()
{
  new Ext.Panel({
    renderTo: Ext.get('actindopanel<?php echo $GLOBALS['__actindoidprefix']; ?>'),
    id: 'ACTPanelContainer<?php echo $GLOBALS['__actindoidprefix']; ?>',
    autoScroll: false,
    border: false,
    layout: 'fit',
    waitMsg: 'TEST',
    waitMsgTarget: true,
    style: 'height: 100%; width: 100%;',
    defaults: {
      border: false
    },
    items: [
      actindopanel = new Ext.form.FormPanel({
        url: '<?php echo $_SERVER['PHP_SELF']; ?>?action=submit',
        method: 'POST',
        id: 'actindoformpanel<?php echo $GLOBALS['__actindoidprefix']; ?>',
        border: false,
        baseParams: {page: 'startpage' },
        items: [
          autoloadpanel=new Ext.Panel({
            border: false,
            anchor: '100% 100%',
            autoLoad: {url: '<?php echo $_SERVER['PHP_SELF']; ?>?page=startpage&__actindoidprefix=<?php echo $GLOBALS['__actindoidprefix']; ?>', scripts: true},
            autoScroll: true,
            layout: 'fit',
            id: 'actindoautoloadpanel<?php echo $GLOBALS['__actindoidprefix']; ?>'
          })
        ]
      })
    ]
  });
  actindopanel.getForm().doSubmit = function( )
  {
    this.submit({
      success: function(form, action)
      {
        try
        {
          if( action && action.result )
          {
            if( action.result.message )
            {
              Ext.MessageBox.show({
                buttons: Ext.MessageBox.OK,
                closable: true,
                icon: Ext.MessageBox.INFO,
                msg: action.result.message,
                title: 'Erfolgreich',
                fn: function()
                {
                  if( action.result.load_page )
                  {
                    actindopanel.do_load( action.result.load_page );
                  }
                }
              });
            }
            else if( action.result.load_page )
            {
              actindopanel.do_load( action.result.load_page );
            }
          }
        }
        catch(e) {  }
      },
      failure: function(form, action)
      {
        try
        {
          if( action && action.result && action.result.error )
          {
            Ext.MessageBox.alert( 'Fehler', '<b>Es ist ein Fehler aufgetreten:</b><br/>\n<br/>\n'+action.result.error );
          }
        }
        catch(e) {  }
      },
      clientValidation: true
    });
  }

  actindopanel.do_load = function( pagename )
  {
    autoloadpanel.getUpdater().update({ url: '<?php echo $_SERVER['PHP_SELF']; ?>?page='+pagename+'&__actindoidprefix=<?php echo $GLOBALS['__actindoidprefix']; ?>', method: 'GET', scripts: true });
    actindopanel.getForm().baseParams.page = pagename;
  }

var h = Ext.getCmp('ACTPanelContainer<?php echo $GLOBALS['__actindoidprefix']; ?>').getInnerHeight();
Ext.getCmp('actindoformpanel<?php echo $GLOBALS['__actindoidprefix']; ?>').setHeight( h-25 );

});
</script>
<div id="actindopanel<?php echo $GLOBALS['__actindoidprefix'];?>" style="height: 100%; width: 100%;">
<?php
}

?>

