<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Request as RequestFacade;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FilesController extends Controller
{
    public function __construct(
        private FileStorageService $storageService
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $query = Auth::user()->account->files()
            ->with(['uploader'])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Files/Index', [
            'filters' => $request->only('category', 'type', 'search'),
            'files' => $query->paginate(20)->withQueryString()->through(fn ($file) => [
                'id' => $file->id,
                'name' => $file->name,
                'filename' => $file->filename,
                'size' => $file->human_readable_size,
                'category' => $file->category,
                'type' => $file->type,
                'mime_type' => $file->mime_type,
                'extension' => $file->extension,
                'icon' => $file->icon,
                'download_count' => $file->download_count,
                'uploader' => $file->uploader ? [
                    'id' => $file->uploader->id,
                    'name' => $file->uploader->first_name . ' ' . $file->uploader->last_name,
                ] : null,
                'created_at' => $file->created_at->format('Y-m-d H:i'),
                'url' => $file->url,
            ]),
            'categories' => File::getCategories(),
            'types' => File::getTypes(),
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Files/Create', [
            'categories' => File::getCategories(),
            'types' => File::getTypes(),
            'accessLevels' => File::getAccessLevels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file'],
            'type' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_public' => ['boolean'],
            'access_level' => ['nullable', 'string'],
            'related_type' => ['nullable', 'string'],
            'related_id' => ['nullable', 'integer'],
        ]);

        try {
            $file = $this->storageService->upload(
                $request->file('file'),
                Auth::user(),
                [
                    'type' => $validated['type'] ?? File::TYPE_ATTACHMENT,
                    'description' => $validated['description'] ?? null,
                    'is_public' => $validated['is_public'] ?? false,
                    'access_level' => $validated['access_level'] ?? File::ACCESS_PRIVATE,
                    'related_type' => $validated['related_type'] ?? null,
                    'related_id' => $validated['related_id'] ?? null,
                ]
            );

            return Redirect::route('files.show', $file->id)->with('success', 'File uploaded successfully.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', 'Upload failed: ' . $e->getMessage())->withInput();
        }
    }

    public function show(File $file): InertiaResponse
    {
        // Check access
        if (!$this->canAccess($file)) {
            abort(403, 'Access denied');
        }

        return Inertia::render('Files/Show', [
            'file' => [
                'id' => $file->id,
                'name' => $file->name,
                'filename' => $file->filename,
                'size' => $file->human_readable_size,
                'category' => $file->category,
                'type' => $file->type,
                'mime_type' => $file->mime_type,
                'extension' => $file->extension,
                'icon' => $file->icon,
                'description' => $file->description,
                'metadata' => $file->metadata,
                'download_count' => $file->download_count,
                'last_downloaded_at' => $file->last_downloaded_at?->format('Y-m-d H:i'),
                'is_public' => $file->is_public,
                'access_level' => $file->access_level,
                'is_safe' => $file->is_safe,
                'uploader' => $file->uploader ? [
                    'id' => $file->uploader->id,
                    'name' => $file->uploader->first_name . ' ' . $file->uploader->last_name,
                ] : null,
                'created_at' => $file->created_at->format('Y-m-d H:i'),
                'url' => $file->url,
            ],
        ]);
    }

    public function edit(File $file): InertiaResponse
    {
        if (!$this->canAccess($file)) {
            abort(403, 'Access denied');
        }

        return Inertia::render('Files/Edit', [
            'file' => [
                'id' => $file->id,
                'name' => $file->name,
                'description' => $file->description,
                'type' => $file->type,
                'is_public' => $file->is_public,
                'access_level' => $file->access_level,
            ],
            'types' => File::getTypes(),
            'accessLevels' => File::getAccessLevels(),
        ]);
    }

    public function update(File $file): RedirectResponse
    {
        if (!$this->canAccess($file)) {
            abort(403, 'Access denied');
        }

        $validated = RequestFacade::validate([
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'is_public' => ['boolean'],
            'access_level' => ['nullable', 'string'],
        ]);

        $file->update($validated);

        return Redirect::back()->with('success', 'File updated.');
    }

    public function destroy(File $file): RedirectResponse
    {
        if (!$this->canAccess($file)) {
            abort(403, 'Access denied');
        }

        $this->storageService->delete($file);

        return Redirect::route('files')->with('success', 'File deleted.');
    }

    public function download(File $file): Response
    {
        if (!$this->canAccess($file)) {
            abort(403, 'Access denied');
        }

        if (!$file->exists()) {
            abort(404, 'File not found');
        }

        // Increment download count
        $file->incrementDownload();

        // Return file download
        return Storage::disk($file->disk)->download(
            $file->path,
            $file->name,
            [
                'Content-Type' => $file->mime_type,
            ]
        );
    }

    public function preview(File $file): Response
    {
        if (!$this->canAccess($file)) {
            abort(403, 'Access denied');
        }

        if (!$file->exists()) {
            abort(404, 'File not found');
        }

        // Only preview images and PDFs
        if (!in_array($file->category, [File::CATEGORY_IMAGE]) && $file->mime_type !== 'application/pdf') {
            abort(400, 'File type not previewable');
        }

        $content = Storage::disk($file->disk)->get($file->path);

        return response($content, 200, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'inline; filename="' . $file->name . '"',
        ]);
    }

    /**
     * Check if user can access file.
     */
    private function canAccess(File $file): bool
    {
        $user = Auth::user();

        // Owner or account admin
        if ($file->account_id === $user->account_id) {
            return true;
        }

        // Public files
        if ($file->is_public && $file->access_level === File::ACCESS_PUBLIC) {
            return true;
        }

        return false;
    }
}
