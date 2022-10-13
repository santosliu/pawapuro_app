 
@extends('master')

@section('sidebar')
    <p class="list-group-item">學校快捷列</p>    
    <a href="/school/23" class="list-group-item">艦隊高校</a>
    <a href="/school/22" class="list-group-item">世紀末北斗高校</a>    
    <a href="/school/21" class="list-group-item">邊境高校</a>    
    <a href="/school/20" class="list-group-item">惠比留高校</a>
    <a href="/school/19" class="list-group-item">全力學園高校</a>
@endsection

@section('content')
  <div id="carouselExampleIndicators" class="carousel slide my-4" data-ride="carousel">
  </div>

  <div class="row">
    @foreach ($decks as $deck)
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100">
          <a href="/deck/no/{{ $deck->id }}">
            @if ($deck->deck_type == 1)
              <img class="card-img-top" src="https://i.imgur.com/ARNk7tf.jpg" alt=""></a>
            @else
              <img class="card-img-top" src="https://i.imgur.com/12BbkLj.jpg" alt=""></a>
            @endif
          <div class="card-body">
            <h4 class="card-title">
              <a href="/deck/no/{{ $deck->id }}">{{ $deck->deck_title }}</a>
            </h4>            
            <p class="card-text">{{ $deck->deck_description }}</p>
          </div>
          <div class="card-footer">
            <small class="text-muted">登錄時間：{{ substr($deck->created_at,0,10) }}</small>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="fb-comments" data-href="{{ Request::url() }}" data-width="100%" data-numposts="10"></div>
@endsection