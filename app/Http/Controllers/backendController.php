<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\UniqueConstraintViolationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Support\ProductItinerary;
use App\Support\LocalizedRoutes;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Validator;

class backendController extends Controller
{
    //
    public function storeMedia(Request $request)
    {
        $path = public_path('assets/img/rooms/'); //storage_path('tmp/uploads');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $file = $request->file('file');
        $name = uniqid() . '_' . trim($file->getClientOriginalName());
        $file->move($path, $name);
        return response()->json([
            'name'          => $name,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }
    public function deleteMedia(Request $request)
    {
        $toDelete= $request->filetodelete ;
        unlink(public_path('assets/img/rooms/'.$toDelete));
    }

    public function store(Request $request)
    {
        try{
            $exception = DB::transaction(function() use ($request){ 
            
                $data = $request->all();
            
                $gmbr = "";
                $foto = $data['document'];
                foreach($foto as $ft){
                    $gmbr = $gmbr.$ft.";" ;
                }
                $fasi = implode(';', $data['facility']);
                $project = DB::table('bookings')->upsert([
                    'id' => $data['id'],
                    'code' => $data['code'],
                    'real_name' => $data['real_name'],
                    'real_address' => $data['real_address'],
                    'title' => $data['title'],
                    'slug' => $data['slug'],
                    'desc' => $data['desc'],
                    'price' => $data['price'],
                    'facility' => ';'.$fasi,
                    'lang' => $data['lang'],
                    'alotment' => $data['allotment'],
                    'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
                    'updated_at' => \Carbon\Carbon::now()->toDateTimeString()
                ], 'id');

                DB::table('room_fotos')->upsert([
                    'code' => $data['code'],
                    'foto' => $gmbr,
                    'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
                    'updated_at' => \Carbon\Carbon::now()->toDateTimeString()
                ], 'code');
                
                DB::table('room_nomors')->where('unit_code', $data['code'])->delete();
                for ($i = 0; $i < $data['allotment']; $i++) {
                    DB::table('room_nomors')->insert([
                        'room_code' => $i+1 ,
                        'room_no' => $i+1 ,
                        'unit_code' => $data['code'],
                        'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
                        'updated_at' => \Carbon\Carbon::now()->toDateTimeString()
                    ]);
                }
                // echo $gmbr ;
                // print_r($data['document']);
                // foreach ($request->input('document', []) as $file) {
                //     \File::move(storage_path('tmp/uploads/'.$file), public_path('assets/img/rooms/'.$file));
                //     // echo $file ;
                // }
                DB::commit();
            });
            if(is_null($exception)) {
                // return response()->json([
                //     'success' => true,
                //     'message' => 'Post Berhasil Diupdate!',
                // ], 200);
                return redirect()->route('pages.rooms');
            } else {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Post Gagal Diupdate!',
                ], 500);
                // return redirect()->route('pages.room_add');
            }
        } catch (\Exception $e) {
            DB::rollback();
            // something went wrong
            return response()->json([
             'success' => false,
             'message' => 'exception'.$e,
         ], 400);
            // return redirect()->route('pages.room_add');
        }

        // return redirect()->route('pages.rooms');
    }

    public function edit($room_code){
        $roomDetail = DB::table('bookings')->where('bookings.id', $room_code)
                    ->join('room_fotos', 'room_fotos.code', 'bookings.code')
                    ->select('bookings.*', 'room_fotos.foto')
                    ->first();
        $fasilitas = DB::table('facilities')->get();

        return view('admin.pages.room_add', compact('roomDetail', 'fasilitas'));
    }

    public function storeMediaTour(Request $request)
    {
        $path = public_path('assets/img/tour/'); //storage_path('tmp/uploads');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $file = $request->file('file');
        $name = uniqid() . '_' . trim($file->getClientOriginalName());
        $file->move($path, $name);
        return response()->json([
            'name'          => $name,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }
    public function deleteMediaTour(Request $request)
    {
        $toDelete= $request->filetodelete ;
        unlink(public_path('assets/img/tour/'.$toDelete));
    }

    public function storeTour(Request $request)
    {
        $request->validate([
            'price_usd' => ['nullable', 'numeric', 'gt:0', 'max:99999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);
        try{
            $exception = DB::transaction(function() use ($request){ 
            
                $data = $request->all();
                $data['document'] = $data['document'] ?? [];
                $data['parent_type'] = $data['parent_type'] ?? [];
                $data['destination'] = $data['destination'] ?? [];
                $data['activity'] = $data['activity'] ?? [];

                $gmbr = "";
                $foto = $data['document'];
                foreach($foto as $ft){
                    $gmbr = $gmbr.$ft.";" ;
                }
                $desti = implode(';', $data['destination']);
                $acti = implode(';', $data['activity']);
                $project = DB::table('tour_packages')->upsert([
                    'id' => $data['id'],
                    'code' => $data['code'],
                    'type' => $data['type'],
                    'tour_name' => $data['tour_name'],
                    'slug' => $data['slug'],
                    'area_tour' => $data['area_tour'],
                    'itinerary' => $data['itinerary'],
                    'price' => $data['price'],
                    'price_usd' => $data['price_usd'] ?? null,
                    'destination' => $desti.';',
                    'activity' => $acti.';',
                    'lang' => $data['lang'],
                    'payment' => $data['payment'],
                    'note' => $data['note'],
                    'pickup' => $data['pickup'],
                    'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
                    'updated_at' => \Carbon\Carbon::now()->toDateTimeString()
                ], 'id');
                DB::table('tour_fotos')->upsert([
                    'code' => $data['code'],
                    'foto' => $gmbr,
                    'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
                    'updated_at' => \Carbon\Carbon::now()->toDateTimeString()
                ], 'code');

                DB::commit();
            });
            if(is_null($exception)) {
                // return response()->json([
                //     'success' => true,
                //     'message' => 'Post Berhasil Diupdate!',
                // ], 200);
                return redirect()->route('pages.tour');
            } else {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Post Gagal Diupdate!',
                ], 500);
                // return redirect()->route('pages.room_add');
            }
        } catch (\Exception $e) {
            DB::rollback();
            // something went wrong
            return response()->json([
             'success' => false,
             'message' => 'exception'.$e,
         ], 400);
            // return redirect()->route('pages.room_add');
        }

        // return redirect()->route('pages.rooms');
    }

    public function editTour($room_code){
        $tourDetail = DB::table('tour_packages')->where('tour_packages.id', $room_code)
                    ->join('tour_fotos', 'tour_fotos.code', 'tour_packages.code')
                    ->select('tour_packages.*', 'tour_fotos.foto')
                    ->first();
        // return redirect()->route('pages.room_add');
        $destinasi = DB::table('destinations')->get();
        $activities = DB::table('activities')->get();
        return view('admin.pages.tour_add', compact('tourDetail', 'destinasi', 'activities'));

    }

    public function storeMediaDestinasi(Request $request)
    {
        $file = $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
        ])['file'];
        $path = public_path('assets/img/destinasi');
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException('Unable to create the destination photo directory.');
        }
        $name = Str::uuid().'.'.$file->extension();
        $file->move($path, $name);
        return response()->json([
            'name'          => $name,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }
    public function deleteMediaDestinasi(Request $request)
    {
        $name = $request->validate(['filetodelete' => ['required', 'string', 'max:255']])['filetodelete'];
        abort_if(str_contains($name, '/') || str_contains($name, '\\') || str_contains($name, ';'), 422);
        $referenced = DB::table('destination_fotos')->get(['foto'])->contains(function ($row) use ($name) {
            return in_array($name, array_filter(explode(';', $row->foto ?? ''), 'strlen'), true);
        });
        abort_if($referenced, 409, 'This photo is attached to a saved destination.');
        $path = public_path('assets/img/destinasi/'.$name);
        abort_unless(is_file($path), 404);
        if (!unlink($path)) {
            throw new \RuntimeException('Unable to delete the destination photo.');
        }
    }

    public function storeDestinasi(Request $request)
    {
        $id = $request->validate([
            'id' => ['nullable', 'integer', Rule::exists('destinations', 'id')],
        ])['id'] ?? null;
        $previous = $id ? DB::table('destinations')->where('id', $id)->first() : null;
        abort_if($id && !$previous, 404);

        $slug = $request->input('slug');
        if ((!is_string($slug) || trim($slug) === '') && is_string($request->input('name'))) {
            $slug = $request->input('name');
        }
        if (is_string($slug)) {
            $request->merge(['slug' => Str::slug($slug)]);
        }

        $types = ['ubud', 'south', 'north', 'east', 'kintamani', 'west'];
        $languages = array_filter([...LocalizedRoutes::LOCALES, $previous->lang ?? null], fn ($value) => is_string($value) && $value !== '');
        $data = $request->validate([
            'code' => [$id ? 'required' : 'nullable', 'string', 'max:100', ...(!$id ? [Rule::exists('destinations', 'code')] : [])],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('destinations', 'slug')->where('lang', $request->input('lang'))->ignore($id),
            ],
            'lang' => ['required', 'string', Rule::in($languages)],
            'type' => ['required', 'string', Rule::in(array_filter([...$types, $previous->type ?? null]))],
            'deskripsi' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_keyword' => ['nullable', 'string', 'max:500'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'document' => ['nullable', 'array'],
            'document.*' => ['required', 'string', 'max:255', 'distinct'],
        ]);

        $code = $previous->code ?? ($data['code'] ?? null);
        if ($code && (!$previous || $data['lang'] !== $previous->lang)) {
            $duplicate = DB::table('destinations')->where('code', $code)->where('lang', $data['lang'])
                ->when($id, fn ($query) => $query->where('id', '!=', $id))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['code' => 'This destination already has a translation in the selected language.']);
            }
        }

        if (!$code) {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $code = 'DST-'.Str::upper(Str::random(16));
                if (!DB::table('destinations')->where('code', $code)->exists()
                    && !DB::table('destination_fotos')->where('code', $code)->exists()) {
                    break;
                }
            }
            if (DB::table('destinations')->where('code', $code)->exists()
                || DB::table('destination_fotos')->where('code', $code)->exists()) {
                throw ValidationException::withMessages(['code' => 'Could not generate a destination code. Please try again.']);
            }
        }

        $photos = array_values($data['document'] ?? []);
        foreach ($photos as $index => $photo) {
            if (str_contains($photo, '/') || str_contains($photo, '\\') || str_contains($photo, ';')
                || !is_file(public_path('assets/img/destinasi/'.$photo))) {
                throw ValidationException::withMessages(["document.$index" => 'The selected photo is invalid or unavailable. Upload it again.']);
            }
        }

        $photoRow = DB::table('destination_fotos')->where('code', $code)->first();
        $previousPhotos = $photoRow
            ? array_values(array_filter(explode(';', $photoRow->foto ?? ''), 'strlen'))
            : [];
        $preserveSharedGallery = !$id && $photoRow;
        if ($preserveSharedGallery) {
            $photos = array_values(array_unique(array_merge($previousPhotos, $photos)));
        }
        $shouldUpdateGallery = !$preserveSharedGallery || $photos !== $previousPhotos;

        $now = now();
        $attributes = [
            'code' => $code,
            'deskripsi' => $data['deskripsi'] ?? '',
            'name' => $data['name'],
            'slug' => $data['slug'],
            'type' => $data['type'],
            'lang' => $data['lang'],
            'meta_title' => $data['meta_title'] ?? '',
            'meta_keyword' => $data['meta_keyword'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'updated_at' => $now,
        ];

        try {
            DB::transaction(function () use ($id, $attributes, $now, $code, $photos, $shouldUpdateGallery) {
                if ($id) {
                    DB::table('destinations')->where('id', $id)->update($attributes);
                } else {
                    DB::table('destinations')->insert($attributes + ['created_at' => $now]);
                }
                if ($shouldUpdateGallery) {
                    DB::table('destination_fotos')->upsert([
                        'code' => $code,
                        'foto' => $photos ? implode(';', $photos).';' : '',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], ['code'], ['foto', 'updated_at']);
                }
            });
        } catch (UniqueConstraintViolationException $exception) {
            $codeConflict = DB::table('destinations')->where('code', $code)->where('lang', $data['lang'])
                ->when($id, fn ($query) => $query->where('id', '!=', $id))->exists();
            if ($codeConflict) {
                throw ValidationException::withMessages(['code' => 'This destination already has a translation in the selected language.']);
            }
            throw ValidationException::withMessages(['slug' => 'A destination with this slug already exists in the selected language.']);
        }

        $this->removeUnusedDestinationPhotos(array_diff($previousPhotos, $photos));

        return redirect()->route('pages.destinasi')->with('success', 'Destination saved successfully.');
    }

    public function editDestinasi(Request $request){
        $destinasi_code = $request->id ;
        $type = $request->type ;

        $destinasiDetail = DB::table('destinations')->where('destinations.id', $destinasi_code)
                    ->leftJoin('destination_fotos', 'destination_fotos.code', 'destinations.code')
                    ->select('destinations.*', 'destination_fotos.foto')
                    ->first();
        abort_unless($destinasiDetail, 404);
        $existingDestinations = DB::table('destinations')->orderBy('name')->get(['code', 'name', 'lang'])
            ->filter(fn ($destination) => is_string($destination->code) && $destination->code !== '')
            ->groupBy('code');
        if($type == 'copy'){
            $data = 'copy';
        }else{
            $data = 'edit';
        }
        return view('admin.pages.destinasi_add', compact('destinasiDetail', 'existingDestinations') + ['type' => $data]);

    }

    private function removeUnusedDestinationPhotos(array $photos): void
    {
        foreach ($photos as $photo) {
            $stillReferenced = DB::table('destination_fotos')->get(['foto'])->contains(function ($row) use ($photo) {
                return in_array($photo, array_filter(explode(';', $row->foto ?? ''), 'strlen'), true);
            });
            $path = public_path('assets/img/destinasi/'.$photo);
            if (!$stillReferenced && is_file($path) && !unlink($path)) {
                throw new \RuntimeException('Unable to remove an unused destination photo.');
            }
        }
    }

    public function storeMediaActivity(Request $request)
    {
        $path = public_path('assets/img/activity/'); //storage_path('tmp/uploads');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $file = $request->file('file');
        $name = uniqid() . '_' . trim($file->getClientOriginalName());
        $file->move($path, $name);
        return response()->json([
            'name'          => $name,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }
    public function deleteMediaActivity(Request $request)
    {
        $toDelete= $request->filetodelete ;
        unlink(public_path('assets/img/activity/'.$toDelete));
    }

    public function storeActivity(Request $request)
    {
        $id = $request->validate(['id' => ['nullable', 'integer', Rule::exists('activities', 'id')]])['id'] ?? null;
        $existing = $id ? DB::table('activities')->where('id', $id)->first() : null;
        $areaIds = DB::table('travel_area')->pluck('id')->map(fn ($area) => (string) $area)->all();
        $legacyAreas = array_filter(explode(';', $existing->area ?? ''), 'strlen');
        $types = array_filter(['land', 'air', 'water', $existing->type ?? null], fn ($value) => is_string($value) && $value !== '');
        $languages = array_filter([...LocalizedRoutes::LOCALES, $existing->lang ?? null], fn ($value) => is_string($value) && $value !== '');

        $data = $request->validate([
            'code' => [$id ? 'required' : 'nullable', 'string', 'max:20', ...(!$id ? [Rule::exists('activities', 'code')] : [])],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:65535'],
            'lang' => ['required', 'string', Rule::in($languages)],
            'type' => ['required', 'string', Rule::in($types)],
            'area' => ['required', 'array', 'min:1', function ($attribute, $value, $fail) {
                if (is_array($value) && strlen(implode(';', array_filter($value, 'is_string')).';') > 100) {
                    $fail('The selected areas exceed the allowed length.');
                }
            }],
            'area.*' => ['required', 'string', 'distinct', Rule::in(array_merge($areaIds, $legacyAreas))],
            'deskripsi' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_keyword' => ['nullable', 'string', 'max:500'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'document' => ['nullable', 'array'],
            'document.*' => ['required', 'string', 'max:255'],
        ]);

        $code = $id ? $data['code'] : ($data['code'] ?? null);
        if ($code && (!$existing || $code !== $existing->code || $data['lang'] !== $existing->lang)) {
            $duplicate = DB::table('activities')->where('code', $code)->where('lang', $data['lang'])
                ->when($id, fn ($query) => $query->where('id', '!=', $id))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['code' => 'This activity already has a translation in the selected language.']);
            }
        }

        $activity = [
            'name' => $data['name'], 'slug' => $data['slug'], 'lang' => $data['lang'],
            'type' => $data['type'], 'area' => implode(';', $data['area']).';',
            'deskripsi' => $data['deskripsi'] ?? '', 'updated_at' => now(),
            'meta_title' => $data['meta_title'] ?? '',
            'meta_keyword' => $data['meta_keyword'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
        ];
        if (!$id && !$code) {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $code = 'ACT-'.Str::upper(Str::random(16));
                if (DB::table('activities')->where('code', $code)->exists()
                    || DB::table('activity_fotos')->where('code', $code)->exists()) {
                    continue;
                }
                try {
                    DB::transaction(function () use ($data, $code, $activity) {
                        $documents = $data['document'] ?? [];
                        DB::table('activity_fotos')->insert([
                            'code' => $code, 'foto' => $documents ? implode(';', $documents).';' : '',
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                        DB::table('activities')->insert(['code' => $code] + $activity + ['created_at' => now()]);
                    });
                    return redirect()->route('pages.activity', 'all');
                } catch (UniqueConstraintViolationException $exception) {
                    if (!DB::table('activity_fotos')->where('code', $code)->exists()) {
                        throw $exception;
                    }
                }
            }
            throw ValidationException::withMessages(['code' => 'Could not generate an activity code. Please try again.']);
        }

        DB::transaction(function () use ($data, $id, $code, $activity) {
            $activity = ['code' => $code] + $activity;
            if ($id) {
                DB::table('activities')->where('id', $id)->update($activity);
            } else {
                DB::table('activities')->insert($activity + ['created_at' => now()]);
            }
            $documents = $data['document'] ?? [];
            if ($id || $documents || !DB::table('activity_fotos')->where('code', $code)->exists()) {
                DB::table('activity_fotos')->upsert([
                    'code' => $code,
                    'foto' => $documents ? implode(';', $documents).';' : '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], ['code'], ['foto', 'updated_at']);
            }
        });

        return redirect()->route('pages.activity', 'all');
    }

    public function editActivity($code){
        // $tourDetail = DB::table('tour_packages')->where('code', $room_code)->first();
        // return redirect()->route('pages.room_add');
        $activityDetail = DB::table('activities')->where('activities.id', $code)
                    ->leftJoin('activity_fotos', 'activity_fotos.code', 'activities.code')
                    ->select('activities.*', 'activity_fotos.foto')
                    ->first();
        abort_unless($activityDetail, 404);
        $travelAreas = DB::table('travel_area')->orderBy('id')->get(['id', 'name']);
        return view('admin.pages.activity_add', compact('activityDetail', 'travelAreas'));

    }

    public function storeMediaProducts(Request $request)
    {
        $path = public_path('assets/img/products/'); //storage_path('tmp/uploads');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $file = $request->file('file');
        $name = uniqid() . '_' . trim($file->getClientOriginalName());
        $file->move($path, $name);
        return response()->json([
            'name'          => $name,
            'original_name' => $file->getClientOriginalName(),
        ]);
    }
    public function deleteMediaProducts(Request $request)
    {
        $toDelete= $request->filetodelete ;
        unlink(public_path('assets/img/products/'.$toDelete));
    }

    public function storeProducts(Request $request)
    {
        $id = $request->validate(['id' => ['nullable', 'integer', Rule::exists('products', 'id')]])['id'] ?? null;
        $previous = $id ? DB::table('products')->where('id', $id)->first() : null;
        $languages = array_filter([...LocalizedRoutes::LOCALES, $previous->lang ?? null], fn ($value) => is_string($value) && $value !== '');
        if (is_string($request->input('meta_keyword'))) {
            $request->merge(['meta_keyword' => preg_split('/[,;]+/', $request->input('meta_keyword'), -1, PREG_SPLIT_NO_EMPTY)]);
        }
        $data = $request->validate([
            'id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'code' => [$id ? 'required' : 'nullable', 'string', 'max:100', ...(!$id ? [Rule::exists('products', 'code')] : [])],
            'product_name' => ['required', 'string', 'max:255'],
            'lang' => ['required', 'string', Rule::in($languages)],
            'price' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'product_des' => ['nullable', 'string'],
            'product_included' => ['nullable', 'string'],
            'product_excluded' => ['nullable', 'string'],
            'tour_duration' => ['nullable', 'string', 'max:100'],
            'recommendation_badge' => ['nullable', Rule::in(['popular', 'best_value', 'top_pick'])],
            'meta_keyword' => ['nullable', 'array', 'max:6'],
            'meta_keyword.*' => ['string', 'max:40'],
            'meta_description' => ['nullable', 'string'],
            'document' => ['nullable', 'array'],
            'document.*' => ['string'],
            'parent_type' => ['nullable', 'array'],
            'parent_type.*' => ['string'],
            'destination' => ['nullable', 'array'],
            'destination.*' => ['string'],
            'itinerary' => ['nullable', 'array'],
            'itinerary.*' => ['array'],
            'itinerary.*.reference' => ['nullable', 'string', 'regex:/^(activity|destination):[1-9][0-9]*$/'],
            'itinerary.*.note' => ['nullable', 'string'],
        ]);
        $code = $data['code'] ?? null;
        if ($code && (!$previous || $code !== $previous->code || $data['lang'] !== $previous->lang)) {
            $duplicate = DB::table('products')->where('code', $code)->where('lang', $data['lang'])
                ->when($id, fn ($query) => $query->where('id', '!=', $id))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['code' => 'This product already has a translation in the selected language.']);
            }
        }
        if (!$id && !$code) {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $code = 'PRD-'.Str::upper(Str::random(16));
                if (!DB::table('products')->where('code', $code)->exists()) {
                    break;
                }
            }
            if (DB::table('products')->where('code', $code)->exists()) {
                throw ValidationException::withMessages(['code' => 'Could not generate a product code. Please try again.']);
            }
        }

        $items = $data['itinerary'] ?? [];
        $ids = ['activity' => [], 'destination' => []];
        foreach ($items as $index => $item) {
            $reference = $item['reference'] ?? '';
            if ($reference !== '') {
                [$type, $id] = explode(':', $reference, 2);
                if (filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                    throw ValidationException::withMessages(["itinerary.$index.reference" => 'The selected itinerary item is invalid.']);
                }
                $ids[$type][] = (int) $id;
            }
        }
        $places = [
            'activity' => DB::table('activities')->whereIn('id', $ids['activity'])->get()->keyBy('id'),
            'destination' => DB::table('destinations')->whereIn('id', $ids['destination'])->get()->keyBy('id'),
        ];
        $activityCodes = $data['parent_type'] ?? [];
        $destinationCodes = $data['destination'] ?? [];
        $legacyNotes = $previous
            ? collect(ProductItinerary::parse($previous->itinerary))->where('legacy_html', true)->pluck('note')->all()
            : [];
        $savedItems = [];
        foreach ($items as $index => $item) {
            $reference = $item['reference'] ?? '';
            $note = $item['note'] ?? '';
            if ($reference === '' && trim($note) === '') {
                continue;
            }
            if ($reference !== '') {
                [$type, $id] = explode(':', $reference, 2);
                $place = $places[$type]->get((int) $id);
                if (!$place) {
                    throw ValidationException::withMessages(["itinerary.$index.reference" => 'The selected activity or destination no longer exists.']);
                }
                if ($type === 'activity') {
                    $activityCodes[] = $place->code;
                } else {
                    $destinationCodes[] = $place->code;
                }
            }
            $savedItems[] = [
                'reference' => $reference,
                'note' => $note,
                'legacy_html' => in_array($note, $legacyNotes, true),
            ];
        }

        $attributes = [
            'code' => $code,
            'product_des' => $data['product_des'] ?? '',
            'itinerary' => json_encode(['format' => 'product_itinerary_v1', 'items' => $savedItems], JSON_THROW_ON_ERROR),
            'product_included' => $data['product_included'] ?? '',
            'product_excluded' => $data['product_excluded'] ?? '',
            'product_name' => $data['product_name'],
            'slug' => Str::slug($data['product_name']),
            'parent_type' => implode(';', array_unique($activityCodes)).';',
            'dst_type' => implode(';', array_unique($destinationCodes)).';',
            'lang' => $data['lang'],
            'meta_keyword' => implode(', ', array_unique(array_map('trim', $data['meta_keyword'] ?? []))),
            'meta_description' => $data['meta_description'] ?? '',
            'tour_duration' => $data['tour_duration'] ?? '',
            'recommendation_badge' => $data['recommendation_badge'] ?? '',
            'product_foto' => implode('', array_map(fn ($file) => $file.';', $data['document'] ?? [])),
            'price' => $data['price'],
            'updated_at' => now(),
        ];

        if ($previous) {
            DB::table('products')->where('id', $previous->id)->update($attributes);
        } else {
            $attributes['created_at'] = now();
            DB::table('products')->insert($attributes);
        }
        return redirect()->route('pages.products', 'all');
    }

    public function editProducts($code){
        // $tourDetail = DB::table('tour_packages')->where('code', $room_code)->first();
        // return redirect()->route('pages.room_add');
        $productsDetail = DB::table('products')->where('id', $code)->first();
        abort_unless($productsDetail, 404);

        $activities = DB::table('activities')->get();
        $destinasi = DB::table('destinations')->get();
        $itineraryItems = ProductItinerary::parse($productsDetail->itinerary);
        return view('admin.pages.products_add', compact('productsDetail', 'activities', 'destinasi', 'itineraryItems'));

    }

    public function guestOrder(Request $request){
        $email = $request->user()->email;
        $data = DB::table('reservations')->whereRaw('LOWER(guest_email) = ?', [mb_strtolower($email)])->orderby('id', 'DESC')->get();
         return response()->json([
            'success' => true,
            'message' => 'your list reservasi!',
            'data' => $data
        ], 200)->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer');

    }

    public function guestLogin(Request $request){
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);
        $user= DB::table('guests')->where('email', $request->email)
                ->join('countries', 'guests.nationality', 'countries.country_code')    
                ->first();
        
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response([
                    'success'   => false,
                    'message' => ['These credentials do not match our records.']
                ], 404);
            }
               
            $response = [
                'success'   => true,
                'user'      => $user,
            ];
        
        return response($response, 201);

    }


}
