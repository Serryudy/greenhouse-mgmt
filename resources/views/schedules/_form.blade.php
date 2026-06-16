@php
    $days = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'];
    $selectedDays = old('days_of_week', isset($schedule) ? (array) $schedule->days_of_week : []);
@endphp
<div class="row g-3">
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name', $schedule->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label fw-semibold">Greenhouse <span class="text-danger">*</span></label>
        <select name="greenhouse_id" class="form-select @error('greenhouse_id') is-invalid @enderror" required>
            <option value="">Select greenhouse…</option>
            @foreach ($greenhouses as $gh)
                <option value="{{ $gh->id }}" @selected(old('greenhouse_id', $schedule->greenhouse_id ?? '') == $gh->id)>{{ $gh->name }}</option>
            @endforeach
        </select>
        @error('greenhouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Days of Week</label>
        <div class="day-pills">
            @foreach ($days as $key => $label)
                <label class="day-pill {{ in_array($key, $selectedDays) ? 'active' : '' }}" style="cursor:pointer; width:auto; padding:0 12px;"
                       onclick="this.classList.toggle('active'); const c=this.querySelector('input'); c.checked=!c.checked;">
                    {{ $label }}
                    <input type="checkbox" name="days_of_week[]" value="{{ $key }}" class="d-none" {{ in_array($key, $selectedDays) ? 'checked' : '' }}>
                </label>
            @endforeach
        </div>
    </div>

    <div class="col-12 col-md-4">
        <label class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
        <input type="time" name="start_time" value="{{ old('start_time', isset($schedule) ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '06:00') }}"
               class="form-control @error('start_time') is-invalid @enderror" required>
        @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label fw-semibold">Irrigation Duration (min) <span class="text-danger">*</span></label>
        <input type="number" min="0" name="duration_minutes"
               value="{{ old('duration_minutes', isset($schedule) ? intdiv($schedule->duration_seconds, 60) : 20) }}"
               class="form-control @error('duration_minutes') is-invalid @enderror" required>
        @error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label fw-semibold">Fertiliser Dose (sec) <span class="text-danger">*</span></label>
        <input type="number" min="0" name="dose_seconds"
               value="{{ old('dose_seconds', $schedule->dose_seconds ?? 45) }}"
               class="form-control @error('dose_seconds') is-invalid @enderror" required>
        @error('dose_seconds')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <div class="form-check form-switch switch-lg">
            <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabledSwitch"
                   {{ old('enabled', $schedule->enabled ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold ms-2" for="enabledSwitch">Enabled</label>
        </div>
    </div>
</div>
