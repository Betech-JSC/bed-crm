<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => function () use ($request) {
                return [
                    'user' => $request->user() ? [
                        'id' => $request->user()->id,
                        'first_name' => $request->user()->first_name,
                        'last_name' => $request->user()->last_name,
                        'email' => $request->user()->email,
                        'owner' => $request->user()->owner,
                        'account' => [
                            'id' => $request->user()->account->id,
                            'name' => $request->user()->account->name,
                            'logo' => $request->user()->account->logo ? \Illuminate\Support\Facades\Storage::url($request->user()->account->logo) : null,
                        ],
                    ] : null,
                ];
            },
            'flash' => function () use ($request) {
                return [
                    'success' => $request->session()->get('success'),
                    'error' => $request->session()->get('error'),
                ];
            },
            'locale' => function () {
                return App::getLocale();
            },
            'translations' => function () {
                return [
                    'common' => trans('common'),
                ];
            },
            'notifications' => function () use ($request) {
                if (!$request->user()) return ['unread_count' => 0, 'items' => []];
                $userId = $request->user()->id;
                return [
                    'unread_count' => \App\Models\Notification::forUser($userId)->unread()->count(),
                    'items' => \App\Models\Notification::forUser($userId)
                        ->unread()
                        ->latest()
                        ->limit(10)
                        ->get()
                        ->map(fn ($n) => [
                            'id' => $n->id,
                            'title' => $n->title,
                            'body' => $n->body,
                            'icon' => $n->icon,
                            'severity' => $n->severity,
                            'link' => $n->link,
                            'event_type' => $n->event_type,
                            'read_at' => $n->read_at,
                            'created_at' => $n->created_at->diffForHumans(),
                        ]),
                ];
            },
        ]);
    }
}
