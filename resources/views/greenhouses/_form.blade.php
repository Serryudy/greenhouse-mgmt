<div class="mb-3">
    <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
    <input type="text" name="name" value="{{ old('name', $greenhouse->name ?? '') }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label fw-semibold">Location</label>
    <input type="text" name="location" value="{{ old('location', $greenhouse->location ?? '') }}"
           class="form-control @error('location') is-invalid @enderror">
    @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label fw-semibold">Description</label>
    <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $greenhouse->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
