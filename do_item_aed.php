<?php /* HELPDESK $Id: do_item_aed.php,v 1.13 2004/04/26 20:16:33 bloaterpaste Exp $ */

$del = dPgetParam( $_POST, 'del', 0 );
$item_id = dPgetParam( $_POST, 'item_id', 0 );
$do_task_log = dPgetParam( $_POST, 'task_log', 0 );
$new_item = !($item_id>0);

if($do_task_log=="1"){

	$obj = new CTaskLog();

	if (!$obj->bind( $_POST )) {
		$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
		$AppUI->redirect();
	}

	if ($obj->task_log_date) {
		$date = new CDate( $obj->task_log_date );
		$obj->task_log_date = $date->format( FMT_DATETIME_MYSQL );
	}

	$AppUI->setMsg( 'Task Log' );
	if ($del) {
		if (($msg = $obj->delete())) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
		} else {
			$AppUI->setMsg( "deleted", UI_MSG_ALERT );
		}
	} else {
		$obj->task_log_costcode = $obj->task_log_costcode;
		if (($msg = $obj->store())) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
			$AppUI->redirect();
		} else {
			$AppUI->setMsg( @$_POST['task_log_id'] ? 'updated' : 'inserted', UI_MSG_OK, true );
		}
	}

	$AppUI->redirect("m=helpdesk&a=view&item_id=$item_id&tab=0");

} else {

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

			$AppUI->setMsg( $new_item ? 'inserted' : 'updated' , UI_MSG_OK, true );
			$AppUI->redirect('m=helpdesk&a=view&item_id='.$hditem->item_id);
		}
	}
}

?>
