<?php /* HELPDESK $Id: list.php,v 1.35 2004/04/23 17:17:43 agorski Exp $ */

// check permissions for this module
$canReadModule = !getDenyRead( $m );
if (!$canReadModule) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

$AppUI->savePlace();

// check sort order
if (isset( $_GET['orderby'] )) {
	$AppUI->setState( 'HelpDeskIdxOrderBy', $_GET['orderby'] );
}
$orderby = $AppUI->getState( 'HelpDeskIdxOrderBy' ) ? $AppUI->getState( 'HelpDeskIdxOrderBy' ) : 'item_id';

// check sort order way (asc/desc)
if (isset($_GET['orderdesc'])) {
  $AppUI->setState('HelpDeskIdxOrderDesc', $_GET['orderdesc']);
}

$orderdesc = $AppUI->getState('HelpDeskIdxOrderDesc') ? $AppUI->getState('HelpDeskIdxOrderDesc') : 0;

// check for search text
if (isset( $_GET['search'] )) {
  $AppUI->setState('HelpDeskSearch', $_GET['search']);
}
  
$search = $AppUI->getState('HelpDeskSearch');

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

// check for priority filter
if (isset( $_GET['item_priority'] )) {
	$AppUI->setState( 'HelpDeskPriority', $_GET['item_priority'] );
}
$priority = $AppUI->getState( 'HelpDeskPriority' ) !== null ? $AppUI->getState( 'HelpDeskPriority' ) : -1;

$tarr = array();

if (isset($search)) {
	$tarr[] = "(lower(hi.item_title) LIKE lower('%$search%')
              OR lower(hi.item_summary) LIKE lower('%$search%'))";
}

if ($calltype >= 0) {
	$tarr[] = "hi.item_calltype=$calltype";
}

if ($status >= 0) {
	$tarr[] = "hi.item_status=$status";
}

if ($priority >= 0) {
	$tarr[] = "hi.item_priority=$priority";
}

$where = '';
$join = winnow( 'projects', 'project_id', $where );

if (count( $tarr )) {
	$where = 'WHERE ' . implode(' AND ', $tarr);
}

$sql = "SELECT hi.*,
       CONCAT(u2.user_first_name,' ',u2.user_last_name) assigned_fullname,
       u2.user_email as assigned_email,
        p.project_id,
        p.project_name,
        p.project_color_identifier
        FROM helpdesk_items hi
        LEFT JOIN users u2 ON u2.user_id = hi.item_assigned_to
        LEFT JOIN projects p ON p.project_id = hi.item_project_id
        $where
        ORDER BY ";

// Do custom order by if needed, default at the end
if ($orderby == "project_name") {
  $sql .= "p.project_name";
} else if ($orderby == "item_assigned_to") {
  $sql .= "assigned_fullname";
} else {
  $sql .= "hi.$orderby";
}

// Ascending or Descending
if ($orderdesc) {
  $sql .= " DESC";
}

$rows = db_loadList( $sql );

// Setup the title block
$titleBlock = new CTitleBlock( 'Help Desk', 'helpdesk.png', $m, 'ID_HELP_HELPDESK_IDX' );

if ($canEdit) {
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('New Item').'" />', '',
		'<form action="?m=helpdesk&a=addedit" method="post">', '</form>'
	);
}

$titleBlock->addCrumb( "?m=helpdesk", "Home" );
$titleBlock->addCrumb( "?m=helpdesk&a=list", "List" );
$titleBlock->show();

?>
<script language="javascript">
function changeList() {
	document.filterFrm.submit();
}
</script>

<table border="0" cellpadding="2" cellspacing="1" class="std" width="100%">
  <form name="filterFrm" action="?index.php" method="get">
  <input type="hidden" name="m" value="<?=$m?>" />
  <input type="hidden" name="a" value="<?=$a?>" />
  <tr>
    <td><?=$AppUI->_('Search')?>:</td>
    <td><input type="text" name="search" class="text" value="<?=$search;?>"></td>
    <td align="right" nowrap><?=$AppUI->_('Call Type')?>:</td>
    <td><?=arraySelect( arrayMerge( array( '-1'=>'All' ), $ict ), 'item_calltype',
                                           'size="1" class="text" onchange="changeList()"',
                                           $calltype )?></td>
    <td align="right"><?=$AppUI->_('Status')?>:</td>
    <td><?=arraySelect( arrayMerge( array( '-1'=>'All' ), $ist ), 'item_status',
                                           'size="1" class="text" onchange="changeList()"',
                                           $status )?></td>
    <td align="right"><?=$AppUI->_('Priority')?>:</td>
    <td><?=arraySelect( arrayMerge( array( '-1'=>'All' ), $ipr ), 'item_priority',
                                           'size="1" class="text" onchange="changeList()"',
                                           $priority )?></td>
    <td align="right"><input type="submit" value="<?=$AppUI->_('Search')?>" class="button" /></td>
  </tr>
  </form>
</table>
<br>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<td align="right" nowrap>&nbsp;</td>
	<th nowrap="nowrap"><?=sort_header("item_id", $AppUI->_('Number'))?></th>
	<th nowrap="nowrap"><?=sort_header("item_requestor", $AppUI->_('Requestor'))?></th>
	<th nowrap="nowrap"><?=sort_header("item_title", $AppUI->_('Title'))?></th>
	<th nowrap="nowrap"><?=sort_header("item_assigned_to", $AppUI->_('Assigned To'))?></th>
	<th nowrap="nowrap"><?=sort_header("item_status", $AppUI->_('Status'))?></th>
	<th nowrap="nowrap"><?=sort_header("item_priority", $AppUI->_('Priority'))?></th>
	<th nowrap="nowrap"><?=sort_header("project_name", $AppUI->_('Project'))?></th>
</tr>
<?php
$s = '';

foreach ($rows as $row) {
  /* We need to check if the user who requested the item is still in the
     system. Just because we have a requestor id does not mean we'll be
     able to retrieve a full name */

	$s .= $CR . '<form method="post">';
	$s .= $CR . '<tr>';
	$s .= $CR . '<td align="right" nowrap>';

	if ($canEdit) {
		$s .= $CR . '<a href="?m=helpdesk&a=addedit&item_id='
              . $row["item_id"]
              . '">'
              . dPshowImage("./images/icons/pencil.gif", 12, 12, "edit")
              . '</a>&nbsp;';
	}

	$s .= $CR . '</td>';
	$s .= $CR . '<td><a href="./index.php?m=helpdesk&a=view&item_id='
            . $row["item_id"]
            . '">'
		        . '<strong>'
            . $row["item_id"]
            . '</strong></a> '
            . dPshowImage (dPfindImage( 'ct'.$row["item_calltype"].'.png', $m ), 15, 17, '')
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
	$s .= $CR . "<td nowrap align=\"center\">";
	if ($row["assigned_email"]) {
		$s .= $CR . "<a href=\"mailto:".$row["assigned_email"]."\">"
              . $row['assigned_fullname']
              . "</a>";
	} else {
		$s .= $CR . $row['assigned_fullname'];
	}
	$s .= $CR . "</td>";
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
