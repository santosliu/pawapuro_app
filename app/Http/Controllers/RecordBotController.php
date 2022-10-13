<?php

namespace App\Http\Controllers;

use Log;
use Illuminate\Support\Facades\Redis;
use Response;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Keywords;
use App\Models\Message_Records;
use App\Services\BotService;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class RecordBotController extends Controller
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

        $this->channelToken = config('bot.record.channel_token');
        $this->channelSecret = config('bot.record.channel_secret');
        $this->channelId = config('bot.record.channel_id');
        
        $this->httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($this->channelToken);
        $this->bot = new \LINE\LINEBot($this->httpClient, ['channelSecret' => $this->channelSecret]);        

        if (env('APP_ENV') == 'production') {
            $redis_data = Redis::get('keywords:record:list');
            if ($redis_data) {
                $this->keywords = json_decode($redis_data);
            } else {
                $this->keywords = Keywords::where([
                    'game' => 'record'
                ])->get();
                
                Redis::set('keywords:record:list', json_encode($this->keywords), 'EX', 60);
            }
        } else {
            $this->keywords = Keywords::where([
                'game' => 'record',
            ])->get();
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

            $service->reconizeKeywords($msg,$keywords,$this->channelToken,$this->channelSecret);
        }
    }

    //截收訊息
    public function msgReceive(Request $request){
        $msgData = $request->events;
              
        // $this->msgSend($msgData);

        // 記錄各 ID 最後發言時間
        $this->updateRecords($msgData);
        $this->resp['status'] = true;        
        return Response::json($this->resp);
    }

    /*
    *   更新最後發言時間
    */
    public function updateRecords($msgData){

        // Log::info('record:'.json_encode($msgData));

        foreach ($msgData as $msg) {
            $user_id = ''; $last_message = '貼圖/影片或傳檔'; $group_id = ''; $user_name = '';
            
            if (isset($msg['source']['userId'])) {
                $user_id = $msg['source']['userId'];

                // 取得 profile
                $profile = $this->getUserProfile($user_id);
                if (isset($profile->displayName)) {
                    $user_name = $profile->displayName;
                }
            }

            if (isset($msg['message']['text'])) {
                $last_message = $msg['message']['text'];
            }

            if (isset($msg['source']['groupId'])) {
                $group_id = $msg['source']['groupId'];
            }

            if (strlen($user_id) > 3) {
                $record = Message_Records::where([
                    'user_id' => $user_id,
                    'group_id' => $group_id
                ])->first();
                
                if (!$record) {
                    $record = new Message_Records();
                }
                
                $record->user_id = $user_id;
                if ($user_name != '') $record->user_name = $user_name;
                $record->group_id = $group_id;
                $record->last_message = $last_message;
                $record->message_count = $record->message_count + 1;
                $record->save();
            }
        }

    }

    public function getUserProfile($user_id){

        $client = new Client();

        try {
            $response = $client->get('https://api.line.me/v2/bot/profile/'.$user_id,[
                'verify' => false,
                'headers' => [
                    'Authorization' => 'Bearer '.$this->channelToken,
                ],
            ]);
            
            $profile = $response->getBody()->getContents();
            
            Log::Info('profile:'.$profile);

            return json_decode($profile);
        } catch (RequestException $e) {
            Log::Info('error:'.json_encode($e));
            return '';
        }
    }
}
