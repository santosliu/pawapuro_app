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

    private $imgurClientID;
    private $imgurClientSecret;
    private $imgurSealAlbum;
    private $imgurGirlAlbum;

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

        $this->imgurClientID = config('bot.imgur_client_id');
        $this->imgurClientSecret = config('bot.imgur_client_secret');
        $this->imgurAccesstoken = config('bot.imgur_accesstoken');

        $this->imgurSealAlbum = config('bot.imgur_seal_album');
        $this->imgurGirlAlbum = config('bot.imgur_girl_album');
    }

    //取得 LINE 群圖片
    public function downloadPic($pic_id){
        $client = new Client();
        $response = $client->get('https://api.line.me/v2/bot/message/'.$pic_id.'/content',[
            'verify' => false,
            'headers' => [
                'Authorization' => 'Bearer '.$this->channelToken,
            ],
        ]);
        
        $imageBody = $response->getBody();
        $filename = storage_path('girls/'.uniqid().'.png');
        file_put_contents($filename, $imageBody);

        return $filename;
    }

    //取得相本內容
    public function getAlbum($album_id){
        $images = "";

        if (Redis::exists($album_id)) {
            $images = json_decode(Redis::get($album_id));
        } else {
            $client = new Client();
        
            $response = $client->get('https://api.imgur.com/3/album/'.$album_id.'/images',[
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Client-ID '.$this->imgurClientID,
                ],
            ]);
        
            $images = json_decode($response->getBody());

            Redis::set($album_id, json_encode($images), 'EX', 360);
        }
        
        return $images;
    }

    //上傳指定檔案到指定相本
    public function uploadAlbum($filename,$album_id){
        // $client = new Client();
        
        // $handle = fopen($filename, "r");
        // $data = fread($handle, filesize($filename));

        // $response = $client->post('https://api.imgur.com/3/image',[
        //     'verify' => false,
        //     'headers' => [
        //         // 'Authorization: Bearer '.$this->imgurAccesstoken,
        //         'Authorization: Bearer '.$this->imgurClientID,
        //         'Content-Type' => 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW',
        //     ],
        //     'form_params' => [
        //         // 'album' => $album_id,
        //         'image' => base64_encode($data),
        //     ],
        // ]);

        //存到資料庫備用

        $handle = fopen($filename, "r");
        $data = fread($handle, filesize($filename));
        $pvars   = array(
            'image' => base64_encode($data),
            'album' => $album_id,
        );

        $timeout = 30;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.imgur.com/3/image.json');
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            #'Authorization: Client-ID '.$client_id,
            'Authorization: Bearer '.$this->imgurAccesstoken,
        ));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $pvars);
        $response = curl_exec($curl);
        curl_close ($curl);

        // Log::info($response);
    }

    public function msgSend($msgData){
        
        foreach ((array)$msgData as $msg) {
            $replyToken = $msg['replyToken'];
            
            //抓小光貼的妹子圖丟到 Imgur 上
            if ($msg['message']['type'] == 'image') {
                $user_id = $msg['source']['userId'];
                $pic_id = $msg['message']['id'];

                if ($user_id == 'Ueb13bb47744e0b1058177378357c5978') {
                    $filename = $this->downloadPic($pic_id);
                    $this->uploadAlbum($filename,$this->imgurGirlAlbum);
                }
            }


            if ($msg['message']['type'] == 'text') {
                
                //貼妹子圖
                if ($msg['message']['text'] == "光大濕") {
                    $girls = $this->getAlbum($this->imgurGirlAlbum)->data;
                    $girl_pic = $girls[rand(1,count($girls))-1]->link;
                    
                    $imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($girl_pic, $girl_pic);
                    $this->bot->replyMessage($replyToken, $imageMessageBuilder);
                }

                //貼海豹圖
                if ($msg['message']['text'] == "你太歐澤") {
                    $seals = $this->getAlbum($this->imgurSealAlbum)->data;
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
