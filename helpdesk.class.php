<?php /* HELPDESK $Id: helpdesk.class.php,v 1.21 2004/04/26 21:09:51 bloaterpaste Exp $ */
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
  var $item_assetno = NULL;

  var $item_created = NULL;
  var $item_modified = NULL;

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
  
  function log_status_changes() {
    global $ist, $ict, $ics, $ios, $iap, $ipr, $isv, $ist, $isa, $field_event_map, $AppUI;



	if(dPgetParam( $_POST, "item_id")){
		$hditem = new CHelpDeskItem();
		$hditem->load( dPgetParam( $_POST, "item_id") );
		foreach($field_event_map as $key => $value){
			if(!eval("return \$hditem->$value == \$this->$value;")){
				switch($value){
					// Create the comments here
					case 'item_assigned_to':
						$sql = "
							SELECT 
								concat(u1.user_first_name,' ',u1.user_last_name) as old_user_name, 
								concat(u2.user_first_name,' ',u2.user_last_name) as new_user_name 
							FROM 
								users as u1
								JOIN users as u2
							WHERE 
								u1.user_id ={$hditem->$value} AND
								u2.user_id ={$this->$value}
						";
						db_loadHash( $sql, $ids );
						$this->log_status($key, "changed from \"".$ids['old_user_name']."\" to \"".$ids['new_user_name']."\"");
						break;
					case 'item_company_id':
						$sql = "
							SELECT 
								c1.company_name as old_company_name, 
								c2.company_name as new_company_name 
							FROM 
								companies as c1
								JOIN companies as c2
							WHERE 
								c1.company_id ={$hditem->$value} AND
								c2.company_id ={$this->$value}
						";

						db_loadHash( $sql, $ids );
						$this->log_status($key, "changed from \"".$ids['old_company_name']."\" to \"".$ids['new_company_name']."\"");
						break;
					case 'item_project_id':
						$sql = "
							SELECT 
								p1.project_name as old_project_name, 
								p2.project_name as new_project_name 
							FROM 
								projects as p1
								JOIN projects as p2
							WHERE 
								p1.project_id ={$hditem->$value} AND
								p2.project_id ={$this->$value}
						";
//$AppUI->setMsg($sql);

						db_loadHash( $sql, $ids );
						$this->log_status($key, "changed from \"".$ids['old_project_name']."\" to \"".$ids['new_project_name']."\"");
						break;
					case 'item_calltype':
						$this->log_status($key, "changed from \"".$ict[$hditem->$value]."\" to \"".$ict[$this->$value]."\"");
						break;
					case 'item_source':
						$this->log_status($key, "changed from \"".$ics[$hditem->$value]."\" to \"".$ics[$this->$value]."\"");
						break;
					case 'item_status':
						$this->log_status($key, "changed from \"".$ist[$hditem->$value]."\" to \"".$ist[$this->$value]."\"");
						break;
					case 'item_priority':
						$this->log_status($key, "changed from \"".$ipr[$hditem->$value]."\" to \"".$ipr[$this->$value]."\"");
						break;
					case 'item_severity':
						$this->log_status($key, "changed from \"".$isv[$hditem->$value]."\" to \"".$isv[$this->$value]."\"");
						break;
					case 'item_os':
						$this->log_status($key, "changed from \"".$ios[$hditem->$value]."\" to \"".$ios[$this->$value]."\"");
						break;
					case 'item_application':
						$this->log_status($key, "changed from \"".$iap[$hditem->$value]."\" to \"".$iap[$this->$value]."\"");
						break;
					default:
	//					$this->log_status($key);
						$this->log_status($key, "changed from \"{$hditem->$value}\" to \"{$this->$value}\"");
					break;
				}
			}
		}
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
