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

class MonicaBotController extends Controller
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

        $this->channelToken = config('bot.monica.channel_token');
        $this->channelSecret = config('bot.monica.channel_secret');
        $this->channelId = config('bot.monica.channel_id');
        
        $this->httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($this->channelToken);
        $this->bot = new \LINE\LINEBot($this->httpClient, ['channelSecret' => $this->channelSecret]);        

        if (env('APP_ENV') == 'production') {
            if (Redis::exists('keywords:monica:list')) {
                $this->keywords = json_decode(Redis::get('keywords:monica:list'));
            } else {
                $this->keywords = Keywords::where([
                    'game' => 'monica'
                ])->get();
                
                Redis::set('keywords:monica:list', json_encode($this->keywords), 'EX', 60);
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

        foreach ($msgData as $msg) {
            $replyToken = '';
            if (isset($msg['replyToken'])) $replyToken = $msg['replyToken'];

            //加入簡介
            if ($msg['type'] == 'memberJoined') {
                foreach ($keywords as $data) {
                    if ($data->type == 'member_join') {
                        $text = $data->reply_content;
                        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text);
                        $this->bot->replyMessage($replyToken, $textMessageBuilder);
                    }
                }                
            }

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
