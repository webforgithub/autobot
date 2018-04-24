@extends("la.layouts.app")

@section("contentheader_title")
	<a href="{{ url(config('laraadmin.adminRoute') . '/configuremacdbots') }}">ConfigureMACDBot</a> :
@endsection
@section("contentheader_description", $configuremacdbot->$view_col)
@section("section", "ConfigureMACDBots")
@section("section_url", url(config('laraadmin.adminRoute') . '/configuremacdbots'))
@section("sub_section", "Edit")

@section("htmlheader_title", "ConfigureMACDBots Edit : ".$configuremacdbot->$view_col)

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
				{!! Form::model($configuremacdbot, ['route' => [config('laraadmin.adminRoute') . '.configuremacdbots.update', $configuremacdbot->id ], 'method'=>'PUT', 'id' => 'configuremacdbot-edit-form']) !!}
					{{--@la_form($module)--}}
                                        <div class="form-group">
                                            <label for="symbol">Trade Symbol* :</label>
                                            <select id="symbol" name="symbol" class="form-control select2-hidden-accessible" required="1" data-placeholder="Enter Trade Symbol" rel="select2" name="dept" tabindex="-1" aria-hidden="true" aria-required="true">
                                            @foreach( $symbols as $col )
                                            <option {{ $module->row->symbol == $col->col ? 'selected="selected"' : "" }}  values='{{ strtoupper($col->col) }}'>{{ strtoupper($col->col) }}</option>
                                            @endforeach
                                            </select>
                                        </div>
					@la_input($module, 'alert_email')
					@la_input($module, 'alert_mobile')
					@la_input($module, 'volume')
					@la_input($module, 'totalorder')
					{{--
                                        @la_input($module, 'period')
					@la_input($module, 'period_length')
					@la_input($module, 'min_periods')
					--}}
                                        @la_input($module, 'ema_short_period')
					@la_input($module, 'ema_long_period')
					@la_input($module, 'signal_period')
					{{--
                                        @la_input($module, 'up_trend_threshold')
					@la_input($module, 'down_trend_threshold')
					@la_input($module, 'overbought_periods')
					@la_input($module, 'overbought_rsi')
					@la_input($module, 'use_all_fund')
                                        --}}
                                        <input type="hidden" name="userid" id="userid" />					
					{{--
					@la_input($module, 'symbol')
					@la_input($module, 'userid')
					@la_input($module, 'alert_email')
					@la_input($module, 'alert_mobile')
					@la_input($module, 'volume')
					@la_input($module, 'totalorder')
					@la_input($module, 'period')
					@la_input($module, 'period_length')
					@la_input($module, 'min_periods')
					@la_input($module, 'ema_short_period')
					@la_input($module, 'ema_long_period')
					@la_input($module, 'signal_period')
					@la_input($module, 'up_trend_threshold')
					@la_input($module, 'down_trend_threshold')
					@la_input($module, 'overbought_periods')
					@la_input($module, 'overbought_rsi')
					@la_input($module, 'use_all_fund')
					--}}
                    <br>
					<div class="form-group">
						{!! Form::submit( 'Update', ['class'=>'btn btn-success']) !!} <button class="btn btn-default pull-right"><a href="{{ url(config('laraadmin.adminRoute') . '/configuremacdbots') }}">Cancel</a></button>
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
	$("#configuremacdbot-edit-form").validate({
		
	});
});
</script>
@endpush
