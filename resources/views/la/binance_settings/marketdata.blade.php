@extends("la.layouts.app")

@section("contentheader_title", "Binance Market")
@section("contentheader_description", "Binance Settings listing")
@section("section", "Binance Settings")
@section("sub_section", "Listing")
@section("htmlheader_title", "Binance Market")

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
            <div class="col-md-6">
                <table id="tblTicker" class="table table-bordered">
                    <thead>
                        <tr class="success">
                            @foreach( $listing_cols as $col )
                            <th>{{ ucfirst($col) }}</th>
                            @endforeach
                        </tr>

                    </thead>
                    <tbody>                
                        @foreach( $ticker as $key => $col )
                        <tr>
                            <td class="tdFirst" data-tokenPair='{{ ucfirst($key) }}'>{{ ucfirst($key) }}</td>
                            <td class="tdSecond" data-tokenPrice='{{ $col }}'>{{ $col }}</td>
                        </tr>
                        @endforeach                
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">            
                <div class="nav-tabs-custom">
                    <div id='dvMessage' class=""></div>
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#tab-buy" data-toggle="tab">Buy</a></li>
                        <li><a href="#tab-sell" data-toggle="tab">Sell</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab-buy">
                            <form name="frmOrderBuy" id="frmOrderBuy" method="post" action="{{ url('/admin/binance/buy-order') }}">
                                <input type="hidden" name="txtBuyTokenPair" id="txtBuyTokenPair" />
                                <input type="hidden" name="isBuy" id="isBuy" value="buy" />
                                <div class="form-group">
                                    <label class="col-md-4">Order for:</label>
                                    <label id='lblBuyTokenPair'></label>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-4" for="txtVolume">Volume:</label>
                                    <input type="number" name="txtVolume" id="txtVolume" class="form-control" maxlength="12" minlength="1" placeholder="Enter token Volume" />
                                </div>
                                <div class="form-group">
                                    <label class="col-md-4" for="txtPrice">Price:</label>
                                    <input type="number" name="txtPrice" id="txtPrice" class="form-control" maxlength="12" minlength="1" placeholder="Enter token price" />
                                </div>
                                <div class="form-group">
                                    <label class="col-md-4" for="orderType">Order type:</label>
                                    <input type="radio" name="orderType" id="orderType" value="Limit" checked="checked" title="Limit Order" /> Limit
                                    <input type="radio" name="orderType" id="orderType" value="Market" title="Market Order" /> Market
                                </div>

                                <div class="form-group">
                                    <button type="button" id='btnOrderBuy' class='btn btn-success'>Buy</button>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane" id="tab-sell">
                            <form name="frmOrderSell" id="frmOrderSell" method="post" action="{{ url('/admin/binance/sell-order') }}">
                                <input type="hidden" name="txtSellTokenPair" id="txtSellTokenPair" />
                                <input type="hidden" name="isBuy" id="isBuy" value="sell" />
                                <div class="form-group">
                                    <label class="col-md-4">Order for:</label>
                                    <label id='lblSellTokenPair'>Order for:</label>
                                </div>
                                <div class="form-group">
                                    <label class="col-md-4" for="txtVolume">Volume:</label>
                                    <input type="number" name="txtVolume" id="txtVolume" class="form-control" maxlength="12" minlength="1" placeholder="Enter token Volume" />
                                </div>
                                <div class="form-group">
                                    <label class="col-md-4" for="txtPrice">Price:</label>
                                    <input type="number" name="txtPrice" id="txtPrice" class="form-control" maxlength="12" minlength="1" placeholder="Enter token price" />
                                </div>
                                <div class="form-group">
                                    <label class="col-md-4" for="orderType">Order type:</label>
                                    <input type="radio" name="orderType" id="orderType" value="Limit" checked="checked" title="Limit Order" /> Limit
                                    <input type="radio" name="orderType" id="orderType" value="Market" title="Market Order" /> Market
                                </div>
                                <div class="form-group">
                                    <button type="button" id='btnOrderSell' class='btn btn-success'>Sell</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
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
            url: "{{ url('/admin/binance/buy-order') }}",
            data: $("#frmOrderBuy").serialize(),
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }, 
            success: function (response) {
                $("#dvMessage").addClass('alert alert-success').append(response.data + "<br />");
                resetForm();
            },
            error: function(data){
                var errorObj =  $.parseJSON(data.responseText);
                $("#dvMessage").addClass('alert alert-danger').append(errorObj.message + "<br />");
            }
        });
    });
    $('#btnOrderSell').on('click', function () {
        $("#dvMessage").removeClass('alert alert-success').html('');
        var token = $('#frmOrderSell input[name="_token"]').val();
        $.ajax({
            type: "POST",
            url: "{{ url('/admin/binance/sell-order') }}",
            data: $("#frmOrderSell").serialize(),          
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }, 
            success: function (data) {
                $("#dvMessage").addClass('alert alert-success').append(data.message + "<br />");
                resetForm();
            },
            error: function(data){
                var errorObj =  $.parseJSON(data.responseText);
                $("#dvMessage").addClass('alert alert-danger').append(errorObj.message + "<br />");
            }
        });
    });
    
    function resetForm() {
        $('body #frmOrderBuy #txtPrice').val('');
        $('body #frmOrderBuy #lblBuyTokenPair').html('');
        $('body #frmOrderBuy #txtVolume').val('');
        $('body #frmOrderBuy #txtBuyTokenPair').val('');
        
        $('body #frmOrderSell #txtPrice').val('');
        $('body #frmOrderSell #lblBuyTokenPair').html('');
        $('body #frmOrderSell #txtVolume').val();
        $('body #frmOrderSell #lblSellTokenPair').html('');
        
        setTimeout(function() {
            $("#dvMessage").removeClass('alert alert-danger').html('');
        }, 1500);
    }

    $('#tblTicker tbody tr').on('click', function () {
        var tokenPair = $(this).find('td.tdFirst').attr('data-tokenPair');
        var tokenPrice = $(this).find('td.tdSecond').attr('data-tokenPrice');
        
        $('body #frmOrderBuy #txtPrice').val(tokenPrice);
        $('body #frmOrderBuy #lblBuyTokenPair').html(tokenPair);
        $('body #frmOrderBuy #txtVolume').val();
        $('body #frmOrderBuy #txtBuyTokenPair').val(tokenPair);
        
        $('body #frmOrderSell #txtPrice').val(tokenPrice);
        $('body #frmOrderSell #txtVolume').val();
        $('body #frmOrderSell #lblSellTokenPair').html(tokenPair);
    });
});
</script>
@endpush
