<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class QrCodeController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $filters = $request->only('search', 'qr_type');

        $codes = QrCode::where('account_id', $accountId)
            ->filter($filters)
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($q) => [
                'id' => $q->id,
                'name' => $q->name,
                'target_url' => $q->target_url,
                'short_code' => $q->short_code,
                'tracking_url' => $q->tracking_url,
                'qr_type' => $q->qr_type,
                'design' => $q->design,
                'scans_count' => $q->scans_count,
                'unique_scans' => $q->unique_scans,
                'created_at' => $q->created_at->format('d/m/Y'),
            ]);

        $stats = [
            'total' => QrCode::where('account_id', $accountId)->count(),
            'total_scans' => QrCode::where('account_id', $accountId)->sum('scans_count'),
            'unique_scans' => QrCode::where('account_id', $accountId)->sum('unique_scans'),
        ];

        return Inertia::render('QrCodes/Index', [
            'codes' => $codes,
            'stats' => $stats,
            'qrTypes' => QrCode::getQrTypes(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_url' => 'required|url',
            'qr_type' => 'nullable|in:url,vcard,wifi,text',
            'design' => 'nullable|array',
            'content_data' => 'nullable|array',
        ]);

        $user = Auth::user();
        QrCode::create([
            'account_id' => $user->account_id,
            'created_by' => $user->id,
            ...$validated,
        ]);

        return redirect()->back()->with('success', 'Đã tạo QR Code!');
    }

    public function destroy(QrCode $qrCode): \Illuminate\Http\RedirectResponse
    {
        $qrCode->delete();
        return redirect()->back()->with('success', 'Đã xóa.');
    }

    /**
     * QR redirect — track scan and redirect.
     */
    public function redirect(string $code)
    {
        $qr = QrCode::where('short_code', $code)->firstOrFail();
        $qr->increment('scans_count');
        return redirect()->to($qr->target_url);
    }
}
