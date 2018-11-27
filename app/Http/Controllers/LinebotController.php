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

        $this->ImgurClientID = config('bot.imgur_client_id');
        $this->ImgurClientSecret = config('bot.imgur_client_secret');

        $this->ImgurSealAlbum = config('bot.imgur_seal_album');
        $this->ImgurGirlAlbum = config('bot.imgur_girl_album');
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
    public function getAlbum($hash){
        $images = "";

        if (Redis::exists($hash)) {
            $images = json_decode(Redis::get($hash));
        } else {
            $client = new Client();
        
            $response = $client->get('https://api.imgur.com/3/album/'.$hash.'/images',[
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Client-ID '.$this->imgurClientID,
                ],
            ]);
        
            $images = json_decode($response->getBody());

            Redis::set($hash, json_encode($images), 'EX', 360);
        }
        
        return $images;
    }

    //取得 Imgur Accesstoken
    public function getImgurAccesstoken(){

    }

    //上傳指定檔案到指定相本
    public function uploadAlbum($filename,$album_id){
        
        // $accessToken = getImgurAccesstoken();
        
        // $client = new Client();
        
        // $response = $client->post('https://api.imgur.com/3/image',[
        //     'verify' => false,
        //     'headers' => [
        //         'Authorization' => 'Client-ID fa8c58678371db9',
        //         'Content-Type' => 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW',
        //     ],
        //     'body' => [
        //         'album' => $album_id,
        //         'image' => base64_encode($filename),
        //     ],
        // ]);

        $client_id = $this->imgurClientID;
        $client_secret = $this->imgurClientSecret;
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
            'Authorization: Client-ID '.$client_id,
            'Authorization: Bearer 4084d3ff3b0c295189c9c7402af7de265f5cf894',
        ));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $pvars);
        $response = curl_exec($curl);
        curl_close ($curl);

        Log::info($response);
    }

    public function msgSend($msgData){
        
        foreach ((array)$msgData as $msg) {
            $replyToken = $msg['replyToken'];
            
            //抓小光貼的妹子圖丟到 Imgur 上
            if ($msg['message']['type'] == 'image') {
                $user_id = $msg['source']['userId'];
                $pic_id = $msg['message']['id'];

                $filename = $this->downloadPic($pic_id);
                $this->uploadAlbum($filename,$this->ImgurGirlAlbum);
                // if ($userId == '') {

                // }
            }


            if ($msg['message']['type'] == 'text') {
                
                //貼妹子圖
                if ($msg['message']['text'] == "光大濕") {
                    $girls = $this->getAlbum($this->ImgurGirlAlbum)->data;
                    $girl_pic = $girls[rand(1,count($girls))-1]->link;
                    
                    $imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($girl_pic, $girl_pic);
                    $this->bot->replyMessage($replyToken, $imageMessageBuilder);
                }

                //貼海豹圖
                if ($msg['message']['text'] == "你太歐澤") {
                    $seals = $this->getAlbum($this->ImgurSealAlbum)->data;
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
