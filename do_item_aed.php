<?php /* HELPDESK $Id: do_item_aed.php,v 1.21 2004/05/28 15:51:29 agorski Exp $ */
$del = dPgetParam( $_POST, 'del', 0 );
$item_id = dPgetParam( $_POST, 'item_id', 0 );
$do_task_log = dPgetParam( $_POST, 'task_log', 0 );
$new_item = !($item_id>0);

if($do_task_log=="1"){

	//first update the status on to current helpdesk item.
	$hditem = new CHelpDeskItem();
	$hditem->load( $item_id );

	$new_status = dPgetParam( $_POST, 'item_status', 0 );

	if($new_status!=$hditem->item_status){
		$status_log_id = $hditem->log_status(11, $AppUI->_('helpdeskChangedFrom')
                                           . " \"{$ist[$hditem->item_status]}\" "
                                           . $AppUI->_('helpdeskChangeTo')
                                           . "\"{$ist[$new_status]}\"");
		$hditem->item_status = $new_status;

		if (($msg = $hditem->store())) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
			$AppUI->redirect();
		} else {
      $hditem->notify(STATUS_LOG, $status_log_id);
    }
	}

	//then create/update the task log
	$obj = new CTaskLog();

	if (!$obj->bind( $_POST )) {
		$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
		$AppUI->redirect();
	}

	if ($obj->task_log_date) {
		$date = new CDate( $obj->task_log_date );
		$obj->task_log_date = $date->format( FMT_DATETIME_MYSQL );
	}

	$AppUI->setMsg( 'helpdeskTaskLog' );

  $obj->task_log_costcode = $obj->task_log_costcode;
  if (($msg = $obj->store())) {
    $AppUI->setMsg( $msg, UI_MSG_ERROR );
    $AppUI->redirect();
  } else {
    $hditem->notify(TASK_LOG, $obj->task_log_id);
    $AppUI->setMsg( @$_POST['task_log_id'] ? $AppUI->_('updated') : $AppUI->_('added'), UI_MSG_OK, true );
  }

	$AppUI->redirect("m=helpdesk&a=view&item_id=$item_id&tab=0");

} else {

	$hditem = new CHelpDeskItem();

	if ( !$hditem->bind( $_POST )) {
		$AppUI->setMsg( $hditem->error, UI_MSG_ERROR );
		$AppUI->redirect();
	}

	$AppUI->setMsg( $AppUI->_('helpdeskHDItem'), UI_MSG_OK );

	if ($del) {
		if (($msg = $hditem->delete())) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
		} else {
			$AppUI->setMsg( $AppUI->_('deleted'), UI_MSG_OK, true );
			$hditem->log_status(17);
			$AppUI->redirect('m=helpdesk&a=list');
		}
	} else {
    $status_log_id = $hditem->log_status_changes();

		if (($msg = $hditem->store())) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
		} else {
      if($new_item){
        $status_log_id = $hditem->log_status(0,$AppUI->_('helpdeskCreated'));
      }

      $hditem->notify(STATUS_LOG, $status_log_id);

			$AppUI->setMsg( $new_item ? $AppUI->_('added') : $AppUI->_('updated') , UI_MSG_OK, true );
			$AppUI->redirect('m=helpdesk&a=view&item_id='.$hditem->item_id);
		}
	}
}

?>
