<?php /* HELPDESK $Id: do_item_aed.php,v 1.4 2004/04/15 17:32:01 adam Exp $ */

$del = dPgetParam( $_POST, 'del', 0 );
$item_id = dPgetParam( $_POST, 'item_id', 0 );
$notify = dPgetParam( $_POST, 'notify', 1 );

$hditem = new CHelpDeskItem();

if ( !$hditem->bind( $_POST )) {
	$AppUI->setMsg( $hditem->error, UI_MSG_ERROR );
	$AppUI->redirect();
}

//print "<pre><font color=red>"; print_r( $hditem ); print "</font></pre>\n";
//print "<pre><font color=red>"; print_r( $_POST ); print "</font></pre>\n";

$AppUI->setMsg( "Help Desk item", UI_MSG_OK );

if ($del) {
	if (($msg = $hditem->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( "Help Desk item deleted", UI_MSG_ALERT );
	}
} else {
	if (($msg = $hditem->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( $item_id ? 'updated' : 'inserted', UI_MSG_OK, true );

    if ($notify) {
      $hditem->notify();
    }
	}
}

//die( "Debugging output enabled in do_item_aed.php" );

$AppUI->redirect();
?>
