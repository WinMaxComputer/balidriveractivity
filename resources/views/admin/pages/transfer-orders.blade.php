@extends('layouts.app', ['page' => __('Transfer Order'), 'pageSlug' => 'transfer-order'])

@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">Transfer Orders</h4>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table">
            <thead class="text-primary">
              <tr>
                <th>Requested</th>
                <th>Full name</th>
                <th>Email</th>
                <th>Mobile</th>
                <th>Booking type</th>
                <th>Destination</th>
                <th>Pickup date</th>
                <th>Flight number</th>
                <th>Arrival time</th>
                <th>Passengers</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($orders as $order)
                <tr>
                  <td>{{ $order->created_at }}</td>
                  <td>{{ $order->name }}</td>
                  <td>{{ $order->email }}</td>
                  <td>{{ $order->mobile }}</td>
                  <td>{{ ucfirst($order->booking_type) }}</td>
                  <td>{{ $order->destination }}</td>
                  <td>{{ $order->pickup_date }}</td>
                  <td>{{ $order->flight_number ?: '—' }}</td>
                  <td>{{ $order->arrival_time ?: '—' }}</td>
                  <td>{{ $order->passengers }}</td>
                </tr>
              @empty
                <tr><td colspan="10">No transfer orders found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        {{ $orders->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>
@endsection
