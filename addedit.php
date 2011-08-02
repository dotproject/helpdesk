<?php /* HELPDESK $Id: addedit.php 334 2011-6-07 21:26:00CST-6 kpeters $ */
if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

global $HELPDESK_CONFIG;

$item_id = dPgetParam($_GET, 'item_id', 0);

$allowedCompanies = arrayMerge( array( 0 => '' ), getAllowedCompanies() );

$projects = getAllowedProjectsForJavascript(1);

// by ig.moon 2/16/2010
 if($projects=="") { 
      ?>
        <script language='javascript'>
            alert('Can not find the list for allowed projects!');
        </script>
      <?php
     $projects[] = "";      
 }
 
// Lets check cost codes
/*$q = new DBQuery;
$q->addTable('billingcode');
$q->addQuery('billingcode_id, billingcode_name');
$q->addWhere('billingcode_status=0');

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
// Pull data if editing an existing item
if ($item_id) {
	$q = new DBQuery;
	$q->addQuery('*');
	$q->addTable('helpdesk_items');
	$q->addWhere('item_id = \''.$item_id."'");
	$hditem = $q->loadHash();
	$q->clear();
}
//echo "\n<br />HDITEM: ";
//var_dump($hditem);
//echo "\n<br />GET: ".count($_GET);
//var_dump($_GET);
if ( (!$hditem) && (count($_GET) > 3) && (!$item_id) ) {
	$item_calltype = dPgetParam($_GET, 'item_calltype', 0);
	$item_status = dPgetParam($_GET, 'item_status', 0);
	$item_priority = dPgetParam($_GET, 'item_priority', 0);
	$item_severity = dPgetParam($_GET, 'item_severity', 0);
	$item_source = dPgetParam($_GET, 'item_source', 0);
	$item_os = dPgetParam($_GET, 'item_os', 0);
	$item_application = dPgetParam($_GET, 'item_application', 0);
	$project_id = dPgetParam($_GET, 'project', 0);
	$company_id = dPgetParam($_GET, 'company', 0);
	$assigned_to = dPgetParam($_GET, 'assigned_to', 0);
	$requestor = dPgetParam($_GET, 'requestor', 0);
}

// Check permissions for this record
$perms =& $AppUI->acl();
$canView = $perms->checkModule( $m, 'view' );
if ($item_id) {
	$canEdit = $perms->checkModuleItem($m, 'edit', $item_id);
} else {
	$canEdit = $perms->checkModule($m, 'add');
}

if (!$canView) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

if(!@$hditem["item_assigned_to"]){
  if ($assigned_to > 0) {
      @$hditem["item_assigned_to"] = $assigned_to;
  }
  elseif($HELPDESK_CONFIG['default_assigned_to']=='-1'){
      @$hditem["item_assigned_to"] = 0;
  }
  elseif($HELPDESK_CONFIG['default_assigned_to']=='0'){
      @$hditem["item_assigned_to"] = $AppUI->user_id;
  }
  else{
      @$hditem["item_assigned_to"] = @$HELPDESK_CONFIG['default_assigned_to'];
  }
}


if(!@$hditem["item_status"]) {
  if (@$hditem["item_assigned_to"]) {
                @$hditem["item_status"]=@$HELPDESK_CONFIG['assigned_status_id'];
  }
  elseif ($item_status > 0)  {
                @$hditem["item_status"]=@$item_status;
  }
  else {
                @$hditem["item_status"]=0;
  }
}


if(!@$hditem["item_company_id"]) {
  if ($company_id > 0) {
    @$hditem["item_company_id"] = $company_id;
  }
  else if ($HELPDESK_CONFIG['default_company_current_company']){
    @$hditem["item_company_id"] = $AppUI->user_company;
  }
}

if ( (!@$hditem["item_project_id"]) && ($project_id > 0) ) {
    @$hditem["item_project_id"] = $project_id;
}

if ( (!@$hditem["item_calltype"]) && ($item_calltype > 0) ) {
    @$hditem["item_calltype"] = $item_calltype;
}

if ( (!@$hditem["item_os"]) && ($item_os > 0) ) {
    @$hditem["item_os"] = $item_os;
}

if ( (!@$hditem["item_application"]) && ($item_application > 0) ) {
    @$hditem["item_application"] = $item_application;
}

if ( (!@$hditem["item_requestor"]) && ($item_requestor > 0) ) {
    @$hditem["item_requestor"] = $item_requestor;
}

if ( (!@$hditem["item_severity"]) && ($item_severity) > 0 ) {
    @$hditem["item_severity"] = $item_severity;
}

if ( (!@$hditem["item_source"]) && ($item_source > 0) ) {
    @$hditem["item_source"] = $item_source;
}

if ( (!@$hditem["item_priority"]) && ($item_priority > 0) ) {
    @$hditem["item_priority"] = $item_priority;
}

// get current user's company id and use it to filter users
$q = new DBQuery;
$q->addQuery('DISTINCT contact_company');
$q->addTable('contacts');
$q->addTable('users');
$q->addWhere('user_id = \''.$AppUI->user_id.'\'');
$q->addWhere('user_contact = contact_id');
$allowedComp = $q->loadHashList();
$q->clear();

if(!count($allowedComp)){
	echo "ERROR: No company found for current user!!<br>";
$compId=0;
} elseif(count($allowedComp)==1) {
	$tmp=array_keys($allowedComp);
//	print_r($tmp);
	$compId=$tmp[0];
//	echo $compId;
} else {
	echo "ERROR: Multiple companies found for current user!!!<br>";
	$compId=0;
}

// Determine whether current user is a client
if($compId!=$HELPDESK_CONFIG['the_company']) {
	$is_client=1;
} else {
	$is_client=0;
}
//ANDY - issue management by IT
//$is_client = 0;
//print_r($allowedComp);

$users = getAllowedUsers($compId,1);

//$allowedCompanies = arrayMerge( array( 0 => '' ), getAllowedCompanies($compId) );

$q = new DBQuery;
$q->addQuery('company_id, company_name');
$q->addTable('companies');
$q->addWhere(getCompanyPerms('company_id'));
$q->addOrder('company_name');
$companies = arrayMerge(array(0=>''), $q->loadHashList());
$q->clear();


//Use new watcher list --KZHAO
//if($item_id){ 
  // if editing an existing helpdesk item, get its watchers from database
	$q = new DBQuery;
	$q->addQuery('hiw.user_id, CONCAT(contact_last_name, ",", contact_first_name) AS name, contact_email');
	$q->addTable('helpdesk_item_watchers','hiw');
	$q->addJoin('users', 'u', 'hiw.user_id = u.user_id');
	$q->addJoin('contacts', 'c', 'u.user_contact = c.contact_id');
	$q->addWhere('item_id = \''.$item_id."'");
	$watchers = $q->loadHashList();
	$q->clear();

//}
//else{ // for a new item, check default
//  if($HELPDESK_CONFIG['default_watcher'] && $HELPDESK_CONFIG['default_watcher_list']){
//      $watchers = explode(',',$HELPDESK_CONFIG['default_watcher_list']);
      /*echo "<pre>";
      print_r($watchers);
      echo "<pre>";*/
//  }

//}

// Setup the title block
$ttl = $item_id ? 'Editing Issue Item' : 'Adding Issue Item';

$titleBlock = new CTitleBlock( $ttl, 'helpdesk.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=helpdesk", 'home' );
$titleBlock->addCrumb( "?m=helpdesk&a=list", 'list');

if ($item_id) {
  $titleBlock->addCrumb( "?m=helpdesk&a=view&item_id=$item_id", 'view this item' );
}

$titleBlock->show();

if ($item_id) { 
	$df = $AppUI->getPref('SHDATEFORMAT');
	$tf = $AppUI->getPref('TIMEFORMAT');
	$item_date = new CDate( $hditem["item_created"] );
	if( (!strcmp($hditem["item_deadline"], "0000-00-00 00:00:00")==0) &&
						$hditem['item_deadline']!="" ) {
		$deadline_date = new CDate( $hditem["item_deadline"] );
	} else {
		//$deadline_date = new CDate("0000-00-00 00:00:00");
		$deadline_date = ' ';
	}
	$tc = $item_date->format( "$df $tf" );
} else {
	//$deadline_date = new CDate("0000-00-00 00:00:00");
	$deadline_date = '0';
	$item_date = new CDate();
	$item_date = $item_date->format( FMT_DATETIME_MYSQL );
	$hditem["item_created"] = $item_date;
}

function getRequestorInfo($requser, $usertype, $datatype) {
	switch ($usertype) {
	  case 0:
		break;
	  case 1:
		$q = new DBQuery();
		$q->addTable('users','u');
		$q->addQuery('u.user_id as id');
		$q->addJoin('contacts','c','u.user_contact = c.contact_id');
		$q->addQuery("c.contact_email as email, c.contact_phone as phone, CONCAT(c.contact_first_name,' ', c.contact_last_name) as name");
		$q->addWhere('u.user_id='.$requser);
        	break;
	  case 2:
		$q = new DBQuery();
		$q->addTable('contacts','c');
		$q->addQuery("c.contact_email as email, c.contact_phone as phone, CONCAT(c.contact_first_name,' ', c.contact_last_name) as name");
		$q->addWhere('contact_id='.$requser);
		break;
	  default:
		break;
	}
	if(isset($q)) {
		$result = $q->loadHash();
		$q->clear();
		if ($datatype==1) return $result['email'];
		if ($datatype==2) return $result['phone'];
	}
}

?>
<html>

<script language="javascript" type="text/javascript">
function checkRequestorType() {
	var f = document.frmHelpDeskItem;
	if ( f.item_requestor_type.value != 0 ) {
	    document.getElementById("ire").disabled=true;
	    document.getElementById("irp").disabled=true;
	} else {
	    document.getElementById("ire").disabled=false;
	    document.getElementById("irp").disabled=false;
	}
}

function submitIt() {
  var f   = document.frmHelpDeskItem;
  var msg = '';

  if ( f.item_title.value.length < 1 ) {
    msg += "\n<?php echo $AppUI->_('Title'); ?>";
    f.item_title.focus();
  }

  if( f.item_requestor.value.length < 1 ) {
    msg += "\n<?php echo $AppUI->_('Requestor'); ?>";
    f.item_requestor.focus();
  }

  if( f.item_summary.value.length < 1 ) {
    msg += "\n<?php echo $AppUI->_('Summary'); ?>";
    f.item_summary.focus();
  }

  //concat all the multiselect values together for easier retrieval on the back end.
  var watchers = "";
  var list = f.watchers_select;
  for (var i=0, n = list.options.length; i < n; i++) {
    var user = list.options[i];
    if(user.selected)
    	watchers += user.value + ",";
  }
  if(watchers.length>0){
  	f.watchers.value = watchers.substring(0,watchers.length-1);
  }
 
  if( msg.length > 0) {
    alert('<?php echo $AppUI->_('helpdeskSubmitError', UI_OUTPUT_JS); ?>:' + msg);
  } else {
    f.submit();
  }
} 

function popUserDialog() {
//	var target='./index.php?m=helpdesk&a=selector&callback=setUserRequestor&table=users&dialog=1';
//TODO: FIX UPDATE - FIXED?  Test with a null company
	var target='./index.php?m=helpdesk&a=selector&callback=setUserRequestor&table=users&dialog=1&comp=<?php echo $compId; ?>';
	window.open(target, 'selector', 'left=50,top=50,height=400,width=400,resizable,scrollbars=yes');
}

function popContactDialog() {
	var target='./index.php?m=helpdesk&a=selector&callback=setContactRequestor&table=contacts&dialog=1';
	window.open(target, 'selector', 'left=50,top=50,height=400,width=400,resizable,scrollbars=yes');
}

function popClientContactDialog() {
	var target='./index.php?m=helpdesk&a=selector&callback=setContactRequestor&table=contacts&dialog=1&comp=<?php echo $compId; ?>';
	window.open(target, 'selector', 'left=50,top=50,height=300,width=400,resizable,scrollbars=yes');
}

var oldRequestor = '';

// Callback function for the generic selector
function setRequestor( key, val ) {
  var f = document.frmHelpDeskItem;

  if (val != '') {
    oldRequestor = f.item_requestor.value;
    f.item_requestor_id.value = key;
    f.item_requestor.value = val;
    if( (key == '') || (key == 0) ) {
      // Since the key is empty or not assigned, wipe the e-mail and phone fields
      f.item_requestor_email.value = '';
      f.item_requestor_phone.value = '';
    }
  }//end of blank val if
}//end of function

function setUserRequestor( key, val ) {
  var f = document.frmHelpDeskItem;

  if (val != '') {
    setRequestor( key, val );
    f.item_requestor_type.value = 1
    document.getElementById("ire").disabled=true;
    document.getElementById("irp").disabled=true;
// Always pulls the values when page was loaded		TODO: Fix it & remove workaround below
//    document.getElementById("ire").value = "<?php echo getRequestorInfo($hditem["item_requestor_id"], $hditem["item_requestor_type"], 1); ?>";
//    document.getElementById("irp").value = "<?php echo getRequestorInfo($hditem["item_requestor_id"], $hditem["item_requestor_type"], 2); ?>";
    document.getElementById("ire").value = "Filled upon submission"
    document.getElementById("irp").value = "Filled upon submission"
  }
}

function setContactRequestor( key, val ) {
  var f = document.frmHelpDeskItem;

  if (val != '') {
    setRequestor( key, val );
    f.item_requestor_type.value = 2
    document.getElementById("ire").disabled=true;
    document.getElementById("irp").disabled=true;
// Always pulls the values when page was loaded		TODO: Fix it & remove workaround below
//    document.getElementById("ire").value = "<?php echo getRequestorInfo($hditem["item_requestor_id"], $hditem["item_requestor_type"], 1); ?>";
//    document.getElementById("irp").value = "<?php echo getRequestorInfo($hditem["item_requestor_id"], $hditem["item_requestor_type"], 2); ?>";
    document.getElementById("ire").value = "Filled upon submission"
    document.getElementById("irp").value = "Filled upon submission"
  }
}

function updateStatus(obj){
  var f = document.frmHelpDeskItem;
  
  if(obj.options[obj.selectedIndex].value>0){
    if(f.item_status.selectedIndex <= <?php echo $HELPDESK_CONFIG['assigned_status_id']?>){
    	f.item_status.selectedIndex = <?php echo $HELPDESK_CONFIG['assigned_status_id']?>;
    }
  } else {
    f.item_status.selectedIndex=0;
  }
}

<?php 
	$ua = $_SERVER['HTTP_USER_AGENT'];
	$isMoz = strpos( $ua, 'Gecko' ) !== false;

	print "\nvar projects = new Array(";
	if(count($projects)>0){
		print implode(",",$projects );
			
	}
	print ");"; 
	
?>

// Dynamic project list handling functions
function emptyList( list ) {
<?php 
	if ($isMoz) { 
?>
	 list.options.length = 0;
<?php 
 	} else {
?>
	 while( list.options.length > 0 )
		list.options.remove(0);
<?php } ?>

}

function addToList( list, text, value ) {
<?php if ($isMoz) { ?>
  list.options[list.options.length] = new Option(text, value);
<?php } else { ?>
  var newOption = document.createElement("OPTION");
  newOption.text = text;
  newOption.value = value;
  list.add( newOption, 0 );
<?php } ?>

}

function changeList( listName, source, target ) {
  var f = document.frmHelpDeskItem;
  var list = eval( "f."+listName );
  // Clear the options
  emptyList( list );
  // Refill the list based on the target
  // Add a blank first to force a change
   addToList( list, '', '0' );

   for (var i=0, n = source.length; i < n; i++) {
    if( source[i][0] == target ) {
      addToList( list, source[i][2], source[i][1] );
    }
  }
}

// Select an item in the list by target value
function selectList( listName, target ) {
  var f = document.frmHelpDeskItem;
  var list = eval( 'f.'+listName );

  for (var i=0, n = list.options.length; i < n; i++) {
    if( list.options[i].value == target ) {
      list.options.selectedIndex = i;
      return;
    }
  }
 
}
				  
</script>

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
		document.frmHelpDeskItem.task_log_hours.value = total_hours;
	   	timerID = setTimeout("UpdateTimer()", 60000);
	}
	
	function timerStart() {
		if(!timerID) { // this means that it needs to be started
			document.frmHelpDeskItem.timerStartStopButton.value = "<?php echo $AppUI->_('Stop');?>";
			total_minutes = Math.round(document.frmHelpDeskItem.task_log_hours.value*60) - 1;
			UpdateTimer();
		} else { // timer must be stoped
			document.frmHelpDeskItem.timerStartStopButton.value = "<?php echo $AppUI->_('Start');?>";
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
		document.frmHelpDeskItem.task_log_hours.value = "0.00";
		total_minutes = -1;
	}
	
	function popCalendar(now, field, cdate ){
		calendarField = field;
		if(now==0)// if the deadline is already specified
			idate = eval( 'document.frmHelpDeskItem.item_' + field + '.value' );
		else  // if there is no deadline
			idate = cdate;
		window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=270,height=250,scollbars=false' );
	}
     
	function setCalendar( idate, fdate ) {
		fld_date = eval( 'document.frmHelpDeskItem.item_' + calendarField );
		fld_fdate = eval( 'document.frmHelpDeskItem.' + calendarField );
		if (idate=="") {
			fld_date.value = "N/A"; 
			fld_fdate.value = "N/A";
		} else {
			fld_date.value = idate; 
			fld_fdate.value = fdate;
		}
	}
     
</script>
<!-- END OF TIMER RELATED SCRIPTS -->
<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
  <form name="frmHelpDeskItem" action="?m=helpdesk" method="post">
  <input type="hidden" name="dosql" value="do_item_aed" />
  <input name="del" type="hidden" value="0" />
  <input type="hidden" name="item_id" value="<?php echo $item_id; ?>" />
  <input type="hidden" name="item_requestor_type" value="<?php echo @$hditem["item_requestor_type"]; ?>" />
  <input type="hidden" name="item_requestor_id" value="<?php echo @$hditem["item_requestor_id"]; ?>" />
  <input type="hidden" name="item_created" value="<?php echo @$hditem["item_created"]; ?>" />
  <?php if (!$item_id): ?>
  <input type="hidden" name="item_created_by" value="<?php echo $AppUI->user_id; ?>" />
  <?php endif; ?>

  <tr>
  <td valign="top" width="50%">
    <table cellspacing="0" cellpadding="2" border="0">
    <?php if ($item_id): ?>
    <tr>
      <td align="right" nowrap="nowrap"><?php echo $AppUI->_('Date Created'); ?>:</td>
      <td width="100%"><strong><?php echo $tc; ?></strong></td>
    </tr>
    <?php endif; ?>
    <tr>

      <td align="right"><font color="red"><label for="it">* <?php echo $AppUI->_('Title'); ?>:</label></font></td>
      <td valign="top"><input type="text" class="text" id="it" name="item_title"
                              value="<?php echo @$hditem["item_title"]; ?>" maxlength="64" /></td>
    </tr>

    <tr>
      <td align="right" nowrap="nowrap"><font color="red"><label for="ir">* <?php echo $AppUI->_('Requestor'); ?>:</label></font></td>
      <td valign="top" nowrap="nowrap">
        <input type="text" class="text" id="ir" name="item_requestor"
        value="<?php echo @$hditem["item_requestor"]; ?>" maxlength="64"
	onClick="checkRequestorType();"
        onChange="if (this.value!=oldRequestor) {
                    document.frmHelpDeskItem.item_requestor_id.value = 0;
                    document.frmHelpDeskItem.item_requestor_type.value = 0;
                    checkRequestorType();
                  }" />
<?php if ($is_client==0) { ?>
      <input type="button" class="button" 
      		value="<?php echo $AppUI->_('Users'); ?>" onclick="popUserDialog();" />
<?php } ?>
      <input type="button" class="button" alt="Choose from Contacts"
		value="<?php
			echo $AppUI->_('Contacts').'" ';
			if ($is_client==1) {
				echo 'onclick="popClientContactDialog()';
			} else {
				echo 'onclick="popContactDialog()';
			}
			?>;" />
      </td>
    </tr>

    <tr>
      <td align="right" nowrap="nowrap"><label for="ire">&dagger; <?php echo $AppUI->_('Requestor E-mail'); ?>:</label></td>
      <td valign="top"><input type="text" class="text" id="ire"
	                      onClick="checkRequestorType();"
      <?php if ($hditem["item_requestor_type"]!=0): ?>
                              disabled="disabled"
                              value="<?php echo getRequestorInfo($hditem["item_requestor_id"], $hditem["item_requestor_type"], 1); ?>"
      <?php else: ?>
                              value="<?php echo @$hditem["item_requestor_email"]; ?>"
      <?php endif; ?>
                              name="item_requestor_email"
                              maxlength="64" /></td>
    </tr>

    <tr>
      <td align="right" nowrap="nowrap"><label for="irp">&dagger; <?php echo $AppUI->_('Requestor Phone'); ?>:</label></td>
      <td valign="top"><input type="text" class="text" id="irp"
                              onClick="checkRequestorType();"
      <?php if ($hditem["item_requestor_type"]!=0): ?>
                              disabled="disabled"
                              value="<?php echo getRequestorInfo($hditem["item_requestor_id"], $hditem["item_requestor_type"], 2); ?>"
      <?php endif; ?>
                              name="item_requestor_phone"
                              value="<?php echo @$hditem["item_requestor_phone"]; ?>"
                              maxlength="30" /></td>
    </tr>

    <tr>
      <td align="right"><label for="c"><?php echo $AppUI->_('Company'); ?>:</label></td>
      <td><?php echo arraySelect( $allowedCompanies, 'item_company_id', 'size="1" class="text" id="c" onchange="changeList(\'item_project_id\',projects, this.options[this.selectedIndex].value)"',
                          @$hditem["item_company_id"] ); ?></td>
    </tr>

    <tr>
      <td align="right"><label for="p"><?php echo $AppUI->_('Project'); ?>:</label></td>
      <td><select name="item_project_id" size="1" class="text" id="p"></select></td>
    </tr>

    <tr>
      <td align="right" valign="top"><label for="iat"><?php echo $AppUI->_('Assigned To'); ?>:</label></td>
      <td><?php 
          if($is_client)
            echo arraySelect( arrayMerge( array( 0 => '' ), $users), 'item_assigned_to', 'size="1" class="text" id="iat" disabled onchange="updateStatus(this)"', @$hditem["item_assigned_to"] ); 
          else
            echo arraySelect( arrayMerge( array( 0 => '' ), $users), 'item_assigned_to', 'size="1" class="text" id="iat" onchange="updateStatus(this)"', @$hditem["item_assigned_to"] );
          ?>
        <!--
	<br />Send email notification to:<br />  
	<input type="checkbox" name="item_notify_requestor" value="1" id="inr" checked />
	<label for="inw"><?php echo $AppUI->_( 'Requestor' ); ?></label>
	<input type="checkbox" name="item_notify" value="1" id="in"
        <?php 
          if (!$item_id) {
            print $HELPDESK_CONFIG['default_notify_by_email'] ? "checked" : "";
          } else {
            print $hditem["item_notify"] ? "checked" : "";
          }
        ?>
        />
        <label for="in"><?php echo $AppUI->_( 'Assignee' ); ?></label>
	<input type="checkbox" name="item_notify_watcher" value="1" id="inw" checked />
	<label for="inw"><?php echo $AppUI->_( 'Watchers' ); ?></label>
	</td>
        -->

    </tr>

    <?php   if($item_id){
    		//existing item
		if($hditem['item_notify']) $emailNotify=1;
		else $emailNotify=0;
	    }
	    else{
		$emailNotify=$HELPDESK_CONFIG['default_notify_by_email'];
	    }
	
    ?>
    <tr>
       <td align="right" valign="top"><label for="iat"><?php echo $AppUI->_('E-mail Notification'); ?>:</label>
       </td>  
       <td>
       	    <input type="radio" name="item_notify" value="1" id="ina" 
	    		<?php if($emailNotify) echo "checked";
             if($is_client) echo " disabled "; 
           ?> />
		<label for="ina"><?php echo "&nbsp;".$AppUI->_( 'Yes' )."&nbsp;&nbsp;&nbsp;&nbsp;"; ?></label>
	    <input type="radio" name="item_notify" value="0" id="inn" 
	    		<?php if(!$emailNotify) echo "checked";
             if($is_client) echo " disabled ";
          ?> />
	       <label for="inn"><?php echo "&nbsp;".$AppUI->_( 'No' )."&nbsp;&nbsp;&nbsp;&nbsp;"; ?></label>
       </td>
    </tr>
  </table>
  </td>
  <td valign="top" width="50%">
    <table cellspacing="0" cellpadding="2" border="0">
    <tr>
      <td align="right" nowrap="nowrap"><label for="ict"><?php echo $AppUI->_('Issue Type'); ?>:</label></td>
      <td><?php echo arraySelect( $ict, 'item_calltype', 'size="1" class="text" id="ict"',
                          @$hditem["item_calltype"], true ); ?></td>
    </tr>

    <tr>
      <td align="right" nowrap="nowrap"><label for="ics"><?php echo $AppUI->_('Source'); ?>:</label></td>
      <td><?php echo arraySelect( $ics, 'item_source', 'size="1" class="text" id="ics"',
                          @$hditem["item_source"], true); ?></td>
    </tr>

    <tr>
      <td align="right"><label for="ist"><?php echo $AppUI->_('Status'); ?>:</label></td>
      <td><?php 
	  //ANDY ; limit to 0 ~ 9 status
	  foreach($ist as $key => $value) { if ($key < 10) $lv_ist[$key] = $value; }

	  echo arraySelect( $lv_ist, 'item_status', 'size="1" class="text" id="ist"',
                          @$hditem["item_status"], true ); ?></td>
    </tr>

    <tr>
      <td align="right"><label for="ipr"><?php echo $AppUI->_('Priority'); ?>:</label></td>
      <td><?php echo arraySelect( $ipr, 'item_priority', 'size="1" class="text" id="ipr"',
                          @$hditem["item_priority"], true ); ?></td>
    </tr>

    <tr>
      <td align="right"><label for="isv"><?php echo $AppUI->_('Severity'); ?>:</label></td>
      <td><?php echo arraySelect( $isv, 'item_severity', 'size="1" class="text" id="isv"',
                          @$hditem["item_severity"], true ); ?></td>
    </tr>

    <tr>
      <td align="right" nowrap="nowrap"><label for="ios"><?php echo $AppUI->_('Operating System'); ?>:</label></td>
      <td><?php echo arraySelect( $ios, 'item_os', 'size="1" class="text" id="ios"',
                          @$hditem["item_os"], true); ?></td>
    </tr>

    <tr>
      <td align="right"><label for="iap"><?php echo $AppUI->_('Application'); ?>:</label></td>
      <td><?php echo arraySelect( $iap, 'item_application', 'size="1" class="text" id="iap"',
                          @$hditem["item_application"], true); ?></td>
    </tr>
    <tr>
      <td align="right" nowrap="nowrap"><label for="idl"><?php echo $AppUI->_('Deadline'); ?>:</label>
      </td>
      <td>
	<input type="hidden" name="item_deadline" value="<?php 
		if($item_id && $hditem['item_deadline']!=NULL && !strcmp($hditem['item_deadline'],"0000-00-00 00:00:00")==0
								&& !strcmp($hditem['item_deadline'],"N/A")==0) {
			echo $deadline_date->format( FMT_DATETIME_MYSQL );
		} else {
			echo "N/A";
		}
		?>">	
	<input type="text" name="deadline" value="<?php 
		if($item_id && $hditem['item_deadline']!=NULL && !strcmp($hditem['item_deadline'],"0000-00-00 00:00:00")==0
								&& !strcmp($hditem['item_deadline'],"N/A")==0) {
			echo $deadline_date->format( $df );
		} else {
			 echo "N/A";
		}
		?>" class="text" disabled="disabled" size="15">
     	    <a href="#"<?php
		if($item_id && $hditem['item_deadline']!=NULL && !strcmp($hditem['item_deadline'],"0000-00-00 00:00:00")==0
								&& !strcmp($hditem['item_deadline'],"N/A")==0) {
			 echo "onClick=\"popCalendar(0,'deadline','".$deadline_date->format( FMT_DATETIME_MYSQL)."')\"";
		 } else {
			 echo "onClick=\"popCalendar(1,'deadline','')\"";
		 }
		?>
            ><img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar'); ?>" border="0" /></a>
     	    &nbsp;&nbsp;&nbsp;<a href="#" title="<?php echo $AppUI->_('Clear Deadline'); ?>" onClick='document.frmHelpDeskItem.deadline.value = "N/A"; document.frmHelpDeskItem.item_deadline.value = "N/A";' ><font color="red"><?php echo $AppUI->_('Clear'); ?></font></a>
      </td>
																      
    </tr>
    </table>
  </td>
</tr>

<tr><td colspan="2">
<table cellspacing="0" cellpadding="0" border="0">
<tr>
  <td align="left"><font color="red"><label for="summary">* <?php echo $AppUI->_('Summary'); ?>:</label></font>
  </td>
  <td>&nbsp;&nbsp;</td>
  <td><label for="watchers"><?php echo $AppUI->_('Watchers'); ?>:</label></td>
</tr>

<tr>
  <td valign="top">
    <textarea id="summary" cols="75" rows="12" class="textarea"
              name="item_summary"><?php echo @$hditem["item_summary"]; ?></textarea>
  </td>
  <td>&nbsp;&nbsp;</td>
      <td>
      <select name="watchers_select" size="12" id="watchers_select" multiple="multiple" 
      <?php if($is_client) echo "disabled class=disabledText";
            else echo "class=text";			
      ?>
      >
      <?php
	      foreach($users as $id => $name){
		echo "<option value=\"{$id}\"";
    // Two situations -- KZHAO
		if($item_id && array_key_exists($id,$watchers))
			echo " selected";
    elseif(!$item_id && $watchers && in_array($id, $watchers))
      echo " selected";
		echo ">{$name}</option>";
	      }
      ?></select>
      <input type="hidden" name="watchers" value="" /></td>
</tr></table>
</td></tr>

<!--commented by KZHAO 7-20-2006
    code dealing with hours worked and cost code
-->

<tr>
  <td colspan="2">
  <br />
  <small>
    <font color="red">* <?php echo $AppUI->_('Required fields'); ?></font><br />
    &dagger; <?php echo $AppUI->_('Automatically set and locked if picked from Users or Contacts, clear requestor field to edit.'); ?>
  </small>
  <br /><br />
  </td>
</tr>

<tr>
  <td><input type="button" value="<?php echo $AppUI->_('back'); ?>" class="button" onClick="javascript:history.back(-1);" />
  </td>
  <td align="right"><input type="button" value="<?php echo $AppUI->_('submit'); ?>" class="button" onClick='submitIt()' >
  </td>
</tr>

</form>
</table>

<p>&nbsp;</p>
<?php 
  /* If we have a company stored, pre-select it.
     If we have a project but not a company (version <0.2) do a reverse
     lookup.
     Else, select nothing */
  if (@$hditem['item_company_id']) {
    $target = $hditem['item_company_id'];
  } else if (@$hditem['item_project_id']) {
    $target = $reverse[$hditem['item_project_id']];
  } else {
    $target = 0;
  }

  /* Select the project from the list */
  $select = @$hditem['item_project_id'] ? $hditem['item_project_id'] : 0;
?>

<script language="javascript">
selectList('item_company_id',<?php echo $target?>);
changeList('item_project_id', projects, <?php echo $target?>);
selectList('item_project_id',<?php echo $select?>);
</script>
</html>
