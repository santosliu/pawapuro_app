<?php

namespace App\Http\Controllers;

use Log;
use Redis;
use Response;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Keywords;
use App\Services\BotService;
use GuzzleHttp\Client;

class LinebotController extends Controller
{
    private $channelToken;
    private $channelSecret;
    private $channelId;
    
    private $httpClient;
    private $bot;
    private $keywords;    

    private $imgurClientID;
    private $imgurClientSecret;
    private $imgurSealAlbum;
    private $imgurGirlAlbum;
    private $imgurFoodAlbum;

    public function __construct()
    {
        $this->game = 'pawapuro';
        $this->channelToken = config('bot.pawapuro.channel_token');
        $this->channelSecret = config('bot.pawapuro.channel_secret');
        $this->channelId = config('bot.pawapuro.channel_id');
        
        $this->httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($this->channelToken);
        $this->bot = new \LINE\LINEBot($this->httpClient, ['channelSecret' => $this->channelSecret]);        

        if (env('APP_ENV') == 'production') {
            if (Redis::exists('keywords:'.$this->game.':list')) {
                $this->keywords = json_decode(Redis::get('keywords:'.$this->game.':list'));
            } else {
                $this->keywords = Keywords::where([
                    'game' => $this->game,
                ])->get();

                Redis::set('keywords:'.$this->game.':list', json_encode($this->keywords), 'EX', 360);
            }
        } else {
            $this->keywords = Keywords::get();
        }

        $this->imgurClientID = config('bot.imgur_client_id');
        $this->imgurClientSecret = config('bot.imgur_client_secret');
        $this->imgurAccesstoken = config('bot.imgur_accesstoken');

        $this->imgurSealAlbum = config('bot.imgur_seal_album');
        $this->imgurGirlAlbum = config('bot.imgur_girl_album');
        $this->imgurFoodAlbum = config('bot.imgur_food_album');
    }

    //送出訊息
    public function msgSend($msgData){
        $service = new BotService();
        $keywords = $this->keywords;

        foreach ((array)$msgData as $msg) {
            $replyToken = $msg['replyToken'];
            
            //抓小光貼的妹子圖丟到 Imgur 上
            // if ($msg['message']['type'] == 'image') {
            //     $user_id = $msg['source']['userId'];
            //     $pic_id = $msg['message']['id'];

            //     if ($user_id == 'Ueb13bb47744e0b1058177378357c5978') {
            //         $filename = $service->downloadPic($pic_id);
            //         $service->uploadAlbum($filename,$this->imgurGirlAlbum);
            //     }
            // }

            $service->reconizeKeywords($msg,$keywords,$this->channelToken,$this->channelSecret);
        }
    }

    //截收訊息
    public function msgReceive(Request $request){
        $msgData = $request->events;
        // Log::info($request);        
        $this->msgSend($msgData);

        $this->resp['status'] = true;        
        return Response::json($this->resp);
    }
}
