<?PHP
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



// Stores the RACE_DATA: 




/* 

0 => Federation
1 => Romulan
2 => Klingon
3 => Cardassian
4 => Dominion
5 => Ferengi
 6 => Borg
 7 => Orion Syndacate
8 => Breen
9 => Hirogen
10 => Vidiianer ==> Krenim
11 => Kazon
 12 => Men 29th
 13 => Settler

 * 0 => Name of Race

 * 1 => Construction period (buildings)

 * 2 => Construction period (units)

 * 3 => Construction period (ships)

 * 4 => Research Time

 * 5 => Cost (buildings)

 * 6 => Cost (units)

 * 7 => Cost (ships)

 * 8 => Research and development expenses

 * 9 => Metal mine yield

 * 10 => Mineral mine yield

 * 11 => Latinum refinery yield

 * 12 => "Workers yield"

 * 13 => Ship attack

 * 14 => Unit attack

 * 15 => Ship defense

 * 16 => Unit defense

 * 17 => Building defense (against planetary attacks)

 * 18 => Ship speed

 * 19 => Sensors range

 * 20 => Technology Exploitation (Modifies the efficiency of ALL technologies)

 * 21 => Fighting power of workers
  
 * 22 => Playable

 * 23 => Metal cost factor for buildings

 * 24 => Mineral cost factor for buildings

 * 25 => Latinum cost factor for buildings

 * 26 => Metal cost factor for research

 * 27 => Mineral cost factor for research

 * 28 => Latinum cost factor for research
 *
 * 29 => array() flags for research available to be taught to Settlers
 *
 * 30 => is allowed to create a new independent Settlers' colony
 * 
 * 31 => max planets a player can control

 */ 



$RACE_DATA = array( 

   0 => array( 

      0 => 'F&ouml;deration', 

      1 => 1.1, 

      2 => 1.15, 

      3 => 1.0, 

      4 => 7.25, 

      5 => 1.1, 

      6 => 1.1, 

      7 => 1.0, 

      8 => 1.0, 

      9 => 0.9, 

      10 => 0.9, 

      11 => 0.9, 

      12 => 1.0, 

      13 => 1.0, 

      14 => 1.0, 

      15 => 1.0, 

      16 => 1.0, 

      17 => 1.0, 

      18 => 1.0, 

      19 => 1.0, 

      20 => 1.1, 

      21 => 3.0, 

      22 => true,

      23 => 1.0, 

      24 => 1.0, 

      25 => 0.8,

      26 => 0.9, 

      27 => 0.9, 

      28 => 1.25,

      29 => array(1, 1, 0, 1, 0),

      30 => true,
       
      31 => 30,
       
      32 => 15,
       
      33 => 15,
       
      34 => 15,
       
      35 => 15

   ), 



   1 => array( 

      0 => 'Romulaner', 

      1 => 1.1, 

      2 => 0.95, 

      3 => 0.90, 

      4 => 7.25,

      5 => 1.1, 

      6 => 1.1, 

      7 => 0.95, 

      8 => 1.0, 

      9 => 0.95, 

      10 => 0.95, 

      11 => 0.95, 

      12 => 0.9, 

      13 => 0.9,

      14 => 1.0, 

      15 => 0.95, 

      16 => 0.85, 

      17 => 0.95, 

      18 => 0.75, 

      19 => 0.90, 

      20 => 1.15,

      21 => 3.5, 

      22 => true, 

      23 => 1.15, 

      24 => 1.0, 

      25 => 1.0,

      26 => 1.0, 

      27 => 0.6, 

      28 => 1.3,

      29 => array(1, 1, 0, 1, 0),

      30 => true,
       
      31 => 30,
       
      32 => 15,
       
      33 => 15,
       
      34 => 15,
       
      35 => 15       

   ), 



    2 => array( 

      0 => 'Klingonen', 

       1 => 1.0, 

       2 => 1.2, 

       3 => 1.1, 

       4 => 8.0, 

       5 => 1.0, 

       6 => 1.0,

      7 => 1.05, 

      8 => 1.0,

       9 => 1.1,

      10 => 1.1, 

      11 => 1.1,

      12 => 1.05,

      13 => 1.4,

      14 => 1.5,

      15 => 1.2,

      16 => 1.2,

      17 => 1.05,

      18 => 0.85,

      19 => 0.7,

      20 => 1.0, 

      21 => 6, 

      22 => true,

      23 => 0.8, 

      24 => 0.8, 

      25 => 0.6,

      26 => 0.6, 

      27 => 0.6, 

      28 => 1.05,

      29 => array(1, 1, 0, 0, 1),

      30 => true,      

      31 => 30,
        
      32 => 10,
       
      33 => 10,
       
      34 => 10,
       
      35 => 10        
   ), 



    3 => array( 

      0 => 'Cardassianer', 

      1 => 1.0, 

      2 => 0.95, 

      3 => 1.05, 

      4 => 8.0, 

      5 => 1.0, 

      6 => 1.0, 

      7 => 0.9, 

      8 => 1.0, 

      9 => 1.15, 

      10 => 1.15, 

      11 => 1.15, 

      12 => 1.1, 

      13 => 1.1, 

      14 => 1.1, 

      15 => 0.95, 

      16 => 1.05, 

      17 => 1.0, 

      18 => 0.9, 

      19 => 1.0, 

      20 => 1.05, 

      21 => 3, 

      22 => true, 

      23 => 0.8, 

      24 => 0.8, 

      25 => 0.6,

      26 => 0.6, 

      27 => 0.6, 

      28 => 1.1,

      29 => array(1, 1, 0, 0, 1),

      30 => true,
        
      31 => 30,
        
      32 => 10,
       
      33 => 10,
       
      34 => 10,
       
      35 => 10        

    ), 



   4 => array( 

      0 => 'Dominion', 

      1 => 1.2, 

      2 => 0.95, 

      3 => 1.05, 

      4 => 6.0, 

      5 => 1.2, 

      6 => 1.2, 

      7 => 1.15, 

      8 => 1.0, 

      9 => 0.80, 

      10 => 0.80, 

      11 => 0.80, 

      12 => 0.9, 

      13 => 1.35, 

      14 => 1.15, 

      15 => 1.15, 

      16 => 1.25, 

      17 => 0.95, 

      18 => 1.05, 

      19 => 1.0, 

      20 => 1.25, 

      21 => 2, 

      22 => true, 

      23 => 1.15, 

      24 => 1.15, 

      25 => 1.0,

      26 => 1.0, 

      27 => 1.0, 

      28 => 1.3,

      29 => array(1, 1, 1, 1, 1),

      30 => false,
       
      31 => 30,
       
      32 => 20,
       
      33 => 20,
       
      34 => 0,
       
      35 => 0       

   ), 



   5 => array( 

      0 => 'Ferengi', 

      1 => 0.95, 

      2 => 0.75, 

      3 => 0.85, 

      4 => 8.0, 

      5 => 0.85, 

      6 => 0.80, 

      7 => 1.05, 

      8 => 1.0, 

      9 => 1.25, 

      10 => 1.25, 

      11 => 1.50, 

      12 => 1.0, 

      13 => 0.6, 

      14 => 0.9, 

      15 => 0.95, 

      16 => 0.95, 

      17 => 1.0, 

      18 => 1.0, 

      19 => 1.0, 

      20 => 1.0, 

      21 => 4.5, 

      22 => true, 

      23 => 0.7, 

      24 => 0.7, 

      25 => 0.95,

      26 => 0.85, 

      27 => 0.85, 

      28 => 1.25,

      29 => array(0, 1, 0, 1, 1),

      30 => true,
       
      31 => 30,
       
      32 => 0,
       
      33 => 15,
       
      34 => 0,
       
      35 => 0      

), 



    6 => array( 

       0 => 'Borg', 

       1 => 0.75, 

       2 => 1.0, 

       3 => 1.2, 

       4 => 1.0, 

       5 => 0.95, 

       6 => 1.0, 

       7 => 1.2, 

       8 => 1.0, 

       9 => 1.2, 

       10 => 1.2, 

       11 => 1.2, 

       12 => 1.5, 

       13 => 1.5, 

       14 => 1.5, 

       15 => 1.5, 

       16 => 1.5, 

       17 => 1.5, 

        18 => 1.3, 

        19 => 1.1, 

        20 => 2.0, 

        21 => 6, 

      22 => false,

      23 => 1.5, 

      24 => 1.2, 

      25 => 1.0,

      26 => 1.5, 

      27 => 1.2, 

      28 => 1.0,

      29 => array(0, 0, 0, 0, 0),

      30 => false,
        
      31 => 0,
        
      32 => 30,
       
      33 => 30,
       
      34 => 30,
       
      35 => 30        

    ), 



    7 => array( 

        0 => 'Orion Syndacate', 

       1 => 1.1, 

       2 => 1.1, 

       3 => 1.1, 

       4 => 1.0, 

       5 => 0.8, 

       6 => 0.8, 

       7 => 0.8, 

       8 => 1.0, 

       9 => 1.1, 

       10 => 1.1, 

       11 => 1.25, 

       12 => 1.5, 

       13 => 1.0, 

       14 => 1.0, 

       15 => 1.0, 

       16 => 1.0, 

       17 => 1.0, 

        18 => 1.0, 

        19 => 1.0, 

        20 => 1.15, 

        21 => 1.35, 
       
      22 => false, 

      23 => 1.0, 

      24 => 1.0, 

      25 => 1.0,

      26 => 1.0, 

      27 => 1.0, 

      28 => 1.0,

      29 => array(0, 0, 0, 0, 0),

      30 => false,
        
      31 => 0,
        
      32 => 10,
       
      33 => 10,
       
      34 => 10,
       
      35 => 10        

     ), 



    8 => array( 

      0 => 'Breen', 

       1 => 1.20, 

       2 => 0.95, 

       3 => 1.10, 

       4 => 8.0, 

       5 => 1.1, 

      6 => 1.1, 

      7 => 1.0, 

      8 => 1.0, 

      9 => 0.90, 

      10 => 0.95, 

      11 => 0.90, 

       12 => 0.8, 

       13 => 0.85, 

       14 => 1.25, 

       15 => 1.05, 

      16 => 1.0, 

      17 => 1.0, 

      18 => 1.1, 

       19 => 1.0, 

      20 => 1.2, 

      21 => 5, 
       
      22 => true,

      23 => 0.7, 

      24 => 1.1, 

      25 => 0.8,

      26 => 1.0, 

      27 => 1.2, 

      28 => 1.4,

      29 => array(1, 1, 1, 1, 1),

      30 => false,
        
      31 => 30,
        
      32 => 10,
       
      33 => 10,
       
      34 => 20,
       
      35 => 20        

   ), 



    9 => array( 

      0 => 'Hirogen',

      1 => 1.2,

      2 => 1.1,

      3 => 1.1,

      4 => 6.5,

      5 => 1.2,

      6 => 1.2,

      7 => 1.05,

      8 => 1.0,

      9 => 1.1,

      10 => 1.1,

      11 => 1.1,

      12 => 0.9,

      13 => 1.2,

      14 => 1.2,

      15 => 1.2,

      16 => 1.3,

      17 => 0.8,

      18 => 0.95,

      19 => 1.15,

      20 => 1.2,

      21 => 5, 
       
      22 => true,

      23 => 1.15, 

      24 => 1.1, 

      25 => 1.05,

      26 => 1.0, 

      27 => 1.2, 

      28 => 1.45,

      29 => array(0, 0, 1, 1, 1),

      30 => false,
        
      31 => 30,
        
      32 => 20,
       
      33 => 20,
       
      34 => 0,
       
      35 => 0        

   ), 



    10 => array( 

      0 => 'Krenim', 

      1 => 1.05, 

      2 => 0.9, 

      3 => 1.05, 

      4 => 1.05, 

      5 => 1.0, 

      6 => 1.05, 

      7 => 1.05, 

      8 => 0.8, 

      9 => 1.05, 

      10 => 0.85, 

      11 => 1.0, 

      12 => 1.25, 

      13 => 1.0, 

      14 => 0.85, 

      15 => 0.85, 

      16 => 0.9, 

      17 => 1.1, 

      18 => 1.05, 

      19 => 1.1, 

      20 => 1.15, 

      21 => 3, 
       
      22 => false, 

      23 => 0.9, 

      24 => 0.9, 

      25 => 0.9,

      26 => 0.9, 

      27 => 0.9, 

      28 => 0.9,

      29 => array(0, 0, 0, 0, 0),

      30 => false,
        
      31 => 0,
        
      32 => 0,
       
      33 => 0,
       
      34 => 0,
       
      35 => 0        

   ), 

    

    

    11 => array( 

      0 => 'Kazon', 

       1 => 0.9, 

      2 => 0.6, 

       3 => 1.05, 

       4 => 9.0, 

      5 => 0.9, 

       6 => 0.75, 

       7 => 1.0, 

      8 => 1.0, 

       9 => 1.0, 

      10 => 1.0, 

       11 => 1.0, 

      12 => 1.2, 

      13 => 1.1, 

      14 => 1.35, 

      15 => 0.9, 

      16 => 1.15, 

      17 => 0.8, 

      18 => 1.2, 

      19 => 0.7, 

      20 => 0.9, 

      21 => 4, 
       
      22 => true, 

      23 => 0.6, 

      24 => 0.6, 

      25 => 0.4,

      26 => 0.4, 

      27 => 0.4, 

      28 => 0.65,

      29 => array(0, 0, 1, 1, 1),

      30 => false,
        
      31 => 30,
        
      32 => 5,
       
      33 => 5,
       
      34 => 5,
       
      35 => 5        

    ), 





   12 => array( 

      0 => 'Menschen 29th', 

      1 => 1.0, 

      2 => 0.8, 

      3 => 1.0, 

      4 => 1.0, 

      5 => 1.0, 

      6 => 1.0, 

      7 => 1.0, 

      8 => 1.0, 

      9 => 1.0, 

      10 => 1.0, 

      11 => 1.0, 

      12 => 1.0, 

      13 => 1.0, 

      14 => 1.0, 

      15 => 1.0, 

      16 => 1.0, 

      17 => 1.0, 

      18 => 1.0, 

      19 => 0.75, 

      20 => 1.1, 

      21 => 3, 
       
      22 => false, 

      23 => 1.0, 

      24 => 1.0, 

      25 => 1.0,

      26 => 1.0, 

      27 => 1.0, 

      28 => 1.0,

      29 => array(0, 0, 0, 0, 0),

      30 => false,
       
      31 => 50,
       
      32 => 30,
       
      33 => 30,
       
      34 => 30,
       
      35 => 30       

   ), 




   13 => array( 

      0 => 'Siedler', 

      1 => 0.75, 

      2 => 0.50, 

      3 => 1.0, 

      4 => 8.0, 

      5 => 0.50, 

      6 => 0.50, 

      7 => 1.0, 

      8 => 1.0, 

      9 => 1.5, 

      10 => 1.5, 

      11 => 1.5, 

      12 => 1.5, 

      13 => 1.0, 

      14 => 1.0, 

      15 => 1.0, 

      16 => 1.0, 

      17 => 1.0, 

      18 => 1.0, 

      19 => 0.75, 

      20 => 1.1, 

      21 => 6, 
       
      22 => false, 

      23 => 1.0, 

      24 => 1.0, 

      25 => 1.0,

      26 => 1.0, 

      27 => 1.0, 

      28 => 1.0,

      29 => array(0, 0, 0, 0, 0),

      30 => true,
       
      31 => 0,
       
      32 => 10,
       
      33 => 10,
       
      34 => 10,
       
      35 => 10       

   ), 
); 

?> 
