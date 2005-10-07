<?php /* HELPDESK $Id: do_item_aed.php,v 1.29 2005/09/08 02:16:03 pedroix Exp $ */
$del = dPgetParam( $_POST, 'del', 0 );
$item_id = dPgetParam( $_POST, 'item_id', 0 );
$do_task_log = dPgetParam( $_POST, 'task_log', 0 );
$new_item = !($item_id>0);
$updated_date = new CDate();
$udate = $updated_date->format( FMT_DATETIME_MYSQL );

if($do_task_log){

	//first update the status on to current helpdesk item.
	$hditem = new CHelpDeskItem();
	$hditem->load( $item_id );
	$hditem->item_updated = $udate;

	$new_status = dPgetParam( $_POST, 'item_status', 0 );

	if($new_status!=$hditem->item_status){
		$status_log_id = $hditem->log_status(11, $AppUI->_('changed from')
                                           . " \"".$AppUI->_($ist[$hditem->item_status])."\" "
                                           . $AppUI->_('to')
                                           . " \"".$AppUI->_($ist[$new_status])."\"");
		$hditem->item_status = $new_status;
		
		if (($msg = $hditem->store())) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
			$AppUI->redirect();
		} else {
      		$hditem->notify(STATUS_LOG, $status_log_id);
    	}
	} else {
	//Store the item_update no matter if the status was changed or not
		if (($msg = $hditem->store())) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
			$AppUI->redirect();
		}
	}
	//then create/update the task log
	$obj = new CHDTaskLog();

	if (!$obj->bind( $_POST )) {
		$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
		$AppUI->redirect();
	}

	if ($obj->task_log_date) {
		$date = new CDate( $obj->task_log_date );
		$obj->task_log_date = $date->format( FMT_DATETIME_MYSQL );
	}

	$AppUI->setMsg( 'Task Log' );

  $obj->task_log_costcode = $obj->task_log_costcode;
  if (($msg = $obj->store())) {
    $AppUI->setMsg( $msg, UI_MSG_ERROR );
    $AppUI->redirect();
  } else {
    $hditem->notify(TASK_LOG, $obj->task_log_id);
    $AppUI->setMsg( @$_POST['task_log_id'] ? 'updated' : 'added', UI_MSG_OK, true );
  }

	$AppUI->redirect("m=helpdesk&a=view&item_id=$item_id&tab=0");

} else {

	$hditem = new CHelpDeskItem();

	if ( !$hditem->bind( $_POST )) {
		$AppUI->setMsg( $hditem->error, UI_MSG_ERROR );
		$AppUI->redirect();
	}

	$AppUI->setMsg( 'Help Desk Item', UI_MSG_OK );

	if ($del) {
		$hditem->item_updated = $udate;
		if (($msg = $hditem->store())) 
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
		if (($msg = $hditem->delete())) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
		} else {
			$AppUI->setMsg( 'deleted', UI_MSG_OK, true );
			$hditem->log_status(17);
			$AppUI->redirect('m=helpdesk&a=list');
		}
	} else {
      	$status_log_id = $hditem->log_status_changes();
		if ($new_item) {
			$item_date = new CDate();
  			$idate = $item_date->format( FMT_DATETIME_MYSQL );
			$hditem->item_created = $idate;
			$hditem->item_updated = $udate;
		} else {
			$hditem->item_updated = $udate;
		}
		
		if (($msg = $hditem->store())) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
		} else {
		    if($new_item){
				$status_log_id = $hditem->log_status(0,$AppUI->_('Created'),$new_item);
		    }
	      	doWatchers(dPgetParam( $_POST, 'watchers', 0 ), $hditem);
			$AppUI->setMsg( $new_item ? ($AppUI->_('Help Desk Item') .' '. $AppUI->_('added')) : ($AppUI->_('Help Desk Item') . ' ' . $AppUI->_('updated')) , UI_MSG_OK, true );
			$AppUI->redirect('m=helpdesk&a=view&item_id='.$hditem->item_id);
		}
	}
}

function doWatchers($list, $hditem){
	global $AppUI;

	# Create the watcher list
	$watcherlist = split(',', $list);

	$sql = "SELECT user_id FROM helpdesk_item_watchers WHERE item_id=" . $hditem->item_id;
	$current_users = db_loadHashList($sql);
	$current_users = array_keys($current_users);

	# Delete the existing watchers as the list might have changed
	$sql = "DELETE FROM helpdesk_item_watchers WHERE item_id=" . $hditem->item_id;
	db_exec($sql);

	if (!$del){
		if($list){
			foreach($watcherlist as $watcher){
				$sql = "SELECT user_id, contact_email FROM users LEFT JOIN contacts ON user_contact = contact_id WHERE user_id=" . $watcher;
				$rows = db_loadlist($sql);
				foreach($rows as $row){
					# Send the notification that they've been added to a watch list.
					if(!in_array($row['user_id'],$current_users)){
						notifyWatchers($row['contact_email'], $hditem);
					}
				}

				$sql = "INSERT INTO helpdesk_item_watchers VALUES(". $hditem->item_id . "," . $watcher . ",'Y')";
				db_exec($sql);
			}
		}
	}
	
}

function notifyWatchers($address, $hditem){
	global $AppUI;

	$mail = new Mail;
	if($mail->ValidEmail($address)){
		if ($mail->ValidEmail($AppUI->user_email)) {
			$email = $AppUI->user_email;
		} else {
			$email = "dotproject@".$AppUI->cfg['site_domain'];
		}

		$mail->From("\"{$AppUI->user_first_name} {$AppUI->user_last_name}\" <{$email}>");
		$mail->To($address);
		$mail->Subject(
			$AppUI->_('Help Desk Item')." #".
			$hditem->item_id." ".
			$AppUI->_('Updated')." ".
			$hditem->item_title);
		$mail->Body(
			"Ticket #" . 
			$hditem->item_id . " " .
			$AppUI->_('IsNowWatched')
			);
		$mail->Send();
	}
}

?>