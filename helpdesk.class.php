<?php /* HELPDESK $Id: helpdesk.class.php,v 1.2 2003/04/12 00:16:54 eddieajau Exp $ */

require_once( "{$AppUI->cfg['root_dir']}/classes/dp.class.php" );

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
/*
	function store() {
		$msg = $this->check();
		if( $msg ) {
			return get_class( $this )."::store-check failed";
		}
		if( $this->item_id ) {
			$ret = db_updateObject( 'helpdesk_items', $this, 'item_id' );
		} else {
			$ret = db_insertObject( 'helpdesk_items', $this, 'item_id' );
		}
		if( !$ret ) {
			return get_class( $this )."::store failed <br />" . db_error();
		} else {
			return NULL;
		}
	}
	function delete() {
			$sql = "DELETE FROM helpdesk_items WHERE item_id = $this->item_id";
			if (!db_exec( $sql )) {
				return db_error();
			} else {
				return NULL;
			}
	}
*/
}
?>
