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

if(!empty($_GET['call_back'])) {
    $move_id = (int)$_GET['call_back'];

    $sql = 'SELECT *
            FROM scheduler_shipmovement
            WHERE move_id = '.$move_id;

    if(($move = $db->queryrow($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query ship movement data');
    }
    
    if(empty($move['move_id'])) {
        message(NOTICE, constant($game->sprache("TEXT0")));
    }

    if($move['owner_id'] != $game->player['user_id']) {
        message(NOTICE, constant($game->sprache("TEXT0")));
    }
    
    if($move['move_status'] != 0) {
        message(NOTICE, constant($game->sprache("TEXT0")));
    }

    if(in_array($move['action_code'], array(12, 13, 32, 33))) {
        message(NOTICE, constant($game->sprache("TEXT1")));
    }

    if($move['move_rerouted'] == 1) {
        message(NOTICE, constant($game->sprache("TEXT1")));
    }    
    
    $ticks_left = $move['move_finish'] - $ACTUAL_TICK;
    
    if($ticks_left < 2) {
        message(NOTICE, constant($game->sprache("TEXT1")));
    }    
    
    if( ($move['move_begin'] == $ACTUAL_TICK) || ($move['start'] == $move['dest']) ) {
        $sql = 'UPDATE scheduler_shipmovement
                SET move_status = 4
                WHERE move_id = '.$move_id;

        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not update movement data');
        }
        
        $sql = 'UPDATE ship_fleets
                SET planet_id = '.$move['start'].',
                    move_id = 0
                WHERE move_id = '.$move_id;

        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not update fleets location data');
        }
        
        message(NOTICE, constant($game->sprache("TEXT2")));
    }
    else {
        $sql = 'UPDATE scheduler_shipmovement
                SET start = '.$move['dest'].',
                    dest = '.$move['start'].',
                    remaining_distance = total_distance - remaining_distance,
                    move_begin = '.$ACTUAL_TICK.',
                    move_finish = '.($ACTUAL_TICK + ( ($ACTUAL_TICK + 1) - $move['move_begin'] ) ).',
                    action_code = 13,
                    action_data = "'.addslashes(serialize(array('callback_15', (int)$move['action_code'], (int)$move['move_begin'], (int)$move['move_finish'], $move['action_data']))).'"
                WHERE move_id = '.$move_id;

        if(!$db->query($sql)) {
            message(DATABASE_ERROR, 'Could not update ship movement data');
        }
    }
    
    redirect('a=tactical_moves');
}
elseif(!empty($_GET['restore_orders'])) {
    $move_id = (int)$_GET['restore_orders'];
    
    $sql = 'SELECT *
            FROM scheduler_shipmovement
            WHERE move_id = '.$move_id;

    if(($move = $db->queryrow($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query ship movement data');
    }

    if(empty($move['move_id'])) {
        message(NOTICE, constant($game->sprache("TEXT0")));
    }

    if($move['owner_id'] != $game->player['user_id']) {
        message(NOTICE, constant($game->sprache("TEXT0")));
    }

    if($move['move_status'] != 0) {
        message(NOTICE, constant($game->sprache("TEXT0")));
    }

    if($move['action_code'] != 13) {
        message(NOTICE, constant($game->sprache("TEXT3")));
    }
    
    $action_data = unserialize($move['action_data']);
    
    if(!is_array($action_data)) {
        message(NOTICE, constant($game->sprache("TEXT4")));
    }
    
    if($action_data[0] != 'callback_15') {
        message(NOTICE, constant($game->sprache("TEXT4")));
    }
    
    $travel_time = $action_data[3] - $action_data[2];

    $sql = 'UPDATE scheduler_shipmovement
            SET start = '.$move['dest'].',
                dest = '.$move['start'].',
                remaining_distance = total_distance - remaining_distance,
                move_begin = '.$action_data[2].',
                move_finish = '.($action_data[3] + ($ACTUAL_TICK - $action_data[2]) - ($move['move_finish'] - $ACTUAL_TICK) + 2).',
                action_code = '.$action_data[1].',
                action_data = "'.$action_data[4].'"
            WHERE move_id = '.$move_id;
            
    if(!$db->query($sql)) {
        message(DATABASE_ERROR, 'Could not update ship movement data');
    }

    redirect('a=tactical_moves');
}
elseif(!empty($_GET['reroute'])) {
    $move_id = filter_input(INPUT_GET, 'reroute', FILTER_SANITIZE_NUMBER_INT);
    
    $sql = 'SELECT *
            FROM scheduler_shipmovement
            WHERE move_id = '.$move_id;

    if(($move = $db->queryrow($sql)) === false) {
        message(DATABASE_ERROR, 'Could not query ship movement data');
    }

    if(empty($move['move_id'])) {
        message(NOTICE, constant($game->sprache("TEXT0")));
    }

    if($move['user_id'] != $game->player['user_id']) {
        message(NOTICE, constant($game->sprache("TEXT0")));
    }

    if($move['move_status'] != 0) {
        message(NOTICE, constant($game->sprache("TEXT0")));
    }

    if($move['move_rerouted'] == 1) {
        message(NOTICE, constant($game->sprache("TEXT5")));
    }
    
    if(!isset($move['dest2']) || $move['dest2'] == 0) {
        message(NOTICE, constant($game->sprache("TEXT8")));
    }
    
    if(in_array($move['action_code'], array(12, 13, 32, 33))) {
        message(NOTICE, constant($game->sprache("TEXT6")));
    }
    
    $ticks_left = $move['move_finish'] - $ACTUAL_TICK;
    
    if($ticks_left < 1 || $ticks_left > 6) {
        message(NOTICE, constant($game->sprache("TEXT7")));
    }
    
    $sql = 'UPDATE scheduler_shipmovement 
            SET move_rerouted = 1,
                dest = '.$move['dest2'].', 
                dest2 = '.$move['dest'].' 
            WHERE move_id = '.$move_id;
    
    if(!$db->query($sql)) {
        message(DATABASE_ERROR, 'Could not update ship movement data');
    }

    redirect('a=tactical_moves');    
}

?>
