@extends('layouts.app', ['page' => __('Orders'), 'pageSlug' => 'booking'])

@push('styles')
  @vite('resources/js/admin-orders.js')
@endpush

@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">Orders</h4>
      </div>
      <div class="card-body">
        @if (session('order_message'))
          <div class="alert alert-success" role="status">{{ session('order_message') }}</div>
        @endif
        <div id="orders-table-error" class="alert alert-danger" role="alert" hidden>
          Unable to load orders. Please refresh the page.
        </div>
        <div class="table-responsive">
          <table id="orders-table" class="table" data-source="{{ route('pages.orders.data') }}" data-detail-url="{{ route('pages.orders.detail', ['order' => 999999999]) }}" data-delete-url="{{ route('pages.orders.delete', ['order' => 999999999]) }}">
            <thead class="text-primary">
              <tr>
                <th>Booking number</th>
                <th>Booking date</th>
                <th>Guest name</th>
                <th>Guest email</th>
                <th>Check-in</th>
                <th>Adults</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Payment</th>
                <th>Payment status</th>
                <th>Booking status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($orders as $order)
                <tr>
                  <td>{{ $order->no_reservasi }}</td>
                  <td>{{ $order->tgl_reservasi }}</td>
                  <td>{{ $order->guest_name }}</td>
                  <td>{{ $order->guest_email }}</td>
                  <td>{{ $order->cek_in }}</td>
                  <td>{{ $order->adult }}</td>
                  <td>{{ $order->total }}</td>
                  <td>{{ $order->guest_paid }}</td>
                  <td>{{ $order->payment_type }}</td>
                  <td>{{ $order->status }}</td>
                  <td>{{ $order->book_status }}</td>
                  <td>
                    <a class="btn btn-sm btn-info" href="{{ route('pages.orders.detail', $order->id) }}">Detail</a>
                    <form method="POST" action="{{ route('pages.orders.delete', $order->id) }}" onsubmit="return confirm('Permanently delete this order and its room details? Stock and payments will not be changed.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="12">No orders found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div id="orders-fallback-pagination">{{ $orders->links('pagination::bootstrap-5') }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
