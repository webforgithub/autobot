@extends("la.layouts.app")

@section("contentheader_title")
<a href="{{ url(config('laraadmin.adminRoute') . '/coinigy_settings') }}">Coinigy Setting</a> :
@endsection
@section("contentheader_description", $coinigy_setting->$view_col)
@section("section", "Coinigy Settings")
@section("section_url", url(config('laraadmin.adminRoute') . '/coinigy_settings'))
@section("sub_section", "Edit")

@section("htmlheader_title", "Coinigy Settings Edit : ".$coinigy_setting->$view_col)

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
                {!! Form::model($coinigy_setting, ['route' => [config('laraadmin.adminRoute') . '.coinigy_settings.update', $coinigy_setting->id ], 'method'=>'PUT', 'id' => 'coinigy_setting-edit-form']) !!}
                @la_input($module, 'api_key')
                @la_input($module, 'secrete_key')
                @la_input($module, 'webSocket_api_key')
                <input type="hidden" name="user_id" id="user_id" />
                {{--
                    @la_input($module, 'api_key')
                    @la_input($module, 'secrete_key')
                    @la_input($module, 'webSocket_api_key')
                    @la_input($module, 'user_id')
                    --}}
                <br>
                <div class="form-group">
                    {!! Form::submit( 'Update', ['class'=>'btn btn-success']) !!} <button class="btn btn-default pull-right"><a href="{{ url(config('laraadmin.adminRoute') . '/coinigy_settings') }}">Cancel</a></button>
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
        $("#coinigy_setting-edit-form").validate({
        });
    });
</script>
@endpush
