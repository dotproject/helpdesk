<?php /* HELPDESK $Id: do_item_aed.php,v 1.8 2004/04/21 23:23:47 bloaterpaste Exp $ */

$del = dPgetParam( $_POST, 'del', 0 );
$item_id = dPgetParam( $_POST, 'item_id', 0 );
$notify = dPgetParam( $_POST, 'notify', 1 );
$old_status = dPgetParam( $_POST, 'old_status', 0 );

$hditem = new CHelpDeskItem();

if ( !$hditem->bind( $_POST )) {
	$AppUI->setMsg( $hditem->error, UI_MSG_ERROR );
	$AppUI->redirect();
}

$AppUI->setMsg( "Help Desk item", UI_MSG_OK );

if ($del) {
	if (($msg = $hditem->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( "Help Desk item deleted", UI_MSG_OK );
    $AppUI->redirect('', -1);
	}
} else {
	if (($msg = $hditem->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( $item_id ? 'updated' : 'inserted', UI_MSG_OK, true );

    if ($notify) {
      $hditem->notify();
    }

    $hditem->log_status($old_status);

    if ($item_id) {
      $AppUI->redirect();
    } else {
      $AppUI->redirect('m=helpdesk&a=view&item_id='.$hditem->item_id);
    }
	}
}

?>
