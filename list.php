<?php /* HELPDESK $Id: list.php,v 1.63 2004/06/04 15:59:35 uid157919 Exp $ */

$AppUI->savePlace();

$df = $AppUI->getPref( 'SHDATEFORMAT' );
$tf = $AppUI->getPref( 'TIMEFORMAT' );
$format = $df." ".$tf;

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

if (isset($_GET['page'])) {
  $AppUI->setState('HelpDeskListPage', $_GET['page']);
} else {
  // If page isn't mentioned, we need to reset
  $AppUI->setState('HelpDeskListPage', 0);
}

$page = $AppUI->getState('HelpDeskListPage') ? $AppUI->getState('HelpDeskListPage') : 0;

$tarr = array();
$selectors = array();

// check for search text
if($HELPDESK_CONFIG['search_criteria_search']){
	$search = '';

	if(isset($_GET['search'])){
		$search = $_GET['search'];

		if(strlen(trim($search))>0){
			$tarr[] = "(lower(hi.item_title) LIKE lower('%$search%')
			      OR lower(hi.item_summary) LIKE lower('%$search%'))";
		}
	}
	$selectors[] = "<td align=\"right\"><label for=\"search\">"
               . $AppUI->_('Search')
               . ":</label></td><td nowrap=\"nowrap\">"
               . "<input type=\"text\" name=\"search\" id=\"search\" class=\"text\" value=\"$search\" size=\"12\">"
               . " <input type=\"submit\" value=\""
               . $AppUI->_('Search')
               . "\" class=\"button\" /></td>";

}

// check for calltype filter
if($HELPDESK_CONFIG['search_criteria_call_type']){
	if (isset( $_GET['item_calltype'] )) {
		$AppUI->setState( 'HelpDeskCallType', $_GET['item_calltype'] );
	}

	$calltype = $AppUI->getState( 'HelpDeskCallType' ) !== null ? $AppUI->getState( 'HelpDeskCallType' ) : -1;

	if ($calltype >= 0) {
		$tarr[] = "hi.item_calltype=$calltype";
	}

	$selectors[] = "<td align=\"right\" nowrap><label for=\"call_type\">"
               . $AppUI->_('helpdeskCallType')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $ict ),
                              'item_calltype',
						                  'size="1" id="call_type" class="text" onchange="changeList()"',
						                  $calltype, true )
               . "</td>";
}

// check for status filter
if($HELPDESK_CONFIG['search_criteria_status']){
	if (isset( $_GET['item_status'] )) {
		$AppUI->setState( 'HelpDeskStatus', $_GET['item_status'] );
	}

	$status = $AppUI->getState( 'HelpDeskStatus' ) !== null ? $AppUI->getState( 'HelpDeskStatus' ) : -1;

	if ($status >= 0) {
		$tarr[] = "hi.item_status=$status";
	}

	$selectors[] = "<td align=\"right\"><label for=\"status\">"
               . $AppUI->_('helpdeskStatus')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $ist ),
                              'item_status',
						                  'size="1" id="status" class="text" onchange="changeList()"',
						                  $status, true )
               . "</td>";
  }

// check for priority filter
if($HELPDESK_CONFIG['search_criteria_priority']){
	if (isset( $_GET['item_priority'] )) {
		$AppUI->setState( 'HelpDeskPriority', $_GET['item_priority'] );
	}

	$priority = $AppUI->getState( 'HelpDeskPriority' ) !== null ? $AppUI->getState( 'HelpDeskPriority' ) : -1;

	if ($priority >= 0) {
		$tarr[] = "hi.item_priority=$priority";
	}

	$selectors[] = "<td align=\"right\"><label for=\"priority\">"
               . $AppUI->_('helpdeskPriority')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $ipr ),
                              'item_priority',
							                'size="1" id="priority" class="text" onchange="changeList()"',
							                $priority, true )
               . "</td>";
}

// check for severity filter
if($HELPDESK_CONFIG['search_criteria_severity']){
	if (isset( $_GET['item_severity'] )) {
		$AppUI->setState( 'HelpDeskSeverity', $_GET['item_severity'] );
	}

	$item_severity = $AppUI->getState( 'HelpDeskSeverity' ) !== null ? $AppUI->getState( 'HelpDeskSeverity' ) : -1;

	if ($item_severity >= 0) {
		$tarr[] = "hi.item_severity=$item_severity";
	}

	$selectors[] = "<td align=\"right\"><label for=\"severity\">"
               . $AppUI->_('helpdeskSeverity')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $isv ),
                              'item_severity',
						                  'size="1" id="severity" class="text" onchange="changeList()"',
						                   $item_severity, true )
               . "</td>";
}

// check for source filter
if($HELPDESK_CONFIG['search_criteria_call_source']){
	if (isset( $_GET['item_source'] )) {
		$AppUI->setState( 'HelpDeskSource', $_GET['item_source'] );
	}

	$item_source = $AppUI->getState( 'HelpDeskSource' ) !== null ? $AppUI->getState( 'HelpDeskSource' ) : -1;

	if ($item_source >= 0) {
		$tarr[] = "hi.item_source=$item_source";
	}

	$selectors[] = "<td align=\"right\" nowrap><label for=\"call_source\">"
               . $AppUI->_('helpdeskCallSource')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $ics ), 
                              'item_source',
						                  'size="1" id="call_source" class="text" onchange="changeList()"',
						                  $item_source, true )
               . "</td>";
}

// check for os filter
if($HELPDESK_CONFIG['search_criteria_os']){
	if (isset( $_GET['item_os'] )) {
		$AppUI->setState( 'HelpDeskOS', $_GET['item_os'] );
	}

	$item_os = $AppUI->getState( 'HelpDeskOS' ) !== null ? $AppUI->getState( 'HelpDeskOS' ) : -1;

	if (isset($item_os)  && strlen($item_os)>0 && $item_os!='-1') {
		$tarr[] = "hi.item_os='$item_os'";
	}

	$selectors[] = "<td align=\"right\"><label for=\"os\">"
               . $AppUI->_('helpdeskOS')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $ios ),
                              'item_os',
						                  'size="1" id="os" class="text" onchange="changeList()"',
						                  $item_os, true )
               . "</td>";
}

// check for application filter
if($HELPDESK_CONFIG['search_criteria_application']){
	if (isset( $_GET['item_application'] )) {
		$AppUI->setState( 'HelpDeskApplication', $_GET['item_application'] );
	}

	$item_application = $AppUI->getState( 'HelpDeskApplication' ) !== null ? $AppUI->getState( 'HelpDeskApplication' ) : -1;

	if (isset($item_application)  && strlen($item_application)>0 && $item_application!='-1') {
		$tarr[] = "hi.item_application='$item_application'";
	}

	$selectors[] = "<td align=\"right\"><label for=\"application\">"
               . $AppUI->_('helpdeskApplication')
               . "</label>:</td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $iap ),
                              'item_application',
						                  'size="1" id="application" class="text" onchange="changeList()"',
						                  $item_application, true )
               . "</td>";
}

// check for company filter
if($HELPDESK_CONFIG['search_criteria_company']){
	if (isset( $_GET['company'] )) {
		$AppUI->setState( 'HelpDeskCompany', $_GET['company'] );
	}

	$company = $AppUI->getState( 'HelpDeskCompany' ) !== null ? $AppUI->getState( 'HelpDeskCompany' ) : -1;

	if ($company >= 0) {
		$tarr[] = "hi.item_company_id=$company";
	}

	// retrieve company list
	$sql = "SELECT company_id, company_name
		FROM companies
    WHERE ".getCompanyPerms("company_id", NULL, PERM_READ)."
		ORDER BY company_name";
	$company_list = db_loadHashList( $sql );

	$selectors[] = "<td align=\"right\"><label for=\"company\">"
               . $AppUI->_('helpdeskCompany')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $company_list ),
                              'company',
							                'size="1" id="company" class="text" onchange="changeList()"',
							                $company )
               . "</td>";
}

// check for project filter
if($HELPDESK_CONFIG['search_criteria_project']){
	if (isset( $_GET['project'] )) {
		$AppUI->setState( 'HelpDeskProject', $_GET['project'] );
	}

	$project = $AppUI->getState( 'HelpDeskProject' ) !== null ? $AppUI->getState( 'HelpDeskProject' ) : -1;

	if ($project >= 0) {
		$tarr[] = "hi.item_project_id=$project";
	}

	// retrieve project list
	$sql = "SELECT project_id, project_name
		      FROM projects
          WHERE ".getCompanyPerms("project_company", NULL, PERM_READ)
		   . "ORDER BY project_name";
	$project_list = db_loadHashList( $sql );

	$selectors[] = "<td align=\"right\"><label for=\"project\">"
               . $AppUI->_('helpdeskProject')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $project_list ),
                              'project',
							                'size="1" id="project" class="text" onchange="changeList()"',
							                $project )
               . "</td>";
}

// check for assigned_to filter
if($HELPDESK_CONFIG['search_criteria_assigned_to']){
	if (isset( $_GET['assigned_to'] )) {
		$AppUI->setState( 'HelpDeskAssignedTo', $_GET['assigned_to'] );
	}

	$assigned_to = $AppUI->getState( 'HelpDeskAssignedTo' ) !== null ? $AppUI->getState( 'HelpDeskAssignedTo' ) : -1;

	if ($assigned_to >= 0) {
		$tarr[] = "hi.item_assigned_to=$assigned_to";
	}

	// retrieve assigned to user list
	$sql = "SELECT user_id, CONCAT(user_first_name, ' ', user_last_name)
		      FROM users
          WHERE ".getCompanyPerms("user_company", NULL, PERM_READ, $HELPDESK_CONFIG['the_company'])."
		      ORDER BY user_first_name";
	$assigned_to_list = db_loadHashList( $sql );

	$selectors[] = "<td align=\"right\" nowrap><label for=\"assigned_to\">"
               . $AppUI->_('helpdeskAssignedTo')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $assigned_to_list ),
                              'assigned_to',
						                  'size="1" id="assigned_to" class="text" onchange="changeList()"',
						                  $assigned_to )
               . "</td>";
}

// check for requestor filter
if($HELPDESK_CONFIG['search_criteria_requestor']){
	if (isset( $_GET['requestor'] )) {
		$AppUI->setState( 'HelpDeskRequestor', $_GET['requestor'] );
	}

	$requestor = $AppUI->getState( 'HelpDeskRequestor' ) !== null ? $AppUI->getState( 'HelpDeskRequestor' ) : -1;

	if (isset($requestor)  && strlen($requestor)>0 && $requestor!='-1') {
		$tarr[] = "hi.item_requestor='$requestor'";
	}

	// retrieve requestor list
	$sql = "SELECT distinct(item_requestor) as requestor, item_requestor
		      FROM helpdesk_items
		      WHERE ".getCompanyPerms("item_company_id", NULL, PERM_READ)."
		      ORDER BY item_requestor";
	$requestor_list = db_loadHashList( $sql );

	$selectors[] = "<td align=\"right\"><label for=\"requestor\">"
               . $AppUI->_('helpdeskRequestor')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $requestor_list ),
                              'requestor',
						                  'size="1" id="requestor" class="text" onchange="changeList()"',
						                  $requestor )
               . "</td>";
}


$where = getItemPerms();

if (count( $tarr )) {
	$where .=  'AND ('.implode("\n AND ", $tarr).') ';
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
        WHERE $where
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

// Pagination
$items_per_page = $HELPDESK_CONFIG['items_per_page'];

// Figure out number of total results, but do not retrieve
$total_results = db_num_rows(db_exec($sql));

// Figure out the offset
$offset = $page * $items_per_page;

// Limit the results to enable pagination
$sql .= " LIMIT $offset,$items_per_page";

// Get the actual, paginated results
$rows = db_loadList( $sql );

// Setup the title block
$titleBlock = new CTitleBlock( $AppUI->_('helpdeskHelpDesk'), 'helpdesk.png', $m, 'ID_HELP_HELPDESK_IDX' );

if (hditemCreate()) {
  $titleBlock->addCell(
    '<input type="submit" class="button" value="'.$AppUI->_('helpdeskNewItem').'" />', '',
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
	<?php
		$count = 1;
		foreach($selectors as $selector){
			print $selector;
			if($count%3==0){
				print "</tr>\n<tr>";
			}
			$count++;	
		}
	implode("</tr>\n<tr>",$selectors)
	?>
  </tr>
  </form>
</table>
<br>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<td align="right" nowrap>&nbsp;</td>
	<th nowrap="nowrap"><?=sort_header("item_id", $AppUI->_('helpdeskNumber'))?></th>
	<th nowrap="nowrap"><?=sort_header("item_created", $AppUI->_('helpdeskOpenedOn'))?></th>
	<th nowrap="nowrap"><?=sort_header("item_requestor", $AppUI->_('helpdeskRequestor'))?></th>
	<th nowrap="nowrap"><?=sort_header("item_title", $AppUI->_('helpdeskTitle'))?></th>
	<th nowrap="nowrap"><?=sort_header("item_assigned_to", $AppUI->_('helpdeskAssignedTo'))?></th>
	<th nowrap="nowrap"><?=sort_header("item_status", $AppUI->_('helpdeskStatus'))?></th>
	<th nowrap="nowrap"><?=sort_header("item_priority", $AppUI->_('helpdeskPriority'))?></th>
	<th nowrap="nowrap"><?=sort_header("project_name", $AppUI->_('helpdeskProject'))?></th>
</tr>
<?php
$s = '';

foreach ($rows as $row) {
  $canEdit = hditemEditable($row);

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

	

	$date = new CDate( $row['item_created'] );
	$s .= $CR . "<td nowrap>".$date->format( $format )."</td>";
	

	$s .= $CR . "<td nowrap align=\"center\">";
	if ($row["item_requestor_email"]) {
		$s .= $CR . "<a href=\"mailto:".$row["item_requestor_email"]."\">"
              . $row['item_requestor']
              . "</a>";
	} else {
		$s .= $CR . $row['item_requestor'];
	}
	$s .= $CR . "</td>";

	$s .= $CR . '<td width="99%"><a href="?m=helpdesk&a=view&item_id='
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
	$s .= $CR . '<td align="center" nowrap>' . $AppUI->_($ist[@$row["item_status"]]) . '</td>';
	$s .= $CR . '<td align="center" nowrap>' . $AppUI->_($ipr[@$row["item_priority"]]) . '</td>';
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

// Pagination
$pages = 0;
if ($total_results > $items_per_page) {
  $pages_per_side = $HELPDESK_CONFIG['pages_per_side'];
  $pages = ceil($total_results / $items_per_page) - 1; 

  if ($page < $pages_per_side) {
    $start = 0;
  } else {
    $start = $page - $pages_per_side;
  }

  if ($page > ($pages - $pages_per_side)) {
    $end = $pages;
  } else {
    $end = $page + $pages_per_side;
  }

  print "<tr><td colspan=\"9\" align=\"center\">";

  $link = "?m=helpdesk&a=list&page=";

  if ($page > 0) {
    print "<a href=\"{$link}0\">&larr; "
        . $AppUI->_('helpdeskFirst')
        . "</a>&nbsp;&nbsp;";

    print "<a href=\"$link"
        . ($page - 1)
        . "\">&larr; "
        . $AppUI->_('helpdeskPrevious') 
        . "</a>&nbsp;&nbsp;";
  }

  for ($i = $start; $i <= $end; $i++) {
    if ($i == $page) {
      print " <b>".($i + 1)."</b> ";
    } else {
      print " <a href=\"$link$i\">"
          . ($i + 1)
          . "</a> ";
    }
  }

  if ($page < $pages) {
    print "&nbsp;&nbsp;<a href=\"$link"
        . ($page + 1)
        . "\">"
        . $AppUI->_('helpdeskNext')
        . "&rarr;</a>";

    print "&nbsp;&nbsp;<a href=\"$link$pages\">"
        . $AppUI->_('helpdeskLast') 
        . " &rarr;</a>";
  }

  print "</td></tr>";
}
?>
</table>
<?php
  print "<center><small>$total_results "
      . (($total_results == 1) ? $AppUI->_('helpdeskItem') : $AppUI->_('helpdeskItems'))
      . " "
      . $AppUI->_('helpdeskFound');
      
  if ($pages > 0) {
    print ", "
        . ($pages + 1)
        . " "
        . $AppUI->_('helpdeskPages');
  }
      
  print "</small></center>";

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
