<?php /* HELPDESK $Id: helpdesk.class.php 265 2011-6-05 16:09:00CST-6 HaTaX $ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

if (($m) and (getDenyRead($m))) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

// Check to make sure config was done
$isSetup = file_get_contents('./modules/helpdesk/config.php');
if ($isSetup == "CONFIGURE_ME") {
	$isSetup = false;
} else {
	$isSetup = true;
	require ("./modules/helpdesk/config.php");
}

require_once ("./modules/helpdesk/helpdesk.functions.php");
require_once( $AppUI->getSystemClass( 'libmail' ) );
require_once( $AppUI->getSystemClass( 'dp' ) );
require_once( $AppUI->getSystemClass('date') );

// Define log types
define("NEW_ITEM_LOG", 1);
define("STATUS_LOG", 2);
define("STATUSTASK_LOG", 3);
define("TASK_LOG", 4);
define("NEW_WATCHER_LOG", 5);
define("LOG_STATUS_CHANGE", 6);
define("FILE_LOG", 7);
define("REMINDER", 8);

// Pull in some standard arrays
$ict = dPgetSysVal( 'HelpDeskCallType' );
$ics = dPgetSysVal( 'HelpDeskSource' );
$ios = dPgetSysVal( 'HelpDeskOS' );
$iap = dPgetSysVal( 'HelpDeskApplic' );
$ipr = dPgetSysVal( 'HelpDeskPriority' );
$isv = dPgetSysVal( 'HelpDeskSeverity' );
$ist = dPgetSysVal( 'HelpDeskStatus' );
$isa = dPgetSysVal( 'HelpDeskAuditTrail' );

$field_event_map = array(
//0=>Created
  1=>"item_title",            //Title
  2=>"item_requestor",        //Requestor Name
  3=>"item_requestor_email",  //Requestor E-mail
  4=>"item_requestor_phone",  //Requestor Phone
  5=>"item_assigned_to",      //Assigned To
  6=>"item_notify",           //Notify by e-mail
  7=>"item_company_id",       //Company
  8=>"item_project_id",       //Project
  9=>"item_calltype",         //Call Type
  10=>"item_source",          //Call Source
  11=>"item_status",          //Status
  12=>"item_priority",        //Priority
  13=>"item_severity",        //Severity
  14=>"item_os",              //Operating System
  15=>"item_application",     //Application
  16=>"item_summary",         //Summary
  17=>"item_deadline"	      //Deadline
//18=>Deleted
);

// Issue class
class CHelpDeskItem extends CDpObject {

  var $item_id = NULL;
  var $item_title = NULL;
  var $item_summary = NULL;

  var $item_calltype = NULL;
  var $item_source = NULL;
  var $item_os = NULL;
  var $item_application = NULL;
  var $item_priority = NULL;
  var $item_severity = NULL;
  var $item_status = NULL;
  var $item_project_id = NULL;
  var $item_company_id = NULL;

  var $item_assigned_to = NULL;
  var $item_notify = 0;
  var $item_requestor = NULL;
  var $item_requestor_id = NULL;
  var $item_requestor_email = NULL;
  var $item_requestor_phone = NULL;
  var $item_requestor_type = NULL;

  var $item_created_by = NULL;
  var $item_created = NULL;
  var $item_modified = NULL;
  var $item_updated = NULL;
  var $item_deadline = NULL;
  
function CHelpDeskItem() {
	$this->CDpObject( 'helpdesk_items', 'item_id' );
}

function check() {
	if ($this->item_id === NULL) {
	//Had to remove this check or else we couldn't add tasklogs
	//      return ("$AppUI->_('Issue Item ID is NULL')");
	}
	if (!$this->item_created) { 
		$this->item_created = new CDate();
		$this->item_created = $this->item_created->format( FMT_DATETIME_MYSQL );
	}
	// TODO More checks
	return NULL;
}

function store() {
  global $AppUI;
	// Update the last modified time and user
	// Pretty much duplicates the item_updated field, will keep for now.
	$this->item_modified = new CDate();
	$this->item_modified = $this->item_modified->format( FMT_DATETIME_MYSQL );
    
	$this->item_summary = strip_tags($this->item_summary);

	//if type indicates a contact or a user, then look up that phone and email
	//for those entries
	switch ($this->item_requestor_type) {
	  case '0'://it's not a user or a contact
		break;
	  case '1'://it's a system user
		$q = new DBQuery();
		$q->addTable('users','u');
		$q->addQuery('u.user_id as id');
		$q->addJoin('contacts','c','u.user_contact = c.contact_id');
		$q->addQuery("c.contact_email as email, c.contact_phone as phone, CONCAT(c.contact_first_name,' ', c.contact_last_name) as name");
		// KZHAO  8-3-2006
		$q->addWhere('u.user_id='.$this->item_requestor_id);
        	break;
	  case '2': //it's only a contact
		$q = new DBQuery();
		$q->addTable('contacts','c');
		$q->addQuery("c.contact_email as email, c.contact_phone as phone, CONCAT(c.contact_first_name,' ', c.contact_last_name) as name");
		$q->addWhere('contact_id='.$this->item_requestor_id);
		break;
	  default:
		break;
	}
	// get requestor's information 
	if(isset($q)) {
		$result = $q->loadHash();
		$q->clear();
		$this->item_requestor_email = $result['email'];
		$this->item_requestor_phone = $result['phone'];
		$this->item_requestor = $result['name'];
	}
	// if the store is successful, pull the new id value and insert it into the object.
	// call parent class' store method to insert this record into DB
	if (($msg = parent::store())) {
		return $msg;
	} else {
		if(!$this->item_id) {  
			$this->item_id = mysql_insert_id();
		}
		return $msg;
	}
}

  function delete() {
	  
		// This section will grant every request to delete an HDitem
		$k = $this->_tbl_key;
		if ($oid) {
			$this->$k = intval( $oid );
		}
		//load the item first so we can get the item_title for history
		$this->load($this->item_id);
		$this->clearReminder(true);
		addHistory($this->_tbl, $this->$k, 'delete', $this->item_title, $this->item_project_id);
		$result = null;
		$q  = new DBQuery;
		$q->setDelete($this->_tbl);
		$q->addWhere("$this->_tbl_key = '".$this->$k."'");
		if (!$q->exec()) {
			$result = db_error();
		}
		$q->clear();
		$q->setDelete('helpdesk_item_status');
		$q->addWhere("status_item_id = '".$this->item_id."'");
		if (!$q->exec()) {
			$result .= db_error();
		}
		$q->clear();
		$q->setDelete('helpdesk_item_watchers');
		$q->addWhere("item_id = '".$this->item_id."'");
		if (!$q->exec()) {
			$result .= db_error();
		}
		$q->clear();
		$q->setDelete('task_log');
		$q->addWhere("task_log_help_desk_id = '".$this->item_id."'");
		if (!$q->exec()) {
			$result .= db_error();
		}
		$q->clear();
		return $result;	
  }

function notify($type, $log_msg, $email_list=null) {
	global $AppUI, $dPconfig, $notify_all_event, $HELPDESK_CONFIG, $locale_char_set;

	// Load these tables in this function in case of an outside call
	// file notifications specifically give blank entries without this
	include 'config.php';
	$ict = dPgetSysVal( 'HelpDeskCallType' );
	$ics = dPgetSysVal( 'HelpDeskSource' );
	$ios = dPgetSysVal( 'HelpDeskOS' );
	$iap = dPgetSysVal( 'HelpDeskApplic' );
	$ipr = dPgetSysVal( 'HelpDeskPriority' );
	$isv = dPgetSysVal( 'HelpDeskSeverity' );
	$ist = dPgetSysVal( 'HelpDeskStatus' );
	$isa = dPgetSysVal( 'HelpDeskAuditTrail' );

	if ( (!$this->item_notify) && ($type!=5) ) {
		// Notify is turned off for this item and it is not a new watcher log, exit the function.
		return "skipped - notify disabled";
	}

	if (isset($this->item_assigned_to)) {
		//add the assigned user email to the list of mailing people,
		//if they are not the user making the changes
		if ($this->item_assigned_to != $AppUI->user_id) {
			$q = new DBQuery();
			$q->addTable('users','u');
			$q->addQuery('c.contact_email');
			$q->addJoin('contacts','c','u.user_contact = c.contact_id');
			$q->addWhere('u.user_id='.$this->item_assigned_to);
			$assigned_to_email_list = $q->loadHashList();
			$q->clear();
			$assigned_to_email_list = array_keys($assigned_to_email_list);
			foreach ($assigned_to_email_list as $user_email) {
				if (trim($user_email)) {
					$assigned_email = $user_email;
					$built_email_list[] = $user_email;
				}
			}
		}
	}

	if( ( ($HELPDESK_CONFIG['task_requestor_notification']==1)
		 || ($notify_all_event) || ($type==1) || ($type==2) || ($type==3)
		 ) && (isset($this->item_requestor_email)) ) {
		$built_email_list[] = $this->item_requestor_email;
	}

	if ( $HELPDESK_CONFIG['task_watchers_notification']==1
		|| ($notify_all_event) || ($type==1) || ($type==2) || ($type==3) ) {
		// Pull up the email address of everyone on the watch list 
		// this list does not include the assignee
		$q = new DBQuery();
		$q->addTable('helpdesk_item_watchers','hdw');
		$q->addQuery('c.contact_email');
		$q->addJoin('users','u','hdw.user_id = u.user_id');
		$q->addJoin('contacts','c','u.user_contact = c.contact_id');
		$q->addWhere('hdw.item_id='.$this->item_id);
		$watcher_email_list = $q->loadHashList();
		$q->clear();
		$watcher_email_list = array_keys($watcher_email_list);
		foreach ($watcher_email_list as $user_email) {
				if (trim($user_email)) {
					$built_email_list[] = $user_email;
				}
		}
	}
	if (!isset($email_list)) {
		// If email list isn't provided to function, use the pre-built list
		$email_list = $built_email_list;
	} 

	// Build basic subject information
	$subject = $HELPDESK_CONFIG['email_subject']." - Issue #{$this->item_id} ({$this->item_title})";

	// Build notification specific information for top of body
	// any comments to body string made prior will be retained at the top
	switch ($type) {
		case item_notify:
			//Ignore status updates of changing email notification setting on issue
			return "skipped - ignoring email notification changes";
		case item_status:
		case STATUS_LOG:
			$subject .= " - " . $AppUI->_('Updated status');
			if (is_numeric($log_msg)) {
				$q = new DBQuery();
				$q->addTable('helpdesk_item_status','hds');
				$q->addQuery('hds.status_code, hds.status_comment');
				$q->addWhere('hds.status_id='.$log_msg);
				$log=$q->loadHash();
				$q->clear();
				$body .= $AppUI->_('Updated status')."  -  {$isa[$log['status_code']]} {$log['status_comment']}\n\n";
			} else {
				$body .= $AppUI->_('Updated status')." - {$ist[$this->item_status]}\n\n";
			}
			break;
		case NEW_ITEM_LOG:
			$subject .= " - " . $AppUI->_('Created');
			$body .= "\t\t\t--- " . $AppUI->_('New issue created') . " --- \t\n\n";
			break;
		case TASK_LOG:
			$subject .= " - " . $AppUI->_('Updated task log');
			$body .= "\t\t\t    --- ".$AppUI->_('Task log') . " ---\n" . $log_msg
			 . "\n\t\t\t--- ".$AppUI->_('Task log end') . " ---\n\n\n";
			break;
		case STATUSTASK_LOG:
			$subject .= " - " . $AppUI->_('Updated Task + Status');
			$body .= "\t\t\t    --- ".$AppUI->_('Task Log') . " ---\n" . $log_msg
			 . "\n\t\t\t--- ".$AppUI->_('Task Log End') . " ---\n\n\n";
			break;
		case NEW_WATCHER_LOG:
			$subject .= " - " . $AppUI->_('Watchers Notification');
	    		$body .= $AppUI->_(' - You have been added to the watchers list for the following issue - ')."\n\n";
			break;
		case LOG_STATUS_CHANGE:
			$subject .= " - " . $AppUI->_('Updated');
			$body .= "\t\t\t    --- ".$AppUI->_('Updates') . " ---\n" . $log_msg
			 . "\t\t\t--- ".$AppUI->_('Updates End') . " ---\n\n\n";
			break;
		case REMINDER:
			$subject .= " - " . $AppUI->_('Deadline Reminder');
			$body .= "\t\t\t    --- ".$AppUI->_('Deadline Info') . " ---\n" . $log_msg
			 . "\t\t\t--- ".$AppUI->_('Deadline Info End') . " ---\n\n\n";
			break;
		case FILE_LOG:
			if (is_numeric($log_msg)) {
				$q = new DBQuery();
				$q->addTable('task_log','tl');
				$q->addQuery('tl.task_log_name, tl.task_log_description');
				$q->addWhere('tl.task_log_id='.$log_msg);
				$log=$q->loadHash();
				$q->clear();
				$subject .= " - " . $AppUI->_('Updated file log');
				$body .= "\t\t\t    --- ".$AppUI->_('File log') . " ---\n"
				 . $AppUI->_('Summary') . "\t:\t".$log['task_log_name']."\n";
				if ($log['task_log_description']) {
					$body .= $AppUI->_('Description')."\t:\t" . $log['task_log_description']."\n"; }
				$body .= "\t\t\t--- ".$AppUI->_('File log end') . " ---\n\n\n";
			} else {
				$subject .= " - " . $AppUI->_('Updated file log');
				$body .= "\t\t\t    --- ".$AppUI->_('File log') . " ---\n" . $log_msg
				 . "\n\t\t\t--- ".$AppUI->_('File log end') . " ---\n\n\n";
			}
			break;
		DEFAULT:
			$subject .= " - " . $AppUI->_('Notification');
	    		$body .= $AppUI->_('Current notification type is') . " - {$type}\t\n"
			 . $log_msg . "\n\n";
			break;
	}
	// Build basic information provided in all notifications
	if (strcmp($HELPDESK_CONFIG['email_header'],"NONE")<>0) {
		$body .= $HELPDESK_CONFIG{'email_header'} . "\n";
	}
	$body .= $AppUI->_('Issue')." #{$this->item_id}  -  {$this->item_title} " . "\n";
	$body .= "-----------------------------------------\n";
	if ($this->item_calltype!=0) {
		$body .= $AppUI->_('Issue Type')."\t:\t".$ict[$this->item_calltype]."\n"; }
	if ($this->item_assigned_to>0) {
		$body .= $AppUI->_('Assigned')."\t:\t".dPgetUsernameFromID($this->item_assigned_to)."\n"; }
	if ($this->item_requestor!="") {
		$body .= $AppUI->_('Requestor')."\t:\t".$this->item_requestor."\n"; }
	if ($this->item_requestor_email!="") {
		$body .= $AppUI->_('Requestor Email')." :\t".$this->item_requestor_email."\n"; }
	if ($this->item_requestor_phone!="") {
		$body .= $AppUI->_('Requestor Phone')." :\t".$this->item_requestor_phone."\n"; }
	if ($this->item_company_id!="0") {
		$allowedCompanies = arrayMerge( array( 0 => '' ), getAllowedCompanies() );
		if ($allowedCompanies[$this->item_company_id] != "") {
			$body .= $AppUI->_('Company')."\t:\t{$allowedCompanies[$this->item_company_id]}\n"; } }
	if ($this->item_project_id > 0) {
		$allowedProjects = arrayMerge( array( 0 => '' ), getAllowedProjects() );
		if ($allowedProjects[$this->item_project_id] != "") {
			$body .= $AppUI->_('Project')."\t\t:\t{$allowedProjects[$this->item_project_id]}\n"; } }
	if ($this->item_source!=0) {
		$body .= $AppUI->_('Source')."\t\t:\t".$ics[$this->item_source]."\n"; }
	if ($this->item_priority!=0) {
		$body .= $AppUI->_('Priority')."\t\t:\t".$ipr[$this->item_priority]."\n"; }
	if ($this->item_severity!=0) {
		$body .= $AppUI->_('Severity')."\t:\t".$isv[$this->item_severity]."\n"; }
	if ($this->item_os!=0) {
		$body .= $AppUI->_('OS')."\t\t:\t".$ios[$this->item_os]."\n"; }
	if ($this->item_application!=0) {
		$body .= $AppUI->_('Application')."\t:\t".$iap[$this->item_application]."\n"; }
	if ( ($this->item_deadline!="") && (strcmp($this->item_deadline,'0000-00-00 00:00:00')<>0) && (strcmp($this->item_deadline,"N/A")<>0) ) {
		$fmtdate = new DateTime($this->item_deadline);
		$fmtdate = date_format($fmtdate, $HELPDESK_CONFIG['notification_deadline_format']);
		$body .= $AppUI->_('Deadline')."\t:\t".$fmtdate."\n";
	}
	$body .= $AppUI->_('Status')."\t\t:\t".$ist[$this->item_status]."\n";
	if ($AppUI->user_id>0) {
	$body .= $AppUI->_('Modified By')."\t:\t".dPgetUsernameFromID($AppUI->user_id)."\n"; }

	// check if being called from a cron job, replace the base URL with external URL if so.
	if ($dPconfig['base_url'] == "http://127.0.0.1") {
		$curUrlBase = $HELPDESK_CONFIG['notification_cron_url'];;
	} else {
		$curUrlBase = $dPconfig['base_url'];
	}
	// End of basic information build, link and item summary are added below

	//if there's no one in the list, nothing to do.
	if ( count($email_list)>0 && is_array($email_list) ) {
//below is for debugging, unremark to enable
//		$body .= "\n Debugging notes";
//		foreach($email_list as $assigned_to_email){$body .= "\n (Notification address: " . $assigned_to_email .")";}

		$email_list=array_unique($email_list);
  		foreach($email_list as $assigned_to_email){
			$mail = new Mail();
			//check if the email is valid and if the current WebUI user is in the list.
			//if the current user is in the list, do NOT send them an email.
			if ($mail->ValidEmail($assigned_to_email) && ($AppUI->user_email!=$assigned_to_email)) {
				$to=$assigned_to_email;
				if ($mail->ValidEmail($AppUI->user_email)) {
					$email = $AppUI->user_email;
				} else {
					// "notity" is not a typo, this is how it is listed in
					// config.php and other files.  TO-DO: Correct typo
					$email = $HELPDESK_CONFIG['notity_email_address'];
				}
				if ($email=="") {
					//We need a from address
					return "error - missing from address";
				}
				// Mail it
				$mail->Subject($subject, $locale_char_set);

				if (($AppUI->user_first_name!=NULL) && ($AppUI->user_last_name!=NULL) &&
						($HELPDESK_CONFIG['notify_mask_user_name']!=1) ) {
					$mail->From("\"{$AppUI->user_first_name} {$AppUI->user_last_name}\" <{$email}>");
				} else {
					$mail->From("\"".dPgetConfig('email_prefix')."\" <{$email}>");
				}

				$mail->To($assigned_to_email);

				if ($assigned_to_email==$assigned_email) {
					$sendbody = " -= ".$AppUI->_('You are assigned to the following issue')." =- \n\n".$body;
					$sendbody .= "\n".$AppUI->_('Link')."\t: ".$curUrlBase."/index.php?m=helpdesk&a=view&item_id=".$this->item_id."\n";
				} elseif (trim($assigned_to_email)==trim($this->item_requestor_email)) {
					$sendbody = " -= ".$AppUI->_('You are the requestor of the following issue')." =- \n\n".$body;
					if ($this->item_requestor_type==1) {
						//only include the URL to the requestor if they are a user on the system
						$sendbody .= "\n".$AppUI->_('Link')."\t: ".$curUrlBase."/index.php?m=helpdesk&a=view&item_id=".$this->item_id."\n";
					}
				} elseif ($watcher_email_list) {
					foreach ($watcher_email_list as $watcher_addy) {
						if (trim($assigned_to_email)==trim($watcher_addy)) {
							$sendbody = " -= ".$AppUI->_('You are a watcher of the following issue')." =- \n\n".$body;
							$sendbody .= "\n".$AppUI->_('Link')."\t: ".$curUrlBase."/index.php?m=helpdesk&a=view&item_id=".$this->item_id."\n";
						}
					}
				} else {
					//This sends with no banner at the top of the message body
					//Really we shouldn't get here unless there's a problem
					$sendbody = $body;
				}
				$sendbody .= "\n\t\t\t--- ".$AppUI->_('Summary') . " ---\n" . $this->item_summary."\n";
				$mail->Body($sendbody, isset( $GLOBALS['locale_char_set']) ? $GLOBALS['locale_char_set'] : "");
				$sendbody = '';
				if (!$mail->Send()) {
					// Something bad happened sending the email, exit the function.
					return "error - sending of email failed";
				}
			} 
		}
		// This is where we should exit the function, everything was successful.
		return "successful - notifications sent";
	}
	// Blank email_list array, no one to send to.
	return "error - no email list";
}

  function log_status_changes() {
    global $ist, $ict, $ics, $ios, $iap, $ipr, $isv, $ist, $isa, $field_event_map, $AppUI, $notify_all_event;

    if(dPgetParam( $_POST, "item_id")){
	$hditem = new CHelpDeskItem();
	$hditem->load( dPgetParam( $_POST, "item_id") );
      
	$notify_all_event=false;
	$count=0;
	$status_changes_summary="";
	foreach($field_event_map as $key => $value){
	   if (eval("return  (isset(\$this->$value) && (\$hditem->$value != \$this->$value));")) {
		$old = $new = "";
		$skipnotify = NULL;
		
		switch($value){
	            // Create the comments here
	            case 'item_assigned_to':
	              $q = new DBQuery; 
	              $q->addQuery('user_id, concat(contact_first_name,\' \',contact_last_name) as user_name');
	              $q->addTable('users');
	              $q->addJoin('contacts','','user_contact = contact_id');
	              $q->addWhere('user_id in ('.($hditem->$value?$hditem->$value:'0').
	                                          ($this->$value&&$hditem->$value?', ':'').
	                                          ($this->$value?$this->$value:'').')');
	              $ids = $q->loadList();
	              foreach ($ids as $row){
	                if($row["user_id"]==$this->$value){
	                  $new = $row["user_name"];
	                } else if($row["user_id"]==$hditem->$value){
	                  $old = $row["user_name"];
	                }
	              }
	              break;
	            case 'item_company_id':
	              $q = new DBQuery; 
	              $q->addQuery('company_id, company_name');
	              $q->addTable('companies');
	              $q->addWhere('company_id in ('.($hditem->$value?$hditem->$value:'').
	                                          ($this->$value&&$hditem->$value?', ':'').
	                                          ($this->$value?$this->$value:'').')');
	              $ids = $q->loadList();
	              foreach ($ids as $row){
	                if($row["company_id"]==$this->$value){
	                  $new = $row["company_name"];
	                } 
			else if($row["company_id"]==$hditem->$value){
	                  $old = $row["company_name"];
	                }
	              }
	              break;
	            case 'item_project_id':
	              $q = new DBQuery; 
	              $q->addQuery('project_id, project_name');
	              $q->addTable('projects');
	              $q->addWhere('project_id in ('.($hditem->$value?$hditem->$value:'').
	                                          ($this->$value&&$hditem->$value?', ':'').
	                                          ($this->$value?$this->$value:'').')');
	              $ids = $q->loadList();
	              foreach ($ids as $row){
	                if($row["project_id"]==$this->$value){
	                  $new = $row["project_name"];
	                } else if($row["project_id"]==$hditem->$value){
	                  $old = $row["project_name"];
	                }
	              }
	              break;
	            case 'item_calltype':
	              $old = $AppUI->_($ict[$hditem->$value]);
	              $new = $AppUI->_($ict[$this->$value]);
	              break;
	            case 'item_source':
	              $old = $AppUI->_($ics[$hditem->$value]);
	              $new = $AppUI->_($ics[$this->$value]);
	              break;
	            case 'item_status':
	              $old = $AppUI->_($ist[$hditem->$value]);
	              $new = $AppUI->_($ist[$this->$value]);
		      $notify_all_event=true;
	              break;
	            case 'item_priority':
	              $old = $AppUI->_($ipr[$hditem->$value]);
	              $new = $AppUI->_($ipr[$this->$value]);
	              break;
	            case 'item_severity':
	              $old = $AppUI->_($isv[$hditem->$value]);
	              $new = $AppUI->_($isv[$this->$value]);
	              break;
	            case 'item_os':
	              $old = $AppUI->_($ios[$hditem->$value]);
	              $new = $AppUI->_($ios[$this->$value]);
	              break;
	            case 'item_application':
	              $old = $AppUI->_($iap[$hditem->$value]);
	              $new = $AppUI->_($iap[$this->$value]);
	              break;
	            case 'item_notify':
	              $old = $hditem->$value ? $AppUI->_('On') : $AppUI->_('Off');
	              $new = $this->$value ? $AppUI->_('On') : $AppUI->_('Off');
	              $skipnotify = true;
	              break;
	            case 'item_deadline':
	              $old = $hditem->$value;
	              $new = $this->$value;
	              if ( $new=='' || (strcmp($new,'N/A')==0) || (strcmp($new,'0000-00-00 00:00:00')==0) ) { 
	                  $new="N/A";
 	                  if ( $old=='' || (strcmp($old,'N/A')==0) || (strcmp($old,'0000-00-00 00:00:00')==0) ) { 
 	                      $old="N/A";
 	                  }			
	              } else {
			$dl=new CDate($new);
			$dl->setTime(dPgetConfig('cal_day_end'),00,00);
			$new=$dl->format( FMT_DATETIME_MYSQL );
			$hditem->addReminder();
		      }
	              break;
	            case 'item_requestor_email':
	              $old = $hditem->$value;
	              $new = $this->$value;
		      $notify_all_event=true;
	              break;
	            default:
	              $old = $hditem->$value;
	              $new = $this->$value;
	              break;
		}// end of switch
		// if value has changed, add it to the summary of changes.
		if(!eval("return \$new == \$old;")){
			if ($new=='') {$new = ' ';}
			$last_status_comment = $this->log_status($key, $old, $new);
			// Added by KP to be able to skip event notifications -
			// If the skip notify flag is set, the log will not be added to
			// notification emails and the count is not increased.
			// If this was the only change, no notification will be sent.
			if ( (!$skipnotify) || ($notify_all_event) ) {
				$status_changes_summary .= $last_status_comment . "\t\n";
				$count++;
			}
		}
	   }//end of if
	}//end of foreach loop
	// Send notifications out about the update if notify is on and there were records iterated
	if ($this->item_notify && $count) {
		$this->notify(LOG_STATUS_CHANGE, $status_changes_summary);
		return $status_changes_summary;
	} else {
		return "nochanges";
	}
    }//end of top if
  }//end of function
  
 function log_status ($audit_code, $commentfrom="", $commentto="", $notify=0) {
	global $AppUI, $isa, $HELPDESK_CONFIG ;
 	if ($commentto) {
  		$sep = ' ';
		$sepend = ' ';
		if ($audit_code==16) {
			// summary change, change formatting so longer strings of text look good in emails
			// audit_code is tied to "HelpDeskAuditTrail" in the sysvals table on dotproject DB
			$comment = $AppUI->_('changed From'). " : \t\n" . addslashes($commentfrom) . " \n\n";
			$comment .= $AppUI->_('To') . " : \t\n" . addslashes($commentto);
		} elseif ($audit_code==17) {
			// deadline change, format date output to look nice
			$sepend = ": \n   \t";
			if ( !$commentfrom || (strcmp($commentfrom,"N/A")==0) || (strcmp($commentfrom,"0000-00-00 00:00:00")==0) ) {
				$prevdate = "N/A";
			} else {
				$prevdate = new DateTime($commentfrom);
				$prevdate = date_format($prevdate, $HELPDESK_CONFIG['notification_deadline_format'].'\, H:i:s O');
			}
			if ($commentto == "N/A") {
				$newdate = $commentto;
			} else {
				$newdate = new DateTime($commentto);
				$newdate = date_format($newdate, $HELPDESK_CONFIG['notification_deadline_format'].'\, H:i:s O');
			}
			if ($prevdate!=$newdate) {
				$comment = $sep . $AppUI->_('changed from'). $sepend . "\"" . $prevdate . "\" \n";
				$comment .= $AppUI->_('to') . $sepend . "\"" . $newdate . "\"";
			} 
		} elseif ($audit_code==99) {
			// file was uploaded or deleted, change formatting to reflect activity
			if ($commentto) {
				$comment = " - \"".$commentfrom."\" was ".$commentto;
			} else {
				$comment = " - \"".$commentfrom." \"";
			}
		} else {
			// all other audit codes
			$comment = $sep . $AppUI->_('changed from'). $sepend . "\"" . addslashes($commentfrom) . "\"";
			$comment .= $sep . $AppUI->_('to') . $sepend . "\"" . addslashes($commentto) . "\"";
		}
	} else {
		// no commentto set, process commentfrom without formatting
		$comment=$commentfrom;
	}
	// if there is no comment, do not store
	if ($comment!="") {
		$currDateTime = new CDate;
		$currDateTime = $currDateTime->format( FMT_DATETIME_MYSQL );
		$q = new DBQuery;
		$q->addTable('helpdesk_item_status');
		$q->addInsert('status_item_id', $this->item_id, true);
		$q->addInsert('status_code', $audit_code, true);
		$q->addInsert('status_date', $currDateTime, true);
		$q->addInsert('status_modified_by', $AppUI->user_id, true);
		$q->addInsert('status_comment', $comment, false);
		$result = $q->exec();
		$q->clear();
		if (!$result) {
			return false;
		}
//		$log_id = mysql_insert_id();
	}
	return $isa[$audit_code] . $comment;
  }

// EventQueue reminder functions below
function addReminder() {
  global $AppUI, $dPconfig, $HELPDESK_CONFIG;
	$day = 86400;
	
	if (!dPgetConfig('task_reminder_control')) {
		return;
	}
	
	if (! $this->item_deadline) { // No deadline, can't notify
		return $this->clearReminder(true); // Also no point if it is changed to null
	}
	
	// If the issue is closed, clear the reminder.
	if ($this->item_status == $HELPDESK_CONFIG['closed_status_id']) {
		return $this->clearReminder(true);
	}
	
	$eq = new EventQueue;
	$pre_charge = dPgetConfig('task_reminder_days_before', 1);
	$repeat = dPgetConfig('task_reminder_repeat', 100);
	
	//If we don't need any arguments (and we don't) then we set this to null. 
	//We can't just put null in the call to add as it is passed by reference.
	$args = null;
	
	// Find if we have a reminder on this item already
	$old_reminders = $eq->find('helpdesk', 'helpremind', $this->item_id);
	if (count($old_reminders)) {
		//It shouldn't be possible to have more than one reminder, 
		//but if we do, we may as well clean them up now.
		foreach ($old_reminders as $old_id => $old_data) {
			$eq->remove($old_id);
		}
	}
	
	// Find the deadline date of this task, then subtract the required number of days.
	$date = new CDate($this->item_deadline);
	$today = new CDate(date('Y-m-d'));
	if (CDate::compare($date, $today) < 0) {
		$start_day = time();
	} else {
		$start_day = $date->getDate(DATE_FORMAT_UNIXTIME);
		$start_day -= ($day * $pre_charge);
	}
	
	$eq->add(array($this, 'helpremind'), $args, 'helpdesk', false, $this->item_id, 'helpremind', 
			 $start_day, $day, $repeat);
}
	
function helpremind($module, $type, $id, $owner, &$args) {
    global $locale_char_set, $AppUI, $dPconfig, $HELPDESK_CONFIG;

	include 'config.php';

	$today = new CDate();
	if (! $today->isWorkingDay()) {
		return false;
	}
	
	if (! $this->load($id)) {
		$hditem = new CHelpDeskItem;
		$hditem->load($id);
		$hditem->clearReminder(true);
		return false;
	}
	
	if ($this->item_status == $HELPDESK_CONFIG['closed_status_id']) {
		$hditem = new CHelpDeskItem;
		$hditem->load($id);
		$hditem->clearReminder(true);
		return false;
	}
	
	list($deadyear, $deadmonth, $deadday) = explode("-", $this->item_deadline);
	if (!checkdate($deadmonth, $deadday, $deadyear)) {
		$hditem = new CHelpDeskItem;
		$hditem->load($id);
		$hditem->clearReminder(true);
		return false;
	}
	
	// if using HTML emails, unremark following line
	//$this->htmlDecode();

	$expires = new CDate($this->item_deadline);
	$now = new CDate();
	$diff = $expires->dateDiff($now);
	
	$prefix = $AppUI->_('Issue Due', UI_OUTPUT_RAW);
	if ($diff == 0) {
		$msg = $AppUI->_('Today', UI_OUTPUT_RAW);
	} else if ($expires < $now) {
		$msg = $AppUI->_('OVERDUE')." ".abs($diff)." ".$AppUI->_('DAYS');
		$prefix = $AppUI->_('Issue', UI_OUTPUT_RAW);
	} else if ($diff == 1) {
		$msg = $AppUI->_('Tomorrow', UI_OUTPUT_RAW);
	} else {
		$msg = $AppUI->_(array($diff, 'DAYS'));
	}

	$msg = $prefix." - ".$msg." \t ($this->item_deadline) \t\n";

	if (!$this->notify(8, $msg)) {
		// an error was encountered with notify()
		return false;
	} else {
		// everything went fine, dequeue the event
		return true;
	}
}

function clearReminder($dont_check = false) {
   global $AppUI, $dPconfig, $HELPDESK_CONFIG;
	$ev = new EventQueue;
	
	$event_list = $ev->find('helpdesk', 'helpremind', $this->item_id);
	if (count($event_list)) {
		foreach ($event_list as $id => $data) {
			if ($dont_check || $this->item_status == $HELPDESK_CONFIG['closed_status_id']) {
				$ev->remove($id);
			}
		}
	}
}
}//end of class
/**
* Overloaded CTask Class
*/
if (!class_exists('CHDTaskLog')) {
class CHDTaskLog extends CDpObject {
  var $task_log_id = NULL;
  var $task_log_task = NULL;
  var $task_log_help_desk_id = NULL;
  var $task_log_name = NULL;
  var $task_log_description = NULL;
  var $task_log_creator = NULL;
  var $task_log_hours = NULL;
  var $task_log_date = NULL;
  var $task_log_costcode = NULL;
  var $task_log_reference = NULL;	//ANDY added

  function CHDTaskLog() {
    $this->CDpObject( 'task_log', 'task_log_id' );
  }

  // overload check method
  function check() {
    $this->task_log_hours = (float) $this->task_log_hours;
    return NULL;
  }
}
}
//ANDY added function; having 'company' filter in dashboard screen
function issue_list_data($user_id = false) {
	// retrieve list of records
	global $AppUI, $addPwOiD, $buffer, $company, $company_id, $company_prefix;

	// get the list of permitted companies
	$obj_company = new CCompany();
	$companies = $obj_company->getAllowedRecords($AppUI->user_id, 'company_id,company_name', 
	                                             'company_name');
	if(count($companies) == 0) { 
		$companies = array(0);
	}
	
	// get the list of permitted companies
	$companies = arrayMerge(array('0' => $AppUI->_('All')), $companies);
	
	//get list of all departments, filtered by the list of permitted companies.
	$q = new DBQuery;
	$q->addTable('companies', 'c');
	$q->addQuery('c.company_id, c.company_name');
	$q->addOrder('c.company_name');
	$obj_company->setAllowedSQL($AppUI->user_id, $q);
	$rows = $q->loadList();
	$q->clear();
	
	//display the select list
	$buffer = '<select name="department" onChange="document.pickCompany.submit()" class="text">';
	$buffer .= ('<option value="company_0" style="font-weight:bold;">' . $AppUI->_('All') 
	            . '</option>'."\n");
	foreach ($rows as $row) {
		$buffer .= ('<option value="' . $company_prefix . $row['company_id'] 
					. '" style="font-weight:bold;"' 
					. (($company_id == $row['company_id']) ? 'selected="selected"' : '') 
					. '>' . $row['company_name'] . '</option>' . "\n");
		
	}
	$buffer .= '</select>';
	
}
?>
