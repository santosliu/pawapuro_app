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

    public function downloadPic($pic_id){
        $client = new Client();
        //curl -v -X GET https://api.line.me/v2/bot/message/8923937667228/content -H 'Authorization: Bearer Tvyb9ZQ2Fe0qFPhdHMSqwPEoipCdrmbEicY6VcVssD1TiK4i9/y8lMAVvsJNMtXZ5NLOYJEORz42ydC7p1fRAg7a3ucFi1ixSj0dfHe/axa7jWo28x88PqQrJKYqrUqZta+w52C88psQ3Rg4fNZT/QdB04t89/1O/w1cDnyilFU='
        $response = $client->get('https://api.line.me/v2/bot/message/'.$pic_id.'/content',[
            'verify' => false,
            'headers' => [
                'Authorization' => 'Bearer '.$this->channelToken,
            ],
        ]);
        
        $imageBody = $response->getBody();
        Log::info($images);

        // return $filepath;
    }

    //取得相本內容
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
            
            //抓小光貼的妹子圖丟到 Imgur 上
            if ($msg['message']['type'] == 'image') {
                $user_id = $msg['source']['userId'];
                $pic_id = $msg['message']['id'];

                $this->downloadPic($pic_id);
                //curl -v -X GET https://api.line.me/v2/bot/message/8923937667228/content -H 'Authorization: Bearer Tvyb9ZQ2Fe0qFPhdHMSqwPEoipCdrmbEicY6VcVssD1TiK4i9/y8lMAVvsJNMtXZ5NLOYJEORz42ydC7p1fRAg7a3ucFi1ixSj0dfHe/axa7jWo28x88PqQrJKYqrUqZta+w52C88psQ3Rg4fNZT/QdB04t89/1O/w1cDnyilFU='

                // if ($userId == '') {

                // }
            }


            if ($msg['message']['type'] == 'text') {
                
                //貼妹子圖
                if ($msg['message']['text'] == "光大濕") {
                    $girls = $this->getAlbum('RGN0xHm')->data;
                    $girl_pic = $girls[rand(1,count($girls))-1]->link;
                    
                    $imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($girl_pic, $girl_pic);
                    $this->bot->replyMessage($replyToken, $imageMessageBuilder);
                }

                //貼海豹圖
                if ($msg['message']['text'] == "你太歐澤") {
                    $seals = $this->getAlbum('Lnpbn3H')->data;
                    $seal_pic = $seals[rand(1,count($seals))-1]->link;
                    
                    $imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($seal_pic, $seal_pic);
                    $this->bot->replyMessage($replyToken, $imageMessageBuilder);
                }

                //一般關鍵字回應
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
