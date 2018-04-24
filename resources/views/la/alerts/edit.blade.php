@extends("la.layouts.app")

@section("contentheader_title")
	<a href="{{ url(config('laraadmin.adminRoute') . '/alerts') }}">Alert</a> :
@endsection
@section("contentheader_description", $alert->$view_col)
@section("section", "Alerts")
@section("section_url", url(config('laraadmin.adminRoute') . '/alerts'))
@section("sub_section", "Edit")

@section("htmlheader_title", "Alerts Edit : ".$alert->$view_col)

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
				{!! Form::model($alert, ['route' => [config('laraadmin.adminRoute') . '.alerts.update', $alert->id ], 'method'=>'PUT', 'id' => 'alert-edit-form']) !!}
					@la_form($module)
					
					{{--
					@la_input($module, 'configuremacdbot_id')
					@la_input($module, 'user_id')
					@la_input($module, 'alert_type')
					@la_input($module, 'currency_name')
					@la_input($module, 'currency_price')
					@la_input($module, 'alert_time')
					--}}
                    <br>
					<div class="form-group">
						{!! Form::submit( 'Update', ['class'=>'btn btn-success']) !!} <button class="btn btn-default pull-right"><a href="{{ url(config('laraadmin.adminRoute') . '/alerts') }}">Cancel</a></button>
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
	$("#alert-edit-form").validate({
		
	});
});
</script>
@endpush
