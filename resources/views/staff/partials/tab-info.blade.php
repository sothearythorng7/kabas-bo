<div class="card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('messages.staff.personal_info') }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('staff.update', $staffMember) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('messages.staff.name') }} *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                               value="{{ old('name', $staffMember->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">{{ __('messages.staff.email') }} *</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                               value="{{ old('email', $staffMember->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="phone" class="form-label">{{ __('messages.staff.phone') }}</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                               value="{{ old('phone', $staffMember->phone) }}">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="hire_date" class="form-label">{{ __('messages.staff.hire_date') }}</label>
                        <input type="date" class="form-control @error('hire_date') is-invalid @enderror" id="hire_date" name="hire_date"
                               value="{{ old('hire_date', $staffMember->hire_date?->format('Y-m-d')) }}">
                        @error('hire_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">{{ __('messages.staff.address') }}</label>
                <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address', $staffMember->address) }}</textarea>
                @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.staff.store') }}</label>
                        <input type="text" class="form-control" value="{{ $staffMember->store->name ?? '-' }}" disabled>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.staff.roles') }}</label>
                        <input type="text" class="form-control" value="{{ $staffMember->user?->roles?->pluck('name')->join(', ') ?: '-' }}" disabled>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> {{ __('messages.btn.save') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Carte Compte utilisateur lié --}}
<div class="card mt-3">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-person-badge"></i> {{ __('messages.staff.linked_account') }}</h5>
    </div>
    <div class="card-body">
        @if($staffMember->user)
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>{{ $staffMember->user->name }}</strong>
                    <br>
                    <small class="text-muted">{{ $staffMember->user->email }}</small>
                    @if($staffMember->user->roles?->isNotEmpty())
                        <br><small class="text-muted"><i class="bi bi-shield"></i> {{ $staffMember->user->roles->pluck('name')->join(', ') }}</small>
                    @endif
                    @if($staffMember->user->store)
                        <br><small class="text-muted"><i class="bi bi-shop"></i> {{ $staffMember->user->store->name }}</small>
                    @endif
                </div>
                <a href="{{ route('users.edit', $staffMember->user) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil"></i> {{ __('messages.staff.view_user_account') }}
                </a>
            </div>
        @else
            <p class="text-muted mb-0">
                <i class="bi bi-info-circle"></i> {{ __('messages.staff.no_linked_account') }}
            </p>
        @endif
    </div>
</div>
