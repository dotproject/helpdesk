<?php
/* This file will write a php config file to be included during execution of
 * all helpdesk files which require the configuration options. */

// Deny all but system admins
if (getDenyEdit('system')) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

@include_once( "./functions/admin_func.php" );

$CONFIG_FILE = "./modules/helpdesk/config.php";

$AppUI->savePlace();


// Get a list of companies
$sql = "SELECT company_id, company_name
        FROM companies
        ORDER BY company_name";

$res = db_exec($sql);

// Add "No Company"
$companies['-1'] = '';

while ($row = db_fetch_assoc($res)) {
  $companies[$row['company_id']] = $row['company_name'];
}

// Define user type list
$user_types = arrayMerge( $utypes, array( '-1' => $AppUI->_('None') ) );

/* All config options, their descriptions and their default values are defined
* here. Add new config options here. Type can be "checkbox", "text", "radio" or
* "select". If the type is "radio," it must include a set of buttons. If it's
* "select" then be sure to include a 'list' entry with the options.  if the key
* starts with headingXXX then it will just display the contents on the value.
* This is used for grouping.
*/
$config_options = array(
	"heading1" => $AppUI->_('Paging Options'),
	"items_per_page" => array(
		"description" => $AppUI->_('helpdeskItemsPerPage'),
		"value" => 30,
		'type' => 'text'
	),
	"status_log_items_per_page" => array(
		"description" => $AppUI->_('helpdeskLogsPerPage'),
		"value" => 15,
		'type' => 'text'
	),
	"pages_per_side" => array(
		"description" => $AppUI->_('helpdeskPagesPerSide'),
		"value" => 5,
		'type' => 'text'
	),
	"heading2" => $AppUI->_('Permission Options'),
	"the_company" => array(
		"description" => $AppUI->_('helpdeskHDCompany'),
		"value" => '',
		'type' => 'select',
		'list' => $companies
	),
	"no_company_editable" => array(
		"description" => $AppUI->_('helpdeskItemsNoCompany'),
		"value" => '0',
		'type' => 'radio',
    		'buttons' => array (1 => $AppUI->_('Yes'),
                        0 => $AppUI->_('No'))
	),
	'minimum_edit_level' => array(
		'description' => $AppUI->_('helpdeskMinLevel'),
		'value' => 0,
		'type' => 'select',
		'list' => @$user_types
	),
	"use_project_perms" => array(
		"description" => $AppUI->_('helpdeskUseProjectPerms'),
		"value" => '0',
		'type' => 'radio',
    		'buttons' => array (1 => $AppUI->_('Yes'),
                        0 => $AppUI->_('No'))
	),
	"heading3" => $AppUI->_('New Item Default Selections'),
	"default_assigned_to_current_user" => array(
		"description" => $AppUI->_('helpdeskDefCurUser'),
		"value" => 1,
		'type' => 'radio',
    'buttons' => array (1 => $AppUI->_('Yes'),
                       0 => $AppUI->_('No'))
	),
	"default_notify_by_email" => array(
		"description" => $AppUI->_('helpdeskDefNotify'),
		"value" => 1,
		'type' => 'radio',
    'buttons' => array (1 => $AppUI->_('Yes'),
                       0 => $AppUI->_('No'))
	),
	"default_company_current_company" => array(
		"description" => $AppUI->_('helpdeskDefCompany'),
		"value" => 1,
		'type' => 'radio',
    'buttons' => array (1 => $AppUI->_('Yes'),
                       0 => $AppUI->_('No'))
	),
	"heading4" => $AppUI->_('Search Fields On Item List'),
	"search_criteria_search" => array(
		"description" => $AppUI->_('Title/Summary Search'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_call_type" => array(
		"description" => $AppUI->_('Call Type'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_company" => array(
		"description" => $AppUI->_('Company'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_status" => array(
		"description" => $AppUI->_('Status'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_call_source" => array(
		"description" => $AppUI->_('Call Source'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_project" => array(
		"description" => $AppUI->_('Project'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_assigned_to" => array(
		"description" => $AppUI->_('Assigned To'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_priority" => array(
		"description" => $AppUI->_('Priority'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_application" => array(
		"description" => $AppUI->_('Application'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_requestor" => array(
		"description" => $AppUI->_('Requestor'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_severity" => array(
		"description" => $AppUI->_('Severity'),
		"value" => 1,
		'type' => 'checkbox'
	),
	"search_criteria_os" => array(
		"description" => $AppUI->_('Operating System'),
		"value" => 1,
		'type' => 'checkbox'
	)

);

//if this is a submitted page, overwrite the config file.
if(dPgetParam( $_POST, "Save", '' )!=''){

	if (is_writable($CONFIG_FILE)) {
		if (!$handle = fopen($CONFIG_FILE, 'w')) {
			$AppUI->setMsg( $CONFIG_FILE." ".$AppUI->_('cannot be opened'), UI_MSG_ERROR );
			exit;
		}

		if (fwrite($handle, "<?php //Do not edit this file by hand, it will be overwritten by the configuration utility. \n") === FALSE) {
			$AppUI->setMsg( $CONFIG_FILE." ".$AppUI->_('cannot be written to'), UI_MSG_ERROR );
			exit;
		} else {
			foreach ($config_options as $key=>$value){
			  if(substr($key,0,7)=='heading') continue;

				$val="";
				switch($value['type']){
					case 'checkbox': 
						$val = isset($_POST[$key])?"1":"0";
						break;
					case 'text': 
						$val = isset($_POST[$key])?$_POST[$key]:"";
						break;
					case 'select': 
						$val = isset($_POST[$key])?$_POST[$key]:"0";
						break;
					case 'radio':
						$val = $_POST[$key];
						break;
					default:
						break;
				}
				
				fwrite($handle, "\$HELPDESK_CONFIG['".$key."'] = '".$val."';\n");
			}

			fwrite($handle, "?>\n");
			$AppUI->setMsg( $CONFIG_FILE." ".$AppUI->_('has been successfully updated'), UI_MSG_OK );
			fclose($handle);
			require( $CONFIG_FILE );
		}
	} else {
		$AppUI->setMsg( $CONFIG_FILE." ".$AppUI->_('is not writable'), UI_MSG_ERROR );
	}
} else if(dPgetParam( $_POST, $AppUI->_('cancel'), '' )!=''){
	$AppUI->redirect("m=system&a=viewmods");
}

//$HELPDESK_CONFIG = array();
require_once( $CONFIG_FILE );

//Read the current config values from the config file and update the array.
foreach ($config_options as $key=>$value){
	if(isset($HELPDESK_CONFIG[$key])){

		$config_options[$key]['value']=$HELPDESK_CONFIG[$key];
	}
}

// setup the title block
$titleBlock = new CTitleBlock( 'Configure Help Desk Module', 'helpdesk.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=system", "System Admin" );
$titleBlock->addCrumb( "?m=system&a=viewmods", "Modules" );
$titleBlock->show();

?>

<form method="post">
<table class="std">
<?php
foreach ($config_options as $key=>$value){
?>
	<tr>
		<?php
    // the key starts with hr, then just display the value
	  if(substr($key,0,7)=='heading'){ ?>
		  <th align="center" colspan="2"><?=$value?></th>
		<?php } else { ?>
		<td align="right"><?=$value['description']?></td>
		<td><?php
      switch($value['type']){
        case 'checkbox': ?>
          <input type="checkbox" name="<?=$key?>" <?=$value['value']?"checked=\"checked\"":""?>>
          <?php
          break;
        case 'text': ?>
          <input type="text" name="<?=$key?>" value="<?=$value['value']?>">
          <?php
          break;
        case 'select': 
          print arraySelect( $value["list"], $key, 'class="text" size="1" id="' . $key . '" ' . $value["events"], $value["value"] );
          break;
        case 'radio':
          foreach ($value['buttons'] as $v => $n) {?>
            <label><input type="radio" name="<?php echo $key; ?>" id="<?php echo $key; ?>" value=<?php echo $v; ?> <?php echo (($value['value'] == $v)?"checked":""); ?> <?php echo $value['events']; ?>> <?php echo $n;?></label>
          <?php }
          break;
        default:
          break;
      }
		?></td>
		<?php
			}
		?>
	</tr>
<?php	
}
?>
	<tr>
		<td colspan="2" align="right"><input type="Submit" name="Cancel" value="<?=$AppUI->_('back')?>">
                                  <input type="Submit" name="Save" value="<?=$AppUI->_('save')?>"></td>
	</tr>
</table>
</form>
