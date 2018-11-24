<?php

namespace App\Http\Controllers;

use DB;
use PDO;
use Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Validator;
use Response;

use GuzzleHttp\Client;

class LinebotController extends Controller
{
    public function msgSend($msgData){
        $channelToken = config('bot.channel_token');
        $channelSecret = config('bot.channel_secret');
        $channelId = config('bot.channel_id');
        
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($channelToken);
        $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);

        foreach ($msgData as $msg) {
            $replyToken = $msg['replyToken'];
            #$sendMessage = $msg['message']['text'];
            
            if (strpos($msg['message']['text'],'本期活動') !== false ){
                $sendMessage = "本期活動為 北斗神拳合作活動 詳情請看連結 http://pawamobile.blogspot.com/2018/11/20181122.html";

                $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($sendMessage);
                $response = $bot->replyMessage($replyToken, $textMessageBuilder);
            }
            
            
        }
    }

    public function msgReceive(Request $request){
        $channelToken = config('bot.channel_token');
        $channelSecret = config('bot.channel_secret');
        $channelId = config('bot.channel_id');
        
        $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($channelToken);
        $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);

        $msgData = $request->events;

        //處理訊息
        $this->msgSend($msgData);

        $this->resp['status'] = true;        
        return Response::json($this->resp);
    }
}
