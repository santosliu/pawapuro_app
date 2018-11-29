<?php

return [

    /*
    Line Bot 所需參數皆在此
    */

    'channel_id' => env('LINEBOT_CHANNEL_ID'),
    'channel_token' => env('LINEBOT_CHANNEL_TOKEN'),
    'channel_secret' => env('LINEBOT_CHANNEL_SECRET'),
    
    'imgur_client_id' => env('IMGUR_CLIENT_ID'),
    'imgur_client_secret' => env('IMGUR_CLIENT_SECRET'),
    'imgur_accesstoken' => env('IMGUR_ACCESSTOKEN'),

    'imgur_seal_album' => env('IMGUR_SEAL_ALBUM'),
    'imgur_girl_album' => env('IMGUR_GIRL_ALBUM'),
    'imgur_food_album' => env('IMGUR_FOOD_ALBUM'),
];
