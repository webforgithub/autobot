@extends("la.layouts.app")

@section("contentheader_title", "ConfigurationBots")
@section("contentheader_description", "ConfigurationBots listing")
@section("section", "ConfigurationBots")
@section("sub_section", "Listing")
@section("htmlheader_title", "ConfigurationBots Listing")

@section("headerElems")
@la_access("ConfigurationBots", "create")
<button class="btn btn-success btn-sm pull-right" data-toggle="modal" data-target="#AddModal">Add ConfigurationBot</button>
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

@la_access("ConfigurationBots", "create")
<div class="modal fade" id="AddModal" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Add ConfigurationBot</h4>
            </div>
            {!! Form::open(['action' => 'LA\ConfigurationBotsController@store', 'id' => 'configurationbot-add-form']) !!}
            <div class="modal-body">
                <div class="box-body">
                    {{-- @la_form($module)--}}
                    @la_input($module, 'alert_email')
                    @la_input($module, 'alert_mobile_no')
                    @la_input($module, 'buy_sale_volume')
                    @la_input($module, 'trade_type')
                    <div class="form-group">
                        <label for="trade_symbol">Trade Symbol* :</label>
                        <select id="trade_symbol" name="trade_symbol" class="form-control select2-hidden-accessible" required="1" data-placeholder="Enter Trade Symbol" rel="select2" name="dept" tabindex="-1" aria-hidden="true" aria-required="true">
                        @foreach( $symbols as $key => $col )
                        <option values='{{ ucfirst($key) }}'>{{ ucfirst($key) . ' - ' . $col }}</option>                        
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
        ajax: "{{ url(config('laraadmin.adminRoute') . '/configurationbot_dt_ajax') }}",
        language: {
        lengthMenu: "_MENU_",
                search: "_INPUT_",
                searchPlaceholder: "Search"
        },
        @if ($show_actions)
        columnDefs: [ { orderable: false, targets: [ - 1] }],
        @endif
});
$("#configurationbot-add-form").validate({

});
});
</script>
@endpush
