<?php /* HELPDESK $Id: do_item_aed.php,v 1.10 2004/04/23 18:11:49 agorski Exp $ */

$del = dPgetParam( $_POST, 'del', 0 );
$item_id = dPgetParam( $_POST, 'item_id', 0 );
$new_item = !($item_id>0);

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
		$hditem->log_status(17);
		$AppUI->redirect('', -1);
	}
} else {
	$hditem->log_status_changes();
	
	if (($msg = $hditem->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		if($new_item){
			$hditem->item_id = mysql_insert_id();
			$hditem->log_status(0,"Created");
		}
	    $AppUI->setMsg( $new_item ? 'updated' : 'inserted', UI_MSG_OK, true );

	      $AppUI->redirect('m=helpdesk&a=view&item_id='.$hditem->item_id);
	}
}

?>
