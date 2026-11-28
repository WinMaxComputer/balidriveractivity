<div class="sidebar">
    <div class="sidebar-wrapper">
        <div class="logo">
            <a href="#" class="simple-text logo-mini">{{ __('BD') }}</a>
            <a href="#" class="simple-text logo-normal">{{ __('Dashboard') }}</a>
        </div>
        <ul class="nav">
            <li @if ($pageSlug == 'dashboard') class="active " @endif>
                <a href="{{ route('dashboard') }}">
                    <i class="tim-icons icon-chart-pie-36"></i>
                    <p>{{ __('Dashboard') }}</p>
                </a>
            </li>
            
           
            <li @if (in_array($pageSlug, ['travel-guide', 'travel-guide-add'], true)) class="active " @endif>
                <a href="{{ route('pages.travel-guide') }}">
                    <i class="tim-icons icon-chart-pie-36"></i>
                    <p>{{ __('Travel Guide & Tips') }}</p>
                </a>
            </li>
            <li @if ($pageSlug == 'tour') class="active " @endif>
                <a href="{{ route('pages.tour') }}">
                    <i class="tim-icons icon-chart-pie-36"></i>
                    <p>{{ __('Tour') }}</p>
                </a>
            </li>
            <li @if ($pageSlug == 'destinasi') class="active " @endif>
                <a href="{{ route('pages.destinasi') }}">
                    <i class="tim-icons icon-chart-pie-36"></i>
                    <p>{{ __('Destination') }}</p>
                </a>
            </li>
            <li @if ($pageSlug == 'activity') class="active " @endif>
                <a href="{{ route('pages.activity', 'all') }}">
                    <i class="tim-icons icon-chart-pie-36"></i>
                    <p>{{ __('Aktifitas') }}</p>
                </a>
            </li>
            <li @if ($pageSlug == 'Mix Tours') class="active " @endif>
                <a href="{{ route('pages.products', 'all') }}">
                    <i class="tim-icons icon-chart-pie-36"></i>
                    <p>{{ __('Mix Tours') }}</p>
                </a>
            </li>
            @if (auth()->user()->role === 'admin')
                <li @if ($pageSlug == 'booking') class="active " @endif>
                    <a href="{{ route('pages.orders') }}">
                        <i class="tim-icons icon-chart-pie-36"></i>
                        <p>Booking</p>
                    </a>
                </li>
                <li @if ($pageSlug == 'transfer-order') class="active " @endif>
                    <a href="{{ route('pages.transfer-order') }}">
                        <i class="tim-icons icon-chart-pie-36"></i>
                        <p>Transfer Order</p>
                    </a>
                </li>
            @endif
            
        </ul>
    </div>
</div>
