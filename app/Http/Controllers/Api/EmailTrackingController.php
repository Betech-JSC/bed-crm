<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    public function __construct(
        private EmailService $emailService
    ) {
    }

    /**
     * Track email open
     */
    public function trackOpen(Request $request, string $messageId): Response
    {
        $this->emailService->trackOpen(
            messageId: $messageId,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        // Return 1x1 transparent pixel
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        
        return response($pixel, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Track email click and redirect
     */
    public function trackClick(Request $request, string $messageId): \Illuminate\Http\RedirectResponse
    {
        $url = $request->query('url');
        
        if (!$url) {
            abort(404);
        }

        $this->emailService->trackClick(
            messageId: $messageId,
            url: $url,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect($url);
    }

    /**
     * Unsubscribe from emails
     */
    public function unsubscribe(Request $request, string $token): \Illuminate\View\View
    {
        // Decode token to get email send ID
        // In production, use proper encryption
        $emailSendId = base64_decode($token);
        $emailSend = \App\Models\EmailSend::find($emailSendId);

        if (!$emailSend) {
            abort(404);
        }

        // Mark as unsubscribed
        $listContact = \App\Models\EmailListContact::where('email', $emailSend->email)
            ->whereNull('unsubscribed_at')
            ->first();

        if ($listContact) {
            $listContact->unsubscribed_at = now();
            $listContact->unsubscribe_reason = 'user_requested';
            $listContact->save();

            $listContact->emailList->updateContactsCount();
        }

        return view('email-unsubscribe', [
            'email' => $emailSend->email,
        ]);
    }
}
