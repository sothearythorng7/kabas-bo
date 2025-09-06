<div class="mb-3">
    <label class="form-label">Upload photos</label>
    <input type="file" name="photos[]" class="form-control" multiple>
</div>

@if($product->images->count())
    <div class="mb-2">Existing photos — choose primary:</div>
    <div class="d-flex flex-wrap gap-3">
        @foreach($product->images as $img)
            <label class="border rounded p-2 d-inline-flex align-items-center gap-2">
                <input type="radio" name="primary_image_id" value="{{ $img->id }}" @checked($img->is_primary)>
                <img src="{{ asset('storage/'.$img->path) }}" alt="" style="height:70px;">
            </label>
        @endforeach
    </div>
@endif
