@php
  $recommendationLabels = [
    'popular' => 'Popular',
    'best_value' => 'Best Value',
    'top_pick' => 'Top Pick',
  ];
  $recommendationValue = $product->recommendation_badge ?? '';
  $recommendationLabel = $recommendationLabels[$recommendationValue] ?? null;
@endphp
@if ($recommendationLabel)
  <span class="product-recommendation-badge recommendation-{{ $recommendationValue }} {{ !empty($inline) ? 'is-inline' : '' }}">{{ $recommendationLabel }}</span>
@endif
