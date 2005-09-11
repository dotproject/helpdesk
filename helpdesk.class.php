<?php /* HELPDESK $Id: helpdesk.class.php,v 1.62 2005/09/09 21:40:59 pedroix Exp $ */
require_once( $AppUI->getSystemClass( 'dp' ) );
require_once( $AppUI->getSystemClass( 'libmail' ) );
include_once("helpdesk.functions.php");
include_once("./modules/helpdesk/config.php");
require_once $AppUI->getSystemClass('date');

// Make sure we can read the module
if (getDenyRead($m)) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

// Define log types
define("STATUS_LOG", 1);
define("TASK_LOG", 2);

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
//17=>Deleted
);
  
// Help Desk class
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

  function CHelpDeskItem() {
    $this->CDpObject( 'helpdesk_items', 'item_id' );
  }

//Use CDpObjects Load instead
//  function load( $oid ) {
//	$q  = new DBQuery;
//	$q->addTable('helpdesk_items');
//	$q->addWhere("item_id = ".$oid."");
//	return $q->loadObject($this);
//  }

  function check() {
    if ($this->item_id === NULL) {
//Had to remove this check or else we couldn't add tasklogs
//      return ("$AppUI->_('Help Desk Item ID is NULL')");
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
    //$this->item_created = new CDate();
    
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
/*        $sql = "SELECT user_id as id,
                       contact_email as email,
                       contact_phone as phone,
                CONCAT(contact_first_name,' ', contact_last_name) as name
                FROM users
               LEFT JOIN contacts ON user_contact = contact_id
                WHERE user_id='{$this->item_requestor_id}'";*/
        break;
      case '2':
		$q = new DBQuery();
		$q->addTable('contacts','c');
		$q->addQuery("c.contact_email as email, c.contact_phone as phone, CONCAT(c.contact_first_name,' ', c.contact_last_name) as name");
		$q->addWhere('contact_id='.$this->item_requestor_id);
/*        $sql = "SELECT contact_id as id,
                       contact_email as email,
                       contact_phone as phone,
                CONCAT(contact_first_name,' ', contact_last_name) as name
                FROM contacts
                WHERE contact_id='{$this->item_requestor_id}'";*/
        break;
      default:
        break;
    }

    if(isset($q)) {
      $result = $q->loadHash();
      $q->clear();
      $this->item_requestor_email = $result['email'];
      $this->item_requestor_phone = $result['phone'];
      $this->item_requestor = $result['name'];
    }
      
    /* if the store is successful, pull the new id value and insert it into the 
       object. */
    if (($msg = parent::store())) {
	    return $msg;
    } else {
	    if(!$this->item_id){  
	    	$this->item_id = mysql_insert_id();
	    }
	    return $msg;
    }
  }

  function delete() {
	  
		// This section will grant every request to delete an HPitem
		$k = $this->_tbl_key;
		if ($oid) {
			$this->$k = intval( $oid );
		}
		//load the item first so we can get the item_title for history
		$this->load($this->item_id);
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
  
  function notify($type, $log_id, $newhdi=0) {
    global $AppUI, $ist, $ict, $isa, $dPconfig;

//    if (!$this->item_notify ||
//        ($this->item_assigned_to == $AppUI->user_id)) {
//      return;
//    }

    // Pull up the email address of everyone on the watch list 
	$q = new DBQuery();
	$q->addTable('helpdesk_item_watchers','hdw');
	$q->addQuery('c.contact_email');
	$q->addJoin('users','u','hdw.user_id = u.user_id');
	$q->addJoin('contacts','c','u.user_contact = c.contact_id');
	if($this->item_notify) {
		$q->addWhere('hdw.item_id='.$log_id. ' OR u.user_id='.$this->item_assigned_to);
	} else {
		$q->addWhere('hdw.item_id='.$log_id);
	}
/*    $sql = "SELECT contact_email
            FROM 
            	helpdesk_item_watchers
            	LEFT JOIN users ON helpdesk_item_watchers.user_id = users.user_id
		LEFT JOIN contacts ON user_contact = contact_id
            WHERE 
            	helpdesk_item_watchers.item_id='{$this->item_id}'";
     //if they choose, along with the person who the ticket is assigned to.
    if($this->item_notify)
     	$sql.=" or users.user_id='{$this->item_assigned_to}'";*/

    $email_list = $q->loadHashList();
    $q->clear();
    $email_list = array_keys($email_list);

	//add the requestor email to the list of mailing people
    $email_list[] = $this->item_requestor_email;

    //echo $sql."\n";
    //if there's no one in the list, skip the rest.
    if(count($email_list)<=0)
      return;

    if (is_numeric($log_id)) {
      switch ($type) {
        case STATUS_LOG:
			$q = new DBQuery();
			$q->addTable('helpdesk_item_status','hds');
			$q->addQuery('hds.status_code, hds.status_comment');
			$q->addWhere('hds.status_id='.$log_id);
/*          $sql = "SELECT status_code, status_comment
                  FROM helpdesk_item_status
                  WHERE status_id=$log_id";*/
          break;
        case TASK_LOG:
			$q = new DBQuery();
			$q->addTable('task_log','tl');
			$q->addQuery('tl.task_log_name, tl.task_log_description');
			$q->addWhere('tl.task_log_id='.$log_id);
/*          $sql = "SELECT task_log_name,task_log_description
                  FROM task_log
                  WHERE task_log_id=$log_id";*/
          break;
      }
        
      $log=$q->loadHash();
    }

    foreach($email_list as $assigned_to_email){
	    $mail = new Mail;
	    if ($mail->ValidEmail($assigned_to_email)) {
	      $subject = $AppUI->cfg['page_title']." ".$AppUI->_('Help Desk Item')." #{$this->item_id}";

	      switch ($type) {
		case STATUS_LOG:
		  $body = $AppUI->_('Title').": {$this->item_title}\n"
			. $AppUI->_('Call Type').": {$ict[$this->item_calltype]}\n"
			. $AppUI->_('Status').": {$ist[$this->item_status]}\n";

		  if($newhdi){
		    $mail->Subject("$subject ".$AppUI->_('Created'));
		  } else {
		    $mail->Subject("$subject ".$AppUI->_('Updated'));
		    $body .= $AppUI->_('Update').": {$isa[$log['status_code']]} {$log['status_comment']}\n";
		  }

		  $body .= $AppUI->_('Link')
			 . ": {$dPconfig['base_url']}/index.php?m=helpdesk&a=view&item_id={$this->item_id}\n"
			 . "\n"
			 . $AppUI->_('Summary')
			 . ":\n"
			 . $this->item_summary;
		  break;
		case TASK_LOG:
		  $mail->Subject("$subject ".$AppUI->_('Task Log')." ".$AppUI->_('Update'));
		  $body = $AppUI->_('Summary')
			. ": "
			. $log['task_log_name']
			. "\n"
			. $AppUI->_('Link')
			. ": {$dPconfig['base_url']}/index.php?m=helpdesk&a=view&item_id={$this->item_id}\n"
			. "\n"
			. $AppUI->_('Comments')
			. ":\n" 
			. $log['task_log_description'];
		  break;
	      }

	      $body .= "\n\n-- \n"
		     . $AppUI->_('helpdeskSignature');

	      if ($mail->ValidEmail($AppUI->user_email)) {
		$email = $AppUI->user_email;
	      } else {
		$email = "dotproject@".$AppUI->cfg['site_domain'];
	      }

	      $mail->From("\"{$AppUI->user_first_name} {$AppUI->user_last_name}\" <{$email}>");
	      $mail->To($assigned_to_email);
	      $mail->Body($body, isset( $GLOBALS['locale_char_set']) ? $GLOBALS['locale_char_set'] : "");
	      $mail->Send();
      }
    }
  }
  
  function log_status_changes() {
    global $ist, $ict, $ics, $ios, $iap, $ipr, $isv, $ist, $isa,
    $field_event_map, $AppUI;

	  if(dPgetParam( $_POST, "item_id")){
      $hditem = new CHelpDeskItem();
      $hditem->load( dPgetParam( $_POST, "item_id") );

      foreach($field_event_map as $key => $value){
        if(!eval("return \$hditem->$value == \$this->$value;")){
          $old = $new = "";
          switch($value){
            // Create the comments here
            case 'item_assigned_to':
              $sql = "
                SELECT 
                  user_id, concat(contact_first_name,' ',contact_last_name) as user_name
                FROM 
                  users
               LEFT JOIN contacts ON user_contact = contact_id
                WHERE 
                  user_id in (".
                  ($hditem->$value?$hditem->$value:"").
                  ($this->$value&&$hditem->$value?", ":"").
                  ($this->$value?$this->$value:"").
                  ")
              ";

              $ids = db_loadList($sql);
              foreach ($ids as $row){
                if($row["user_id"]==$this->$value){
                  $new = $row["user_name"];
                } else if($row["user_id"]==$hditem->$value){
                  $old = $row["user_name"];
                }
              }
              break;
            case 'item_company_id':
              $sql = "
                SELECT 
                  company_id, company_name
                FROM 
                  companies
                WHERE 
                  company_id in (".
                  ($hditem->$value?$hditem->$value:"").
                  ($this->$value&&$hditem->$value?", ":"").
                  ($this->$value?$this->$value:"").
                  ")
              ";
                  
              $ids = db_loadList($sql);

              foreach ($ids as $row){
                if($row["company_id"]==$this->$value){
                  $new = $row["company_name"];
                } else if($row["company_id"]==$hditem->$value){
                  $old = $row["company_name"];
                }
              }

              break;
            case 'item_project_id':
              $sql = "
                SELECT 
                  project_id, project_name
                FROM 
                  projects
                WHERE 
                  project_id in (".
                  ($hditem->$value?$hditem->$value:"").
                  ($this->$value&&$hditem->$value?", ":"").
                  ($this->$value?$this->$value:"").
                  ")
              ";

              $ids = db_loadList($sql);
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
              break;
            default:
              $old = $hditem->$value;
              $new = $this->$value;
              break;
				  }

				  $last_status_log_id = $this->log_status($key, $AppUI->_('changed from')
                                                      . " \""
                                                      . addslashes($old)
                                                      . "\" "
                                                      . $AppUI->_('to')
                                                      . " \""
                                                      . addslashes($new)
                                                      . "\"");
			  }
		  }

      return $last_status_log_id;
	  }
  }
  
  function log_status ($audit_code, $comment="", $newhdi=0) {
  	global $AppUI;

    $sql = "
      INSERT INTO helpdesk_item_status
      (status_item_id,status_code,status_date,status_modified_by,status_comment)
      VALUES('{$this->item_id}','{$audit_code}',NOW(),'{$AppUI->user_id}','$comment')
    ";

    db_exec($sql);

    if (db_error()) {
      return false;
    }
    
    $log_id = mysql_insert_id();
    $this->notify(STATUS_LOG, $log_id, $newhdi);
    return $log_id;
  }
}

/**
* Overloaded CTask Class
*/
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

  function CHDTaskLog() {
    $this->CDpObject( 'task_log', 'task_log_id' );
  }

  // overload check method
  function check() {
    $this->task_log_hours = (float) $this->task_log_hours;
    return NULL;
  }
}

?>