<?php

namespace App\Http\Controllers;

use Log;
use Redis;
use Response;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Decks;
use App\Models\Schools;

class DeckController extends Controller
{
    public function byDeck($deck_id){
        $deck_detail = Decks::where('id', $deck_id)->first();
        
        return view('deck',compact('deck_detail'));
    }

    public function bySchool($school_id){
        $decks = Decks::where('school_id', $school_id)->orderBy('created_at', 'desc')->get();
        // if (is_null($decks)) return view('');

        return view('school',compact('decks'));
    }

    public function schoolList(){
        $schools = Schools::orderBy('id', 'desc')->get();
        return view('school_list',compact('schools'));
    }
}
