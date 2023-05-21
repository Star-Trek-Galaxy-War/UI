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
$game->out('<span class="caption">'.$BUILDING_NAME[$game->player['user_race']][5].':</span><br><br>');

function CreateCostsText($unit, $quantity)
{
    global $game;
    $icons = ['<img src="'.$game->GFX_PATH.'menu_metal_small.gif">: ', 
              '<img src="'.$game->GFX_PATH.'menu_mineral_small.gif">: ', 
              '<img src="'.$game->GFX_PATH.'menu_latinum_small.gif">: ',
              '<img src="'.$game->GFX_PATH.'menu_worker_small.gif">: '];
    $ress = [0,0,0,0];
    $cost_str = '';
    $ress[0] = UnitPrice($unit-1,0)*$quantity;
    $ress[1] = UnitPrice($unit-1,1)*$quantity;
    $ress[2] = UnitPrice($unit-1,2)*$quantity;
    $ress[3] = UnitPrice($unit-1,3)*$quantity;
    for ($i = 0; $i < 3; $i++) {
        if($ress[$i] > 0) {$cost_str .= $icons[$i].$ress[$i].' ';}
    }
    $cost_str .= '<br>'.$icons[3].$ress[3].' ';
    $time = UnitTimeTicks($unit-1)*$quantity*TICK_DURATION;
    $cost_str .= 'Time: '.(Zeit($time));
    return $cost_str;
}


function UnitMetRequirements($unit)
{
    global $game;
    if (($unit==0 && $game->planet['building_6']<1) ||
        ($unit==1 && $game->planet['building_6']<5) ||
        ($unit==2 && ($game->planet['building_6']<9 || $game->planet['building_9']<1)) ||
        ($unit==3 && $game->planet['building_6']<1) ||
        ($unit==4 && $game->planet['building_6']<1) ||
        ($unit==5 && $game->planet['building_6']<1))
        return 0;
    return 1;
}



function UnitPrice($unit,$resource)
{
    global $game, $RACE_DATA, $UNIT_DATA;

    $price = $UNIT_DATA[$unit][$resource];
    $price*= $RACE_DATA[$game->player['user_race']][6];
    return round($price,0);
}


function UnitTime($unit)
{
    global $game, $RACE_DATA, $UNIT_DATA;

    $time=$UNIT_DATA[$unit][4];
    $time*=$RACE_DATA[$game->player['user_race']][2];
    $time/=100;
    $time*=(100-2*($game->planet['research_4']*$RACE_DATA[$game->player['user_race']][20]));
    if ($time<1) $time=1;
    $time=round($time,0);
    $time*=TICK_DURATION;
    return (Zeit($time));
}

function UnitTimeTicks($unit)
{
global $db;
global $game;
global $RACE_DATA, $UNIT_NAME, $UNIT_DATA, $MAX_BUILDING_LVL,$NEXT_TICK,$ACTUAL_TICK;

$time=$UNIT_DATA[$unit][4];
$time*=$RACE_DATA[$game->player['user_race']][2];
$time/=100;
$time*=(100-2*($game->planet['research_4']*$RACE_DATA[$game->player['user_race']][20]));

if ($time<1) $time=1;
$time=round($time,0);

return $time;
}

function Stop_Train()
{
global $db;
global $game;
global $UNIT_NAME, $UNIT_DESCRIPTION, $UNIT_DATA, $MAX_BUILDING_LVL,$NEXT_TICK,$ACTUAL_TICK;
$db->query('UPDATE planets SET unittrainid_nexttime="-1" WHERE planet_id="'.$game->planet['planet_id'].'"');
$game->planet['unittrainid_nexttime']=0;
}

function Start_Train()
{
global $db;
global $game;
global $UNIT_NAME, $UNIT_DESCRIPTION, $UNIT_DATA, $MAX_BUILDING_LVL,$NEXT_TICK,$ACTUAL_TICK;
if ($game->planet['unittrainid_nexttime']>0) return 0;

		// 1. Jump to the next unit + new time set:
		// First:
		$started=0;
		$tries=0;
		while ($started==0 && $tries<=10)
		{
			if ($game->planet['unittrain_actual']>10) $game->planet['unittrain_actual']=1;
			if ($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]<13 && $game->planet['unittrainid_'.($game->planet['unittrain_actual'])]>=0 &&  $game->planet['unittrainnumberleft_'.($game->planet['unittrain_actual'])]>0)
			{
				// If Unit
				if ($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]<7) {$db->query('UPDATE planets SET unittrain_actual="'.($game->planet['unittrain_actual']).'",unittrainid_nexttime="'.($ACTUAL_TICK+UnitTimeTicks($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]-1)).'" WHERE planet_id="'.$game->planet['planet_id'].'" LIMIT 1');}
				else // If Break
				{
					if ($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]==10) {$db->query('UPDATE planets SET unittrain_actual="'.($game->planet['unittrain_actual']).'",unittrainid_nexttime="'.($ACTUAL_TICK+1).'" WHERE planet_id="'.$game->planet['planet_id'].'" LIMIT 1');}
					if ($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]==11) {$db->query('UPDATE planets SET unittrain_actual="'.($game->planet['unittrain_actual']).'",unittrainid_nexttime="'.($ACTUAL_TICK+9).'" WHERE planet_id="'.$game->planet['planet_id'].'" LIMIT 1');}
					if ($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]==12) {$db->query('UPDATE planets SET unittrain_actual="'.($game->planet['unittrain_actual']).'",unittrainid_nexttime="'.($ACTUAL_TICK+18).'" WHERE planet_id="'.$game->planet['planet_id'].'" LIMIT 1');}
				}
				$started=1;
			}

			if (!$started)
			{
				$tries++;
				$db->query('UPDATE planets SET unittrain_actual="1" WHERE planet_id="'.$game->planet['planet_id'].'"');
			}

			$game->planet['unittrain_actual']++;
		}


}

function Jump_Train($step)
{
    global $db, $game, $ACTUAL_TICK;
    echo 'step is '.$step;
    $game->planet['unittrain_actual'] = $step;
    if ($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]<7) {$db->query('UPDATE planets SET unittrain_actual="'.($game->planet['unittrain_actual']).'",unittrainid_nexttime="'.($ACTUAL_TICK+UnitTimeTicks($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]-1)).'" WHERE planet_id="'.$game->planet['planet_id'].'" LIMIT 1');}
    else // If Break
    {
            if ($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]==10) {$db->query('UPDATE planets SET unittrain_actual="'.($game->planet['unittrain_actual']).'",unittrainid_nexttime="'.($ACTUAL_TICK+1).'" WHERE planet_id="'.$game->planet['planet_id'].'" LIMIT 1');}
            if ($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]==11) {$db->query('UPDATE planets SET unittrain_actual="'.($game->planet['unittrain_actual']).'",unittrainid_nexttime="'.($ACTUAL_TICK+9).'" WHERE planet_id="'.$game->planet['planet_id'].'" LIMIT 1');}
            if ($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]==12) {$db->query('UPDATE planets SET unittrain_actual="'.($game->planet['unittrain_actual']).'",unittrainid_nexttime="'.($ACTUAL_TICK+18).'" WHERE planet_id="'.$game->planet['planet_id'].'" LIMIT 1');}
    }
}


function Reset_List()
{
	global $game,$db;

	$db->query('UPDATE planets SET
	                   unittrainid_1  = 0,
	                   unittrainid_2  = 0,
	                   unittrainid_3  = 0,
	                   unittrainid_4  = 0,
	                   unittrainid_5  = 0,
	                   unittrainid_6  = 0,
	                   unittrainid_7  = 0,
	                   unittrainid_8  = 0,
	                   unittrainid_9  = 0,
	                   unittrainid_10 = 0,
	                   unittrainnumber_1  = 0,
	                   unittrainnumber_2  = 0,
	                   unittrainnumber_3  = 0,
	                   unittrainnumber_4  = 0,
	                   unittrainnumber_5  = 0,
	                   unittrainnumber_6  = 0,
	                   unittrainnumber_7  = 0,
	                   unittrainnumber_8  = 0,
	                   unittrainnumber_9  = 0,
	                   unittrainnumber_10 = 0,
	                   unittrainnumberleft_1  = 0,
	                   unittrainnumberleft_2  = 0,
	                   unittrainnumberleft_3  = 0,
	                   unittrainnumberleft_4  = 0,
	                   unittrainnumberleft_5  = 0,
	                   unittrainnumberleft_6  = 0,
	                   unittrainnumberleft_7  = 0,
	                   unittrainnumberleft_8  = 0,
	                   unittrainnumberleft_9  = 0,
	                   unittrainnumberleft_10 = 0,
	                   unittrainendless_1  = 0,
	                   unittrainendless_2  = 0,
	                   unittrainendless_3  = 0,
	                   unittrainendless_4  = 0,
	                   unittrainendless_5  = 0,
	                   unittrainendless_6  = 0,
	                   unittrainendless_7  = 0,
	                   unittrainendless_8  = 0,
	                   unittrainendless_9  = 0,
	                   unittrainendless_10 = 0,
	                   unittrain_actual = 1,
	                   unittrainid_nexttime=-1
	            WHERE planet_id="'.$game->planet['planet_id'].'"');
}

function Save_List()
{
global $db;
global $game;
global $UNIT_NAME, $UNIT_DESCRIPTION, $UNIT_DATA, $MAX_BUILDING_LVL,$NEXT_TICK,$ACTUAL_TICK;

for ($t=0; $t<10; $t++)
{
$_POST['listid_'.$t]=(int)$_POST['listid_'.$t];
$_POST['listnumber_'.$t]=(int)$_POST['listnumber_'.$t];
$_POST['listendless_'.$t]=(int)$_POST['listendless_'.$t];
if ($_POST['listid_'.$t]==-1) {$_POST['listnumber_'.$t]=$_POST['listendless_'.$t]=0;}

$db->query('UPDATE planets SET
unittrainid_'.($t+1).'="'.$_POST['listid_'.$t].'",
unittrainnumber_'.($t+1).'="'.$_POST['listnumber_'.$t].'",
unittrainnumberleft_'.($t+1).'="'.$_POST['listnumber_'.$t].'",
unittrainendless_'.($t+1).'="'.$_POST['listendless_'.$t].'"
WHERE planet_id="'.$game->planet['planet_id'].'" LIMIT 1');
}
Stop_Train();
redirect('a=academy&start_list=1');
}



function Apply_Template()
{
    global $game,$db,$ACTUAL_TICK;

    // Check if we have the needed data
    if(isset($_POST['templates_list'])) $_POST['templates_list'] = (int)strtok($_POST['templates_list'],',');
    if($_POST['templates_list'] != -1)
    {
        // Retrieve the minimum troops required by the ship
        $sql = 'SELECT `min_unit_1`,`min_unit_2`,`min_unit_3`,`min_unit_4`,`unit_5` AS min_unit_5, `unit_6` AS min_unit_6
                FROM ship_templates
                WHERE id = '.$_POST['templates_list'];

        if(($ship_crew = $db->queryrow($sql)) === false) {
            message(DATABASE_ERROR, 'Could not query ship template data');
        }

        // Check if the academy on the planet meets all the requirements
        $req_ok = false;
        $queue = 1;
        $units_to_train = '';
        for ($t=0; $t<6; $t++)
        {
            if($ship_crew['min_unit_'.($t+1)] > 0 && UnitMetRequirements($t))
            {
                $req_ok = true;
                $units_to_train .= 'unittrainid_'.$queue.' = '.($t+1).',
                                    unittrainnumber_'.$queue.'  = '.$ship_crew['min_unit_'.($t+1)].',
                                    unittrainnumberleft_'.$queue.'  = '.$ship_crew['min_unit_'.($t+1)].',
                                    unittrainendless_'.$queue.'  = 1,';
                $queue++;
            }
        }

        // Empty the remaining slots
        for ($t=$queue; $t <= 10; $t++)
        {
            $units_to_train .= 'unittrainid_'.$queue.' = 0,
                                unittrainnumber_'.$queue.'  = 0,
                                unittrainnumberleft_'.$queue.'  = 0,
                                unittrainendless_'.$queue.'  = 0,';
            $queue++;
        }

        if($req_ok)
        {
            $sql = 'UPDATE planets SET
                           '.$units_to_train.'
                           unittrain_actual = 1,
                           unittrainid_nexttime = '.($ACTUAL_TICK+UnitTimeTicks(1)).'
                    WHERE planet_id = '.$game->planet['planet_id'];

            $db->query($sql);
            $game->planet['unittrainid_nexttime']=0;
        }
        else
            message(NOTICE, constant($game->sprache("Text37")));
    }
}



function Show_Main()
{
global $db;
global $game;
global $UNIT_NAME, $UNIT_DESCRIPTION, $UNIT_DATA, $MAX_BUILDING_LVL,$NEXT_TICK,$ACTUAL_TICK;
$pow_factor=2;


///////////////////////// 1st Build in Progress
if ($game->planet['unittrainid_nexttime']>0)
{
$game->out('
<table border="0" cellpadding="1" cellspacing="1" width="350" class="style_outer"><tr><td>
<table border="0" cellpadding="1" cellspacing="1" width="350" class="style_inner"><tr><td>');

if ($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]<=6)
{
$game->out(constant($game->sprache("Text1")).' <b>'.$UNIT_NAME[$game->player['user_race']][$game->planet['unittrainid_'.($game->planet['unittrain_actual'])]-1].'</b><br>
	'.constant($game->sprache("Text2")));

if ($game->planet['unittrain_error']==0)
    $game->out('<b id="timer3" title="time1_'.($NEXT_TICK+TICK_DURATION*60*($game->planet['unittrainid_nexttime']-$ACTUAL_TICK)).'_type1_1">&nbsp;</b>');
else if ($game->planet['unittrain_error']==1)
    $game->out('<b>'.constant($game->sprache("Text3")).'</b>');
else
    $game->out('<b>'.constant($game->sprache("Text36")).'</b>');
}
else
{
$text=constant($game->sprache("Text26"));
if ($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]==11) $text=constant($game->sprache("Text27"));
if ($game->planet['unittrainid_'.($game->planet['unittrain_actual'])]==12) $text=constant($game->sprache("Text28"));
$game->out(constant($game->sprache("Text4")) .'- <b>'.$text.'</b><br>'.
constant($game->sprache("Text5")).'
<b id="timer3" title="time1_'.($NEXT_TICK+TICK_DURATION*60*($game->planet['unittrainid_nexttime']-$ACTUAL_TICK)).'_type1_1">&nbsp;</b>');
}
$game->out('</td></tr></table></td></tr></table><br>');

$game->set_autorefresh(($NEXT_TICK+TICK_DURATION*60*($game->planet['unittrainid_nexttime']-$ACTUAL_TICK)));
}


//////////////////////// 2nd Buildmenu
$game->out('<table border="0" cellpadding="2" cellspacing="2" width="400" class="style_outer">
  <tr><td width=100%><span class="sub_caption2">'.constant($game->sprache("Text6")).'</span><br>
  <table border=0 cellpadding=1 cellspacing=1 width=398 class="style_inner">');

$game->out(constant($game->sprache("Text7")));
for ($t=0; $t<6; $t++)
{

if (UnitMetRequirements($t))
{
$game->out('<tr height=20><td><img src="'.$game->GFX_PATH.'menu_unit'.($t+1).'_small.gif">&nbsp;<b><a href="javascript:void(0);" onmouseover="return overlib(\''.$UNIT_DESCRIPTION[$game->player['user_race']][$t].constant($game->sprache("Text8")).GetAttackUnit($t).constant($game->sprache("Text9")).$UNIT_DATA[$t][5].constant($game->sprache("Text10")).GetDefenseUnit($t).constant($game->sprache("Text9")).$UNIT_DATA[$t][6].')\', CAPTION, \''.$UNIT_NAME[$game->player['user_race']][$t].'\', WIDTH, 400, '.OVERLIB_STANDARD.');" onmouseout="return nd();">'.$UNIT_NAME[$game->player['user_race']][$t].' ('.$game->planet['unit_'.($t+1).''].')</a></b></td><td><img src="'.$game->GFX_PATH.'menu_metal_small.gif"> '.UnitPrice($t,0).'&nbsp;&nbsp; <img src="'.$game->GFX_PATH.'menu_mineral_small.gif">'.UnitPrice($t,1).'&nbsp;&nbsp; <img src="'.$game->GFX_PATH.'menu_latinum_small.gif"> '.UnitPrice($t,2).'&nbsp;&nbsp; <img src="'.$game->GFX_PATH.'menu_worker_small.gif"> '.UnitPrice($t,3).'</td><td>'.UnitTime($t).'</td></tr>');
}
}

$game->out('</td></tr></table></td></tr></table>');


$game->out('<br><span class="sub_caption">'.constant($game->sprache("Text11")).HelpPopup('academy_1').' :</span><br><br>
<table border="0" cellpadding="2" cellspacing="2" width="400" class="style_outer"><tr><td width=100%><span class="sub_caption2">');
if ($game->planet['unittrainid_nexttime']>0) $game->out(constant($game->sprache("Text12")));
else $game->out(constant($game->sprache("Text13")));
$game->out('</span>');
$game->out('<table border="0" cellpadding="2" cellspacing="2" width="400" class="style_inner">
<tr><td align="center">
<br>
<form name="academy1" method="post" action="'.parse_link('a=academy').'"><input type="submit" name="start_list" class="button_nosize" value="'.constant($game->sprache("Text23")).'">&nbsp;&nbsp;&nbsp;<input type="submit" name="stop_list" class="button_nosize" value="'.constant($game->sprache("Text24")).'"></form>

</td></tr></table></td></tr></table>');


$game->out('<br><span class="sub_caption">'.constant($game->sprache("Text14")).' '.HelpPopup('academy_2').' :</span><br>');


$game->out('<script language="JavaScript">
function UpdateTroops() {
    var tmpl = document.getElementById("templates_list");
    var units = tmpl.options[tmpl.selectedIndex].value.split(\',\',7);
    for (t=1;t<=6;t++)
        document.getElementById( "unit"+t ).firstChild.nodeValue = units[t];
}
</script>');

$game->out('<br><form name="academy2" method="post" action="'.parse_link('a=academy').'">
<table border="0" cellpadding="2" cellspacing="2" width="400" class="style_outer">
  <tr><td width=100%><span class="sub_caption2">'.constant($game->sprache("Text38")).'</span><br>
  <table border=0 cellpadding=1 cellspacing=1 width=398 class="style_inner">');

$sql = 'SELECT `id`,`name`,`min_unit_1`,`min_unit_2`,`min_unit_3`,
            `min_unit_4`,`unit_5` AS min_unit_5, `unit_6` AS min_unit_6
        FROM ship_templates
        WHERE removed <> 1 AND owner = '.$game->player['user_id'];

$templates = $db->query($sql);

$game->out('<tr><td>'.constant($game->sprache("Text39")).'<select name="templates_list" id="templates_list" class="Select" size="1" onChange="UpdateTroops();"><option value="-1,0,0,0,0,0,0">'.constant($game->sprache("Text25")).'</option>');

while(($template = $db->fetchrow($templates)))
    $game->out('<option value="'.$template['id'].','.$template['min_unit_1'].','.$template['min_unit_2'].','.$template['min_unit_3'].','.$template['min_unit_4'].','.$template['min_unit_5'].','.$template['min_unit_6'].'">'.$template['name'].'</option>');

$game->out('</select></td>');

for ($t=0; $t<6; $t++)
    $game->out('<td><img src="'.$game->GFX_PATH.'menu_unit'.($t+1).'_small.gif">&nbsp;<b id="unit'.($t+1).'">0</b></td>');

$game->out('<td><input type="submit" name="apply_template" class="button_nosize" value="'.constant($game->sprache("Text40")).'"></td></tr></table></td></tr></table></form>');


$game->out('<br><table border="0" cellpadding="2" cellspacing="2" width="450" class="style_outer"><tr><td width=100%>
<span class="sub_caption2">'.constant($game->sprache("Text15")).'</span><br>
<table border=0 cellpadding=2 cellspacing=2 width=448 class="style_inner">
<tr><td align="center">
<form name="academy3" method="post" action="'.parse_link('a=academy').'">
<input type="hidden" name="jump_list" value="">
<table border=0 cellpadding=2 cellspacing=2 width=430><tr><td width="5%">&nbsp;</td>'.constant($game->sprache("Text16")).'</tr>');

/*
for ($t=0; $t<10; $t++)
{
if ($game->planet['unittrain_actual']!=($t+1)) $game->out('<tr><td>&nbsp;</td><td width=40>'.($t+1).':</td>');
else $game->out('<tr><td width=20><img src="'.$game->PLAIN_GFX_PATH.'arrow_right.png"></td><td width=40><b><u>'.($t+1).'</u></b>:</td>');
$game->out('<td width=150><select name="listid_'.$t.'" class="Select" size="1"><option value="-1">'.(constant($game->sprache("Text25"))).'</option>');
if ($game->planet['unittrainid_'.($t+1)]==10) $game->out(constant($game->sprache("Text17")));
else $game->out(constant($game->sprache("Text18")));
if ($game->planet['unittrainid_'.($t+1)]==11) $game->out(constant($game->sprache("Text31")));
else $game->out(constant($game->sprache("Text32")));
if ($game->planet['unittrainid_'.($t+1)]==12) $game->out(constant($game->sprache("Text33")));
else $game->out(constant($game->sprache("Text34")));

for ($u=0; $u<6; $u++)
{
if (UnitMetRequirements($u))
{
if ($game->planet['unittrainid_'.($t+1)]==($u+1)) $game->out('<option value="'.($u+1).'" selected>'.$UNIT_NAME[$game->player['user_race']][$u].'</option>');
else $game->out('<option value="'.($u+1).'">'.$UNIT_NAME[$game->player['user_race']][$u].'</option>');
}
}

$number=$game->planet['unittrainnumber_'.($t+1)];
if ($game->planet['unittrainendless_'.($t+1)]!=1) $number=$game->planet['unittrainnumberleft_'.($t+1)];

$game->out('
</select>
</td>
<td width=40>
<input type="text" name="listnumber_'.$t.'" value="'.$number.'" class="Field_nosize" size="3" maxlength="5">
</td>
<td width=40 align="center">
<input type="checkbox" name="listendless_'.$t.'" value="1" '.(( $game->planet['unittrainendless_'.($t+1)]) ? 'checked="checked"':'').'>
</select>
</td>

</tr>
');
}
*/

for($t = 0; $t < 10; $t++) {
    if($game->planet['unittrainnumber_'.($t+1)] == 0) {
        $game->out('<tr><td width="5%">&nbsp;</td><td width="8%">'.($t+1).':</td>');
    }
    else if($game->planet['unittrain_actual']!=($t+1)) {
        $game->out('<tr><td width="5%"><img id="jump_list_'.($t+1).'" value="'.($t+1).'" onClick="document.academy3.jump_list.value = '.($t+1).'; document.academy3.submit();" src="'.$game->PLAIN_GFX_PATH.'arrow_grey_right.png"></td><td width="8%">'.($t+1).':</td>');
    }
    else {
        $game->out('<tr><td width="5%"><img src="'.$game->PLAIN_GFX_PATH.'arrow_right.png"></td><td width="8%"><b><u>'.($t+1).'</u></b>:</td>');
    }
    $game->out('<td width="20%"><select name="listid_'.$t.'" class="Select" size="1"><option value="-1">'.(constant($game->sprache("Text25"))).'</option>');
    $game->out('<option value="10"'.($game->planet['unittrainid_'.($t+1)]==10 ? ' selected="selected"' : '').'>'.constant($game->sprache("Text18")).'</option>');
    $game->out('<option value="11"'.($game->planet['unittrainid_'.($t+1)]==11 ? ' selected="selected"' : '').'>'.constant($game->sprache("Text32")).'</option>');
    $game->out('<option value="12"'.($game->planet['unittrainid_'.($t+1)]==12 ? ' selected="selected"' : '').'>'.constant($game->sprache("Text34")).'</option>');
    for ($u=0; $u<6; $u++)
    {
        if (UnitMetRequirements($u))
        {
            if ($game->planet['unittrainid_'.($t+1)]==($u+1)) { 
                $game->out('<option value="'.($u+1).'" selected>'.$UNIT_NAME[$game->player['user_race']][$u].'</option>');
            }
            else {
                $game->out('<option value="'.($u+1).'">'.$UNIT_NAME[$game->player['user_race']][$u].'</option>');
            }                
        }
    }
    $number=$game->planet['unittrainnumber_'.($t+1)];
    if ($game->planet['unittrainendless_'.($t+1)]!=1) {$number=$game->planet['unittrainnumberleft_'.($t+1)];}
    switch ($game->planet['unittrainid_'.($t+1)]) {
        case 1:
        case 2:
        case 3:
        case 4:
        case 5:
        case 6:
            $costs = CreateCostsText($game->planet['unittrainid_'.($t+1)],$number);
            break;
        case 10:
            $costs = 'Time: '.Zeit($number*1*TICK_DURATION);
            break;
        case 11:
            $costs = 'Time: '.Zeit($number*9*TICK_DURATION);
            break;
        case 12:
            $costs = 'Time: '.Zeit($number*18*TICK_DURATION);
            break;
        default :
            $costs = '---';
            break;
    }
    $game->out('
            </select>
        </td>
        <td width="10%">
            <input type="text" name="listnumber_'.$t.'" value="'.$number.'" class="Field_nosize" size="3" maxlength="5">
        </td>
        <td width="10%" align="center">
            <input type="checkbox" name="listendless_'.$t.'" value="1" '.(( $game->planet['unittrainendless_'.($t+1)]) ? 'checked="checked"':'').'>
            </select>
        </td>
        <td width="50%">'.($number > 0 ? $costs : '---').'
        </td>
    </tr>
    ');    
}

$game->out('</table>'.constant($game->sprache("Text19")).'&nbsp;<img src="'.$game->PLAIN_GFX_PATH.'arrow_right.png">&nbsp;'.constant($game->sprache("Text19a")).'<br>
<input type="submit" name="exec_list" class="button_nosize" value="'.constant($game->sprache("Text22")).'" autofocus>&nbsp;&nbsp;
<input type="submit" name="reset_list" class="button_nosize" value="'.constant($game->sprache("Text35")).'"></form></td></tr></table></td></tr></table>');
}




if ($game->planet['building_6']<1)
{
//$game->out('.$game->sprache('Text20').$BUILDING_NAME[$game->player['user_race']]['5'].$game->sprache('Text21'));
message(NOTICE, constant($game->sprache("Text20")).' '.$BUILDING_NAME[$game->player['user_race']][5].' '.constant($game->sprache("Text21")));
//$game->out(constant($game->sprache("Text20")).$BUILDING_NAME[$game->player['user_race']]['5'].constant($game->sprache("Text21")));
}
else
{
$sub_action = (!empty($_POST['a2'])) ? $_POST['a2'] : 'main';
if (isset($_POST['exec_list'])) 

if($_POST['listid_0'] == 2 && $game->planet['building_6'] < 5){

echo constant($game->sprache("Text29"));

}

elseif($_POST['listid_1'] == 2 && $game->planet['building_6'] < 5){

echo constant($game->sprache("Text29"));

}

elseif($_POST['listid_2'] == 2 && $game->planet['building_6'] < 5){

echo constant($game->sprache("Text29"));

}
elseif($_POST['listid_3'] == 2 && $game->planet['building_6'] < 5){

echo constant($game->sprache("Text29"));

}

elseif($_POST['listid_4'] == 2 && $game->planet['building_6'] < 5){

echo constant($game->sprache("Text29"));

}

elseif($_POST['listid_5'] == 2 && $game->planet['building_6'] < 5){

echo constant($game->sprache("Text29"));

}
elseif($_POST['listid_6'] == 2 && $game->planet['building_6'] < 5){

echo constant($game->sprache("Text29"));

}
elseif($_POST['listid_7'] == 2 && $game->planet['building_6'] < 5){

echo constant($game->sprache("Text29"));

}
elseif($_POST['listid_8'] == 2 && $game->planet['building_6'] < 5){

echo constant($game->sprache("Text29"));

}
elseif($_POST['listid_9'] == 2 && $game->planet['building_6'] < 5){

echo constant($game->sprache("Text29"));

}
elseif($_POST['listid_0'] == 3 && $game->planet['building_6'] < 9){

echo constant($game->sprache("Text30"));

}

elseif($_POST['listid_1'] == 3 && $game->planet['building_6'] < 9){

echo constant($game->sprache("Text30"));

}

elseif($_POST['listid_2'] == 3 && $game->planet['building_6'] < 9){

echo constant($game->sprache("Text30"));

}
elseif($_POST['listid_3'] == 3 && $game->planet['building_6'] < 9){

echo constant($game->sprache("Text30"));

}

elseif($_POST['listid_4'] == 3 && $game->planet['building_6'] < 9){

echo constant($game->sprache("Text30"));

}

elseif($_POST['listid_5'] == 3 && $game->planet['building_6'] < 9){

echo constant($game->sprache("Text30"));

}
elseif($_POST['listid_6'] == 3 && $game->planet['building_6'] < 9){

echo constant($game->sprache("Text30"));

}
elseif($_POST['listid_7'] == 3 && $game->planet['building_6'] < 9){

echo constant($game->sprache("Text30"));

}
elseif($_POST['listid_8'] == 3 && $game->planet['building_6'] < 9){

echo constant($game->sprache("Text30"));

}
elseif($_POST['listid_9'] == 3 && $game->planet['building_6'] < 9){

echo constant($game->sprache("Text30"));

}


else {
Save_List();
}
if (isset($_REQUEST['start_list']))
{
Start_Train();
redirect('a=academy');
}
if (isset($_POST['stop_list']))
{
Stop_Train();
redirect('a=academy');
}
if (isset($_POST['reset_list']))
{
Reset_List();
redirect('a=academy');
}
if (isset($_POST['apply_template']))
{
Apply_Template();
redirect('a=academy');
}
if (isset($_POST['jump_list']))
{
Jump_Train(filter_input(INPUT_POST, 'jump_list', FILTER_SANITIZE_NUMBER_INT));
redirect('a=academy');
}
Show_Main();
}

?>
