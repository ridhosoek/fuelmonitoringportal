@props(['value' => null])

<div>
    <label class="form-label" for="status">Status</label>
    <select id="status" name="status" class="form-select" required>
        @foreach(['ACTIVE', 'INACTIVE'] as $status)
            <option value="{{ $status }}" @selected(old('status', $value ?? 'ACTIVE') === $status)>{{ $status }}</option>
        @endforeach
    </select>
</div>
