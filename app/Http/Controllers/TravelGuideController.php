<?php

namespace App\Http\Controllers;

use App\Support\LocalizedRoutes;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TravelGuideController extends Controller
{
    public function adminIndex()
    {
        $guides = DB::table('travel_guides')->orderByDesc('updated_at')->paginate(25);

        return view('admin.pages.travel-guide', compact('guides'));
    }

    public function create()
    {
        $existingGuides = $this->existingGuides();

        return view('admin.pages.travel-guide_add', compact('existingGuides'));
    }

    public function edit(int $id)
    {
        $guide = DB::table('travel_guides')->where('id', $id)->first();
        abort_unless($guide, 404);
        $existingGuides = $this->existingGuides();

        return view('admin.pages.travel-guide_add', compact('guide', 'existingGuides'));
    }

    public function store(Request $request)
    {
        $id = $request->validate([
            'id' => ['nullable', 'integer', Rule::exists('travel_guides', 'id')],
        ])['id'] ?? null;
        $previous = $id ? DB::table('travel_guides')->where('id', $id)->first() : null;
        abort_if($id && !$previous, 404);
        $slug = $request->input('slug');
        if ((!is_string($slug) || trim($slug) === '') && is_string($request->input('title'))) {
            $slug = $request->input('title');
        }
        if (is_string($slug)) {
            $request->merge(['slug' => Str::slug($slug)]);
        }

        $data = $request->validate([
            'code' => [$id ? 'required' : 'nullable', 'string', 'max:100', ...(!$id ? [Rule::exists('travel_guides', 'code')] : [])],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('travel_guides', 'slug')->where('lang', $request->input('lang'))->ignore($id),
            ],
            'lang' => ['required', 'string', Rule::in(LocalizedRoutes::LOCALES)],
            'content' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['required', 'string', 'max:255', 'distinct', 'regex:/^[A-Za-z0-9-]+\.(?:jpg|jpeg|png|webp)$/i'],
        ]);
        $photos = array_values($data['photos'] ?? []);
        foreach ($photos as $index => $photo) {
            if (!is_file(public_path('assets/img/travel-guide/'.$photo))) {
                throw ValidationException::withMessages(["photos.$index" => 'The selected photo is no longer available. Upload it again.']);
            }
        }

        $code = $previous->code ?? ($data['code'] ?? null);
        if ($code && (!$previous || $data['lang'] !== $previous->lang)) {
            $duplicate = DB::table('travel_guides')->where('code', $code)->where('lang', $data['lang'])
                ->when($id, fn ($query) => $query->where('id', '!=', $id))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['code' => 'This article already has a translation in the selected language.']);
            }
        }

        if (!$code) {
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $code = 'GUIDE-'.Str::upper(Str::random(16));
                if (!DB::table('travel_guides')->where('code', $code)->exists()) {
                    break;
                }
            }
            if (DB::table('travel_guides')->where('code', $code)->exists()) {
                throw ValidationException::withMessages(['code' => 'Could not generate an article code. Please try again.']);
            }
        }

        $attributes = [
            'code' => $code,
            'title' => $data['title'],
            'slug' => Str::slug($data['slug']),
            'lang' => $data['lang'],
            'content' => $data['content'],
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'meta_keywords' => $data['meta_keywords'] ?? '',
            'photos' => $photos ? implode(';', $photos).';' : '',
            'updated_at' => now(),
        ];
        $previousPhotos = $previous
            ? array_values(array_filter(explode(';', $previous->photos ?? ''), 'strlen'))
            : [];

        try {
            if ($previous) {
                DB::table('travel_guides')->where('id', $id)->update($attributes);
            } else {
                DB::table('travel_guides')->insert($attributes + ['created_at' => now()]);
            }
        } catch (UniqueConstraintViolationException $exception) {
            $codeConflict = DB::table('travel_guides')->where('code', $code)->where('lang', $data['lang'])
                ->when($id, fn ($query) => $query->where('id', '!=', $id))->exists();
            if ($codeConflict) {
                throw ValidationException::withMessages(['code' => 'This article already has a translation in the selected language.']);
            }
            throw ValidationException::withMessages(['slug' => 'An article already uses this slug in the selected language.']);
        }

        $this->removeUnusedPhotos(array_diff($previousPhotos, $photos));

        return redirect()->route('pages.travel-guide')->with('success', 'Article saved successfully.');
    }

    public function storePhoto(Request $request)
    {
        $file = $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ])['file'];
        $path = public_path('assets/img/travel-guide');
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException('Unable to create the travel guide photo directory.');
        }
        $name = Str::uuid().'.'.$file->extension();
        $file->move($path, $name);

        return response()->json(['name' => $name]);
    }

    public function deletePhoto(Request $request)
    {
        $name = $request->validate([
            'filetodelete' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9-]+\.(?:jpg|jpeg|png|webp)$/i'],
        ])['filetodelete'];
        $referenced = DB::table('travel_guides')->get(['photos'])->contains(function ($guide) use ($name) {
            return in_array($name, array_filter(explode(';', $guide->photos ?? ''), 'strlen'), true);
        });
        abort_if($referenced, 409, 'This photo is attached to a saved article.');

        $path = public_path('assets/img/travel-guide/'.$name);
        abort_unless(is_file($path), 404);
        if (!unlink($path)) {
            throw new \RuntimeException('Unable to delete the travel guide photo.');
        }

        return response()->json(['deleted' => true]);
    }

    public function destroy(int $id)
    {
        $guide = DB::table('travel_guides')->where('id', $id)->first();
        abort_unless($guide, 404);
        DB::table('travel_guides')->where('id', $id)->delete();
        $this->removeUnusedPhotos(array_filter(explode(';', $guide->photos ?? ''), 'strlen'));

        return redirect()->route('pages.travel-guide')->with('success', 'Article deleted successfully.');
    }

    public function index()
    {
        $guides = DB::table('travel_guides')->where('lang', app()->getLocale())
            ->orderByDesc('created_at')->paginate(12);

        return view('pages.travel-guides', compact('guides'));
    }

    public function show(string $slug)
    {
        $guide = DB::table('travel_guides')->where('slug', $slug)
            ->where('lang', app()->getLocale())->first();
        abort_unless($guide, 404);
        $translations = DB::table('travel_guides')->where('code', $guide->code)->get(['lang', 'slug']);

        return view('pages.travel-guide-detail', compact('guide', 'translations'));
    }

    private function existingGuides()
    {
        return DB::table('travel_guides')->orderBy('title')->get(['code', 'title', 'lang'])
            ->filter(fn ($guide) => is_string($guide->code) && $guide->code !== '')
            ->groupBy('code');
    }

    private function removeUnusedPhotos(array $photos): void
    {
        foreach ($photos as $photo) {
            $stillReferenced = DB::table('travel_guides')->get(['photos'])->contains(function ($guide) use ($photo) {
                return in_array($photo, array_filter(explode(';', $guide->photos ?? ''), 'strlen'), true);
            });
            $path = public_path('assets/img/travel-guide/'.$photo);
            if (!$stillReferenced && is_file($path) && !unlink($path)) {
                throw new \RuntimeException('Unable to remove an unused travel guide photo.');
            }
        }
    }
}
