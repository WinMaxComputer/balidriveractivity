@extends('layouts.app', ['page' => __('Travel Guide & Tips'), 'pageSlug' => 'travel-guide-add'])

@section('content')
@php
  $storedPhotos = array_values(array_filter(explode(';', $guide->photos ?? ''), 'strlen'));
  $photoNames = old('photos_present')
    ? old('photos', [])
    : $storedPhotos;
  $photoNames = is_array($photoNames) ? array_values(array_filter($photoNames, 'is_string')) : [];
@endphp
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">{{ isset($guide) ? 'Edit Article' : 'Add Article' }}</h4>
      </div>
      <div class="card-body">
        @if ($errors->any())
          <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
          </div>
        @endif
        <form id="travel-guide-form" action="{{ route('travel-guide.store') }}" method="POST">
          @csrf
          <input type="hidden" name="id" value="{{ old('id', $guide->id ?? '') }}">
          <input type="hidden" name="photos_present" value="1">
          <div class="row">
            <div class="form-group col-lg-6">
              <label for="guide-code">Code</label>
              @if (isset($guide))
                <input type="hidden" name="code" value="{{ $guide->code }}">
                <input id="guide-code" class="form-control" value="{{ $guide->code }}" readonly>
              @else
                <select id="guide-code" name="code" class="form-control" style="background-color: #000; color: #fff;">
                  <option value="">Create a new article code</option>
                  @foreach ($existingGuides as $code => $translations)
                    <option value="{{ $code }}" @selected(old('code') === (string) $code)>{{ $translations->first()->title }} — {{ $code }} ({{ $translations->pluck('lang')->unique()->implode(', ') }})</option>
                  @endforeach
                </select>
                <small>Select an existing code to add its translation.</small>
              @endif
            </div>
            <div class="form-group col-lg-6">
              <label for="guide-lang">Language</label>
              <select id="guide-lang" name="lang" class="form-control" style="background-color: #000; color: #fff;" required>
                <option value="en" @selected(old('lang', $guide->lang ?? 'en') === 'en')>English</option>
                <option value="id" @selected(old('lang', $guide->lang ?? 'en') === 'id')>Indonesia</option>
              </select>
            </div>
            <div class="form-group col-lg-6">
              <label for="guide-title">Article title</label>
              <input id="guide-title" type="text" name="title" class="form-control" maxlength="255" value="{{ old('title', $guide->title ?? '') }}" required>
            </div>
            <div class="form-group col-lg-6">
              <label for="guide-slug">URL slug</label>
              <input id="guide-slug" type="text" name="slug" class="form-control" maxlength="255" value="{{ old('slug', $guide->slug ?? '') }}" required>
            </div>
          </div>

          <div class="form-group">
            <label for="travel-guide-dropzone">Article photos</label>
            <div class="needsclick dropzone" id="travel-guide-dropzone"></div>
            <small>Upload JPG, PNG, or WebP photos (maximum 10 MB per image).</small>
          </div>

          <div class="form-group">
            <label for="guide-content">Article content</label>
            <textarea id="guide-content" name="content" class="form-control" rows="14" required>{{ old('content', $guide->content ?? '') }}</textarea>
            <small>Use the editor's link and image buttons to insert links or place photos within the article.</small>
          </div>

          <div class="card">
            <div class="card-header"><h5 class="card-title">SEO settings</h5></div>
            <div class="card-body">
              <div class="form-group">
                <label for="meta-title">SEO title <span id="meta-title-count">0/70</span></label>
                <input id="meta-title" name="meta_title" class="form-control" maxlength="70" value="{{ old('meta_title', $guide->meta_title ?? '') }}" placeholder="Leave empty to use the article title">
              </div>
              <div class="form-group">
                <label for="meta-description">Meta description <span id="meta-description-count">0/160</span></label>
                <textarea id="meta-description" name="meta_description" class="form-control" maxlength="160" rows="3">{{ old('meta_description', $guide->meta_description ?? '') }}</textarea>
              </div>
              <div class="form-group">
                <label for="meta-keywords">Meta keywords</label>
                <input id="meta-keywords" name="meta_keywords" class="form-control" maxlength="500" value="{{ old('meta_keywords', $guide->meta_keywords ?? '') }}">
              </div>
              <div class="seo-preview border rounded p-3">
                <small>Search result preview</small>
                <div id="seo-preview-title" class="text-primary mt-1"></div>
                <div id="seo-preview-url" class="text-success small"></div>
                <div id="seo-preview-description" class="small"></div>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Save Article</button>
          <a href="{{ route('pages.travel-guide') }}" class="btn btn-secondary">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('js')
<script src="//cdn.ckeditor.com/4.6.2/standard/ckeditor.js"></script>
<script>
  (function ($) {
    var title = $('#guide-title');
    var slug = $('#guide-slug');
    var slugManuallyEdited = slug.val() !== '';

    function makeSlug(value) {
      return value.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '');
    }

    function updatePreview() {
      var articleTitle = title.val().trim() || 'Article title';
      var seoTitle = $('#meta-title').val().trim() || articleTitle + ' | Travel Guide & Tips - Bali Scenic';
      var content = $('#guide-content').val() || '';
      if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['guide-content']) {
        content = CKEDITOR.instances['guide-content'].getData();
      }
      var contentPreview = content.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
      if (contentPreview.length > 157) contentPreview = contentPreview.substring(0, 157).trim() + '...';
      var description = $('#meta-description').val().trim() || contentPreview || 'Add a meta description to preview how this article may appear in search results.';
      $('#meta-title-count').text($('#meta-title').val().length + '/70');
      $('#meta-description-count').text($('#meta-description').val().length + '/160');
      $('#seo-preview-title').text(seoTitle);
      $('#seo-preview-url').text(window.location.origin + '/' + $('#guide-lang').val() + '/travel-guide/' + (slug.val() || 'article-slug'));
      $('#seo-preview-description').text(description);
    }

    title.on('input', function () {
      if (!slugManuallyEdited) slug.val(makeSlug(this.value));
      updatePreview();
    });
    slug.on('input', function () {
      slugManuallyEdited = this.value !== '';
      updatePreview();
    });
    $('#meta-title, #meta-description, #guide-lang').on('input change', updatePreview);
    updatePreview();

    var editorOptions = {
      filebrowserImageBrowseUrl: '/laravel-filemanager?type=Images',
      filebrowserImageUploadUrl: '/laravel-filemanager/upload?type=Images&_token={{ csrf_token() }}',
      filebrowserBrowseUrl: '/laravel-filemanager?type=Files',
      filebrowserUploadUrl: '/laravel-filemanager/upload?type=Files&_token={{ csrf_token() }}'
    };
    var editor = CKEDITOR.replace('guide-content', editorOptions);
    editor.on('change', updatePreview);
    editor.on('instanceReady', updatePreview);

    Dropzone.options.travelGuideDropzone = {
      url: '{{ route('travel-guide.storePhoto') }}',
      maxFilesize: 10,
      acceptedFiles: '.jpg,.jpeg,.png,.webp',
      addRemoveLinks: true,
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
      success: function (file, response) {
        file.travelGuideName = response.name;
        $('<input>', { type: 'hidden', name: 'photos[]', value: response.name }).appendTo('#travel-guide-form');
      },
      removedfile: function (file) {
        var name = file.travelGuideName;
        if (name) {
          $('#travel-guide-form input[name="photos[]"]').filter(function () {
            return this.value === name;
          }).remove();
          if (!file.existingPhoto) {
            $.ajax({
              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
              type: 'POST',
              url: '{{ route('travel-guide.deletePhoto') }}',
              data: { filetodelete: name },
              error: function () { alert('Unable to remove the uploaded photo. Please try again.'); }
            });
          }
        }
        if (file.previewElement) file.previewElement.remove();
      },
      error: function (file) {
        file.travelGuideUploadFailed = true;
      },
      init: function () {
        var photos = @json($photoNames);
        var savedPhotos = @json($storedPhotos);
        photos.forEach(function (name) {
          var mockFile = {
            name: name,
            size: 12345,
            type: 'image/jpeg',
            accepted: true,
            travelGuideName: name,
            existingPhoto: savedPhotos.indexOf(name) !== -1
          };
          this.emit('addedfile', mockFile);
          this.emit('thumbnail', mockFile, '/assets/img/travel-guide/' + encodeURIComponent(name));
          this.emit('complete', mockFile);
          this.files.push(mockFile);
          $('<input>', { type: 'hidden', name: 'photos[]', value: name }).appendTo('#travel-guide-form');
        }, this);
      }
    };
  })(jQuery);
</script>
@endpush
