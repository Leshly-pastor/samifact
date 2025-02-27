@extends('tenant.layouts.app')

@section('content')

    <tenant-dispatch-order-index
    :user-id="{{ json_encode(auth()->user()->id) }}"
        :soap-company="{{ json_encode($soap_company) }}"
        :type-user="{{ json_encode(auth()->user()->type) }}"
        :configuration="{{\App\Models\Tenant\Configuration::getPublicConfig()}}"
    ></tenant-dispatch-order-index>

@endsection
