@extends("la.layouts.app")

@section("contentheader_title")
<a href="{{ url(config('laraadmin.adminRoute') . '/binance_settings') }}">Binance Setting</a> :
@endsection
@section("contentheader_description", $binance_setting->$view_col)
@section("section", "Binance Settings")
@section("section_url", url(config('laraadmin.adminRoute') . '/binance_settings'))
@section("sub_section", "Edit")

@section("htmlheader_title", "Binance Settings Edit : ".$binance_setting->$view_col)

@section("main-content")

@if (count($errors) > 0)
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="box">
    <div class="box-header">

    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                {!! Form::model($binance_setting, ['route' => [config('laraadmin.adminRoute') . '.binance_settings.update', $binance_setting->id ], 'method'=>'PUT', 'id' => 'binance_setting-edit-form']) !!}
                @la_input($module, 'api_key')
                @la_input($module, 'secrete_key')
                <input type="hidden" name="user_id" id="user_id" />
                {{--
					@la_input($module, 'api_key')
					@la_input($module, 'secrete_key')
					@la_input($module, 'user_id')
					--}}
                <br>
                <div class="form-group">
                    {!! Form::submit( 'Update', ['class'=>'btn btn-success']) !!} <button class="btn btn-default pull-right"><a href="{{ url(config('laraadmin.adminRoute') . '/binance_settings') }}">Cancel</a></button>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(function () {
        $("#binance_setting-edit-form").validate({
        });
    });
</script>
@endpush
