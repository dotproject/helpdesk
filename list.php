<?php /* HELPDESK $Id: list.php,v 1.13 2004/01/23 16:28:59 adam Exp $ */
$AppUI->savePlace();

// check sort order
if (isset( $_GET['orderby'] )) {
	$AppUI->setState( 'HelpDeskIdxOrderBy', $_GET['orderby'] );
}
$orderby = $AppUI->getState( 'HelpDeskIdxOrderBy' ) ? $AppUI->getState( 'HelpDeskIdxOrderBy' ) : 'item_id';

// check for search text
$search = dPgetParam( $_GET, 'search', '' );

// check for calltype filter
if (isset( $_GET['item_calltype'] )) {
	$AppUI->setState( 'HelpDeskCallType', $_GET['item_calltype'] );
}
$calltype = $AppUI->getState( 'HelpDeskCallType' ) !== null ? $AppUI->getState( 'HelpDeskCallType' ) : -1;

// check for status filter
if (isset( $_GET['item_status'] )) {
	$AppUI->setState( 'HelpDeskStatus', $_GET['item_status'] );
}
$status = $AppUI->getState( 'HelpDeskStatus' ) !== null ? $AppUI->getState( 'HelpDeskStatus' ) : -1;

// check for status filter
if (isset( $_GET['item_priority'] )) {
	$AppUI->setState( 'HelpDeskPriority', $_GET['item_priority'] );
}
$priority = $AppUI->getState( 'HelpDeskPriority' ) !== null ? $AppUI->getState( 'HelpDeskPriority' ) : -1;

$tarr = array();
if ($search) {
	$tarr[] = "(hi.item_title LIKE '%$search%' OR hi.item_summary LIKE '%$search%')";
}
if ($calltype >= 0) {
	$tarr[] = "hi.item_calltype=$calltype";
}
if ($status >= 0) {
	$tarr[] = "hi.item_status=$status";
}
$where = '';
if (count( $tarr )) {
	$where = 'WHERE ' . implode( ' AND ', $tarr );
}

$sql = "
SELECT hi.*,
	CONCAT(u1.user_first_name,' ',u1.user_last_name) user_fullname,
	u1.user_email,
	CONCAT(u2.user_first_name,' ',u2.user_last_name) assigned_fullname,
  p.project_id,
  p.project_name
FROM helpdesk_items hi
LEFT JOIN users u1 ON u1.user_id = hi.item_requestor_id
LEFT JOIN users u2 ON u2.user_id = hi.item_assigned_to
LEFT OUTER JOIN projects p ON p.project_id = hi.item_project_id
$where
ORDER BY hi.$orderby
";
//echo "<pre>$sql</pre>";
$rows = db_loadList( $sql );

// setup the title block
$titleBlock = new CTitleBlock( 'Help Desk', 'helpdesk.png', $m, 'ID_HELP_HELPDESK_IDX' );
if ($canEdit) {
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('new item').'" />', '',
		'<form action="?m=helpdesk&a=addedit" method="post">', '</form>'
	);
}
$titleBlock->addCrumb( "?m=helpdesk", "home" );
$titleBlock->show();

?>
<script language="javascript">
function changeList() {
	document.filterFrm.submit();
}
</script>

<table border="0" cellpadding="2" cellspacing="1" class="std">
<form name="filterFrm" action="?index.php" method="get">
	<input type="hidden" name="m" value="<?php echo $m;?>" />
	<input type="hidden" name="a" value="<?php echo $a;?>" />
<tr>
	<td><?php echo $AppUI->_('search');?>:</td>
	<td><input type="text" name="search" class="text" value="<?php echo $search;?>"></td>
	<td align="right" nowrap><?php echo $AppUI->_('Call Type');?>:</td>
	<td>
<?php
	echo arraySelect( arrayMerge( array( '-1'=>'All' ), $ict ), 'item_calltype', 'size="1" class="text" onchange="changeList()"', $calltype );
?>
	</td>
	<td align="right"><?php echo $AppUI->_('Status');?>:</td>
	<td>
<?php
	echo arraySelect( arrayMerge( array( '-1'=>'All' ), $ist ), 'item_status', 'size="1" class="text" onchange="changeList()"', $status );
?>
	</td>
	<td align="right"><?php echo $AppUI->_('Priority');?>:</td>
	<td>
<?php
	echo arraySelect( arrayMerge( array( '-1'=>'All' ), $ipr ), 'item_priority', 'size="1" class="text" onchange="changeList()"', $priority );
?>
	</td>
	<td align="right" width="100%">
		<input type="submit" value="<?php echo $AppUI->_('search');?>" class="button" />
	</td>
</tr>
</form>
</table>

<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<td align="right" nowrap>&nbsp;<?php echo $AppUI->_('sort by');?>:&nbsp;</td>
	<th nowrap="nowrap">
		<a href="?m=helpdesk&a=list&orderby=item_id" class="hdr"><?php echo $AppUI->_('Number');?></a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=helpdesk&a=list&orderby=item_requestor" class="hdr"><?php echo $AppUI->_('Requestor');?></a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=helpdesk&a=list&orderby=item_title" class="hdr"><?php echo $AppUI->_('Title');?></a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=helpdesk&a=list&orderby=item_assigned_to" class="hdr"><?php echo $AppUI->_('Assigned To');?></a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=helpdesk&a=list&orderby=item_status" class="hdr"><?php echo $AppUI->_('Status');?></a>
	</th>
	<th nowrap="nowrap">
		<a href="?m=helpdesk&a=list&orderby=item_priority" class="hdr"><?php echo $AppUI->_('Priority');?></a>
	</th>
	<th nowrap="nowrap">
		<?php echo $AppUI->_('Project');?>
	</th>
	<th>&nbsp;</th>
</tr>
<?php
$s = '';
foreach ($rows as $row) {
	$name = $row["item_requestor_id"] ? $row["user_fullname"] : $row["item_requestor"];
	$email = $row["user_email"] ? $row["user_email"] : $row["item_requestor_email"];

	$s .= $CR . '<form method="post">';
	$s .= $CR . '<tr>';
	$s .= $CR . '<td align="right">';
	if ($email) {
		$s .= $CR . '<a href="mailto:' . $email . '"><img src="images/obj/email.gif" width="16" height="16" border="0" alt="' . $email . '"></a>';
	}
	if ($canEdit) {
		$s .= $CR . '<a href="?m=helpdesk&a=addedit&item_id='.$row["item_id"].'"><img src="./images/icons/pencil.gif" alt="edit" border="0" width="12" height="12"></a>';
	}
	$s .= $CR . '</td>';
	$s .= $CR . '<td><a href="./index.php?m=helpdesk&a=view&item_id=' . $row["item_id"] . '">'
		.'<strong># ' . $row["item_id"] .'</strong></a></td>';
	$s .= $CR . '<td nowrap>' . $name . '</td>';
	$s .= $CR . '<td width="99%">' . $CR. '<table cellspacing="0" cellpadding="0" border="0">' . $CR . '<tr>'
		. $CR .'<td width="17"><img src="'.dPfindImage( 'ct'.$row["item_calltype"].'.png', $m ).'" width="15" height="17" border=0 alt="' . $ict[@$row["item_calltype"]] . '" /></td>'
		. $CR .'<td><a href="?m=helpdesk&a=view&item_id='.$row["item_id"].'">'
		. $row["item_title"] . '</a></td></tr></table></td>';
	$s .= $CR . '<td align="center" nowrap>' . @$row["assigned_fullname"] . '</td>';
	$s .= $CR . '<td align="center" nowrap>' . $ist[@$row["item_status"]] . '</td>';
	$s .= $CR . '<td align="center" nowrap>' . $ipr[@$row["item_priority"]] . '</td>';
	$s .= $CR . '<td align="center" nowrap><a href="./index.php?m=projects&a=view&project_id='.$row['project_id'].'">'.$row['project_name'].'</a></td>';
	$s .= $CR . '<td align="center" nowrap><input type="checkbox" name="batch[]" value="' . @$row["item_id"] . '"</td>';
	$s .= $CR . '</tr></form>';
}
echo "$s\n";
?>
</table>
