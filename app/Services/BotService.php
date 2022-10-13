<?php

namespace App\Services;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

use Log;
use Response;
use Illuminate\Support\Facades\Redis;

class BotService
{
    private $imgurClientID;
    private $imgurClientSecret;
    private $imgurSealAlbum;
    private $imgurGirlAlbum;
    private $imgurFoodAlbum;

    public function __construct()
    {
        $this->imgurClientID = config('bot.imgur_client_id');
        $this->imgurClientSecret = config('bot.imgur_client_secret');
        $this->imgurAccesstoken = config('bot.imgur_accesstoken');

        $this->imgurSealAlbum = config('bot.imgur_seal_album');
        $this->imgurGirlAlbum = config('bot.imgur_girl_album');
        $this->imgurFoodAlbum = config('bot.imgur_food_album');

        $this->channelToken = config('bot.pawapuro.channel_token');
    }

    //關鍵字匹配判斷
    public function reconizeKeywords($msg,$keywords,$channelToken,$channelSecret){
        $reply_token = '';
        if (isset($msg['replyToken'])) $reply_token = $msg['replyToken'];
        if ($reply_token != '00000000000000000000000000000000' && $reply_token != 'ffffffffffffffffffffffffffffffff') {
            // Log::Info('msg:'.json_encode($msg));
            
            if (!isset($msg['message']['text'])) {
                return "";
            } else if ($msg['message']['text'] == '關鍵字') {
                $keywords_list = "目前可用關鍵字如下：";
                
                foreach ($keywords as $data) {
                    $keywords_list .= $data->keyword.'、';
                }    
    
                $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($channelToken);
                $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);
    
                $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($keywords_list);
                $bot->replyMessage($msg['replyToken'], $textMessageBuilder);
    
            } else {
                foreach ($keywords as $data) {
                    if ($data->type == 'part') {
                        if (strstr($msg['message']['text'],$data->keyword)){
                            $this->replyByType($data,$msg['replyToken'],$channelToken,$channelSecret);
                        }
                    } 
                    
                    if ($data->type == 'full') {
                        if ($msg['message']['text'] == $data->keyword){
                            $this->replyByType($data,$msg['replyToken'],$channelToken,$channelSecret);
                        }
                    }
                }
            }
        } else {
            return "bot test ok";
        }
        
    }

    //依照 replyType 輸出回應
    public function replyByType($data,$replyToken,$channelToken,$channelSecret){
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($channelToken);
        $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);

        switch($data->reply_type) 
        {
            case 'text':
                $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($data->reply_content);
                $bot->replyMessage($replyToken, $textMessageBuilder);
                break;
            case 'pic':
                $pic = $data->reply_content;
                           
                $imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($pic, $pic);
                $bot->replyMessage($replyToken, $imageMessageBuilder);
                break;
            case 'album':
                $album = $this->getAlbum($data->reply_content)->data;
                $pics = $album[rand(1,count($album))-1]->link;
                            
                $imageMessageBuilder = new \LINE\LINEBot\MessageBuilder\ImageMessageBuilder($pics, $pics);
                $bot->replyMessage($replyToken, $imageMessageBuilder);
                break;
            default:
                $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($data->reply_content);
                $bot->replyMessage($replyToken, $textMessageBuilder);
                break;
        }
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

    //上傳指定檔案到指定相本
    public function uploadAlbum($filename,$album_id){
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
    }
}
