<?php

return [

    'currency' => 'EUR',

    // Airport transfer price per leg (euro cents), chosen by passenger count.
    'transfer_prices' => [
        4 => 4500,   // car, up to 4 passengers
        8 => 7000,   // minivan, up to 8 passengers
        16 => 11000, // minibus, up to 16 passengers
    ],

    'max_guests' => 16,

    // Tee sheet generation defaults used by the seeder and the artisan command.
    'tee_sheet' => [
        'first' => '07:30',
        'last' => '15:00',
        'interval_minutes' => 10,
        'capacity' => 4,
    ],

];
