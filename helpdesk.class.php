<?php /* HELPDESK $Id: helpdesk.class.php,v 1.17 2004/04/23 18:11:49 agorski Exp $ */
require_once( $AppUI->getSystemClass( 'dp' ) );
require_once( $AppUI->getSystemClass( 'libmail' ) );

// Pull in some standard arrays
$ict = dPgetSysVal( 'HelpDeskCallType' );
$ics = dPgetSysVal( 'HelpDeskSource' );
$ios = dPgetSysVal( 'HelpDeskOS' );
$iap = dPgetSysVal( 'HelpDeskApplic' );
$ipr = dPgetSysVal( 'HelpDeskPriority' );
$isv = dPgetSysVal( 'HelpDeskSeverity' );
$ist = dPgetSysVal( 'HelpDeskStatus' );
$isa = dPgetSysVal( 'HelpDeskAuditTrail' );

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
  var $item_assetno = NULL;

  var $item_created = NULL;
  var $item_created_by = NULL;
  var $item_modified = NULL;
  var $item_modified_by = NULL;

  function CHelpDeskItem() {
    $this->CDpObject( 'helpdesk_items', 'item_id' );
  }

  function load( $oid ) {
    $sql = "SELECT * FROM helpdesk_items WHERE item_id = $oid";
    return db_loadObject( $sql, $this );
  }

  function check() {
    if ($this->item_id === NULL) {
      return 'Help Desk item id is NULL';
    }
    if (!$this->item_created) { 
      $this->item_created = db_unix2dateTime( time() );
    }
    
    // TODO More checks
    return NULL;
  }

  function store() {
    global $AppUI;

    // Update the last modified time and user
    $this->item_modified = db_unix2dateTime( time() );
    $this->item_modified_by = $AppUI->user_id;

    //if type indicates a contact or a user, then look up that phone and email for those entries
    switch ($this->item_requestor_type) {
      case '0'://it's not a user or a contact
        break;
      case '1'://it's a system user
        $sql = "SELECT user_id as id,
                user_email as email,
                user_phone as phone,
                CONCAT(user_first_name,' ', user_last_name) as name
                FROM users
                WHERE user_id='{$this->item_requestor_id}'";
        break;
      case '2':
        $sql = "SELECT contact_id as id,
                contact_email as email,
                contact_phone as phone,
                CONCAT(contact_first_name,' ', contact_last_name) as name
                FROM contacts
                WHERE contact_id='{$this->item_requestor_id}'";
        break;
      default:
        break;
    }

    if(isset($sql)) {
      db_loadHash( $sql, $result );
      $this->item_requestor_email = $result['email'];
      $this->item_requestor_phone = $result['phone'];
      $this->item_requestor = $result['name'];
    }
      
    if ($this->item_notify) {
      $this->notify();
    }

    return parent::store();
  }

  function delete() {
    return parent::delete();
  }
  
  function notify() {
    global $AppUI, $ist, $ict;

    // TODO Not localized

    $sql = "SELECT user_email
            FROM users
            WHERE user_id='{$this->item_assigned_to}'";

    $assigned_to_email = db_loadResult($sql);

    $mail = new Mail;

    if ($mail->ValidEmail($assigned_to_email)) {
      $body = "Title: {$this->item_title}\n"
            . "Call Type: {$ict[$this->item_calltype]}\n"
            . "Status: {$ist[$this->item_status]}\n"
            . "Link: {$AppUI->cfg['base_url']}/index.php?m=helpdesk&a=view&item_id={$this->item_id}\n"
            . "\nSummary:\n\n"
            . $this->item_summary
            . "\n\n-- \n"
            . "Sincerely,\nThe dotProject Help Desk module";

      if ($mail->ValidEmail($this->item_requestor_email)) {
        $email = $this->item_requestor_email;
      } else {
        $email = "dotproject@".$AppUI->cfg['site_domain'];
      }

      $mail->From("\"".$this->item_requestor."\" <{$email}>");
      $mail->To($assigned_to_email);
      $mail->Subject($AppUI->cfg['page_title']." Help Desk item #".$this->item_id);
      $mail->Body($body);
      $mail->Send();
    }
  }

  function log_status_changes(){

	$hditem = new CHelpDeskItem();
	$hditem->load( dPgetParam( $_POST, "item_id") );

	//0=>Created
	$field_event_map = array(
		1=>"item_title",//Title
		2=>"item_requestor",//Requestor Name
		3=>"item_requestor_email",//Requestor E-mail
		4=>"item_requestor_phone",//Requestor Phone
		5=>"item_assigned_to",//Assigned To
		6=>"item_notify",//Notify by e-mail
		7=>"item_company_id",//Company
		8=>"item_project_id",//Project
		9=>"item_calltype",//Call Type
		10=>"item_source",//Call Source
		11=>"item_status",//Status
		12=>"item_priority",//Priority
		13=>"item_severity",//Severity
		14=>"item_os",//Operating System
		15=>"item_application",//Application
		16=>"item_summary",//Summary
	);
	//19=>Deleted

	$c=0;
	foreach($field_event_map as $key => $value){
		if(!eval("return \$hditem->$value == \$this->$value;")){
			switch($key){
      // Create the comments here
//					case '':
//						break;
				default:
					$this->log_status($key);
				break;
			}
		}
		$c++;
	}

  }
  
  function log_status ($audit_code, $comment="") {
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
      
      return true;

  }
}

/**
* CTask Class
*/
class CTaskLog extends CDpObject {
	var $task_log_id = NULL;
	var $task_log_task = NULL;
	var $task_log_help_desk_id = NULL;
	var $task_log_name = NULL;
	var $task_log_description = NULL;
	var $task_log_creator = NULL;
	var $task_log_hours = NULL;
	var $task_log_date = NULL;
	var $task_log_costcode = NULL;

	function CTaskLog() {
		$this->CDpObject( 'task_log', 'task_log_id' );
	}

// overload check method
	function check() {
		$this->task_log_hours = (float) $this->task_log_hours;
		return NULL;
	}
}
?>
