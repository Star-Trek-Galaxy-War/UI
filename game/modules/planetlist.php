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
include_once('include/libs/moves.php');
define ('FULL_DETAILS', 0);


//Ne day global class is nice, but not always match their content
$game->init_player();
include('include/static/static_components_'.$game->player['user_race'].'.php');
$filename = 'include/static/static_components_'.$game->player['user_race'].'_'.$game->player['language'].'.php';
if (file_exists($filename)) include($filename);

error_reporting(E_ERROR);


if (isset($_GET['s_o']) && $_GET['s_o']>=0 && $_GET['s_o']<=6) $game->option_store('planetlist_order',(int)$_GET['s_o']);
if (isset($_GET['s_s']) && $_GET['s_s']>=0 && $_GET['s_s']<=3) $game->option_store('planetlist_show',(int)$_GET['s_s']);

if (!$game->SITTING_MODE && isset($_GET['s_op']) && $_GET['s_op']>=0 && $_GET['s_op']<=2) $game->option_store('redalert_options',(int)$_GET['s_op']);

if (isset($_REQUEST['a2']) && $_REQUEST['a2']>=0)
{
 $game->player->active_planet($_REQUEST['a2']);
}


$game->out('<span class="caption">'.constant($game->sprache("TEXT1")).'</span><br><br>');
$game->out(constant($game->sprache("TEXT2")).'<br>'.constant($game->sprache("TEXT3")).'<br>
'.constant($game->sprache("TEXT4")).'<font color=#80ff80>'.constant($game->sprache("TEXT5")).'</font>'.constant($game->sprache("TEXT6")).'<br>
'.constant($game->sprache("TEXT7")).'<font color=#ffff80>'.constant($game->sprache("TEXT8")).'</font> '.constant($game->sprache("TEXT9")).' <font color=#ff8080>'.constant($game->sprache("TEXT10")).'</font>.<br><br>');

$game->out('<span class="sub_caption">'.constant($game->sprache("TEXT12")).' '.HelpPopup('planetlist').' :</span><br><br>');
$game->out('
<table border=0 cellpadding=1 cellaspacing=1 class="style_outer">
<tr>
<td>
<table border=0 cellpadding=1 cellspacing=1 class="style_inner">
<tr>
  <td width=130 valign="top"><span class="sub_caption2">'.constant($game->sprache("TEXT13")).'</span></td>
  <td width=20 valign="top"></td>
  <td width=70 valign="top"><span class="sub_caption2">'.constant($game->sprache("TEXT14")).'</span></td>
  <td width=70 valign="top"><span class="sub_caption2">'.constant($game->sprache("TEXT57")).'</span></td>
  <td width=390 valign="top"><span class="sub_caption2">'.constant($game->sprache("TEXT15")).'</span></td>
  <td width=70 valign="top"><span class="sub_caption2">'.constant($game->sprache("TEXT16")).'</span></td>
  <td width=50 valign="top"><span class="sub_caption2">'.constant($game->sprache("TEXT17")).'</span></td>
  <td width=50 valign="top"><span class="sub_caption2">'.constant($game->sprache("TEXT18")).'</span></td>
</tr>
');

$order[0]=' pl.planet_name ASC';
$order[1]=' pl.planet_points DESC';
$order[2]=' pl.planet_owned_date ASC';
$order[3]=' pl.sector_id ASC, pl.system_id ASC';
$order[4]=' ((pl.unit_1*2+pl.unit_2*3+pl.unit_3*4+pl.unit_4*4)/pl.min_security_troops) ASC';
$order[5]=' pl.planet_type ASC';
$order[6]=' pl.planet_altname ASC';

$planets=array();
$planet_ids=array();
$spacedock_planets = array();
$spacedock_ids = array();
if (FULL_DETAILS) {
    $researches = array();
    $buildings = array();
}


// 1. Fetch the planet and the star system
$planetquery=$db->query('SELECT pl. * , sys.system_x, sys.system_y FROM (planets pl)
LEFT JOIN (starsystems sys) ON sys.system_id = pl.system_id WHERE pl.planet_owner = '.$game->player['user_id'].'
GROUP BY pl.planet_id
ORDER BY '.$order[$game->option_retr('planetlist_order')]);

$numerate=0;
while(($planet = $db->fetchrow($planetquery)))
{
	$planet['numerate']=$numerate;
	$planets[$planet['planet_id']]=$planet;
	$planet_ids[]=$planet['planet_id'];
	$numerate++;
}

// 1.1 Fetch the planet and the star system to get Spacedock
/*$planetquery=$db->query('SELECT pl. * , sys.system_x, sys.system_y FROM (planets pl)
LEFT JOIN (starsystems sys) ON sys.system_id = pl.system_id WHERE pl.planet_owner = '.$game->player['user_id'].'
GROUP BY pl.planet_id
ORDER BY '.$order[$game->option_retr('planetlist_order')]);

$numerate_space=0;
while(($spacedock_planet = $db->fetchrow($planetquery)))
{
	$spacedock_planet['numerate']=$numerate_space;
	$spacedock_planets[$planet['planet_id']]=$spacedock_planet;
	$spacedock_ids[]=(-$spacedock_planet['planet_id']);
	$numerate_space++;
}*/

// 2. Fetch research
if (FULL_DETAILS)
    $sql = 'SELECT r.planet_id AS tmp1, r.research_id, r.research_start, r.research_finish
            FROM (scheduler_research r)
            WHERE r.planet_id IN ('.implode(', ',$planet_ids).')';
else
    $sql = 'SELECT r.planet_id AS tmp1,
            MAX(r.research_finish) AS  research_active
            FROM (scheduler_research r)
            WHERE r.planet_id IN ('.implode(', ',$planet_ids).')
            GROUP BY r.planet_id';

$planetquery=$db->query($sql);
while(($research = $db->fetchrow($planetquery)))
{
    if (FULL_DETAILS) {
        /*	$planets[$research['tmp1']]['research_id']=$research['research_id'];
        $planets[$research['tmp1']]['research_start']=$research['research_start'];
        $planets[$research['tmp1']]['research_finish']=$research['research_finish'];*/
        $researches[$research['tmp1']]['research_id']=$research['research_id'];
        $researches[$research['tmp1']]['research_start']=$research['research_start'];
        $researches[$research['tmp1']]['research_finish']=$research['research_finish'];
    }
    else {
        $planets[$research['tmp1']]['research_active']=$research['research_active'];
    }
}


// 3. Fetch building
if (FULL_DETAILS) 
    $sql = 'SELECT b.planet_id AS tmp2,
                   b.installation_type AS build_active,
                   b.build_start AS building_start,
                   b.build_finish AS building_finish
            FROM (scheduler_instbuild b)
            WHERE b.planet_id IN ('.implode(', ',$planet_ids).')
            ORDER BY b.build_start ASC';
else
    $sql = 'SELECT b.planet_id AS tmp2,
               MAX(b.build_finish) AS build_active
            FROM (scheduler_instbuild b)
            WHERE b.planet_id IN ('.implode(', ',$planet_ids).')
            GROUP BY b.planet_id';

$planetquery=$db->query($sql);
while(($building = $db->fetchrow($planetquery)))
{
    if (FULL_DETAILS) {
        $buildings[$building['tmp2']]['build_active']=$building['build_active'];
        $buildings[$building['tmp2']]['building_start']=$building['building_start'];
        $buildings[$building['tmp2']]['building_finish']=$building['building_finish'];
    }
    else {
        $planets[$building['tmp2']]['build_active']=$building['build_active'];
    }
}


// 4. Fetch shipbuilding
$planetquery=$db->query('SELECT s.planet_id AS tmp3, MAX(s.finish_build) AS shipyard_active
FROM (scheduler_shipbuild s)
WHERE s.planet_id IN ('.implode(', ',$planet_ids).') GROUP BY s.planet_id');
while(($shipyard = $db->fetchrow($planetquery)))
{
	$planets[$shipyard['tmp3']]['shipyard_active']=$shipyard['shipyard_active'];
}

// 4.1 Bring contents of the Spacedock
// Yes, I know the wrong body and improper coded, but does function nevertheless^^
/*
$planetquery=$db->query('SELECT s.fleet_id AS tmp4, COUNT(s.ship_id) AS spacedock_full
FROM (ships s)
WHERE s.fleet_id IN ('.implode(', ',$spacedock_ids).') GROUP BY s.fleet_id');
while(($spacedock = $db->fetchrow($planetquery)))
{
	$spacedock_planets[$spacedock['tmp4']]['spacedock_full']=$spacedock['spacedock_full'];
}*/

// 21/03/08 - AC: Read planet positions from DB
// 4.2 Add distance from currently selected planet.
$planetquery = $db->query('SELECT p.planet_id AS tmp4, p.system_id,s.system_global_x, s.system_global_y
        FROM (planets p, starsystems s)
        WHERE p.planet_id IN ('.implode(', ',$planet_ids).') AND
              s.system_id = p.system_id');
while(($coordinates = $db->fetchrow($planetquery)))
{
	$planets[$coordinates['tmp4']]['system_global_x']=$coordinates['system_global_x'];
	$planets[$coordinates['tmp4']]['system_global_y']=$coordinates['system_global_y'];
}

// 5. Sort the array:
foreach ($planets as $key => $row) {
   $sort[$key]  = $row['numerate'];
}
array_multisort($sort, SORT_ASC, $planets);
unset($sort);


// 6. Output data:
foreach ($planets as $key => $planet) {

	/* 21/03/08 - AC: Add distance from currently selected planet */
	if($planet['planet_id'] != $game->planet['planet_id'])
	{
		$distance = get_distance(array($game->planet['system_global_x'], $game->planet['system_global_y']),
			array($planet['system_global_x'], $planet['system_global_y']));
		$distance = round($distance, 2);
		$min_time = ceil( ( ($distance / warpf(6)) / TICK_DURATION) );
		$min_stardate = sprintf('%.1f', ($game->config['stardate'] + ($min_time / 10)));
		$min_stardate_int = str_replace('.', '', $min_stardate);

		if($distance > 0)
		{
			$arrival_minutes = ($min_stardate_int - (int)str_replace('.', '', $game->config['stardate'])) * TICK_DURATION;
			$arrival_hours = 0;
			$arrival_days = floor($arrival_minutes / 1440);

			$arrival_minutes -= $arrival_days * 1440;
			while($arrival_minutes > 59) {
				$arrival_hours++;
				$arrival_minutes -= 60;
			}
		}
		else
		{
			$arrival_minutes = $INTER_SYSTEM_TIME * TICK_DURATION;
			$arrival_hours = 0;
			$arrival_days = 0;
		}

		if($arrival_days > 0)
			$distance_str = $arrival_days.' gg ';
		else
			$distance_str = '';
		if($arrival_hours > 0)
			$distance_str .= $arrival_hours.' hh ';
		if($arrival_minutes > 0)
			$distance_str .= $arrival_minutes.' mm ';
	}
	/* */

    // Building announcement:
    if (FULL_DETAILS) {
        $building=constant($game->sprache("TEXT18a"));
        if (isset($build['build_active'])) 
        {
            $building=$BUILDING_NAME[$game->player['user_race']][$planet['build_active']].' ('.constant($game->sprache("TEXT18b")).' '.($planet['building_'.($planet['build_active']+1)]).') <b>'.Zeit(TICK_DURATION*($planet['building_finish']-$ACTUAL_TICK)).'</b><br>';
        }
    }
    else {
        if (isset($planet['build_active']))
            $building=constant($game->sprache("TEXT58")).' <b>'.Zeit(TICK_DURATION*($planet['build_active']-$ACTUAL_TICK)).'</b>';
    }

    // Research announcement:
    if (FULL_DETAILS) {
        $research=constant($game->sprache("TEXT18a"));
        if (isset($planet['research_id']))
        {
            if ($planet['research_id']>=5)
                $research=$ship_components[$game->player['user_race']][($planet['research_id']-5)][$planet['catresearch_'.(($planet['research_id']-4))]]['name'].' <b>'.Zeit(TICK_DURATION*($planet['research_finish']-$ACTUAL_TICK)).'</b>';
            else
            {
                $research=$TECH_NAME[$game->player['user_race']][$planet['research_id']].' <b>'.Zeit(TICK_DURATION*($planet['research_finish']-$ACTUAL_TICK)).'</b>';
            }
        }
    }
    else {
        if (isset($planet['research_active']))
            $research=constant($game->sprache("TEXT59")).' <b>'.Zeit(TICK_DURATION*($planet['research_active']-$ACTUAL_TICK)).'</b>';
    }

	$outofresources=0;
	$outofspace=0;
	$academy=constant($game->sprache("TEXT18c"));
	if ($planet['unittrainid_nexttime']>0) 
	{
		if ($planet['unittrainid_'.($planet['unittrain_actual'])]<=6)
		{
			$academy=constant($game->sprache("TEXT22")).' ('.$UNIT_NAME[$game->player['user_race']][$planet['unittrainid_'.($planet['unittrain_actual'])]-1].' ';
			if ($planet['unittrain_error']==0)
			$academy.='<b>'.( ($NEXT_TICK+TICK_DURATION*60*($planet['unittrainid_nexttime']-$ACTUAL_TICK)>$NEXT_TICK+TICK_DURATION*60-ACTUAL_TICK) ? Zeit(TICK_DURATION*($planet['unittrainid_nexttime']-$ACTUAL_TICK)) : constant($game->sprache("TEXT20")) ).'</b>)';
			else
			{
				if($planet['unittrain_error']==2){
					$academy.='<br><b>'.constant($game->sprache("TEXT23")).'</b>)';
					$outofspace=1;
				}
				else {
					$academy.='<br><b>'.constant($game->sprache("TEXT19")).'</b>)';
					$outofresources=1;
				}
				
			}

		}
		else
		{
			$text=(TICK_DURATION).constant($game->sprache("TEXT21"));
			if ($planet['unittrainid_'.($planet['unittrain_actual'])]==11) $text=(TICK_DURATION).constant($game->sprache("TEXT21"));
			if ($planet['unittrainid_'.($planet['unittrain_actual'])]==12) $text=(TICK_DURATION).constant($game->sprache("TEXT21"));
			$academy= constant($game->sprache("TEXT22")).' ('.$text.' <b>'.Zeit(TICK_DURATION*($planet['unittrainid_nexttime']-$ACTUAL_TICK)).'</b>)';
		}

		// ALT, habs times changed, should fit now better. Greeting Mojo ;)
		/*$unitcount=($planet['unit_1']*2+$planet['unit_2']*3+$planet['unit_3']*4+$planet['unit_4']*4+$planet['unit_5']*4+$planet['unit_6']*4);
		if ($unitcount>$planet['max_units']-4)
		{
			
		}*/

	}

	if (isset($planet['shipyard_active']))
	{
		$shipbuild=constant($game->sprache("TEXT24")).' <b>'.Zeit(TICK_DURATION*($planet['shipyard_active']-$ACTUAL_TICK)).'</b>';
	}
	// Set colors for the symbols
    if (FULL_DETAILS) {
        $building_color=($planet['building_finish']-$ACTUAL_TICK)/12;
        $research_color=($planet['research_finish']-$ACTUAL_TICK)/12;
    }
    else {
        $building_color=($planet['build_active']-$ACTUAL_TICK)/12;
        $research_color=($planet['research_active']-$ACTUAL_TICK)/12;
    }

    if ($building_color>8) $building_color=8;
    if ($building_color<0) $building_color=0;
    $building_color='#80'.dechex(128+16*$building_color-1).'80';

    if ($research_color>8) $research_color=8;
    if ($research_color<0) $research_color=0;
    $research_color='#80'.dechex(128+16*$research_color-1).'80';

	$shipyard_color=($planet['shipyard_active']-$ACTUAL_TICK)/12;
	if ($shipyard_color>8) $shipyard_color=8;
	if ($shipyard_color<0) $shipyard_color=0;
	$shipyard_color='#80'.dechex(128+16*$shipyard_color-1).'80';


	// Output of construction status, etc.:
	$status='<table border=0 cellpadding=0 cellspacing=0><tr>';
	if (isset($planet['build_active'])) $status.='<td width=12><a href="javascript:void(0);" onmouseover="return overlib(\''.$building.'\', CAPTION, \''.constant($game->sprache("TEXT25")).'\', WIDTH, 320, '.OVERLIB_STANDARD.');" onmouseout="return nd();"><font color='.$building_color.'>'.constant($game->sprache("TEXT29")).'</font></a></td>'; else $status.='<td width=12>&nbsp;</td>';
	if (isset($planet['research_active'])) $status.='<td width=12><a href="javascript:void(0);" onmouseover="return overlib(\''.$research.'\', CAPTION, \''.constant($game->sprache("TEXT26")).'\', WIDTH, 300, '.OVERLIB_STANDARD.');" onmouseout="return nd();"><font color='.$research_color.'>'.constant($game->sprache("TEXT30")).'</font></a></td>'; else $status.='<td width=12>&nbsp;</td>';
	if ($planet['unittrainid_nexttime']>0) $status.='<td width=12><a href="javascript:void(0);" onmouseover="return overlib(\''.$academy.'\', CAPTION, \''.constant($game->sprache("TEXT27")).'\', WIDTH, 250, '.OVERLIB_STANDARD.');" onmouseout="return nd();">'.($outofresources ? (($outofspace==1) ? '<font color=#ff8080>'.constant($game->sprache("TEXT31")).'</font>' : '<font color=#ffff80>'.constant($game->sprache("TEXT31")).'</font>') : (($outofspace==1) ? '<font color=#ff8080>'.constant($game->sprache("TEXT31")).'</font>' : '<font color=#80ff80>'.constant($game->sprache("TEXT31")).'</font>') ).'</a></td>'; else $status.='<td width=12>&nbsp;</td>';
	if (isset($planet['shipyard_active'])) $status.='<td width=12><a href="javascript:void(0);" onmouseover="return overlib(\''.$shipbuild.'\', CAPTION, \''.constant($game->sprache("TEXT28")).'\', WIDTH, 270, '.OVERLIB_STANDARD.');" onmouseout="return nd();"><font color='.$shipyard_color.'>'.constant($game->sprache("TEXT32")).'</font></a></td>'; else $status.='<td width=12>&nbsp;</td>';
	

	// Output of security forces display
	if (round($planet['unit_1'] * 2 + $planet['unit_2'] * 3 + $planet['unit_3'] * 4 + $planet['unit_4'] * 4, 0)<$planet['min_security_troops']) 
	{
		$percent=round($planet['unit_1'] * 2 + $planet['unit_2'] * 3 + $planet['unit_3'] * 4 + $planet['unit_4'] * 4, 0)/$planet['min_security_troops'];
		if ($planet['planet_insurrection_time']-time()>3600*48 && ( $percent<0.3 || ($percent<0.7 && $planet['planet_points']<30) ) )
			$status.='<td width=21><a href="javascript:void(0);" onmouseover="return overlib(\''.constant($game->sprache("TEXT33")).' '.round($planet['unit_1'] * 2 + $planet['unit_2'] * 3 + $planet['unit_3'] * 4 + $planet['unit_4'] * 4, 0).'<br>'.constant($game->sprache("TEXT34")).' '.$planet['min_security_troops'].'\', CAPTION, \''.constant($game->sprache("TEXT35")).'\', WIDTH, 250, '.OVERLIB_STANDARD.');" onmouseout="return nd();"><img src="'.$game->GFX_PATH.'menu_revolution_small.gif" border=0></a></td>';
		else
			$status.='<td width=21><a href="javascript:void(0);" onmouseover="return overlib(\''.constant($game->sprache("TEXT33")).' '.round($planet['unit_1'] * 2 + $planet['unit_2'] * 3 + $planet['unit_3'] * 4 + $planet['unit_4'] * 4, 0).'<br>'.constant($game->sprache("TEXT34")).' '.$planet['min_security_troops'].'\', CAPTION, \''.constant($game->sprache("TEXT36")).'\', WIDTH, 250, '.OVERLIB_STANDARD.');" onmouseout="return nd();"><img src="'.$game->GFX_PATH.'menu_fight_small.gif" border=0></a></td>';
	}
	$status.='</tr></table>';

	$tax=$db->queryrow('SELECT taxes FROM alliance WHERE alliance_id = '.$game->player['user_alliance'].'');


	// Display of resources
	if ($game->option_retr('planetlist_show')==0){ 
		$stat_out='<img src="'.$game->GFX_PATH.'menu_metal_small.gif"><span'.($planet['resource_1'] >= $planet['max_resources'] ? ' style="color: yellow;"' : '').'>'.(($planet['resource_1']>=100000) ? round($planet['resource_1']/1000).'k' : round($planet['resource_1'],0)).'</span>&nbsp;
		<img src="'.$game->GFX_PATH.'menu_mineral_small.gif"><span'.($planet['resource_2'] >= $planet['max_resources'] ? ' style="color: yellow;"' : '').'>'.(($planet['resource_2']>=100000) ? round($planet['resource_2']/1000).'k' : round($planet['resource_2'],0)).'</span>&nbsp;
		<img src="'.$game->GFX_PATH.'menu_latinum_small.gif"><span'.($planet['resource_3'] >= $planet['max_resources'] ? ' style="color: yellow;"' : '').'>'.(($planet['resource_3']>=100000) ? round($planet['resource_3']/1000).'k' : round($planet['resource_3'],0)).'</span>&nbsp;
		<img src="'.$game->GFX_PATH.'menu_techp1_small.gif"> '.($planet['techpoints'] >= 1000 ?  round($planet['techpoints']/1000,1).'k' : round($planet['techpoints'],0)).'&nbsp;                    
		<img src="'.$game->GFX_PATH.'menu_worker_small.gif">'.round($planet['resource_4'],0).'';
	}
	// Display of the troops status
	elseif($game->option_retr('planetlist_show')==1){
		$stat_out='<img src="'.$game->GFX_PATH.'menu_unit1_small.gif">'.round($planet['unit_1'],0).'&nbsp;
		<img src="'.$game->GFX_PATH.'menu_unit2_small.gif">'.round($planet['unit_2'],0).'&nbsp;
		<img src="'.$game->GFX_PATH.'menu_unit3_small.gif">'.round($planet['unit_3'],0).'&nbsp;
		<img src="'.$game->GFX_PATH.'menu_unit4_small.gif">'.round($planet['unit_4'],0).'&nbsp;&nbsp;
		<img src="'.$game->GFX_PATH.'menu_unit5_small.gif">'.round($planet['unit_5'],0).'&nbsp;
		<img src="'.$game->GFX_PATH.'menu_unit6_small.gif">'.round($planet['unit_6'],0);
	}
	elseif($game->option_retr('planetlist_show')==2){
		$stat_out='<b>'.$planet['planet_altname'].'</b>&nbsp';
	}
	else {
		$stat_out='<b><img src="'.$game->GFX_PATH.'menu_metal_small.gif">&nbsp;'.$planet['add_1']*((100-$tax['taxes'])/100).'&nbsp;<img src="'.$game->GFX_PATH.'menu_mineral_small.gif">&nbsp;'.$planet['add_2']*((100-$tax['taxes'])/100).'&nbsp;<img src="'.$game->GFX_PATH.'menu_latinum_small.gif">&nbsp;'.$planet['add_3']*((100-$tax['taxes'])/100).'&nbsp;<img src="'.$game->GFX_PATH.'menu_worker_small.gif">&nbsp;'.$planet['add_4'].'</b>&nbsp';
	}

	$metallo  = $metallo + $planet['resource_1'];
	$minerali = $minerali  + $planet['resource_2'];
	$dilitio = $dilitio + $planet['resource_3'];
	$lavoratori = $lavoratori + $planet['resource_4'];

	$unita1 = $unita1 + round($planet['unit_1'],0);
	$unita2 = $unita2 + round($planet['unit_2'],0);
	$unita3 = $unita3 + round($planet['unit_3'],0);
	$unita4 = $unita4 + round($planet['unit_4'],0);
	$unita5 = $unita5 + round($planet['unit_5'],0);
	$unita6 = $unita6 + round($planet['unit_6'],0);

	// Show the coordinates and the exchange symbol:
	if ($game->planet['planet_id']==$planet['planet_id'])
	{
		$game->out('
			<tr><td><a href="'.parse_link('a=tactical_cartography&planet_id='.encode_planet_id($planet['planet_id'])).'"><span class="highlight">'.$planet['planet_name'].'</span></a></td><td></td>
			<td>'.$game->get_sector_name($planet['sector_id']).':'.$game->get_system_cname($planet['system_x'],$planet['system_y']).':'.($planet['planet_distance_id'] + 1).'</td>
			<td></td>
			<td>'.$stat_out.'&nbsp;&nbsp;');
	}
	else
	{
		$game->out('
			<tr height=20><td><a href="'.parse_link('a=tactical_cartography&planet_id='.encode_planet_id($planet['planet_id'])).'">'.$planet['planet_name'].'</a></td><td>[<a href="'.parse_link('a=headquarter&switch_active_planet='.$planet['planet_id']).'" title="'.constant($game->sprache("TEXT37")).'">'.constant($game->sprache("TEXT38")).'</a>]</td>
			<td>'.$game->get_sector_name($planet['sector_id']).':'.$game->get_system_cname($planet['system_x'],$planet['system_y']).':'.($planet['planet_distance_id'] + 1).'</td>
			<td><a href="javascript:void(0);" onmouseover="return overlib(\''.$distance_str.'\', CAPTION, \'@ Warp 6.0\', WIDTH, 250, '.OVERLIB_STANDARD.');" onmouseout="return nd();">'.$distance.'</a></td>
			<td>'.$stat_out.'&nbsp;&nbsp;');
	}

	// Output status of construction from above, etc. as well as the display of impending attack (it's set in the scheduler):
	$attack=$planet['planet_next_attack'];
	if ($game->option_retr('redalert_options')==2) $attack=0;
	if ($game->option_retr('redalert_options')==1 && $attack-date()>3600*24*7) $attack=0;
	$game->out('</td><td>'.( $attack>0 ? '<font color=red><b>'.constant($game->sprache("TEXT39")).' </b>'.date('d.m.y H:i', ($attack)).'</font></b><br>' : '').$status.'</td><td>'.$planet['planet_points'].'</td><td>'.strtoupper($planet['planet_type']).'</td></tr>');

} // End of the planets table

function conversione ($risorsa) {
if ($risorsa >= 1000000) $risorsa = round($risorsa/1000000,2).' Mio.';
elseif ($risorsa >= 1000) $risorsa= round($risorsa/1000,0).'k';
else $risorsa= round($risorsa,0);
return ($risorsa);
}


$metallo = conversione($metallo);
$minerali = conversione($minerali);
$dilitio = conversione($dilitio);

$game->out('<tr><td colspan=8><hr size=1>

<tr>
<td colspan=8><fieldset><legend>'.constant($game->sprache("TEXT40")).'</legend>
<table border=0 cellpadding=0 cellspacing=0 widtH=500>
<tr>
<td align=center><img src="'.$game->GFX_PATH.'menu_metal_small.gif">&nbsp;<b>'.$metallo.'</td>
<td align=center><img src="'.$game->GFX_PATH.'menu_mineral_small.gif">&nbsp;<b>'.$minerali.'</td>
<td align=center><img src="'.$game->GFX_PATH.'menu_latinum_small.gif">&nbsp;<b>'.$dilitio.'</td>
<td align=center><img src="'.$game->GFX_PATH.'menu_worker_small.gif">&nbsp;<b>'.round($lavoratori,0).'</td>
</tr>
</table>
<table border=0 cellpadding=0 cellspacing=0 widtH=500>
<tr>
<td align=center><img src="'.$game->GFX_PATH.'menu_unit1_small.gif">&nbsp;<b>'.round($unita1,0).'&nbsp;</td>
<td align=center><img src="'.$game->GFX_PATH.'menu_unit2_small.gif">&nbsp;<b>'.round($unita2,0).'&nbsp;</td>
<td align=center><img src="'.$game->GFX_PATH.'menu_unit3_small.gif">&nbsp;<b>'.round($unita3,0).'&nbsp;</td>
<td align=center><img src="'.$game->GFX_PATH.'menu_unit4_small.gif">&nbsp;<b>'.round($unita4,0).'&nbsp;&nbsp;</td>
<td align=center><img src="'.$game->GFX_PATH.'menu_unit5_small.gif">&nbsp;<b>'.round($unita5,0).'&nbsp;</td>
<td align=center><img src="'.$game->GFX_PATH.'menu_unit6_small.gif">&nbsp;<b>'.round($unita6,0).'</td>
</tr>
</table>

</fieldset></tr>');

// Options for sorting etc.:
$game->out('</table></td></tr></table><br><br>
<table border=0 cellpadding=1 cellspacing=1 class="style_outer"><tr><td>
<table border=0 cellpadding=1 cellspacing=1 class="style_inner">
<tr valign=top><td width=120><b>'.constant($game->sprache("TEXT41")).'</b><br>
<a href="'.parse_link('a=planetlist&s_o=0').'">'.($game->option_retr('planetlist_order')==0 ? '<u>' : '').''.constant($game->sprache("TEXT42")).'</u></a><br>
<a href="'.parse_link('a=planetlist&s_o=1').'">'.($game->option_retr('planetlist_order')==1 ? '<u>' : '').''.constant($game->sprache("TEXT43")).'</u></a><br>
<a href="'.parse_link('a=planetlist&s_o=2').'">'.($game->option_retr('planetlist_order')==2 ? '<u>' : '').''.constant($game->sprache("TEXT44")).'</u></a><br>
<a href="'.parse_link('a=planetlist&s_o=3').'">'.($game->option_retr('planetlist_order')==3 ? '<u>' : '').''.constant($game->sprache("TEXT45")).'</u></a><br>
<a href="'.parse_link('a=planetlist&s_o=4').'">'.($game->option_retr('planetlist_order')==4 ? '<u>' : '').''.constant($game->sprache("TEXT46")).'</u></a><br>
<a href="'.parse_link('a=planetlist&s_o=5').'">'.($game->option_retr('planetlist_order')==5 ? '<u>' : '').''.constant($game->sprache("TEXT47")).'</u></a><br>
<a href="'.parse_link('a=planetlist&s_o=6').'">'.($game->option_retr('planetlist_order')==6 ? '<u>' : '').''.constant($game->sprache("TEXT48")).'</u></a><br>

</td><td width=120><b>'.constant($game->sprache("TEXT49")).'</b><br>
<a href="'.parse_link('a=planetlist&s_s=0').'">'.($game->option_retr('planetlist_show')==0 ? '<u>' : '').''.constant($game->sprache("TEXT50")).'</u></a><br>
<a href="'.parse_link('a=planetlist&s_s=1').'">'.($game->option_retr('planetlist_show')==1 ? '<u>' : '').''.constant($game->sprache("TEXT51")).'</u></a><br>
<a href="'.parse_link('a=planetlist&s_s=2').'">'.($game->option_retr('planetlist_show')==2 ? '<u>' : '').''.constant($game->sprache("TEXT48")).'</u></a><br>
<a href="'.parse_link('a=planetlist&s_s=3').'">'.($game->option_retr('planetlist_show')==3 ? '<u>' : '').''.constant($game->sprache("TEXT52")).'</u></a><br>
</td>
</td><td width=120><b>'.constant($game->sprache("TEXT53")).'</b><br>
'.(($game->SITTING_MODE) ? '' :'<a href="'.parse_link('a=planetlist&s_op=0').'">').($game->option_retr('redalert_options')==0 ? '<u>' : '').''.constant($game->sprache("TEXT54")).'</u>'.(($game->SITTING_MODE) ? '' :'</a>').'<br>
'.(($game->SITTING_MODE) ? '' :'<a href="'.parse_link('a=planetlist&s_op=1').'">').($game->option_retr('redalert_options')==1 ? '<u>' : '').''.constant($game->sprache("TEXT55")).'</u>'.(($game->SITTING_MODE) ? '' :'</a>').'<br>
'.(($game->SITTING_MODE) ? '' :'<a href="'.parse_link('a=planetlist&s_op=2').'">').($game->option_retr('redalert_options')==2 ? '<u>' : '').''.constant($game->sprache("TEXT56")).'</u>'.(($game->SITTING_MODE) ? '' :'</a>').'
</td>
</tr>
</table>
</td>
</tr>
</table>
');

?>
