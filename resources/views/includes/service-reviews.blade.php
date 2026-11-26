<section class="section" id="tour-reviews">
    <div class="container">
        <h3>Guest Reviews</h3>
        @if (session('tour_review_success'))
            <div class="alert alert-success" role="status">{{ session('tour_review_success') }}</div>
        @endif
        @forelse ($reviews as $review)
            <article class="card mb-3">
                <div class="card-body">
                    <strong>{{ $review->guest_name }}</strong>
                    <div aria-label="{{ $review->star_rating }} out of 5 stars">
                        @for ($star = 0; $star < (int) $review->star_rating && $star < 5; $star++)
                            <i class="bi bi-star-fill text-warning" aria-hidden="true"></i>
                        @endfor
                    </div>
                    <p class="mb-0">{{ $review->comments }}</p>
                </div>
            </article>
        @empty
            <p>No reviews yet.</p>
        @endforelse

        @if ($reviewReservation)
            <form method="POST" action="{{ request()->fullUrl() }}" class="card card-body mt-4">
                @csrf
                <h4>Write a Review</h4>
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="mb-3">
                    <label for="review-rating" class="form-label">Rating</label>
                    <select id="review-rating" name="rating" class="form-select" required>
                        <option value="">Select a rating</option>
                        @for ($rating = 5; $rating >= 1; $rating--)
                            <option value="{{ $rating }}" @selected(old('rating') == $rating)>{{ $rating }} / 5</option>
                        @endfor
                    </select>
                </div>
                <div class="mb-3">
                    <label for="review-comment" class="form-label">Your review</label>
                    <textarea id="review-comment" name="comment" class="form-control" rows="4" maxlength="2000" required>{{ old('comment') }}</textarea>
                </div>
                <div><button type="submit" class="btn btn-primary">Submit Review</button></div>
            </form>
        @endif
    </div>
</section>
