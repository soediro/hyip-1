@extends('main')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <h1>@lang('messages.news')</h1>
                <hr>
            </div>
        </div>
        
        <div class="row">
            <div class="col-xs-12">
                @forelse($data['news'] as $news)
                    <div class="news__item">
                        <div class="row">
                            <div class="col-xs-12 col-md-3">
                                <div class="news_title">
                                    <img src="{{ route('get.image', ['type' => 'blog', 'name' => $news['preview']]) }}" alt="">
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-9 left-text">
                                <div class="news__description">
                                    <h4>{{ $news['title'] }}</h4>
                                    <p>
                                        {!!  $news['content'] !!}
                                    </p>
                                </div>
                                <a href="{{ route('news.show', ['uri' => $news['uri']]) }}" class="red-text">@lang('messages.more')</a>
                            </div>
                        </div>

                    </div>
                @empty
                @endforelse
            </div>
            <div class="col-xs-12 text-center">
                {{ $data['news']->links() }}
            </div>
        </div>

    </div>
@endsection

@section('js')
    <script>
        $(function () {

        });
    </script>
@stop

