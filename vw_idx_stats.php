<?php /* HELPDESK $Id: vw_idx_stats.php,v 1.7 2004/05/25 18:45:57 agorski Exp $*/
global $m, $ict, $ist;

$permarr = array();
//pull in permitted companies
$permarr[] = getPermsWhereClause("item_company_id", "item_created_by", PERM_READ);
//it's assigned to the current user
$permarr[] = "item_assigned_to=".$AppUI->user_id;
//it's requested by a user and that user is you
$permarr[] = "( item_requestor_type=1 AND item_requestor_id=".$AppUI->user_id.' ) ' ;
$perm_sql  = ' ('.implode("\n OR ", $permarr).') ';

$stats = array();

foreach ($ict as $k => $v) {
	$sql = "SELECT item_status, count(item_id)
          FROM helpdesk_items
          WHERE item_calltype=$k
          AND $perm_sql
          GROUP BY item_status";
	$stats[$k] = db_loadHashList( $sql );
}

?>
<table cellspacing="1" cellpadding="2" border="0" width="100%" class="tbl">
<tr>
	<th colspan="2"><?=$AppUI->_('Type')?></th>
<?php
	$s = '';
	foreach ($ist as $k => $v) {
		$s .= "<th width=\"20%\"><a href=\"?m=helpdesk&a=list&item_status=$k\" class=\"hdr\">$v</a></th>";
	}
	echo $s;

	$s = '';
	foreach ($ict as $kct => $vct) {
		$s .= '<tr>';
		$s .= '<td width="15">'
        . dPshowImage (dPfindImage( 'ct'.$kct.'.png', $m ), 15, 17, $ict[$kct])
        . '</td>';
		$s .= "<td nowrap><a href=\"?m=helpdesk&a=list&item_calltype=$kct\">$vct</a></td>";

		foreach ($ist as $kst => $vst) {
			$s .= "<td align=\"center\"><a href=\"?m=helpdesk&a=list&item_calltype={$kct}&item_status=$kst\">"
          . @$stats[$kct][$kst]
          . "</a></td>";
		}

		$s .= '</tr>';
	}
	echo $s;
?>
</table>
