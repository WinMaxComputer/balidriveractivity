@extends('layouts.app', ['page' => __('Travel Guide & Tips'), 'pageSlug' => 'travel-guide'])

@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title">Travel Guide &amp; Tips</h4>
        <a href="{{ route('pages.travel-guide_add') }}" class="btn btn-primary">Add Article</a>
      </div>
      <div class="card-body">
        @if (session('success'))
          <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif
        <div class="table-responsive">
          <table class="table">
            <thead class="text-primary">
              <tr>
                <th>Title</th>
                <th>Code</th>
                <th>Language</th>
                <th>Updated</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($guides as $guide)
                <tr>
                  <td>{{ $guide->title }}</td>
                  <td>{{ $guide->code }}</td>
                  <td>{{ strtoupper($guide->lang) }}</td>
                  <td>{{ $guide->updated_at }}</td>
                  <td class="text-right">
                    <a href="{{ route('travel-guide.edit', $guide->id) }}" class="btn btn-sm btn-info">Edit</a>
                    <a href="{{ route('travel_guides.detail', ['locale' => $guide->lang, 'slug' => $guide->slug]) }}" class="btn btn-sm btn-secondary" target="_blank" rel="noopener">View</a>
                    <form action="{{ route('travel-guide.destroy', $guide->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this article?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center">No articles yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        {{ $guides->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
