<?php /* HELPDESK $Id: index.php,v 1.7 2004/04/21 15:08:54 agorski Exp $ */
$AppUI->savePlace();

if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'HelpDeskIdxTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'HelpDeskIdxTab' ) !== NULL ? $AppUI->getState( 'HelpDeskIdxTab' ) : 0;

// Setup the title block
$titleBlock = new CTitleBlock( 'Help Desk', 'helpdesk.png', $m, 'ID_HELP_HELPDESK_IDX' );

if ($canEdit) {
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('New Item').'" />', '',
		'<form action="?m=helpdesk&a=addedit" method="post">', '</form>'
	);
	$titleBlock->addCrumb( "?m=helpdesk", "Home" );
}

$titleBlock->show();

$sql = "SELECT COUNT(item_id)
        FROM helpdesk_items";

$numtotal = db_loadResult ($sql);

$sql = "SELECT COUNT(item_id)
        FROM helpdesk_items
        WHERE (TO_DAYS(NOW()) - TO_DAYS(item_resolved) = 0)
        AND (item_status = 2)";

$numclosed = db_loadResult ($sql);

$sql = "SELECT COUNT(item_id)
        FROM helpdesk_items
        WHERE (TO_DAYS(NOW()) - TO_DAYS(item_created) = 0)
        AND (item_status = 0 OR item_status = 1)";

$numopened = db_loadResult ($sql);

?>
<table cellspacing="0" cellpadding="2" border="0" width="100%">
<tr>
	<td width="80%" valign="top">
  <?php
  // Tabbed information boxes
  $tabBox = new CTabBox( "?m=helpdesk", "{$AppUI->cfg['root_dir']}/modules/helpdesk/", $tab );
  $tabBox->add( 'vw_idx_stats', "Help Desk Items ($numtotal)" );
  $tabBox->add( 'vw_idx_new', "Opened Today ($numopened)" );
  $tabBox->add( 'vw_idx_closed', "Closed Today ($numclosed)" );
  $tabBox->show();
  ?>
	</td>
</tr>
</table>
