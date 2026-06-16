<div class="row g-3">
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $device->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Greenhouse <span class="text-danger">*</span></label>
        <select name="greenhouse_id" class="form-select @error('greenhouse_id') is-invalid @enderror" required>
            <option value="">Select greenhouse…</option>
            @foreach ($greenhouses as $gh)
                <option value="{{ $gh->id }}" @selected(old('greenhouse_id', $device->greenhouse_id ?? '') == $gh->id)>{{ $gh->name }}</option>
            @endforeach
        </select>
        @error('greenhouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Identifier (MAC) <span class="text-danger">*</span></label>
        <input type="text" name="identifier" value="{{ old('identifier', $device->identifier ?? '') }}"
               class="form-control mono @error('identifier') is-invalid @enderror" placeholder="ESP32-XXXX" required>
        @error('identifier')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">IP Address</label>
        <input type="text" name="ip_address" value="{{ old('ip_address', $device->ip_address ?? '') }}"
               class="form-control mono @error('ip_address') is-invalid @enderror" placeholder="192.168.1.x">
        @error('ip_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Firmware Version</label>
        <input type="text" name="firmware_version" value="{{ old('firmware_version', $device->firmware_version ?? '') }}"
               class="form-control @error('firmware_version') is-invalid @enderror" placeholder="1.0.0">
        @error('firmware_version')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @isset($device)
        <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Status</label>
            <select name="status" class="form-select">
                @foreach (['online', 'offline', 'unknown'] as $st)
                    <option value="{{ $st }}" @selected(old('status', $device->status) === $st)>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
    @endisset
</div>
