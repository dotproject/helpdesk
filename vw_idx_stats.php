<?php /* HELPDESK $Id */
global $m, $ict, $ist;

$stats = array();
foreach ($ict as $k => $v) {
	$sql = "SELECT item_status, count(item_id) FROM helpdesk_items WHERE item_calltype=$k GROUP BY item_status";
//echo "<br>$sql";
	$stats[$k] = db_loadHashList( $sql );
}
//echo '<pre>';print_r($stats);echo '</pre>';

?>
<table cellspacing="1" cellpadding="2" border="0" width="100%" class="tbl">
<tr>
	<th colspan="2"><?php echo $AppUI->_('Status');?>:</th>
<?php
	$s = '';
	foreach ($ist as $k => $v) {
		$s .= '<th width="20%"><a href="?m=helpdesk&a=list&item_status=' . $k . '" class="hdr">' . $v . '</a></th>';
	}
	echo $s;

	$s = '';
	foreach ($ict as $kct => $vct) {
		$s .= '<tr>';
		$s .= '<td width="15"><img src="'.dPfindImage( 'ct'.$kct.'.png', $m ).'" width="15" height="17" border=0 alt="' . $ict[$kct] . '" /></td>';
		$s .= '<td><a href="?m=helpdesk&a=list&item_calltype=' . $kct . '">' . $vct . '</a></td>';
		foreach ($ist as $kst => $vst) {
			$s .= '<td align="center"><a href="?m=helpdesk&a=list&item_calltype=' . $kct . '&item_status=' .$kst. '">' . @$stats[$kct][$kst] . '</a></td>';
		}
		$s .= '</tr>';
	}
	echo $s;
?>
</table>
