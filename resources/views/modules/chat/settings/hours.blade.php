<div class="card stat-card">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-1">
            <h5 class="fw-bold mb-0">Business Hours</h5>
            <span class="badge bg-{{ $isOpenNow ? 'success' : 'secondary' }}">
                {{ $isOpenNow ? 'Open now' : 'Closed now' }}
            </span>
        </div>
        <p class="text-muted small">
            Outside these hours the widget shows your offline message. Visitors can still send messages —
            they land in the inbox as usual.
        </p>

        <form method="POST" action="{{ route('chat.settings.hours') }}">
            @csrf
            @method('PUT')

            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1"
                               id="hours-enabled" {{ old('enabled', $hours['enabled']) ? 'checked' : '' }}>
                        <label class="form-check-label" for="hours-enabled">Enforce business hours</label>
                    </div>
                </div>
                <div class="col-md-5">
                    <label for="hours-timezone" class="form-label fw-medium small mb-1">Timezone</label>
                    <select name="timezone" id="hours-timezone" class="form-select">
                        @foreach($timezones as $timezone)
                            <option value="{{ $timezone }}"
                                {{ old('timezone', $hours['timezone']) === $timezone ? 'selected' : '' }}>
                                {{ $timezone }}
                            </option>
                        @endforeach
                    </select>
                    @error('timezone') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-3">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Day</th>
                            <th>Opens</th>
                            <th>Closes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dayLabels as $key => $label)
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="days[{{ $key }}][enabled]" value="1"
                                               id="day-{{ $key }}"
                                               {{ old("days.{$key}.enabled", $hours['days'][$key]['enabled']) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="day-{{ $key }}">{{ $label }}</label>
                                    </div>
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm"
                                           name="days[{{ $key }}][start]"
                                           value="{{ old("days.{$key}.start", $hours['days'][$key]['start']) }}">
                                    @error("days.{$key}.start") <div class="text-danger small">{{ $message }}</div> @enderror
                                </td>
                                <td>
                                    <input type="time" class="form-control form-control-sm"
                                           name="days[{{ $key }}][end]"
                                           value="{{ old("days.{$key}.end", $hours['days'][$key]['end']) }}">
                                    @error("days.{$key}.end") <div class="text-danger small">{{ $message }}</div> @enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="form-text mb-3">
                A closing time earlier than the opening time is treated as an overnight shift.
            </div>

            <button type="submit" class="btn btn-primary">Save Business Hours</button>
        </form>
    </div>
</div>
