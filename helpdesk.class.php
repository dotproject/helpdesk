<?php /* HELPDESK $Id: helpdesk.class.php,v 1.3 2004/04/15 17:32:01 adam Exp $ */
require_once( $AppUI->getSystemClass( 'dp' ) );
require_once( $AppUI->getSystemClass( 'libmail' ) );

// some standard arrays
$ict = dPgetSysVal( 'HelpDeskCallType' );
$ics = dPgetSysVal( 'HelpDeskSource' );
$ios = dPgetSysVal( 'HelpDeskOS' );
$iap = dPgetSysVal( 'HelpDeskApplic' );
$ipr = dPgetSysVal( 'HelpDeskPriority' );
$isv = dPgetSysVal( 'HelpDeskSeverity' );
$ist = dPgetSysVal( 'HelpDeskStatus' );

##
## CHelpDeskItem Class
##

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

	var $item_assigned_to = NULL;
	var $item_requestor = NULL;
	var $item_requestor_id = NULL;
	var $item_requestor_email = NULL;
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
		// TODO MORE
		return NULL; // object is ok
	}

	function store() {
    return parent::store();
	}

	function delete() {
    return parent::delete();
	}
  
  function notify() {
    // TODO Not localized

    $sql = "SELECT user_email
            FROM users
            WHERE user_id='{$this->item_assigned_to}'";

    $assigned_to_email = db_loadResult($sql);

    $mail = new Mail;

    if ($mail->ValidEmail($assigned_to_email)) {
      $mail->From('"'.$this->item_requestor.'" <'.$this->item_requestor_email.'>');
      $mail->To($assigned_to_email);
      $mail->Subject("Help Desk item #".$this->item_id." has been updated");
      $mail->Body($this->item_title."\n\n".$this->item_summary);
      $mail->Send();
    }
  }
}
?>
