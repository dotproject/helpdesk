<?php /* COMPANIES $Id: view.php,v 1.21 2004/04/19 18:24:12 adam Exp $ */
$AppUI->savePlace();

$item_id = dPgetParam( $_POST, 'item_id', 0 );

// Pull data
$sql = "SELECT hi.*,
        CONCAT(u1.user_first_name,' ',u1.user_last_name) user_fullname,
        CONCAT(u2.user_first_name,' ',u2.user_last_name) assigned_to_fullname,
        u1.user_email as user_email,
        u2.user_email as assigned_email,
        p.project_id,
        p.project_name,
        p.project_color_identifier
        FROM helpdesk_items hi
        LEFT JOIN users u1 ON u1.user_id = hi.item_requestor_id
        LEFT JOIN users u2 ON u2.user_id = hi.item_assigned_to
        LEFT OUTER JOIN projects p ON p.project_id = hi.item_project_id
        WHERE item_id = '$item_id'";

if (!db_loadHash( $sql, $hditem )) {
	$titleBlock = new CTitleBlock( 'Invalid Helpdesk ID', 'helpdesk.png', $m, 'ID_HELP_HELPDESK_VIEW' );
	$titleBlock->addCrumb( "?m=helpdesk", "Home" );
	$titleBlock->addCrumb( "?m=helpdesk&a=list", "List" );
	$titleBlock->show();
} else {
  /* We need to check if the user who requested the item is still in the
     system. Just because we have a requestor id does not mean we'll be
     able to retrieve a full name */
  if ($hditem["item_requestor_id"]) {
	  $name = $hditem["user_fullname"] ? $hditem["user_fullname"] : $hditem["item_requestor"];
  } else {
    $name = $hditem['item_requestor'];
  }

	$assigned_to_name = $hditem["item_assigned_to"] ? $hditem["assigned_to_fullname"] : "";

	$email = $hditem["user_email"] ? $hditem["user_email"] : $hditem["item_requestor_email"];
  $assigned_email = $hditem["assigned_email"];

	$ts = db_dateTime2unix( @$hditem["item_created"] );
	$tc = $ts < 0 ? null : date( "m/d/Y g:i a", $ts );

	$ts = db_dateTime2unix( $hditem["item_modified"] );
	$tm = $ts < 0 ? null : date( "m/d/Y g:i a", $ts );

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
  if (confirm( "<?php echo $AppUI->_('doDelete').' '.$AppUI->_('item').'?';?>" )) {
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
			<td class="hilite" width="100%"><?php print $email ? "<a href=\"mailto:$email\">$name</a>" : $name;?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Status')?>:</td>
			<td class="hilite" width="100%"><?=$ist[$hditem["item_status"]]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Priority')?>:</td>
			<td class="hilite" width="100%"><?=$ipr[$hditem["item_priority"]]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Assigned To')?>:</td>
			<td class="hilite" width="100%"><?php print $assigned_email ? "<a href=\"mailto:$assigned_email\">$assigned_to_name</a>" : $assigned_to_name;?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Call Type')?>:</td>
			<td class="hilite" width="100%"><?php echo dPshowImage (dPfindImage( 'ct'.$hditem["item_calltype"].'.png', $m ), 15, 17, 'align=center');
                                            echo " ".$ict[$hditem["item_calltype"]];?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Call Source')?>:</td>
			<td class="hilite" width="100%"><?=$ics[$hditem["item_source"]]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Operating System')?>:</td>
			<td class="hilite" width="100%"><?=$ics[$hditem["item_os"]]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Application')?>:</td>
			<td class="hilite" width="100%"><?=$ics[$hditem["item_application"]]?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Severity')?>:</td>
			<td class="hilite" width="100%"><?=$ics[$hditem["item_severity"]]?></td>
		</tr>

    <tr>
      <td align="right" nowrap="nowrap"><?=$AppUI->_('Project')?>:</td>
      <td class="hilite" width="100%" style="background-color: #<?=$hditem['project_color_identifier']?>;"><a href="./index.php?m=projects&a=view&project_id=<?=$hditem["project_id"]?>"><?=$hditem["project_name"]?></a></td>
    </tr>
		</table>

	</td>
	<td width="50%" valign="top">
		<strong><?=$AppUI->_('Time Lines')?></strong>
		<table cellspacing="1" cellpadding="2" border="0" width="100%">
		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Opened')?>:</td>
			<td class="hilite" width="100%"><?=$tc;?></td>
		</tr>

    <tr>
      <td align="right" nowrap="nowrap"><?=$AppUI->_('Last Modified')?>:</td>
      <td class="hilite" width="100%"><?=$tm;?></td>
    </tr>

		<tr><td align="right" nowrap="nowrap" colspan="2">&nbsp;</td></tr>

    <?php /* Commented out until implemented 
		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Receipt Target')?>:</td>
			<td class="hilite" width="100%"><?=$hditem["item_receipt_target"]?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Receipt Negotiated')?>:</td>
			<td class="hilite" width="100%"><?=$hditem["item_receipt_custom"]?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Receipt Actual')?>:</td>
			<td class="hilite" width="100%"><?=$hditem["item_receipted"]?></td>
		</tr>

		<tr><td align="right" nowrap="nowrap" colspan="2">&nbsp;</td></tr>

		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Resolved Target')?>:</td>
			<td class="hilite" width="100%"><?=$hditem["item_resolve_target"]?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Resolved Negotiated')?>:</td>
			<td class="hilite" width="100%"><?=$hditem["item_resolve_custom"]?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?=$AppUI->_('Resolved Actual')?>:</td>
			<td class="hilite" width="100%"><?=$hditem["item_resolved"]?></td>
		</tr>
    */ ?>
		</table>
	</td>
</tr>
<tr>
	<td valign="top" colspan="2">
		<strong><?=$AppUI->_('Summary')?></strong>
		<table cellspacing="0" cellpadding="2" border="0" width="100%">
		<tr>
			<td class="hilite"><?=str_replace( chr(10), "<br />", $hditem["item_summary"])?>&nbsp;</td>
		</tr>
		</table>
	</td>
	<td valign="top"> &nbsp;
<?php
/* This is interesting, but I'm not sure where it was going...
<strong><?=$AppUI->_('Action Log')?></strong>
<br>
//$log = array( 'this is', 'the history', 'log', 'TODO' );
//echo arraySelect( $log, '', 'size="8" disabled="disabled"', -1 );
*/
?>
	</td>
</tr>
</table>
<?php } ?>
