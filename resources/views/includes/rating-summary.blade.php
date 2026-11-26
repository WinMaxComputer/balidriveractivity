@if ($rating)
    @php
        $score = (float) $rating->average_rating;
        $stars = round($score * 2) / 2;
        $formattedScore = number_format($score, 1);
    @endphp
    <div class="tour-rating rating-summary" aria-label="Rated {{ $formattedScore }} out of 5 from {{ $rating->review_count }} reviews">
        @for ($star = 1; $star <= 5; $star++)
            <i class="bi {{ $stars >= $star ? 'bi-star-fill' : ($stars >= $star - 0.5 ? 'bi-star-half' : 'bi-star') }}" aria-hidden="true"></i>
        @endfor
        <span>{{ $formattedScore }} ({{ $rating->review_count }})</span>
    </div>
@else
    <div class="tour-rating rating-summary">No reviews yet</div>
@endif
