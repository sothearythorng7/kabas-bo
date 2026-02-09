<div class="row">
    {{-- Upload Form --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.staff.upload_document') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.documents.upload', $staffMember) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="type" class="form-label">{{ __('messages.staff.document_type') }} *</label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            <option value="photo">{{ __('messages.staff.document_types.photo') }}</option>
                            <option value="contract">{{ __('messages.staff.document_types.contract') }}</option>
                            <option value="id_card">{{ __('messages.staff.document_types.id_card') }}</option>
                            <option value="other">{{ __('messages.staff.document_types.other') }}</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="document" class="form-label">{{ __('messages.staff.file') }} *</label>
                        <input type="file" class="form-control @error('document') is-invalid @enderror" id="document" name="document" required>
                        <small class="text-muted">{{ __('messages.staff.max_file_size') }}</small>
                        @error('document')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-upload"></i> {{ __('messages.btn.upload') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Documents List --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('messages.staff.documents') }}</h5>
            </div>
            <div class="card-body">
                @if($staffMember->documents->isEmpty())
                    <p class="text-muted text-center">{{ __('messages.staff.no_documents') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.staff.document_type') }}</th>
                                    <th>{{ __('messages.staff.file_name') }}</th>
                                    <th>{{ __('messages.staff.uploaded_by') }}</th>
                                    <th>{{ __('messages.staff.uploaded_at') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffMember->documents as $document)
                                    <tr>
                                        <td>
                                            @php
                                                $typeClass = match($document->type) {
                                                    'photo' => 'primary',
                                                    'contract' => 'success',
                                                    'id_card' => 'warning',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $typeClass }}">{{ $document->getTypeLabel() }}</span>
                                        </td>
                                        <td>{{ $document->original_name }}</td>
                                        <td>{{ $document->uploader->name ?? '-' }}</td>
                                        <td>{{ $document->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="text-end">
                                            <a href="{{ asset('storage/' . $document->path) }}" target="_blank" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ asset('storage/' . $document->path) }}" download class="btn btn-sm btn-secondary">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <form action="{{ route('staff.documents.delete', $document) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('{{ __('messages.staff.confirm_delete_document') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
