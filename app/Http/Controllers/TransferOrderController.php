<?php

namespace App\Http\Controllers;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TransferOrderController extends Controller
{
    public function show(Request $request)
    {
        $token = Str::random(64);
        $request->session()->put('transfer_tokens.'.$token, true);

        return response()->view('pages.transfer', compact('token'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:64', 'alpha_num'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:100'],
            'mobile' => ['required', 'string', 'max:50'],
            'booking_type' => ['required', Rule::in(['arrival', 'departure'])],
            'destination' => ['required', 'string', 'max:2000'],
            'pickup_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'flight_number' => ['nullable', 'string', 'max:50'],
            'arrival_time' => ['nullable', 'date_format:H:i'],
            'passengers' => ['required', 'integer', 'min:1', 'max:99999'],
        ]);
        abort_unless($request->session()->has('transfer_tokens.'.$data['token']), 403);
        $token = hash('sha256', $data['token']);
        unset($data['token']);

        try {
            DB::table('transfer_orders')->insert(array_merge($data, [
                'request_token' => $token,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        } catch (UniqueConstraintViolationException $exception) {
            if (!DB::table('transfer_orders')->where('request_token', $token)->exists()) {
                throw $exception;
            }
        }

        return redirect()->route('transfer.confirmation')->with('transfer_received', true);
    }

    public function confirmation(Request $request)
    {
        abort_unless($request->session()->has('transfer_received'), 404);

        return response()->view('pages.transfer-confirmation')
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function index()
    {
        $orders = DB::table('transfer_orders')->orderByDesc('id')->paginate(20);

        return response()->view('admin.pages.transfer-orders', compact('orders'))
            ->header('Cache-Control', 'private, no-store');
    }
}
