<?php /* HELPDESK $Id: index.php,v 1.1.1.1 2004/01/14 23:05:22 root Exp $ */

// enable debug output
#include( "../../misc/debug.php" );
writeDebug( "DEBUGGING", 'ENABLED', __FILE__, __LINE__ );

$AppUI->savePlace();

if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'HelpDeskIdxTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'HelpDeskIdxTab' ) !== NULL ? $AppUI->getState( 'HelpDeskIdxTab' ) : 0;

// setup the title block
$titleBlock = new CTitleBlock( 'Help Desk', 'helpdesk.png', $m, 'ID_HELP_HELPDESK_IDX' );
if ($canEdit) {
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('new item').'" />', '',
		'<form action="?m=helpdesk&a=addedit" method="post">', '</form>'
	);
	$titleBlock->addCrumb( "?m=helpdesk&a=list", "index" );
}
$titleBlock->show();
?>

<table cellspacing="0" cellpadding="2" border="0" width="100%">
<tr>
	<td width="20%" valign="top">
		<br />
		<table cellspacing="0" cellpadding="2" border="0" width="100%" class="std">
		<tr>
			<th>
<?php
	$df = $AppUI->getPref( 'SHDATEFORMAT' );
	$tf = $AppUI->getPref( 'TIMEFORMAT' );
    writeDebug( $df, 'SHDATEFORMAT', __FILE__, __LINE__ );
    writeDebug( $tf, 'TIMEFORMAT', __FILE__, __LINE__ );
    
    // Right, so this sure isn't how PEAR::Date is defined <mike@linuxbox.nu>
	//$now = new CDate( null, "$df $tf" );
    //writeDebug( $now->toString(), 'NOW', __FILE__, __LINE__ );
	//echo $now->toString();
    $now = date( "m.d.y g:i a" );
    echo $now;
    
?>
			</th>
		</tr>
		<tr>
			<td>
				Some info
			</td>
		</tr>
		</table>
	</td>

	<td width="80%" valign="top">
<?php
// tabbed information boxes
$tabBox = new CTabBox( "?m=helpdesk", "{$AppUI->cfg['root_dir']}/modules/helpdesk/", $tab );
$tabBox->add( 'vw_idx_stats', 'Call Type Statistics' );
$tabBox->add( 'vw_idx_new', 'Opened Today' );
$tabBox->add( 'vw_idx_closed', 'Closed Today' );
$tabBox->show();
?>
	</td>
</tr>
</table>
