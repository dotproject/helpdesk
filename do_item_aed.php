<?php /* HELPDESK $Id: do_item_aed.php,v 1.2 2004/01/19 17:56:53 mike Exp $ */

$del = dPgetParam( $_POST, 'del', 1 );
$item_id = dPgetParam( $_POST, 'item_id', 0 );

$hditem = new CHelpDeskItem();

if ( !$hditem->bind( $_POST )) {
	$AppUI->setMsg( $hditem->error, UI_MSG_ERROR );
	$AppUI->redirect();
}

#print "<pre><font color=red>"; print_r( $hditem ); print "</font></pre>\n";
#print "<pre><font color=red>"; print_r( $_POST ); print "</font></pre>\n";

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

#die( "Debugging output enabled in do_item_aed.php" );

$AppUI->redirect();
?>
