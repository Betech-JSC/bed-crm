<?php

namespace App\Http\Controllers;

use App\Models\ReferralCode;
use App\Models\ReferralConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReferralProgramController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $filters = $request->only('search', 'status');

        $codes = ReferralCode::where('account_id', $accountId)
            ->filter($filters)
            ->withCount('conversions')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'referrer_name' => $c->referrer_name,
                'referrer_email' => $c->referrer_email,
                'reward_type' => $c->reward_type,
                'reward_display' => $c->reward_display,
                'max_uses' => $c->max_uses,
                'uses_count' => $c->uses_count,
                'conversions_count' => $c->conversions_count,
                'status' => $c->status,
                'total_revenue' => $c->total_revenue,
                'expires_at' => $c->expires_at?->format('d/m/Y'),
            ]);

        $stats = [
            'total_codes' => ReferralCode::where('account_id', $accountId)->count(),
            'active_codes' => ReferralCode::where('account_id', $accountId)->where('status', 'active')->count(),
            'total_referrals' => ReferralCode::where('account_id', $accountId)->sum('uses_count'),
            'total_revenue' => ReferralConversion::whereHas('referralCode', fn ($q) => $q->where('account_id', $accountId))
                ->whereNotNull('deal_value')->sum('deal_value'),
            'total_commission' => ReferralConversion::whereHas('referralCode', fn ($q) => $q->where('account_id', $accountId))
                ->whereNotNull('commission_amount')->sum('commission_amount'),
        ];

        // Recent conversions
        $recentConversions = ReferralConversion::whereHas('referralCode', fn ($q) => $q->where('account_id', $accountId))
            ->with('referralCode:id,code,referrer_name')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'referred_name' => $c->referred_name,
                'referred_email' => $c->referred_email,
                'status' => $c->status,
                'deal_value' => $c->deal_value,
                'commission_amount' => $c->commission_amount,
                'code' => $c->referralCode?->code,
                'referrer_name' => $c->referralCode?->referrer_name,
                'created_at' => $c->created_at->format('d/m/Y'),
            ]);

        return Inertia::render('ReferralProgram/Index', compact('codes', 'stats', 'recentConversions', 'filters'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'referrer_name' => 'required|string|max:255',
            'referrer_email' => 'nullable|email',
            'code' => 'nullable|string|max:20|unique:referral_codes,code',
            'reward_type' => 'in:discount,credit,commission',
            'reward_value' => 'numeric|min:0',
            'reward_unit' => 'in:percent,fixed',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
        ]);

        ReferralCode::create([
            'account_id' => Auth::user()->account_id,
            ...$validated,
        ]);

        return redirect()->back()->with('success', 'Đã tạo mã giới thiệu!');
    }

    public function update(Request $request, ReferralCode $referralCode): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'referrer_name' => 'required|string|max:255',
            'referrer_email' => 'nullable|email',
            'reward_type' => 'in:discount,credit,commission',
            'reward_value' => 'numeric|min:0',
            'reward_unit' => 'in:percent,fixed',
            'max_uses' => 'nullable|integer|min:1',
            'status' => 'in:active,paused,expired',
            'expires_at' => 'nullable|date',
        ]);

        $referralCode->update($validated);
        return redirect()->back()->with('success', 'Đã cập nhật!');
    }

    public function destroy(ReferralCode $referralCode): \Illuminate\Http\RedirectResponse
    {
        $referralCode->delete();
        return redirect()->back()->with('success', 'Đã xóa.');
    }

    public function addConversion(Request $request, ReferralCode $referralCode): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'referred_name' => 'required|string|max:255',
            'referred_email' => 'nullable|email',
            'referred_phone' => 'nullable|string|max:20',
            'deal_value' => 'nullable|numeric|min:0',
        ]);

        $commission = null;
        if ($validated['deal_value'] && $referralCode->reward_type === 'commission') {
            $commission = $referralCode->reward_unit === 'percent'
                ? $validated['deal_value'] * ($referralCode->reward_value / 100)
                : $referralCode->reward_value;
        }

        $referralCode->conversions()->create([
            ...$validated,
            'commission_amount' => $commission,
            'status' => 'pending',
        ]);

        $referralCode->increment('uses_count');

        return redirect()->back()->with('success', 'Đã ghi nhận referral!');
    }
}
