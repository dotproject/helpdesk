<?php /* COMPANIES $Id: view.php,v 1.8 2004/01/23 19:14:58 adam Exp $ */
  #include( "../../misc/debug.php" );

$AppUI->savePlace();

$item_id = isset($_GET['item_id']) ? $_GET['item_id'] : 0;

foreach( $_GET as $key => $value ) {
	writeDebug( "$value", "$key", __FILE__, __LINE__ );
}

// pull data
$sql = "
SELECT hi.*,
	CONCAT(u1.user_first_name,' ',u1.user_last_name) user_fullname,
	CONCAT(u2.user_first_name,' ',u2.user_last_name) assigned_to_fullname,
	u1.user_email,
  p.project_id,
  p.project_name
FROM helpdesk_items hi
LEFT JOIN users u1 ON u1.user_id = hi.item_requestor_id
LEFT JOIN users u2 ON u2.user_id = hi.item_assigned_to
LEFT OUTER JOIN projects p ON p.project_id = hi.item_project_id
WHERE item_id = '$item_id'
";

#writeDebug( "$sql", "sql", __FILE__, __LINE__ );

if (!db_loadHash( $sql, $hditem )) {
	#writeDebug( "false", "db_loadHash", __FILE__, __LINE__ );
	
	$titleBlock = new CTitleBlock( 'Invalid Helpdesk ID', 'helpdesk.png', $m, 'ID_HELP_HELPDESK_VIEW' );
	$titleBlock->addCrumb( "?m=helpdesk", "Index" );
	$titleBlock->show();
} else {
	#writeDebug( "true", "db_loadHash", __FILE__, __LINE__ );
	
	$email = $hditem["user_email"] ? $hditem["user_email"] : $hditem["item_requestor_email"];
	$name = $hditem["item_requestor_id"] ? $hditem["user_fullname"] : $hditem["item_requestor"];
	$assigned_to_name = $hditem["item_assigned_to"] ? $hditem["assigned_to_fullname"] : "";

	$ts = db_dateTime2unix( @$hditem["item_created"] );
	$tc = $ts < 0 ? null : date( "m/d/y g:i a", $ts );

	$ts = db_dateTime2unix( $hditem["item_modified"] );
	$tm = $ts < 0 ? null : date( "m/d/y g:i a", $ts );

	writeDebug( "$tm", "tm", __FILE__, __LINE__ );

	$titleBlock = new CTitleBlock( "Viewing Help Desk Item #{$hditem["item_id"]}", 'helpdesk.png', $m, 'ID_HELP_HELPDESK_IDX' );
	$titleBlock->addCrumb( "?m=helpdesk", "Home" );
	$titleBlock->addCrumb( "?m=helpdesk&a=list", "Index" );
	if ($canEdit) {
		$titleBlock->addCell(
			'<input type="submit" class="button" value="'.$AppUI->_('new item').'">', '',
			'<form action="?m=helpdesk&a=addedit" method="post">', '</form>'
		);
		$titleBlock->addCrumb( "?m=helpdesk&a=addedit&item_id=$item_id", "Edit this item" );
	}
	$titleBlock->show();
?>

<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std">
<tr>
	<td valign="top" width="50%">
		<strong><?php echo $AppUI->_('Details');?></strong>
		<table cellspacing="1" cellpadding="2" width="100%">
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Item Number');?>:</td>
			<td class="hilite" width="100%"><strong><?php echo $hditem["item_id"];?></strong></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Title');?>:</td>
			<td class="hilite" width="100%"><strong><?php echo $hditem["item_title"];?></strong></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Requestor');?>:</td>
			<td class="hilite" width="100%"><strong><?php echo $name;?></strong></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Status');?>:</td>
			<td class="hilite" width="100%"><strong><?php echo $ist[$hditem["item_status"]];?></strong></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Priority');?>:</td>
			<td class="hilite" width="100%"><strong><?php echo $ipr[$hditem["item_priority"]];?></strong></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Assigned To');?>:</td>
			<td class="hilite" width="100%"><strong><?php echo $assigned_to_name;?></strong></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Call Type');?>:</td>
			<td class="hilite" width="100%"><?php echo $ict[$hditem["item_calltype"]];?></td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Call Source');?>:</td>
			<td class="hilite" width="100%"><?php echo $ics[$hditem["item_source"]];?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Operating System');?>:</td>
			<td class="hilite" width="100%"><?php echo $hditem["item_os"];?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Application');?>:</td>
			<td class="hilite" width="100%"><?php echo $hditem["item_application"];?></td>
		</tr>
    <tr>
      <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Project');?>:</td>
      <td class="hilite" width="100%"><a href="./index.php?m=projects&a=view&project_id=<?=$hditem["project_id"]?>"><?=$hditem["project_name"]?></a></td>
    </tr>
		</table>

	</td>
	<td width="50%" valign="top">
		<strong><?php echo $AppUI->_('Time Lines');?></strong>
		<table cellspacing="1" cellpadding="2" border="0" width="100%">
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Opened');?>:</td>
			<td class="hilite" width="100%"><?php echo $tc;?></td>
		</tr>

		<tr><td align="right" nowrap="nowrap" colspan="2">&nbsp;</td></tr>

		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Receipt Target');?>:</td>
			<td class="hilite" width="100%"><?php echo $hditem["item_receipt_target"];?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Receipt Negotiated');?>:</td>
			<td class="hilite" width="100%"><?php echo $hditem["item_receipt_custom"];?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Receipt Actual');?>:</td>
			<td class="hilite" width="100%"><?php echo $hditem["item_receipted"];?></td>
		</tr>

		<tr><td align="right" nowrap="nowrap" colspan="2">&nbsp;</td></tr>

		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Resolved Target');?>:</td>
			<td class="hilite" width="100%"><?php echo $hditem["item_resolve_target"];?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Resolved Negotiated');?>:</td>
			<td class="hilite" width="100%"><?php echo $hditem["item_resolve_custom"];?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Resolved Actual');?>:</td>
			<td class="hilite" width="100%"><?php echo $hditem["item_resolved"];?></td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td valign="top" colspan="2">
		<strong><?php echo $AppUI->_('Summary');?></strong>
		<table cellspacing="0" cellpadding="2" border="0" width="100%">
		<tr>
			<td class="hilite">
				<?php echo str_replace( chr(10), "<br />", $hditem["item_summary"]);?>&nbsp;
			</td>
		</tr>
		</table>
	</td>
	<td valign="top"> &nbsp;
<!--		<strong><?php echo $AppUI->_('Action Log');?></strong>
		<br />
		<?php
			//$log = array( 'this is', 'the history', 'log', 'TODO' );
			//echo arraySelect( $log, '', 'size="8" disabled="disabled"', -1 );
		?>
-->
	</td>
</tr>
<tr>
	<td colspan="2" align="right">
		<?php echo $AppUI->_('Last Modified').' '.$tm;?>
	</td>
</tr>
</table>
<?php } ?>
