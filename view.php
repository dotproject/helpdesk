<?php /* COMPANIES $Id: view.php,v 1.39 2004/04/26 20:20:56 bloaterpaste Exp $ */
$AppUI->savePlace();

$item_id = dPgetParam( $_GET, 'item_id', 0 );
// retrieve any state parameters
if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'HelpLogVwTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'HelpLogVwTab' ) !== NULL ? $AppUI->getState( 'HelpLogVwTab' ) : 0;

// Pull data
$sql = "SELECT hi.*,
        CONCAT(u.user_first_name,' ',u.user_last_name) assigned_to_fullname,
        CONCAT(u1.user_first_name,' ',u1.user_last_name) created_by_fullname,
        CONCAT(u2.user_first_name,' ',u2.user_last_name) modified_by_fullname,
        u2.user_email as modified_by_email,
        u.user_email as assigned_email,
        p.project_id,
        p.project_name,
        p.project_color_identifier,
        c.company_name
        FROM helpdesk_items hi
        LEFT JOIN users u ON u.user_id = hi.item_assigned_to
        LEFT JOIN users u1 ON u1.user_id = hi.item_created_by
        LEFT JOIN users u2 ON u2.user_id = hi.item_modified_by
        LEFT JOIN projects p ON p.project_id = hi.item_project_id
        LEFT JOIN companies c ON c.company_id = hi.item_company_id
        WHERE item_id = '$item_id'";

if (!db_loadHash( $sql, $hditem )) {
	$titleBlock = new CTitleBlock( 'Invalid Helpdesk ID', 'helpdesk.png', $m, 'ID_HELP_HELPDESK_VIEW' );
	$titleBlock->addCrumb( "?m=helpdesk", "Home" );
	$titleBlock->addCrumb( "?m=helpdesk&a=list", "List" );
	$titleBlock->show();
} else {
  $sql = "SELECT *,
          TRIM(CONCAT(u.user_first_name,' ',u.user_last_name)) modified_by, u.user_email as email
          FROM helpdesk_item_status h
          LEFT JOIN users u ON u.user_id = h.status_modified_by
          WHERE h.status_item_id='{$hditem['item_id']}'
          ORDER BY h.status_date";

  $status_log = db_loadList($sql);

  $name = $hditem['item_requestor'];
  $assigned_to_name = $hditem["item_assigned_to"] ? $hditem["assigned_to_fullname"] : "";
  $assigned_email = $hditem["assigned_email"];

	// User's specified format for date and time
	$df = $AppUI->getPref('SHDATEFORMAT');
	$tf = $AppUI->getPref('TIMEFORMAT');
	$format = "$df $tf";
  $tc = $tm = $tr = "";

	if(@$hditem["item_created"]){
		$created = new CDate( @$hditem["item_created"] );
		$tc = $created->format( $format );
	}

	if(@$hditem["item_modified"]){
		$modified = new CDate( @$hditem["item_modified"] );
		$tm = $modified->format( $tf );
	}

	$titleBlock = new CTitleBlock( "Viewing Help Desk Item #{$hditem["item_id"]}", 'helpdesk.png',
                                 $m, 'ID_HELP_HELPDESK_IDX' );
	$titleBlock->addCrumb( "?m=helpdesk", "Home" );
	$titleBlock->addCrumb( "?m=helpdesk&a=list", "List" );

	if ($canEdit) {
    $titleBlock->addCrumbDelete("Delete this item", 1);
		$titleBlock->addCell(
			'<input type="submit" class="button" value="'.$AppUI->_('New Item').'">', '',
			'<form action="?m=helpdesk&a=addedit" method="post">', '</form>'
		);
		$titleBlock->addCrumb( "?m=helpdesk&a=addedit&item_id=$item_id", "Edit this item" );
	}
	$titleBlock->show();
?>

<script language="JavaScript">
function delIt() {
  if (confirm( "<?php print $AppUI->_('doDelete').' '.$AppUI->_('item').'?';?>" )) {
    document.frmDelete.submit();
  }
}
</script>

<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std">
<form name="frmDelete" action="./index.php?m=helpdesk&a=list" method="post">
  <input type="hidden" name="dosql" value="do_item_aed">
  <input type="hidden" name="del" value="1" />
  <input type="hidden" name="item_id" value="<?=$item_id?>" />
</form>
<tr>
	<td valign="top" width="50%">
		<strong><?=$AppUI->_('Details')?></strong>
		<table cellspacing="1" cellpadding="2" width="100%">
		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Item Number')?>:</td>
			<td class="hilite" width="100%"><?=$hditem["item_id"]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Title')?>:</td>
			<td class="hilite" width="100%"><?=$hditem["item_title"]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Requestor')?>:</td>
			<td class="hilite" width="100%"><?php
        print $hditem["item_requestor_email"] ? 
          "<a href=\"mailto:".$hditem["item_requestor_email"]."\">".$hditem['item_requestor']."</a>" :
          $hditem['item_requestor'];?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Requestor Phone')?>:</td>
			<td class="hilite" width="100%"><?=$hditem["item_requestor_phone"]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Assigned To')?>:</td>
			<td class="hilite" width="100%"><?php
        print $assigned_email ?
          "<a href=\"mailto:$assigned_email\">$assigned_to_name</a>" :
          $assigned_to_name;?></td>
		</tr>

    <tr>
      <td align="right" nowrap="nowrap"><?=$AppUI->_('Company')?>:</td>
			<td class="hilite" width="100%"><?=$hditem["company_name"]?></td>
    </tr>

    <tr>
      <td align="right" nowrap="nowrap"><?=$AppUI->_('Project')?>:</td>
      <td class="hilite" width="100%" style="background-color: #<?=$hditem['project_color_identifier']?>;"><a href="./index.php?m=projects&a=view&project_id=<?=$hditem["project_id"]?>"><?=$hditem["project_name"]?></a></td>
    </tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Call Type')?>:</td>
			<td class="hilite" width="100%"><?php
        print $ict[$hditem["item_calltype"]]." ";
        print dPshowImage (dPfindImage( 'ct'.$hditem["item_calltype"].'.png', $m ), 15, 17, 'align=center');
      ?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Call Source')?>:</td>
			<td class="hilite" width="100%"><?=@$ics[$hditem["item_source"]]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Status')?>:</td>
			<td class="hilite" width="100%"><?=@$ist[$hditem["item_status"]]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Priority')?>:</td>
			<td class="hilite" width="100%"><?=@$ipr[$hditem["item_priority"]]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Severity')?>:</td>
      <td class="hilite" width="100%"><?=@$ics[$hditem["item_severity"]]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Operating System')?>:</td>
			<td class="hilite" width="100%"><?=@$ios[$hditem["item_os"]]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Application')?>:</td>
			<td class="hilite" width="100%"><?=@$iap[$hditem["item_application"]]?></td>
		</tr>
		</table>
		<strong><?=$AppUI->_('Summary')?></strong>
		<table cellspacing="0" cellpadding="2" border="0" width="100%">
		<tr>
			<td class="hilite"><?=str_replace( chr(10), "<br />", $hditem["item_summary"])?>&nbsp;</td>
		</tr>
		</table>

	</td>
	<td width="50%" valign="top">
		<strong><?=$AppUI->_('Status Log')?></strong>
		<table cellspacing="1" cellpadding="2" border="0" width="100%" bgcolor="black">
    <?php
    $last_date = "";
    foreach ($status_log as $log) {
		  $log_date = new CDate($log['status_date']);
		  $date = $log_date->format( $df );
		  if($date!=$last_date){
		  	$last_date = $date;
		  ?>
      <tr>
        <th nowrap="nowrap" colspan="3"><?=$date?>:</th>
      </tr>
		  <?php
		  }
		
		  $time = $log_date->format( $tf );
      ?>
      <tr>
        <td class="hilite" nowrap="nowrap" width="1%"><?=$time?></td>
        <td class="hilite" nowrap="nowrap" width="1%"><?=($log['email']?"<a href=\"mailto: {$log['email']}\">{$log['modified_by']}</a>":$log['modified_by'])?></td>
        <td class="hilite" width="98%"><?php
        	if($log['status_code']==0 || $log['status_code']==17){
        		print $isa[$log['status_code']];
        	} else {
        		print $isa[$log['status_code']]." ".$log['status_comment'];
        	}
        ?></td>
      </tr>
      <?php
    }
    ?>
    <tr>
        <td class="hilite" nowrap="nowrap" width="1%"><?=$tm?></td>
        <td class="hilite" nowrap="nowrap" width="1%"><?=($hditem['modified_by_fullname'] ? ($hditem['modified_by_email']?"<a href=\"mailto: {$hditem['modified_by_email']}\">{$hditem['modified_by_fullname']}</a>":$hditem['modified_by_fullname']) : $AppUI->_('unknown')) ?></td>
        <td class="hilite" nowrap="nowrap" width="98%"><?=$AppUI->_('Last Modified')?></td>
    </tr>
		</table>
	</td>
</tr>
<tr>
	<td valign="top" colspan="2">
	</td>
</tr>
</table>
<?php 

$tabBox = new CTabBox( "?m=helpdesk&a=view&item_id=$item_id", "", $tab );
//if ( $obj->task_dynamic == 0 ) {
	// tabbed information boxes
$tabBox->add( "{$AppUI->cfg['root_dir']}/modules/helpdesk/vw_logs", 'Task Logs' );
	// fixed bug that dP automatically jumped to access denied if user does not
	// have read-write permissions on task_id and this tab is opened by default (session_vars)
	// only if user has r-w perms on this task, new or edit log is beign showed
//	if (!getDenyEdit( $m, $task_id )) {
$tabBox->add( "{$AppUI->cfg['root_dir']}/modules/helpdesk/vw_log_update", 'New Log' );
//	}
//}
$tabBox->show();
} 
?>
