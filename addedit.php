<?php /* HELPDESK $Id: addedit.php,v 1.1.1.1 2004/01/14 23:05:22 root Exp $ */
  #include( "../../misc/debug.php" );

$item_id = isset($_GET['item_id']) ? $_GET['item_id'] : 0;

// pull data
$sql = "
SELECT *
FROM helpdesk_items
WHERE item_id = '$item_id'
";
db_loadHash( $sql, $hditem );

print "<pre><font color=blue>"; print_r( $_GET ); print "</font></pre>\n";
print "<pre><font color=green>"; print_r( $hditem ); print "</font></pre>\n";

//$df = $AppUI->getPref( 'SHDATEFORMAT' );
//$tf = $AppUI->getPref( 'TIMEFORMAT' );

$tsm = $rightNow = time();
$hditem["item_modified"] = db_unix2dateTime( $rightNow );

if( $item_id == 0 ) { 
  $tsc = $rightNow;
  $hditem["item_created"] = db_unix2dateTime( $rightNow );
}
else {
  $tsc = db_dateTime2unix( $hditem["item_created"] );
}

#print "<pre><font color=red>"; print_r( $hditem ); print "</font></pre>\n";
#echo "<pre>{$hditem["item_created"]}\n{$hditem["item_modified"]}\n</pre>";
#echo "<pre>$tsc\n$tsm\n</pre>";

$tc = $tsc < 0 ? null : date( "m/d/y g:i a", $tsc );
$tm = $tsm < 0 ? null : date( "m/d/y g:i a", $tsm );

$sql = "SELECT user_id, CONCAT(user_first_name, ' ', user_last_name) FROM users";
$users = arrayMerge( array( 0 => '' ), db_loadHashList( $sql ) );

// setup the title block
$ttl = $item_id > 0 ? "Edit Item" : "Add Item";
$titleBlock = new CTitleBlock( $ttl, 'helpdesk.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=helpdesk", "home" );
$titleBlock->addCrumb( "?m=helpdesk&a=list", "index" );
$titleBlock->addCrumb( "?m=helpdesk&a=view&item_id=$item_id", "view this item" );
$titleBlock->show();
?>

<script language="javascript">
function submitIt() {
	var f = document.frmHelpDeskItem;
	if (f.item_title.value.length < 3) {
		alert( "Please enter a valid title" );
		f.item_name.focus();
	} else {
		f.submit();
	}
}

function popDialog() {
	var f = document.frmHelpDeskItem;
	window.open('./index.php?m=public&a=selector&callback=setRequestor&table=users&dialog=1', 'selector', 'left=50,top=50,height=250,width=400,resizable')
}

var oldRequestor = '';
// Callback function for the generic selector
function setRequestor( key, val ) {
	var f = document.frmHelpDeskItem;
	if (val != '') {
		f.item_requestor_id.value = key;
		f.item_requestor.value = val;
		oldRequestor = val;
	} else {
		f.permission_item.value = '0';
		f.permission_item_name.value = '';
	}
}
</script>

<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
<form name="frmHelpDeskItem" action="?m=helpdesk" method="post">
	<input type="hidden" name="dosql" value="do_item_aed" />
	<input name="del" type="hidden" value="0" />
	<input type="hidden" name="item_id" value="<?php echo $item_id;?>" />
	<input type="hidden" name="item_requestor_id" value="<?php echo @$hditem["item_requestor_id"];?>" />
  <input type="hidden" name="item_created" value="<?php echo @$hditem["item_created"]; ?>" />
  <input type="hidden" name="item_modified" value="<?php echo @$hditem["item_modified"]; ?>" />
<tr>
	<td valign="top" width="50%">
		<table cellspacing="0" cellpadding="2" border="0">
	<?php if ($item_id) { ?>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Created At');?>:</td>
			<td class="hilite" width="100%"><?php echo $tc;?></td>
		</tr>
	<?php } ?>
		<tr>
			<td align="right"><?php echo $AppUI->_('Title');?>:</td>
			<td valign="top">
				<input type="text" class="text" name="item_title" value="<?php echo @$hditem["item_title"];?>" size="40" maxlength="64" />
			</td>
			<td align="left">*</td>
		</tr>

		<tr>
			<td align="right"><?php echo $AppUI->_('Requestor');?>:</td>
			<td valign="top">
				<input type="text" class="text" name="item_requestor" value="<?php echo @$hditem["item_requestor"];?>" size="40" maxlength="64" onchange="if(this.value!=oldRequestor) {document.frmHelpDeskItem.item_requestor_id.value=0;oldRequestor=this.value}" />
				<input type="button" class="button" value="..." onclick="popDialog();" />
			</td>
			<td align="left">
				 *
			</td>
		</tr>

		<tr>
			<td align="right"><?php echo $AppUI->_('Requestor EMail');?>:</td>
			<td valign="top">
				<input type="text" class="text" name="item_requestor_email" value="<?php echo @$hditem["item_requestor_email"];?>" size="40" maxlength="64" />
			</td>
			<td align="left">
			</td>
		</tr>

		<tr>
			<td align="right"><?php echo $AppUI->_('Assigned to');?>:</td>
			<td>
		<?php
			echo arraySelect( $users, 'item_assigned_to', 'size="1" class="text"', @$hditem["item_assigned_to"] );
		?>
			</td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Status');?>:</td>
			<td>
		<?php
			echo arraySelect( $ist, 'item_status', 'size="1" class="text"', @$hditem["item_status"] );
		?>
			</td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Priority');?>:</td>
			<td>
		<?php
			echo arraySelect( $ipr, 'item_priority', 'size="1" class="text"', @$hditem["item_priority"] );
		?>
			</td>
		</tr>

		<tr>
			<td align="right"><?php echo $AppUI->_('Asset Reference');?>:</td>
			<td valign="top">
				<input type="text" class="text" name="item_assetno" value="<?php echo @$hditem["item_assetno"];?>" size="40" maxlength="24" />
			</td>
			<td align="left"></td>
		</tr>
		</table>
	</td>
	<td valign="top" width="50%">
		<table cellspacing="0" cellpadding="2" border="0">
		<tr>
			<td align="right"><?php echo $AppUI->_('Call Type');?>:</td>
			<td>
		<?php
			echo arraySelect( $ict, 'item_calltype', 'size="1" class="text"', @$hditem["item_calltype"] );
		?>
			</td>
		</tr>

		<tr>
			<td align="right"><?php echo $AppUI->_('Call Source');?>:</td>
			<td>
		<?php
			echo arraySelect( $ics, 'item_source', 'size="1" class="text"', @$hditem["item_source"] );
		?>
			</td>
		</tr>

		<tr>
			<td align="right"><?php echo $AppUI->_('Operating System');?>:</td>
			<td>
		<?php
			echo arraySelect( $ios, 'item_os', 'size="1" class="text"', @$hditem["item_os"] );
		?>
			</td>
		</tr>

		<tr>
			<td align="right"><?php echo $AppUI->_('Application');?>:</td>
			<td>
		<?php
			echo arraySelect( $iap, 'item_application', 'size="1" class="text"', @$hditem["item_applic"] );
		?>
			</td>
		</tr>

		<tr>
			<td align="right"><?php echo $AppUI->_('Severity');?>:</td>
			<td>
		<?php
			echo arraySelect( $isv, 'item_severity', 'size="1" class="text"', @$hditem["item_severity"] );
		?>
			</td>
		</tr>

		</table>
	</td>
</tr>



<tr>
	<td align="left"><?php echo $AppUI->_('Summary');?>:</td>
	<td>&nbsp; </td>
</tr>
<tr>
	<td colspan="2" align="left">
		<textarea cols="60" rows="10" class="textarea" name="item_summary"><?php echo @$hditem["item_summary"];?></textarea>
	</td>
</tr>
<tr>
	<td><input type="button" value="<?php echo $AppUI->_('back');?>" class="button" onClick="javascript:history.back(-1);" /></td>
	<td align="right"><input type="button" value="<?php echo $AppUI->_('submit');?>" class="button" onClick="submitIt()" /></td>
</tr>
</form>
</table>

<p>&nbsp;</p>
