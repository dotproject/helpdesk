<?php /* HELPDESK $Id: vw_idx_handler.php,v 1.3 2004/04/21 18:02:07 bloaterpaste Exp $*/
function vw_idx_handler ($opened) {
  global $m, $ipr, $AppUI;

  $df = $AppUI->getPref( 'SHDATEFORMAT' );
  $tf = $AppUI->getPref( 'TIMEFORMAT' );
  $format = $df." ".$tf;

  /*  Items with with 'closed' status: item_status = 2
   *  Items with with 'opened' status: item_status = 1
   *
   *  unassigned = 0, open = 1, closed = 2, on hold = 3
   */

  $sql = "SELECT hi.*,
          CONCAT(u2.user_first_name,' ',u2.user_last_name) assigned_fullname,
          u2.user_email as assigned_email,
          p.project_id,
          p.project_name,
          p.project_color_identifier
          FROM helpdesk_items hi
          LEFT JOIN users u2 ON u2.user_id = hi.item_assigned_to
          LEFT JOIN projects p ON p.project_id = hi.item_project_id
          WHERE ";

  if ($opened) {
    $sql .= "(TO_DAYS(NOW()) - TO_DAYS(item_created) = 0) ";
    $sql .= "AND item_status = 1";
  } else {
    $sql .= "(TO_DAYS(NOW()) - TO_DAYS(item_resolved) = 0) ";
    $sql .= "AND item_status = 2";
  }

  $sql .= " ORDER BY item_id";

  $items = db_loadList( $sql );

  ?>
  <table cellspacing="1" cellpadding="2" border="0" width="100%" class="tbl">
  <tr>
    <th><?=$AppUI->_('Number')?></th>
    <th><?=$AppUI->_('Requestor')?></th>
    <th><?=$AppUI->_('Title')?></th>
    <th nowrap="nowrap"><?=$AppUI->_('Assigned To')?></th>
    <th><?=$AppUI->_('Priority')?></th>
    <th><?=$AppUI->_('Project')?></th>
    <th nowrap="nowrap">
    <?php
      if ($opened) {
        print $AppUI->_('Opened On');
      } else {
        print $AppUI->_('Closed On');
      }
    ?></th>
  </tr>
  <?php
  foreach ($items as $row) {
    /* We need to check if the user who requested the item is still in the
       system. Just because we have a requestor id does not mean we'll be
       able to retrieve a full name */

    if ($opened) {
      if ($row["item_created"]) {
        $created = new CDate( $row["item_created"] );
        $tc = $created->format( $format );
      }
    } else {
      if($row["item_resolved"]){
        $resolved = new CDate( $row["item_resolved"] );
        $tc = $resolved->format( $format );
      }
    }
    ?>
    <tr>
      <td><b><a href="?m=helpdesk&a=view&item_id=<?=$row['item_id']?>"><?=$row['item_id']?></a></b>
          <?=dPshowImage (dPfindImage( 'ct'.$row["item_calltype"].'.png', $m ), 15, 17, '')?></td>
      <td nowrap=\"nowrap\">
      <?php
      if ($row["item_requestor_email"]) {
        print "<a href=\"mailto: ".$row["item_requestor_email"]."\">".$row['item_requestor']."</a>";
      } else {
        print $row['item_requestor'];
      }
      ?>
      </td>
      <td width="80%"><a href="?m=helpdesk&a=view&item_id=<?=$row['item_id']?>"><?=$row['item_title']?></a></td>
      <td nowrap="nowrap">
      <?php
      if ($row['assigned_email']) {
        print "<a href='mailto:{$row['assigned_email']}'>{$row['assigned_fullname']}</a>";
      } else {
        print $row['assigned_fullname'];
      }
      ?>
      </td>
      <td align="center" nowrap><?=$ipr[@$row["item_priority"]]?></td>
      <td align="center" style="background-color: #<?=$row['project_color_identifier']?>;" nowrap>
      <a href="./index.php?m=projects&a=view&project_id=<?=$row['project_id']?>"><?=$row['project_name']?></a>
      </td>
      <td nowrap="nowrap"><?php print ($tc ? $tc : '-'); ?></td>
    </tr>
  <?php } ?>
  </table>
<?php
}
