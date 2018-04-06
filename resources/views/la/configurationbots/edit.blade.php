@extends("la.layouts.app")

@section("contentheader_title")
<a href="{{ url(config('laraadmin.adminRoute') . '/configurationbots') }}">ConfigurationBot</a> :
@endsection
@section("contentheader_description", $configurationbot->$view_col)
@section("section", "ConfigurationBots")
@section("section_url", url(config('laraadmin.adminRoute') . '/configurationbots'))
@section("sub_section", "Edit")

@section("htmlheader_title", "ConfigurationBots Edit : ".$configurationbot->$view_col)

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
                {!! Form::model($configurationbot, ['route' => [config('laraadmin.adminRoute') . '.configurationbots.update', $configurationbot->id ], 'method'=>'PUT', 'id' => 'configurationbot-edit-form']) !!}
                {{-- @la_form($module)--}}
                    @la_input($module, 'alert_email')
                    @la_input($module, 'alert_mobile_no')
                    @la_input($module, 'buy_sale_volume')
                    @la_input($module, 'trade_type')
                    <div class="form-group">
                        <label for="trade_symbol">Trade Symbol* :</label>
                        <select id="trade_symbol" name="trade_symbol" class="form-control select2-hidden-accessible" required="1" data-placeholder="Enter Trade Symbol" rel="select2" name="dept" tabindex="-1" aria-hidden="true" aria-required="true">
                        @foreach( $symbols as $key => $col )
                        <option {{ ($key == $module->fields['trade_symbol'] ? "selected='selected'" : "") }} values='{{ ucfirst($key) }}'>{{ ucfirst($key) . ' - ' . $col }}</option>                        
                        @endforeach
                        </select>
                    </div>
                    @la_input($module, 'current_price')
                    @la_input($module, 'is_percentage')
                    @la_input($module, 'buy_price')
                    @la_input($module, 'sell_price')
                    <input type="hidden" name="user_id" id="user_id" />

                {{--
                    @la_input($module, 'alert_email')
                    @la_input($module, 'alert_mobile_no')
                    @la_input($module, 'user_id')
                    @la_input($module, 'buy_sale_volume')
                    @la_input($module, 'trade_type')
                    @la_input($module, 'trade_symbol')
                    @la_input($module, 'current_price')
                    @la_input($module, 'is_percentage')
                    @la_input($module, 'buy_price')
                    @la_input($module, 'sell_price')
                    --}}
                <br>
                <div class="form-group">
                    {!! Form::submit( 'Update', ['class'=>'btn btn-success']) !!} <button class="btn btn-default pull-right"><a href="{{ url(config('laraadmin.adminRoute') . '/configurationbots') }}">Cancel</a></button>
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
        $("#configurationbot-edit-form").validate({
        });
    });
</script>
@endpush
