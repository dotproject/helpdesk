<?php /* HELPDESK $Id: setup.php,v 1.3 2004/04/05 19:15:45 adam Exp $ */
/*
dotProject Module

Name:      HelpDesk
Directory: HelpDesk
Version:   0.1
Class:     user
UI Name:   HelpDesk
UI Icon:

This file does no action in itself.
If it is accessed directory it will give a summary of the module parameters.
*/

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'HelpDesk';
$config['mod_version'] = '0.1';
$config['mod_directory'] = 'helpdesk';
$config['mod_setup_class'] = 'CSetupHelpDesk';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Help Desk';
$config['mod_ui_icon'] = 'helpdesk.png';
$config['mod_description'] = 'Help Desk is a bug, feature request,
                              complaint and suggestion tracking centre';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

require_once( $AppUI->cfg['root_dir'].'/modules/system/syskeys/syskeys.class.php' );

/*
// MODULE SETUP CLASS
	This class must contain the following methods:
	install - creates the required db tables
	remove - drop the appropriate db tables
	upgrade - upgrades tables from previous versions
*/
class CSetupHelpDesk {
/*
	Install routine
*/
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
			  PRIMARY KEY (item_id)
			) TYPE=MyISAM
		";
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
/*
	Removal routine
*/
	function remove() {
		$sql = "DROP TABLE helpdesk_items";
		db_exec( $sql );

		$sql = "SELECT syskey_id FROM syskeys WHERE syskey_name = 'HelpDeskList'";
		$id = db_loadResult( $sql );

		$sql = "DELETE FROM syskeys WHERE syskey_id = $id";
		db_exec( $sql );

		$sql = "DELETE FROM sysvals WHERE sysval_key_id = $id";
		db_exec( $sql );

		return null;
	}
/*
	Upgrade routine
*/
	function upgrade() {
		return null;
	}
}

?>
