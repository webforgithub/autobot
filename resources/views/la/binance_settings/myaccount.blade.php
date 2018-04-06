@extends("la.layouts.app")

@section("contentheader_title", "Binance")
@section("contentheader_description", "My Account")
@section("section", "Binance Settings")
@section("sub_section", "Listing")
@section("htmlheader_title", "My account")

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
        <div class="row">
            <div class="col-md-12">
                <table id="tblTicker" class="table table-bordered">
                    <thead>
                        <tr class="success">
                            @foreach( $listing_cols as $col )
                            <th>{{ ucfirst($col) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>                
                        @foreach( $myAccount['balances'] as $key => $col )
                        @if($col["free"] > 0)
                        <tr>
                            <td class="tdFirst" data-tokenPair='{{ ucfirst($key) }}'>{{ ucfirst($col["asset"]) }}</td>
                            <td class="tdSecond">{{ $col["free"] }}</td>
                        </tr>
                        @endif
                        @endforeach                
                    </tbody>
                </table>
            </div>
        </div>
    </div>        
</div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" type="text/css" href="{{ asset('la-assets/plugins/datatables/datatables.min.css') }}"/>
@endpush

@push('scripts')
<script src="{{ asset('la-assets/plugins/datatables/datatables.min.js') }}"></script>
<script>
$(function () {
    $('#btnOrderBuy').on('click', function () {
        $("#dvMessage").removeClass('alert alert-success').html('');
        $.ajax({
            type: "POST",
            headers: {'X-CSRF-TOKEN': token},
            url: $('#frmOrder').attr('action'),
            data: $("#frmOrder").serialise(),            
            success: function (data) {
                $("#dvMessage").addClass('alert alert-success').append(data.data + "<br />");
            },
            error: function (data) {                
            }
        });
    });
    $('#btnOrderSell').on('click', function () {
        alert('hello');
    });

    $('#tblTicker tbody tr').on('click', function () {
        var tokenPair = $(this).find('td:tdFirst').attr('data-tokenPair');
        var tokenPrice = $(this).find('td.tdSecond').attr('data-tokenPrice');

        alert(tokenPair + " " + tokenPrice);
    });
});
</script>
@endpush
