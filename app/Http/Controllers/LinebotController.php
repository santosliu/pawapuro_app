<?php

namespace App\Http\Controllers;

use DB;
use PDO;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Validator;
use Response;

use GuzzleHttp\Client;

class LinebotController extends Controller
{
    public function msgSend(Request $request){
        $channelToken = config('bot.channel_token');
        $channelSecret = config('bot.channel_secret');
        $channelId = config('bot.channel_id');
        
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($channelToken);
        $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);

        dd($bot);

    }

    public function msgReceive(Request $request){
        $channelToken = config('bot.channel_token');
        $channelSecret = config('bot.channel_secret');
        $channelId = config('bot.channel_id');
        
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($channelToken);
        $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);

    }
}
