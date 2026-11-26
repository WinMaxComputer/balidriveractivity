<div class="itinerary-item row mb-2">
  <div class="col-lg-5">
    <select class="form-control itinerary-reference" name="itinerary[{{ $index }}][reference]" aria-label="Activity or destination" style="background-color: #000; color: #fff;">
      <option value="">Text only / choose later</option>
      @if (($item['reference'] ?? '') !== '' && !$knownReferences->contains($item['reference']))
        <option value="{{ $item['reference'] }}" selected>Previously selected item unavailable</option>
      @endif
      <optgroup label="Activities">
        @foreach ($activities as $activity)
          <option value="activity:{{ $activity->id }}" data-type="activity" data-code="{{ $activity->code }}" @selected(($item['reference'] ?? '') === 'activity:'.$activity->id)>{{ $activity->name }} - {{ $activity->lang }}</option>
        @endforeach
      </optgroup>
      <optgroup label="Destinations">
        @foreach ($destinasi as $destination)
          <option value="destination:{{ $destination->id }}" data-type="destination" data-code="{{ $destination->code }}" @selected(($item['reference'] ?? '') === 'destination:'.$destination->id)>{{ $destination->name }} - {{ $destination->lang }}</option>
        @endforeach
      </optgroup>
    </select>
  </div>
  <div class="col-lg-6">
    <textarea class="form-control" name="itinerary[{{ $index }}][note]" placeholder="Optional itinerary note">{{ $item['note'] ?? '' }}</textarea>
  </div>
  <div class="col-lg-1">
    <button type="button" class="btn btn-sm btn-danger remove-itinerary" aria-label="Remove itinerary item">-</button>
  </div>
</div>
