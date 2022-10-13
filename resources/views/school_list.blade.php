 
@extends('master')

@section('sidebar')
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
    @foreach ($schools as $school)
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100">
          <a href="/school/{{ $school->id }}">
              <img class="card-img-top" src="{{ $school->school_pic }}" alt=""></a>
          <div class="card-body">
            <p class="card-title">
              <a href="/school/{{ $school->id }}">{{ $school->school_name }}</a>
            </p>                        
          </div>
          <div class="card-footer">
            <small class="text-muted">開放時間：{{ substr($school->created_at,0,10) }}</small>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="fb-comments" data-href="{{ Request::url() }}" data-width="100%" data-numposts="10"></div>
@endsection