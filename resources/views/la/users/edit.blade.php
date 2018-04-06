@extends("la.layouts.app")

@section("contentheader_title")
	<a href="{{ url(config('laraadmin.adminRoute') . '/users') }}">User</a> :
@endsection
@section("contentheader_description", $user->$view_col)
@section("section", "Users")
@section("section_url", url(config('laraadmin.adminRoute') . '/users'))
@section("sub_section", "Edit")

@section("htmlheader_title", "Users Edit : ".$user->$view_col)

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
				{!! Form::model($user, ['route' => [config('laraadmin.adminRoute') . '.users.update', $user->id ], 'method'=>'PUT', 'id' => 'user-edit-form']) !!}
					@la_form($module)
					
					{{--
					@la_input($module, 'name')
					@la_input($module, 'context_id')
					@la_input($module, 'email')
					@la_input($module, 'password')
					@la_input($module, 'type')
					@la_input($module, 'balance_settings')
					--}}
                    <br>
					<div class="form-group">
						{!! Form::submit( 'Update', ['class'=>'btn btn-success']) !!} <button class="btn btn-default pull-right"><a href="{{ url(config('laraadmin.adminRoute') . '/users') }}">Cancel</a></button>
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
	$("#user-edit-form").validate({
		
	});
});
</script>
@endpush
