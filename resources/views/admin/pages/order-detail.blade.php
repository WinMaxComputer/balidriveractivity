@extends('layouts.app', ['page' => __('Order Detail'), 'pageSlug' => 'booking'])

@section('content')
<div class="row">
  <div class="col-md-10">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">Order Detail</h4>
      </div>
      <div class="card-body">
        <dl class="row">
          @foreach ([
            'Booking number' => $order->no_reservasi,
            'Booking date' => $order->tgl_reservasi,
            'Guest name' => $order->guest_name,
            'Guest email' => $order->guest_email,
            'Product / tour' => $serviceName,
            'Check-in' => $order->cek_in,
            'Adults' => $order->adult,
            'Total' => ($order->checkout_token ?? null) ? 'USD '.$order->total : $order->total,
            'Paid' => ($order->checkout_token ?? null) ? 'USD '.$order->guest_paid : $order->guest_paid,
            'Payment' => $order->payment_type,
            'Payment status' => $order->status,
            'Booking status' => $order->book_status,
          ] as $label => $value)
            <dt class="col-sm-4">{{ $label }}</dt>
            <dd class="col-sm-8">{{ $value }}</dd>
          @endforeach
        </dl>

        @if (session('order_message'))
          <div class="alert alert-success" role="status">{{ session('order_message') }}</div>
        @endif
        @if ($errors->any())
          <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif
        <h5>Update payment and booking</h5>
        <p>Changes are recorded manually; they do not process a payment or refund.</p>
        @php
          $paymentStatuses = ['PENDING', 'PAID', 'Downpayment'];
          $bookingStatuses = ['New', 'Confirmed', 'Completed', 'Cancelled'];
        @endphp
        <form method="POST" action="{{ route('pages.orders.update', $order->id) }}">
          @csrf
          @method('PUT')
          <div class="form-group">
            <label for="order-payment-type">Payment method</label>
            <input id="order-payment-type" class="form-control" type="text" name="payment_type" maxlength="50" value="{{ old('payment_type', $order->payment_type) }}" required>
          </div>
          <div class="form-group">
            <label for="order-payment-status">Payment status</label>
            <select id="order-payment-status" class="form-control" name="status" style="background-color: #000; color: #fff;" required>
              <option value="">Select a status</option>
              @if ($order->status && !in_array($order->status, $paymentStatuses, true))
                <option value="{{ $order->status }}" @selected(old('status', $order->status) === $order->status)>{{ $order->status }} (current)</option>
              @endif
              @foreach ($paymentStatuses as $status)
                <option value="{{ $status }}" @selected(old('status', $order->status) === $status)>{{ $status }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label for="order-paid">Paid{{ ($order->checkout_token ?? null) ? ' (USD)' : '' }}</label>
            <input id="order-paid" class="form-control" type="number" name="guest_paid" min="0" max="99999999.99" step="0.01" value="{{ old('guest_paid', $order->guest_paid) }}" data-total="{{ $order->total }}" required>
            <small>PAID uses the order total; PENDING is zero. Enter the amount received for Downpayment.</small>
          </div>
          <div class="form-group">
            <label for="order-booking-status">Booking status</label>
            <select id="order-booking-status" class="form-control" name="book_status" style="background-color: #000; color: #fff;" required>
              <option value="">Select a status</option>
              @if ($order->book_status && !in_array($order->book_status, $bookingStatuses, true))
                <option value="{{ $order->book_status }}" @selected(old('book_status', $order->book_status) === $order->book_status)>{{ $order->book_status }} (current)</option>
              @endif
              @foreach ($bookingStatuses as $status)
                <option value="{{ $status }}" @selected(old('book_status', $order->book_status) === $status)>{{ $status }}</option>
              @endforeach
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </form>
        <script>
          (function () {
            var status = document.getElementById('order-payment-status');
            var paid = document.getElementById('order-paid');
            var manualPaid = paid.value;
            var wasAutomatic = false;
            function updatePaid() {
              var automatic = status.value === 'PAID' || status.value === 'PENDING';
              if (automatic && !wasAutomatic) manualPaid = paid.value;
              if (!automatic && wasAutomatic) paid.value = manualPaid;
              paid.readOnly = automatic;
              if (status.value === 'PAID') paid.value = paid.dataset.total;
              if (status.value === 'PENDING') paid.value = '0.00';
              wasAutomatic = automatic;
            }
            status.addEventListener('change', updatePaid);
            updatePaid();
          })();
        </script>

        <h5>Customer reviews</h5>
        @if ($orderReviews === null)
          <p>Reviews are unavailable because the booking number or service code is missing.</p>
        @else
          @forelse ($orderReviews as $review)
            <div class="border rounded p-3 mb-3">
              <strong>{{ $review->guest_name }}</strong>
              <p class="mb-1">Rating: {{ $review->star_rating }} / 5</p>
              <p class="mb-1">Status: {{ $review->status }}</p>
              <p class="mb-1">Date: {{ $review->created_at }}</p>
              <p style="white-space: pre-wrap;">{{ $review->comments }}</p>
              <form method="POST" action="{{ route('pages.orders.reviews.delete', ['order' => $order->id, 'review' => $review->id]) }}" onsubmit="return confirm('Permanently delete this review?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-danger">Delete review</button>
              </form>
            </div>
          @empty
            <p>No reviews for this booking yet.</p>
          @endforelse
        @endif

        <h5>Review link</h5>
        @if ($reviewService && !$reviewService['ambiguous'])
          <p>Generate a 30-day review link for this booking and share it privately with the guest.</p>
          <form method="POST" action="{{ route('pages.orders.review-link', $order->id) }}">
            @csrf
            <button type="submit" class="btn btn-primary">Create review link</button>
          </form>
          @isset($reviewLink)
            <label for="review-link">Guest review link</label>
            <textarea id="review-link" class="form-control" rows="3" readonly>{{ $reviewLink }}</textarea>
            <button type="button" class="btn btn-secondary" id="copy-review-link">Copy link</button>
            <span id="copy-review-link-status" role="status" aria-live="polite"></span>
            <script>
              document.getElementById('copy-review-link').addEventListener('click', async function () {
                var link = document.getElementById('review-link');
                var status = document.getElementById('copy-review-link-status');
                try {
                  if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(link.value);
                  } else {
                    link.focus();
                    link.select();
                    if (!document.execCommand('copy')) throw new Error('Copy failed');
                  }
                  status.textContent = 'Link copied.';
                } catch (error) {
                  link.focus();
                  link.select();
                  status.textContent = 'Could not copy the link. Please copy the selected text manually.';
                }
              });
            </script>
          @endisset
        @elseif ($reviewService)
          <p>This service code matches both a tour and a product; a review link cannot be generated until the code is unambiguous.</p>
        @else
          <p>No tour package or product matches this service code; a review link is unavailable.</p>
        @endif

        <a href="{{ route('pages.orders') }}" class="btn btn-secondary">Back to orders</a>
        <form method="POST" action="{{ route('pages.orders.delete', $order->id) }}" onsubmit="return confirm('Permanently delete this order and its room details? Stock and payments will not be changed.');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">Delete order</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
