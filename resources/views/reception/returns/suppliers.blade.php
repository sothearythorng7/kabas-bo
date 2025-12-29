@extends('reception.layouts.app')

@section('title', 'Select Supplier')

@section('content')
<div class="header">
    <a href="{{ route('reception.home') }}" class="header-back">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Back
    </a>
    <div class="header-title">Return</div>
    <div style="width: 60px;"></div>
</div>

<div class="container">
    <div class="text-sm text-muted mb-4">
        Select a supplier to create a return
    </div>

    @if($suppliers->isEmpty())
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">No suppliers</div>
            <div>No consignment suppliers available</div>
        </div>
    @else
        <div class="search-box">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" id="searchSupplier" placeholder="Search supplier...">
        </div>

        <div id="suppliersList">
            @foreach($suppliers as $supplier)
                <div class="card supplier-card" data-name="{{ strtolower($supplier->name) }}" style="cursor: pointer;" onclick="window.location='{{ route('reception.returns.products', $supplier) }}'">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="card-title">{{ $supplier->name }}</div>
                            <div class="card-subtitle">
                                {{ $supplier->products->count() }} products
                            </div>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 24px; height: 24px; color: var(--text-light);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('searchSupplier')?.addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.supplier-card').forEach(card => {
            const name = card.dataset.name;
            card.style.display = name.includes(search) ? 'block' : 'none';
        });
    });
</script>
@endsection
