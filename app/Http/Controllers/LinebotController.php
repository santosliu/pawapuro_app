<?php

namespace App\Http\Controllers;

use Log;
use Redis;
use Response;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Keywords;
use GuzzleHttp\Client;

class LinebotController extends Controller
{
    private $channelToken;
    private $channelSecret;
    private $channelId;
    private $httpClient;
    private $bot;
    private $keywords;

    public function __construct()
    {
        $this->channelToken = config('bot.channel_token');
        $this->channelSecret = config('bot.channel_secret');
        $this->channelId = config('bot.channel_id');
        
        $this->httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($this->channelToken);
        $this->bot = new \LINE\LINEBot($this->httpClient, ['channelSecret' => $this->channelSecret]);        

        if (env('APP_ENV') == 'production') {
            if (Redis::exists('keywords:list')) {
                $this->keywords = json_decode(Redis::get('keywords:list'));
            } else {
                $this->keywords = Keywords::get();
                Redis::set('keywords:list', json_encode($this->keywords), 'EX', 360);
            }
        } else {
            $this->keywords = Keywords::get();
        }
    }

    public function getAlbum($hash){
        $images = "";

        if (Redis::exists($hash)) {
            $images = json_decode(Redis::get($hash));
        } else {
            $client = new Client();
        
            $response = $client->get('https://api.imgur.com/3/album/'.$hash.'/images',[
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Client-ID fa8c58678371db9',
                ],
            ]);
        
            $images = json_decode($response->getBody());

            Redis::set($hash, json_encode($images), 'EX', 360);
        }
        
        return $images;
    }


    public function msgSend($msgData){
        
        foreach ((array)$msgData as $msg) {
            $replyToken = $msg['replyToken'];
            
            if ($msg['message']['type'] == 'text') {
                
                if ($msg['message']['text'] == "抽圖") {
                    //從 Imgur 隨機挖圖出來
                }

                //貼海豹圖
                if ($msg['message']['text'] == "你太歐澤") {
                    $seals = $this->getAlbum('Lnpbn3H')->data;
                    // $seals = [
                    //     'https://i.imgur.com/3epMKoW.png',
                    //     'https://i.imgur.com/HjclLnJ.png',
                    //     'https://i.imgur.com/7JGCOXS.png',
                    //     'https://i.imgur.com/qOpaf85.png',
                    //     'https://i.imgur.com/45PWgTR.png',
                    //     'https://i.imgur.com/zx0WZxY.png',
                    //     'https://i.imgur.com/MHyFRfg.png',
                    //     'https://i.imgur.com/XWkyn4G.png',
                    //     'https://i.imgur.com/3aP1ZWa.png',
                    //     'https://i.imgur.com/YWs7I2Q.png',
                    //     'https://i.imgur.com/7cb9pta.png',
                    //     'https://i.imgur.com/xAK6v3K.png',
                    //     'https://i.imgur.com/UPCa816.png',
                    //     'https://i.imgur.com/Be4h9ii.png',
                    //     'https://i.imgur.com/MaJ94YM.png',
                    // ];
                    $seal_pic = $seals[rand(0,count($seals))]->link;
                    $imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($seal_pic, $seal_pic);
                    $this->bot->replyMessage($replyToken, $imageMessageBuilder);
                }

                $keywords = $this->keywords;
                foreach ($keywords as $data) {
                    if ($msg['message']['text'] == $data->keyword){
                        $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($data->reply_content);
                        $this->bot->replyMessage($replyToken, $textMessageBuilder);
                    }    
                }
            }
        }
    }

    public function msgReceive(Request $request){
        $msgData = $request->events;
        // Log::info($request);        
        $this->msgSend($msgData);

        $this->resp['status'] = true;        
        return Response::json($this->resp);
    }
}
