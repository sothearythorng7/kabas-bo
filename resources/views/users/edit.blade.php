@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="crud_title">{{ __('messages.user_edit.title_edit') }}</h1>

    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf @method('PUT')

        <div class="mb-3">
            <label>{{ __('messages.user_edit.name') }}</label>
            <input type="text" name="name" value="{{ $user->name }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.email') }}</label>
            <input type="email" name="email" value="{{ $user->email }}" class="form-control" required>
        </div>
        
        <div class="mb-3">
            <label>{{ __('messages.user_edit.language') }}</label>
            <select name="locale" class="form-select">
                <option value="fr" {{ $user->locale === 'fr' ? 'selected' : '' }}>Français</option>
                <option value="en" {{ $user->locale === 'en' ? 'selected' : '' }}>English</option>
            </select>
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.new_password') }}</label>
            <input type="password" name="password" class="form-control">
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.password_confirmation') }}</label>
            <input type="password" name="password_confirmation" class="form-control">
        </div>

        <div class="mb-3">
            <label>{{ __('messages.user_edit.role') }}</label>
            <select name="role" class="form-control" required>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                        {{ ucfirst($role->name) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3" id="store-select" style="display:{{ $user->hasRole('Saler') ? 'block' : 'none' }};">
            <label>Site de vente</label>
            <select name="store_id" class="form-control" id="store_id">
                <option value="">Sélectionnez un site</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ $user->store_id == $store->id ? 'selected' : '' }}>
                        {{ $store->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-success"><i class="bi bi-floppy-fill"></i> {{ __('messages.btn.save') }}</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle"></i> {{ __('messages.btn.cancel') }}</a>
    </form>
</div>

<script>
const roleSelect = document.querySelector('select[name="role"]');
const storeDiv = document.getElementById('store-select');
const storeSelect = document.getElementById('store_id');

function toggleStoreSelect() {
    if(roleSelect.value === 'Saler') {
        storeDiv.style.display = 'block';
        storeSelect.setAttribute('required', true);
    } else {
        storeDiv.style.display = 'none';
        storeSelect.removeAttribute('required');
    }
}

roleSelect.addEventListener('change', toggleStoreSelect);

// Initialisation
toggleStoreSelect();
</script>
@endsection
