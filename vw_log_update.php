<?php /* $Id$ */
GLOBAL $AppUI, $hditem, $ist, $HELPDESK_CONFIG;
//New style for date selection--KZHAO 9-11-2006
$item_id = dPgetParam( $_GET, 'item_id', 0 );

// check permissions
//$canEdit = !getDenyEdit( 'tasks', $item_id );
//if (!$canEdit) {
//	$AppUI->redirect( "m=public&a=access_denied" );
//}

$users = getAllowedUsers();

$task_log_id = intval( dPgetParam( $_GET, 'task_log_id', 0 ) );
$log = new CHDTaskLog();
if ($task_log_id) {
	$log->load( $task_log_id );

	//Prevent users from editing other ppls timecards.
	// KZHAO  11-30-2006
	// Problem: the $HELPDESK_CONFIG['minimum_edit_level'] is based on pre-defined user types in dP/functions/admin_func.php
	// the user types are not consistent with the user type defined for actual users...
	// Solution: use hard-coded admin user type 7 here
	//$can_edit_task_logs = $HELPDESK_CONFIG['minimum_edit_level']>=$AppUI->user_type;
	if($HELPDESK_CONFIG['minimum_edit_level']>=$AppUI->user_type || $AppUI->user_type==1)
		$can_edit_task_logs=true;

	//echo "---".$AppUI->user_type.">".$HELPDESK_CONFIG['minimum_edit_level']."!!!!";
	if (!$can_edit_task_logs)
	{	
		if($log->task_log_creator!= $AppUI->user_id){
			$AppUI->redirect( "m=public&a=access_denied" );
		}
	}
} else {
	$log->task_log_help_desk_id = $item_id;
	$log->task_log_name = $hditem['item_title'];
}
// Disable cost codes--KZHAO 2-11-2007
// Lets check cost codes
/*$q = new DBQuery;
$q->addTable('billingcode');
$q->addQuery('billingcode_id, billingcode_name');
$q->addWhere('billingcode_status=0');
$q->addWhere("company_id='$hditem[item_company_id]'"." OR company_id='0'");
$q->addOrder('billingcode_name');

$task_log_costcodes[0]=$AppUI->_('None');
$ptrc = $q->exec();
echo db_error();
$nums = 0;
if ($ptrc)
	$nums=db_num_rows($ptrc);
for ($x=0; $x < $nums; $x++) {
        $row = db_fetch_assoc( $ptrc );
        $task_log_costcodes[$row["billingcode_id"]] = $row["billingcode_name"];
}
*/
//if ($canEdit) {
// Task Update Form
	$df = $AppUI->getPref( 'SHDATEFORMAT' );
	$log_date = new CDate( $log->task_log_date );

	if ($task_log_id) {
		echo $AppUI->_( "Edit Log" );
	} else {
		echo $AppUI->_( "Add Log" );
	}
?>

<!-- TIMER RELATED SCRIPTS -->
<script language="JavaScript">
	// please keep these lines on when you copy the source
	// made by: Nicolas - http://www.javascript-page.com
	// adapted by: Juan Carlos Gonzalez jcgonz@users.sourceforge.net
	
	var timerID       = 0;
	var tStart        = null;
    var total_minutes = -1;
	
	function UpdateTimer() {
	   if(timerID) {
	      clearTimeout(timerID);
	      clockID  = 0;
	   }
	
     // One minute has passed
     total_minutes = total_minutes+1;
	   
	   document.getElementById("timerStatus").innerHTML = "( "+total_minutes+" <?php echo $AppUI->_('minutes elapsed'); ?> )";

	   // Lets round hours to two decimals
	   var total_hours   = Math.round( (total_minutes / 60) * 100) / 100;
	   document.editFrm.task_log_hours.value = total_hours;
	   
	   timerID = setTimeout("UpdateTimer()", 60000);
	}
	
	function timerStart() {
		if(!timerID){ // this means that it needs to be started
			document.editFrm.timerStartStopButton.value = "<?php echo $AppUI->_('Stop'); ?>";
      total_minutes = Math.round(document.editFrm.task_log_hours.value*60) - 1;
      UpdateTimer();
		} else { // timer must be stoped
			document.editFrm.timerStartStopButton.value = "<?php echo $AppUI->_('Start'); ?>";
			document.getElementById("timerStatus").innerHTML = "";
			timerStop();
		}
	}

	
	function timerStop() {
	   if(timerID) {
	      clearTimeout(timerID);
	      timerID  = 0;
        total_minutes = total_minutes-1;
	   }
	}
	
	function timerReset() {
		document.editFrm.task_log_hours.value = "0.00";
    total_minutes = -1;
	}
	
  function popCalendar( field ){
    calendarField = field;
    idate = eval( 'document.editFrm.task_' + field + '.value' );
    window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=270,height=250,scollbars=false' );
  }

  function setCalendar( idate, fdate ) {
    fld_date = eval( 'document.editFrm.task_' + calendarField );
    fld_fdate = eval( 'document.editFrm.' + calendarField );
    fld_date.value = idate; 
    fld_fdate.value = fdate;
  }
  
  //FIXME
  function updateStatus(obj){
    var f = document.editFrm;
    if(obj.options[obj.selectedIndex].value>0){
      if(f.item_status.selectedIndex==0){
    	f.item_status.selectedIndex=<?php echo $HELPDESK_CONFIG['assigned_status_id'];?>;
      }
    }
  }	

</script>

<!-- END OF TIMER RELATED SCRIPTS -->


<table cellspacing="1" cellpadding="2" border="0" width="100%">
<form name="editFrm" action="?m=helpdesk&a=view&item_id=<?php echo $item_id; ?>" method="post">
	<input type="hidden" name="uniqueid" value="<?php echo uniqid(""); ?>" />
	<input type="hidden" name="dosql" value="do_item_aed" />
	<input type="hidden" name="item_id" value="<?php echo $item_id; ?>" />
	<input type="hidden" name="task_log" value="1" />
	<input type="hidden" name="task_log_id" value="<?php echo $log->task_log_id; ?>" />
	<input type="hidden" name="task_log_help_desk_id" value="<?php echo $item_id; ?>" />
	<input type="hidden" name="task_log_creator" value="<?php echo $AppUI->user_id; ?>" />
	<input type="hidden" name="task_log_name" value="Update :<?php echo $log->task_log_name; ?>" />
<tr>
	<td nowrap="nowrap">
		<?php echo $AppUI->_('Date'); ?><br />
	<!-- patch by rowan  bug #890841 against v1.0.2-1   email: bitter at sourceforge dot net -->
		<input type="hidden" name="task_log_date" value="<?php echo $log_date->format( FMT_DATETIME_MYSQL ); ?>">
	<!-- end patch #890841 -->
		<input type="text" name="log_date" value="<?php echo $log_date->format( $df ); ?>" class="disabledText" disabled="disabled">
		<a href="#" onClick="popCalendar('log_date')">
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar'); ?>" border="0" />
		</a>
	</td>
	<td><?php echo $AppUI->_('Summary'); ?>:<br />
		<input type="text" class="text" name="task_log_name" value="<?php echo $log->task_log_name; ?>" maxlength="255" size="30" />
	</td>

</tr>
<tr>
      <td><?php echo $AppUI->_('Status')?>:<br />
	  <?php 
		//ANDY allowed status ; workflow - need more testing
		//require_once DP_BASE_DIR . '/modules/helpdesk/custom/workflow.inc.php';
		foreach($ist as $key => $value) { $lv_ist[$key] = $value; }
		
		echo arraySelect( $lv_ist, 'item_status', 'size="1" class="text" id="medium"',@$hditem["item_status"], true )
	  ?>
	  </td>
	<td rowspan="3">
	<?php echo $AppUI->_('Description'); ?>:<br />
		<textarea name="task_log_description" class="textarea" cols="50" rows="6"><?php echo $log->task_log_description; ?></textarea>
	</td>
</tr>
<!--tr-->
  <!--Disable the cost code  by KZHAO 2-12-2007-->
	<!--td>
		<?php //echo $AppUI->_('Cost Code'); ?>:<br /-->
<?php
		//echo arraySelect( $task_log_costcodes, 'task_log_costcodes', 'size="1" class="text" onchange="javascript:task_log_costcode.value = this.options[this.selectedIndex].value;"', '' );
?>
		<!--&nbsp;->&nbsp; <input type="text" class="text" name="task_log_costcode" value="<?php echo $log->task_log_costcode; ?>" maxlength="8" size="8" />
	</td-->
<!--/tr-->
<tr>
	<td>
		<?php echo $AppUI->_('Assigned To'); ?>:<br />
      <?php 
			//ANDY not allowed to change assign to after assigned status; 0-new, 1-assigned
// Removed by HaTaX - Why would we want this?
//			if ($hditem["item_status"] > 1) {
//				$lv_users = array( $hditem["item_assigned_to"] => $users[$hditem["item_assigned_to"]] );
//				echo arraySelect( $lv_users, 'item_assigned_to', 'size="1" class="text" id="iat" ', @$hditem["item_assigned_to"] ); 
//			} else {
		        echo arraySelect( arrayMerge( array( 0 => '' ), $users), 'item_assigned_to', 'size="1" class="text" id="iat" onchange="updateStatus(this)"',
                          @$hditem["item_assigned_to"] ); 
//			}
	?>
	</td>
</tr>
<tr>
	<td colspan="2" valign="bottom" align="right">
		<input type="submit" class="button" value="<?php echo $AppUI->_($task_log_id?'update task log':'Create Task Log'); ?>" onclick="" />
	</td>
</tr>

</form>
</table>
<?php //}
?>


