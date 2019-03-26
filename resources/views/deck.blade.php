 
@extends('master')

@section('sidebar')
    <a href="/school/21" class="list-group-item">邊境高校</a>    
    <a href="/school/20" class="list-group-item">惠比留高校</a>
    <a href="/school/19" class="list-group-item">全力學園高校</a>
@endsection

@section('content')
  <div id="carouselExampleIndicators" class="carousel slide my-4" data-ride="carousel">
    <h2>{{ $deck_detail["deck_title"] }}</h2>
    <div>
      {{ $deck_detail["deck_description"] }}
    </div>
  </div>

  <div class="row">
    {{--  顯示圖片內容  --}}
    <div class="col-lg-4 col-md-6 mb-4">
      <img src='{{ $deck_detail["pic_1"] }}' width="100%" />
    </div>
    <div class="col-lg-4 col-md-6 mb-4">
      <img src='{{ $deck_detail["pic_2"] }}' width="100%" />
    </div>
    <div class="col-lg-4 col-md-6 mb-4">
      <img src='{{ $deck_detail["pic_3"] }}' width="100%" />
    </div>
    <div class="col-lg-4 col-md-6 mb-4">
      <img src='{{ $deck_detail["pic_4"] }}' width="100%" />
    </div>
  </div>

  <div class="fb-comments" data-href="{{ Request::url() }}" data-width="100%" data-numposts="10"></div>
@endsection