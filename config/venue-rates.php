<?php

// Official GSU venue rates, seating and packages from the published rental sheet (Venues/Facilities & Rentals).
// 'aliases' map facility names used in the system to the sheet's name.
return [
    ['name' => 'PaghiUsA Hall',  'capacity' => '500-600 persons',    'rate' => 'PhP 15,000.00',          'img' => 'paghiusa-hall.jpg',  'package' => ['Fully Airconditioned w/sounds', '700 monoblock chairs', '10 wooden long tables', '4 Industrial Fan', 'Maximum of 8hrs.']],
    ['name' => 'bUsAlian Hall',  'capacity' => '70-80 persons',      'rate' => 'PhP 10,000.00',          'img' => 'busalian-hall.jpg',  'package' => ['Fully Airconditioned', '90 monoblock chairs', '2 wooden long tables', 'Maximum of 8hrs.']],
    ['name' => 'TiripUnAn Hall', 'capacity' => '500-600 persons',    'rate' => 'PhP 8,000.00',           'img' => 'tiripunan-hall.jpg', 'package' => ['600 monoblock chairs', '4 wooden long tables', '4 Industrial Fan', 'Maximum of 8hrs.']],
    ['name' => 'Balay ni jUAn',  'capacity' => '50-60 persons',      'rate' => 'PhP 8,000.00',           'img' => null,                 'package' => ['Fully Airconditioned', '60 monoblock chairs', '2 wooden long tables', 'Maximum of 8hrs.']],
    ['name' => 'E-Hub',          'capacity' => '100-150 persons',    'rate' => 'PhP 5,000.00',           'img' => null,                 'package' => ['Fully Airconditioned', '150 monoblock chairs', '4 wooden long tables', 'Maximum of 8hrs.']],
    ['name' => 'New AVR',        'capacity' => '80-100 persons',     'rate' => 'PhP 5,000.00',           'img' => 'new-avr.jpg',        'package' => ['Fully Airconditioned', '100 monoblock chairs', '4 wooden long tables', 'Maximum of 8hrs.']],
    ['name' => 'CEA AVR',        'capacity' => '60-80 persons',      'rate' => 'PhP 2,000.00',           'img' => null,                 'package' => ['80 monoblock chairs', '2 square plastic tables', '2 Industrial Fan', 'Maximum of 8hrs.']],
    ['name' => 'CCS AVR',        'aliases' => ['ICT AVR'], 'capacity' => '60-80 persons',      'rate' => 'PhP 2,000.00',           'img' => null,                 'package' => ['80 monoblock chairs', '2 square plastic tables', '2 Industrial Fan', 'Maximum of 8hrs.']],
    ['name' => 'CBA AVR',        'capacity' => '60-80 persons',      'rate' => 'PhP 2,000.00',           'img' => null,                 'package' => ['80 monoblock chairs', '2 square plastic tables', '2 Industrial Fan', 'Maximum of 8hrs.']],
    ['name' => 'Class Room/s',   'capacity' => '25-30 persons/room', 'rate' => 'PhP 50.00/head in a day', 'img' => null,                'package' => ['Ceiling Fan']],
    ['name' => 'Open Space',     'aliases' => ['Covered Gym'], 'capacity' => '-',                  'rate' => 'PhP 300.00/day',         'img' => null,                'package' => ['Electrical']],
    ['name' => 'Track Oval',     'aliases' => ['Grand Stand', 'GrandStand/Track Oval'], 'capacity' => '-',                  'rate' => 'PhP 1,000.00/day',       'img' => 'grand-stand.jpg',    'package' => ['Maximum of 8hrs.']],
];
