<?php /* HELPDESK $Id: vw_idx_closed.php,v 1.7 2004/04/21 00:54:16 bloaterpaste Exp $*/
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
          CONCAT(u1.user_first_name,' ',u1.user_last_name) user_fullname,
          CONCAT(u2.user_first_name,' ',u2.user_last_name) assigned_fullname,
          u1.user_email,
          u2.user_email as assigned_email,
          p.project_id,
          p.project_name,
          p.project_color_identifier
          FROM helpdesk_items hi
          LEFT JOIN users u1 ON u1.user_id = hi.item_requestor_id
          LEFT JOIN users u2 ON u2.user_id = hi.item_assigned_to
          LEFT JOIN projects p ON p.project_id = hi.item_project_id
          WHERE (TO_DAYS(NOW()) - TO_DAYS(item_created) = 0) ";

  if ($opened) {
    $sql .= "AND item_status = 1";
  } else {
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
    if ($row["item_requestor_id"]) {
      $name = $row["user_fullname"] ? $row["user_fullname"] : $row["item_requestor"];
    } else {
      $name = $row['item_requestor'];
    }

    $email = $row["user_email"] ? $row["user_email"] : $row["item_requestor_email"];

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
      <td><a href="?m=helpdesk&a=view&item_id=<?=$row['item_id']?>"><?=$row['item_id']?></a>
          <?=dPshowImage (dPfindImage( 'ct'.$row["item_calltype"].'.png', $m ), 15, 17, '')?>
      </td>
      <td nowrap=\"nowrap\">
      <?php
      if ($email) {
        print "<a href=\"mailto: $email\">$name</a>";
      } else {
        print $name;
      }
      ?>
      </td>
      <td width="80%"><?=$row['item_title']?></td>
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
