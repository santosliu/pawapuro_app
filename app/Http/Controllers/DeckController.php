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
        $deck_detail = Decks::where('id', $deck_id)->first();
       
        return view('deck',compact('deck_detail'));
    }

    public function bySchool($school_id){
        $decks = Decks::where('school', $school_id)->orderBy('created_at', 'desc')->get();
        
        return view('school',compact('decks'));
    }
}
