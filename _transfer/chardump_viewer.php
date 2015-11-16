<?php
session_start();
$ID = $_SESSION['id'];
include_once("t_dbfunctions.php");
include_once("t_functions.php");
include_once("t_config.php");
include_once("f_switch.php");
require_once("item_list.php");
require_once("definitions.php");
?>
<link rel="stylesheet" href="../template/libs/css/bootstrap.min.css">
<link rel="stylesheet" href="../template/libs/css/bootstrap-theme.min.css">
<script src="../template/libs/js/bootstrap.min.js"></script>
<div class="container">
  <?php
  if (isset($_POST["PortingType"]))
  {
  ?><script type="text/javascript" src="http://cdn.openwow.com/api/tooltip.js"></script><?php
    
    if (isset($_POST['chardump']))
      $buffer = $_POST['chardump'];
    else
    {
      $file = $_FILES['file']['tmp_name'];
      $fileopen = fopen($file, 'r');
      $buffer = '';
      $reason = '';

      while (!feof($fileopen)) {
        $buffer2 = fgets($fileopen);
        $buffer .= $buffer2;
      }

      fclose($fileopen);
      unlink($file);
    }

    $part = explode('"', $buffer);
    if (isset($part[1])) {
      $DUMP = $part[1];      
      $arrDump=parse_ini_string ( $buffer  );

      $VER = isset($arrDump["CHDMP_VER"]) ? $arrDump["CHDMP_VER"] : "<335.700";
      if ($VER!=ADDON_VER)
        echo "<h2 class=\"text-warning\">!!ATTENZIONE!!</h2>
        <p class=\"text-danger\">La versione dell'addon con cui è stato estratto questo chardump è obsoleta: $VER <br> La nuova versione è la: ".ADDON_VER."<br><br>Potresti avere problemi al termine del porting!.<br><br></p>";

      $REALM_NAME = REALM_NAME;
      $DECODED_DUMP = _DECRYPT($DUMP);
      $CHAR_REALM = GetRealmID($AccountDBHost, $DBUser, $DBPassword, $AccountDB, $REALM_NAME);
      $json = json_decode(stripslashes($DECODED_DUMP), true);
      $CHAR_NAME = mb_convert_case(mb_strtolower($json['uinf']['name'], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
      $CharLevel = _MaxValue($json['uinf']['level'], $MaxCL);
      $O_REALMLIST = $json['ginf']['realmlist'];
      $O_REALM = $json['ginf']['realm'];
      $pType = $_POST["PortingType"];


      $AchievementsCount = 0;
      $ACHMINTime = 0;
      $ACHMAXTime = 0;
      foreach ($json['achiev'] as $key => $value) {
        if ($ACHMINTime == 0)
          $ACHMINTime = $value['D'];
        if ($ACHMINTime > $value['D'])
          $ACHMINTime = $value['D'];
        if ($ACHMAXTime < $value['D'])
          $ACHMAXTime = $value['D'];
        ++$AchievementsCount;
      }
      if (CheckGameBuild($json['ginf']['clientbuild'], $GAMEBUILD)) {
        $reason = _RT($write[50] . " " . $GAMEBUILD);
      } else if (((10 + $CharLevel > $AchievementsCount) || ($AchievementsCount > $AchievementsMinCount)) && $AchievementsCheck == 1)       {
        $reason = _RT("Seems bad characters, not enought achievements!");
      }/*
      else if (CHECKDAY($ACHMAXTime, $ACHMINTime) < $PLAYTIME) {
        $reason = _RT("Small playtime!");
      }/*
    else if (_CheckBlackList($AccountDBHost, $DBUser, $DBPassword, $AccountDB, $O_REALMLIST, $O_REALM, $o_URL)) {
      $reason = _RT($write[57]." [ realm: ".(empty($O_REALMLIST) ? "No realmlist" : $O_REALMLIST)." --- ".(empty($O_REALM) ? "No realmn name" : $O_REALM)." ]");
    }
*/

      $GUID = CheckCharacterGuid($AccountDBHost, $DBUser, $DBPassword, $AccountDB, $CHAR_REALM, GetCharacterGuid(_HostDBSwitch($CHAR_REALM), $DBUser, $DBPassword, _CharacterDBSwitch($CHAR_REALM)));

    } else if (!isset($part[1]))
      $reason = _RT($write[51]);

    if (!empty($reason)) {
      viewerForm($reason);
    } else {
      $char_money = _MaxValue($json['uinf']['money'], $MaxMoney);
      $char_speccount = $json['uinf']['specs'];
      $char_gender = $json['uinf']['gender'] - 2 == 1 ? 1 : 0;
      $char_totalkills = $json['uinf']['kills'];
      $char_arenapoints = _MaxValue($json['uinf']['arenapoints'], $MaxAP);
      $char_honorpoints = _MaxValue($json['uinf']['honor'], $MaxHP);
      $INVrow = "";
      $downgrade = "<b class=\"text-warning\">Downgrade:</b> <br>";
      $GEMrow = "";
      $CURrow = "";

      if (_CheckCharacterName(_HostDBSwitch($CHAR_REALM), $DBUser, $DBPassword, _CharacterDBSwitch($CHAR_REALM), $CHAR_NAME) > 0) {
        // UN AVVISO (character con lo stesso nome)
      }

      /* Server Realm */
      echo '<h3 style="display:inline;" class="text-success">Server info</h3><br>';
      echo "<b class=\"text-info\">Realm:</b> ".$json['ginf']['realm']."<br>";
      echo "<b class=\"text-info\">realmlist:</b> ".$json['ginf']['realmlist']."<br>";
      echo "<b class=\"text-info\">locale:</b> ".$json['ginf']['locale']."<br>";
      echo "<b class=\"text-info\">clientbuild:</b>".$json['ginf']['clientbuild']."<br><br>";

      /* CHARACTER */
      /* apply some filters */
      if ($json['uinf']['honor']> $MaxHP) $json['uinf']['honor'] = $MaxHP;
      if ($json['uinf']['arenapoints']> $MaxAP) $json['uinf']['arenapoints'] = $MaxAP;
      if ($json['uinf']['level']> $MaxCL) $json['uinf']['level'] = $MaxCL;

      echo '<b class="text-warning">Character</b><br>';
      echo "<b class=\"text-info\">Name:</b> ".$json['uinf']['name']."<br>";
      echo "<b class=\"text-info\">Level:</b> ".$json['uinf']['level']."<br>";
      echo "<b class=\"text-info\">Race:</b> ".$json['uinf']['race']."<br>";
      echo "<b class=\"text-info\">Class:</b> ".$json['uinf']['class']."<br>";
      echo "<b class=\"text-info\">ArenaPoints:</b> ".$json['uinf']['arenapoints']."<br>";
      echo "<b class=\"text-info\">Honor:</b> ".$json['uinf']['honor']."<br>";
      echo "<b class=\"text-info\">Kills:</b> ".$json['uinf']['kills']."<br>";

      /* MONEY */
      $money = $json['uinf']['money'];
      if ($money > $MaxMoney)
        $money = $MaxMoney;
      if ($money > 9999)
        $money = substr($money, 0, -4) . " <span style=\"color: yellow;\">gold</span> " . substr($money, -4, 2) . " <span style=\"color: grey;\">silver</span> " . substr($money, -2) . " <span class=\"text-danger\">copper</span>";
      elseif ($money > 99)
        $money = "00 <span style=\"color: yellow;\">gold</span> " . substr($money, -4, 2) . " <span style=\"color: grey;\">silver</span> " . substr($money, -2) . " <span class=\"text-danger\">copper</span>";
      else
        $money = "00 <span style=\"color: yellow;\">gold</span> 00 <span style=\"color: grey;\">silver</span> " . substr($money, -2) . " <span class=\"text-danger\">copper</span>";
      echo "<b class=\"text-info\">Money:</b> ".$money."<br>";
      echo "<br><br>";


      /* ACHIEVEMENTS */
      $ach_invalid = "";
      $ach_valid = "";
      foreach ($json['achiev'] as $key => $value)
      {
        $achievement = '<a href="http://wotlk.openwow.com/achievement='.$value['I'].'">'.$value['I'].'</a>';
        $date = $value['D'];
        if (_CheckWrongOrNoAchievement($achievement))
          $ach_invalid .= $achievement." ";
        else
          $ach_valid .= $achievement." ";
      }

      if($ach_invalid != "" and $ach_valid != "")
      {
        echo "<br><b class=\"text-warning\">Achievements</b><br>";
        echo "$ach_valid <br>";
        if ($ach_invalid != "")
          echo "$ach_invalid <br><br>";
      }

      /* GLYPHS */
      /* spec 1 */
      $Glyph[0] = $json['glyphs'][0][0][0];
      $Glyph[1] = $json['glyphs'][0][0][1];
      $Glyph[2] = $json['glyphs'][0][0][2];
      $Glyph[3] = $json['glyphs'][0][1][0];
      $Glyph[4] = $json['glyphs'][0][1][1];
      $Glyph[5] = $json['glyphs'][0][1][2];
      echo '<div class="col-xs-4 col-sm-3 col-lg-2">';
      echo '<b class="text-warning">Glyphs</b> <br>';
//    echo '<span class="text-success">first spec</span><br>';
      echo '<b class="text-primary">major</b><br>';
      echo ($Glyph[0] != -1 ? '<a href="http://wotlk.openwow.com/spell='.$Glyph[0].'">'.$Glyph[0].'</a>' : '<span class="text-danger">No glyph</span>') . "<br>";
      echo ($Glyph[1] != -1 ? '<a href="http://wotlk.openwow.com/spell='.$Glyph[1].'">'.$Glyph[1].'</a>' : '<span class="text-danger">No glyph</span>') . "<br>";
      echo ($Glyph[2] != -1 ? '<a href="http://wotlk.openwow.com/spell='.$Glyph[2].'">'.$Glyph[2].'</a>' : '<span class="text-danger">No glyph</span>') . "<br>";
      echo '<b class="text-primary">minor</b><br>';
      echo ($Glyph[3] != -1 ? '<a href="http://wotlk.openwow.com/spell='.$Glyph[3].'">'.$Glyph[3].'</a>' : '<span class="text-danger">No glyph</span>') . "<br>";
      echo ($Glyph[4] != -1 ? '<a href="http://wotlk.openwow.com/spell='.$Glyph[4].'">'.$Glyph[4].'</a>' : '<span class="text-danger">No glyph</span>') . "<br>";
      echo ($Glyph[5] != -1 ? '<a href="http://wotlk.openwow.com/spell='.$Glyph[5].'">'.$Glyph[5].'</a>' : '<span class="text-danger">No glyph</span>') . "<br>";
      echo "</div>";
      /* spec 2 */
      /*
      if ($char_speccount == 2)
      {
        $Glyph[6] = $json['glyphs'][1][0][0];
        $Glyph[7] = $json['glyphs'][1][0][1];
        $Glyph[8] = $json['glyphs'][1][0][2];
        $Glyph[9] = $json['glyphs'][1][1][0];
        $Glyph[10] = $json['glyphs'][1][1][1];
        $Glyph[11] = $json['glyphs'][1][1][2];
        echo '<div class="col-xs-4 col-sm-3 col-lg-2">';
        echo '<br><span class="text-success">second spec</span><br>';
        echo '<b class="text-primary">major</b><br>';
        echo ($Glyph[6] != -1 ? '<a href="http://wotlk.openwow.com/spell='.$Glyph[6].'">'.$Glyph[6].'</a>' : '<span class="text-danger">No glyph</span>') . "<br>";
        echo ($Glyph[7] != -1 ? '<a href="http://wotlk.openwow.com/spell='.$Glyph[7].'">'.$Glyph[7].'</a>' : '<span class="text-danger">No glyph</span>') . "<br>";
        echo ($Glyph[8] != -1 ? '<a href="http://wotlk.openwow.com/spell='.$Glyph[8].'">'.$Glyph[8].'</a>' : '<span class="text-danger">No glyph</span>') . "<br>";
        echo '<b class="text-primary">minor</b><br>';
        echo ($Glyph[9] != -1 ? '<a href="http://wotlk.openwow.com/spell='.$Glyph[9].'">'.$Glyph[9].'</a>' : '<span class="text-danger">No glyph</span>') . "<br>";
        echo ($Glyph[10] != -1 ? '<a href="http://wotlk.openwow.com/spell='.$Glyph[10].'">'.$Glyph[10].'</a>' : '<span class="text-danger">No glyph</span>') . "<br>";
        echo ($Glyph[11] != -1 ? '<a href="http://wotlk.openwow.com/spell='.$Glyph[11].'">'.$Glyph[11].'</a>' : '<span class="text-danger">No glyph</span>') . "<br>";
        echo "</div>";
      }
      */

      echo '<div class="col-xs-12">';
      /* SKILLS */
      echo "<br><b class=\"text-warning\">Skills</b>";
      foreach ($json['skills'] as $key => $value)
        if ($value['M'] != 0 and $value['M'] != 1 and strpos($value['N'], "Language") === false)
          echo "<br><span class=\"text-success\">".$value['N']."</span> <span class=\"text-info\">".$value['M']." ".$value['C']."</span>";

      /* RECIPES */
      $recipes = "";
      foreach ($json['recipes'] as $SpellID)
        if (_isProfessionSpell($SpellID))
          $recipes .= '<a href="http://wotlk.openwow.com/spell='.$SpellID.'">'.$SpellID.'</a> ';
      
      if ($recipes != "")
        echo "<br><br><b class=\"text-warning\">Recipes<br></b><br>$recipes";

      echo "<br>";

      /* MOUNTS/COMPANIONS */
      $Mounts = "";
      foreach ($json['creature'] as $key => $SpellID)
        $Mounts .= '<a href="http://wotlk.openwow.com/spell='.$SpellID.'">'.$SpellID.'</a> ';

      if ($Mounts != "")
        echo "<br><b class=\"text-warning\">Mounts/Companions<br></b><br>$Mounts";
      

      echo '<div id="items">';
      /* INVENTORY */
      foreach ($json['inventory'] as $key => $value)
      {
        // qui vengono eseguiti tutti i check e i downgrade degli items
        $item = _itemCheck($CHAR_REALM, $value['I'], $pType);
        $count = CheckItemCount($value['C']);
        
        if ($item != $value['I'])
          $downgrade .= '<a href="http://wotlk.openwow.com/item='.$value['I'].'">'.$value['I'].'</a>' . "<span class=\"text-danger\">x" . $count . "</span> => ".'<a href="http://wotlk.openwow.com/item='.$item.'">'.$item.'</a>' . "<span class=\"text-danger\">x" . $count . "</span><br>";
        else
        {
          $INVrow .= '<a href="http://wotlk.openwow.com/item='.$item.'">'.$item.'</a>' . "<span class=\"text-danger\">x" . $count . "</span> ";
          $GEM1 = _GetGemID($value['G1']);
          $GEM2 = _GetGemID($value['G2']);
          $GEM3 = _GetGemID($value['G3']);
          if ($GEM1 > 1)
            $GEMrow .= '<a href="http://wotlk.openwow.com/item='.$GEM1.'">'.$GEM1.'</a><span class="text-danger">x1</span> ';
          if ($GEM2 > 1)
            $GEMrow .= '<a href="http://wotlk.openwow.com/item='.$GEM2.'">'.$GEM2.'</a><span class="text-danger">x1</span> ';
          if ($GEM3 > 1)
            $GEMrow .= '<a href="http://wotlk.openwow.com/item='.$GEM3.'">'.$GEM3.'</a><span class="text-danger">x1</span> ';
        }
      }
      echo "<br><br><b class=\"text-warning\">Inventory</b><br>".$INVrow."<br><br>";
      
      if ($downgrade != "<b class=\"text-warning\">Downgrade:</b> <br>")
        echo "$downgrade<br><br>";
      
      if ($GEMrow != "")
        echo "<b class=\"text-warning\">Gems</b><br>".$GEMrow."<br><br>";

      /* CURRENCY */
      foreach ($json['currency'] as $key => $value)
      {
        $CurrencyID = $value['I'];
        $COUNT = $value['C'];
        if ($COUNT < 1)
          continue;

        // questi dovrebbero essere tutti i token / emblemi del player
        // l'unico filtro che fa è sugli arena / honor points che vengono aggiunti già prima
        if (_CheckCurrency($CurrencyID))
          $CURrow .= '<a href="http://wotlk.openwow.com/item='.$CurrencyID.'">'.$CurrencyID.'</a>' . "<span class=\"text-danger\">x" . $COUNT . "</span> ";
      }

      if ($CURrow != "")
        echo "<b class=\"text-warning\">Currency</b><br>".$CURrow."<br><br>";

      echo '</div>';

      /* REPUTATIONS */
      echo "<b class=\"text-warning\">Reputations</b><br>";
      foreach ($json['rep'] as $key => $value)
        echo ($value['V'] != 0 ? "<i style=\"color: #777;\">".$value['V']."</i> <a href=\"http://wotlk.openwow.com/faction=".$value['V']."\">" . $value['N']."</a><br>" : "");

      echo "<br><br><br></div>";
  ?>

  <?php
    }
  } else {
    viewerForm();
  ?> 
  <form action="" method="POST" enctype="multipart/form-data">
    <h1 class="text-success">Visualizzatore Chardump</h1>
    <div>
      <b class="text-primary">Tipologia di porting: </b><br><input name="PortingType" value="0" required type="radio">
      <b><span class="porting-type text-info">Free</span></b>
      <input name="PortingType" value="1" required type="radio"> <b><span class="porting-type text-success">Basic</span></b>
      <input name="PortingType" value="2" required type="radio"> <b><span class="porting-type text-danger">Full</span></b>
    </div>
    <br>
    <div class="MythInput">
      <input name="file" id="file" accept=".lua" type="file" class="text-warning">
      <?php if (isset($_SESSION['id']) and _CheckGMAccess($AccountDBHost, $DBUser, $DBPassword, $AccountDB, $ID, $AllowedGMLevels)) { ?>
      <br><br>
      <span class="text-warning">Inserisci il contenuto del file chardump.lua</span>
      <textarea rows="5" class="form-control" name="chardump"></textarea>
      <?php } ?>
      <br><br>
      <input name="load" value="Visualizza chardump" type="submit" class="btn btn-success">
    </div>
  </form>
  <?php
  }

  function viewerForm($err="") {
    echo "$err";
  }
  ?>
</div>
