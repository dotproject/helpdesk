<?php /* HELPDESK $Id */
/*
$sql = "
SELECT item_id, item_title, item_created,
	user_username
FROM helpdesk_items
LEFT JOIN users ON user_id = item_assigned_to
ORDER BY item_id DESC
LIMIT 10
";
*/




/*  select items created today with 'closed' status
 *  
 *  unassigned = 0, open = 1, closed = 2, on hold = 3
 */
$sql = "
SELECT item_id, item_title, item_created, user_username
FROM helpdesk_items
LEFT JOIN users ON user_id = item_assigned_to
WHERE (TO_DAYS(NOW()) - TO_DAYS(item_created) = 0)
AND (item_status = 2)
ORDER BY item_id DESC
";

$newitems = db_loadList( $sql );
?>
<table cellspacing="1" cellpadding="2" border="0" width="100%" class="tbl">
<tr>
	<th></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Title');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Created On');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Assigned To');?></th>
</tr>
<?php
	$s = '';
	foreach ($newitems as $row) {
		$df = $AppUI->getPref( 'SHDATEFORMAT' );
		$tf = $AppUI->getPref( 'TIMEFORMAT' );

		$ts = db_dateTime2unix( $row["item_created"] );
		$tc = $ts < 0 ? null : date( "m.d.y g:i a", $ts );
		#$tc = $ts < 0 ? null : new CDate( $ts, $df );

		$s .= '<tr>';
		$s .= '<td><a href="?m=helpdesk&a=view&item_id=' . $row['item_id'] . '">#&nbsp;' . $row['item_id'] . '</a></td>';
		$s .= '<td width="100%">' . $row['item_title'] . '</td>';
		$s .= '<td nowrap="nowrap">' . ($tc ? $tc : '-') . '</td>';
		$s .= '<td>' . $row['user_username'] . '</td>';
		$s .= '</tr>';
	}

  if( $s == '' ) {
    $s = "<tr><td colspan=4><p><font color=red><i>No items were closed today</i></font><p></td></tr>\n";
  }

	echo $s;
?>
</table>
