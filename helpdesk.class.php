<?php /* HELPDESK $Id: helpdesk.class.php,v 1.48 2004/05/28 15:51:29 agorski Exp $ */
require_once( $AppUI->getSystemClass( 'dp' ) );
require_once( $AppUI->getSystemClass( 'libmail' ) );
require_once("helpdesk.functions.php");

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
    
    $this->item_summary = strip_tags($this->item_summary);

    //if type indicates a contact or a user, then look up that phone and email
    //for those entries
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
    return parent::delete();
  }
  
  function notify($type, $log_id) {
    global $AppUI, $ist, $ict, $isa;

    if (!$this->item_notify ||
        ($this->item_assigned_to == $AppUI->user_id)) {
      return;
    }

    // Pull up the assignee's e-mail
    $sql = "SELECT user_email
            FROM users
            WHERE user_id='{$this->item_assigned_to}'";

    $assigned_to_email = db_loadResult($sql);

    if (is_numeric($log_id)) {
      switch ($type) {
        case STATUS_LOG:
          $sql = "SELECT status_code, status_comment
                  FROM helpdesk_item_status
                  WHERE status_id=$log_id";
          break;
        case TASK_LOG:
          $sql = "SELECT task_log_name,task_log_description
                  FROM task_log
                  WHERE task_log_id=$log_id";
          break;
      }
        
      db_loadHash($sql, $log);
    }

    $mail = new Mail;

    if ($mail->ValidEmail($assigned_to_email)) {
      $subject = $AppUI->cfg['page_title']." Help Desk Item #{$this->item_id}";

      switch ($type) {
        case STATUS_LOG:
          $body = "Title: {$this->item_title}\n"
                . "Call Type: {$ict[$this->item_calltype]}\n"
                . "Status: {$ist[$this->item_status]}\n";

          if($log['status_code'] == 0){
            $mail->Subject("$subject Created");
          } else {
            $mail->Subject("$subject Updated");
            $body .= "Update: {$isa[$log['status_code']]} {$log['status_comment']}\n";
          }

          $body .= "Link: {$AppUI->cfg['base_url']}/index.php?m=helpdesk&a=view&item_id={$this->item_id}\n"
                 . "\nSummary:\n"
                 . $this->item_summary;
          break;
        case TASK_LOG:
          $mail->Subject("$subject Task Log Update");
          $body = "Summary: "
                . $log['task_log_name']
                . "\nLink: {$AppUI->cfg['base_url']}/index.php?m=helpdesk&a=view&item_id={$this->item_id}\n"
                . "\nComments:\n" 
                . $log['task_log_description'];
          break;
      }
      
      $body .= "\n\n-- \n"
             . "Sincerely,\nThe dotProject Help Desk module";

      if ($mail->ValidEmail($AppUI->user_email)) {
        $email = $AppUI->user_email;
      } else {
        $email = "dotproject@".$AppUI->cfg['site_domain'];
      }

      $mail->From("\"{$AppUI->user_first_name} {$AppUI->user_last_name}\" <{$email}>");
      $mail->To($assigned_to_email);
      $mail->Body($body);
      $mail->Send();
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
                  user_id, concat(user_first_name,' ',user_last_name) as user_name
                FROM 
                  users
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
              $old = $ict[$hditem->$value];
              $new = $ict[$this->$value];
              break;
            case 'item_source':
              $old = $ics[$hditem->$value];
              $new = $ics[$this->$value];
              break;
            case 'item_status':
              $old = $ist[$hditem->$value];
              $new = $ist[$this->$value];
              break;
            case 'item_priority':
              $old = $ipr[$hditem->$value];
              $new = $ipr[$this->$value];
              break;
            case 'item_severity':
              $old = $isv[$hditem->$value];
              $new = $isv[$this->$value];
              break;
            case 'item_os':
              $old = $ios[$hditem->$value];
              $new = $ios[$this->$value];
              break;
            case 'item_application':
              $old = $iap[$hditem->$value];
              $new = $iap[$this->$value];
              break;
            case 'item_notify':
              $old = $hditem->$value ? "on" : "off";
              $new = $this->$value ? "on" : "off";
              break;
            default:
              $old = $hditem->$value;
              $new = $this->$value;
              break;
				  }

				  $last_status_log_id = $this->log_status($key, "changed from \""
                                                         .addslashes($old)
                                                         ."\" to \""
                                                         .addslashes($new)
                                                         ."\"");
			  }
		  }

      return $last_status_log_id;
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
    
    return mysql_insert_id();
  }
}

/**
* Overloaded CTask Class
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
