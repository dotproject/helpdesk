<?php /* HELPDESK $Id: vw_idx_watched.php,v 1.2 2007/06/07 14:54:24 cyberhorse Exp $ */

$sql = "SELECT hi.*,
       CONCAT(contact_first_name,' ',contact_last_name) assigned_fullname,
       contact_email as assigned_email,
        p.project_id,
        p.project_name,
        p.project_color_identifier
        FROM helpdesk_items hi
        INNER JOIN helpdesk_item_watchers hiw ON hiw.item_id = hi.item_id
        LEFT JOIN users u2 ON u2.user_id = hiw.user_id
        LEFT JOIN contacts ON u2.user_contact = contacts.contact_id
        LEFT JOIN projects p ON p.project_id = hi.item_project_id
	WHERE hiw.user_id = $AppUI->user_id
	";
	
#print $sql;

$rows = db_loadlist($sql);

?>
<script language="javascript">
function changeList() {
	document.filterFrm.submit();
}
</script>
<? 
$ipr = dPgetSysVal('HelpDeskPriority');
$ist = dPgetSysVal('HelpDeskStatus');
?>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<!--<td align="right" nowrap>&nbsp;</td>-->
	<th nowrap="nowrap"><? echo $AppUI->_('Number')?></th>
	<th nowrap="nowrap"><? echo $AppUI->_('Requestor')?></th>
	<th nowrap="nowrap"><? echo $AppUI->_('Title')?></th>
	<!--<th nowrap="nowrap"><?=sort_header("item_assigned_to", $AppUI->_('Assigned To'))?></th>-->
	<th nowrap="nowrap"><? echo $AppUI->_('Status')?></th>
	<th nowrap="nowrap"><? echo $AppUI->_('Priority')?></th>
	<th nowrap="nowrap"><? echo $AppUI->_('Project')?></th>
</tr>
<?php
$s = '';

foreach ($rows as $row) {
  /* We need to check if the user who requested the item is still in the
     system. Just because we have a requestor id does not mean we'll be
     able to retrieve a full name */

	$s .= $CR . '<form method="post">';
	$s .= $CR . '<tr>';
	#$s .= $CR . '<td align="right" nowrap>';

	#if ($canEdit) {
	#	$s .= $CR . '<a href="?m=helpdesk&a=addedit&item_id='
        #      . $row["item_id"]
        #      . '">'
        #      . dPshowImage("./images/icons/pencil.gif", 12, 12, "edit")
        #      . '</a>&nbsp;';
	#}

	#$s .= $CR . '</td>';
	$s .= $CR . '<td><a href="./index.php?m=helpdesk&a=view&item_id='
            . $row["item_id"]
            . '">'
		        . '<strong>'
            . $row["item_id"]
            . '</strong></a> '
	    . '-'
            . '</td>';

	$s .= $CR . "<td nowrap align=\"center\">";
	if ($row["item_requestor_email"]) {
		$s .= $CR . "<a href=\"mailto:".$row["item_requestor_email"]."\">"
              . $row['item_requestor']
              . "</a>";
	} else {
		$s .= $CR . $row['item_requestor'];
	}
	$s .= $CR . "</td>";

	$s .= $CR . '<td width="80%"><a href="?m=helpdesk&a=view&item_id='
            . $row["item_id"]
            . '">'
		        . $row["item_title"]
            . '</a></td>';
	#$s .= $CR . "<td nowrap align=\"center\">";
	#if ($row["assigned_email"]) {
	#	$s .= $CR . "<a href=\"mailto:".$row["assigned_email"]."\">"
        #      . $row['assigned_fullname']
        #      . "</a>";
	#} else {
	#	$s .= $CR . $row['assigned_fullname'];
	#}
	#$s .= $CR . "</td>";
	$s .= $CR . '<td align="center" nowrap>' . $ist[@$row["item_status"]] . '</td>';
	$s .= $CR . '<td align="center" nowrap>' . $ipr[@$row["item_priority"]] . '</td>';
	if($row['project_id']){
		$s .= $CR . '<td align="center" style="background-color: #'
		    . $row['project_color_identifier']
		    . ';" nowrap><a href="./index.php?m=projects&a=view&project_id='
		    . $row['project_id'].'">'.$row['project_name'].'</a></td>';
	} else {
		$s .= $CR . '<td align="center">-</td>';
	}
	$s .= $CR . '</tr></form>';
}

print "$s\n";
?>
</table>

<?php
// Returns a header link used to sort results
// TODO Probably need a better up/down arrow
function sort_header($field, $name) {
  global $orderby, $orderdesc;

  $arrow = "";

  $link = "<a class=\"hdr\" href=\"?m=helpdesk&a=list&orderby=$field&orderdesc=";

  if ($orderby == $field) {
    $link .= $orderdesc ? "0" : "1";
    $arrow .= $orderdesc ? " &uarr;" : " &darr;";
  } else {
    $link .= "0";
  }

  $link .= "\">$name</a>$arrow";

  return $link;
}
?>
