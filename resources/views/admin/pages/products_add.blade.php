@extends('layouts.app', ['page' => __('Add Products'), 'pageSlug' => 'products_add'])

@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="card ">
      <div class="card-header">
        <h4 class="card-title">Products</h4>
        
      </div>
      <div class="card-body">
        @if (\Session::has('success'))
          <div class="alert alert-warning">
              <ul>
                  <li>{!! \Session::get('success') !!}</li>
              </ul>
          </div>
        @endif


      <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
      @csrf
        @if ($errors->any())
          <div class="alert alert-danger" role="alert">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
          </div>
        @endif
        <input type="hidden" name="documents_present" value="1">
        <input type="hidden" name="categories_present" value="1">
        <div class="row">
          <div class="form-group col-lg-6">
            <label>Code</label>
            <input type="hidden" name="id" class="form-control" placeholder="code" value="{{ $productsDetail->id ?? '' }}" >
            @if (isset($productsDetail))
              <input type="text" name="code" class="form-control" placeholder="code" value="{{ old('code', $productsDetail->code) }}" required>
              <small>Translations of the same product share this code.</small>
            @else
              <select name="code" class="form-control" style="background-color: #000; color: #fff;">
                <option value="">Generate a new code automatically</option>
                @foreach ($existingProducts as $code => $products)
                  <option value="{{ $code }}" @selected(old('code') === (string) $code)>{{ $products->first()->product_name }} — {{ $code }} ({{ $products->pluck('lang')->unique()->implode(', ') }})</option>
                @endforeach
              </select>
              <small>Select an existing product only when adding its translation.</small>
            @endif
          </div>
          <div class="form-group col-lg-6">
              <label>Name</label>
              <input type="text" name="product_name" id="product_name" class="form-control" placeholder="product_name" value="{{ old('product_name', $productsDetail->product_name ?? '') }}" required>
          </div>
        </div>
        <div class="row">
          <div class="form-group col-lg-6">
            <label>Price</label>
            <input type="number" name="price" class="form-control" placeholder="Price" value="{{ old('price', $productsDetail->price ?? '') }}" required>
          </div>
          
          <div class="form-group col-lg-6">
              <label>Lang</label>
              <select name="lang" class="form-control" style="background-color: #000; color: #fff;" required>

                @if (!empty($productsDetail->lang) && !in_array($productsDetail->lang, ['en', 'id'], true))
                  <option value="{{ $productsDetail->lang }}" @selected(old('lang', $productsDetail->lang) === $productsDetail->lang)>{{ $productsDetail->lang }} (existing)</option>
                @endif
                <option value="en" @selected(old('lang', $productsDetail->lang ?? 'en') === 'en')>English</option>
                <option value="id" @selected(old('lang', $productsDetail->lang ?? 'en') === 'id')>Indonesia</option>
              </select>
          </div>
        </div>
        <div class="row">
          <div class="form-group col-lg-6">
              <label>Slug</label>
              <input type="text" name="slug" id="slug" class="form-control" placeholder="Slug" value="{{ old('slug', $productsDetail->slug ?? '') }}">
          </div>
          <div class="form-group col-lg-6">
                <label>Estimated Tour Duration</label>
                <input type="text" name="tour_duration" class="form-control" placeholder="e.g. 5 hours or 2 days" value="{{ old('tour_duration', $productsDetail->tour_duration ?? '') }}">
          </div>
          </div>
          <div class="row">
            <div class="form-group col-lg-6">
              <label>Recommendation Badge</label>
              <select name="recommendation_badge" class="form-control" style="background-color: #000; color: #fff;">
                <option value="">No badge</option>
                <option value="popular" @selected(old('recommendation_badge', $productsDetail->recommendation_badge ?? '') === 'popular')>Popular</option>
                <option value="best_value" @selected(old('recommendation_badge', $productsDetail->recommendation_badge ?? '') === 'best_value')>Best Value</option>
                <option value="top_pick" @selected(old('recommendation_badge', $productsDetail->recommendation_badge ?? '') === 'top_pick')>Top Pick</option>
              </select>
            </div>
            <div class="form-group col-lg-6">
              <label>Meta Description</label>
              <input class="form-control" id="meta_description" name="meta_description" value="{{ old('meta_description', $productsDetail->meta_description ?? '') }}">
            </div>
          </div>
          <div class="form-group">
            <label for="meta-keyword-entry">Meta Keywords</label>
            @php
              $keywordInput = old('meta_keyword', $productsDetail->meta_keyword ?? '');
              $selectedKeywords = collect(is_array($keywordInput) ? $keywordInput : preg_split('/[,;]+/', (string) $keywordInput))
                ->filter(fn ($keyword) => is_string($keyword) && trim($keyword) !== '')
                ->map(fn ($keyword) => trim($keyword))->unique()->values();
            @endphp
            <div id="meta-keyword-picker" class="form-control d-flex flex-wrap align-items-center" style="height: auto; min-height: 42px; gap: 6px;">
              <div id="meta-keyword-tags" class="d-flex flex-wrap" style="gap: 6px;">
                @foreach ($selectedKeywords as $keyword)
                  <span class="badge badge-info d-inline-flex align-items-center">
                    {{ $keyword }}
                    <button type="button" class="remove-meta-keyword btn btn-link btn-sm p-0 ml-1 text-white" aria-label="Remove {{ $keyword }}">&times;</button>
                    <input type="hidden" name="meta_keyword[]" value="{{ $keyword }}">
                  </span>
                @endforeach
              </div>
              <input type="text" id="meta-keyword-entry" maxlength="40" class="form-control border-0 shadow-none" style="width: 220px; flex: 1;" placeholder="Type a keyword and press Enter">
            </div>
            <small class="form-text text-muted">Add up to 6 keywords by pressing Enter or separating them with commas.</small>
          </div>
        </div>
        <div class="form-group">
            <label>Des</label>
            <textarea class="form-control" id="product_des" name="product_des" >{{ old('product_des', $productsDetail->product_des ?? '') }}</textarea>
        </div>
        <div class="form-group">
          <label>Itinerary</label>
          <p>Choose an activity or destination for each stop and optionally add a note. Selected stops are added to the product categories.</p>
          <p id="itinerary-category-message" role="status"></p>
          @php
            $rows = collect(old('itinerary', $itineraryItems))->filter(fn ($row) => is_array($row))
              ->map(fn ($row) => [
                'reference' => is_string($row['reference'] ?? null) ? $row['reference'] : '',
                'note' => is_string($row['note'] ?? null) ? $row['note'] : '',
              ])->values()->all();
            if (empty($rows)) $rows = [['reference' => '', 'note' => '']];
            $knownReferences = $activities->map(fn ($activity) => 'activity:'.$activity->id)
              ->merge($destinasi->map(fn ($destination) => 'destination:'.$destination->id));
          @endphp
          <div id="itinerary-container">
            @foreach($rows as $index => $item)
              @include('admin.includes.product-itinerary-row', ['index' => $index, 'item' => $item])
            @endforeach
          </div>
          <template id="itinerary-template">
            @include('admin.includes.product-itinerary-row', ['index' => '__INDEX__', 'item' => ['reference' => '', 'note' => '']])
          </template>
          <button type="button" class="btn btn-sm btn-success" id="add-itinerary">+ Add Itinerary</button>
        </div>
        
        @push('js')
        <script>
          
        $(document).ready(function() {
          if ($('#itinerary-container').data('initialized')) return;
          $('#itinerary-container').data('initialized', true);
          var nextIndex = {{ count($rows) }};
          $('#add-itinerary').click(function() {
            var newItem = $('#itinerary-template').html().replace(/__INDEX__/g, nextIndex++);
            $('#itinerary-container').append(newItem);
            updateRemoveButtons();
          });

          function syncItineraryCategory(select) {
            var option = select.options[select.selectedIndex];
            var name = option.dataset.type === 'activity' ? 'parent_type[]' : 'destination[]';
            if (!option.dataset.code) return;
            $('input[type="checkbox"]').filter(function() {
              return this.name === name && this.value === option.dataset.code;
            }).prop('checked', true);
          }
          function addMetaKeywords(value) {
            value.split(/[,;\n]+/).forEach(function(keyword) {
              keyword = $.trim(keyword);
              if (!keyword) return;
              var exists = $('#meta-keyword-tags input[name="meta_keyword[]"]').toArray().some(function(input) {
                return input.value.toLowerCase() === keyword.toLowerCase();
              });
              if (exists) return;

              var tag = $('<span>', { class: 'badge badge-info d-inline-flex align-items-center' });
              tag.append(document.createTextNode(keyword + ' '));
              $('<button>', {
                type: 'button',
                class: 'remove-meta-keyword btn btn-link btn-sm p-0 ml-1 text-white',
                text: '×',
                'aria-label': 'Remove ' + keyword
              }).appendTo(tag);
              $('<input>', { type: 'hidden', name: 'meta_keyword[]', value: keyword }).appendTo(tag);
              $('#meta-keyword-tags').append(tag);
            });
          }
          $('#meta-keyword-entry').on('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ',') {
              event.preventDefault();
              addMetaKeywords(this.value);
              this.value = '';
            }
          }).on('blur', function() {
            addMetaKeywords(this.value);
            this.value = '';
          });
          $('#meta-keyword-picker').on('click', '.remove-meta-keyword', function() {
            $(this).closest('.badge').remove();
          });
          $('form').on('submit', function() {
            addMetaKeywords($('#meta-keyword-entry').val());
          });
          $(document).on('change', '.itinerary-reference', function() { syncItineraryCategory(this); });
          $('.itinerary-reference').each(function() { syncItineraryCategory(this); });
          $(document).on('change', 'input[name="parent_type[]"], input[name="destination[]"]', function() {
            if (this.checked) return;
            var checkbox = this;
            var referenced = $('.itinerary-reference').toArray().some(function(select) {
              var option = select.options[select.selectedIndex];
              var name = option.dataset.type === 'activity' ? 'parent_type[]' : 'destination[]';
              return name === checkbox.name && option.dataset.code === checkbox.value;
            });
            if (referenced) {
              this.checked = true;
              $('#itinerary-category-message').text('Remove the itinerary stop before unchecking its category.');
            }
          });
          
          $(document).on('click', '.remove-itinerary', function(e) {
            e.preventDefault();
            $(this).closest('.itinerary-item').remove();
            updateRemoveButtons();
          });
          
          function updateRemoveButtons() {
            if ($('.itinerary-item').length > 1) {
              $('.remove-itinerary').show();
            } else {
              $('.remove-itinerary').hide();
            }
          }
          updateRemoveButtons();
        });
        </script>
        @endpush
        <div class="row">
          <div class="form-group col-lg-6">
              <label>Included</label>
              <textarea class="form-control" id="product_included" name="product_included" >{{ old('product_included', $productsDetail->product_included ?? '') }}</textarea>
          </div>
          <div class="form-group col-lg-6">
              <label>Excluded</label>
              <textarea class="form-control" id="product_excluded" name="product_excluded" >{{ old('product_excluded', $productsDetail->product_excluded ?? '') }}</textarea>
          </div>
        </div>
        <div class="form-group">
            <label>Aktivity</label>
            <div class="row">
            @php $activitySelection = old('categories_present') ? old('parent_type', []) : explode(';', $productsDetail->parent_type ?? ''); @endphp
            @foreach($activities as $activity)
              <div class="col-md-6">
                <label class="checkbox-inline">
                  <input type="checkbox" name="parent_type[]" value="{{ $activity->code }}" @checked(in_array($activity->code, $activitySelection)) />
                  {{ $activity->name }} - {{ $activity->lang }}
                </label>
              </div>
            @endforeach
            </div>
        </div>
        <div class="form-group">
            <label>Destination  </label>
              <div class="row">

                @php $destinationSelection = old('categories_present') ? old('destination', []) : explode(';', $productsDetail->dst_type ?? ''); @endphp
                @foreach($destinasi as $desti)
                  <div class="col-md-6">
                    <label class="checkbox-inline">
                      <input type="checkbox" name="destination[]" value="{{ $desti->code }}" @checked(in_array($desti->code, $destinationSelection)) />
                      {{ $desti->name }} - {{ $desti->lang }}
                    </label>
                  </div>
                @endforeach
            </div>
        </div>
        <div class="form-group">
            <div class="needsclick dropzone" id="document-dropzone"></div>
        </div>
        <button type="submit" class="btn btn-fill btn-primary">Simpan</button>
      </form>

      </div>
    </div>
  </div>
  
</div>
          <!-- <textarea class="my-edit form-control" id="my-edit" name="wysiwyg-editor"></textarea> -->
          
  @push('js')
  <script src="//cdn.ckeditor.com/4.6.2/standard/ckeditor.js"></script>
  <script>
    var options = {
      filebrowserImageBrowseUrl: '/laravel-filemanager?type=Images',
      filebrowserImageUploadUrl: '/laravel-filemanager/upload?type=Images&_token=',
      filebrowserBrowseUrl: '/laravel-filemanager?type=Files',
      filebrowserUploadUrl: '/laravel-filemanager/upload?type=Files&_token='
    };
    CKEDITOR.replace('product_des', options);
    CKEDITOR.replace('product_included', options);
    CKEDITOR.replace('product_excluded', options);

    // Function to generate slug from name
    function generateSlug(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '') // Remove invalid chars
            .replace(/[\s_-]+/g, '-')    // Collapse whitespace and replace by -
            .replace(/^-+|-+$/g, '');    // Remove dashes from start and end
    }

    // Listen for changes on the name input to auto-generate slug
    $('#product_name').on('keyup', function() {
        @if(!isset($productsDetail))
        const name = $(this).val();
        const slug = generateSlug(name);
        $('#slug').val(slug);
        @endif
    });


    var uploadedDocumentMap = {}
    Dropzone.options.documentDropzone = {
      url: '{{ route('products.storeMedia') }}',
      maxFilesize: 10, // MB
      acceptedFiles: '.png, .jpg, .jpeg, .webp',
      addRemoveLinks: true,
      headers: {
        'X-CSRF-TOKEN': "{{ csrf_token() }}"
      },
      success: function (file, response) {
        console.log(file);
        $('form').append('<input type="hidden" name="document[]" value="' + response.name + '">')
        uploadedDocumentMap[file.name] = response.name
      },
      removedfile: function (file) {
        $.ajax({
            headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
            type:'POST',
            url:'/products/media/delete',
            data : {"filetodelete" : file.name},
            success : function (data) {
            }
        });
        // console.log(file);
        file.previewElement.remove()
        var name = ''
        
        if (typeof file.name !== 'undefined') {
          name = file.name
        } else {
          name = uploadedDocumentMap[file.name]
        }
        $('form').find('input[name="document[]"][value="' + name + '"]').remove()
      },
      init: function () {
        // console.log('onload dropzone');

          var filesa = @json(old('documents_present') ? old('document', []) : array_values(array_filter(explode(';', $productsDetail->product_foto ?? ''))));
          const files = filesa.map(function (name) { return { file_name: name }; });

          // console.log(files);
          for (var i in files) {
            var file = files[i]
            // console.log(this)
            // this.files.push(file);

            // this.options.addedfile.call(this, file)
            // file.previewElement.classList.add('dz-complete')

            // var principal = '@Model.Article.Image'; 
            let mockFile = { name: file.file_name, size: 12345, type: 'image/jpg', accepted: true };
            this.emit("addedfile", mockFile);
            this.emit("thumbnail", mockFile, "/assets/img/products/" + file.file_name)
            {
                $('[data-dz-thumbnail]').css('height', '120');
                $('[data-dz-thumbnail]').css('width', '120');
                $('[data-dz-thumbnail]').css('object-fit', 'cover');
            };              
            this.emit('complete', mockFile);
            this.files.push(mockFile);

            $('form').append('<input type="hidden" name="document[]" value="' + file.file_name + '">')
          }
      }
    }
    
  </script>
 
  @endpush
@endsection
