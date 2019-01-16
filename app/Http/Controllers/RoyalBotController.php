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

class RoyalBotController extends Controller
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

        $this->channelToken = config('bot.royal.channel_token');
        $this->channelSecret = config('bot.royal.channel_secret');
        $this->channelId = config('bot.royal.channel_id');
        
        $this->httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($this->channelToken);
        $this->bot = new \LINE\LINEBot($this->httpClient, ['channelSecret' => $this->channelSecret]);        

        if (env('APP_ENV') == 'production') {
            if (Redis::exists('keywords:royal:list')) {
                $this->keywords = json_decode(Redis::get('keywords:royal:list'));
            } else {
                $this->keywords = Keywords::where([
                    'game' => 'royal'
                ])->get();
                
                Redis::set('keywords:royal:list', json_encode($this->keywords), 'EX', 60);
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
            $replyToken = $msg['replyToken'];

            if ($msg['type'] == 'memberLeft') {
                $text = "啊啊～又一位英勇的戰士離開了～
                祝他一路順風，頭髮茂盛～";
                $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($text);
                $this->bot->replyMessage($replyToken, $textMessageBuilder);
            }

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

            /*
            //讀取 keywords，依照 type 進行不同的回應
            if ($msg['message']['type'] == 'text') {
                foreach ($keywords as $data) {
                    if ($data->type == 'part') {
                        if (strstr($msg['message']['text'],$data->keyword)){
                            switch($data->reply_type) 
                            {
                                case 'text':
                                    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($data->reply_content);
                                    $this->bot->replyMessage($replyToken, $textMessageBuilder);
                                    break;
                                case 'pic':
                                    $pic = $data->reply_content;
                                    
                                    $imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($pic, $pic);
                                    $this->bot->replyMessage($replyToken, $imageMessageBuilder);
                                    break;
                                case 'album':
                                    $album = $service->getAlbum($data->reply_content)->data;
                                    $pics = $album[rand(1,count($album))-1]->link;
                                    
                                    $imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($pics, $pics);
                                    $this->bot->replyMessage($replyToken, $imageMessageBuilder);
                                    break;
                            }
                        }
                    } 
                    
                    if ($data->type == 'full') {
                        if ($msg['message']['text'] == $data->keyword){
                            switch($data->reply_type) 
                            {
                                case 'text':
                                    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($data->reply_content);
                                    $this->bot->replyMessage($replyToken, $textMessageBuilder);
                                    break;
                                case 'pic':
                                    $pic = $data->reply_content;
                                    
                                    $imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($pic, $pic);
                                    $this->bot->replyMessage($replyToken, $imageMessageBuilder);
                                    break;
                                case 'album':
                                    $album = $service->getAlbum($data->reply_content)->data;
                                    $pics = $album[rand(1,count($album))-1]->link;
                                    
                                    $imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($pics, $pics);
                                    $this->bot->replyMessage($replyToken, $imageMessageBuilder);
                                    break;
                            }
                        }
                    }
                }
            }
            */
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
