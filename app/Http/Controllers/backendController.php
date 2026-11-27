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
        $path = public_path('assets/img/destinasi/'); //storage_path('tmp/uploads');
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
    public function deleteMediaDestinasi(Request $request)
    {
        $toDelete= $request->filetodelete ;
        unlink(public_path('assets/img/destinasi/'.$toDelete));
    }

    public function storeDestinasi(Request $request)
    {
        try{
            $exception = DB::transaction(function() use ($request){ 
            
                $data = $request->all();
                $data['id'] = $data['id'] ?? '';
                $data['code'] = $data['code'] ?? '';
                $data['deskripsi'] = $data['deskripsi'] ?? '';
                $data['name'] = $data['name'] ?? '';
                $data['slug'] = $data['slug'] ?? '';
                $data['type'] = $data['type'] ?? '';
                $data['lang'] = $data['lang'] ?? '';
                $data['meta_keyword'] = $data['meta_keyword'] ?? '';
                $data['meta_description'] = $data['meta_description'] ?? '';
                $data['document'] = $data['document'] ?? [];
                $data['parent_type'] = $data['parent_type'] ?? [];
                $data['destination'] = $data['destination'] ?? [];
                $data['price'] = $data['price'] ?? 0;
            
                $gmbr = "";
                $foto = $data['document'];
                foreach($foto as $ft){
                    $gmbr = $gmbr.$ft.";" ;
                }
                // $desti = implode(';', $data['destination']);
                $project = DB::table('destinations')->upsert([
                    'id' => $data['id'],
                    'code' => $data['code'],
                    'deskripsi' => $data['deskripsi'],
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'type' => $data['type'],
                    'lang' => $data['lang'],
                    'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
                    'updated_at' => \Carbon\Carbon::now()->toDateTimeString()
                ], 'id');
                DB::table('destination_fotos')->upsert([
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
                return redirect()->route('pages.destinasi');
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

    public function editDestinasi(Request $request){
        $destinasi_code = $request->id ;
        $type = $request->type ;

        $destinasiDetail = DB::table('destinations')->where('destinations.id', $destinasi_code)
                    ->join('destination_fotos', 'destination_fotos.code', 'destinations.code')
                    ->select('destinations.*', 'destination_fotos.foto')
                    ->first();
        if($type == 'copy'){
            $data = 'copy';
        }else{
            $data = 'edit';
        }
        return view('admin.pages.destinasi_add', ['destinasiDetail' => $destinasiDetail, 'type' => $data]);

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
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:products,id'],
            'code' => ['required', 'string', 'max:100'],
            'product_name' => ['required', 'string', 'max:255'],
            'lang' => ['required', 'string', 'max:50'],
            'price' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'product_des' => ['nullable', 'string'],
            'product_included' => ['nullable', 'string'],
            'product_excluded' => ['nullable', 'string'],
            'meta_keyword' => ['nullable', 'string'],
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
        $previous = isset($data['id']) ? DB::table('products')->where('id', $data['id'])->first() : null;
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
            'code' => $data['code'],
            'product_des' => $data['product_des'] ?? '',
            'itinerary' => json_encode(['format' => 'product_itinerary_v1', 'items' => $savedItems], JSON_THROW_ON_ERROR),
            'product_included' => $data['product_included'] ?? '',
            'product_excluded' => $data['product_excluded'] ?? '',
            'product_name' => $data['product_name'],
            'slug' => Str::slug($data['product_name']),
            'parent_type' => implode(';', array_unique($activityCodes)).';',
            'dst_type' => implode(';', array_unique($destinationCodes)).';',
            'lang' => $data['lang'],
            'meta_keyword' => $data['meta_keyword'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
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
