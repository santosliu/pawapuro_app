<?php

namespace App\Http\Controllers;

use Log;
use Redis;
use Response;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Decks;

class DeckController extends Controller
{
    public function byDeck($deck_id){
        
        dd($deck_id);
    }

    public function bySchool($school_name){
        
        
    }
}
