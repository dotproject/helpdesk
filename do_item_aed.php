<?php /* HELPDESK $Id: do_item_aed.php,v 1.2 2003/04/12 00:16:54 eddieajau Exp $ */
  #include( "../../misc/debug.php" );

#foreach( $_POST as $key => $value ) {
#	   writeDebug( "$value", "$key", __FILE__, __LINE__ );
#}

$del = dPgetParam( $_POST, 'del', 1 );
$item_id = dPgetParam( $_POST, 'item_id', 0 );

$hditem = new CHelpDeskItem();

if ( !$hditem->bind( $_POST )) {
	$AppUI->setMsg( $hditem->error, UI_MSG_ERROR );
	$AppUI->redirect();
}

writeDebug( $hditem->item_created, "hditem->item_created", __FILE__, __LINE__);

// convert dates to SQL format first
$hditem->item_created = db_unix2DateTime( $hditem->item_created );

writeDebug( $hditem->item_created, "hditem->item_created", __FILE__, __LINE__ );

//echo '<pre>';print_r($hditem);echo "</pre>";die;

$AppUI->setMsg( "HelpDesk item", UI_MSG_OK );
if ($del) {
	if (($msg = $hditem->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( "HelpDesk item deleted", UI_MSG_ALERT );
	}
} else {
	if (($msg = $hditem->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( $item_id ? 'updated' : 'inserted', UI_MSG_OK, true );
	}
}
$AppUI->redirect();
?>
