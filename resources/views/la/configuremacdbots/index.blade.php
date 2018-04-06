@extends("la.layouts.app")

@section("contentheader_title", "ConfigureMACDBots")
@section("contentheader_description", "ConfigureMACDBots listing")
@section("section", "ConfigureMACDBots")
@section("sub_section", "Listing")
@section("htmlheader_title", "ConfigureMACDBots Listing")

@section("headerElems")
@la_access("ConfigureMACDBots", "create")
	<button class="btn btn-success btn-sm pull-right" data-toggle="modal" data-target="#AddModal">Add ConfigureMACDBot</button>
@endla_access
@endsection

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

<div class="box box-success">
	<!--<div class="box-header"></div>-->
	<div class="box-body">
		<table id="example1" class="table table-bordered">
		<thead>
		<tr class="success">
			@foreach( $listing_cols as $col )
			<th>{{ $module->fields[$col]['label'] or ucfirst($col) }}</th>
			@endforeach
			@if($show_actions)
			<th>Actions</th>
			@endif
		</tr>
		</thead>
		<tbody>
			
		</tbody>
		</table>
	</div>
</div>

@la_access("ConfigureMACDBots", "create")
<div class="modal fade" id="AddModal" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">Add ConfigureMACDBot</h4>
			</div>
			{!! Form::open(['action' => 'LA\ConfigureMACDBotsController@store', 'id' => 'configuremacdbot-add-form']) !!}
			<div class="modal-body">
				<div class="box-body">
                                        {{--@la_form($module)--}}
                                        <div class="form-group">
                                            <label for="symbol">Trade Symbol* :</label>
                                            <select id="symbol" name="symbol" class="form-control select2-hidden-accessible" required="1" data-placeholder="Enter Trade Symbol" rel="select2" name="dept" tabindex="-1" aria-hidden="true" aria-required="true">
                                            @foreach( $symbols as $col )
                                            <option values='{{ strtoupper($col->col) }}'>{{ strtoupper($col->col) }}</option>                        
                                            @endforeach
                                            </select>
                                        </div>
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
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
				{!! Form::submit( 'Submit', ['class'=>'btn btn-success']) !!}
			</div>
			{!! Form::close() !!}
		</div>
	</div>
</div>
@endla_access

@endsection

@push('styles')
<link rel="stylesheet" type="text/css" href="{{ asset('la-assets/plugins/datatables/datatables.min.css') }}"/>
@endpush

@push('scripts')
<script src="{{ asset('la-assets/plugins/datatables/datatables.min.js') }}"></script>
<script>
$(function () {
	$("#example1").DataTable({
		processing: true,
        serverSide: true,
        ajax: "{{ url(config('laraadmin.adminRoute') . '/configuremacdbot_dt_ajax') }}",
		language: {
			lengthMenu: "_MENU_",
			search: "_INPUT_",
			searchPlaceholder: "Search"
		},
		@if($show_actions)
		columnDefs: [ { orderable: false, targets: [-1] }],
		@endif
	});
	$("#configuremacdbot-add-form").validate({
		
	});
});
</script>
@endpush
