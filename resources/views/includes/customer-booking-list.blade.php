@if ($bookings->isEmpty())
  <p>No bookings found.</p>
@else
  @foreach ($bookings as $booking)
    <article class="card mb-3">
      <div class="card-body">
        <h3 class="h5">{{ $serviceNames[$booking->code_service] ?? $booking->code_service }}</h3>
        <dl class="row mb-0">
          @foreach ([
            'Booking number' => $booking->no_reservasi,
            'Travel date' => $booking->cek_in,
            'Travelers' => $booking->adult,
            'Booking status' => $booking->book_status,
            'Payment status' => $booking->status,
            'Total' => ($booking->checkout_token ?? null) ? 'USD '.$booking->total : $booking->total,
            'Paid' => ($booking->checkout_token ?? null) ? 'USD '.$booking->guest_paid : $booking->guest_paid,
          ] as $label => $value)
            <dt class="col-sm-4">{{ $label }}</dt>
            <dd class="col-sm-8">{{ $value }}</dd>
          @endforeach
        </dl>
      </div>
    </article>
  @endforeach
@endif
