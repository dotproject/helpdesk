<?php /* HELPDESK $Id: index.php 265 2011-04-27 06:06:35Z HaTaX $ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

if (($m) and (getDenyRead($m))) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

// Check to make sure config was done
if (!$isSetup) {
?>
<img src="images/shim.gif" width="1" height="5" alt="" border="0"><br />
<table width="98%" cellspacing="1" cellpadding="0" border="0">
	<tr>
	<td><img src="./modules/helpdesk/images/helpdesk.png" alt="" border="0"></td>
		<td nowrap="nowrap" width="100%"><h1><?php echo $AppUI->_('Help Desk setup not completed');?>!</h1></td>
	</tr>
</table>
<p><table width="95%" border=0 cellpadding="5" cellspacing=0>
<tr valign=top>
	<td width=50%><?php echo $AppUI->_('Please configure the Help Desk module in System Admin');?></td>
	<td width=50%>&nbsp;</td>
</tr></table></p>
<?php
die();
}

$AppUI->savePlace();

require_once ($AppUI->getModuleClass('companies'));

// ANDY - get CCompany() to filter tasks by company
if (isset($_POST['f2'])) {
	$AppUI->setState('CompanyIdxFilter', $_POST['f2']);
}
$company_id = $AppUI->getState('CompanyIdxFilter') ? $AppUI->getState('CompanyIdxFilter') : 'all';

$obj = new CCompany();
$companies = $obj->getAllowedRecords($AppUI->user_id, 'company_id,company_name', 'company_name');
$filters2 = arrayMerge( array('all' => $AppUI->_('All Companies', UI_OUTPUT_RAW)), $companies);
//ANDY ; company filter -- end


if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'HelpDeskIdxTab', $_GET['tab'] );
}

$tab = $AppUI->getState( 'HelpDeskIdxTab' ) !== NULL ? $AppUI->getState( 'HelpDeskIdxTab' ) : 0;

// Setup the title block
$titleBlock = new CTitleBlock( 'Issue Management', 'helpdesk.png', $m, 'ID_HELP_HELPDESK_IDX' );

// ANDY - select company
// collect the full (or filtered) list data via function in helpdesk.class.php
issue_list_data();
$titleBlock->addCell($AppUI->_('Company') . ':');
$titleBlock->addCell(arraySelect($filters2, 'f2', 
                                 'size=1 class=text onChange="document.companyFilter.submit();"', 
                                 $company_id, false), '', 
                     '<form action="?m=helpdesk" method="post" name="companyFilter">', '</form>');



if (hditemCreate()) { //if ($canEdit) {
  $titleBlock->addCell(
    '<input type="submit" class="button" value="'.$AppUI->_('New Item').'" />', '',
    '<form action="?m=helpdesk&a=addedit" method="post">', '</form>'
  );
}

$titleBlock->addCrumb( "?m=helpdesk", 'home' );
$titleBlock->addCrumb( "?m=helpdesk&a=list", 'list' );
$titleBlock->addCrumb( "?m=helpdesk&a=reports", 'reports' );	

$titleBlock->show();

$item_perms = getItemPerms();

//ANDY - add company filter
if ($company_id != 0) 
	$item_perms = $item_perms . ' and item_company_id = ' . $company_id;

$q = new DBQuery; 
$q->addQuery('COUNT(item_id)'); 
$q->addTable('helpdesk_items');
$q->addWhere($item_perms);
$numtotal = $q->loadResult ();
$q->clear();

/*
 * New = 0
 * Assigned = 1
 * Closed = 3
 * On Hold = 4
 * In Progress = 5
 */
//ANDY added item_perms
$q = new DBQuery; 
$q->addQuery('COUNT(DISTINCT(item_id))'); 
$q->addTable('helpdesk_items');
$q->addWhere($item_perms);
$q->addWhere('item_assigned_to='.$AppUI->user_id);
$q->addWhere('item_status != '.$HELPDESK_CONFIG['closed_status_id']);
$q->addWhere('item_status <90');
$nummine = $q->loadResult ();
$q->clear();

$q = new DBQuery; 
$q->addQuery('COUNT(DISTINCT(item_id))'); 
$q->addTable('helpdesk_items');
$q->addJoin('helpdesk_item_status','his','helpdesk_items.item_id = his.status_item_id');
$q->addWhere($item_perms);
$q->addWhere('item_status != '.$HELPDESK_CONFIG['closed_status_id']);
$q->addWhere('status_code = 0');
$q->addWhere('(TO_DAYS(NOW()) - TO_DAYS(status_date) = 0)');
$numopened = $q->loadResult ();
$q->clear();

$q = new DBQuery; 
$q->addQuery('COUNT(DISTINCT(item_id))'); 
$q->addTable('helpdesk_items');
$q->addJoin('helpdesk_item_status','his','helpdesk_items.item_id = his.status_item_id');
$q->addWhere($item_perms);
$q->addWhere('item_status = '.$HELPDESK_CONFIG['closed_status_id']);
$q->addWhere('status_code = 11');
$q->addWhere('(TO_DAYS(NOW()) - TO_DAYS(status_date) = 0)');
$numclosed = $q->loadResult ();
$q->clear();

$q = new DBQuery; 
$q->addQuery('COUNT(DISTINCT(hi.item_id))');
$q->addTable('helpdesk_items','hi');
$q->innerJoin('helpdesk_item_watchers','hiw','hiw.item_id = hi.item_id');
$q->leftJoin('users','u2','u2.user_id = hiw.user_id');
$q->leftJoin('contacts','','u2.user_contact = contacts.contact_id');
$q->leftJoin('projects','p','p.project_id = hi.item_project_id');
$q->addWhere('hiw.user_id = '.$AppUI->user_id);
$q->addWhere('hi.item_status != '.$HELPDESK_CONFIG['closed_status_id']);
$numwatching = $q->loadResult();

?>
<table cellspacing="0" cellpadding="2" border="0" width="100%">
<tr>
	<td width="80%" valign="top">
  <?php
  // Tabbed information boxes
  $tabBox = new CTabBox( "?m=helpdesk", DP_BASE_DIR . '/modules/helpdesk/', $tab );
  $tabBox->add( 'vw_idx_stats', $AppUI->_('Issue Items') . ' (' . $numtotal . ')' , true);
  $tabBox->add( 'vw_idx_my', $AppUI->_('My Open') . ' (' . $nummine . ')' , true);
  $tabBox->add( 'vw_idx_new', $AppUI->_('Opened Today') . ' (' . $numopened . ')' , true);
  $tabBox->add( 'vw_idx_closed', $AppUI->_('Closed Today') . ' (' . $numclosed . ')' , true);
  $tabBox->add( 'vw_idx_watched', $AppUI->_('Watched Open Tickets') . ' (' . $numwatching . ')' , true);
  $tabBox->show();
  ?>
	</td>
</tr>
</table>


