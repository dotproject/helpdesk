<?php /* HELPDESK $Id: vw_idx_closed.php,v 1.6 2004/04/20 23:49:05 bloaterpaste Exp $*/
global $m, $ipr;

$df = $AppUI->getPref( 'SHDATEFORMAT' );
$tf = $AppUI->getPref( 'TIMEFORMAT' );
$format = $df." ".$tf;

/*  select items created today with 'closed' status
 *  unassigned = 0, open = 1, closed = 2, on hold = 3
 */
$sql = "SELECT item_id, item_title, item_created, item_priority, item_resolved, 
	CONCAT(user_first_name,' ',user_last_name) as assigned_fullname, user_email as assigned_email,
	project_id, project_name
        FROM helpdesk_items
        LEFT JOIN users ON user_id = item_assigned_to
        LEFT JOIN projects ON project_id = item_project_id
        WHERE (TO_DAYS(NOW()) - TO_DAYS(item_created) = 0)
        AND (item_status = 2)
        ORDER BY item_id DESC";
//echo "<pre>".$sql."</pre>";
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
	<th nowrap="nowrap"><?=$AppUI->_('Closed On')?></th>
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


		if($row["item_resolved"]){
			$resolved = new CDate( $row["item_resolved"] );
			$tc = $resolved->format( $format );
		}

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
$s .= '<td nowrap="nowrap">' .($row['assigned_email']?"<a href='mailto: ".$row['assigned_email']."'>".$row['assigned_fullname']."</a>":$row['assigned_fullname']) . '</td>';
$s .= '<td align="center" nowrap>' . $ipr[@$row["item_priority"]] . '</td>';
$s .= '<td align="center" style="background-color: #'
        . $row['project_color_identifier']
        . ';" nowrap><a href="./index.php?m=projects&a=view&project_id='
        . $row['project_id'].'">'.$row['project_name'].'</a></td>';
		$s .= '<td nowrap="nowrap">' . ($tc ? $tc : '-') . '</td>';
    $s .= '</tr>';
	echo $s;
	}
?>
</table>
