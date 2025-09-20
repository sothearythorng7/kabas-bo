@php
    $flashData = session()->only(['success', 'error', 'warning', 'info']);
    $errorData = $errors->any() ? $errors->all() : [];
@endphp

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.initFlash(@json($flashData), @json($errorData));
});
</script>
@endpush
