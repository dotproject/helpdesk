<?php /* HELPDESK $Id: list.php 334 2007-02-16 19:55:20Z kang $ */
if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

include_once( dPgetConfig('root_dir') . '/modules/helpdesk/helpdesk.functions.php' );
include_once("./modules/helpdesk/config.php");
$allowedCompanies = getAllowedCompanies();
$ipr = dPgetSysVal( 'HelpDeskPriority' );
$ist = dPgetSysVal( 'HelpDeskStatus' );

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
  //$search = '';

	if(isset($_GET['search'])){
    // Set the search text as system state--Kang
    $AppUI->setState( 'HelpDeskSearch', $_GET['search'] );
    //echo $AppUI->getState( 'HelpDeskSearch' ); 
    /*
    $search =$AppUI->getState( 'HelpDeskSearch' ) !== null ? $AppUI->getState( 'HelpDeskSearch' ) : '';
    echo "<br>".$search."<br>";
    */
		/*if(strlen(trim($search))>0){
			$tarr[] = "(lower(hi.item_title) LIKE lower('%$search%')
			      OR lower(hi.item_summary) LIKE lower('%$search%'))";
		}*/
	}
  
  $search =$AppUI->getState( 'HelpDeskSearch' ) !== null ? $AppUI->getState( 'HelpDeskSearch' ) : '';
  //echo "<br>".$search."<br>";
  if(strlen(trim($search))>0){
      $tarr[] = "(lower(hi.item_title) LIKE lower('%$search%')
                    OR lower(hi.item_summary) LIKE lower('%$search%'))";
  }
  
	if (!$_REQUEST['project_id'] && !$_REQUEST['company_id']) {
		$selectors[] = "<td align=\"right\"><label for=\"search\">"
               . $AppUI->_('Search')
               . ":</label></td><td nowrap=\"nowrap\">"
               . "<input type=\"text\" name=\"search\" id=\"search\" class=\"text\" value=\"".$search."\" size=\"20\">"
               . " <input type=\"submit\" value=\""
               . $AppUI->_('Search')
               . "\" class=\"button\" /></td>";
	}
}

// check for company filter
if($HELPDESK_CONFIG['search_criteria_company']){
	if (isset( $_GET['company'] )) {
		$AppUI->setState( 'HelpDeskCompany', $_GET['company'] );
	}
	
	if (empty($_REQUEST['company_id'])) {
		$company = $AppUI->getState( 'HelpDeskCompany' ) !== null ? $AppUI->getState( 'HelpDeskCompany' ) : -1;
	} else {
		$company = $_REQUEST['company_id'];
	}

	if ( ($_GET['project'] > 0) && ($company == -1) ) {
		$q = new DBQuery; 
		$q->addQuery('project_company');
		$q->addTable('projects');
		$q->addWhere('project_id='.$_GET['project']);
		$company = $q->loadResult();
	}

	if ($company >= 0) {
		$tarr[] = "hi.item_company_id=$company";
	}
	
	if (!$_REQUEST['project_id'] && !$_REQUEST['company_id']) {
		$selectors[] = "<td align=\"right\"><label for=\"company\">"
               . $AppUI->_('Company')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $allowedCompanies ),
                              'company',
							                'size="1" id="company" class="text" onchange="changeList()"',
							                $company )
               . "</td>";
	}
}

// check for project filter
if($HELPDESK_CONFIG['search_criteria_project']){
	if (isset( $_GET['project'] )) {
		$AppUI->setState( 'HelpDeskProject', $_GET['project'] );
	}

	if (empty($_REQUEST['project_id'])) {
		$project = $AppUI->getState( 'HelpDeskProject' ) !== null ? $AppUI->getState( 'HelpDeskProject' ) : -1;
	} else {
		$project = $_REQUEST['project_id'];
	}

	if ($project >= 0) {
		$tarr[] = "hi.item_project_id=$project";
	}

	// retrieve project list
	if ($company > 0) {
		if($HELPDESK_CONFIG['use_project_perms']){
			require_once( $AppUI->getModuleClass('projects'));
			$project_make_list = new CProject;
			$active_arr = array('where' => 'project_status!='.$HELPDESK_CONFIG['archived_project_status_id'],
				'where' => 'project_company='.$company);
			$project_list = $project_make_list->getAllowedRecords($AppUI->user_id, 'project_id, project_name','project_name',$project_arr,$active_arr);
		} else {
			$q = new DBQuery; 
			$q->addQuery('project_id, project_name'); 
			$q->addTable('projects');
			$q->addOrder('project_name');
			$q->addWhere('project_company in (' . implode(',',array_keys(arrayMerge( array( 0 => '' ), getAllowedCompanies($company)))) .')');
			$q->addWhere('project_status!='.$HELPDESK_CONFIG['archived_project_status_id']);
			$project_list = $q->loadHashList();
			$q->close();
		}
	} else {
		$project_list = getAllowedProjects(1,1);
	}

	if (!$_REQUEST['project_id']) {
		$selectors[] = "<td align=\"right\"><label for=\"project\">"
               . $AppUI->_('Project')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>'('.$AppUI->_('All').')', '0'=>'('.$AppUI->_('Without Project').')' ), $project_list ),
                              'project',
							                'size="1" id="project" class="text" onchange="changeList()"',
							                $project )
               . "</td>";
	}
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
     $q = new DBQuery; 
     $q->addQuery('user_id, CONCAT(contact_first_name, \' \', contact_last_name)');
     $q->addTable('users');
     $q->addJoin('contacts','','contact_id = user_contact','INNER');
     $q->addJoin('helpdesk_items','','item_assigned_to = user_id','INNER');
     $q->addWhere(getCompanyPerms('contact_company', NULL, PERM_READ, $HELPDESK_CONFIG['the_company']));
     $q->addOrder('contact_first_name');
     $assigned_to_list = $q->loadHashList();

     if (!$_REQUEST['project_id']) {

        $selectors[] = "<td align=\"right\" nowrap><label for=\"assigned_to\">"
               . $AppUI->_('Assigned To')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $assigned_to_list ),
                              'assigned_to',
						                  'size="1" id="assigned_to" class="text" onchange="changeList()"',
						                  $assigned_to )
               . "</td>";
	}
}

// check for status filter
if($HELPDESK_CONFIG['search_criteria_status']){
	if (isset( $_GET['item_status'] )) {
		$AppUI->setState( 'HelpDeskStatus', $_GET['item_status'] );
	}

	$status = $AppUI->getState( 'HelpDeskStatus' ) !== null ? $AppUI->getState( 'HelpDeskStatus' ) : -1;

	if ($status >= 0) {
		$tarr[] = "hi.item_status=$status";
	} elseif ($status == -2) {
		$tarr[] = "hi.item_status<>".$HELPDESK_CONFIG['closed_status_id'];
	}

	if (!$_REQUEST['project_id'] && !$_REQUEST['company_id']) {
		$selectors[] = "<td align=\"right\"><label for=\"status\">"
               . $AppUI->_('Status')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>'All', '-2'=>'All (not closed)'), $ist ),
                              'item_status',
						                  'size="1" id="status" class="text" onchange="changeList()"',
						                  $status, true )
               . "</td>";
  }
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
	
	if (!$_REQUEST['project_id'] && !$_REQUEST['company_id']) {
		$selectors[] = "<td align=\"right\" nowrap><label for=\"call_type\">"
               . $AppUI->_('Issue Type')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>'All' ), $ict ),
                              'item_calltype',
						                  'size="1" id="call_type" class="text" onchange="changeList()"',
						                  $calltype, true )
               . "</td>";
	}
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

	if (!$_REQUEST['project_id'] && !$_REQUEST['company_id']) {
		$selectors[] = "<td align=\"right\" nowrap><label for=\"call_source\">"
               . $AppUI->_('Source')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $ics ), 
                              'item_source',
						                  'size="1" id="call_source" class="text" onchange="changeList()"',
						                  $item_source, true)
               . "</td>";
	}
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

	if (!$_REQUEST['project_id'] && !$_REQUEST['company_id']) {
		$selectors[] = "<td align=\"right\"><label for=\"priority\">"
               . $AppUI->_('Priority')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>'All' ), $ipr ),
                              'item_priority',
							                'size="1" id="priority" class="text" onchange="changeList()"',
							                $priority, true )
               . "</td>";
	}
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

	if (!$_REQUEST['project_id'] && !$_REQUEST['company_id']) {
		$selectors[] = "<td align=\"right\"><label for=\"severity\">"
               . $AppUI->_('Severity')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>'All' ), $isv ),
                              'item_severity',
						                  'size="1" id="severity" class="text" onchange="changeList()"',
						                   $item_severity, true )
               . "</td>";
	}
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

	if (!$_REQUEST['project_id'] && !$_REQUEST['company_id']) {
		$selectors[] = "<td align=\"right\"><label for=\"application\">"
               . $AppUI->_('Application')
               . "</label>:</td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $iap ),
                              'item_application',
						                  'size="1" id="application" class="text" onchange="changeList()"',
						                  $item_application, true)
               . "</td>";
	}
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
  $q = new DBQuery; 
  $q->addQuery('distinct(item_requestor) as requestor, item_requestor');
  $q->addTable('helpdesk_items');
  $q->addWhere(getCompanyPerms('item_company_id', NULL, PERM_READ));
  $q->addOrder('item_requestor');
  $requestor_list = $q->loadHashList();


	if (!$_REQUEST['project_id']) {
		$selectors[] = "<td align=\"right\"><label for=\"requestor\">"
               . $AppUI->_('Requestor')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $requestor_list ),
                              'requestor',
						                  'size="1" id="requestor" class="text" onchange="changeList()"',
						                  $requestor )
               . "</td>";
	}
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

	if (!$_REQUEST['project_id'] && !$_REQUEST['company_id']) {
		$selectors[] = "<td align=\"right\"><label for=\"os\">"
               . $AppUI->_('Operating System')
               . ":</label></td><td>"
               . arraySelect( arrayMerge( array( '-1'=>$AppUI->_('All') ), $ios ),
                              'item_os',
						                  'size="1" id="os" class="text" onchange="changeList()"',
						                  $item_os, true )
               . "</td>";
	}
}

$where = getItemPerms();

if (count( $tarr )) {
	$where .=  'AND ('.implode("\n AND ", $tarr).') ';
}

$q = new DBQuery; 
$q->addQuery('hi.*,CONCAT(co.contact_first_name,\' \',co.contact_last_name) assigned_fullname,
             co.contact_email as assigned_email,p.project_id,p.project_name,p.project_color_identifier');
$q->addTable('helpdesk_items','hi');
$q->addJoin('users','u2','u2.user_id = hi.item_assigned_to');
$q->addJoin('contacts','co','u2.user_contact = co.contact_id');
$q->addJoin('projects','p','p.project_id = hi.item_project_id');
$q->addWhere($where);
// Do custom order by if needed, default at the end
if ($orderby == 'project_name') {
  $order = 'p.project_name';
} elseif ($orderby == 'item_assigned_to') {
  $order = 'assigned_fullname';
} elseif ($orderby == 'item_updated') {
  $order = 'hi.item_updated';
} else {
  $order = 'hi.' . $orderby;
}
if ($orderdesc) {
  $order .= ' DESC';
}
// Pagination
$items_per_page = $HELPDESK_CONFIG['items_per_page'];
// Figure out number of total results, but do not retrieve
$total_results = db_num_rows($q->exec());

// Figure out the offset
$offset = $page * $items_per_page;
$q->addOrder($order);
$q->setLimit($items_per_page,$offset);
// Get the actual, paginated results
$rows = $q->loadList();

// Setup the title block
if (!$_REQUEST['project_id'] && !$_REQUEST['company_id']) {
$titleBlock = new CTitleBlock( 'Issue Management', 'helpdesk.png', $m, 'ID_HELP_HELPDESK_IDX' );

if (hditemCreate()) {
	$titleBlock->addCell(
	'<input type="submit" onclick="newItemFromList()" class="button" value="'.$AppUI->_('New Item').'" />', ''
  );
}

$titleBlock->addCrumb( "?m=helpdesk", 'home' );
$titleBlock->addCrumb( "?m=helpdesk&a=list", "list" );
$titleBlock->addCrumb( "?m=helpdesk&a=reports", "reports" );
$titleBlock->show();
}
?>

<script language="javascript">
function changeList() {
	document.filterFrm.submit();
}
function newItemFromList() {
	document.filterFrm.a.value='addedit';
	document.filterFrm.submit();
}
</script>
<?php
  if (!$_REQUEST['project_id'] && !$_REQUEST['company_id']) {
?>
<table border="0" cellpadding="2" cellspacing="1" class="std" width="100%">
  <form name="filterFrm" action="?index.php" method="get">
  <input type="hidden" name="m" value="<?php echo $m?>" />
  <input type="hidden" name="a" value="<?php echo $a?>" />
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
<?php
  }
?>
<br />
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<td align="right" nowrap="nowrap">&nbsp;</td>
	<th nowrap="nowrap"><?php echo sort_header("item_id", $AppUI->_('Number')); ?></th>
	<th nowrap="nowrap"><?php echo sort_header("item_created", $AppUI->_('Opened On')); ?></th>
	<th nowrap="nowrap"><?php echo sort_header("item_requestor", $AppUI->_('Requestor')); ?></th>
	<th nowrap="nowrap"><?php echo sort_header("item_title", $AppUI->_('Title')); ?></th>
	<th nowrap="nowrap"><?php echo sort_header("item_summary", $AppUI->_('Summary')); ?></th>
	<th nowrap="nowrap"><?php echo sort_header("item_assigned_to", $AppUI->_('Assigned To')); ?></th>
	<th nowrap="nowrap"><?php echo sort_header("item_status", $AppUI->_('Status')); ?></th>
	<th nowrap="nowrap"><?php echo sort_header("item_priority", $AppUI->_('Priority')); ?></th>
	<th nowrap="nowrap"><?php echo sort_header("item_updated", $AppUI->_('Updated')); ?></th>
	<th nowrap="nowrap"><?php echo sort_header("item_deadline", $AppUI->_('Deadline')); ?></th>
	<th nowrap="nowrap"><?php echo sort_header("project_name", $AppUI->_('Project')); ?></th>
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
              . $row['item_id']
              . '">'
              . dPshowImage("./images/icons/pencil.gif", 12, 12, "edit")
              . '</a>&nbsp;';
	}

	$s .= $CR . '</td>';
	$s .= $CR . '<td nowrap="nowrap"><a href="./index.php?m=helpdesk&a=view&item_id='
            . $row['item_id']
            . '">'
		        . '<strong>'
            . $row['item_id']
            . '</strong></a> '
            . dPshowImage (dPfindImage( 'ct'.$row["item_calltype"].'.png', $m ), 15, 17, '')
            . '</td>';

	
	// KZHAO: Display the creation date
	$date = new CDate( $row['item_created'] );
	//$s .= $CR . "<td nowrap>".$date->format( $format )."</td>";
	//Check whether the creation date is available
	if($row['item_created']==NULL){
		$s .= $CR . '<td nowrap><a title="Unknown">N/A</a></td>';
	}
	else{
		$s .= $CR . '<td nowrap><a title="'.$date->format( $format ).'">'.get_time_ago($row['item_created']).'</a></td>';
	}
	
	$s .= $CR . '<td nowrap align="center">';
	if ($row['item_requestor_email']) {
		$s .= $CR . "<a href=\"mailto:".$row['item_requestor_email']."\">"
              . $row['item_requestor']
              . "</a>";
	} else {
		$s .= $CR . $row['item_requestor'];
	}
	$s .= $CR . "</td>";

	$s .= $CR . '<td width="20%" align="center"><a href="?m=helpdesk&a=view&item_id='
            . $row['item_id']
            . '">'
            . $row['item_title']
            . '</a></td>';
	$s .= $CR . '<td width="50%">' 
            . substr($row['item_summary'],0,max(strpos($row['item_summary']."\n","\n"),70))
            . ' </td>';
	$s .= $CR . '<td nowrap align="center">';
	if ($row['assigned_email']) {
		$s .= $CR . '<a href="mailto:'.$row['assigned_email'].'">'
              . $row['assigned_fullname']
              . "</a>";
	} else {
		$s .= $CR . $row['assigned_fullname'];
	}
	$s .= $CR . "</td>";
	$s .= $CR . '<td align="center" nowrap>' . $AppUI->_($ist[@$row['item_status']]) . '</td>';
	$s .= $CR . '<td align="center" nowrap>' . $AppUI->_($ipr[@$row['item_priority']]) . '</td>';
	//Lets retrieve the updated date
//	$sql = "SELECT MAX(status_date) status_date FROM helpdesk_item_status WHERE status_item_id =".$row['item_id'];
//	$sdrow = db_loadList( $sql );
//	$dateu = new CDate( $sdrow[0]['status_date'] );	
	
	// Display the date of updating
	$dateu = new CDate( $row['item_updated'] );	
	//Check which date is available
	if($row['item_updated']!=NULL){
		$s .= $CR . '<td align="center" nowrap><a title="'.@$dateu->format($format).'">'.get_time_ago($row['item_updated']).'</a></td>';
	}
	elseif($row['item_modified']!=NULL){
		$dateu = new CDate( $row['item_modified'] );
		$s .= $CR . '<td align="center" nowrap><a title="'.@$dateu->format($format).'">'.get_time_ago($row['item_modified']).'</a></td>';		
	}
	else{
		$s .= $CR . '<td align="center" nowrap><a title="Unknown">N/A</a></td>';
		//$s .= $CR . '<td align="center" nowrap>' . get_time_ago($row['item_updated']) . '</td>';
	}

	// KZHAO  8-10-2006
	// Display deadline
	if($ist[@$row['item_status']]=='Close'){
	
	}
	elseif(($row['item_deadline']!=NULL)&&($row['item_deadline']!="0000-00-00 00:00:00")){
		$dl=new CDate ($row['item_deadline']);
		$s .= $CR . '<td align="center" nowrap><a title="'.@$dl->format($format).'">'.get_due_time($row['item_deadline'],1).'</a></td>';
	}
	else{
		$s .= $CR . '<td align="center" nowrap><a title="Unknown">N/A</a></td>';
	}

	
	if($row['project_id']){
		$s .= $CR . '<td width="30%" align="center" style="background-color: #'
		    . $row['project_color_identifier']
		    . ';"><a href="./index.php?m=projects&a=view&project_id='
        . $row['project_id'].'" style="color: '
        . bestColor( @$row['project_color_identifier'] )
        . ';">'
        . $row['project_name']
        .'</a></td>';
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
        . $AppUI->_('First')
        . "</a>&nbsp;&nbsp;";

    print "<a href=\"$link"
        . ($page - 1)
        . "\">&larr; "
        . $AppUI->_('Previous') 
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
        . $AppUI->_('Next')
        . "&rarr;</a>";

    print "&nbsp;&nbsp;<a href=\"$link$pages\">"
        . $AppUI->_('Last') 
        . " &rarr;</a>";
  }

  print "</td></tr>";
}
?>
</table>
<?php
  print "<center><small>$total_results "
      . (($total_results == 1) ? $AppUI->_('Item') : $AppUI->_('Items'))
      . " "
      . $AppUI->_('found');
      
  if ($pages > 0) {
    print ", "
        . ($pages + 1)
        . " "
        . $AppUI->_('Pages');
  }
      
  print "</small></center>";

// Returns a header link used to sort results
// TODO Probably need a better up/down arrow
function sort_header($field, $name) {
  global $orderby, $orderdesc;

  $arrow = "";

  if (!$_REQUEST['project_id'] && !$_REQUEST['company_id']) {
  	$link = "<a class=\"hdr\" href=\"?m=helpdesk&a=list&orderby=$field&orderdesc=";
  } else {
  	if (!$_REQUEST['project_id']) {
  		$link = "<a class=\"hdr\" href=\"?m=companies&a=view&company_id={$_REQUEST['company_id']}&orderby=$field&orderdesc=";
  	} else {
  		$link = "<a class=\"hdr\" href=\"?m=projects&a=view&project_id={$_REQUEST['project_id']}&orderby=$field&orderdesc=";
  	}
  }

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
