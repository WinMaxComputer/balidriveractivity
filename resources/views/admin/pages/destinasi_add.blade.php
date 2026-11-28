@extends('layouts.app', ['page' => __('Add Destinations'), 'pageSlug' => 'destinasi_add'])

@section('content')
@php
  $isEditing = isset($destinasiDetail) && ($type ?? '') !== 'copy';
  $isCopying = isset($destinasiDetail) && ($type ?? '') === 'copy';
  $destinationTypes = [
    'ubud' => 'Ubud Bali',
    'south' => 'South Bali',
    'north' => 'North Bali',
    'east' => 'East Bali',
    'kintamani' => 'Kintamani Bali',
    'west' => 'West Bali',
  ];
  if ($isEditing && !empty($destinasiDetail->type) && !isset($destinationTypes[$destinasiDetail->type])) {
    $destinationTypes[$destinasiDetail->type] = $destinasiDetail->type.' (existing)';
  }
  $storedPhotos = array_values(array_filter(explode(';', $destinasiDetail->foto ?? ''), 'strlen'));
  $photoNames = old('documents_present') ? old('document', []) : $storedPhotos;
  $photoNames = is_array($photoNames) ? array_values(array_filter($photoNames, 'is_string')) : [];
@endphp
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">{{ $isEditing ? 'Edit Destination' : ($isCopying ? 'Copy Destination' : 'Add Destination') }}</h4>
      </div>
      <div class="card-body">
        @if ($errors->any())
          <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
          </div>
        @endif
        <form id="destination-form" action="{{ route('destinasi.store') }}" method="POST">
          @csrf
          <input type="hidden" name="id" value="{{ old('id', $isEditing ? $destinasiDetail->id : '') }}">
          <input type="hidden" name="documents_present" value="1">

          <div class="row">
            <div class="form-group col-lg-6">
              <label for="destination-code">Code</label>
              @if ($isEditing)
                <input type="hidden" name="code" value="{{ $destinasiDetail->code }}">
                <input id="destination-code" class="form-control" value="{{ $destinasiDetail->code }}" readonly>
                <small>Translations of the same destination share this code and photo gallery.</small>
              @else
                <select id="destination-code" name="code" class="form-control" style="background-color: #000; color: #fff;">
                  <option value="">Generate a new code automatically</option>
                  @foreach ($existingDestinations as $code => $destinations)
                    <option value="{{ $code }}" @selected(old('code', $isCopying ? $destinasiDetail->code : '') === (string) $code)>{{ $destinations->first()->name }} — {{ $code }} ({{ $destinations->pluck('lang')->unique()->implode(', ') }})</option>
                  @endforeach
                </select>
                <small>Select an existing destination only when adding its translation.</small>
              @endif
            </div>
            <div class="form-group col-lg-6">
              <label for="destination-name">Name</label>
              <input id="destination-name" type="text" name="name" class="form-control" maxlength="255" value="{{ old('name', $destinasiDetail->name ?? '') }}" required>
            </div>
            <div class="form-group col-lg-6">
              <label for="destination-lang">Language</label>
              <select id="destination-lang" name="lang" class="form-control" style="background-color: #000; color: #fff;" required>
                @if ($isEditing && !in_array($destinasiDetail->lang, ['en', 'id'], true))
                  <option value="{{ $destinasiDetail->lang }}" @selected(old('lang', $destinasiDetail->lang) === $destinasiDetail->lang)>{{ $destinasiDetail->lang }} (existing)</option>
                @endif
                <option value="en" @selected(old('lang', $destinasiDetail->lang ?? 'en') === 'en')>English</option>
                <option value="id" @selected(old('lang', $destinasiDetail->lang ?? 'en') === 'id')>Indonesia</option>
              </select>
            </div>
            <div class="form-group col-lg-6">
              <label for="destination-slug">Slug</label>
              <input id="destination-slug" type="text" name="slug" class="form-control" maxlength="255" value="{{ old('slug', $destinasiDetail->slug ?? '') }}">
            </div>
            <div class="form-group col-lg-6">
              <label for="destination-type">Area</label>
              <select id="destination-type" name="type" class="form-control" style="background-color: #000; color: #fff;" required>
                @foreach ($destinationTypes as $value => $label)
                  <option value="{{ $value }}" @selected(old('type', $destinasiDetail->type ?? '') === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="destination-photos">Photos</label>
            <div class="needsclick dropzone" id="destination-photos"></div>
            <small>Upload JPG, PNG, GIF, or WebP images (maximum 10 MB per image).</small>
          </div>

          <div class="form-group">
            <label for="destination-description">Description</label>
            <textarea id="destination-description" class="form-control" name="deskripsi" rows="12">{{ old('deskripsi', $destinasiDetail->deskripsi ?? '') }}</textarea>
          </div>

          <div class="card">
            <div class="card-header"><h5 class="card-title">SEO settings</h5></div>
            <div class="card-body">
              <div class="form-group">
                <label for="meta-title">SEO title <span id="meta-title-count">0/70</span></label>
                <input id="meta-title" class="form-control" name="meta_title" maxlength="70" value="{{ old('meta_title', $destinasiDetail->meta_title ?? '') }}" placeholder="Leave empty to use the destination name">
              </div>
              <div class="form-group">
                <label for="meta-description">Meta description <span id="meta-description-count">0/160</span></label>
                <textarea id="meta-description" class="form-control" name="meta_description" maxlength="160" rows="3">{{ old('meta_description', $destinasiDetail->meta_description ?? '') }}</textarea>
              </div>
              <div class="form-group">
                <label for="meta-keyword">Meta keywords</label>
                <input id="meta-keyword" class="form-control" name="meta_keyword" maxlength="500" value="{{ old('meta_keyword', $destinasiDetail->meta_keyword ?? '') }}">
              </div>
              <div class="border rounded p-3">
                <small>Search result preview</small>
                <div id="seo-preview-title" class="text-primary mt-1"></div>
                <div id="seo-preview-url" class="text-success small"></div>
                <div id="seo-preview-description" class="small"></div>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-fill btn-primary">Simpan</button>
          <a href="{{ route('pages.destinasi') }}" class="btn btn-secondary">Cancel</a>
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
    var nameInput = $('#destination-name');
    var slugInput = $('#destination-slug');
    var slugManuallyEdited = {{ $isEditing ? 'true' : 'false' }};

    function makeSlug(value) {
      return value.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '');
    }

    function updateSeoPreview() {
      var destinationName = nameInput.val().trim() || 'Destination name';
      var seoTitle = $('#meta-title').val().trim() || destinationName + ' | Bali Destinations - Bali Scenic';
      var content = $('#destination-description').val() || '';
      if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['destination-description']) {
        content = CKEDITOR.instances['destination-description'].getData();
      }
      var contentPreview = content.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
      if (contentPreview.length > 157) contentPreview = contentPreview.substring(0, 157).trim() + '...';
      var description = $('#meta-description').val().trim() || contentPreview || 'Add a meta description to preview this destination in search results.';

      $('#meta-title-count').text($('#meta-title').val().length + '/70');
      $('#meta-description-count').text($('#meta-description').val().length + '/160');
      $('#seo-preview-title').text(seoTitle);
      $('#seo-preview-url').text(window.location.origin + '/' + $('#destination-lang').val() + '/destinations/' + (slugInput.val() || 'destination-slug'));
      $('#seo-preview-description').text(description);
    }

    nameInput.on('input', function () {
      if (!slugManuallyEdited) slugInput.val(makeSlug(this.value));
      updateSeoPreview();
    });
    slugInput.on('input', function () {
      slugManuallyEdited = this.value !== '';
      updateSeoPreview();
    });
    $('#meta-title, #meta-description, #destination-lang').on('input change', updateSeoPreview);
    updateSeoPreview();

    var editorOptions = {
      filebrowserImageBrowseUrl: '/laravel-filemanager?type=Images',
      filebrowserImageUploadUrl: '/laravel-filemanager/upload?type=Images&_token={{ csrf_token() }}',
      filebrowserBrowseUrl: '/laravel-filemanager?type=Files',
      filebrowserUploadUrl: '/laravel-filemanager/upload?type=Files&_token={{ csrf_token() }}'
    };
    var editor = CKEDITOR.replace('destination-description', editorOptions);
    editor.on('change', updateSeoPreview);
    editor.on('instanceReady', updateSeoPreview);

    Dropzone.options.destinationPhotos = {
      url: '{{ route('destinasi.storeMedia') }}',
      maxFilesize: 10,
      acceptedFiles: '.jpg,.jpeg,.png,.gif,.webp',
      addRemoveLinks: true,
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
      success: function (file, response) {
        file.destinationPhotoName = response.name;
        $('<input>', { type: 'hidden', name: 'document[]', value: response.name }).appendTo('#destination-form');
      },
      removedfile: function (file) {
        var photoName = file.destinationPhotoName;
        if (photoName) {
          $('#destination-form input[name="document[]"]').filter(function () {
            return this.value === photoName;
          }).remove();
          if (!file.existingPhoto) {
            $.ajax({
              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
              type: 'POST',
              url: '{{ route('destinasi.deleteMedia') }}',
              data: { filetodelete: photoName },
              error: function () { alert('Unable to remove the uploaded photo. Please try again.'); }
            });
          }
        }
        if (file.previewElement) file.previewElement.remove();
      },
      init: function () {
        var photos = @json($photoNames);
        var savedPhotos = @json($storedPhotos);
        photos.forEach(function (photoName) {
          var mockFile = {
            name: photoName,
            size: 12345,
            type: 'image/jpeg',
            accepted: true,
            destinationPhotoName: photoName,
            existingPhoto: savedPhotos.indexOf(photoName) !== -1
          };
          this.emit('addedfile', mockFile);
          this.emit('thumbnail', mockFile, '/assets/img/destinasi/' + encodeURIComponent(photoName));
          this.emit('complete', mockFile);
          this.files.push(mockFile);
          $('<input>', { type: 'hidden', name: 'document[]', value: photoName }).appendTo('#destination-form');
        }, this);
      }
    };
  })(jQuery);
</script>
@endpush
