<?php /* HELPDESK $Id: setup.php,v 1.14 2004/04/22 17:25:11 bloaterpaste Exp $ */

/* Help Desk module definitions */
$config = array();
$config['mod_name'] = 'HelpDesk';
$config['mod_version'] = '0.2';
$config['mod_directory'] = 'helpdesk';
$config['mod_setup_class'] = 'CSetupHelpDesk';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Help Desk';
$config['mod_ui_icon'] = 'helpdesk.png';
$config['mod_description'] = 'Help Desk is a bug, feature request, '
                           . 'complaint and suggestion tracking centre';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

require_once( $AppUI->cfg['root_dir'].'/modules/system/syskeys/syskeys.class.php');

class CSetupHelpDesk {
	function install() {
		$sql = "
			CREATE TABLE helpdesk_items (
			  `item_id` int(11) unsigned NOT NULL auto_increment,
			  `item_title` varchar(64) NOT NULL default '',
			  `item_summary` text,
			  `item_calltype` int(3) unsigned NOT NULL default '0',
			  `item_source` int(3) unsigned NOT NULL default '0',
			  `item_os` varchar(48) NOT NULL default '',
			  `item_application` varchar(48) NOT NULL default '',
			  `item_priority` int(3) unsigned NOT NULL default '0',
			  `item_severity` int(3) unsigned NOT NULL default '0',
			  `item_status` int(3) unsigned NOT NULL default '0',
			  `item_assigned_to` int(11) NOT NULL default '0',
			  `item_requestor` varchar(48) NOT NULL default '',
			  `item_requestor_id` int(11) NOT NULL default '0',
			  `item_requestor_email` varchar(128) NOT NULL default '',
			  `item_requestor_phone` varchar(30) NOT NULL default '',
			  `item_requestor_type` tinyint NOT NULL default '0',
			  `item_assetno` varchar(24) NOT NULL default '',
			  `item_created` datetime default NULL,
			  `item_modified` datetime default NULL,
			  `item_receipt_target` datetime default NULL,
			  `item_receipt_custom` int(1) unsigned NOT NULL default '0',
			  `item_receipted` datetime default NULL,
			  `item_resolve_target` datetime default NULL,
			  `item_resolve_custom` int(1) unsigned NOT NULL default '0',
			  `item_resolved` datetime default NULL,
			  `item_parent` int(10) unsigned NOT NULL default '0',
			  `item_project_id` int(11) NOT NULL default '0',
			  `item_company_id` int(11) NOT NULL default '0',
			  PRIMARY KEY (item_id)
			) TYPE=MyISAM";

		db_exec( $sql );

		$sql = "ALTER TABLE `task_log` ADD `task_log_help_desk_id` int(11) NOT NULL default '0' AFTER `task_log_task`";
		db_exec( $sql );

		$sk = new CSysKey( 'HelpDeskList', 'Enter values for list', '0', "\n", '|' );
		$sk->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskPriority', "0|Not Specified\n1|Low\n2|Medium\n3|High" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskSeverity', "0|Not Specified\n1|No Impact\n2|Low\n3|Medium\n4|High\n5|Critical" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskCallType', "0|Not Specified\n1|Bug\n2|Feature Request\n3|Complaint\n4|Suggestion" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskSource', "0|Not Specified\n1|E-Mail\n2|Phone\n3|Fax\n4|In Person\n5|E-Lodged\n6|WWW" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskOS', "Not Applicable\nLinux\nUnix\nSolaris 8\nSolaris 9\nRed Hat 6\nRed Hat 7\nRed Hat 8\nWindows 95\nWindow 98\nWindows 2000\nWindow 2000 Server\nWindows XP" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskApplic', "Not Applicable\nWord\nExcel" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskStatus', "0|Unassigned\n1|Open\n2|Closed\n3|On Hold" );
		$sv->store();

		return null;
	}

	function remove() {
		$sql = "DROP TABLE helpdesk_items";
		db_exec( $sql );
		
		$sql = "ALTER TABLE `task_log` DROP COLUMN `task_log_help_desk_id`";
		db_exec( $sql );

		$sql = "SELECT syskey_id FROM syskeys WHERE syskey_name = 'HelpDeskList'";
		$id = db_loadResult( $sql );

		$sql = "DELETE FROM syskeys WHERE syskey_id = $id";
		db_exec( $sql );

		$sql = "DELETE FROM sysvals WHERE sysval_key_id = $id";
		db_exec( $sql );

		return null;
	}

	function upgrade($old_version) {
    global $AppUI;

    switch ($old_version) {
      case "0.1":
        $sql[] = "
          ALTER TABLE `helpdesk_items`
          ADD `item_requestor_phone` varchar(30) NOT NULL default '' AFTER `item_requestor_email`,
          ADD `item_company_id` int(11) NOT NULL default '0' AFTER `item_project_id`,
          ADD `item_requestor_type` tinyint NOT NULL default '0' AFTER `item_requestor_phone`;
        ";

        $sql[] = "
          ALTER TABLE `task_log`
          ADD `task_log_help_desk_id` int(11) NOT NULL default '0' AFTER `task_log_task`;
        ";
        break;
      default:
        return false;
    }

    foreach ($sql as $s) {
      db_exec($s);

      if (db_error()) {
        /* Setting a message with $AppUI-setMsg would be pointless since it's
           just overwritten */
        return false;
      }
    }
  
    // NOTE: Need to return true, not null, if all is good
    return true;
	}
}

?>
