<?php /* HELPDESK $Id: addedit.php,v 1.13 2004/04/19 18:24:12 adam Exp $ */
$AppUI->savePlace();

$item_id = dPgetParam($_GET, 'item_id', 0);

// Pull data

$sql = "SELECT *
        FROM helpdesk_items
        WHERE item_id = '$item_id'";

db_loadHash( $sql, $hditem );

$tsm = $rightNow = time();
$hditem["item_modified"] = db_unix2dateTime( $rightNow );

if ($item_id) { 
  $tsc = db_dateTime2unix( $hditem["item_created"] );
} else {
  $tsc = $rightNow;
  $hditem["item_created"] = db_unix2dateTime( $rightNow );
}

$tc = $tsc < 0 ? null : date( "m/d/y g:i a", $tsc );
$tm = $tsm < 0 ? null : date( "m/d/y g:i a", $tsm );

$sql = "SELECT user_id, CONCAT(user_first_name, ' ', user_last_name)
        FROM users
        ORDER BY user_first_name";

$users = arrayMerge( array( 0 => '' ), db_loadHashList( $sql ) );

$sql = "SELECT project_id, CONCAT(companies.company_name, ': ', project_name) as proj 
        FROM projects
        LEFT JOIN companies ON company_id = projects.project_company
        ORDER BY proj";

$projects = arrayMerge( array( 0 => '' ), db_loadHashList( $sql ) );

// Setup the title block
$ttl = $item_id ? "Editing Item #$item_id" : "Adding New Item";

$titleBlock = new CTitleBlock( $ttl, 'helpdesk.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=helpdesk", "Home" );
$titleBlock->addCrumb( "?m=helpdesk&a=list", "List" );

if ($item_id) {
  $titleBlock->addCrumb( "?m=helpdesk&a=view&item_id=$item_id", "View this item" );
}

$titleBlock->show();

?>

<script language="javascript">
function submitIt() {
  var f   = document.frmHelpDeskItem;
  var msg = 'You must enter the following value(s):';

  if ( f.item_title.value.length < 3 ) {
    msg += "\nSubject";
    f.item_title.focus();
  }

  if( f.item_requestor.value.length < 3 ) {
    msg += "\nYour Name";
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

function popDialog() {
	window.open('./index.php?m=public&a=selector&callback=setRequestor&table=users&dialog=1', 'selector', 'left="50",top="50",height="250",width="400",resizable')
}

var oldRequestor = '';

// Callback function for the generic selector
function setRequestor( key, val ) {
	var f = document.frmHelpDeskItem;

	if (val != '') {
		f.item_requestor_id.value = key;
		f.item_requestor.value = val;
		oldRequestor = val;
	}
}
</script>

<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
  <form name="frmHelpDeskItem" action="?m=helpdesk" method="post">
	<input type="hidden" name="dosql" value="do_item_aed" />
	<input name="del" type="hidden" value="0" />
	<input type="hidden" name="item_id" value="<?=$item_id?>" />
	<input type="hidden" name="item_requestor_id" value="<?=@$hditem["item_requestor_id"]?>" />
  <input type="hidden" name="item_created" value="<?=@$hditem["item_created"]?>" />
  <input type="hidden" name="item_modified" value="<?=@$hditem["item_modified"]?>" />
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
			<td align="right"><font color="red"><?=$AppUI->_('* Subject')?>:</font></td>
			<td valign="top"><input type="text" class="text" id="large" name="item_title"
                              value="<?=@$hditem["item_title"]?>" maxlength="64" /></td>
		</tr>

		<tr>
			<td align="right" nowrap><font color="red">* <?=$AppUI->_('Your Name');?>:</font></td>
			<td valign="top" nowrap>
				<input type="text" class="text" id="large" name="item_requestor"
        value="<?=@$hditem["item_requestor"]?>" maxlength="64"
        onChange="if (this.value!=oldRequestor) {
                    document.frmHelpDeskItem.item_requestor_id.value = 0;
                    oldRequestor = this.value;
                  }" />
			<input type="button" class="button" value="..." onclick="popDialog();" />
			</td>
		</tr>

		<tr>
			<td align="right" nowrap>&dagger; <?=$AppUI->_('Your E-mail');?>:</td>
			<td valign="top"><input type="text" class="text" id="large"
                              name="item_requestor_email"
                              value="<?=@$hditem["item_requestor_email"]?>"
                              maxlength="64" /></td>
		</tr>

		<tr>
			<td align="right" nowrap><?=$AppUI->_('Assigned To')?>:</td>
			<td><?=arraySelect( $users, 'item_assigned_to', 'size="1" class="text" id="medium"',
                          @$hditem["item_assigned_to"] )?></td>
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
			<td align="right" nowrap><?=$AppUI->_('Operating System')?>:</td>
			<td><?=arraySelect( $ios, 'item_os', 'size="1" class="text" id="medium"',
                          @$hditem["item_os"] )?></td>
		</tr>

		<tr>
			<td align="right"><?=$AppUI->_('Application')?>:</td>
			<td><?=arraySelect( $iap, 'item_application', 'size="1" class="text" id="medium"',
                          @$hditem["item_applic"] )?></td>
		</tr>

		<tr>
			<td align="right"><?=$AppUI->_('Severity')?>:</td>
			<td><?=arraySelect( $isv, 'item_severity', 'size="1" class="text" id="medium"',
                          @$hditem["item_severity"] )?></td>
		</tr>

    <tr>
      <td align="right"><?=$AppUI->_('Project')?>:</td>
      <td><?=arraySelect( $projects, 'item_project_id', 'size="1" class="text" id="large"',
                          @$hditem["item_project_id"] )?></td>
    </tr>

    <tr>
      <td>&nbsp;</td>
      <td>
        <input type="checkbox" name="notify" value="1" checked />
        <?=$AppUI->_( 'Notify assignee by e-mail' );?>
      </td>
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
    &dagger; If you select your name from the popup window, you do not need 
    to enter an e-mail address.
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
