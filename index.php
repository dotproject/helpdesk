<?php /* HELPDESK $Id: index.php,v 1.6 2004/04/19 18:24:12 adam Exp $ */
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

?>
<table cellspacing="0" cellpadding="2" border="0" width="100%">
<tr>
	<td width="80%" valign="top">
  <?php
  // Tabbed information boxes
  $tabBox = new CTabBox( "?m=helpdesk", "{$AppUI->cfg['root_dir']}/modules/helpdesk/", $tab );
  $tabBox->add( 'vw_idx_stats', 'Help Desk Items' );
  $tabBox->add( 'vw_idx_new', 'Opened Today' );
  $tabBox->add( 'vw_idx_closed', 'Closed Today' );
  $tabBox->show();
  ?>
	</td>
</tr>
</table>
