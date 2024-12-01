<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Tentukan tanggal jatuh tempo
        $expirationDate = Carbon::create(2024, 12, 4);

        // Ambil tanggal saat ini
        $currentDate = Carbon::now();

        // Periksa apakah tanggal saat ini lebih besar dari tanggal jatuh tempo
        if ($currentDate > $expirationDate) {
            // Jika ya, arahkan pengguna diarahkakn ke halaman 'expired'
            return response()->view('500', [], 500);
        }

        // Jika tanggal saat ini masih sebelum tanggal jatuh tempo, lanjutkan permintaan ke rute berikutnya
        return $next($request);
    }
}
