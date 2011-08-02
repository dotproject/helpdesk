<?php /* HELPDESK $Id: setup.php,v 1.47 2005/10/07 16:09:10 pedroix Exp $ */

/* Issue module definitions */
$config = array();
$config['mod_name'] = 'HelpDesk';
$config['mod_version'] = '1.0.1';
$config['mod_directory'] = 'helpdesk';
$config['mod_setup_class'] = 'CSetupHelpDesk';
$config['mod_type'] = 'user';
$config['mod_config'] = true;
$config['mod_ui_name'] = 'Help Desk';
$config['mod_ui_icon'] = 'helpdesk.png';
$config['mod_description'] = 'Help Desk is a bug, feature request, '
                           . 'complaint and suggestion tracking centre';
//This will allow permissions to be applied to this module based on the following database criteria
$config['permissions_item_table'] = 'helpdesk_items';
$config['permissions_item_label'] = 'item_title';
$config['permissions_item_field'] = 'item_id';
/*
$config['permissions_item_table'] = 'companies';
$config['permissions_item_label'] = 'company_name';
$config['permissions_item_field'] = 'company_id';
*/

if (@$a == 'setup') {
	print dPshowModuleConfig( $config );
}

require_once( $dPconfig['root_dir'].'/modules/system/syskeys/syskeys.class.php');

class CSetupHelpDesk {
	function install() {
		$dbprefix = dPgetConfig('dbprefix', '');
		$success = 1;

		$bulk_sql[] = "
			CREATE TABLE `{$dbprefix}helpdesk_items` (
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
			  `item_created_by` int(11) NOT NULL default '0',
			  `item_notify` int(1) DEFAULT '1' NOT NULL ,
			  `item_requestor` varchar(48) NOT NULL default '',
			  `item_requestor_id` int(11) NOT NULL default '0',
			  `item_requestor_email` varchar(128) NOT NULL default '',
			  `item_requestor_phone` varchar(30) NOT NULL default '',
			  `item_requestor_type` tinyint NOT NULL default '0',
			  `item_created` datetime default NULL,
			  `item_modified` datetime default NULL,
			  `item_parent` int(10) unsigned NOT NULL default '0',
			  `item_project_id` int(11) NOT NULL default '0',
			  `item_company_id` int(11) NOT NULL default '0',
			  `item_task_id` int(11) default '0',
			  `item_updated` datetime default NULL,
			  `item_deadline` datetime default NULL,
			  PRIMARY KEY (`item_id`)
			);";

		$bulk_sql[] = "
			ALTER TABLE `{$dbprefix}task_log`
			  ADD `task_log_help_desk_id` int(11) NOT NULL default '0' AFTER `task_log_task`
		";

		$bulk_sql[] = "
		  CREATE TABLE `{$dbprefix}helpdesk_item_status` (
		    `status_id` int NOT NULL AUTO_INCREMENT,
		    `status_item_id` int NOT NULL,
		    `status_code` tinyint NOT NULL,
		    `status_date` timestamp NOT NULL,
		    `status_modified_by` int NOT NULL,
		    `status_comment` text,
		    PRIMARY KEY (`status_id`)
		);";

		$bulk_sql[] = "
		CREATE TABLE `{$dbprefix}helpdesk_item_watchers` (
		  `item_id` int(11) NOT NULL default '0',
		  `user_id` int(11) NOT NULL default '0',
		  `notify` char(1) NOT NULL default ''
			);";

		$bulk_sql[] = "
		  ALTER TABLE `{$dbprefix}files`
		  ADD `file_helpdesk_item` int(11) NOT NULL default '0' AFTER `file_task`
		";
		$bulk_sql[] = "CREATE INDEX idx2 ON files (file_helpdesk_item);";
		$bulk_sql[] = "CREATE INDEX idx2 ON task_log (task_log_help_desk_id);";
		$bulk_sql[] = "CREATE INDEX idx2 ON projects (project_company);";
		$bulk_sql[] = "CREATE INDEX idx2 ON tasks (task_owner, task_status);";
		$bulk_sql[] = "CREATE INDEX idx2 ON helpdesk_items (item_company_id, item_status);";
		$bulk_sql[] = "CREATE INDEX idx3 ON helpdesk_items (item_project_id);";

		foreach ($bulk_sql as $s) {
			db_exec($s);
			if (db_error()) {
				$success = 0;
			}
		}

		$sk = new CSysKey( 'HelpDeskList', 'Enter values for list', '0', "\n", '|' );
		$sk->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskPriority', "0|Not Specified\n1|Low\n2|Medium\n3|High" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskSeverity', "0|Not Specified\n1|Level 1 - Critical\n2|Level 2 - High Impact\n3|Level 3 - Low impact\n4|Level 4 - Service Request" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskCallType', "0|Not Specified\n1|Application error\n2|Feature Request\n3|Complaint\n4|Suggestion\n11|Programming Work\n21|Change management\n22|Project management\n31|Performance Issue\n32|Security\n34|Data Management\n41|Information Request" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskSource', "0|Not Specified\n1|Customer\n2|Internal Request\n3|Scheduled Task\n9|Others" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskOS', "0|Not Specified\n1|Windows XP / Server 2003\n2|Windows Vista / Server 2008\n3|Windows 7 / Server 2008 R2\n20|Mac OSX 10.4\n21|Mac OSX 10.5\n22|Mac OSX 10.6\n|Linux / Unix\n90|Other / Not listed" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskApplic', "0|Not Specified\n11|Microsoft Windows\n12|Microsoft Word\n15|Microsoft Outlook\n21|Adobe Acrobat\n22|Adobe Distiller\n31|Mozilla Firefox\n32|Mozilla Thunderbird\n41|Document Management System\n51|Web-Based App" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskStatus', "0|New\n1|Assigned\n2|Closed\n3|On Hold\n4|In Progress" );
		$sv->store();

		$sv = new CSysVal( $sk->syskey_id, 'HelpDeskAuditTrail', "0|Created\n1|Title\n2|Requestor Name\n3|Requestor E-mail\n4|Requestor Phone\n5|Assigned To\n6|Notify by e-mail\n7|Company\n8|Project\n9|Call Type\n10|Call Source\n11|Status\n12|Priority\n13|Severity\n14|Operating System\n15|Application\n16|Summary\n17|Deadline\n18|Deleted\n99|File Event" );
		$sv->store();

		if (!file_exists($CONFIG_FILE)) {
			if (!(file_put_contents('./modules/helpdesk/config.php', 'CONFIGURE_ME')))
				$success = 0;
		}
		
	  return $success;
	}

	function remove() {
		$dbprefix = dPgetConfig('dbprefix', '');
		$success = 1;

		$bulk_sql[] = "DROP TABLE `{$dbprefix}helpdesk_items`";
		$bulk_sql[] = "DROP TABLE `{$dbprefix}helpdesk_item_status`";
		$bulk_sql[] = "DROP TABLE `{$dbprefix}helpdesk_item_watchers`";
		$bulk_sql[] = "ALTER TABLE `{$dbprefix}files' DROP COLUMN `file_helpdesk_item`";
		$bulk_sql[] = "ALTER TABLE `{$dbprefix}task_log` DROP COLUMN `task_log_help_desk_id`";

		foreach ($bulk_sql as $s) {
			db_exec($s);
			if (db_error())
				$success = 0;
		}

		$sql = "
			SELECT syskey_id
			FROM {$dbprefix}syskeys
			WHERE syskey_name = 'HelpDeskList'";
		$id = db_loadResult( $sql );

	  	unset($bulk_sql);

		$bulk_sql[] = "DELETE FROM {$dbprefix}syskeys WHERE syskey_id = $id";
		$bulk_sql[] = "DELETE FROM {$dbprefix}sysvals WHERE sysval_key_id = $id";

		foreach ($bulk_sql as $s) {
			db_exec($s);
			if (db_error())
				$success = 0;
		}

		return $success;
	}

	function upgrade($old_version) {
		$dbprefix = dPgetConfig('dbprefix', '');
		$success = 1;
		if (!file_exists($CONFIG_FILE)) {
			if (!(file_put_contents('./modules/helpdesk/config.php', 'CONFIGURE_ME')))
				$success = 0;
		}

	  switch ($old_version) {
	    case "0.1":
	        // Drop unused columns, add some new columns
	        $bulk_sql[] = "
	          ALTER TABLE `{$dbprefix}helpdesk_items`
	          ADD `item_requestor_phone` varchar(30) NOT NULL default '' AFTER `item_requestor_email`,
	          ADD `item_company_id` int(11) NOT NULL default '0' AFTER `item_project_id`,
	          ADD `item_requestor_type` tinyint NOT NULL default '0' AFTER `item_requestor_phone`,
	          ADD `item_notify` int(1) DEFAULT '1' NOT NULL AFTER `item_assigned_to`,
	          ADD `item_created_by` int(11) NOT NULL default '0',
		  ADD `item_updated` datetime default NULL,
		  ADD `item_deadline` datetime default NULL,
	          DROP `item_receipt_target`,
	          DROP `item_receipt_custom`,
	          DROP `item_receipted`,
	          DROP `item_resolve_target`,
	          DROP `item_resolve_custom`,
	          DROP `item_resolved`,
	          DROP `item_assetno`";

	        // Add help desk item id to task log table
	        $bulk_sql[] = "
	          ALTER TABLE `{$dbprefix}task_log`
	          ADD `task_log_help_desk_id` int(11) NOT NULL default '0' AFTER `task_log_task`";

	        // Add help desk file id to files table
	        $bulk_sql[] = "
	          ALTER TABLE `{$dbprefix}files`
	          ADD `file_helpdesk_item` int(11) NOT NULL default '0' AFTER `file_task`";

		// Add help desk item status log table
	        $bulk_sql[] = "
	          CREATE TABLE `{$dbprefix}helpdesk_item_status` (
	            `status_id` INT NOT NULL AUTO_INCREMENT,
	            `status_item_id` INT NOT NULL,
	            `status_code` TINYINT NOT NULL,
	            `status_date` TIMESTAMP NOT NULL,
	            `status_modified_by` INT NOT NULL,
	            `status_comment` TEXT DEFAULT '',
	            PRIMARY KEY (`status_id`)
	          )";

	        // Execute the above SQL
	        foreach ($bulk_sql as $s) {
	          db_exec($s);
	          if (db_error())
	            $success = 0;
	        }

	        // Add audit trail to system values
	        $sql = "SELECT syskey_id
	                FROM ${dbprefix}syskeys
	                WHERE syskey_name = 'HelpDeskList'";
	        $syskey_id = db_loadResult( $sql );

	        $sv = new CSysVal( $syskey_id, 'HelpDeskAuditTrail', "0|Created\n1|Title\n2|Requestor Name\n3|Requestor E-mail\n4|Requestor Phone\n5|Assigned To\n6|Notify by e-mail\n7|Company\n8|Project\n9|Call Type\n10|Call Source\n11|Status\n12|Priority\n13|Severity\n14|Operating System\n15|Application\n16|Summary\n17|Deadline\n18|Deleted\n99|File Event" );
	        $sv->store();

	        // Update help desk status values
	        $sql = "UPDATE {$dbprefix}sysvals
	                SET sysval_value='0|New\n1|Assigned\n2|Closed\n3|On Hold\n4|In Progress'
	                WHERE sysval_title='HelpDeskStatus'
	                LIMIT 1";
	        db_exec($sql);

	        // Get data for conversion update
	        $sql = "SELECT item_id,item_requestor_id,item_created,item_project_id
	                FROM {$dbprefix}helpdesk_items";
	        $items = db_loadList($sql);

	        // Populate the status log table with the item's creation date
	        foreach ($items as $item) {
	          $timestamp = date('Ymdhis', db_dateTime2unix($item['item_created']));

	          $sql = "INSERT INTO {$dbprefix}helpdesk_item_status
	                    (status_item_id,status_code,status_date,status_modified_by)
	                  VALUES ({$item['item_id']},0,'$timestamp',
	                          {$item['item_requestor_id']})";
	          db_exec($sql);
	        }

	        /* Figure out the company for each item based on project id or based
	           on requestor id */
	        foreach ($items as $item) {
	          if ($item['item_project_id']) {
	            $sql = "SELECT project_company
	                    FROM {$dbprefix}projects
	                    WHERE project_id='{$item['item_project_id']}'";
	            $company_id = db_loadResult($sql);

	          } else if ($item['item_requestor_id']) {
	            $sql = "SELECT user_company
	                    FROM {$dbprefix}users
	                    WHERE user_id='{$item['item_requestor_id']}'";
	            $company_id = db_loadResult($sql);
	          }

	          if ($company_id) {
	            $sql = "UPDATE {$dbprefix}helpdesk_items
	                    SET item_company_id='$company_id'
	                    WHERE item_id='{$item['item_id']}'";
	            db_exec($sql);
	          }
	        }

	        // If our status was 5 (Testing), now it is 4 (In Progress)
	        $sql = "UPDATE {$dbprefix}helpdesk_items
	                SET item_status='4'
	                WHERE item_status='5'";
	        db_exec($sql);

	        break;
	      case 0.2:
	        // Version 0.3 features new permissions
	        $success = 1;
	        break;
	      case 0.3:
	        // Version 0.31 includes new watchers functionality
			$sql = "
			CREATE TABLE {$dbprefix}helpdesk_item_watchers (
			  `item_id` int(11) NOT NULL default '0',
			  `user_id` int(11) NOT NULL default '0',
			  `notify` char(1) NOT NULL default ''
			);";
			db_exec($sql);
		  case 0.31:
		    $sql = "
	          ALTER TABLE `{$dbprefix}helpdesk_items`
		  ADD `item_updated` datetime default NULL,
		  ADD `item_deadline` datetime default NULL";
			db_exec($sql);
		    $sql = "SELECT `item_id` FROM {$dbprefix}helpdesk_items";
			$rows = db_loadList( $sql );
			$sql = '';
			foreach ($rows as $row) {
		    	$sql = "SELECT MAX(status_date) status_date FROM helpdesk_item_status WHERE status_item_id =".$row['item_id'];
				$sdrow = db_loadList( $sql );

			    $sql = '';
				$sql = "UPDATE `helpdesk_items`
	    	  	SET `item_updated`='".$sdrow[0]['status_date']."' 
	    	  	WHERE `item_id`=".$row['item_id'];
				db_exec($sql);			
			}
		if (db_error())
			$success = 0;
		else  
		        $success = 1;
	        break;

	     case 0.4:
	     case 0.5:
	     case 0.6:
		// These changes are to help bridge people from the "old" helpdesk
		// to the newer helpdesk module.  Errors are ignored in case the 
		// modified helpdesk with files support was already installed.
		// TODO: Detection of table existance before blind creation with
		// no error return
	        $bulk_sql[] = "
			ALTER TABLE `{$dbprefix}files`
			ADD `file_helpdesk_item` int(11) NOT NULL default '0' AFTER `file_task`";

	     	$bulk_sql[] = "
			ALTER TABLE `{$dbprefix}helpdesk_items`
			ADD `item_deadline` datetime default NULL,
			ADD `item_task_id` int(11) default '0'";

	        foreach ($bulk_sql as $s) {
	          db_exec($s);
	          //if (db_error())
	            //$success = 0;
	        }

	     default:
	        $success = 0;
		  }

	// NOTE: Need to return true, not null, if all is good
	return $success;
	}

  function configure() {
    global $AppUI;

    $AppUI->redirect("m=helpdesk&a=configure");

    return true;
  }
}
?>


