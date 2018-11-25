<?php

namespace App\Http\Controllers;

use Log;
use Redis;
use Response;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Keywords;

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
                $keywords = Redis::get('keywords:list');
            } else {
                $this->keywords = Keywords::get();
                Redis::set('keywords:list', json_encode($this->keywords), 'EX', 360);
            }
        } else {
            $this->keywords = Keywords::get();
        }
    }

    public function msgSend($msgData){
        
        foreach ($msgData as $msg) {
            $replyToken = $msg['replyToken'];
            
            foreach ($this->keywords as $data) {
                if ($msg['message']['text'] == $data->keyword){
                    $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($data->reply_content);
                    $response = $this->bot->replyMessage($replyToken, $textMessageBuilder);
                }    
            }
        }
    }

    public function msgReceive(Request $request){
        // $channelToken = config('bot.channel_token');
        // $channelSecret = config('bot.channel_secret');
        // $channelId = config('bot.channel_id');
        
        // $httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($channelToken);
        // $bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $channelSecret]);

        $msgData = $request->events;
        Log::info($request);        
        $this->msgSend($msgData);

        $this->resp['status'] = true;        
        return Response::json($this->resp);
    }
}
