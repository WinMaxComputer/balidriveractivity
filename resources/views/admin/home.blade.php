@extends('layouts.app', ['pageSlug' => 'dashboard'])

@section('content')
<div class="row">
    <div class="col-12">
        <h2>Dashboard</h2>
    </div>
</div>
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Recent bookings</h4>
                <a href="{{ route('pages.orders') }}">View all</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead class="text-primary"><tr><th>Booking</th><th>Guest</th><th>Service code</th><th>Booked</th><th>Payment</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($bookings as $booking)
                                <tr>
                                    <td><a href="{{ route('pages.orders.detail', $booking->id) }}">{{ $booking->no_reservasi }}</a></td>
                                    <td>{{ $booking->guest_name }}</td>
                                    <td>{{ $booking->code_service }}</td>
                                    <td>{{ $booking->tgl_reservasi ?: '—' }}</td>
                                    <td>{{ $booking->status ?: '—' }}</td>
                                    <td>{{ $booking->book_status ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">No bookings found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Recent transfer orders</h4>
                <a href="{{ route('pages.transfer-order') }}">View all</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead class="text-primary"><tr><th>Requested</th><th>Guest</th><th>Type</th><th>Destination</th><th>Pickup date</th></tr></thead>
                        <tbody>
                            @forelse ($transfers as $transfer)
                                <tr>
                                    <td>{{ $transfer->created_at }}</td>
                                    <td>{{ $transfer->name }}</td>
                                    <td>{{ ucfirst($transfer->booking_type) }}</td>
                                    <td>{{ $transfer->destination }}</td>
                                    <td>{{ $transfer->pickup_date }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">No transfer orders found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h4 class="card-title">Recent reviews</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead class="text-primary"><tr><th>Date</th><th>Guest</th><th>Service code</th><th>Rating</th><th>Review</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($reviews as $review)
                                <tr>
                                    <td>{{ $review->created_at }}</td>
                                    <td>{{ $review->guest_name }}</td>
                                    <td>{{ $review->product_code }}</td>
                                    <td>{{ $review->star_rating }} / 5</td>
                                    <td>{{ $review->comments }}</td>
                                    <td>{{ $review->status }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">No reviews found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <p>Best sellers are ranked by number of bookings, excluding cancelled bookings. Service codes shared by products and tours are excluded because their type cannot be determined.</p>
    </div>
    @foreach (['Best-selling products' => $topProducts, 'Best-selling tours' => $topTours] as $title => $services)
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h4 class="card-title">{{ $title }}</h4></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="text-primary"><tr><th>Name</th><th>Service code</th><th>Bookings</th></tr></thead>
                            <tbody>
                                @forelse ($services as $service)
                                    <tr>
                                        <td>{{ $service->name }}</td>
                                        <td>{{ $service->code_service }}</td>
                                        <td>{{ $service->booking_count }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3">No qualifying bookings found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
