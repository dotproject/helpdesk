<?php /* HELPDESK $Id: do_item_aed.php,v 1.1.1.1 2004/01/14 23:05:22 root Exp $ */
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

// convert dates to SQL format first
/*
$hditem->item_modified = db_unix2DateTime( $hditem->item_modified );
if( $item_id == 0 ) {
  $hditem->item_created  = db_unix2DateTime( $hditem->item_created );
} else {
  unset( $hditem->item_created );
}
*/
//print "<pre><font color=red>"; print_r( $hditem ); print "</font></pre>\n";

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
