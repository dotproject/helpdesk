<?php /* HELPDESK $Id: helpdesk.class.php,v 1.12 2004/04/22 14:54:21 agorski Exp $ */
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
  var $item_requestor = NULL;
  var $item_requestor_id = NULL;
  var $item_requestor_email = NULL;
  var $item_requestor_phone = NULL;
  var $item_requestor_type = NULL;
  var $item_assetno = NULL;

  var $item_created = NULL;
  var $item_modified = NULL;
  var $item_receipt_target = NULL;
  var $item_receipt_custom = NULL;
  var $item_receipted = NULL;
  var $item_resolve_target = NULL;
  var $item_resolve_custom = NULL;
  var $item_resolved = NULL;

  function CHelpDeskItem() {
    $this->CDpObject( 'helpdesk_items', 'item_id' );
  }

  function load( $oid ) {
    $sql = "SELECT * FROM helpdesk_items WHERE item_id = $oid";
    return db_loadObject( $sql, $this );
  }

  function check() {
    if ($this->item_id === NULL) {
      return 'helpdesk item id is NULL';
    }
    if (!$this->item_created) { 
      $this->item_created = db_unix2dateTime( time() );
    }
    
    // TODO More checks
    return NULL;
  }

  function store() {
    //if the status is changed to #2 "Closed" mark as resolved.
    if ($this->item_status==2) { 
      $this->item_resolved = db_unix2dateTime( time() );
    }

    // Update the last modified time
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

    if($sql){
      db_loadHash( $sql, $result );
      $this->item_requestor_email = $result['email'];
      $this->item_requestor_phone = $result['phone'];
      $this->item_requestor = $result['name'];
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
}
?>
