<?php
/*
    This file is part of STFC.
    Copyright 2006-2007 by Michael Krauss (info@stfc2.de) and Tobias Gafner

    STFC is based on STGC,
    Copyright 2003-2007 by Florian Brede (florian_brede@hotmail.com) and Philipp Schmidt

    STFC is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    STFC is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include('include/libs/moves.php');



$tc_views_map = array(
    0 => 'galaxy',
    1 => 'quadrant_id=%u',
    2 => 'sector_id=%u',
    3 => 'system_id=%s',
    4 => 'planet_id=%s'
);


if(!empty($_POST['claimvalue'])){
    
    $system_id = decode_system_id($_POST['claimvalue']);
    
    $sql = 'SELECT system_id, system_n_planets, system_global_x, system_global_y, system_closed, system_owner FROM starsystems WHERE system_id = '.$system_id;

    if(($res = $db->queryrow($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query starsystem data');
    }
    
    if($res['system_n_planets'] < 3) {
        message(NOTICE, 'Non puoi chiedere la chiusura di un sistema con meno di tre pianeti');
    }
    
    if($res['system_id'] != $system_id) {
        message(DATABASE_ERROR, 'Could not query starsystem data');
    }
    
    if($res['system_closed']) {
        message(DATABASE_ERROR, 'Non puoi chiedere la chiusura di un sistema chiuso');
    }
    
    if($res['system_owner'] != 0) {
        message(DATABASE_ERROR, 'Non puoi chiedere la chiusura di un sistema chiuso');
    }
    
    $sql = 'SELECT planet_points FROM planets WHERE planet_owner = '.$game->player['user_id'].' AND system_id = '.$system_id;
    
    if(($res2 = $db->queryrowset($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query system data');
    }

    $planet_owned_cnt = $planet_owned_pts = $near_system_id = 0;
    
    foreach ($res2 as $planet) {
        $planet_owned_cnt++;
        $planet_owned_pts += $planet['planet_points'];
    }
    
    if($planet_owned_pts < 3*320) {
        message(NOTICE, 'Non puoi chiedere la chiusura di un sistema se possiedi meno di 960 punti struttura al suo interno');
    }
    
    if(!($planet_owned_cnt >= round($res['system_n_planets']/2))) {
        message(NOTICE, 'Non puoi chiedere la chiusura di un sistema se controlli meno della met&agrave; dei pianeti nel sistema');
    }
    
    if($planet_owned_pts > ($game->player['user_protect_level'] - $game->player['user_points_protected'] )) {
        message(NOTICE, 'Non puoi chiedere la chiusura di un sistema se non hai abbastanza punti protezione disponibili');
    }
    
    $sql = 'SELECT system_id, system_global_x, system_global_y FROM starsystems WHERE system_id <> '.$system_id.' AND system_closed > 0 AND system_owner = '.$game->player['user_id'];

    if(($res3 = $db->queryrowset($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query nearby systems data');
    }
    
    foreach ($res3 as $near_system) {
        $distance = get_distance(array($res['system_global_x'], $res['system_global_y']), array($near_system['system_global_x'],$near_system['system_global_y']));
        if($distance <= CLAIM_SYSTEM_RANGE) {
            $near_system_id = $near_system['system_id'];
            break;
        }
    }
    
    if($near_system_id == 0) {
        message(NOTICE, 'Non puoi chiedere la chiusura di un sistema se non controlli un sistema privato a meno di '.CLAIM_SYSTEM_RANGE.' AU da questo');
    }

    $db->query('INSERT INTO scheduler_claim_system (user_id, system_id, near_system_id, exec_tick) VALUES ('.$game->player['user_id'].','.$res['system_id'].', '.$near_system_id.', '.$ACTUAL_TICK.')');

    message(NOTICE, 'La richiesta di chiusura del sistema &egrave stata inoltrata al Catasto Stellare, attendere qualche minuto e verificare.');
}
if(!empty($_POST['pretendvalue'])) {
    
    $system_id = decode_system_id($_POST['pretendvalue']);
    
    $sql = 'SELECT system_id, system_name, system_global_x, system_global_y, system_closed, system_owner FROM starsystems WHERE system_id = '.$system_id;

    if(($res = $db->queryrow($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query starsystem data');
    }
    
    if($res['system_id'] != $system_id) {
        message(DATABASE_ERROR, 'Could not query starsystem data');
    }

    if($res['system_owner'] == FERENGI_USERID) {
        message(NOTICE, 'Non puoi sfidare la chiusura di un sistema controllato dal Bot Ferengi!!!');
    }

    if($res['system_owner'] == 10) {
        message(NOTICE, 'Non puoi sfidare la chiusura di un sistema controllato dal SysAdmin!!!');
    }

    $sql = 'SELECT user_auth_level FROM user WHERE user_id = '.$res['system_owner'];

    if(($resb = $db->queryrow($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query starsystem data');
    }

    if($resb['user_auth_level'] == 3) {
        message(NOTICE, 'Non puoi sfidare la chiusura di un sistema controllato da questo utente!!!');
    }

    $sql = 'SELECT ud_id FROM user_diplomacy 
            WHERE (accepted = 1 AND user1_id = '.$game->player['user_id'].' AND user2_id = '.$res['system_owner'].') OR 
                  (accepted = 1 AND user2_id = '.$game->player['user_id'].' AND user1_id = '.$res['system_owner'].')';
    
    if(($res1 = $db->queryrow($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query user diplomacy data');
    }
    
    if(!empty($res1['ud_id'])) {
        message(NOTICE, 'Non puoi sfidare la chiusura di un sistema controllato da un tuo alleato!');
    }
    
    $sql = 'SELECT system_id, system_global_x, system_global_y FROM starsystems WHERE system_id <> '.$system_id.' AND system_closed > 0 AND system_owner = '.$game->player['user_id'];

    if(($res2 = $db->queryrowset($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query nearby systems data');
    }    
    
    foreach ($res2 as $near_system) {
        $distance = get_distance(array($res['system_global_x'], $res['system_global_y']), array($near_system['system_global_x'],$near_system['system_global_y']));
        if($distance <= CLAIM_SYSTEM_RANGE) {
            $near_system_id = $near_system['system_id'];
            break;
        }
    }

    if($near_system_id == 0) {
        message(NOTICE, 'Non puoi sfidare la chiusura di un sistema se non controlli un sistema privato a meno di '.CLAIM_SYSTEM_RANGE.' AU da questo');
    }

    $db->query('INSERT INTO starsystems_details (system_id, user_id, timestamp, log_code) VALUES ('.$res['system_id'].', '.$game->player['user_id'].', '.time().', 100)');

    $header = 'Rivendicazione territoriale.';
    
    $message = 'Il giocatore <b>'.$game->player['user_name'].'</b> ha deciso di sfidarti per il controllo di questo sistema: <a href="'.parse_link('a=tactical_cartography&system_id='.encode_system_id($res['system_id'])).'"><b>'.$res['system_name'].'</b>.</a>';
    
    SystemMessage($res['system_owner'], $header, $message);
    
    message(NOTICE, 'Hai notificato con successo la tua pretesa di controllo sul sistema.');
    
}

if (isset($_GET['strade'])) {
    $game->option_store('show_trade',(int)$_GET['strade']);
}

if(isset($_GET['enter_crazy_encode_mode'])) {
    check_auth(STGC_DEVELOPER);

    if(!empty($_GET['system_id'])) {
        redirect('a=tactical_cartography&system_id='.encode_system_id((int)$_GET['system_id']));
    }
    elseif(!empty($_GET['planet_id'])) {
        redirect('a=tactical_cartography&planet_id='.encode_planet_id((int)$_GET['planet_id']));
    }
    else {
        die(constant($game->sprache("TEXT6")));
    }
}


function UnitPrice($unit,$resource, $race=-1)
{
global $db;
global $game;
global $RACE_DATA, $UNIT_NAME, $UNIT_DATA, $MAX_BUILDING_LVL,$NEXT_TICK,$ACTUAL_TICK;

if ($race=-1) $race=$game->player['user_race'];
$price = $UNIT_DATA[$unit][$resource];
$price*= $RACE_DATA[$race][6];
return round($price,0);
}



function set_tcartography_remind($view, $id) {
    global $db, $game;

    if( ($game->player['last_tcartography_view'] != $view) || ($game->player['last_tcartography_id'] != $id) ) {
        $sql = 'UPDATE user
                SET last_tcartography_view = '.$view.',
                    last_tcartography_id = '.$id.'
                WHERE user_id = '.$game->player['user_id'];

        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not update user last tcartography data');
        }
    }

    return true;
}

function display_cartography_jump() {
    global $game, $db;

    $sql = 'SELECT memo_name, memo_view, memo_id
            FROM tc_coords_memo
            WHERE user_id = '.$game->player['user_id'];

    if(!$q_cmemo = $db->query($sql)) {
        message(DATABASE_ERROR, 'Could not query tactical cartography memo data');
    }

    $n_cmemo = $db->num_rows($q_cmemo);

    $game->out('
<br><br>
<table class="style_outer" width="400" align="center" border="0" cellpadding="2" cellspacing="2"><tr><td>
<table class="style_inner" align="center" border="0" cellpadding="2" cellspacing="2" width="400">
  <form name="jump_form" method="post" action="'.parse_link('a=tactical_cartography').'">
  <tr>
    <td><input type="radio" name="jump_type" value="1" checked="checked"></td>
    <td>'.constant($game->sprache("TEXT7")).'&nbsp;[<a href="'.parse_link('a=tactical_cartography&memo_setup').'">'.constant($game->sprache("TEXT8")).'</a>]</td>
    <td><select name="memo_jump"'.( ($n_cmemo == 0) ? ' disabled="disabled"' : ' onClick="return document.jump_form.jump_type[0].checked = true;"' ).'>
    ');

    if($n_cmemo > 0) {
        while($cmemo = $db->fetchrow($q_cmemo)) {
            $game->out('<option value="'.$cmemo['memo_view'].'-'.$cmemo['memo_id'].'">'.$cmemo['memo_name'].'</option>');
        }
    }
    else {
        $game->out('<option value="-">'.constant($game->sprache("TEXT9")).'</option>');
    }

    $game->out('
    </select></td>
  </tr>
    
  <tr>
    <td width="20"><input type="radio" name="jump_type" value="2"></td>
    <td width="230">'.constant($game->sprache("TEXT10")).'</td>
    <td width="150"><input class="field" type="text" name="jump_coords" onClick="return document.jump_form.jump_type[1].checked = true;"></td>
  </tr>
  
  <tr>
    <td><input type="radio" name="jump_type" value="3"></td>
    <td>
      <select class="select" name="jump_target" onClick="return document.jump_form.jump_type[2].checked = true;">
        <option value="1">'.constant($game->sprache("TEXT11")).'</option>
        <option value="2">'.constant($game->sprache("TEXT12")).'</option>
        <option value="3">'.constant($game->sprache("TEXT13")).'</option>
      </select>
    </td>
    <td><input type="text" class="field" name="jump_id" onClick="return document.jump_form.jump_type[2].checked = true;"></td>
  </tr>
    
  <tr><td height="5"></td></tr>
  <tr><td align="center" colspan="3"><input class="button" type="submit" name="jump" value="'.constant($game->sprache("TEXT14")).'"></td></tr>
  <tr><td height="2"></td></tr>
  </form>
</table>
</td></tr></table>
<br>
    ');
}

function display_ferengi_transfer($planet_id,$planet_system,$system_x,$system_y,$build_11) {
    global $game, $db,$INTER_SYSTEM_TIME;

    $game->out('<br><br>
<table class="style_outer" width="400" align="center" border="0" cellpadding="2" cellspacing="2"><tr><td>
  <table class="style_inner" wisth0"400" align="center" border="0" cellpadding="2" cellspacing="2">');

    if ($game->option_retr('show_trade')==0)
        $game->out('
  <tr>
    <td><b>'.constant($game->sprache("TEXT15")).'  <b>[<a href="'.parse_link('a=tactical_cartography&planet_id='.$_GET['planet_id'].'&strade=1').'"><i>'.constant($game->sprache("TEXT16")).'</i></a>]</b></td></tr>');
    /* 25/05/08 - AC: We need to check commercial centre presence also on the active planet */
    elseif ($build_11<1 || $game->planet['building_11']<1)
    {
        $game->out('
  <tr>
    <td><b>'.constant($game->sprache("TEXT15")).'  <b>[<a href="'.parse_link('a=tactical_cartography&planet_id='.$_GET['planet_id'].'"&strade=0').'"><i>'.constant($game->sprache("TEXT17")).'</i></a>]</b><br><b><br>'.constant($game->sprache("TEXT18")).'</b></td></tr>');
    }
    else
    {
        $game->out('
    <script language="JavaScript">
    function UpdateValues()
    {
    var i;
        var res_1=eval(document.tradeform.res_1.value);
        var res_2=eval(document.tradeform.res_2.value);
        var res_3=eval(document.tradeform.res_3.value);
        ');
        for ($x=0; $x<6; $x++)
        {
            $game->out('
        if (document.tradeform.unit_'.($x+1).'.value>0)
        {
        res_1+=eval("'.UnitPrice($x,0,$game->player['user_race']).'")*eval(document.tradeform.unit_'.($x+1).'.value);
        res_2+=eval("'.UnitPrice($x,1,$game->player['user_race']).'")*eval(document.tradeform.unit_'.($x+1).'.value);
        res_3+=eval("'.UnitPrice($x,2,$game->player['user_race']).'")*eval(document.tradeform.unit_'.($x+1).'.value);
        }
        ');
        }



        if ($game->planet['system_id']==$planet_system) $distance=$INTER_SYSTEM_TIME;
        else
        {
            $distance = get_distance(array($game->planet['system_global_x'], $game->planet['system_global_y']), array($system_x,$system_y));
            $velocity = warpf(6);
            $distance= ceil( ( ($distance / $velocity) / TICK_DURATION ) );
        }




        $game->out('
        var ttax_set='.(0.18-0.01*($game->planet['building_11'])).';
        document.getElementById( "res1" ).firstChild.nodeValue = Math.round(res_1*ttax_set);
        document.getElementById( "res2" ).firstChild.nodeValue = Math.round(res_2*ttax_set);
        document.getElementById( "res3" ).firstChild.nodeValue = Math.round(res_3*ttax_set);
        window.setTimeout( \'UpdateValues()\', 500 );
    }
    </script>

  <form name="tradeform" method="post" action="'.parse_link('a=tactical_cartography&planet_id='.$_GET['planet_id']).'">
  <tr>
    <td colspan=3 align="center"><b>'.constant($game->sprache("TEXT19")).'<br>('.constant($game->sprache("TEXT22")).' '.Zeit($distance*TICK_DURATION).')</b><br><br>
    </td>
  </tr>
  <tr>
  <td><img src='.$game->GFX_PATH.'menu_metal_small.gif>&nbsp;&nbsp;&nbsp;<input class="field"  style="width: 60px;" type="text" name="res_1" value="0" onFocus="UpdateValues();">&nbsp&nbsp</td>
  <td><img src='.$game->GFX_PATH.'menu_unit1_small.gif>&nbsp;&nbsp;&nbsp;<input class="field"  style="width: 60px;" type="text" name="unit_1" value="0" onFocus="UpdateValues();">&nbsp&nbsp</td>
  <td><img src='.$game->GFX_PATH.'menu_unit4_small.gif>&nbsp;&nbsp;&nbsp;<input class="field"  style="width: 60px;" type="text" name="unit_4" value="0" onFocus="UpdateValues();">&nbsp&nbsp</td>
  </tr>

  <tr>
  <td><img src='.$game->GFX_PATH.'menu_mineral_small.gif>&nbsp;&nbsp;&nbsp;<input class="field"  style="width: 60px;" type="text" name="res_2" value="0" onFocus="UpdateValues();">&nbsp&nbsp</td>
  <td><img src='.$game->GFX_PATH.'menu_unit2_small.gif>&nbsp;&nbsp;&nbsp;<input class="field"  style="width: 60px;" type="text" name="unit_2" value="0" onFocus="UpdateValues();">&nbsp&nbsp</td>
  <td><img src='.$game->GFX_PATH.'menu_unit5_small.gif>&nbsp;&nbsp;&nbsp;<input class="field"  style="width: 60px;" type="text" name="unit_5" value="0" onFocus="UpdateValues();">&nbsp&nbsp</td>
  </tr>

  <tr>
  <td><img src='.$game->GFX_PATH.'menu_latinum_small.gif>&nbsp;&nbsp;&nbsp;<input class="field"  style="width: 60px;" type="text" name="res_3" value="0" onFocus="UpdateValues();">&nbsp&nbsp</td>
  <td><img src='.$game->GFX_PATH.'menu_unit3_small.gif>&nbsp;&nbsp;&nbsp;<input class="field"  style="width: 60px;" type="text" name="unit_3" value="0" onFocus="UpdateValues();">&nbsp&nbsp</td>
  <td><img src='.$game->GFX_PATH.'menu_unit6_small.gif>&nbsp;&nbsp;&nbsp;<input class="field"  style="width: 60px;" type="text" name="unit_6" value="0" onFocus="UpdateValues();">&nbsp&nbsp</td>
  </tr>
  <tr>
  <td colspan=3 align="center">
    <b>'.constant($game->sprache("TEXT20")).'</b>
    <img src='.$game->GFX_PATH.'menu_metal_small.gif>&nbsp;&nbsp;&nbsp;<b id="res1">0</b>
    <img src='.$game->GFX_PATH.'menu_mineral_small.gif>&nbsp;&nbsp;&nbsp;<b id="res2">0</b>
    <img src='.$game->GFX_PATH.'menu_latinum_small.gif>&nbsp;&nbsp;&nbsp;<b id="res3">0</b>
  </td>
  </tr>
  <tr>
  <td colspan=3 align="center">
    <input class="button" type="submit" name="trade" value="'.constant($game->sprache("TEXT21")).'"><br><br><b>
    [<a href="'.parse_link('a=tactical_cartography&planet_id='.$_GET['planet_id'].'&strade=0').'"><i>'.constant($game->sprache("TEXT17")).'</i></a>]</b>
  </td>
  </tr>
  </form>');
    }

    $game->out('</table></td></tr></table><br>');
}

function system_view_by_sensor($system_id) {
    global $game, $db;
    
    $sql = 'SELECT SUM(building_7) as sensors FROM planets WHERE planet_owner = '.$game->player['user_id'].' AND system_id = '.$system_id;
    
    $res = $db->queryrow($sql);
    
    if($res['sensors'] > 0 ) {return 1;}
    
    return 0;
}

$game->init_player();


if(!empty($_POST['trade'])) {

    $dpid = (int)decode_planet_id(filter_input(INPUT_GET, 'planet_id', FILTER_SANITIZE_EMAIL)); 

    // $_GET['planet_id'] = ($dpid != 0) ? $dpid : $_GET['planet_id'];
    
    if(empty($dpid)) $dpid = filter_input(INPUT_GET, 'planet_id', FILTER_SANITIZE_NUMBER_INT);
    
    // $_POST['res_1']=(int)$_POST['res_1'];
    // $_POST['res_2']=(int)$_POST['res_2'];
    // $_POST['res_3']=(int)$_POST['res_3'];
    // if ($_POST['res_1']<0) $_POST['res_1']=0;
    // if ($_POST['res_2']<0) $_POST['res_2']=0;
    // if ($_POST['res_3']<0) $_POST['res_3']=0;

    $traderes1 = filter_input(INPUT_POST, 'res_1', FILTER_SANITIZE_NUMBER_INT);
    $traderes2 = filter_input(INPUT_POST, 'res_2', FILTER_SANITIZE_NUMBER_INT);
    $traderes3 = filter_input(INPUT_POST, 'res_3', FILTER_SANITIZE_NUMBER_INT);
    
    if (!is_numeric($traderes1) || $traderes1 === false || $traderes1<0) $traderes1=0;
    if (!is_numeric($traderes2) || $traderes2 === false || $traderes2<0) $traderes2=0;
    if (!is_numeric($traderes3) || $traderes3 === false || $traderes3<0) $traderes3=0;
    
    /*
    $_POST['unit_1']=(int)$_POST['unit_1'];
    $_POST['unit_2']=(int)$_POST['unit_2'];
    $_POST['unit_3']=(int)$_POST['unit_3'];
    $_POST['unit_4']=(int)$_POST['unit_4'];
    $_POST['unit_5']=(int)$_POST['unit_5'];
    $_POST['unit_6']=(int)$_POST['unit_6'];

    if ($_POST['unit_1']<0) $_POST['unit_1']=0;
    if ($_POST['unit_2']<0) $_POST['unit_2']=0;
    if ($_POST['unit_3']<0) $_POST['unit_3']=0;
    if ($_POST['unit_4']<0) $_POST['unit_4']=0;
    if ($_POST['unit_5']<0) $_POST['unit_5']=0;
    if ($_POST['unit_6']<0) $_POST['unit_6']=0;
     */

    $tradeunit['unit_1'] = filter_input(INPUT_POST, 'unit_1', FILTER_SANITIZE_NUMBER_INT);
    $tradeunit['unit_2'] = filter_input(INPUT_POST, 'unit_2', FILTER_SANITIZE_NUMBER_INT);
    $tradeunit['unit_3'] = filter_input(INPUT_POST, 'unit_3', FILTER_SANITIZE_NUMBER_INT);
    $tradeunit['unit_4'] = filter_input(INPUT_POST, 'unit_4', FILTER_SANITIZE_NUMBER_INT);
    $tradeunit['unit_5'] = filter_input(INPUT_POST, 'unit_5', FILTER_SANITIZE_NUMBER_INT);    
    $tradeunit['unit_6'] = filter_input(INPUT_POST, 'unit_6', FILTER_SANITIZE_NUMBER_INT);    
    
    if(!is_numeric($tradeunit['unit_1']) || $tradeunit['unit_1'] === false || $tradeunit['unit_1']<0) $tradeunit['unit_1']=0;
    if(!is_numeric($tradeunit['unit_2']) || $tradeunit['unit_2'] === false || $tradeunit['unit_2']<0) $tradeunit['unit_2']=0;
    if(!is_numeric($tradeunit['unit_3']) || $tradeunit['unit_3'] === false || $tradeunit['unit_3']<0) $tradeunit['unit_3']=0;
    if(!is_numeric($tradeunit['unit_4']) || $tradeunit['unit_4'] === false || $tradeunit['unit_4']<0) $tradeunit['unit_4']=0;
    if(!is_numeric($tradeunit['unit_5']) || $tradeunit['unit_5'] === false || $tradeunit['unit_5']<0) $tradeunit['unit_5']=0;    
    if(!is_numeric($tradeunit['unit_6']) || $tradeunit['unit_6'] === false || $tradeunit['unit_6']<0) $tradeunit['unit_6']=0;    
    
    /*
    if ($_POST['res_1']==0 && $_POST['res_2']==0 && $_POST['res_3']==0 &&
        $_POST['unit_1']==0 && $_POST['unit_2']==0 && $_POST['unit_3']==0 &&
        $_POST['unit_4']==0 && $_POST['unit_5']==0 && $_POST['unit_6']==0) {
        redirect('a=tactical_cartography&planet_id='.encode_planet_id($_GET['planet_id']));
    }
     */

    if ($traderes1 == 0 && $traderes2 == 0 && $traderes3 == 0 &&
        $tradeunit['unit_1'] == 0 && $tradeunit['unit_2'] == 0 && $tradeunit['unit_3'] == 0 &&
        $tradeunit['unit_4'] == 0 && $tradeunit['unit_5'] == 0 && $tradeunit['unit_6'] == 0) {
        redirect('a=tactical_cartography&planet_id='.encode_planet_id($dpid));
    }

    if ($game->planet['planet_id']==$dpid)
        redirect('a=tactical_cartography&planet_id='.encode_planet_id($dpid));

    $sql = 'SELECT p.planet_id, p.planet_name, p.system_id, p.sector_id, p.planet_type, p.planet_owner, p.planet_points, p.planet_distance_id,
                   s.system_global_x,s.system_global_y,
                   s.system_name, s.system_x, s.system_y,
                   u.user_race, u.user_vacation_end, u.user_vacation_end
            FROM (planets p, starsystems s)
            LEFT JOIN (user u) ON u.user_id=p.planet_owner
            WHERE p.planet_id = '.$dpid.' AND
                  s.system_id = p.system_id';

    if(($dest = $db->queryrow($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query planet data');
    }

    if($dest['user_vacation_start']<=$ACTUAL_TICK && $dest['user_vacation_end']>=$ACTUAL_TICK) {
       message(NOTICE, constant($game->sprache("TEXT23")));
    }
    
    if(empty($dest['planet_id'])) {
        message(NOTICE, constant($game->sprache("TEXT24")));
    }

    if($dest['planet_owner']==0) {
        message(NOTICE, constant($game->sprache("TEXT25")));
    }

    //Activated by Mojo1987, yay the TC lives
    if($dest['planet_owner']!=$game->player['user_id']) {
        message(NOTICE, constant($game->sprache("TEXT26")));
    } 

    if($game->SITTING_MODE) {
        if($dest['planet_owner']!=$game->player['user_id']) {
            message(NOTICE, constant($game->sprache("TEXT27")));
        }
    }

    if ($game->planet['system_id']==$dest['system_id']) $distance=$INTER_SYSTEM_TIME;
    else
    {
        $distance = get_distance(array($game->planet['system_global_x'], $game->planet['system_global_y']), array($dest['system_global_x'],$dest['system_global_y']));
        $velocity = warpf(6);
        $distance= ceil( ( ($distance / $velocity) / TICK_DURATION ) );
    }

    if ($distance<$INTER_SYSTEM_TIME) $distance=$INTER_SYSTEM_TIME;



    $gebuehr[0]=$traderes1;
    $gebuehr[1]=$traderes2;
    $gebuehr[2]=$traderes3;
    
    for ($x=0; $x<6; $x++)
    {
        $gebuehr[0]+=UnitPrice($x,0,$dest['user_race'])*$tradeunit['unit_'.($x+1)];
        $gebuehr[1]+=UnitPrice($x,1,$dest['user_race'])*$tradeunit['unit_'.($x+1)];
        $gebuehr[2]+=UnitPrice($x,2,$dest['user_race'])*$tradeunit['unit_'.($x+1)];
    }

    $gebuehr[0]=ceil($gebuehr[0]*(0.18-0.01*($game->planet['building_11'])));
    $gebuehr[1]=ceil($gebuehr[1]*(0.18-0.01*($game->planet['building_11'])));
    $gebuehr[2]=ceil($gebuehr[2]*(0.18-0.01*($game->planet['building_11'])));


    if ($game->planet['resource_1']<$traderes1+$gebuehr[0]) {redirect('a=tactical_cartography&planet_id='.encode_planet_id($dpid));exit;}
    if ($game->planet['resource_2']<$traderes2+$gebuehr[1]) {redirect('a=tactical_cartography&planet_id='.encode_planet_id($dpid));exit;}
    if ($game->planet['resource_3']<$traderes3+$gebuehr[2]) {redirect('a=tactical_cartography&planet_id='.encode_planet_id($dpid));exit;}
    if ($game->planet['unit_1']<$tradeunit['unit_1'])  {redirect('a=tactical_cartography&planet_id='.encode_planet_id($dpid));exit;}
    if ($game->planet['unit_2']<$tradeunit['unit_2'])  {redirect('a=tactical_cartography&planet_id='.encode_planet_id($dpid));exit;}
    if ($game->planet['unit_3']<$tradeunit['unit_3'])  {redirect('a=tactical_cartography&planet_id='.encode_planet_id($dpid));exit;}
    if ($game->planet['unit_4']<$tradeunit['unit_4'])  {redirect('a=tactical_cartography&planet_id='.encode_planet_id($dpid));exit;}
    if ($game->planet['unit_5']<$tradeunit['unit_5'])  {redirect('a=tactical_cartography&planet_id='.encode_planet_id($dpid));exit;}
    if ($game->planet['unit_6']<$tradeunit['unit_6'])  {redirect('a=tactical_cartography&planet_id='.encode_planet_id($dpid));exit;}

    $sql = 'UPDATE planets SET resource_1=resource_1-'.($traderes1+$gebuehr[0]).',
                               resource_2=resource_2-'.($traderes2+$gebuehr[1]).',
                               resource_3=resource_3-'.($traderes3+$gebuehr[2]).',
                               unit_1=unit_1-'.($tradeunit['unit_1']).',
                               unit_2=unit_2-'.($tradeunit['unit_2']).',
                               unit_3=unit_3-'.($tradeunit['unit_3']).',
                               unit_4=unit_4-'.($tradeunit['unit_4']).',
                               unit_5=unit_5-'.($tradeunit['unit_5']).',
                               unit_6=unit_6-'.($tradeunit['unit_6']).'
            WHERE planet_id= "'.$game->planet['planet_id'].'"';
    if (($db->query($sql))===true) {
        $sql = 'INSERT INTO scheduler_resourcetrade (planet,resource_1,resource_2,resource_3,unit_1,unit_2,unit_3,unit_4,unit_5,unit_6,arrival_time)
                VALUES ("'.$dest['planet_id'].'","'.$traderes1.'","'.$traderes2.'","'.$traderes3.'","'.$tradeunit['unit_1'].'","'.$tradeunit['unit_2'].'","'.$tradeunit['unit_3'].'","'.$tradeunit['unit_4'].'","'.$tradeunit['unit_5'].'","'.$tradeunit['unit_6'].'","'.($ACTUAL_TICK+$distance).'")';
        if (($db->query($sql))===true)
        {
            // Fake Fleets starts:
            $res=$traderes1+$traderes2+$traderes3;
            $unit=$tradeunit['unit_1']+$tradeunit['unit_2']+$tradeunit['unit_3']+$tradeunit['unit_4']+$tradeunit['unit_5']+$tradeunit['unit_6'];

            $shipsr=ceil($res/MAX_TRANSPORT_RESOURCES);
            $shipsu=ceil($unit/MAX_TRANSPORT_UNITS);
            $ships=max($shipsr,$shipsu);
            if ($ships<1) $ships=1;
            send_fake_transporter(array(FERENGI_TRADESHIP_ID=>$ships), FERENGI_USERID, $game->planet['planet_id'], $dest['planet_id']);
            $db->query('UPDATE config SET ferengitax_1=ferengitax_1+'.($gebuehr[0]).', ferengitax_2=ferengitax_2+'.($gebuehr[1]).', ferengitax_3=ferengitax_3+'.($gebuehr[2]));
        }
        else {
            message(DATABASE_ERROR, 'Could not send troops/resources via ferengi transport.');
        }
    }
    else {
        message(DATABASE_ERROR, 'Could not pick up goods for ferengi transport.');
    }

    redirect('a=tactical_cartography&planet_id='.encode_planet_id($dpid));
}
elseif(!empty($_POST['jump'])) {
    $type = (int)$_POST['jump_type'];

    switch($type) {
        case 1:
            if(empty($_POST['memo_jump'])) {
                message(NOTICE, constant($game->sprache("TEXT28")));
            }

            $memo_pieces = explode('-', $_POST['memo_jump']);

            if(count($memo_pieces) != 2) {
                message(GENERAL, constant($game->sprache("TEXT29")), 'count($memo_pieces) = '.count($memo_pieces));
            }
            
            $view = (int)$memo_pieces[0];
            $id = (int)$memo_pieces[1];
            
            if(empty($tc_views_map[$view])) {
                message(GENERAL, constant($game->sprache("TEXT30")), '$tc_views_map[$view] = empty');
            }

            // Workaround for new encode_planet_id        
            if($view == 3) $id = encode_system_id($id);
            if($view == 4) $id = encode_planet_id($id);

            redirect('a=tactical_cartography&'.sprintf($tc_views_map[$view], $id));
        break;

        case 2:
            if(empty($_POST['jump_coords'])) {
                message(NOTICE, constant($game->sprache("TEXT31")));
            }

            $coord_pieces = explode(':', $_POST['jump_coords']);
            $n_pieces = count($coord_pieces);

            $sector_id = $game->get_sector_id($coord_pieces[0]);

            if($n_pieces == 1) {
                redirect('a=tactical_cartography&sector_id='.$sector_id);
            }

            $letters = array('A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8, 'I' => 9);
            $numbers = array('1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9);

            if(!isset($letters[$coord_pieces[1][0]])) {
                message(NOTICE, constant($game->sprache("TEXT32")), $coord_pieces[1][0].' '.constant($game->sprache("TEXT33")));
            }

            $system_y = $letters[$coord_pieces[1][0]];

            if(!isset($numbers[$coord_pieces[1][1]])) {
                message(NOTICE, constant($game->sprache("TEXT34")), $coord_pieces[1][1].' '.constant($game->sprache("TEXT35")));
            }

            $system_x = $numbers[$coord_pieces[1][1]];

            if($n_pieces == 2) {
                $sql = 'SELECT system_id
                        FROM starsystems
                        WHERE sector_id = '.$sector_id.' AND
                              system_x = '.$system_x.' AND
                              system_y = '.$system_y;

                if(($system = $db->queryrow($sql)) === false) {
                    message(DATABASE_ERROR, 'Could not query starsystem data');
                }

                if(empty($system['system_id'])) {
                    message(NOTICE, constant($game->sprache("TEXT36")).' <b>'.$_POST['jump_coords'].'</b> (<a href="'.parse_link('a=tactical_cartography&sector_id='.$sector_id).'">'.constant($game->sprache("TEXT37")).'</a>)');
                }

                redirect('a=tactical_cartography&system_id='.encode_system_id($system['system_id']));
            }
            elseif($n_pieces == 3) {
                $distance_id = (int)$coord_pieces[2] - 1;

                $sql = 'SELECT p.planet_id
                        FROM (planets p, starsystems s)
                        WHERE s.sector_id = '.$sector_id.' AND
                              s.system_x = '.$system_x.' AND
                              s.system_y = '.$system_y.' AND
                              s.system_id = p.system_id AND
                              p.planet_distance_id = '.$distance_id;

                if(($planet = $db->queryrow($sql)) === false) {
                    message(DATABASE_ERROR, 'Could not query planets data');
                }

                if(empty($planet['planet_id'])) {
                    message(NOTICE, constant($game->sprache("TEXT38")).' <b>'.$_POST['jump_coords'].'</b> (<a href="'.parse_link('a=tactical_cartography&sector_id='.$sector_id).'">'.constant($game->sprache("TEXT37")).'</a>)');
                }

                redirect('a=tactical_cartography&planet_id='.encode_planet_id($planet['planet_id']));
            }
            else {
                message(NOTICE, constant($game->sprache("TEXT39")));
            }
        break;

        case 3:
            $target = (int)$_POST['jump_target'];

            if(empty($_POST['jump_id'])) {
                message(NOTICE, constant($game->sprache("TEXT40")));
            }

            switch($target) {
                case 1:
                    if(is_numeric($_POST['jump_id'])) {
                        redirect('a=tactical_cartography&sector_id='.$_POST['jump_id']);
                    }
                    else {
                        redirect('a=tactical_cartography&sector_id='.$game->get_sector_id($_POST['jump_id']));
                    }
                break;

                case 2:
//                    if(is_numeric($_POST['jump_id'])) {
//                        redirect('a=tactical_cartography&system_id='.$_POST['jump_id']);
//                    }
//                    else {
                        $system_name = addslashes($_POST['jump_id']);

                        $sql = 'SELECT system_id, sector_id, system_x, system_y
                                FROM starsystems
                                WHERE system_name LIKE "'.$system_name.'"';

                        if(($systems = $db->queryrowset($sql)) === false) {
                            message(DATABASE_ERROR, 'Could not query system data');
                        }

                        $n_systems = count($systems);

                        if($n_systems == 0) {
                            message(NOTICE, constant($game->sprache("TEXT41")).$system_name.constant($game->sprache("TEXT42")));
                        }
                        elseif($n_systems == 1) {
                            redirect('a=tactical_cartography&system_id='.decode_system_id($systems[0]['system_id']));
                        }
                        else {
                            $game->out('
<table class="style_outer" width="80%" align="center" border="0" cellpadding="2" cellspacing="2"><tr><td>
<table class="style_inner" width="80%" align="center" border="0" cellspacing="2" cellpadding="2">
  <tr>
    <td>'.constant($game->sprache("TEXT43")).' <b>'.$n_systems.'</b> '.constant($game->sprache("TEXT44")).' <b>'.$system_name.'</b> '.constant($game->sprache("TEXT45")).'<br><br>
                            ');

                            for($i = 0; $i < $n_systems; ++$i) {
                                $game->out('- <a href="'.parse_link('a=tactical_cartography&system_id='.encode_system_id($systems[$i]['system_id'])).'">'.$game->get_sector_name($systems[$i]['sector_id']).':'.$game->get_system_cname($systems[$i]['system_x'], $systems[$i]['system_y']).'</a><br>');
                            }

                            $game->out('</td></tr></table></td></tr></table><br>');
                        }
//                    }
                break;

                case 3:
//                    if(is_numeric($_POST['jump_id'])) {
//                        redirect('a=tactical_cartography&planet_id='.$_POST['jump_id']);
//                    }
//                    else {
                        $planet_name = addslashes($_POST['jump_id']);

                        $sql = 'SELECT p.planet_id, p.planet_name, p.sector_id, p.system_id, p.planet_distance_id,
                                       s.system_x, s.system_y
                                FROM (planets p)
                                INNER JOIN (starsystems s) ON s.system_id = p.system_id
                                INNER JOIN (starsystems_details sd) ON p.system_id = sd.system_id AND sd.user_id = '.$game->player['user_id'].'
                                WHERE p.planet_name LIKE "%'.$planet_name.'%" AND p.planet_name!="'.UNINHABITATED_COLONY.'" AND p.planet_name!="'.UNINHABITATED_PLANET.'" AND p.planet_owner <> 0
                                GROUP BY p.planet_id';

                        if(($planets = $db->queryrowset($sql)) === false) {
                            message(DATABASE_ERROR, 'Could not query planet data');
                        }

                        $n_planets = count($planets);

                        if($planet_name == UNINHABITATED_COLONY) $n_planets = 0;
                        elseif($planet_name == UNINHABITATED_PLANET) $n_planets = 0;

                        if($n_planets == 0) {
                            message(NOTICE, constant($game->sprache("TEXT46")).$planet_name.constant($game->sprache("TEXT42")));
                        }

                        elseif($n_planets == 1) {
                            redirect('a=tactical_cartography&planet_id='.encode_planet_id($planets[0]['planet_id']));
                        }
                        else {
                            $game->out('
<table class="style_outer" width="80%" align="center" border="0" cellpadding="2" cellspacing="2"><tr><td>
<table class="style_inner" width="80%" align="center" cellspacing="2" cellpadding="2">
  <tr>
    <td>'.constant($game->sprache("TEXT43")).' <b>'.$n_planets.'</b> '.constant($game->sprache("TEXT47")).' <b>'.$planet_name.'</b> '.constant($game->sprache("TEXT45")).'<br><br>
                            ');

                            for($i = 0; $i < $n_planets; ++$i) {
                                if(($planets[$i]['planet_name']!=UNINHABITATED_COLONY) && ($planets[$i]['planet_name']!=UNINHABITATED_PLANET))
                                {
                                    $game->out('<table><tr><td width="100px"><a href="'.parse_link('a=tactical_cartography&planet_id='.encode_planet_id($planets[$i]['planet_id'])).'">'.$game->get_sector_name($planets[$i]['sector_id']).':'.$game->get_system_cname($planets[$i]['system_x'], $planets[$i]['system_y']).':'.($planets[$i]['planet_distance_id'] + 1).'</a></td><td><a href="'.parse_link('a=tactical_cartography&planet_id='.encode_planet_id($planets[$i]['planet_id'])).'">'.$planets[$i]['planet_name'].'</a></tr></table>');
                                }
                            }

                            $game->out('</td></tr></table></td></tr></table><br>');
                        }
//                    }
                break;
                
                default:
                    message(GENERAL, constant($game->sprache("TEXT48")), '$target = '.$target);
                break;
            }
        break;

        default:
            message(GENERAL, constant($game->sprache("TEXT49")), '$type = '.$type);
        break;
    }
}
elseif(!empty($_POST['memo_add'])) {
    if(empty($_POST['memo_name'])) {
        message(NOTICE, constant($game->sprache("TEXT50")));
    }

    $memo_name = addslashes($_POST['memo_name']);

    if($_POST['memo_entry'] == '-1') {
        $sql = 'SELECT COUNT(memo_view) AS n
                FROM tc_coords_memo
                WHERE user_id = '.$game->player['user_id'];

        if(($cmemo_count = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query tactical cartography memo count data');
        }

        if($cmemo_count['n'] >= 15) {
            message(NOTICE, constant($game->sprache("TEXT51")));
        }

        $sql = 'INSERT INTO tc_coords_memo (user_id, memo_name, memo_view, memo_id)
                VALUES ('.$game->uid.', "'.$memo_name.'", '.$game->player['last_tcartography_view'].', '.$game->player['last_tcartography_id'].')';

        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not insert new tactical cartography memo data');
        }
    }
    else {
        $tcm_id = (int)$_POST['memo_entry'];

        $sql = 'SELECT tcm_id, user_id
                FROM tc_coords_memo
                WHERE tcm_id = '.$tcm_id;

        if(($cmemo = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query tactical coords memo data');
        }

        if(empty($cmemo['tcm_id'])) {
            message(NOTICE, constant($game->sprache("TEXT52")));
        }

        if($cmemo['user_id'] != $game->uid) {
            message(NOTICE, constant($game->sprache("TEXT52")));
        }

        $sql = 'UPDATE tc_coords_memo
                SET memo_name = "'.$memo_name.'",
                    memo_view = '.$game->player['last_tcartography_view'].',
                    memo_id = '.$game->player['last_tcartography_id'].'
                WHERE tcm_id = '.$tcm_id;

        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not update tactical cartography memo data');
        }
    }

    redirect('a=tactical_cartography&memo_setup');
}
elseif(!empty($_GET['memo_delete'])) {
    $tcm_id = (int)$_GET['memo_delete'];

    $sql = 'SELECT tcm_id, user_id
            FROM tc_coords_memo
            WHERE tcm_id = '.$tcm_id;

    if(($cmemo = $db->queryrow($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query tactical coords memo data');
    }

    if(empty($cmemo['tcm_id'])) {
        message(NOTICE, constant($game->sprache("TEXT52")));
    }

    if($cmemo['user_id'] != $game->uid) {
        message(NOTICE, constant($game->sprache("TEXT52")));
    }

    $sql = 'DELETE FROM tc_coords_memo
            WHERE tcm_id = '.$tcm_id;

    if(!$db->query($sql)) {
        message(DATABASE_ERROR, 'Could not delete tactical coords memo data');
    }

    redirect('a=tactical_cartography&memo_setup');
}
elseif(isset($_GET['memo_setup'])) {
    $sql = 'SELECT tcm_id, memo_name, memo_view, memo_id
            FROM tc_coords_memo
            WHERE user_id = '.$game->player['user_id'];

    if(!$q_cmemo = $db->query($sql)) {
        message(DATABASE_ERROR, 'Could not query tactical cartography memo data');
    }

    $n_cmemo = $db->num_rows($q_cmemo);

    $game->out('
<span class="caption">'.constant($game->sprache("TEXT0")).'</span><br><br>[<b>'.constant($game->sprache("TEXT1")).'</b>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_moves').'">'.constant($game->sprache("TEXT2")).'</a>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_player').'">'.constant($game->sprache("TEXT3")).'</a>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_kolo').'">'.constant($game->sprache("TEXT4")).'</a>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_known').'">'.constant($game->sprache("TEXT4a")).'</a>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_sensors').'">'.constant($game->sprache("TEXT5")).'</a>]&nbsp;&nbsp;                
            [<a href="'.parse_link('a=tactical_search').'">'.constant($game->sprache("TEXT5a")).'</a>]<br><br>
<table class="style_outer" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
  <tr>
    <td>
      <table class="style_inner" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
      <form method="post" action="'.parse_link('a=tactical_cartography').'">
        <tr>
          <td>
            '.constant($game->sprache("TEXT53")).'
            <br><br>
            '.constant($game->sprache("TEXT54")).' <b>'.(15 - $n_cmemo).'</b> / <b>15</b><br><br>
            '.constant($game->sprache("TEXT55")).'&nbsp;&nbsp;<input class="field" type="text" name="memo_name" value=""><br><br>
    ');

    while($cmemo = $db->fetchrow($q_cmemo)) {
        $game->out('<input type="radio" name="memo_entry" value="'.$cmemo['tcm_id'].'">&nbsp;'.$cmemo['memo_name'].'&nbsp;[<a href="'.parse_link('a=tactical_cartography&memo_delete='.$cmemo['tcm_id']).'">'.constant($game->sprache("TEXT56")).'</a>]<br><br>');
    }

    if($n_cmemo < 15) {
        $game->out('<input type="radio" name="memo_entry" value="-1" checked="checked">&nbsp;<i>'.constant($game->sprache("TEXT57")).'<i><br><br>');
    }

    $game->out('<br><input class="button" type="submit" name="memo_add" value="'.constant($game->sprache("TEXT58")).'"></td></tr></form></table></td></tr></table>');
}
elseif(!empty($_GET['planet_id'])) {
    if(is_numeric($_GET['planet_id'])) {
        die(constant($game->sprache("TEXT59")));
    }
    else {
        $planet_id = (int)decode_planet_id($_GET['planet_id']);
    }

    $own_planet = $free_planet = $has_fullview = false;

    if($game->planet['planet_id'] == $planet_id) {
        $planet = &$game->planet;

        $sql = 'SELECT system_id, system_name, system_x, system_y
                FROM starsystems
                WHERE system_id = '.$planet['system_id'];

        if(($_system = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query starsystem data');
        }

        if(empty($_system['system_id'])) {
            message(NOTICE, constant($game->sprache("TEXT41")).$planet['system_id'].constant($game->sprache("TEXT42")));
        }

        $planet += $_system;
    }
    else {
        $sql = 'SELECT p.planet_id, p.planet_name, p.building_10, p.building_11, p.building_13, p.system_id, p.sector_id, p.planet_type,
                       p.research_1, p.research_2, p.research_3, p.research_4, p.research_5,
                       p.planet_owner, p.best_mood, p.best_mood_user, p.planet_points, p.planet_distance_id,
                       u.user_name, u.user_alliance, a.alliance_tag,
                       s.system_global_x,s.system_global_y,s.system_name, s.system_x, s.system_y
                FROM (planets p, starsystems s)
                LEFT JOIN user u ON (p.best_mood_user = u.user_id)
                LEFT JOIN alliance a ON (u.user_alliance = a.alliance_id)                
                WHERE p.planet_id = '.$planet_id.' AND
                      s.system_id = p.system_id';

        if(($planet = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query planet data');
        }
        
        if(empty($planet['planet_id'])) {
            message(NOTICE, constant($game->sprache("TEXT24")));
        }
    }
    
    if($planet['planet_owner'] == $game->player['user_id']) {
        $own_planet = true;

        $planet_owner = &$game->player;
    }
    elseif($planet['planet_owner'] == 0) {
        $free_planet = true;

        $planet_owner = array();
    }
    else {
        $sql = 'SELECT u.user_id, u.user_name, u.user_points, u.user_vacation_start, u.user_vacation_end, u.user_capital,
                       a.alliance_id, a.alliance_tag, a.alliance_name
                FROM (user u)
                LEFT JOIN (alliance a) ON a.alliance_id = u.user_alliance
                WHERE u.user_id = '.$planet['planet_owner'];

        if(($planet_owner = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query planet owner data');
        }

        if(empty($planet_owner['user_id'])) {
            $free_planet = true;
        }
    }

    set_tcartography_remind(4, $planet_id);

    $quadrant_id = $game->get_quadrant($planet['sector_id']);

    $planet_thumb = (!empty($planet['planet_thumb'])) ? $planet['planet_thumb'] : $game->PLAIN_GFX_PATH.'planet_type_'.$planet['planet_type'].'.png';
    $planet_type = strtoupper($planet['planet_type']);


    $history_text   = constant($game->sprache("TEXT95"));
    $survey_text    = constant($game->sprache("TEXT96"));
    $tactical_text  = constant($game->sprache("TEXT97"));


    // --- Historical data of the planet ---
    // Only the last nine events are presented in the log, to prevent the log window may be too big.
    $sql = 'SELECT d.*, u.user_name, alliance.alliance_tag FROM planet_details d
        LEFT JOIN user u ON d.user_id = u.user_id
        LEFT JOIN alliance ON d.source_aid = alliance.alliance_id
        WHERE planet_id = '.$planet['planet_id'].'
        AND d.log_code IN (0, 1, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34) 
        ORDER BY timestamp DESC LIMIT 0, 10';

    if($_history = $db->query($sql)) {
        while($_temp = $db->fetchrow($_history)) {
            switch($_temp['log_code']) {
                // Founder
                case 0: $history_text .= constant($game->sprache("TEXT98")).( (!empty($_temp['user_name'])) ? $_temp['user_name'] : constant($game->sprache("TEXT120"))).constant($game->sprache("TEXT99")).date("d.m.y H:i", $_temp['timestamp']).'<br>';
                break;
                // Planet discoverer
                case 1:
                    if($_temp['user_id'] == $game->player['user_id']) {
                        $history_text .= constant($game->sprache("TEXT116")).date("d.m.y H:i", $_temp['timestamp']).constant($game->sprache("TEXT117")).( (!empty($_temp['user_name'])) ? $_temp['user_name'] : constant($game->sprache("TEXT120"))).( (!empty($_temp['alliance_tag'])) ? '['.$_temp['alliance_tag'].']' : '&nbsp;' ).constant($game->sprache("TEXT118")).'<br>';
                    }
                break;
                // Opponents
                case 2: $history_text .= constant($game->sprache("TEXT116")).date("d.m.y H:i", $_temp['timestamp']).constant($game->sprache("TEXT117")).( (!empty($_temp['user_name'])) ? $_temp['user_name'] : constant($game->sprache("TEXT120"))).( (!empty($_temp['alliance_tag'])) ? '['.$_temp['alliance_tag'].']' : '&nbsp;' ).constant($game->sprache("TEXT119")).'<br>';
                break;
                // Colonization
                case 25: $history_text .= constant($game->sprache("TEXT109")).(!empty($_temp['user_name']) ? $_temp['user_name'] : constant($game->sprache("TEXT120"))).(!empty($_temp['alliance_tag']) ? '['.$_temp['alliance_tag'].']' : '').constant($game->sprache("TEXT99")).date("d.m.y H:i", $_temp['timestamp']).'.<br>';
                break;
                // --- Planet conquest!
                case 26: $sql = 'SELECT user_name FROM user WHERE user_id = '.$_temp['defeat_uid'];
                    if(!$_history_q1 = $db->queryrow($sql)) {
                        $_history_d1 = constant($game->sprache("TEXT120"));
                    }
                    else {
                        $_history_d1 = $_history_q1['user_name'];
                        $sql = 'SELECT alliance_tag FROM alliance WHERE alliance_id = '.$_temp['defeat_aid'];
                        if($_history_q2 = $db->queryrow($sql)) {
                            $_history_d2 = '['.$_history_q2['alliance_tag'].']';
                        }
                        else {
                            $_history_d2 = '&nbsp;';
                        }
                    }

                    $history_text .= constant($game->sprache("TEXT110")).(empty($_temp['user_name']) ? constant($game->sprache("TEXT120")) : $_temp['user_name']).'['.(empty($_temp['alliance_tag']) ? ' ' : $_temp['alliance_tag']).']'.constant($game->sprache("TEXT111")).date("d.m.y H:i", $_temp['timestamp']).constant($game->sprache("TEXT112")).$_history_d1.$_history_d2.'</b>.<br>';
                break;
                // --- Planet riots!
                case 27: $history_text .= constant($game->sprache("TEXT113")).$_temp['user_name'].'['.$_temp['alliance_tag'].']'.constant($game->sprache("TEXT114")).date("d.m.y H:i", $_temp['timestamp']).'.<br>';
                break;
                // --- Player account deletion!!!
                case 28: $history_text .= constant($game->sprache("TEXT115")).date("d.m.y H:i", $_temp['timestamp']).'.<br>';
                break;
                // --- Planet assimilated by Borg(NPG)!!!
                case 29: $sql = 'SELECT user_name FROM user WHERE user_id = '.$_temp['defeat_uid'];
                    if(!$_history_q1 = $db->queryrow($sql)) {
                        $_history_d1 = constant($game->sprache("TEXT120"));
                    }
                    else {
                        $_history_d1 = $_history_q1['user_name'];
                        $sql = 'SELECT alliance_tag FROM alliance WHERE alliance_id = '.$_temp['defeat_aid'];
                        if($_history_q2 = $db->queryrow($sql)) {
                            $_history_d2 = '['.$_history_q2['alliance_tag'].']';
                        }
                        else {
                            $_history_d2 = '&nbsp;';
                        }
                    }

                    $history_text .= constant($game->sprache("TEXT116")).date("d.m.y H:i", $_temp['timestamp']).constant($game->sprache("TEXT121")).constant($game->sprache("TEXT112")).$_history_d1.$_history_d2.'</b>.<br>';
                break;
                // --- Planet transfer to Settler faction
                case 30: $history_text .= constant($game->sprache("TEXT122")).$_temp['user_name'].'['.$_temp['alliance_tag'].']'.constant($game->sprache("TEXT123")).date("d.m.y H:i", $_temp['timestamp']).'.<br>';
                break;
                // --- Planet terraforming
                case 31: 
                    $history_text .= constant($game->sprache("TEXT127")).(!empty($_temp['user_name']) ? $_temp['user_name'] : constant($game->sprache("TEXT120"))).(!empty($_temp['alliance_tag']) ? '['.$_temp['alliance_tag'].']' : '').constant($game->sprache("TEXT128")).strtoupper($_temp['planet_type']).constant($game->sprache("TEXT99")).date("d.m.y H:i", $_temp['timestamp']).'.<br>';
                break;
                // --- Building of a colony for Settlers faction
                case 32: 
                    $history_text .= constant($game->sprache("TEXT116")).date("d.m.y H:i", $_temp['timestamp']).constant($game->sprache("TEXT129")).(!empty($_temp['user_name']) ? $_temp['user_name'] : constant($game->sprache("TEXT120"))).(!empty($_temp['alliance_tag']) ? '['.$_temp['alliance_tag'].']' : '').constant($game->sprache("TEXT130")).'.<br>';
                break;
                // --- Planet goes desert after surrendering
                case 33: 
                    $history_text .= constant($game->sprache("TEXT116")).date("d.m.y H:i", $_temp['timestamp']).constant($game->sprache("TEXT137")).(!empty($_temp['user_name']) ? $_temp['user_name'] : constant($game->sprache("TEXT120"))).(!empty($_temp['alliance_tag']) ? '['.$_temp['alliance_tag'].']' : '').constant($game->sprache("TEXT138")).'.<br>';
                break;
                // --- Planet goes desert after surrendering
                case 34: 
                    $history_text .= constant($game->sprache("TEXT116")).date("d.m.y H:i", $_temp['timestamp']).constant($game->sprache("TEXT137")).(!empty($_temp['user_name']) ? $_temp['user_name'] : constant($game->sprache("TEXT120"))).(!empty($_temp['alliance_tag']) ? '['.$_temp['alliance_tag'].']' : '').constant($game->sprache("TEXT138")).'.<br>';
                break;            
            }
        }
    }
    // --- Geological data on the planet ---
    $sql = 'SELECT survey_1, survey_2, survey_3, timestamp, source_uid, user.user_name, source_aid, alliance.alliance_tag, ship_name
            FROM `planet_details` 
                LEFT JOIN user ON planet_details.source_uid = user.user_id
                LEFT JOIN alliance ON planet_details.source_aid = alliance.alliance_id
                WHERE `planet_id` = '.$planet['planet_id'].'
                AND planet_details.user_id = '.$game->player['user_id'].'
                AND `log_code` = 100 
                ORDER BY timestamp DESC';
    if(($_temp = $db->queryrow($sql)) == true) {
        $survey_text .= constant($game->sprache("TEXT100")).$_temp['ship_name'].constant($game->sprache("TEXT101")).$_temp['user_name'].'['.$_temp['alliance_tag'].']'.constant($game->sprache("TEXT102")).date("d.m.y H:i", $_temp['timestamp']).':<br><br>';
        $survey_text .= '<table width=250 border=0 cellpadding= 0 cellspacing=0>';
        switch($_temp['survey_1']) {
            case 0:
                $_survey1 = '<tr align=left><td>'.constant($game->sprache("TEXT103")).'</td><td align=center><font color=red>'.constant($game->sprache("TEXT106")).'</font><td></tr>';
            break;
            case 1:
                $_survey1 = '<tr align=left><td>'.constant($game->sprache("TEXT103")).'</td><td align=center><font color=grey><b>'.constant($game->sprache("TEXT107")).'</b></font></td></tr>';
            break;
            case 2:
                $_survey1 = '<tr align=left><td>'.constant($game->sprache("TEXT103")).'</td><td align=center><font color=green>'.constant($game->sprache("TEXT108")).'</font></td></tr>';
            break;
        }
        switch($_temp['survey_2']) {
            case 0:
                $_survey2 = '<tr align=left><td>'.constant($game->sprache("TEXT104")).'</td><td align=center><font color=red>'.constant($game->sprache("TEXT106")).'</font></td></tr>';
            break;
            case 1:
                $_survey2 = '<tr align=left><td>'.constant($game->sprache("TEXT104")).'</td><td align=center><font color=grey><b>'.constant($game->sprache("TEXT107")).'</b></font></td></tr>';
            break;
            case 2:
                $_survey2 = '<tr align=left><td>'.constant($game->sprache("TEXT104")).'</td><td align=center><font color=green>'.constant($game->sprache("TEXT108")).'</font></td></tr>';
            break;
        }
        switch($_temp['survey_3']) {
            case 0:
                $_survey3 = '<tr align=left><td>'.constant($game->sprache("TEXT105")).'</td><td align=center><font color=red>'.constant($game->sprache("TEXT106")).'</font></td></tr>';
            break;
            case 1:
                $_survey3 = '<tr align=left><td>'.constant($game->sprache("TEXT105")).'</td><td align=center><font color=grey><b>'.constant($game->sprache("TEXT107")).'</b></font></td></tr>';
            break;
            case 2:
                $_survey3 = '<tr align=left><td>'.constant($game->sprache("TEXT105")).'</td><td align=center><font color=green>'.constant($game->sprache("TEXT108")).'</font></td></tr>';
            break;
        }
        $survey_text .= $_survey1.$_survey2.$_survey3.'</table>';
    }

// Tactical / political information on the planet (settlers system)
    if($planet['planet_owner'] == INDEPENDENT_USERID)
    {
        global $SETL_EVENTS;

        $sql = 'SELECT event_code, user_id, count_ok, count_ko, count_crit_ok, count_crit_ko FROM settlers_events
                    WHERE `planet_id` = '.$planet['planet_id'].'
                    AND event_status = 1
                    ORDER BY timestamp ASC';
        $q_setl = $db->query($sql);
        $rows = $db->num_rows($q_setl);
        if($rows > 0)
        {
            $tactical_text .= '<table width=320 border=0 cellpadding= 0 cellspacing=0>';
            $q_d_setl = $db->fetchrowset($q_setl);
            foreach($q_d_setl as $d_setl)
            {
                if($SETL_EVENTS[$d_setl['event_code']][1] && ($game->player['user_id'] != $d_setl['user_id'])) continue;
                $tactical_text .= '<tr align=left><td><i>'.$SETL_EVENTS[$d_setl['event_code']][0].'</i></td>';
                $tactical_text .= ($game->player['user_id'] == $d_setl['user_id'] ? '<td><i>('.$d_setl['count_crit_ok'].' / '.$d_setl['count_ok'].' / '.$d_setl['count_ko'].' / '.$d_setl['count_crit_ko'].')</i></td></tr>' : '</tr>');
            }
            $tactical_text .= '</table>';
        }

        $sql = 'SELECT log_code, timestamp, mood_modifier FROM settlers_relations
                    WHERE `planet_id` = '.$planet['planet_id'].'
                    AND user_id = '.$game->player['user_id'].'
                    ORDER BY timestamp ASC';
        $q_setl = $db->query($sql);
        $rows = $db->num_rows($q_setl);
        if($rows > 0)
        {
            $tactical_text .= '<table width=320 border=0 cellpadding= 0 cellspacing=0>';
            $q_d_setl = $db->fetchrowset($q_setl);
            foreach($q_d_setl as $d_setl)
            {
                $tactical_text .= '<tr align=left><td>'.get_diplo_str($d_setl['log_code']).'</td><td>'.date("d.m.y", $d_setl['timestamp']).'</td><td>'.$d_setl['mood_modifier'].'</td></tr>';
            }
            $tactical_text .= '</table>';
        }
    }
    elseif($planet['planet_owner'] > 10)
    {
        global $BUILDING_NAME, $MAX_POINTS, $MAX_BUILDING_LVL, $TECH_NAME, $MAX_RESEARCH_LVL;
        
        $capital_planet = ($planet['planet_available_points'] == $MAX_POINTS[1] ? 1 : 0);
        $tactical_text .= '<table width=320 border=0 cellpadding= 0 cellspacing=0>';
        $tactical_text .= '<tr align=left><td>'.$BUILDING_NAME[$game->player['user_race']][12].'</td><td>[';
        $defense_value =  (int)(($planet['building_13']*100/($MAX_BUILDING_LVL[$capital_planet][12] + $planet['research_3']))*0.09);
        for($i = 0;$i < 9; $i++)
        {
            $tactical_text .= ($defense_value > $i  ? '<font style=&#34;color: green;&#34;>&#9642</font>' : '&#9643');
        }        
        $tactical_text .= ']</td></tr>';
        $tactical_text .= '<tr align=left><td>'.$BUILDING_NAME[$game->player['user_race']][9].'</td><td>[';
        $defense_value =  (int)(($planet['building_10']*100/($MAX_BUILDING_LVL[$capital_planet][9] + $planet['research_3']))*0.09);
        for($i = 0;$i < 9; $i++)
        {
            $tactical_text .= ($defense_value > $i ? '<font style=&#34;color: green;&#34;>&#9642</font>' : '&#9643');
        }        
        $tactical_text .= ']</td></tr>';        
        foreach ($TECH_NAME[$game->player['user_race']] AS $key => $tech_text)
        {
            $tactical_text .= '<tr align=left><td>'.$tech_text.'</td><td>[';           
            $tech_value = (int)($planet['research_'.($key+1)]*100/$MAX_RESEARCH_LVL[$capital_planet][$key])*0.09;
            for($i = 0;$i < 9; $i++)
            {
                $tactical_text .= ($tech_value > $i ? '<font style=&#34;color: green;&#34;>&#9642</font>' : '&#9643');
            }
            $tactical_text .= ']</td></tr>';
        }
        $tactical_text .= '</table>';
    }
    
    $planet_is_known = false;
    $sql = 'SELECT timestamp FROM starsystems_details
            WHERE system_id = '.$planet['system_id'].'
            AND user_id = '.$game->player['user_id'];
    if($game->player['user_auth_level'] == STGC_DEVELOPER || $db->queryrow($sql) == true)
        $planet_is_known = true;

    $last_update = constant($game->sprache("TEXT93"));

    if($own_planet || ($game->player['user_alliance'] == $planet_owner['alliance_id']) || $planet_is_known) {
        $_thumb = '<a href='.$planet_thumb.' target="_blank"><img src="'.$planet_thumb.'" width="80" height="80" border="0"></a><br>';
        $_name  = '&nbsp;<b>'.$planet['planet_name'].'</b>&nbsp;('.$game->get_sector_name($planet['sector_id']).':'.$game->get_system_cname($planet['system_x'], $planet['system_y']).':'.($planet['planet_distance_id'] + 1).')';
        $_planet_type = '&nbsp;<a href="'.parse_link('a=database&planet_type='.$planet_type.'#'.$planet_type).'">'.$planet_type.'</a>';
        // DC: Yeah, yeah, i know.... why bothering on building all the texts if the planet is not known?
        $detail_text = $history_text.$survey_text.$tactical_text;
        // Last update.
        $sql = 'SELECT timestamp FROM `planet_details`
                WHERE `planet_id` = '.$planet['planet_id'].'
                ORDER BY timestamp DESC
                LIMIT 0, 1';
        if(($_temp = $db->queryrow($sql)) == true) {
            $last_update = constant($game->sprache("TEXT94")).'<b>'.date("d.m.y H:i", $_temp['timestamp']);
        }
    }
    else {
        $_thumb = '&nbsp;<br>';
        $_name  = '&nbsp;<b>'.constant($game->sprache("TEXT120")).'</b>&nbsp;('.$game->get_sector_name($planet['sector_id']).':'.$game->get_system_cname($planet['system_x'], $planet['system_y']).':'.($planet['planet_distance_id'] + 1).')';
        $_planet_type = '&nbsp;<b>'.constant($game->sprache("TEXT120")).'</b>';
    }


    $game->out('
<style type="text/css">
<!--
form {
    spacing = 0px;
    padding: 0px;
    border: 0px solid #000000;
}
//-->
</style>

<span class="caption">'.constant($game->sprache("TEXT0")).'</span><br><br>[<b>'.constant($game->sprache("TEXT1")).'</b>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_moves').'">'.constant($game->sprache("TEXT2")).'</a>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_player').'">'.constant($game->sprache("TEXT3")).'</a>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_kolo').'">'.constant($game->sprache("TEXT4")).'</a>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_known').'">'.constant($game->sprache("TEXT4a")).'</a>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_sensors').'">'.constant($game->sprache("TEXT5")).'</a>]&nbsp;&nbsp;                
            [<a href="'.parse_link('a=tactical_search').'">'.constant($game->sprache("TEXT5a")).'</a>]<br><br>

<table class="style_outer" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
<tr>
  <td>
    <table class="style_inner" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
    <tr>
      <td width="20">&nbsp;</td>
      <td width="380">
          <a href="'.parse_link('a=tactical_cartography&galaxy').'">'.constant($game->sprache("TEXT60")).'</a> ->
          <a href="'.parse_link('a=tactical_cartography&quadrant_id='.$quadrant_id).'">'.$QUADRANT_NAME[$quadrant_id].'</a> ->
          <a href="'.parse_link('a=tactical_cartography&sector_id='.$planet['sector_id']).'">'.constant($game->sprache("TEXT61")).' '.$game->get_sector_name($planet['sector_id']).'</a> ->
          <a href="'.parse_link('a=tactical_cartography&system_id='.encode_system_id($planet['system_id'])).'">'.$planet['system_name'].'</a>
      </td>
    </tr>
    </table>
  </td>
</tr>
</table>
<br>

'.( (!empty($_GET['message'])) ? '<br><b>'.base64_decode($_GET['message']).'</b><br>' : '' ).'

<br>
<table class="style_outer" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
  <tr>
    <td>
      <table class="style_inner" width="400" align="center" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td width="120" align="center">
            '.$_thumb.'
          </td>

          <td width="280" valign="top">
            <table width="280" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td valign="top">'.constant($game->sprache("TEXT62")).'</td>
                <td>'.$_name.'</td>
              </tr>
              <tr>
                <td valign="top">'.constant($game->sprache("TEXT63")).'</td>
                <td>'.$_planet_type.'</td>
              </tr>
              <tr>
                <td valign="top">'.constant($game->sprache("TEXT92")).'</td>
                <td>&nbsp;<a href="javascript:void(0);" onmouseover="return overlib(\''.addslashes($detail_text).'\', CAPTION, \''.$last_update.'\', WIDTH, 400, '.OVERLIB_STANDARD.');" onmouseout="return nd();">'.$last_update.'</a></td>
              </tr>
              <tr height="15"><td></td></tr>
    ');
    
    if($free_planet && $planet_is_known) {
        $game->out('
              <tr>
                <td colspan="2" valign="top"><i>'.constant($game->sprache("TEXT64")).'</i></td>
              </tr>
        ');
    }
    elseif($own_planet) {
        $game->out('
              <tr>
                <td valign="top" colspan="2">'.( ($planet_id == $game->player['user_capital']) ? constant($game->sprache("TEXT65")) : constant($game->sprache("TEXT66")) ).'</td>
              </tr>
              <tr>
                <td valign="top" colspan="2">'.( ($planet_id != $game->planet['planet_id']) ? '<a href="'.parse_link('a=headquarter&switch_active_planet='.$planet_id).'"><i>'.constant($game->sprache("TEXT67")).'</i></a>' : '&nbsp;' ).'</td>
              </tr>
              <tr>
                <td valign="top">'.constant($game->sprache("TEXT68")).'</td>
                <td>&nbsp;<u>'.$planet['planet_points'].'</u>&nbsp;/&nbsp;<u>'.( ($planet_id == $game->player['user_capital']) ? $MAX_POINTS[1] : $MAX_POINTS[0] ).'</u></td>
              </tr>
        ');
    }
    elseif(($game->player['user_alliance'] == $planet_owner['alliance_id']) || $planet_is_known)
    {
        if($planet['planet_owner'] == INDEPENDENT_USERID)
        {
             $game->out('
              <tr>
                <td valign="top">'.constant($game->sprache("TEXT69")).'</td>
                <td>&nbsp;<a href="'.parse_link('a=stats&a2=viewplayer&id='.$planet['planet_owner']).'">'.$planet_owner['user_name'].'</a></td>
              </tr>
              <tr>
                <td valign="top">'.constant($game->sprache("TEXT70")).'</td>
                <td>&nbsp;'.( (!empty($planet['best_mood_user'])) ? '<a href="'.parse_link('a=stats&a2=viewplayer&id='.$planet['best_mood_user']).'">'.$planet['user_name'].'</a> ('.$planet['best_mood'].')</td>' : constant($game->sprache("TEXT71")) ).'</td>
              </tr>
            ');
        }
        else
        {
            $game->out('
              <tr>
                <td valign="top">'.constant($game->sprache("TEXT69")).'</td>
                <td>&nbsp;<a href="'.parse_link('a=stats&a2=viewplayer&id='.$planet['planet_owner']).'">'.$planet_owner['user_name'].'</a> ('.$planet_owner['user_points'].')</td>
              </tr>
              <tr>
                <td valign="top">'.constant($game->sprache("TEXT70")).'</td>
                <td>&nbsp;'.( (!empty($planet_owner['alliance_id'])) ? '<a href="'.parse_link('a=stats&a2=viewalliance&id='.$planet_owner['alliance_id']).'">'.$planet_owner['alliance_name'].'</a>&nbsp;[<a href="'.parse_link('a=stats&a2=viewalliance&id='.$planet_owner['alliance_id']).'">'.$planet_owner['alliance_tag'].'</a>]<br>' : constant($game->sprache("TEXT71")) ).'</td>
              <tr>
                <td valign="top">'.constant($game->sprache("TEXT68")).'</td>
                <td>&nbsp;<u>'.$planet['planet_points'].'</u>&nbsp;/&nbsp;<u>'.( ($planet_id == $planet_owner['user_capital']) ? $MAX_POINTS[1] : $MAX_POINTS[0] ).'</u></td>
              </tr>
            ');
        }
    }
    
    $game->out('
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
    ');

    $has_fullview = system_view_by_sensor($planet['system_id']);    

    $sql = 'SELECT fleet_id, fleet_name, n_ships
            FROM ship_fleets
            WHERE planet_id = '.$planet_id.' AND
                  owner_id = '.$game->player['user_id'];

    if(!$q_own_fleets = $db->query($sql)) {
        message(DATABASE_ERROR, 'Could not query own fleets data');
    }

    $n_own_fleets = $db->num_rows($q_own_fleets);

    if($n_own_fleets > 0) {$has_fullview = true;}
      
    if( $has_fullview || $own_planet ) {
        $sensor_details = (!empty($_GET['sensor_details'])) ? (int)$_GET['sensor_details'] : 0;

        $game->out('
<br><br>
<form name="srs_form" method="post" action="">
<input type="hidden" name="user_id"   value="">
<input type="hidden" name="planet_id" value="'.$planet_id.'">
<input type="hidden" name="mode_id" value="1">
<table class="style_outer" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
  <tr>
    <td>
      <table class="style_inner" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
        <tr>
          <td width="50">&nbsp;</td>
          <td width="150"><b>'.constant($game->sprache("TEXT72")).'</b></td>
          <td width="110"><b>'.constant($game->sprache("TEXT73")).'</b></td>
          <td width="90">&nbsp;</td>
        </tr>
        ');

        if($free_planet) {
            $game->out('
        <tr>
          <td align="center"><img src="'.$planet_thumb.'" width="20" height="20" border="0"></td>
          <td>'.$planet['planet_name'].'</td>
          <td><i>'.constant($game->sprache("TEXT73a")).'</i></td>
          <td>
          '.( ($game->SITTING_MODE) ? '' : '<input type="image" src="'.$game->GFX_PATH.'tc_colo.gif" name="colo_submit" title="'.constant($game->sprache("TEXT74")).'" value="1" onClick="return document.srs_form.action = \''.parse_link('a=ship_actions&step=colo_setup\'"' ).'>&nbsp;' ).'
          <input type="image" src="'.$game->GFX_PATH.'tc_party.gif" name="survey_submit" title="'.constant($game->sprache("TEXT126")).'" value="1" onClick="return document.srs_form.action = \''.parse_link('a=ship_actions&step=survey_setup&user_id='.$planet['planet_owner']).'\';">
          </td>
        </tr>
            ');
        }
        else {
            $game->out('
        <tr>
          <td align="center"><img src="'.$planet_thumb.'" width="20" height="20" border="0"></td>
          <td>'.$planet['planet_name'].'</td>
          <td><a href="'.parse_link('a=stats&a2=viewplayer&id='.$planet_owner['user_id'].'">'.$planet_owner['user_name']).'</a></td>
          <td>
            <input type="image" src="'.$game->GFX_PATH.'tc_transport.gif" name="ptransport_submit" title="'.constant($game->sprache("TEXT75")).'" value="1" onClick="return document.srs_form.action = \''.parse_link('a=ship_fleets_loadingp&to&return_to='.urlencode('a=tactical_cartography&planet_id='.encode_planet_id($planet_id))).'\';">&nbsp;
            <input type="image" src="'.$game->GFX_PATH.'tc_party.gif" name="survey_submit" title="'.constant($game->sprache("TEXT126")).'" value="1" onClick="return document.srs_form.action = \''.parse_link('a=ship_actions&step=survey_setup&user_id='.$planet['planet_owner']).'\';">&nbsp;
            ');

            if(!$own_planet) {
                $game->out('
            '.( ($game->SITTING_MODE) ? '' : '<input type="image" src="'.$game->GFX_PATH.'tc_attack.gif" title="'.constant($game->sprache("TEXT76")).'" name="attack_submit" value="1" onClick="return document.srs_form.action = \''.parse_link('a=ship_actions&step=attack_setup&user_id='.$planet['planet_owner']).'\';">' ).'
                ');
            }

            $game->out('</td></tr>');
        }

        $sql = 'SELECT f.user_id, f.owner_id, SUM(f.n_ships) AS n_ships,
                       u.user_name, u.user_race, u2.user_name AS owner_name
                FROM (ship_fleets f)
                INNER JOIN (user u) ON u.user_id = f.user_id
                INNER JOIN (user u2) ON u2.user_id = f.owner_id
                WHERE f.planet_id = '.$planet_id.'
                GROUP BY f.owner_id,f.user_id';

        if(!$q_fleets = $db->query($sql)) {
            message(DATABASE_ERROR, 'Could not query fleets data for sensors');
        }

        while($fleet = $db->fetchrow($q_fleets)) {
            if($fleet['user_id'] == $sensor_details) {
                $sql = 'SELECT st.ship_torso, st.race,
                               COUNT(s.ship_id) AS n_ships
                        FROM (ship_templates st, ship_fleets f, ships s)
                        WHERE f.planet_id = '.$planet_id.' AND
                              f.user_id = '.$fleet['user_id'].' AND
                              s.template_id = st.id AND
                              s.fleet_id = f.fleet_id
                        GROUP BY st.ship_torso, st.race
                        ORDER BY st.race ASC';

                if(!$q_torsos = $db->query($sql)) {
                    message(DATABASE_ERROR, 'Could not query ship torso data');
                }

                $detail_link = '';
                $detail_symbol = '&lt;';

            }
            else {
                $detail_link = '&sensor_details='.$fleet['user_id'];
                $detail_symbol = '&gt;';
            }

            $game->out('
        <tr>
         <td align="center"><img src="'.$game->PLAIN_GFX_PATH.'fleet_'.$fleet['user_race'].'.gif" border="0"></td>
         <td><i>'.( ($fleet['n_ships'] == 1) ? constant($game->sprache("TEXT77")) : $fleet['n_ships'].' '.constant($game->sprache("TEXT78")) ).'</i> [<a href="'.parse_link('a=tactical_cartography&planet_id='.encode_planet_id($planet_id).$detail_link).'">'.$detail_symbol.'</a>]</td>
         <td><a href="'.parse_link('a=stats&a2=viewplayer&id='.($fleet['owner_id'] != $fleet['user_id'] && $fleet['owner_id'] == $game->player['user_id'] ? $fleet['owner_id'] : $fleet['user_id'])).'">'.($fleet['owner_id'] != $fleet['user_id'] && $fleet['owner_id'] == $game->player['user_id'] ? $fleet['owner_name'].' <i>('.$fleet['user_name'].')</i>' : $fleet['user_name']).'</a></td>
         <td>
            ');
            
            if($fleet['owner_id'] != $game->player['user_id']) {
                $game->out('<input type="image" src="'.$game->GFX_PATH.'tc_transport.gif" title="'.constant($game->sprache("TEXT75")).'" name="beam_submit" value="1" onClick="return document.srs_form.action = \''.parse_link('a=ship_fleets_loadingf&to_unit='.$fleet['user_id'].'&return_to='.urlencode('a=tactical_cartography&planet_id='.encode_planet_id($planet_id))).'\';">&nbsp;');
                
                if($planet['planet_owner'] != $fleet['owner_id']) {
                    $game->out('
         '.( ($game->SITTING_MODE) ? '' : '<input type="image" src="'.$game->GFX_PATH.'tc_attack.gif" title="'.constant($game->sprache("TEXT76")).'" name="attack_submit" value="1" onClick="return document.srs_form.action = \''.parse_link('a=ship_actions&step=attack_setup&user_id='.$fleet['user_id']).'\';">&nbsp;'));
                }
                $game->out('<input type="image" src="'.$game->GFX_PATH.'tc_analyze.gif" title="'.constant($game->sprache("TEXT135")).'" name="analyze_submit" value="1" onClick="document.srs_form.user_id.value = '.$fleet['user_id'].'; return document.srs_form.action = \''.parse_link('a=tactical_analyze').'\';">');
            }

            $game->out('</td></tr>');

            if($fleet['user_id'] == $sensor_details) {
                $game->out('
        <tr>
          <td>&nbsp;</td>
          <td colspan="3">
                ');

                while($torso = $db->fetchrow($q_torsos)) {
                    $game->out('&nbsp;&nbsp;&nbsp;&nbsp;'.$torso['n_ships'].' '.$SHIP_TORSO[$torso['race']][$torso['ship_torso']][29].' <i>('.$RACE_DATA[$torso['race']][0].'</i>)<br>');
                }

                $game->out('
          </td>
        </tr>
                ');
            }
        }

        if($n_own_fleets > 0) {
            $select_size = 3;

            if($n_own_fleets > 5) $select_size += ($n_own_fleets / 5);

            $game->out('
        <tr><td height="10"></td></tr>
        <tr>
          <td colspan="4" align="center">
          <table>
          <tr>
          <td width="90%">
          <select name="fleets[]" style="width: 200px;" size="'.$select_size.'" multiple="multiple">');
            
            while($fleet = $db->fetchrow($q_own_fleets)) {
                $game->out('<option value="'.$fleet['fleet_id'].'">'.$fleet['fleet_name'].' ('.$fleet['n_ships'].')</option>');
            }

            $game->out('
          </select></td>
          <td width="10%" valign="center">
            <input type="image" src="'.$game->GFX_PATH.'tc_analyze.gif" title="'.constant($game->sprache("TEXT136")).'" name="fleet_details" value="1" onClick="return document.srs_form.action = \''.parse_link('a=ship_fleets_display&pfleet_details').'\';">
          </td>
          </tr>
          </table></td>
        </tr>
        <tr><td height="5"></td></tr>
            ');
        }

        $game->out('</table></td></tr></table></form>');
    }

    $sql = 'SELECT move_id, move_finish
            FROM scheduler_shipmovement
            WHERE dest = '.$planet_id.' AND
                  user_id = '.$game->player['user_id'].' AND
                  move_status = 0';

    if(!$q_moves = $db->query($sql)) {
        message(DATABASE_ERROR, 'Could not query ship movement data');
    }

    $n_moves = 0;
    $moves_finish = array();

    while($move = $db->fetchrow($q_moves)) {
        $n_moves++;

        $moves_finish[] = $move['move_finish'];
    }

    if($n_moves == 1) {
        $ticks_left = $moves_finish[0] - $ACTUAL_TICK;
        if($ticks_left < 0) $ticks_left = 0;

        $game->out('
<br><br>
<table class="style_outer" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
  <tr>
    <td>
      <table class="style_inner" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
        <tr>
          <td>
            <b>1</b> '.constant($game->sprache("TEXT79")).'.&nbsp;[<a href="'.parse_link('a=tactical_moves&dest='.encode_planet_id($planet_id)).'">'.constant($game->sprache("TEXT80")).'</a>]<br><br>
            '.constant($game->sprache("TEXT81")).' <b id="timer2" title="time1_'.( ( $ticks_left * TICK_DURATION * 60) + $NEXT_TICK).'_type2_2">&nbsp;</b>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
        ');
    }
    elseif($n_moves > 1) {
        sort($moves_finish, SORT_NUMERIC);

        $ticks_left = $moves_finish[0] - $ACTUAL_TICK;
        if($ticks_left < 1) $ticks_left = 1;

        $game->out('
<br><br>
<table class="style_outer" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
  <tr>
    <td>
      <table class="style_inner" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
        <tr>
          <td>
            <b>'.$n_moves.'</b> '.constant($game->sprache("TEXT79")).'.&nbsp;[<a href="'.parse_link('a=tactical_moves&dest='.encode_planet_id($planet_id)).'">'.constant($game->sprache("TEXT80")).'</a>]<br><br>
            '.constant($game->sprache("TEXT82")).' <b id="timer2" title="time1_'.( $ticks_left * TICK_DURATION * 60).'_type2_2">&nbsp;</b>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
        ');
    }
        
    if($planet_id != $game->planet['planet_id']) {
        /*
        $sql = 'SELECT fleet_id, fleet_name, n_ships, system_id, system_global_x, system_global_y
                FROM ship_fleets
                INNER JOIN planets USING (planet_id)
                INNER JOIN starsystems USING (system_id)                
                WHERE user_id = '.$game->player['user_id'].' AND
                      planet_id <> 0 AND
                      n_ships > 0
                ORDER BY fleet_name';
         * 
         */

        $sql = 'SELECT ship_fleets.fleet_id, fleet_name, MIN(value_10) as speed, n_ships, starsystems.system_id, system_global_x, system_global_y
                FROM ship_fleets
                INNER JOIN ships ON ship_fleets.fleet_id = ships.fleet_id
                INNER JOIN ship_templates ON ships.template_id = ship_templates.id
                INNER JOIN planets USING (planet_id)
                INNER JOIN starsystems ON planets.system_id = starsystems.system_id            
                WHERE ship_fleets.owner_id = '.$game->player['user_id'].' AND
                      planet_id <> 0 AND
                      n_ships > 0
                GROUP BY fleet_id
                ORDER BY fleet_name';
        
        $q_fleets = $db->queryrowset($sql);

        $n_fleets = $db->num_rows();

        if($n_fleets > 0) {
            $game->out('
<br><br>
<table class="style_outer" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
  <tr>
    <td>
      <table class="style_inner" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
        <form name="send_form" method="post" action="'.parse_link('a=ship_send').'">
        <tr>
          <td colspan="2" align="center"><b>'.constant($game->sprache("TEXT83")).'</b><br><br>
        </tr>
        <tr>
          <td>
            <select name="fleets[]" style="width: 390px;" multiple="multiple" size="5">
            ');
            
            foreach($q_fleets AS $fleet) {
                if ($planet['system_id']==$fleet['system_id']) $dist=0;
                else
                {
                    $dist = get_distance(array($planet['system_global_x'], $planet['system_global_y']), array($fleet['system_global_x'],$fleet['system_global_y']));
                    $velocity = warpf($fleet['speed']);
                    $timedist = ceil( ( ($dist / $velocity) / TICK_DURATION ) );                    
                }
                $game->out('<option value="'.$fleet['fleet_id'].'">'.$fleet['fleet_name'].' ('.$fleet['n_ships'].' '.constant($game->sprache("TEXT78")).'; '.constant($game->sprache("TEXT133")).($dist != 0 ? number_format($dist, 0, ',', '.').' A.U. => '.Zeit($timedist*TICK_DURATION).'@W'.$fleet['speed'] : constant($game->sprache("TEXT132")).' => 9m' ).') </option>');
            }

            $game->out('
            </select>
          </td>
        </tr>
        <tr>
          <td width="150" align="center" valign="middle">
            <input type="submit" class="button" name="submit" value="'.constant($game->sprache("TEXT84")).'">
          </td>
        </tr>
        <input type="hidden" name="dest" value="'.encode_planet_id($planet_id).'">
        </form>
      </table>
    </td>
  </tr>
</table>
            ');
        }
    }

    display_cartography_jump();

    if($game->planet['planet_id'] != $planet_id)
    if($planet_is_known && $own_planet) display_ferengi_transfer($planet['planet_id'],$planet['system_id'],$planet['system_global_x'],$planet['system_global_y'],$planet['building_11']);

}
elseif( (!empty($_GET['system_id'])) || (!empty($_GET['sector_id'])) || (!empty($_GET['quadrant_id'])) || (isset($_GET['galaxy'])) ) {
    include_once('include/libs/maps.php');

    $maps = new maps();

    $nav_html = $img_html = $legend_html = '';

    $numbers = $letters = array('&nbsp;', '&nbsp;', '&nbsp;', '&nbsp;', '&nbsp;', '&nbsp;', '&nbsp;');

    if(!empty($_GET['system_id'])) {
        if(is_numeric($_GET['system_id'])) {
            die(constant($game->sprache("TEXT59")));
        }
        else {
            $system_id = (int)decode_system_id($_GET['system_id']);
        }

        $sql = 'SELECT system_name, sector_id, system_closed, system_owner
                FROM starsystems
                WHERE system_id = '.$system_id;

        if(($system = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query starsystem data');
        }

        if(empty($system['sector_id'])) {
            message(NOTICE, constant($game->sprache("TEXT41")).$system_id.constant($game->sprache("TEXT42")));
        }
        
        $sql = 'SELECT timestamp FROM starsystems_details WHERE system_id = '.$system_id.' AND user_id = '.$game->player['user_id'].' AND log_code = 0';
        
        if(($system_details = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query starsystem data');
        }
        
        $system_is_known = false;
        if($system['system_owner'] == $game->player['user_id'] || !empty($system_details['timestamp'])) {$system_is_known = true;}
        
        $sql = 'SELECT timestamp FROM starsystems_details WHERE system_id = '.$system_id.' AND user_id = '.$game->player['user_id'].' AND log_code = 100';
        
        if(($system_details2 = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query starsystem data');
        }

        $system_is_challenged = false;
        if($system_details2['timestamp']) {$system_is_challenged = true;}
        
        $quadrant_id = $game->get_quadrant($system['sector_id']);

        $nav_html = '<a href="'.parse_link('a=tactical_cartography&galaxy').'">'.constant($game->sprache("TEXT60")).'</a> -> '.
                    '<a href="'.parse_link('a=tactical_cartography&quadrant_id='.$quadrant_id).'">'.$QUADRANT_NAME[$quadrant_id].'</a> -> '.
                    '<a href="'.parse_link('a=tactical_cartography&sector_id='.$system['sector_id']).'">'.constant($game->sprache("TEXT61")).' '.$game->get_sector_name($system['sector_id']).'</a> -> '.$system['system_name'];

        $map_html = $maps->create_system_map($system_id);

        $img_html = '<img border="0" src="./maps/images/cache/'.md5($game->player['user_id']).'.png" usemap="#system_map">';

        $legend_html = constant($game->sprache("TEXT85")).
                       constant($game->sprache("TEXT86")).
                       constant($game->sprache("TEXT87")).
                       constant($game->sprache("TEXT131")).constant($game->sprache("TEXT134")).
                       constant($game->sprache("TEXT88")).
                       constant($game->sprache("TEXT89")).
                       constant($game->sprache("TEXT90")).'<br>';

        set_tcartography_remind(3, $system_id);
    }
    elseif(!empty($_GET['sector_id'])) {
        $sector_id = (int)$_GET['sector_id'];

        $quadrant_id = $game->get_quadrant($sector_id);

        $numbers = array('1', '2', '3', '4', '5', '6', '7', '8', '9');
        $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');

        $nav_html = '<a href="'.parse_link('a=tactical_cartography&galaxy').'">'.constant($game->sprache("TEXT60")).'</a> -> '.
                    '<a href="'.parse_link('a=tactical_cartography&quadrant_id='.$quadrant_id).'">'.$QUADRANT_NAME[$quadrant_id].'</a> -> '.constant($game->sprache("TEXT61")).' '.$game->get_sector_name($sector_id);

        $map_html = $maps->create_sector_map($sector_id);

        $img_html = '<img border="0" src="./maps/images/cache/'.md5($game->player['user_id']).'.png" usemap="#sector_map">';

        set_tcartography_remind(2, $sector_id);
    }
    elseif(!empty($_GET['quadrant_id'])) {
        $quadrant_id = (int)$_GET['quadrant_id'];

        //$maps->create_quadrant_map($quadrant_id);

        extract($game->get_quadrant_range($quadrant_id));

        $nav_html = '<a href="'.parse_link('a=tactical_cartography&galaxy').'">'.constant($game->sprache("TEXT60")).'</a> -> '.$QUADRANT_NAME[$quadrant_id];

        $img_html = '<img border="0" src="maps/images/quadrant_'.$quadrant_id.'.jpg" usemap="#quadrant_map">';

        $mapname = 'maps/area/quadrant_'.$quadrant_id.'.html';
        $map_html = implode('', file($mapname));

        set_tcartography_remind(1, $quadrant_id);
    }
    elseif(isset($_GET['galaxy'])) {
        $nav_html = constant($game->sprache("TEXT60"));

        $img_html = '<img border="0" src="maps/images/galaxy.jpg" usemap="#galaxy_map">';

        //$maps->create_galaxy_map();

        $numbers[0] = 1;
        $numbers[8] = 18;
        
        $letters[0] = 'A';
        $letters[8] = 'R';

        $map_html = implode('', file('maps/area/galaxy.html'));

        set_tcartography_remind(0, 0);
    }

    $game->out('
<span class="caption">'.constant($game->sprache("TEXT0")).'</span><br><br>[<b>'.constant($game->sprache("TEXT1")).'</b>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_moves').'">'.constant($game->sprache("TEXT2")).'</a>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_player').'">'.constant($game->sprache("TEXT3")).'</a>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_kolo').'">'.constant($game->sprache("TEXT4")).'</a>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_known').'">'.constant($game->sprache("TEXT4a")).'</a>]&nbsp;&nbsp;
            [<a href="'.parse_link('a=tactical_sensors').'">'.constant($game->sprache("TEXT5")).'</a>]&nbsp;&nbsp;                
            [<a href="'.parse_link('a=tactical_search').'">'.constant($game->sprache("TEXT5a")).'</a>]<br><br>

<table class="style_outer" width="450" align="center" border="0" cellpadding="2" cellspacing="2">
  <tr>
    <td>
      <table class="style_inner" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
        <tr>
          <td width="20">&nbsp;</td>
          <td width="380">'.$nav_html.'</td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br>
<table align="center" border="0">
  <tr>
    <td>&nbsp;</td>
    <td width="38" align="center">'.$numbers[0].'</td>
    <td width="38" align="center">'.$numbers[1].'</td>
    <td width="38" align="center">'.$numbers[2].'</td>
    <td width="38" align="center">'.$numbers[3].'</td>
    <td width="38" align="center">'.$numbers[4].'</td>
    <td width="38" align="center">'.$numbers[5].'</td>
    <td width="38" align="center">'.$numbers[6].'</td>
    <td width="38" align="center">'.$numbers[7].'</td>
    <td width="37" align="center">'.$numbers[8].'</td>
  </tr>
  <tr>
    <td height="40" align="right" valign="middle">'.$letters[0].'</td>
    <td width="368" height="368" colspan="9" rowspan="9">'.$img_html.'</td>
  </tr>
  <tr><td height="40" align="right" valign="middle">'.$letters[1].'</td></tr>
  <tr><td height="40" align="right" valign="middle">'.$letters[2].'</td></tr>
  <tr><td height="40" align="right" valign="middle">'.$letters[3].'</td></tr>
  <tr><td height="40" align="right" valign="middle">'.$letters[4].'</td></tr>
  <tr><td height="40" align="right" valign="middle">'.$letters[5].'</td></tr>
  <tr><td height="40" align="right" valign="middle">'.$letters[6].'</td></tr>
  <tr><td height="40" align="right" valign="middle">'.$letters[7].'</td></tr>
  <tr><td height="40" align="right" valign="middle">'.$letters[8].'</td></tr>
</table>');

if($system_is_known && $system_id > 0 && !$system['system_closed']) {
    $game->out('    
    <br>
    <form name="claimform" method="POST" action="'.parse_link('a=tactical_cartography').'">
    <table class="style_outer" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
    <tr>
        <td>
            <table class="style_inner" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
                <tr>
                    <td align="center">
                        <input type="hidden" name="claimvalue" value="'.encode_system_id((int)$system_id).'">
                        <input class="button" type="submit" name="claim" value="Richiedi il sistema privato">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    </table></form>');    
}

if(($system_is_known && !$system_is_challenged) && $system_id > 0 && $system['system_closed'] && $system['system_owner'] != $game->player['user_id']) {
    $game->out('    
    <br>
    <form method="POST" action="'.parse_link('a=tactical_cartography').'">
    <table class="style_outer" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
    <tr>
        <td>
            <table class="style_inner" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
                <tr>
                    <td align="center">
                        <input type="hidden" name="pretendvalue" value="'.encode_system_id((int)$system_id).'">
                        <input class="button" type="submit" value="!!! Reclama questo sistema privato per te !!!">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    </table></form>');    
}

$game->out('
<br>
<table class="style_outer" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
  <tr>
    <td>
      <table class="style_inner" width="400" align="center" border="0" cellpadding="2" cellspacing="2">
        <tr>
          <td width="20">&nbsp;</td>
          <td width="360">'.$legend_html.$map_html.constant($game->sprache("TEXT91")).'</font></td>
          <td width="20">&nbsp;</td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<br>
    ');

    display_cartography_jump();
}
else {
    if(empty($tc_views_map[$game->player['last_tcartography_view']])) {
        $sql = 'UPDATE user
                SET last_tcartography_view = 0,
                    last_tcartography_id = 0
                WHERE user_id = '.$game->player['user_id'];

        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not update user last tcartography data');
        }

        redirect('a=tactical_cartography&galaxy');
    }
    
    // workaround
    if($game->player['last_tcartography_view'] == 3) $game->player['last_tcartography_id'] = encode_system_id((int)$game->player['last_tcartography_id']);
    if($game->player['last_tcartography_view'] == 4) $game->player['last_tcartography_id'] = encode_planet_id((int)$game->player['last_tcartography_id']);

    redirect('a=tactical_cartography&'.sprintf($tc_views_map[$game->player['last_tcartography_view']], $game->player['last_tcartography_id']));
}

?>
