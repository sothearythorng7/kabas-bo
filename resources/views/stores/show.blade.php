@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Store Details</h1>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">{{ $store->name }}</h5>
            <p class="card-text"><strong>Address:</strong> {{ $store->address }}</p>
            <p class="card-text"><strong>Phone:</strong> {{ $store->phone }}</p>
            <p class="card-text"><strong>Email:</strong> {{ $store->email }}</p>
            <p class="card-text"><strong>Opening Time:</strong> {{ $store->opening_time ?? '-' }}</p>
            <p class="card-text"><strong>Closing Time:</strong> {{ $store->closing_time ?? '-' }}</p>
        </div>
    </div>
    <a href="{{ route('stores.index') }}" class="btn btn-secondary mt-3">Back to List</a>
</div>
@endsection
