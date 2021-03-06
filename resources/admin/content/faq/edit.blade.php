@extends('Admin::index')

@section('content')
    @include('Admin::alerts')

        <div>
            <a href="{{ route('admin.faq.list') }}" class="btn-sm btn-primary pull-right">
                <i class="fa fa-arrow-left" aria-hidden="true"></i>
                &nbsp;&nbsp;
                назад
            </a>

            <h3 class="sub-header">
                @if( empty($item->id) )
                    Создать Вопрос-ответ
                @else
                    Редактировать Вопрос-ответ
                @endif
            </h3>
        </div>

    <div class="row">
        <form method="POST" class="form" enctype="multipart/form-data">

            {{ csrf_field() }}

            <div class="col-md-12">

                <div class="form-group @if( is_error('lang') )has-error @endif">
                    <label for="lang">Язык</label>
                    <select name="lang" class="form-control" id="lang">
                        <option disabled {{ !empty($item) ? "" : "selected" }}>Выберите язык</option>
                        @foreach(config('languages') as $key=>$lang)
                            <option value="{{ $key }}" {{ !empty($item->lang) && $item->lang == $key  ? "selected" : "" }}>{{ $lang }}</option>
                        @endforeach
                    </select>
                    @if( is_error('lang') )
                        <span class="help-block">{{ $errors->first('lang') }}</span>
                    @endif
                </div>

                <div class="form-group @if( is_error('question') )has-error @endif">
                    <label for="edit-form-question">* Вопрос</label>
                    <input type="text"
                           name="question"
                           value="{{ !empty($item->question) ? $item->question : '' }}"
                           id="edit-form-question"
                           class="form-control"
                           maxlength="255"
                           required
                    >
                    @if( is_error('question') )
                        <span class="help-block">{{ $errors->first('question') }}</span>
                    @endif
                </div>

                <div class="form-group @if( is_error('answer') )has-error @endif">
                    <label for="edit-form-answer">* Ответ</label>
                    <textarea name="answer"
                              id="edit-from-answer"
                              cols="8"
                              rows="5"
                              class="form-control"
                              required
                    >{{ !empty($old_input['answer']) ? $old_input['answer'] : $item->answer }}</textarea>
                @if( is_error('answer') )
                        <span class="help-block">{{ $errors->first('answer') }}</span>
                    @endif
                </div>

                @if( !empty($item->id) )
                    <div class="form-group">
                        <input type="checkbox"
                               name="is_active"
                               {{ !empty($old_input['is_active']) || !empty($item->is_active) ? 'checked="checked"' : '' }}
                               value="1"
                               id="edit-form-active">
                        <label for="edit-form-active">Активен</label>
                    </div>
                @endif

                <button class="btn btn-primary">
                    <i class="fa fa-floppy-o" aria-hidden="true"></i>
                    &nbsp;&nbsp;
                    Сохранить
                </button>
            </div>

        </form>
    </div>

@endsection

@push('footer-scripts')
    <script type="text/javascript">
        CKEDITOR.replace('edit-from-answer', {contentsCss: "{{ url( elixir('css/ts_main.css') ) }}"});
    </script>
@endpush