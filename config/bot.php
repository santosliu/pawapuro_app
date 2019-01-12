<?php

return [

    /*
    Line Bot 所需參數皆在此
    */
    
    'pawapuro' => [
        'channel_id' => env('LINEBOT_CHANNEL_ID'),
        'channel_token' => env('LINEBOT_CHANNEL_TOKEN'),
        'channel_secret' => env('LINEBOT_CHANNEL_SECRET'),
    ],
    'royal' => [
        'channel_id' => '1627805174',
        'channel_token' => 'P/khlV9tmdCGMwlfr2NZhymQIDVWwGOIWcEqo70ic5sJN123NexrqSufvoCOucLj6iPfLeeXRxtQD096wSQ7ykNJf5sUK6ArvZgd4+9Ls6nSPmVZCsuLl4KgWprH0YaJH1TKBUvPyIqd+rAWCK1fAQdB04t89/1O/w1cDnyilFU=',
        'channel_secret' => '2f629041b6ab98e0d0629833e056c7f4',
    ],

    'imgur_client_id' => env('IMGUR_CLIENT_ID'),
    'imgur_client_secret' => env('IMGUR_CLIENT_SECRET'),
    'imgur_accesstoken' => env('IMGUR_ACCESSTOKEN'),

    'imgur_seal_album' => env('IMGUR_SEAL_ALBUM'),
    'imgur_girl_album' => env('IMGUR_GIRL_ALBUM'),
    'imgur_food_album' => env('IMGUR_FOOD_ALBUM'),
];
