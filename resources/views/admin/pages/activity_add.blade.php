@extends('layouts.app', ['page' => __('Add Activities'), 'pageSlug' => 'activity_add'])

@section('content')
@php
  $selectedAreas = old('area', array_values(array_filter(explode(';', $activityDetail->area ?? ''), 'strlen')));
  $selectedAreas = is_array($selectedAreas) ? array_values(array_filter($selectedAreas, 'is_string')) : [];
  $existingAreas = array_values(array_filter(explode(';', $activityDetail->area ?? ''), 'strlen'));
  $knownAreas = $travelAreas->pluck('id')->map(fn ($id) => (string) $id)->all();
  $types = ['land' => 'Land Activity', 'air' => 'Air Activity', 'water' => 'Water Activity'];
  $languages = ['en' => 'English', 'id' => 'Indonesian'];
  $documents = old('documents_present') ? old('document', []) : array_values(array_filter(explode(';', $activityDetail->foto ?? ''), 'strlen'));
  $documents = is_array($documents) ? array_values(array_filter($documents, 'is_string')) : [];
@endphp
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title">{{ isset($activityDetail) ? 'Edit Activity' : 'Add Activity' }}</h4>
      </div>
      <div class="card-body">
        <form id="activity-form" action="{{ route('activity.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="id" value="{{ old('id', $activityDetail->id ?? '') }}">
          <input type="hidden" name="documents_present" value="1">
          @if ($errors->any())
            <div class="alert alert-danger" role="alert">
              <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
          @endif
          <div class="row">
            <div class="form-group col-lg-6">
              <label for="activity-code">Code</label>
              @if (isset($activityDetail))
                <input id="activity-code" type="text" name="code" class="form-control" maxlength="20" value="{{ old('code', $activityDetail->code) }}" required>
                <small>Translations of the same activity share this code and gallery.</small>
              @else
                <select id="activity-code" name="code" class="form-control activity-select">
                  <option value="">Generate a new code automatically</option>
                  @foreach ($existingActivities as $code => $activities)
                    <option value="{{ $code }}" @selected(old('code') === (string) $code)>{{ $activities->first()->name }} — {{ $code }} ({{ $activities->pluck('lang')->unique()->implode(', ') }})</option>
                  @endforeach
                </select>
                <small>Select an existing activity only when adding its translation.</small>
              @endif
            </div>
            <div class="form-group col-lg-6">
              <label for="activity-name">Name</label>
              <input id="activity-name" type="text" name="name" class="form-control" maxlength="255" value="{{ old('name', $activityDetail->name ?? '') }}" required>
            </div>
            <div class="form-group col-lg-6">
              <label for="activity-slug">Slug</label>
              <input id="activity-slug" type="text" name="slug" class="form-control" value="{{ old('slug', $activityDetail->slug ?? '') }}" required>
            </div>
            <div class="form-group col-lg-6">
              <label for="activity-lang">Language</label>
              <select id="activity-lang" name="lang" class="form-control activity-select" required>
                <option value="">Select language</option>
                @if (!empty($activityDetail->lang) && !isset($languages[$activityDetail->lang]))
                  <option value="{{ $activityDetail->lang }}" @selected(old('lang', $activityDetail->lang) === $activityDetail->lang)>{{ $activityDetail->lang }} (existing)</option>
                @endif
                @foreach ($languages as $value => $label)
                  <option value="{{ $value }}" @selected(old('lang', $activityDetail->lang ?? '') === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group col-lg-6">
              <label for="activity-type">Type</label>
              <select id="activity-type" name="type" class="form-control activity-select" required>
                <option value="">Select activity type</option>
                @if (!empty($activityDetail->type) && !isset($types[$activityDetail->type]))
                  <option value="{{ $activityDetail->type }}" @selected(old('type', $activityDetail->type) === $activityDetail->type)>{{ $activityDetail->type }} (existing)</option>
                @endif
                @foreach ($types as $value => $label)
                  <option value="{{ $value }}" @selected(old('type', $activityDetail->type ?? '') === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <fieldset class="form-group col-lg-6" aria-describedby="area-help">
              <legend class="area-legend">Areas</legend>
              <div class="activity-area-options">
                @foreach ($travelAreas as $area)
                  <label class="activity-area-option">
                    <input type="checkbox" name="area[]" value="{{ $area->id }}" @checked(in_array((string) $area->id, $selectedAreas, true))>
                    <span>{{ ucfirst($area->name) }}</span>
                  </label>
                @endforeach
                @foreach (array_diff($existingAreas, $knownAreas) as $area)
                  <label class="activity-area-option">
                    <input type="checkbox" name="area[]" value="{{ $area }}" @checked(in_array($area, $selectedAreas, true))>
                    <span>{{ $area }} (existing)</span>
                  </label>
                @endforeach
              </div>
              <small id="area-help">{{ $travelAreas->isEmpty() && !$existingAreas ? 'No areas are available. Add a travel area before saving.' : 'Choose one or more areas.' }}</small>
            </fieldset>
          </div>
          <div class="form-group">
            <label for="deskripsi">Description</label>
            <textarea class="form-control" id="deskripsi" name="deskripsi">{{ old('deskripsi', $activityDetail->deskripsi ?? '') }}</textarea>
          </div>
          <div class="form-group">
            <label>Activity photos</label>
            <div class="needsclick dropzone" id="document-dropzone"></div>
            <small>Photos are shared by activities with the same code. Uploading photos replaces the shared gallery.</small>
          </div>
          <div class="card">
            <div class="card-header"><h5 class="card-title">SEO settings</h5></div>
            <div class="card-body">
              <div class="form-group">
                <label for="meta-title">SEO title <span id="meta-title-count">0/70</span></label>
                <input id="meta-title" name="meta_title" class="form-control" maxlength="70" value="{{ old('meta_title', $activityDetail->meta_title ?? '') }}" placeholder="Leave empty to use the activity name">
              </div>
              <div class="form-group">
                <label for="meta-description">Meta description <span id="meta-description-count">0/160</span></label>
                <textarea id="meta-description" name="meta_description" class="form-control" maxlength="160" rows="3">{{ old('meta_description', $activityDetail->meta_description ?? '') }}</textarea>
              </div>
              <div class="form-group">
                <label for="meta-keyword">Meta keywords</label>
                <input id="meta-keyword" name="meta_keyword" class="form-control" maxlength="500" value="{{ old('meta_keyword', $activityDetail->meta_keyword ?? '') }}">
              </div>
              <div class="border rounded p-3">
                <small>Search result preview</small>
                <div id="seo-preview-title" class="text-primary mt-1"></div>
                <div id="seo-preview-url" class="text-success small"></div>
                <div id="seo-preview-description" class="small"></div>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-fill btn-primary">Save</button>
          <a href="{{ route('pages.activity', 'all') }}" class="btn btn-secondary">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>
@push('styles')
  <style>
    .activity-select, .activity-select option { background-color: #1e1e2f; color: #fff; }
    .area-legend { font-size: .75rem; color: rgba(255,255,255,.8); margin-bottom: .5rem; }
    .activity-area-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(125px, 1fr)); gap: .5rem; }
    .activity-area-option { display: flex; align-items: center; gap: .5rem; border: 1px solid #525f7f; border-radius: .375rem; padding: .65rem .75rem; margin: 0; color: #fff; cursor: pointer; }
    .activity-area-option:focus-within { outline: 2px solid #e14eca; outline-offset: 2px; }
    .activity-area-option input { margin: 0; accent-color: #e14eca; width: 1rem; height: 1rem; }
  </style>
@endpush
  @push('js')
  <script src="//cdn.ckeditor.com/4.6.2/standard/ckeditor.js"></script>
  <script>
    var options = {
      filebrowserImageBrowseUrl: '/laravel-filemanager?type=Images',
      filebrowserImageUploadUrl: '/laravel-filemanager/upload?type=Images&_token={{ csrf_token() }}',
      filebrowserBrowseUrl: '/laravel-filemanager?type=Files',
      filebrowserUploadUrl: '/laravel-filemanager/upload?type=Files&_token={{ csrf_token() }}'
    };
    var activityName = $('#activity-name');
    var activitySlug = $('#activity-slug');
    var slugManuallyEdited = {{ isset($activityDetail) ? 'true' : 'false' }};

    function makeActivitySlug(value) {
      return value.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '');
    }

    function updateSeoPreview() {
      var name = activityName.val().trim() || 'Activity name';
      var title = $('#meta-title').val().trim() || name + ' | Bali Activities - Bali Scenic';
      var content = $('#deskripsi').val() || '';
      if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.deskripsi) {
        content = CKEDITOR.instances.deskripsi.getData();
      }
      var contentPreview = content.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
      if (contentPreview.length > 157) contentPreview = contentPreview.substring(0, 157).trim() + '...';
      var description = $('#meta-description').val().trim() || contentPreview || 'Add a meta description to preview this activity in search results.';

      $('#meta-title-count').text($('#meta-title').val().length + '/70');
      $('#meta-description-count').text($('#meta-description').val().length + '/160');
      $('#seo-preview-title').text(title);
      $('#seo-preview-url').text(window.location.origin + '/' + $('#activity-lang').val() + '/activities/' + (activitySlug.val() || 'activity-slug'));
      $('#seo-preview-description').text(description);
    }

    activityName.on('input', function () {
      if (!slugManuallyEdited) activitySlug.val(makeActivitySlug(this.value));
      updateSeoPreview();
    });
    activitySlug.on('input', function () {
      slugManuallyEdited = this.value !== '';
      updateSeoPreview();
    });
    $('#meta-title, #meta-description, #activity-lang').on('input change', updateSeoPreview);
    updateSeoPreview();

    var areaInputs = document.querySelectorAll('#activity-form input[name="area[]"]');
    function updateAreaRequirement() {
      if (areaInputs.length) {
        areaInputs[0].required = !Array.from(areaInputs).some(function (input) { return input.checked; });
      }
    }
    areaInputs.forEach(function (input) { input.addEventListener('change', updateAreaRequirement); });
    updateAreaRequirement();
    var editor = CKEDITOR.replace('deskripsi', options);
    editor.on('change', updateSeoPreview);
    editor.on('instanceReady', updateSeoPreview);
    Dropzone.options.documentDropzone = {
      url: '{{ route('activity.storeMedia') }}',
      maxFilesize: 10, // MB
      acceptedFiles: '.png, .jpg, .jpeg, .gif, .webp',
      addRemoveLinks: true,
      headers: {
        'X-CSRF-TOKEN': "{{ csrf_token() }}"
      },
      success: function (file, response) {
        $('<input>', { type: 'hidden', name: 'document[]', value: response.name }).appendTo('#activity-form');
      },
      removedfile: function (file) {
        var nama;
        if(file.xhr === undefined){
          nama = file.name ;
        }else{
          var response = JSON.parse(file.xhr.response);
          nama = response.name ;
        }
        $.ajax({
            headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
            type:'POST',
            url: '{{ route('activity.deleteMedia') }}',
            data : { "filetodelete" : nama },
            error: function () {
              alert('Could not delete the activity photo. Please try again.');
            }
        }); 
        file.previewElement.remove()
        $('#activity-form').find('input[name="document[]"]').filter(function () {
          return this.value === nama;
        }).remove();
      
      },
      init: function () {
        var files = @json($documents);
        for (var i = 0; i < files.length; i++) {
          var mockFile = { name: files[i], size: 12345, type: 'image/jpg', accepted: true };
          this.emit('addedfile', mockFile);
          this.emit('thumbnail', mockFile, "{{ asset('assets/img/activity') }}/" + encodeURIComponent(files[i]));
          this.emit('complete', mockFile);
          this.files.push(mockFile);
          $('<input>', { type: 'hidden', name: 'document[]', value: files[i] }).appendTo('#activity-form');
        }
      }
    }
    
  </script>
 
  @endpush
@endsection
