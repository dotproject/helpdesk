<?php /* HELPDESK $Id: addedit.php,v 1.27 2004/04/22 17:04:03 agorski Exp $ */
$item_id = dPgetParam($_GET, 'item_id', 0);

// Pull data

$sql = "SELECT *
        FROM helpdesk_items
        WHERE item_id = '$item_id'";

db_loadHash( $sql, $hditem );

if(!@$hditem["item_assigned_to"]){
  @$hditem["item_assigned_to"] = $AppUI->user_id;
  @$hditem["item_status"] = 1;
}
$sql = "SELECT user_id, CONCAT(user_first_name, ' ', user_last_name)
        FROM users
        ORDER BY user_first_name";

$users = arrayMerge( array( 0 => '' ), db_loadHashList( $sql ) );

$sql = "SELECT project_id, project_name, company_name, company_id
        FROM projects
        LEFT JOIN companies ON company_id = projects.project_company
        ORDER BY project_name";
$company_project_list = db_loadList( $sql );

/* Build array of company/projects for output to javascript
   Adding slashes in case special characters exist */
foreach($company_project_list as $row){
  $projects[] = "[{$row['company_id']},{$row['project_id']},'"
              . addslashes($row['project_name'])
              . "']";
}

$sql = "SELECT company_id, company_name
        FROM companies
        ORDER BY company_name";

$companies = arrayMerge( array( 0 => '' ), db_loadHashList( $sql ) );

// Setup the title block
$ttl = $item_id ? "Editing Item #$item_id" : "Adding New Item";

$titleBlock = new CTitleBlock( $ttl, 'helpdesk.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=helpdesk", "Home" );
$titleBlock->addCrumb( "?m=helpdesk&a=list", "List" );

if ($item_id) {
  $titleBlock->addCrumb( "?m=helpdesk&a=view&item_id=$item_id", "View this item" );
}

$titleBlock->show();

if ($item_id) { 
  $tsc = db_dateTime2unix( $hditem["item_created"] );
} else {
  $hditem["item_created"] = db_unix2dateTime(time());
}

$tc = isset($tsc) ? date( "m/d/y g:i a", $tsc ) : null;

?>

<script language="javascript">
function submitIt() {
  var f   = document.frmHelpDeskItem;
  var msg = 'You must enter the following value(s):';

  if ( f.item_title.value.length < 3 ) {
    msg += "\nTitle";
    f.item_title.focus();
  }

  if( f.item_requestor.value.length < 3 ) {
    msg += "\nRequestor";
    f.item_requestor.focus();
  }

  if( f.item_summary.value.length < 3 ) {
    msg += "\nSummary";
    f.item_summary.focus();
  }

  if( msg.length < 39 ) {
    f.submit();
  }

  else {
    alert( msg );
  }
} 

function popUserDialog() {
  window.open('./index.php?m=helpdesk&a=selector&callback=setUserRequestor&table=users&dialog=1', 'selector', 'left=50,top=50,height=250,width=400,resizable,scrollbars=yes')
}

function popContactDialog() {
  window.open('./index.php?m=helpdesk&a=selector&callback=setContactRequestor&table=contacts&dialog=1', 'selector', 'left=50,top=50,height=250,width=400,resizable,scrollbars=yes')
}

var oldRequestor = '';

// Callback function for the generic selector
function setRequestor( key, val ) {
  var f = document.frmHelpDeskItem;

  if (val != '') {
    f.item_requestor_id.value = key;
    f.item_requestor.value = val;
    oldRequestor = val;

    // Since we probably chose someone else, wipe the e-mail and phone fields
    f.item_requestor_email.value = '';
    f.item_requestor_phone.value = '';
  }
}

function setUserRequestor( key, val ) {
  var f = document.frmHelpDeskItem;

  if (val != '') {
    setRequestor( key, val );
    f.item_requestor_type.value = 1
  }
}

function setContactRequestor( key, val ) {
  var f = document.frmHelpDeskItem;

  if (val != '') {
    setRequestor( key, val );
    f.item_requestor_type.value = 2
  }
}

function updateStatus(obj){
  var f = document.frmHelpDeskItem;

  if(obj.options[obj.selectedIndex].value>0){
    f.item_status.selectedIndex=1;
  }
}

<?php 
$ua = $_SERVER['HTTP_USER_AGENT'];
$isMoz = strpos( $ua, 'Gecko' ) !== false;

print "\nvar projects = new Array(".implode( ",\n", $projects ).")"; 
?>

// Dynamic project list handling functions

function emptyList( list ) {
<?php if ($isMoz) { ?>
  list.options.length = 0;
<?php } else { ?>
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
  var list = eval( 'f.'+listName );
  
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

<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
  <form name="frmHelpDeskItem" action="?m=helpdesk" method="post">
  <input type="hidden" name="dosql" value="do_item_aed" />
  <input name="del" type="hidden" value="0" />
  <input type="hidden" name="item_id" value="<?=$item_id?>" />
  <input type="hidden" name="item_requestor_id" value="<?=@$hditem["item_requestor_id"]?>" />
  <input type="hidden" name="item_requestor_type" value="<?=$item_requestor_type?>" />
  <input type="hidden" name="item_created" value="<?=@$hditem["item_created"]?>" />
  <tr>
  <td valign="top" width="50%">
    <table cellspacing="0" cellpadding="2" border="0">
    <?php if ($item_id) { ?>
    <tr>
      <td align="right" nowrap><?=$AppUI->_('Date Opened')?>:</td>
      <td width="100%"><strong><?=$tc?></strong></td>
    </tr>
    <?php } ?>
    <tr>
      <td align="right"><font color="red"><?=$AppUI->_('* Title')?>:</font></td>
      <td valign="top"><input type="text" class="text" id="large" name="item_title"
                              value="<?=@$hditem["item_title"]?>" maxlength="64" /></td>
    </tr>

    <tr>
      <td align="right" nowrap><font color="red">* <?=$AppUI->_('Requestor Name');?>:</font></td>
      <td valign="top" nowrap>
        <input type="text" class="text" id="large" name="item_requestor"
        value="<?=@$hditem["item_requestor"]?>" maxlength="64"
        onChange="if (this.value!=oldRequestor) {
                    document.frmHelpDeskItem.item_requestor_id.value = 0;
                    oldRequestor = this.value;
                  }" />
      <input type="button" class="button" value="Users..." onclick="popUserDialog();" />
      <input type="button" class="button" value="Contacts..." onclick="popContactDialog();" />
      </td>
    </tr>

    <tr>
      <td align="right" nowrap>&dagger; <?=$AppUI->_('Requestor E-mail');?>:</td>
      <td valign="top"><input type="text" class="text" id="large"
                              name="item_requestor_email"
                              value="<?=@$hditem["item_requestor_email"]?>"
                              maxlength="64" /></td>
    </tr>

    <tr>
      <td align="right" nowrap>&dagger; <?=$AppUI->_('Requestor Phone');?>:</td>
      <td valign="top"><input type="text" class="text" id="large"
                              name="item_requestor_phone"
                              value="<?=@$hditem["item_requestor_phone"]?>"
                              maxlength="30" /></td>
    </tr>

    <tr>
      <td align="right" nowrap><?=$AppUI->_('Assigned To')?>:</td>
      <td><?=arraySelect( $users, 'item_assigned_to', 'size="1" class="text" id="medium" onchange="updateStatus(this)"',
                          @$hditem["item_assigned_to"] )?>
        <input type="checkbox" name="notify" value="1" checked />
        <?=$AppUI->_( 'Notify by e-mail' );?></td>
    </tr>

    <tr>
      <td align="right"><?=$AppUI->_('Company')?>:</td>
      <td><?=arraySelect( $companies, 'item_company_id', 'size="1" class="text" id="large" onchange="changeList(\'item_project_id\',projects, this.options[this.selectedIndex].value)"',
                          @$hditem["item_company_id"] )?></td>
    </tr>

    <tr>
      <td align="right"><?=$AppUI->_('Project')?>:</td>
      <td><select name="item_project_id" size="1" class="text" id="large"></select></td>
    </tr>

    <?php /* Do we want to use this?
    <tr>
      <td align="right"><?=$AppUI->_('Reference')?>:</td>
      <td valign="top">
        <input type="text" class="text" name="item_assetno" value="<?=@$hditem["item_assetno"]?>" size="40" maxlength="24" />
      </td>
      <td align="left"></td>
    </tr>

    */ ?>
    </table>
  </td>
  <td valign="top" width="50%">
    <table cellspacing="0" cellpadding="2" border="0">
    <tr>
      <td align="right" nowrap><?=$AppUI->_('Call Type')?>:</td>
      <td><?=arraySelect( $ict, 'item_calltype', 'size="1" class="text" id="medium"',
                          @$hditem["item_calltype"] )?></td>
    </tr>

    <tr>
      <td align="right" nowrap><?=$AppUI->_('Call Source')?>:</td>
      <td><?=arraySelect( $ics, 'item_source', 'size="1" class="text" id="medium"',
                          @$hditem["item_source"] )?></td>
    </tr>

    <tr>
      <td align="right"><?=$AppUI->_('Status')?>:</td>
      <td><?=arraySelect( $ist, 'item_status', 'size="1" class="text" id="medium"',
                          @$hditem["item_status"] )?></td>
    </tr>

    <tr>
      <td align="right"><?=$AppUI->_('Priority')?>:</td>
      <td><?=arraySelect( $ipr, 'item_priority', 'size="1" class="text" id="medium"',
                          @$hditem["item_priority"] )?></td>
    </tr>

    <tr>
      <td align="right"><?=$AppUI->_('Severity')?>:</td>
      <td><?=arraySelect( $isv, 'item_severity', 'size="1" class="text" id="medium"',
                          @$hditem["item_severity"] )?></td>
    </tr>

    <tr>
      <td align="right" nowrap><?=$AppUI->_('Operating System')?>:</td>
      <td><?=arraySelect( $ios, 'item_os', 'size="1" class="text" id="medium"',
                          @$hditem["item_os"] )?></td>
    </tr>

    <tr>
      <td align="right"><?=$AppUI->_('Application')?>:</td>
      <td><?=arraySelect( $iap, 'item_application', 'size="1" class="text" id="medium"',
                          @$hditem["item_applic"] )?></td>
    </tr>
    </table>
  </td>
</tr>

<tr>
  <td align="left"><br><font color="red">* <?=$AppUI->_('Summary')?>:</font></td>
  <td>&nbsp; </td>
</tr>

<tr>
  <td colspan="2" align="left">
    <textarea cols="90" rows="15" class="textarea"
              name="item_summary"><?=@$hditem["item_summary"]?></textarea>
  </td>
</tr>

<tr>
  <td colspan="2">
  <br>
  <small>
    <font color="red">* Required field</font><br>
    &dagger; If you select your name from the popup window, your e-mail
    address and phone number will be populated from your account details
    (if available).
  </small>
  <br><br>
  </td>
</tr>

<tr>
  <td><input type="button" value="<?=$AppUI->_('Back')?>" class="button" onClick="javascript:history.back(-1);" /></td>
  <td align="right"><input type="button" value="<?=$AppUI->_('Submit')?>" class="button" onClick="submitIt()" /></td>
</tr>

</form>
</table>

<p>&nbsp;</p>
<script language="javascript">
changeList('item_project_id', projects, <?php echo @$hditem['item_company_id'] ? $hditem['item_company_id'] : 0;?>);
selectList( 'item_project_id', <?php echo @$hditem['item_project_id'] ? $hditem['item_project_id'] : 0;?> );
</script>
