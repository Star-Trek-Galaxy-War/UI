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

$game->init_player();
$game->out('<span class="caption">'.constant($game->sprache("TEXT0")).'</span><br><br>');


if(empty($_REQUEST['step'])) {
    message(GENERAL, constant($game->sprache("TEXT1")), '$_REQUEST[\'step\'] = empty');
}

$step = (!empty($_POST['step'])) ? $_POST['step'] : $_GET['step'];

// #############################################################################
// There is a need for fleets over ship_actions

if(empty($_POST['fleets'])) {
    message(NOTICE, constant($game->sprache("TEXT2")));
}


// #############################################################################
// The fleets IDs "clean" (integer cast against SQL exploits)

$fleet_ids = array();

for($i = 0; $i < count($_POST['fleets']); ++$i) {
    $_temp = (int)$_POST['fleets'][$i];

    if(!empty($_temp)) {
        $fleet_ids[] = $_temp;
    }
}


// #############################################################################
// War fleets has a valid ID?

if(empty($fleet_ids)) {
    message(NOTICE, constant($game->sprache("TEXT3")));
}

$sql = 'SELECT *
        FROM ship_fleets
        WHERE fleet_id IN ('.implode(',', $fleet_ids).')';
        
if(!$q_fleets = $db->query($sql)) {
    message(DATABASE_ERROR, 'Could not query fleet data');
}

$fleets = array($db->fetchrow($q_fleets));
$fleet_ids = array($fleets[0]['fleet_id']);

if(empty($fleets[0])) {
    message(NOTICE, constant($game->sprache("TEXT4")));
}

$planet_id = (int)$fleets[0]['planet_id'];

if($planet_id == 0) {
    message(NOTICE, constant($game->sprache("TEXT5")));
}

while($_temp = $db->fetchrow($q_fleets)) {
    if($_temp['planet_id'] == $planet_id) {
        $fleet_ids[] = $_temp['fleet_id'];
        $fleets[] = $_temp;
    }
}

$n_fleets = count($fleets);

if($n_fleets == 0) {
    message(NOTICE, constant($game->sprache("TEXT6")));
}

$is_undiscloused_id = false;
$undisclosed_id = 0;

if($n_fleets == 1 && $fleets[0]['user_id'] != $fleets[0]['owner_id']) {
    $is_undiscloused_id = true;
    $undisclosed_id = $fleets[0]['user_id'];
}

$fleet_ids_str = implode(',', $fleet_ids);


// #############################################################################
// Bring planet data

if($planet_id == $game->planet['planet_id']) {
    $planet = $game->planet;
    
    $planet['user_id'] = $game->player['user_id'];
    $planet['user_name'] = $game->player['user_name'];
    $planet['user_attack_protection'] = $game->player['user_attack_protection'];
    $planet['user_vacation_start'] = $game->player['user_vacation_start'];
    $planet['user_vacation_end'] = $game->player['user_vacation_end'];

    // Player data must not be accepted because they are not displayed / used
}
else {
    $sql = 'SELECT p.planet_id, p.planet_type, p.planet_name, p.system_id, p.sector_id, p.planet_distance_id,
                   s.system_x, s.system_y,
                   u.user_id, u.user_name, u.user_attack_protection, u.user_vacation_start, u.user_vacation_end
            FROM (planets p, starsystems s)
            LEFT JOIN (user u) ON u.user_id = p.planet_owner
            WHERE p.planet_id = '.$planet_id.' AND
                  s.system_id = p.system_id';
                  
    if(($planet = $db->queryrow($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query start planet data');
    }

    if(empty($planet['planet_id'])) {
        message(NOTICE, constant($game->sprache("TEXT7")));
    }
    
    if(empty($planet['user_id'])) {
        $planet['user_id'] = 0;
        $planet['user_name'] = '';
        $planet['user_attack_protection'] = 0;
        $planet['user_vacation_start'] = 0;
        $planet['user_vacation_end'] = 0;
    }
}

if(!$game->is_action_allowed($planet['planet_id']))
{
    message(NOTICE, constant($game->sprache("TEXT87")));
}

// #############################################################################
// Which ship classes are they?
// (for command opportunities)

$sql = 'SELECT st.race, st.ship_torso, st.value_10 AS warp_speed
        FROM (ships s, ship_templates st)
        WHERE s.fleet_id IN ('.implode(',', $fleet_ids).') AND
              st.id = s.template_id';

if(!$q_stpls = $db->query($sql)) {
    message(DATABASE_ERROR, 'Could not query ship template data');
}

$in_scout = $in_transporter = $in_colo = $in_orb = false;

while($_temp = $db->fetchrow($q_stpls)) {
    $race_composition[$_temp['race']]++;
            
    if($_temp['ship_torso'] == SHIP_TYPE_SCOUT) {
        $in_scout = true;
        continue;
    }

    if($_temp['ship_torso'] == SHIP_TYPE_TRANSPORTER) {
        $in_transporter = true;
        continue;
    }

    if($_temp['ship_torso'] == SHIP_TYPE_COLO) {
        $in_colo = true;
        continue;
    }

    if($_temp['ship_torso'] == SHIP_TYPE_ORB) {
        $in_orb = true;
        continue;
    }
}

// How many ships are they?
$sql = 'SELECT n_ships AS ship_count'
        . ' FROM ship_fleets'
        . ' WHERE fleet_id IN ('.implode(',', $fleet_ids).')';

if(!$q_snbr = $db->query($sql)) {
    message(DATABASE_ERROR, 'Could not query ships number data');
}

while($_temp = $db->fetchrow($q_snbr)) {
    $ship_number += $_temp['ship_count'];
    $explore_fleet = ($ship_number == 1) ? true : false;
}

if($explore_fleet) {
    $sql = 'SELECT awayteam, unit_1, unit_2, unit_3, unit_4, unit_5, unit_6, min_unit_1, min_unit_2, min_unit_3, min_unit_4, ship_name, name '
            . ' FROM ships'
            . ' INNER JOIN ship_templates ON ships.template_id = ship_templates.id'
            . ' WHERE fleet_id IN ('.implode(',', $fleet_ids).')';
    
    if(!$q_s_stats = $db->queryrow($sql)) {
        message(DATABASE_ERROR, 'Could not query ship data');
    }    
}



// #############################################################################
// A few settings set

$user_id = (!empty($_GET['user_id'])) ? (int)$_GET['user_id'] : 0;

$free_planet = ($planet['user_id'] == 0) ? true : false;
$own_planet = ($game->player['user_id'] == $planet['user_id']) ? true : false;

$planet_vacation = ($planet['user_vacation_start'] <= $ACTUAL_TICK) && ($planet['user_vacation_end'] > $ACTUAL_TICK) ? true : false;

$starter_atkptc = ($game->player['user_attack_protection'] > $ACTUAL_TICK) ? true : false;

if($in_orb){
  message(NOTICE, constant($game->sprache("TEXT8")));
}

$fleet_race_trail = -1;
$fleet_race_count = 0;

foreach ($race_composition AS $key => $race_comp_item) {
    if($fleet_race_count < $race_comp_item) {
        $fleet_race_count = $race_comp_item;
        $fleet_race_trail = $key;
    }
}

if(($fleet_race_count*100/$ship_number) < 51.0) {
    $fleet_race_trail = -1;
}

// #############################################################################
// If the target player isn't the owner of the planet (and a target users there),
// fetch its data

if(!empty($user_id)) {
    if($planet['user_id'] != $user_id) {
        $sql = 'SELECT f.fleet_id,
                       u.user_id, u.user_name, u.user_attack_protection, u.user_vacation_start, u.user_vacation_end
                FROM (ship_fleets f)
                INNER JOIN (user u) ON u.user_id = f.user_id
                WHERE f.planet_id = '.$planet_id.' AND
                      f.user_id = '.$user_id;

        if(($fleet = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query dest user fleets data');
        }

        if(empty($fleet['user_id'])) {
            message(NOTICE, constant($game->sprache("TEXT9")));
        }
        
        $dest_atkptc = ($fleet['user_attack_protection'] > $ACTUAL_TICK) ? true : false;
        
        $planetary_dest = false;
        
        $other_party = &$fleet;
    }
}

if(!isset($dest_atkptc)) {
    $dest_atkptc = ($planet['user_attack_protection'] > $ACTUAL_TICK) ? true : false;
    $planetary_dest = true;
    
    $other_party = &$planet;
    
    $user_id = $planet['user_id'];
    
    if($planet_vacation) {
        message(NOTICE, constant($game->sprache("TEXT10")));
    }
}

$atktpc_present = ($starter_atkptc || $dest_atkptc) ? true : false;



switch($step) {
    case 'surrender_exec':
        if($game->SITTING_MODE){
            message(NOTICE, constant($game->sprache("TEXT30")));
        }

        if($atkptc_present) {
            message(NOTICE, constant($game->sprache("TEXT31")));
        }

        // #############################################################################
        // Ships hand over

        $sql = 'UPDATE ships
                SET user_id = '.ORION_USERID.'
                WHERE fleet_id iN ('.$fleet_ids_str.')';
                
        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not update ships owner data');
        }

        $sql = 'UPDATE ship_fleets
                SET user_id = '.ORION_USERID.', owner_id = '.ORION_USERID.', fleet_name = "Pirate Fleet", alert_phase = '.ALERT_PHASE_RED.'
                WHERE fleet_id IN ('.$fleet_ids_str.')';
                
        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not update fleets owner data');
        }

        /*
        // #############################################################################
        // Logbook Entry (sponsored by action_23.php)

        $sql = 'SELECT st.name, st.ship_torso, st.race,
                       COUNT(s.ship_id) AS n_ships
                FROM (ship_templates st, ship_fleets f, ships s)
                WHERE f.fleet_id IN ('.$fleet_ids_str.') AND
                      s.template_id = st.id AND
                      s.fleet_id = f.fleet_id
                GROUP BY st.id
                ORDER BY st.ship_torso ASC, st.race ASC';
                
        if(!$q_stpls = $db->query($sql)) {
            message(DATABASE_ERROR, 'Could not query logbook data');
        }

        $log_data = array(23, $game->player['user_id'], $planet_id, $planet['planet_name'], ORION_USERID, 0, 0, 0, array());

        while($stpl = $db->fetchrow($q_stpls)) {
            $log_data[8][] = array($stpl['name'], $stpl['ship_torso'], $stpl['race'], $stpl['n_ships']);
        }

        add_logbook_entry($user_id, LOGBOOK_TACTICAL, constant($game->sprache("TEXT15")).' '.$game->player['user_name'].' '.constant($game->sprache("TEXT16")), $log_data);
        */

        redirect('a=tactical_cartography&planet_id='.encode_planet_id($planet_id));
    break;

    case 'surrender_setup':
        if($game->SITTING_MODE){
            message(NOTICE, constant($game->sprache("TEXT30")));
        }

        if($atkptc_present) {
            message(NOTICE, constant($game->sprache("TEXT31")));
        }
                
        $game->out('
<table class="style_outer" align="center" border="0" cellpadding="2" cellspacing="2" width="450">
  <tr>
    <td>
      <table class="style_inner" align="center" border="0" cellpadding="2" cellspacing="2" width="450">
        <form name="send_form" method="post" action="'.parse_link('a=ship_actions&step=surrender_exec').'" onSubmit="return document.send_form.submit.disabled = true;">
        ');

        $fleet_option_html = '';

        for($i = 0; $i < $n_fleets; ++$i) {
            $fleet_option_html .= '<option>'.$fleets[$i]['fleet_name'].' ('.$fleets[$i]['n_ships'].')</option>';
            $game->out('<input type="hidden" name="fleets[]" value="'.$fleets[$i]['fleet_id'].'">');
        }

        $game->out('
        <tr>
          <td>
            '.constant($game->sprache("TEXT17")).' <a href="'.parse_link('a=tactical_cartography&planet_id='.encode_planet_id($planet_id)).'"><b>'.$planet['planet_name'].'</b></a> ('.$game->get_sector_name($planet['sector_id']).':'.$game->get_system_cname($planet['system_x'], $planet['system_y']).':'.($planet['planet_distance_id'] + 1).')'.( ($planet['user_id'] != $game->player['user_id']) ? ' '.constant($game->sprache("TEXT18")).' <a href="'.parse_link('a=stats&a2=viewplayer&id='.$planet['user_id']).'"><b>'.$planet['user_name'].'</b></a>' : '' ).'<br><br>
            '.constant($game->sprache("TEXT19")).' <select style="width: 200px;">'.$fleet_option_html.'</select><br><br>
            '.constant($game->sprache("TEXT20")).constant($game->sprache("TEXT21")).constant($game->sprache("TEXT23")));

        if($in_transporter) {
            $resource_1 = $resource_2 = $resource_3 = $resource_4 = $unit_1 = $unit_2 = $unit_3 = $unit_4 = $unit_5 = $unit_6 = 0;

            for($i = 0; $i < $n_fleets; ++$i) {
                $resource_1 += $fleets[$i]['resource_1'];
                $resource_2 += $fleets[$i]['resource_2'];
                $resource_3 += $fleets[$i]['resource_3'];
                $resource_4 += $fleets[$i]['resource_4'];

                $unit_1 += $fleets[$i]['unit_1'];
                $unit_2 += $fleets[$i]['unit_2'];
                $unit_3 += $fleets[$i]['unit_3'];
                $unit_4 += $fleets[$i]['unit_4'];
                $unit_5 += $fleets[$i]['unit_5'];
                $unit_6 += $fleets[$i]['unit_6'];
            }

            $n_resources = $resource_1 + $resource_2 + $resource_3 + $resource_4;
            $n_units = $unit_1 + $unit_2 + $unit_3 + $unit_4 + $unit_5 + $unit_6;

            if($n_resources > 0) {
                if($resource_1 > 0) $game->out('<br>'.constant($game->sprache("TEXT24")).' <b>'.$resource_1.'</b>');
                if($resource_2 > 0) $game->out('<br>'.constant($game->sprache("TEXT25")).' <b>'.$resource_2.'</b>');
                if($resource_3 > 0) $game->out('<br>'.constant($game->sprache("TEXT26")).' <b>'.$resource_3.'</b>');
                if($resource_4 > 0) $game->out('<br>'.constant($game->sprache("TEXT27")).' <b>'.$resource_4.'</b>');
                $game->out('<br>');
            }

            if($n_units > 0) {
                if($unit_1 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][0].': <b>'.$unit_1.'</b>');
                if($unit_2 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][1].': <b>'.$unit_2.'</b>');
                if($unit_3 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][2].': <b>'.$unit_3.'</b>');
                if($unit_4 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][3].': <b>'.$unit_4.'</b>');
                if($unit_5 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][4].': <b>'.$unit_5.'</b>');
                if($unit_6 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][5].': <b>'.$unit_6.'</b>');
                $game->out('<br>');
            }
        }

        $game->out('
            <br>
            <center><input class="button" type="button" name="cancel" value="'.constant($game->sprache("TEXT28")).'" onClick="return window.history.back();">&nbsp;&nbsp;<input class="button" type="submit" name="submit" onmouseover="return overlib(\''.constant($game->sprache("TEXT93")).'\', CAPTION, \''.constant($game->sprache("TEXT29")).'\', WIDTH, 400, '.OVERLIB_STANDARD.');" onmouseout="return nd();" onClick="return confirm(\''.constant($game->sprache("TEXT94")).'\')" value="'.constant($game->sprache("TEXT29")).'"></center>
          </td>
        </tr>
        </form>
      </table>
    </td>
  </tr>
</table>
      ');
    break;

    case 'survey_exec':
        switch($_POST['mission']) {
            case 'survey':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 1);
                $action_code = 26;
                $action_data = array(0);
            break;
            case 'first_contact':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(0);
            break;
            case 'recon':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 1);
                $action_code = 27;
                $action_data = array(1);
            break;
            case 'diplomatic':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(2);
            break;
            case 'techsup':
                $tech_type = (int)$_POST['targettech'];
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(3,$tech_type);
            break;
            case 'milsup':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 80);
                $action_code = 27;
                $action_data = array(4);
            break;
            case 'rescue':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 1);
                $action_code = 27;
                $action_data = array(5);
            break;            
            case 'hiro_ambush':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,0);            
            break;
            case 'kazon_ambush':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,1);            
            break;        
            case 'fede_presidio':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,10);            
            break;
            case 'fede_diplo':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,100);            
            break;
            case 'romu_presidio':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,20);            
            break;
            case 'romu_diplo':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,110);            
            break;
            case 'klin_presidio':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,30);            
            break;
            case 'klin_diplo':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,120);            
            break;
            case 'card_presidio':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,40);            
            break;
            case 'card_diplo':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,130);            
            break;
            /*
            case 'dom_presidio':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 5);
                $action_code = 27;
                $action_data = array(10,50);            
            break;
             * 
             */
            case 'dom_diplo':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,140);            
            break;
            case 'fere_presidio':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,50);                
            break;
            case 'fere_diplo':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,150);            
            break;        
            case 'training':
                $duration = ($game->player['user_auth_level'] == STGC_DEVELOPER ? 1 : 2);
                $action_code = 27;
                $action_data = array(10,300);            
            break;
            default:
                message(GENERAL, constant($game->sprache("TEXT1")));
            break;
        }

        $sql = 'INSERT INTO scheduler_shipmovement (user_id, owner_id, move_status, move_exec_started, race_trail, start, dest, total_distance, remaining_distance, tick_speed, move_begin, move_finish, n_ships, action_code, action_data)
                VALUES ('.(!$is_undiscloused_id ? $game->player['user_id'] : $undisclosed_id).', '.$game->player['user_id'].', 0, 0, '.$fleet_race_trail.', '.$planet_id.', '.$planet_id.', 0, 0, 0, '.$ACTUAL_TICK.', '.($ACTUAL_TICK + $duration).', 1, '.$action_code.', "'.serialize($action_data).'")';
                
        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not insert new movement data');
        }

        $new_move_id = $db->insert_id();

        if(empty($new_move_id)) {
            message(GENERAL, 'Could not obtain new move id', '$new_move_id = empty');
        }

        $sql = 'UPDATE ship_fleets SET planet_id = 0, move_id = '.$new_move_id.' WHERE fleet_id IN ('.implode(',', $fleet_ids).')';

        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not update fleets location data');
        }
        // =====>


        redirect('a=tactical_cartography&planet_id='.encode_planet_id($planet_id));

    break;


    case 'survey_setup':
        if($game->SITTING_MODE){
            message(NOTICE, constant($game->sprache("TEXT30")));
        }

        if($atkptc_present) {
            message(NOTICE, constant($game->sprache("TEXT31")));
        }

        if(!$explore_fleet) {
            message(NOTICE, constant($game->sprache("TEXT62")));
        }

        $sql = 'SELECT ship_id FROM ships USE INDEX (fleet_id) WHERE fleet_id IN ('.implode(',', $fleet_ids).')';

        if(($myship = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query real n_ships data');
        }

        global $RACE_DATA;

        include_once('include/libs/moves.php');


        $game->out('
<table class="style_outer" align="center" border="0" cellpadding="2" cellspacing="2" width="450">
  <tr>
    <td>
      <table class="style_inner" align="center" border="0" rules="rows" cellpadding="2" cellspacing="2" width="450">
        <tr>
          <td align="left" valign="top" width="100">
            <b>Ship</b>      :<br>
            <b>Away Team</b> : 
          </td>
          <td align="left" valign="top">
          '.(empty($q_s_stats['ship_name']) ? '<b><i>&#171;'.$q_s_stats['name'].'&#187;</i></b>' : '<b>'.$q_s_stats['ship_name'].'</b>').'<br>');
        if(intval($q_s_stats['awayteam']) == 0) {
           $game->out('<b>Away Team not available</b>');
        }
        else {
            $game->out('
            LIV.'.(intval($q_s_stats['awayteam'])).' <img src='.$game->GFX_PATH.'menu_unit1_small.gif>'.($q_s_stats['unit_1'] - $q_s_stats['min_unit_1']).'&nbsp;&nbsp;<img src='.$game->GFX_PATH.'menu_unit2_small.gif>'.($q_s_stats['unit_2'] - $q_s_stats['min_unit_2']).'&nbsp;&nbsp;<img src='.$game->GFX_PATH.'menu_unit3_small.gif>'.($q_s_stats['unit_3'] - $q_s_stats['min_unit_3']).'&nbsp;&nbsp;<img src='.$game->GFX_PATH.'menu_unit4_small.gif>'.($q_s_stats['unit_4'] - $q_s_stats['min_unit_4']).'
            &nbsp;&nbsp;&nbsp;<img src='.$game->GFX_PATH.'menu_unit5_small.gif>'.$q_s_stats['unit_5'].'&nbsp;&nbsp;<img src='.$game->GFX_PATH.'menu_unit6_small.gif>'.$q_s_stats['unit_6']);
        }
        $game->out('
          </td>
        </tr>      
      </table>
      <br><br>                  
     <table class="style_inner" align="center" border="0" rules="rows" cellpadding="2" cellspacing="2" width="450">
      <form name="send_form" method="post" action="'.parse_link('a=ship_actions&step=survey_exec').'" onSubmit="return document.send_form.submit.disabled = true;">');
        
        // Geological analysis
        $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="survey" checked="checked"></td>
          <td>'.constant($game->sprache("TEXT58")).'</td>
        </tr>');

        /**
         * Missions for Settler's planets
         */
        if($planet['user_id'] == INDEPENDENT_USERID) {
            // Recon
            $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="recon"></td>
          <td>'.constant($game->sprache("TEXT65")).'</td>
        </tr>');
            // Rescue            
            $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="rescue"></td>
          <td>'.constant($game->sprache("TEXT84")).'</td>
        </tr>');
            // First Contact
            $requirement_text = constant($game->sprache("TEXT63"));
            $results = meet_mission_req($myship['ship_id'], 5, 0, 1, 1, 0, 0, 0, 1);
            $all_clear = (array_sum($results) == 0 ? true : false);
            if($all_clear) 
            {
                $requirement_text .= requirements_str_ok(5, 0, 1, 1, 0, 0, 0, 1);
            }
            else
            {
                $requirement_text .= requirements_str_bad($results, 5, 0, 1, 1, 0, 0, 0, 1);
            }

            $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="first_contact" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT61")).'<br><br>'.$requirement_text.'</td>
        </tr>');
            // Diplomatic relationships
            $requirement_text = constant($game->sprache("TEXT63"));
            $results = meet_mission_req($myship['ship_id'], 0, 5, 5, 2, 0, 0, 0, 1);
            $all_clear = (array_sum($results) == 0 ? true : false);
            if($all_clear) 
            {
                $requirement_text .= requirements_str_ok(0, 5, 5, 2, 0, 0, 0, 1);
            }
            else
            {
                $requirement_text .= requirements_str_bad($results, 0, 5, 5, 2, 0, 0, 0, 1);
            }
            $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="diplomatic" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT64")).'<br><br>'.$requirement_text.'</td>
        </tr>');
            // Tech Support Mission
            $requirement_text = constant($game->sprache("TEXT63"));
            $results = meet_mission_req($myship['ship_id'], 15, 10, 5, 2, 5, 0, 0, 1);
            $all_clear = (array_sum($results) == 0 ? true : false);
            if($all_clear) 
            {
                $requirement_text .= requirements_str_ok(15, 10, 5, 2, 5, 0, 0, 1);
            }
            else
            {
                $requirement_text .= requirements_str_bad($results, 15, 10, 5, 2, 5, 0, 0, 1);
            }            
            $game->out('
        <tr>
           <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled='.($all_clear && $RACE_DATA[$game->player['user_race']][29][0] ? 'false' : 'true').'; document.send_form.rtt2.disabled='.($all_clear && $RACE_DATA[$game->player['user_race']][29][1] ? 'false' : 'true').'; document.send_form.rtt3.disabled='.($all_clear && $RACE_DATA[$game->player['user_race']][29][2] ? 'false' : 'true').'; document.send_form.rtt4.disabled='.($all_clear && $RACE_DATA[$game->player['user_race']][29][3] ? 'false' : 'true').'; document.send_form.rtt5.disabled='.($all_clear && $RACE_DATA[$game->player['user_race']][29][4] ? 'false' : 'true').'" value="techsup" '.($all_clear ? '' : 'disabled="disabled"').'></td>
           <td>'.constant($game->sprache("TEXT67")).'
              <table class="style_inner" align="center" border="0" frames="box" cellpadding="2" cellspacing="2" width="360">
              <tr>
                <td><input type="radio" name="targettech" id="rtt1" disabled="disabled" '.($RACE_DATA[$game->player['user_race']][29][0] ? '' : 'hidden').' value=0></td>
                <td>'.constant($game->sprache("TEXT68")).'</td>
              </tr>
              <tr>
                <td><input type="radio" name="targettech" id="rtt2" disabled="disabled" '.($RACE_DATA[$game->player['user_race']][29][1] ? '' : 'hidden').' value=1></td>
                <td>'.constant($game->sprache("TEXT85")).'</td>
              </tr>              
              <tr>
                <td><input type="radio" name="targettech" id="rtt3" disabled="disabled" '.($RACE_DATA[$game->player['user_race']][29][2] ? '' : 'hidden').' value=2></td>
                <td>'.constant($game->sprache("TEXT72")).'</td>
              </tr>
              <tr>
                <td><input type="radio" name="targettech" id="rtt4" disabled="disabled" '.($RACE_DATA[$game->player['user_race']][29][3] ? '' : 'hidden').' value=3></td>
                <td>'.constant($game->sprache("TEXT69")).'</td>
              </tr>
              <tr>
                <td><input type="radio" name="targettech" id="rtt5" disabled="disabled" '.($RACE_DATA[$game->player['user_race']][29][4] ? '' : 'hidden').' value=4></td>
                <td>'.constant($game->sprache("TEXT70")).'</td>
              </tr>
              </table>
           <br><br>'.$requirement_text.'</td>
        </tr>');
            // Military Support Mission
            $requirement_text = constant($game->sprache("TEXT63"));
            $results = meet_mission_req($myship['ship_id'], 20, 15, 15, 2, 15, 0, 0, 1);
            $all_clear = (array_sum($results) == 0 ? true : false);
            if($all_clear) 
            {
                $requirement_text .= requirements_str_ok(20, 15, 15, 2, 15, 0, 0, 1);
            }
            else
            {
                $requirement_text .= requirements_str_bad($results, 20, 15, 15, 2, 15, 0, 0, 1);
            }
            $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="milsup" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT71")).'<br><br>'.$requirement_text.'</td>
        </tr>');
            switch($game->player['user_race']){
                case 0: // Federation
                    // Multiculturalismo
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 15, 10, 5, 1, 0, 0, 0, 20);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(15, 10, 5, 1, 0, 0, 0, 20);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 15, 10, 5, 1, 0, 0, 0, 20);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="fede_presidio" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT74")).'<br><br>'.$requirement_text.'</td>
        </tr>');
                    // Pluralismo
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 20, 15, 10, 2, 0, 0, 0, 30);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="fede_diplo" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT75")).'<br><br>'.$requirement_text.'</td>
        </tr>');                        
                break;
                case 1: // Romulan
                    // Supremazia Tecnologica
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 15, 10, 5, 1, 0, 0, 0, 20);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(15, 10, 5, 1, 0, 0, 0, 20);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 15, 10, 5, 1, 0, 0, 0, 20);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="romu_presidio" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT76")).'<br><br>'.$requirement_text.'</td>
        </tr>');
                    // Orgoglio del Pretore
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 20, 15, 10, 2, 0, 0, 0, 30);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="romu_diplo" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT77")).'<br><br>'.$requirement_text.'</td>
        </tr>');                
                break;
                case 2: // Klingon
                    // Sfida del Coraggio
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 15, 10, 5, 1, 0, 0, 0, 20);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(15, 10, 5, 1, 0, 0, 0, 20);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 15, 10, 5, 1, 0, 0, 0, 20);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="klin_presidio" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT78")).'<br><br>'.$requirement_text.'</td>
        </tr>');
                    // Promuovere Autodifesa
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 20, 15, 10, 2, 0, 0, 0, 30);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="klin_diplo" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT79")).'<br><br>'.$requirement_text.'</td>
        </tr>');                
                break;
                case 3: // Cardassian
                    // Xenofobia
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 15, 10, 5, 1, 0, 0, 0, 20);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(15, 10, 5, 1, 0, 0, 0, 20);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 15, 10, 5, 1, 0, 0, 0, 20);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="card_presidio" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT80")).'<br><br>'.$requirement_text.'</td>
        </tr>');
                    // Propaganda Sovversiva
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 20, 15, 10, 2, 0, 0, 0, 30);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="card_diplo" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT81")).'<br><br>'.$requirement_text.'</td>
        </tr>');                
                break;
                case 4: // Dominion
                /*
                    // Controllore della Colonia
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 15, 10, 5, 1, 0, 0, 20);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(15, 10, 5, 1, 0, 0, 20);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 15, 10, 5, 1, 0, 0, 20);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="dom_presidio" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT82")).'<br><br>'.$requirement_text.'</td>
        </tr>');
                 * 
                 */
                    // Denunciare Incompetenza
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 20, 15, 10, 2, 0, 0, 0, 30);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="dom_diplo" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT83")).'<br><br>'.$requirement_text.'</td>
        </tr>');
                    // Addestramento
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 20, 15, 10, 2, 0, 0, 0, 1);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(20, 15, 10, 2, 0, 0, 0, 1);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 20, 15, 10, 2, 0, 0, 0, 1);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="training" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT86")).'<br><br>'.$requirement_text.'</td>
        </tr>');
                break;
                case 5: // Ferengi
                    // Difesa del mercato
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 15, 10, 5, 1, 0, 0, 0, 20);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(15, 10, 5, 1, 0, 0, 0, 20);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 15, 10, 5, 1, 0, 0, 500, 20);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="fere_presidio" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT89")).'<br><br>'.$requirement_text.'</td>
        </tr>');
                    // Corrompere i locali
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 20, 15, 10, 2, 0, 0, 0, 30);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="fere_diplo" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT90")).'<br><br>'.$requirement_text.'</td>
        </tr>');                    
                break;
                case 6: // Borg
                break;
                case 7: // Q
                break;
                case 8: // Breen
                /*
                    // Controllore della Colonia
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 15, 10, 5, 1, 0, 0, 20);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(15, 10, 5, 1, 0, 0, 20);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 15, 10, 5, 1, 0, 0, 20);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="dom_presidio" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT82")).'<br><br>'.$requirement_text.'</td>
        </tr>');
                */
                    // Denunciare Incompetenza
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 20, 15, 10, 2, 0, 0, 0, 30);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 20, 15, 10, 2, 0, 0, 0, 30);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="dom_diplo" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT83")).'<br><br>'.$requirement_text.'</td>
        </tr>');
                    // Addestramento
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 20, 15, 10, 2, 0, 0, 0, 1);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(20, 15, 10, 2, 0, 0, 0, 1);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 20, 15, 10, 2, 0, 0, 0, 1);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="training" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT86")).'<br><br>'.$requirement_text.'</td>
        </tr>');                        
                break;
                case 9: // Hirogen
                    // Terreno di caccia
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 40, 25, 15, 2, 0, 0, 0, 20);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(40, 25, 15, 2, 0, 0, 0, 20);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 40, 25, 15, 2, 0, 0, 0, 20);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="hiro_ambush" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT73")).'<br><br>'.$requirement_text.'</td>
        </tr>');
                    // Addestramento
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 40, 25, 15, 2, 0, 0, 0, 1);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(40, 25, 15, 2, 0, 0, 0, 1);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 40, 25, 15, 2, 0, 0, 0, 1);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="training" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT86")).'<br><br>'.$requirement_text.'</td>
        </tr>');                        
                break;
                case 10: // Krenim
                break;
                case 11: // Kazon
                    // Razziatori in attesa
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 40, 25, 20, 3, 0, 0, 0, 20);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(40, 25, 20, 3, 0, 0, 0, 20);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 40, 25, 20, 3, 0, 0, 0, 20);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="kazon_ambush" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT88")).'<br><br>'.$requirement_text.'</td>
        </tr>');
                    // Addestramento
                    $requirement_text = constant($game->sprache("TEXT63"));
                    $results = meet_mission_req($myship['ship_id'], 40, 25, 20, 3, 0, 0, 0, 1);
                    $all_clear = (array_sum($results) == 0 ? true : false);
                    if($all_clear) 
                    {
                        $requirement_text .= requirements_str_ok(40, 25, 20, 3, 0, 0, 0, 1);
                    }
                    else
                    {
                        $requirement_text .= requirements_str_bad($results, 40, 25, 20, 3, 0, 0, 0, 1);
                    }
                    $game->out('
        <tr>
          <td><input type="radio" name="mission" onclick="document.send_form.rtt1.disabled=true; document.send_form.rtt2.disabled=true; document.send_form.rtt3.disabled=true; document.send_form.rtt4.disabled=true; document.send_form.rtt5.disabled=true" value="training" '.($all_clear ? '' : 'disabled="disabled"').'></td>
          <td>'.constant($game->sprache("TEXT86")).'<br><br>'.$requirement_text.'</td>
        </tr>');                
                break;
                case 12: // Men 29th century
                break;
                case 13: // Settlers
                break;
            }
        }
        /* Template for other missions
        $game->out('
              <tr>
                <td><input type="radio" name="mission" value="<<<----!!!CAMBIAMI!!!---->>"></td>
                <td>'.constant($game->sprache("TEXTXX")).'</td>
              </tr>');
        */
        $game->out('
        <tr>
          <td colspan="2" align="center">
            <input type="hidden" name="fleets[]" value="'.$fleets[0]['fleet_id'].'">
            <input type="hidden" name="step" value="survey_exec">
            <input class="button" type="button" name="cancel" value="'.constant($game->sprache("TEXT28")).'" onClick="return window.history.back();">&nbsp;&nbsp;<input class="button" type="submit" name="submit" value="'.constant($game->sprache("TEXT29")).'">
          </td>
        </tr>
        </form>
      </table>
    </td>
  </tr>
</table>');
    break;



    case 'attack_normal_exec':
    case 'attack_bomb_exec':
    case 'attack_invade_exec':
        if($game->SITTING_MODE){
            message(NOTICE, constant($game->sprache("TEXT30")));
        }

        if($atkptc_present) {
            message(NOTICE, constant($game->sprache("TEXT31")));
        }

        if($other_party['user_id'] == $game->player['user_id']) {
            message(NOTICE, constant($game->sprache("TEXT13")));
        }

        if($planetary_dest && $free_planet) {
            message(NOTICE, constant($game->sprache("TEXT32")));
        }


        // #############################################################################
        // Move start
        
        $sql = 'SELECT COUNT(ship_id) AS n_ships
                FROM ships
                WHERE fleet_id IN ('.implode(',', $fleet_ids).')';

        if(($_nships = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query real n_ships data');
        }

        $n_ships = $_nships['n_ships'];

        if($n_ships == 0) {
            message(GENERAL, constant($game->sprache("TEXT33")), 'Unexspected: $n_ships = 0');
        }

        $duration=2;
        switch($step) {
            case 'attack_normal_exec':
                $action_code = 51;
                $action_data = array((int)$user_id);
                $duration=2;
            break;
            
            case 'attack_bomb_exec':
                if(!$planetary_dest) {
                    message(NOTICE, constant($game->sprache("TEXT34")));
                }

                $focus = (int)$_POST['focus'];

                if($focus < 0) $focus = 0;
                if($focus > 3) $focus = 3;

                $action_code = 54;
                $action_data = array($focus);
                $duration=2;
            break;

            case 'attack_invade_exec':
                if(!$planetary_dest) {
                    message(NOTICE, constant($game->sprache("TEXT35")));
                }
                
                if(!$in_colo) {
                    message(NOTICE, constant($game->sprache("TEXT36")));
                }
            
                $action_code = 55;
                
                if(empty($_POST['ship_id'])) {
                    message(GENERAL, constant($game->sprache("TEXT1")), '$_POST[\'ship_id\'] = empty');
                }
                
                $action_data = array((int)$_POST['ship_id']);
                $duration=2;
            break;
        }

        $sql = 'INSERT INTO scheduler_shipmovement (user_id, owner_id, move_status, move_exec_started, start, dest, total_distance, remaining_distance, tick_speed, move_begin, move_finish, n_ships, action_code, action_data)
                VALUES ('.(!$is_undiscloused_id ? $game->player['user_id'] : $undisclosed_id).', '.$game->player['user_id'].', 0, 0, '.$planet_id.', '.$planet_id.', 0, 0, 0, '.$ACTUAL_TICK.', '.($ACTUAL_TICK + $duration).', '.$n_ships.', '.$action_code.', "'.serialize($action_data).'")';
                
        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not insert new movement data');
        }
        
        $new_move_id = $db->insert_id();
        
        if(empty($new_move_id)) {
            message(GENERAL, 'Could not obtain new move id', '$new_move_id = empty');
        }
        
        $sql = 'UPDATE ship_fleets
                SET planet_id = 0,
                    move_id = '.$new_move_id.'
                WHERE fleet_id IN ('.implode(',', $fleet_ids).')';
                
        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not update fleets location data');
        }

        redirect('a=tactical_cartography&planet_id='.encode_planet_id($planet_id));
    break;

    
    case 'attack_setup':
        if($game->SITTING_MODE){
            message(NOTICE, constant($game->sprache("TEXT30")));
        }

        if($atkptc_present) {
            message(NOTICE, constant($game->sprache("TEXT31")));
        }

        if($other_party['user_id'] == $game->player['user_id']) {
            message(NOTICE, constant($game->sprache("TEXT37")));
        }

        $game->out('
<table class="style_outer" align="center" border="0" cellpadding="2" cellspacing="2" width="450">
  <tr>
    <td>
      <table class="style_inner" align="center" border="0" cellpadding="2" cellspacing="2" width="450">
        <form name="send_form" method="post" action="'.parse_link('a=ship_actions&user_id='.$user_id).'" onSubmit="return document.send_form.submit.disabled = true;">
        ');

        $fleet_option_html = '';

        for($i = 0; $i < $n_fleets; ++$i) {
            $fleet_option_html .= '<option>'.$fleets[$i]['fleet_name'].' ('.$fleets[$i]['n_ships'].')</option>';
            $game->out('<input type="hidden" name="fleets[]" value="'.$fleets[$i]['fleet_id'].'">');
        }

        $game->out('
        <tr>
          <td>
            '.constant($game->sprache("TEXT17")).' <a href="'.parse_link('a=tactical_cartography&planet_id='.encode_planet_id($planet_id)).'"><b>'.$planet['planet_name'].'</b></a> ('.$game->get_sector_name($planet['sector_id']).':'.$game->get_system_cname($planet['system_x'], $planet['system_y']).':'.($planet['planet_distance_id'] + 1).')'.( ($planet['user_id'] != $game->player['user_id']) ? ' '.constant($game->sprache("TEXT18")).' <a href="'.parse_link('a=stats&a2=viewplayer&id='.$planet['user_id']).'"><b>'.$planet['user_name'].'</b></a>' : '' ).'<br><br>
            '.constant($game->sprache("TEXT19")).' <select style="width: 200px;">'.$fleet_option_html.'</select><br><br>
            <b>'.constant($game->sprache("TEXT38")).'</b><br>
        ');
        
        if($planetary_dest) {
            $game->out(constant($game->sprache("TEXT39")).'<br><br>
            <input type="radio" name="step" value="attack_normal_exec" checked="checked" onClick="return document.send_form.submit.value = \''.constant($game->sprache("TEXT29")).'\';">&nbsp;<b>'.constant($game->sprache("TEXT40")).'</b><br>
            <input type="radio" name="step" value="attack_bomb_setup" onClick="return document.send_form.submit.value = \''.constant($game->sprache("TEXT41")).'\';">&nbsp;<b>'.constant($game->sprache("TEXT42")).'</b><br>
            <input type="radio" name="step" value="attack_invade_setup"'.( (!$in_colo) ? ' disabled="disabled">&nbsp;'.constant($game->sprache("TEXT43")).'<br>' : ' onClick="return document.send_form.submit.value = \''.constant($game->sprache("TEXT41")).'\';">&nbsp;<b>'.constant($game->sprache("TEXT43")).'</b><br>' )
            );
        }
        else {
            $game->out(constant($game->sprache("TEXT44")).'<br><input type="hidden" name="step" value="attack_normal_exec">');
        }

        if($in_transporter) {
            $resource_1 = $resource_2 = $resource_3 = $resource_4 = $unit_1 = $unit_2 = $unit_3 = $unit_4 = $unit_5 = $unit_6 = 0;

            for($i = 0; $i < $n_fleets; ++$i) {
                $resource_1 += $fleets[$i]['resource_1'];
                $resource_2 += $fleets[$i]['resource_2'];
                $resource_3 += $fleets[$i]['resource_3'];
                $resource_4 += $fleets[$i]['resource_4'];

                $unit_1 += $fleets[$i]['unit_1'];
                $unit_2 += $fleets[$i]['unit_2'];
                $unit_3 += $fleets[$i]['unit_3'];
                $unit_4 += $fleets[$i]['unit_4'];
                $unit_5 += $fleets[$i]['unit_5'];
                $unit_6 += $fleets[$i]['unit_6'];
            }

            $n_resources = $resource_1 + $resource_2 + $resource_3 + $resource_4;
            $n_units = $unit_1 + $unit_2 + $unit_3 + $unit_4 + $unit_5 + $unit_6;

            if($n_resources > 0) {
                if($resource_1 > 0) $game->out('<br>'.constant($game->sprache("TEXT24")).' <b>'.$resource_1.'</b>');
                if($resource_2 > 0) $game->out('<br>'.constant($game->sprache("TEXT25")).' <b>'.$resource_2.'</b>');
                if($resource_3 > 0) $game->out('<br>'.constant($game->sprache("TEXT26")).' <b>'.$resource_3.'</b>');
                if($resource_4 > 0) $game->out('<br>'.constant($game->sprache("TEXT27")).' <b>'.$resource_4.'</b>');
                $game->out('<br>');
            }

            if($n_units > 0) {
                if($unit_1 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][0].': <b>'.$unit_1.'</b>');
                if($unit_2 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][1].': <b>'.$unit_2.'</b>');
                if($unit_3 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][2].': <b>'.$unit_3.'</b>');
                if($unit_4 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][3].': <b>'.$unit_4.'</b>');
                if($unit_5 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][4].': <b>'.$unit_5.'</b>');
                if($unit_6 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][5].': <b>'.$unit_6.'</b>');
                $game->out('<br>');
            }
        }

        $game->out('
            <br>
            <center><input class="button" type="button" name="cancel" value="'.constant($game->sprache("TEXT28")).'" onClick="return window.history.back();">&nbsp;&nbsp;<input class="button" type="submit" name="submit" value="'.constant($game->sprache("TEXT29")).'"></center>
          </td>
        </tr>
        </form>
      </table>
    </td>
  </tr>
</table>
      ');
    break;
    
    case 'attack_bomb_setup':
        if($atkptc_present) {
            message(NOTICE, constant($game->sprache("TEXT31")));
        }
        
        if(!$planetary_dest) {
            message(NOTICE, constant($game->sprache("TEXT34")));
        }

        if($other_party['user_id'] == $game->player['user_id']) {
            message(NOTICE, constant($game->sprache("TEXT37")));
        }

        if($is_undisclosed_id) {
            message(NOTICE, constant($game->sprache("TEXT92")));
        }

        $game->out('
<table class="style_outer" align="center" border="0" cellpadding="2" cellspacing="2" width="450">
  <tr>
    <td>
      <table class="style_inner" align="center" border="0" cellpadding="2" cellspacing="2" width="450">
        <form name="send_form" method="post" action="'.parse_link('a=ship_actions&step=attack_bomb_exec&user_id='.$user_id).'" onSubmit="return document.send_form.submit.disabled = true;">
        ');

        $fleet_option_html = '';

        for($i = 0; $i < $n_fleets; ++$i) {
            $fleet_option_html .= '<option>'.$fleets[$i]['fleet_name'].' ('.$fleets[$i]['n_ships'].')</option>';
            $game->out('<input type="hidden" name="fleets[]" value="'.$fleets[$i]['fleet_id'].'">');
        }

        $game->out('
        <tr>
          <td>
            '.constant($game->sprache("TEXT17")).' <a href="'.parse_link('a=tactical_cartography&planet_id='.encode_planet_id($planet_id)).'"><b>'.$planet['planet_name'].'</b></a> ('.$game->get_sector_name($planet['sector_id']).':'.$game->get_system_cname($planet['system_x'], $planet['system_y']).':'.($planet['planet_distance_id'] + 1).')'.( ($planet['user_id'] != $game->player['user_id']) ? ' '.constant($game->sprache("TEXT18")).' <a href="'.parse_link('a=stats&a2=viewplayer&id='.$planet['user_id']).'"><b>'.$planet['user_name'].'</b></a>' : '' ).'<br><br>
            '.constant($game->sprache("TEXT19")).' <select style="width: 200px;">'.$fleet_option_html.'</select><br><br>
            '.constant($game->sprache("TEXT45")).'
            <br><br>
            <input type="radio" name="focus" value="0" checked="checked">&nbsp;'.constant($game->sprache("TEXT46")).'<br>
            <input type="radio" name="focus" value="1">&nbsp;'.constant($game->sprache("TEXT47")).'<br>
            <input type="radio" name="focus" value="2">&nbsp;'.constant($game->sprache("TEXT48")).'<br>
            <input type="radio" name="focus" value="3">&nbsp;'.constant($game->sprache("TEXT49")).'<br><br>
            <center><input class="button" type="button" name="cancel" value="'.constant($game->sprache("TEXT28")).'" onClick="return window.history.back();">&nbsp;&nbsp;<input class="button" type="submit" name="submit" value="'.constant($game->sprache("TEXT29")).'"></center>
          </td>
        </tr>
        </form>
      </table>
    </td>
  </tr>
</table>
      ');
    break;
    
    case 'attack_invade_setup':
        if($atkptc_present) {
            message(NOTICE, constant($game->sprache("TEXT31")));
        }

        if($other_party['user_id'] == $game->player['user_id']) {
            message(NOTICE, constant($game->sprache("TEXT37")));
        }
        
        if(!$planetary_dest) {
            message(NOTICE, constant($game->sprache("TEXT35")));
        }
        
        if(!$in_colo) {
            message(NOTICE, constant($game->sprache("TEXT36")));
        }

        $game->out('
<table class="style_outer" align="center" border="0" cellpadding="2" cellspacing="2" width="450">
  <tr>
    <td>
      <table class="style_inner" align="center" border="0" cellpadding="2" cellspacing="2" width="450">
        <form name="send_form" method="post" action="'.parse_link('a=ship_actions&step=attack_invade_exec&user_id='.$user_id).'" onSubmit="return document.send_form.submit.disabled = true;">
        ');

        $fleet_option_html = '';

        for($i = 0; $i < $n_fleets; ++$i) {
            $fleet_option_html .= '<option>'.$fleets[$i]['fleet_name'].' ('.$fleets[$i]['n_ships'].')</option>';
            $game->out('<input type="hidden" name="fleets[]" value="'.$fleets[$i]['fleet_id'].'">');
        }
        
        $sql = 'SELECT s.ship_id, s.hitpoints, s.experience, s.unit_1, s.unit_2, s.unit_3, s.unit_4,
                       st.name, st.value_5 AS max_hitpoints
                FROM (ships s, ship_templates st)
                WHERE s.fleet_id IN ('.$fleet_ids_str.') AND
                      st.id = s.template_id AND
                      st.ship_torso = '.SHIP_TYPE_COLO;
                      
        if(!$q_cships = $db->query($sql)) {
            message(DATABASE_ERROR, 'Could not query colonisation ship data');
        }
        
        $first_cship = $db->fetchrow($q_cships);
        
        if(empty($first_cship['ship_id'])) {
            message(GENERAL, 'Unexspected: Second try to find colo ship failed', '$first_cship[\'ship_id\'] = empty');
        }

        $game->out('
        <tr>
          <td>
            '.constant($game->sprache("TEXT17")).' <a href="'.parse_link('a=tactical_cartography&planet_id='.encode_planet_id($planet_id)).'"><b>'.$planet['planet_name'].'</b></a> ('.$game->get_sector_name($planet['sector_id']).':'.$game->get_system_cname($planet['system_x'], $planet['system_y']).':'.($planet['planet_distance_id'] + 1).')'.( ($planet['user_id'] != $game->player['user_id']) ? ' '.constant($game->sprache("TEXT18")).' <a href="'.parse_link('a=stats&a2=viewplayer&id='.$planet['user_id']).'"><b>'.$planet['user_name'].'</b></a>' : '' ).'<br><br>
            '.constant($game->sprache("TEXT19")).' <select style="width: 200px;">'.$fleet_option_html.'</select><br><br>
            '.constant($game->sprache("TEXT50")).'<br><br>
      
            <table border="0" cellpadding="2" cellspacing="2">
              <tr>
                <td width="20" align="right"><input type="radio" name="ship_id" value="'.$first_cship['ship_id'].'" checked="checked"></td>
                <td width="350" align="left"><b>'.$first_cship['name'].'</b> ('.$first_cship['hitpoints'].'/'.$first_cship['max_hitpoints'].', Exp: '.$first_cship['experience'].')<br>'.constant($game->sprache("TEXT51")).' '.$first_cship['unit_1'].'/'.$first_cship['unit_2'].'/'.$first_cship['unit_3'].'/'.$first_cship['unit_4'].'</td>
              </tr>
        ');

        while($cship = $db->fetchrow($q_cships)) {
            $game->out('
              <tr>
                <td align="right"><input type="radio" name="ship_id" value="'.$cship['ship_id'].'"></td>
                <td align="left"><b>'.$cship['name'].'</b> ('.$cship['hitpoints'].'/'.$cship['max_hitpoints'].', Exp: '.$cship['experience'].')<br>'.constant($game->sprache("TEXT51")).' '.$cship['unit_1'].'/'.$cship['unit_2'].'/'.$cship['unit_3'].'/'.$cship['unit_4'].'</td>
              </tr>
            ');
        }
        
        $game->out('</table><br><br>');


        if($in_transporter) {
            $resource_1 = $resource_2 = $resource_3 = $resource_4 = $unit_1 = $unit_2 = $unit_3 = $unit_4 = $unit_5 = $unit_6 = 0;

            for($i = 0; $i < $n_fleets; ++$i) {
                $resource_1 += $fleets[$i]['resource_1'];
                $resource_2 += $fleets[$i]['resource_2'];
                $resource_3 += $fleets[$i]['resource_3'];
                $resource_4 += $fleets[$i]['resource_4'];

                $unit_1 += $fleets[$i]['unit_1'];
                $unit_2 += $fleets[$i]['unit_2'];
                $unit_3 += $fleets[$i]['unit_3'];
                $unit_4 += $fleets[$i]['unit_4'];
                $unit_5 += $fleets[$i]['unit_5'];
                $unit_6 += $fleets[$i]['unit_6'];
            }

            $n_resources = $resource_1 + $resource_2 + $resource_3 + $resource_4;
            $n_units = $unit_1 + $unit_2 + $unit_3 + $unit_4 + $unit_5 + $unit_6;

            if($n_resources > 0) {
                if($resource_1 > 0) $game->out('<br>'.constant($game->sprache("TEXT24")).' <b>'.$resource_1.'</b>');
                if($resource_2 > 0) $game->out('<br>'.constant($game->sprache("TEXT25")).' <b>'.$resource_2.'</b>');
                if($resource_3 > 0) $game->out('<br>'.constant($game->sprache("TEXT26")).' <b>'.$resource_3.'</b>');
                if($resource_4 > 0) $game->out('<br>'.constant($game->sprache("TEXT27")).' <b>'.$resource_4.'</b>');
                $game->out('<br>');
            }

            if($n_units > 0) {
                if($unit_1 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][0].': <b>'.$unit_1.'</b>');
                if($unit_2 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][1].': <b>'.$unit_2.'</b>');
                if($unit_3 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][2].': <b>'.$unit_3.'</b>');
                if($unit_4 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][3].': <b>'.$unit_4.'</b>');
                if($unit_5 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][4].': <b>'.$unit_5.'</b>');
                if($unit_6 > 0) $game->out('<br>'.$UNIT_NAME[$game->player['user_race']][5].': <b>'.$unit_6.'</b>');
                $game->out('<br>');
            }
        }

        $game->out('
            <br>
            <center><input class="button" type="button" name="cancel" value="'.constant($game->sprache("TEXT28")).'" onClick="return window.history.back();">&nbsp;&nbsp;<input class="button" type="submit" name="submit" value="'.constant($game->sprache("TEXT29")).'"></center>
          </td>
        </tr>
        </form>
      </table>
    </td>
  </tr>
</table>
      ');
    break;
    
    case 'colo_exec':

        if($game->SITTING_MODE){
            message(NOTICE, constant($game->sprache("TEXT52")));
        }

        if($atkptc_present) {
            message(NOTICE, constant($game->sprache("TEXT53")));
        }

        if(!$free_planet) {
            message(NOTICE, constant($game->sprache("TEXT54")));
        }

        if(!$planetary_dest) {
            message(NOTICE, constant($game->sprache("TEXT55")));
        }

        if(!$in_colo) {
            message(NOTICE, constant($game->sprache("TEXT36")));
        }
        
        // #############################################################################
        // Move start

        $sql = 'SELECT COUNT(ship_id) AS n_ships
                FROM ships
                WHERE fleet_id IN ('.implode(',', $fleet_ids).')';

        if(($_nships = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query real n_ships data');
        }

        $n_ships = $_nships['n_ships'];

        if($n_ships == 0) {
            message(GENERAL, constant($game->sprache("TEXT33")), 'Unexspected: $n_ships = 0');
        }

        if(empty($_POST['ship_id'])) {
            message(GENERAL, constant($game->sprache("TEXT1")), '$_POST[\'ship_id\'] = empty');
        }
        
        $ship_id = (int)$_POST['ship_id'];
        
        $is_terraform = false;
        $is_settler = false;
        if(!empty($_POST['type'])) {
            switch($_POST['type']) {
                case 'terraform': 
                    $is_terraform = true;
                break;
                case 'settler':
                    $is_settler = true;
                break;
            }
        }

        $sql = 'INSERT INTO scheduler_shipmovement (user_id, owner_id, move_status, move_exec_started, start, dest, total_distance, remaining_distance, tick_speed, move_begin, move_finish, n_ships, action_code, action_data)
                VALUES ('.(!$is_undiscloused_id ? $game->player['user_id'] : $undisclosed_id).', '.$game->player['user_id'].', 0, 0, '.$planet_id.', '.$planet_id.', 0, 0, 0, '.$ACTUAL_TICK.', '.($ACTUAL_TICK + 1).', '.$n_ships.', 24, "'.serialize(array($ship_id, $is_terraform, $is_settler)).'")';

        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not insert new movement data');
        }

        $new_move_id = $db->insert_id();

        if(empty($new_move_id)) {
            message(GENERAL, 'Could not obtain new move id', '$new_move_id = empty');
        }

        $sql = 'UPDATE ship_fleets
                SET planet_id = 0,
                    system_id = '.$planet['system_id'].',
                    move_id = '.$new_move_id.'
                WHERE fleet_id IN ('.implode(',', $fleet_ids).')';

        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not update fleets location data');
        }

        redirect('a=tactical_cartography&planet_id='.encode_planet_id($planet_id));
    break;
    
    case 'colo_setup':
        if($game->SITTING_MODE){
            message(NOTICE, constant($game->sprache("TEXT52")));
        }

        if($atkptc_present) {
            message(NOTICE, constant($game->sprache("TEXT53")));
        }

        if(!$free_planet) {
            message(NOTICE, constant($game->sprache("TEXT54")));
        }

        if(!$planetary_dest) {
            message(NOTICE, constant($game->sprache("TEXT55")));
        }

        if(!$in_colo) {
            message(NOTICE, constant($game->sprache("TEXT36")));
        }

        $game->out('
<table class="style_outer" align="center" border="0" cellpadding="2" cellspacing="2" width="450">
  <tr>
    <td>
      <table class="style_inner" align="center" border="0" cellpadding="2" cellspacing="2" width="450">
        <form name="send_form" method="post" action="'.parse_link('a=ship_actions&step=colo_exec').'" onSubmit="return document.send_form.submit.disabled = true;">
        ');

        $fleet_option_html = '';

        for($i = 0; $i < $n_fleets; ++$i) {
            $fleet_option_html .= '<option>'.$fleets[$i]['fleet_name'].' ('.$fleets[$i]['n_ships'].')</option>';
            $game->out('<input type="hidden" name="fleets[]" value="'.$fleets[$i]['fleet_id'].'">');
        }

        $sql = 'SELECT s.ship_id, s.fleet_id, s.hitpoints, s.experience, s.unit_1, s.unit_2, s.unit_3, s.unit_4,
                       st.name, st.value_5 AS max_hitpoints
                FROM (ships s, ship_templates st)
                WHERE s.fleet_id IN ('.$fleet_ids_str.') AND
                      st.id = s.template_id AND
                      st.ship_torso = '.SHIP_TYPE_COLO;

        if(!$q_cships = $db->query($sql)) {
            message(DATABASE_ERROR, 'Could not query colonisation ship data');
        }

        $first_cship = $db->fetchrow($q_cships);

        if(empty($first_cship['ship_id'])) {
            message(GENERAL, 'Unexspected: Second try to find colo ship failed', '$first_cship[\'ship_id\'] = empty');
        }

        $game->out('
        <tr>
          <td>
            '.constant($game->sprache("TEXT17")).' '.constant($game->sprache("TEXT56")).' ('.$game->get_sector_name($planet['sector_id']).':'.$game->get_system_cname($planet['system_x'], $planet['system_y']).':'.($planet['planet_distance_id'] + 1).')
            '.constant($game->sprache("TEXT19")).' <select style="width: 200px;">'.$fleet_option_html.'</select><br><br>
            <input type="radio" name="type" value="terraform" '.(($planet['planet_type'] != 'a' && $planet['planet_type'] != 'b' && $planet['planet_type'] != 'c' && $planet['planet_type'] != 'd' && $planet['planet_type'] != 'h' && $planet['planet_type'] != 'n') ? 'disabled="disabled"' : '&nbsp;').'>'.constant($game->sprache("TEXT59")).' <img src='.$game->GFX_PATH.'menu_latinum_small.gif>.<br><br>	       
	        '.($RACE_DATA[$game->player['user_race']][30] && ($game->config['settler_n_planets'] < SETTLERS_MAX_COLONY) ? '<input type="radio" name="type" value="settler">'.constant($game->sprache("TEXT66")).'<br><br>' : '').'
            <input type="radio" name="type" value="colony" checked="checked">'.constant($game->sprache("TEXT57")).'<br><br>

            <table border="0" cellpadding="2" cellspacing="2">
              <tr>
                <td width="20" align="right"><input type="radio" name="ship_id" value="'.$first_cship['ship_id'].'" checked="checked"></td>
                <td width="350" align="left"><b>'.$first_cship['name'].'</b> ('.$first_cship['hitpoints'].'/'.$first_cship['max_hitpoints'].', Exp: '.$first_cship['experience'].')<br>'.constant($game->sprache("TEXT51")).' '.$first_cship['unit_1'].'/'.$first_cship['unit_2'].'/'.$first_cship['unit_3'].'/'.$first_cship['unit_4'].'</td>
              </tr>
');

        while($cship = $db->fetchrow($q_cships)) {
            $game->out('
              <tr>
                <td align="right"><input type="radio" name="ship_id" value="'.$cship['ship_id'].'"></td>
                <td align="left"><b>'.$cship['name'].'</b> ('.$cship['hitpoints'].'/'.$cship['max_hitpoints'].', Exp: '.$cship['experience'].')<br>'.constant($game->sprache("TEXT51")).' '.$cship['unit_1'].'/'.$cship['unit_2'].'/'.$cship['unit_3'].'/'.$cship['unit_4'].'</td>
              </tr>
            ');
        }

        $game->out('
            </table><br><br>
            <br>
            <center><input class="button" type="button" name="cancel" value="'.constant($game->sprache("TEXT28")).'" onClick="return window.history.back();">&nbsp;&nbsp;<input class="button" type="submit" name="submit" value="'.constant($game->sprache("TEXT29")).'"></center>
          </td>
        </tr>
        </form>
      </table>
    </td>
  </tr>
</table>
      ');
    break;
    
    default:
        message(GENERAL, constant($game->sprache("TEXT1")), '$step = "'.$step.'"');
    break;
}

1?>
