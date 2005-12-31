<?php /* PROJECTS $Id: helpdesklist.php,v 1.1 2005/11/10 22:13:22 pedroix Exp $ */
/**
* Generates a report of the helpdesk logs for given dates including task logs
* Based on the original report tasklist.php by jcgonz
*/
//error_reporting( E_ALL );
$do_report = dPgetParam( $_POST, "do_report", 0 );
$log_all = dPgetParam( $_POST, 'log_all', 0 );
$log_pdf = dPgetParam( $_POST, 'log_pdf', 0 );
$log_ignore = dPgetParam( $_POST, 'log_ignore', 0 );

$list_start_date = dPgetParam( $_POST, "list_start_date", 0 );
$list_end_date = dPgetParam( $_POST, "list_end_date", 0 );

$period = dPgetParam($_POST, "period", 0);
$period_value = dPgetParam($_POST, "pvalue", 1);
if ($period)
{
  $today = new CDate();
  $ts = $today->format(FMT_TIMESTAMP_DATE);
        if (strtok($period, " ") == $AppUI->_("Next"))
                $sign = +1;
        else //if(...)
                $sign = -1;

        $day_word = strtok(" ");
        if ($day_word == $AppUI->_("Day"))
                $days = $period_value;
        else if ($day_word == $AppUI->_("Week"))
                $days = 7*$period_value;
        else if ($day_word == $AppUI->_("Month"))
                $days = 30*$period_value;

        $start_date = new CDate($ts);
        $end_date = new CDate($ts);

        if ($sign > 0)
                $end_date->addSpan( new Date_Span("$days,0,0,0") );
        else
                $start_date->subtractSpan( new Date_Span("$days,0,0,0") );

        $do_report = 1;
        
}
else
{
// create Date objects from the datetime fields
        $start_date = intval( $list_start_date ) ? new CDate( $list_start_date ) : new CDate();
        $end_date = intval( $list_end_date ) ? new CDate( $list_end_date ) : new CDate();
}


if (!$list_start_date) {
	$start_date->subtractSpan( new Date_Span( "14,0,0,0" ) );
}
$end_date->setTime( 23, 59, 59 );

?>
<script language="javascript">
var calendarField = '';

function popCalendar( field ){
	calendarField = field;
	idate = eval( 'document.editFrm.list_' + field + '.value' );
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=250, height=220, scollbars=false' );
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar( idate, fdate ) {
	fld_date = eval( 'document.editFrm.list_' + calendarField );
	fld_fdate = eval( 'document.editFrm.' + calendarField );
	fld_date.value = idate;
	fld_fdate.value = fdate;
}
</script>

<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">

<form name="editFrm" action="index.php?m=helpdesk&a=reports" method="post">
<input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
<input type="hidden" name="report_type" value="<?php echo $report_type;?>" />

<tr>
        <td align="right"><?php echo $AppUI->_('Default Actions'); ?>:</td>
        <td nowrap="nowrap" colspan="2">
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Previous Month'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Previous Week'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Previous Day'); ?>" />
        </td>
        <td nowrap="nowrap">
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Next Day'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Next Week'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Next Month'); ?>" />
        </td>
        <td colspan="3"><input class="text" type="field" size="2" name="pvalue" value="1" /> - value for the previous buttons</td>
<!--
        <td><input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('Previous Month'); ?>" onClick="set(-30)" /></td>
        <td><input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('Previous Week'); ?>" onClick="set(-7)" /></td>
        <td><input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('Next Week'); ?>" onClick="set(7)" /></td>
        <td><input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('Next Month'); ?>" onClick="set(30)" /></td>
-->
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('For period');?>:</td>
	<td nowrap="nowrap">
		<input type="hidden" name="list_start_date" value="<?php echo $start_date->format( FMT_TIMESTAMP_DATE );?>" />
		<input type="text" name="start_date" value="<?php echo $start_date->format( $df );?>" class="text" disabled="disabled" />
		<a href="#" onClick="popCalendar('start_date')">
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
		</a>
	</td>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('to');?></td>
	<td nowrap="nowrap">
		<input type="hidden" name="list_end_date" value="<?php echo $end_date ? $end_date->format( FMT_TIMESTAMP_DATE ) : '';?>" />
		<input type="text" name="end_date" value="<?php echo $end_date ? $end_date->format( $df ) : '';?>" class="text" disabled="disabled" />
		<a href="#" onClick="popCalendar('end_date')">
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
		</a>
	</td>

	<td nowrap="nowrap">
		<input type="checkbox" name="log_all" <?php if ($log_all) echo "checked" ?> />
		<?php echo $AppUI->_( 'Log All' );?>
	</td>
	<td nowrap="nowrap">
		<input type="checkbox" name="log_pdf" <?php if ($log_pdf) echo "checked" ?> />
		<?php echo $AppUI->_( 'Make PDF' );?>
	</td>

	<td align="right" width="50%" nowrap="nowrap">
		<input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('submit');?>" />
	</td>
</tr>
</form>
</table>

<?php
if ($do_report) {
	
	$sql =  "SELECT   hi.*"                                                                  . "\n"
	.	",        concat(rc.contact_first_name, ' ', rc.contact_last_name) requested_by" . "\n"
	.	",        concat(ac.contact_first_name, ' ', ac.contact_last_name) assigned_to"	 . "\n"
	.	"FROM     helpdesk_items       hi"                                               . "\n"
	.	",        users                ru"                                               . "\n"
	.	",        contacts             rc"                                               . "\n"
	.	",        users                au"                                               . "\n"
	.	",        contacts             ac"                                               . "\n";
if ($project_id) {
	$sql.="WHERE    (hi.item_project_id = $project_id"                                     . "\n"
	.	"OR       hi.item_project_id = hi.item_project_id)"                              . "\n"
	.     "AND      ru.user_id         = hi.item_requestor_id"                             . "\n";
} else {
	$sql.="WHERE    ru.user_id         = hi.item_requestor_id"                             . "\n";
}
	$sql.="AND      rc.contact_id      = ru.user_contact"                                  . "\n"
	.	"AND      au.user_id         = hi.item_assigned_to"                              . "\n"
	.	"AND      ac.contact_id      = au.user_contact";
	if (!$log_all) {
		$sql .= "\n"
		.	"AND hi.item_created >= '".$start_date->format( FMT_DATETIME_MYSQL )."'"
		.	"\n"
		.	"AND hi.item_created <= '".$end_date->format( FMT_DATETIME_MYSQL )."'";
	}

	$obj =& new CTask;
	$allowedTasks = $obj->getAllowedSQL($AppUI->user_id);
	if (count($allowedTasks)) {
		$sql .= " AND " . implode(" AND ", $allowedTasks);
	}
	$sql .= "\n" . "ORDER BY hi.item_id";
	$Task_List = db_exec( $sql );
		
	//echo "<pre>".$sql."</pre>";
	//echo db_error();

	echo "<table cellspacing=\"1\" cellpadding=\"4\" border=\"0\" class=\"tbl\">";
	echo "<tr>";
        echo "<th>Number</th>";
        echo "<th>Created On</th>";
	echo "<th>Created By</th>";
        echo "<th>Title</th>";
        echo "<th width=200>Summary</th>";
	echo "<th>Assigned To</th>";
	echo "<th>Status</th>";
	echo "<th>Priority</th>";
        echo "</tr>";
	
	$pdfdata = array();
	$columns = array(
		"<b>".$AppUI->_('Number')."</b>",
		"<b>".$AppUI->_('Created On')."</b>",
		"<b>".$AppUI->_('Created By')."</b>",
		"<b>".$AppUI->_('Title')."</b>",
		"<b>".$AppUI->_('Summary')."</b>",
		"<b>".$AppUI->_('Assigned To')."</b>",
		"<b>".$AppUI->_('Status')."</b>",
		"<b>".$AppUI->_('Priority')."</b>",
	);
	while ($Tasks = db_fetch_assoc($Task_List)){
		$start_date = new CDate( $Tasks['item_created'] );
		$end_date = new CDate( $Tasks['item_created'] );
		$sql_stmt = "SELECT TRIM(SUBSTRING_INDEX(SUBSTRING(sysval_value, LOCATE('"
		.           $Tasks['item_status'] . "|', sysval_value) + 2), '\\n', 1)) item_status_desc"
		.           "\n"
		.           "FROM   sysvals"
		.           "\n"
		.           "WHERE  sysval_title ='HelpDeskStatus'";

		$Log_Status_Query = db_exec($sql_stmt);
		$Log_Status = db_fetch_assoc($Log_Status_Query);

		//echo "<pre>".$sql_stmt."</pre>";
		//echo "<pre>".$Log_Status['item_status_desc']."</pre>";

		//$task_id = $Tasks['task_id'];
		//$sql_user = db_exec ("SELECT * FROM user_tasks WHERE task_id = ".$task_id);
		//$users = null;
		//while ($Task_User = db_fetch_assoc($sql_user)){
		//	//$current_user = $Task_User['user_id'];
		//	if ($users!=null){
		//		$users.=", ";
		//	}
		//	$sql = "SELECT contact_first_name, contact_last_name 
                //        FROM users, contacts
                //        WHERE users.user_contact = contacts.contact_id
                //              AND user_id = ".$Task_User['user_id'];
		//	$sql_user_array = db_exec ($sql);
		//	$user_list = db_fetch_assoc($sql_user_array);
		//	$users .= $user_list['contact_first_name']." ".$user_list['contact_last_name'];
		//}
		if (substr($Log_Status['item_status_desc'], 0, 6) != 'Closed')
		{
		$sql_stmt = "SELECT TRIM(SUBSTRING_INDEX(SUBSTRING(sysval_value, LOCATE('"
		.           $Tasks['item_status'] . "|', sysval_value) + 2), '\\n', 1)) item_priority_desc"
		.           "\n"
		.           "FROM   sysvals"
		.           "\n"
		.           "WHERE  sysval_title ='HelpDeskPriority'";

		$Log_Priority_Query = db_exec($sql_stmt);
		$Log_Priority = db_fetch_assoc($Log_Priority_Query);
		$str =  "<tr valign=\"top\">";
		$str .= "<td align=\"right\">".$Tasks['item_id']."</td>";
		$str .= "<td>".$start_date->format( $df )."</td>";
		$str .= "<td>".$Tasks['requested_by']."</td>";
		$str .= "<td>".$Tasks['item_title']."</td>";
		$str .= "<td>".$Tasks['item_summary']."</td>";
		$str .= "<td>".$Tasks['assigned_to']."</td>";
		$str .= "<td>".$Log_Status['item_status_desc']."</td>";
		$str .= "<td>".$Log_Priority['item_priority_desc']."</td>";
		$str .= "</tr>";
		echo $str;

		$pdfdata[] = array(
			$Tasks['item_id'],
			$start_date->format( $df ),
			$Tasks['requested_by'],
			$Tasks['item_title'],
			$Tasks['item_summary'],
			$Tasks['assigned_to'],
			$Log_Status['item_status_desc'],
			$Log_Priority['item_priority_desc'],
		);

		$sql_stmt = "SELECT   tl.task_log_date"        . "\n"
		.           ",        tl.task_log_description" . "\n"
		.           ",        concat(rc.contact_first_name, ' ', rc.contact_last_name) created_by" . "\n"
		.           "FROM     task_log tl"             . "\n"
		.           ",        users                ru"                                               . "\n"
		.           ",        contacts             rc"                                               . "\n"
		.           "WHERE    tl.task_log_help_desk_id = " . $Tasks['item_id'] . "\n"
		.           "AND      ru.user_id         = tl.task_log_creator"                             . "\n"
		.           "AND      rc.contact_id      = ru.user_contact"                                  . "\n"
		.           "ORDER BY tl.task_log_id";

		$Task_Log_Query = db_exec($sql_stmt);
		$Row_Count = 1;
                while ($Task_Log = db_fetch_assoc($Task_Log_Query)){

		$log_date = new CDate( $Task_Log['task_log_date'] );

		$str =  "<tr valign=\"top\">";
		$str .= "<td align=\"right\">".$Tasks['item_id']."/".$Row_Count."</td>";
		$str .= "<td>".$log_date->format( $df )."</td>";
		$str .= "<td>".$Task_Log['created_by']."</td>";
		$str .= "<td>"."</td>";
		$str .= "<td>".$Task_Log['task_log_description']."</td>";
		$str .= "<td>"."</td>";
		$str .= "<td>"."</td>";
		$str .= "<td>"."</td>";
		$str .= "</tr>";
		echo $str;

		$pdfdata[] = array(
			$Tasks['item_id']."/".$Row_Count,
			$log_date->format( $df ),
			$Task_Log['created_by'],
			"",
			$Task_Log['task_log_description'],
			"",
			"",
			"",
		);
		$Row_Count++;
		}
		}

	}
	echo "</table>";
if ($log_pdf) {
	// make the PDF file
		$sql = "SELECT project_name FROM projects WHERE project_id=$project_id";
		$pname = db_loadResult( $sql );
		echo db_error();

		$font_dir = dPgetConfig( 'root_dir' )."/lib/ezpdf/fonts";
		$temp_dir = dPgetConfig( 'root_dir' )."/files/temp";
		$base_url  = dPgetConfig( 'base_url' );
		require( $AppUI->getLibraryClass( 'ezpdf/class.ezpdf' ) );

		$pdf =& new Cezpdf($paper='A4',$orientation='landscape');
		$pdf->ezSetCmMargins( 1, 2, 1.5, 1.5 );
		$pdf->selectFont( "$font_dir/Helvetica.afm" );

		$pdf->ezText( dPgetConfig( 'company_name' ), 12 );
		// $pdf->ezText( dPgetConfig( 'company_name' ).' :: '.dPgetConfig( 'page_title' ), 12 );		

		$date = new CDate();
		$pdf->ezText( "\n" . $date->format( $df ) , 8 );

		$pdf->selectFont( "$font_dir/Helvetica-Bold.afm" );
		$pdf->ezText( "\n" . $AppUI->_('Helpdesk Report'), 12 );
		$pdf->ezText( "$pname", 15 );
		if ($log_all) {
			$pdf->ezText( "All open entries", 9 );
		} else {
			$pdf->ezText( "Call Log entries from ".$start_date->format( $df ).' to '.$end_date->format( $df ), 9 );
		}
		$pdf->ezText( "\n" );
		$pdf->selectFont( "$font_dir/Helvetica.afm" );
		//$columns = null; This is already defined above... :)
		$title = null;
		$options = array(
			'showLines' => 2,
			'showHeadings' => 1,
			'fontSize' => 9,
			'rowGap' => 4,
			'colGap' => 5,
			'xPos' => 50,
			'xOrientation' => 'right',
			'width'=>'750',
			'shaded'=> 0,
			'cols'=>array(0=>array('justification'=>'right','width'=>150),
					2=>array('justification'=>'left','width'=>95),
					3=>array('justification'=>'center','width'=>75),
					4=>array('justification'=>'center','width'=>75),
					5=>array('justification'=>'center','width'=>200))
		);

		$pdf->ezTable( $pdfdata, $columns, $title, $options );

		if ($fp = fopen( "$temp_dir/temp$AppUI->user_id.pdf", 'wb' )) {
			fwrite( $fp, $pdf->ezOutput() );
			fclose( $fp );
			echo "<a href=\"$base_url/files/temp/temp$AppUI->user_id.pdf\" target=\"pdf\">";
			echo $AppUI->_( "View PDF File" );
			echo "</a>";
		} else {
			echo "Could not open file to save PDF.  ";
			if (!is_writable( $temp_dir )) {
				"The files/temp directory is not writable.  Check your file system permissions.";
			}
		}
	}
}
?>
</table>
