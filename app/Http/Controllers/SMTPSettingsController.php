<?php

namespace App\Http\Controllers;

use App\Models\SMTPSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SMTPSettingsController extends Controller
{
    public function index(): InertiaResponse
    {
        $account = Auth::user()->account;
        $smtpSetting = $account->smtpSetting;

        return Inertia::render('SMTPSettings/Index', [
            'smtpSetting' => $smtpSetting ? [
                'id' => $smtpSetting->id,
                'host' => $smtpSetting->host,
                'port' => $smtpSetting->port,
                'username' => $smtpSetting->username,
                'password' => $smtpSetting->password ? '***' : null,
                'encryption' => $smtpSetting->encryption,
                'from_address' => $smtpSetting->from_address,
                'from_name' => $smtpSetting->from_name,
                'is_active' => $smtpSetting->is_active,
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'encryption' => ['nullable', 'string', 'in:tls,ssl'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $account = Auth::user()->account;

        $smtpSetting = $account->smtpSetting;

        if ($smtpSetting) {
            // Don't update password if it's masked
            if ($validated['password'] === '***') {
                unset($validated['password']);
            }
            $smtpSetting->update($validated);
        } else {
            $smtpSetting = SMTPSetting::create([
                'account_id' => $account->id,
                ...$validated,
            ]);
        }

        return redirect()->route('smtp-settings.index')->with('success', 'SMTP settings saved successfully.');
    }

    public function test(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $account = Auth::user()->account;
        $smtpSetting = $account->smtpSetting;

        if (!$smtpSetting || !$smtpSetting->is_active) {
            return redirect()->back()->with('error', 'SMTP settings not configured or not active.');
        }

        try {
            // Temporarily set mail config
            config([
                'mail.mailers.smtp' => $smtpSetting->toMailConfig(),
                'mail.from.address' => $smtpSetting->from_address,
                'mail.from.name' => $smtpSetting->from_name ?? config('app.name'),
            ]);

            \Illuminate\Support\Facades\Mail::raw('This is a test email from your CRM SMTP settings.', function ($message) use ($validated) {
                $message->to($validated['test_email'])
                    ->subject('Test Email from CRM');
            });

            return redirect()->back()->with('success', 'Test email sent successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }
}
