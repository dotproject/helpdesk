<?php /* HELPDESK $Id: vw_idx_new.php,v 1.6 2004/04/19 18:51:28 adam Exp $*/
global $m, $ipr;

/*  select items created today with 'unassigned' or 'open' status
 *  unassigned = 0, open = 1, closed = 2, on hold = 3
 */
$sql = "SELECT hi.*,
        CONCAT(u1.user_first_name,' ',u1.user_last_name) user_fullname,
        u1.user_email,
        CONCAT(u2.user_first_name,' ',u2.user_last_name) assigned_fullname,
        p.project_id,
        p.project_name,
        p.project_color_identifier
        FROM helpdesk_items hi
        LEFT JOIN users u1 ON u1.user_id = hi.item_requestor_id
        LEFT JOIN users u2 ON u2.user_id = hi.item_assigned_to
        LEFT OUTER JOIN projects p ON p.project_id = hi.item_project_id
        WHERE (TO_DAYS(NOW()) - TO_DAYS(item_created) = 0)
        AND (item_status = 0 OR item_status = 1)
        ORDER BY item_id DESC";

$newitems = db_loadList( $sql );
?>
<table cellspacing="1" cellpadding="2" border="0" width="100%" class="tbl">
<tr>
	<th><?=$AppUI->_('Number')?></th>
	<th><?=$AppUI->_('Requestor')?></th>
	<th><?=$AppUI->_('Title')?></th>
	<th nowrap="nowrap"><?=$AppUI->_('Assigned To')?></th>
  <th><?=$AppUI->_('Priority')?></th>
	<th><?=$AppUI->_('Project')?></th>
	<th nowrap="nowrap"><?=$AppUI->_('Created On')?></th>
</tr>
<?php
	$s = '';
	foreach ($newitems as $row) {
    /* We need to check if the user who requested the item is still in the
       system. Just because we have a requestor id does not mean we'll be
       able to retrieve a full name */
    if ($row["item_requestor_id"]) {
      $name = $row["user_fullname"] ? $row["user_fullname"] : $row["item_requestor"];
    } else {
      $name = $row['item_requestor'];
    }

    $email = $row["user_email"] ? $row["user_email"] : $row["item_requestor_email"];

		$df = $AppUI->getPref( 'SHDATEFORMAT' );
		$tf = $AppUI->getPref( 'TIMEFORMAT' );

		$ts = db_dateTime2unix( $row["item_created"] );
		$tc = $ts < 0 ? null : date( "m/d/Y g:i a", $ts );

		$s .= '<tr>';
		$s .= '<td><a href="?m=helpdesk&a=view&item_id='
        . $row['item_id']
        . '">'
        . $row['item_id']
        . '</a> '
        . dPshowImage (dPfindImage( 'ct'.$row["item_calltype"].'.png', $m ), 15, 17, '')
        . '</td>';
    $s .= "<td nowrap=\"nowrap\">";
    if ($email) {
      $s .= "<a href=\"mailto: $email\">$name</a>";
    } else {
      $s .= $name;
    }
		$s .= '</td><td width="80%">' . $row['item_title'] . '</td>';
		$s .= '<td nowrap="nowrap">' . $row['assigned_fullname'] . '</td>';
	  $s .= '<td align="center" nowrap>' . $ipr[@$row["item_priority"]] . '</td>';
    $s .= '<td align="center" style="background-color: #'
        . $row['project_color_identifier']
        . ';" nowrap><a href="./index.php?m=projects&a=view&project_id='
        . $row['project_id'].'">'.$row['project_name'].'</a></td>';
		$s .= '<td nowrap="nowrap">' . ($tc ? $tc : '-') . '</td>';
    $s .= '</tr>';
	}

  if( $s == '' ) {
    $s = "<tr><td colspan=7><p><font color=red>No items were opened today</font><p></td></tr>\n";
  }

	echo $s;
?>
</table>
