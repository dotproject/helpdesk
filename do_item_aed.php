<?php /* HELPDESK $Id: do_item_aed.php,v 1.32 2006/08/14 23:07:06 theideaman Exp $ */

if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

require_once( $AppUI->getSystemClass('dp') );
require_once( $AppUI->getSystemClass('libmail') );
include_once (DP_BASE_DIR."/modules/helpdesk/config.php");


$del = dPgetParam($_POST, 'del', 0);
$item_id = dPgetParam($_POST, 'item_id', 0);
$do_task_log = dPgetParam($_POST, 'task_log', 0);
$new_item = !($item_id>0);
$updated_date = new CDate();
$udate = $updated_date->format(FMT_DATETIME_MYSQL);
$deadline=dPgetParam( $_POST, 'item_deadline', 0 );
$notify_all=dPgetParam($_POST, 'item_notify', 0);

$new_item_title = dPgetParam($_POST, 'item_title', 0);
$new_calltype = dPgetParam( $_POST, 'item_calltype', 0 );
$new_assignee = dPgetParam($_POST, 'item_assigned_to', 0);
$new_requestor = dPgetParam($_POST, 'item_requestor', 0);
$new_source = dPgetParam($_POST, 'item_source', 0);
$new_priority = dPgetParam($_POST, 'item_priority', 0);
$new_severity = dPgetParam($_POST, 'item_severity', 0);
$new_application = dPgetParam($_POST, 'item_application', 0);
$new_os = dPgetParam($_POST, 'item_os', 0);
$new_status = dPgetParam($_POST, 'item_status', 0);

if ($do_task_log) {

	//This side of "if/else" is processed if modifying / creating a task

	//first update the status on to current helpdesk item.
	$hditem = new CHelpDeskItem();
	$hditem->load($item_id);
	$hditem->item_updated = $udate;

	$ict = dPgetSysVal( 'HelpDeskCallType' );
	$ics = dPgetSysVal( 'HelpDeskSource' );
	$ios = dPgetSysVal( 'HelpDeskOS' );
	$iap = dPgetSysVal( 'HelpDeskApplic' );
	$ipr = dPgetSysVal( 'HelpDeskPriority' );
	$isv = dPgetSysVal( 'HelpDeskSeverity' );
	$ist = dPgetSysVal( 'HelpDeskStatus' );
	$isa = dPgetSysVal( 'HelpDeskAuditTrail' );

	$log_status_msg='';
	$update_item=0;
	$oldstatus = $hditem->item_status;
	$users = getAllowedUsers();

	// Item Status update check
	if ($new_status!=$hditem->item_status) {
		$log_status_msg .= $hditem->log_status(11, $AppUI->_($ist[$hditem->item_status]),
			 $AppUI->_($ist[$new_status]))."\n";
		$hditem->item_status = $new_status;
		
		if (($msg = $hditem->store())) {
			$AppUI->setMsg($msg, UI_MSG_ERROR);
			$AppUI->redirect();
		}
	} else {
		//Store the item_update no matter if the status was changed or not
		if (($msg = $hditem->store())) {
			$AppUI->setMsg( $msg, UI_MSG_ERROR );
			$AppUI->redirect();
		}
	}

	// Assignee update check
	$newass = trim($new_assignee);
	$oldass = trim($hditem->item_assigned_to);
	if ($newass!=$oldass) {
		$log_status_msg .= $hditem->log_status(5, $AppUI->_($users[$hditem->item_assigned_to]),
			$AppUI->_($users[$new_assignee]));
		$hditem->item_assigned_to = $new_assignee;
		
		if (($msg = $hditem->store())) {
			$AppUI->setMsg($msg, UI_MSG_ERROR);
			$AppUI->redirect();
		}
    	}

	//then create/update the task log
	$obj = new CHDTaskLog();

	if (!$obj->bind($_POST)) {
		$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
		$AppUI->redirect();
	}

	if ($obj->task_log_date) {
		$date = new CDate($obj->task_log_date);
		$obj->task_log_date = $date->format(FMT_DATETIME_MYSQL);
	}

	$obj->task_log_reference = $hditem->item_status;
	$AppUI->setMsg('Helpdesk Task Log');
	$obj->task_log_costcode = $obj->task_log_costcode;

	if (($msg = $obj->store())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
		$AppUI->redirect();
	} else {
		$logmsg = $AppUI->_('Summary') . "\t:\t" . $obj->task_log_name . "\n";	
		$logmsg .= $AppUI->_('Description') . "\t:\t" . $obj->task_log_description . "\n";
		if ( ($oldstatus!=$hditem->item_status) || ($oldass!=$hditem->item_assigned_to) ) {
			$logmsg .= $log_status_msg."\n";
			$hditem->notify(STATUSTASK_LOG, $logmsg);
		} else {
			$hditem->notify(TASK_LOG, $logmsg);
		}
		$AppUI->setMsg(@$_POST['task_log_id'] ? 'Updated' : 'Added', UI_MSG_OK, true);
	}
  	$AppUI->redirect("m=helpdesk&a=view&item_id=$item_id&tab=0");

} else {
		//This side of "if/else" is processed if modifying the helpdesk issue

		$hditem = new CHelpDeskItem();

		if (!$hditem->bind($_POST)) {
			$AppUI->setMsg($hditem->error, UI_MSG_ERROR);
			$AppUI->redirect();
		}

		$AppUI->setMsg('Help Desk Item', UI_MSG_OK);

		if ($del) {
			$hditem->item_updated = $udate;
			if (($msg = $hditem->store())) {
				$AppUI->setMsg($msg, UI_MSG_ERROR);
			}
			$hditem->clearReminder(true);
			if (($msg = $hditem->delete())) {
				$AppUI->setMsg($msg, UI_MSG_ERROR);
			} else {
				$AppUI->setMsg('deleted', UI_MSG_OK, true);
				$hditem->log_status(18);
				$AppUI->redirect('m=helpdesk&a=list');
			}
		} else {
			// If there are changes pending, log what changes are to be made
			// Must be done before vaules are changed or no changes will be logged
			// Notifications will be sent out based on what changes are made
			$status_log_msg = $hditem->log_status_changes();
			if ($new_item) {
				$item_date = new CDate();
	  			$idate = $item_date->format(FMT_DATETIME_MYSQL);
				$hditem->item_created = $idate;
				$hditem->item_updated = $udate;
			} else {
				$hditem->item_updated = $udate;
			}

			if ( (!strcmp($deadline,"N/A")==0) &&
				 (!strcmp($deadline,"0000-00-00 00:00:00")==0) &&
					 ($deadline!="") ) {
				$dl=new CDate($deadline);
				$dl->setTime(dPgetConfig('cal_day_end'),00,00);
				$hditem->item_deadline=$dl->format( FMT_DATETIME_MYSQL );
			} else {
				$hditem->clearReminder(true);
				$hditem->item_deadline="";
			}

			if (($msg = $hditem->store())) {
				$AppUI->setMsg($msg, UI_MSG_ERROR);
			} else {
				if ($new_item) {
					$status_log_id = $hditem->log_status(0,$AppUI->_('Created'),$new_item);
					// Lets create a task log for the item creation:
					$obj = new CHDTaskLog();
					$new_item_log = array('task_log_id' => 0,'task_log_help_desk_id' => $hditem->item_id, 'task_log_creator' => $AppUI->user_id, 'task_log_name' => 'Item Created: '.$_POST['item_title'], 'task_log_date' => $hditem->item_created, 'task_log_description' => $_POST['item_title'], 'task_log_hours' => $_POST['task_log_hours'], 'task_log_costcode' => $_POST['task_log_costcode']);
					if (!$obj->bind( $new_item_log )) {
						$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
						$AppUI->redirect();
					}
  					if (($msg = $obj->store())) {
						$AppUI->setMsg($msg, UI_MSG_ERROR);
						$AppUI->redirect();
	  				}
					// Send notifications for created issue
					$hditem->notify(NEW_ITEM_LOG, $status_log_id);
				}
			        if($AppUI->msgNo != UI_MSG_ERROR) {
				        $AppUI->setMsg( $new_item ? ($AppUI->_('Added')) : ($AppUI->_('Updated')) , UI_MSG_OK, true);
				}
	      			doWatchers(dPgetParam($_POST, 'watchers', 0), $hditem);
				$AppUI->redirect('m=helpdesk&a=view&item_id='.$hditem->item_id);
				}
		}

}

/**
 * @param string $list A comma separated list of addresses
 * @param CHelpDeskItem $hditem
 */
function doWatchers($list, $hditem) {
	global $AppUI;

	// Create the watcher list
	$watcherlist = split(',', $list);

	$q = new DBQuery;
	$q->addQuery('user_id');
	$q->addTable('helpdesk_item_watchers');
	$q->addWhere('item_id = '.$hditem->item_id);
	$current_users = $q->loadHashList();
	$q->clear();
	$current_users = array_keys($current_users);

	// Delete the existing watchers as the list might have changed
	$q = new DBQuery;
	$q->setDelete('helpdesk_item_watchers');
	$q->addWhere('item_id='.$hditem->item_id);
	$q->exec();
	$q->clear();

	if (!$del) {
		if ($list) {
			foreach ($watcherlist as $watcher) {
				$q = new DBQuery;
				$q->addQuery('user_id, c.contact_email');
				$q->addTable('users');
				$q->addJoin('contacts', 'c', 'user_contact = contact_id');
				$q->addWhere('user_id = '.$watcher);
				$rows = $q->loadList();
				$q->clear();
				foreach ($rows as $row) {
					//Built list of new watchers to be notified
					if (!in_array($row['user_id'],$current_users)) {
						$email_list[] = $row['contact_email'];
					}
				}
				//Store watchers in DB
				$q = new DBQuery;
				$q->addTable('helpdesk_item_watchers');
				$q->addInsert('item_id',$hditem->item_id, true);
				$q->addInsert('user_id', $watcher, true);
				$q->addWhere('item_id='.$hditem->item_id);
				$q->exec();
				$q->clear();
				if ($email_list) {
					//Send new watchers notification they've been added
					$hditem->notify(NEW_WATCHER_LOG, $hditem->item_id, $email_list); 
				}
			} // foreach end
		} // if $list end
	} // if !del end
} // function end
?>