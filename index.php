<?php /* HELPDESK $Id: index.php,v 1.29 2005/09/13 17:07:21 theideaman Exp $ */
// check permissions for this module
$canReadModule = !getDenyRead( $m );
if (!$canReadModule) {
	$AppUI->redirect( "m=public&a=access_denied" );
}
$AppUI->savePlace();

if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'HelpDeskIdxTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'HelpDeskIdxTab' ) !== NULL ? $AppUI->getState( 'HelpDeskIdxTab' ) : 0;

// Setup the title block
$titleBlock = new CTitleBlock( 'Help Desk', 'helpdesk.png', $m, 'ID_HELP_HELPDESK_IDX' );

if ($canEdit) {
  $titleBlock->addCell(
    '<input type="submit" class="button" value="'.$AppUI->_('new item').'" />', '',
    '<form action="?m=helpdesk&a=addedit" method="post">', '</form>'
  );
}

$titleBlock->addCrumb( "?m=helpdesk", 'home' );
$titleBlock->addCrumb( "?m=helpdesk&a=list", 'list' );
$titleBlock->addCrumb( "?m=helpdesk&a=reports", 'reports' );

$titleBlock->show();

$item_perms = getItemPerms();

$sql = "SELECT COUNT(item_id)
        FROM helpdesk_items
        WHERE $item_perms";

$numtotal = db_loadResult ($sql);

/*
 * Unassigned = 0
 * Open = 1
 * Closed = 2
 * On hold = 3
 * Delete = 4
 * Testing = 5
 */

$sql = "SELECT COUNT(DISTINCT(item_id))
        FROM 
        	helpdesk_items
        WHERE 
        	item_assigned_to=".$AppUI->user_id."
        	AND (item_status != 2)";

$nummine = db_loadResult ($sql);

$sql = "SELECT COUNT(DISTINCT(item_id))
        FROM 
        	helpdesk_items
        	LEFT JOIN helpdesk_item_status on helpdesk_items.item_id = helpdesk_item_status.status_item_id
        WHERE 
        	status_code = 0
        	AND (TO_DAYS(NOW()) - TO_DAYS(status_date) = 0)
        	AND $item_perms";

$numopened = db_loadResult ($sql);

$sql = "SELECT COUNT(DISTINCT(item_id))
        FROM 
        	helpdesk_items
        	LEFT JOIN helpdesk_item_status on helpdesk_items.item_id = helpdesk_item_status.status_item_id
        WHERE 
        	item_status=2
        	AND status_code=11
        	AND (TO_DAYS(NOW()) - TO_DAYS(status_date) = 0)
          AND $item_perms";
$numclosed = db_loadResult ($sql);

?>
<table cellspacing="0" cellpadding="2" border="0" width="100%">
<tr>
	<td width="80%" valign="top">
  <?php
  // Tabbed information boxes
  $tabBox = new CTabBox( "?m=helpdesk", "{$dPconfig['root_dir']}/modules/helpdesk/", $tab );
  $tabBox->add( 'vw_idx_stats', $AppUI->_('Help Desk Items')." ($numtotal)" );
  $tabBox->add( 'vw_idx_my', $AppUI->_('My Open')." ($nummine)" );
  $tabBox->add( 'vw_idx_new', $AppUI->_('Opened Today')." ($numopened)" );
  $tabBox->add( 'vw_idx_closed', $AppUI->_('Closed Today')." ($numclosed)" );
  $tabBox->add( 'vw_idx_watched', "Watched Tickets" );
  $tabBox->show();
  ?>
	</td>
</tr>
</table>