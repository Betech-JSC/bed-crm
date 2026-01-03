<?php

namespace App\Http\Controllers;

use App\Models\ChatWidget;
use App\Models\ChatWidgetDocument;
use App\Services\RAGService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ChatWidgetDocumentsController extends Controller
{
    public function __construct(
        private RAGService $ragService
    ) {
    }

    public function index(ChatWidget $chatWidget): InertiaResponse
    {
        // Ensure widget belongs to user's account
        if ($chatWidget->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $documents = $chatWidget->documents()
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($doc) {
                // Group by base name (without part number)
                return preg_replace('/\s+\(Part\s+\d+\)$/', '', $doc->name);
            })
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'id' => $first->id,
                    'name' => preg_replace('/\s+\(Part\s+\d+\)$/', '', $first->name),
                    'file_type' => $first->file_type,
                    'chunks_count' => $group->count(),
                    'total_tokens' => $group->sum('token_count'),
                    'is_active' => $first->is_active,
                    'created_at' => $first->created_at->format('Y-m-d H:i'),
                ];
            })
            ->values();

        return Inertia::render('ChatWidgetDocuments/Index', [
            'widget' => [
                'id' => $chatWidget->id,
                'name' => $chatWidget->name,
            ],
            'documents' => $documents,
        ]);
    }

    public function store(Request $request, ChatWidget $chatWidget): RedirectResponse
    {
        // Ensure widget belongs to user's account
        if ($chatWidget->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required_without:file', 'string', 'max:50000'],
            'file' => ['nullable', 'file', 'mimes:txt,pdf,doc,docx', 'max:10240'], // 10MB max
        ]);

        $content = $validated['content'] ?? '';
        $filePath = null;
        $fileType = null;

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileType = $file->getClientOriginalExtension();
            $filePath = $file->store('chat-widgets/documents', 'public');

            // Extract text from file
            $content = $this->extractTextFromFile($file, $filePath);
            
            if (empty($content)) {
                return Redirect::back()->with('error', 'Could not extract text from file. Please ensure the file contains readable text.');
            }
        }

        if (empty($content)) {
            return Redirect::back()->with('error', 'Content is required.');
        }

        try {
            // Process document with RAG
            $this->ragService->processDocument(
                $chatWidget,
                $validated['name'],
                $content,
                $filePath,
                $fileType
            );

            return Redirect::back()->with('success', 'Document uploaded and processed successfully. The AI will now use this information to answer questions.');
        } catch (\Exception $e) {
            \Log::error('Failed to process document', [
                'error' => $e->getMessage(),
                'widget_id' => $chatWidget->id,
            ]);

            return Redirect::back()->with('error', 'Failed to process document: ' . $e->getMessage());
        }
    }

    public function destroy(ChatWidget $chatWidget, ChatWidgetDocument $chatWidgetDocument): RedirectResponse
    {
        // Ensure widget and document belong to user's account
        if ($chatWidget->account_id !== Auth::user()->account_id || 
            $chatWidgetDocument->widget_id !== $chatWidget->id) {
            abort(403);
        }

        // Delete file if exists
        if ($chatWidgetDocument->file_path && Storage::disk('public')->exists($chatWidgetDocument->file_path)) {
            Storage::disk('public')->delete($chatWidgetDocument->file_path);
        }

        // Delete all chunks of this document
        $baseName = preg_replace('/\s+\(Part\s+\d+\)$/', '', $chatWidgetDocument->name);
        $chatWidget->documents()
            ->where('name', 'like', $baseName . '%')
            ->delete();

        return Redirect::back()->with('success', 'Document deleted successfully.');
    }

    public function toggle(Request $request, ChatWidget $chatWidget, int $documentId): RedirectResponse
    {
        // Ensure widget belongs to user's account
        if ($chatWidget->account_id !== Auth::user()->account_id) {
            abort(403);
        }

        $document = ChatWidgetDocument::where('id', $documentId)
            ->where('widget_id', $chatWidget->id)
            ->firstOrFail();

        // Toggle all chunks
        $baseName = preg_replace('/\s+\(Part\s+\d+\)$/', '', $document->name);
        $newStatus = !$document->is_active;
        
        $chatWidget->documents()
            ->where('name', 'like', $baseName . '%')
            ->update(['is_active' => $newStatus]);

        return Redirect::back()->with('success', 'Document status updated.');
    }

    /**
     * Extract text from uploaded file
     */
    private function extractTextFromFile($file, string $filePath): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        switch ($extension) {
            case 'txt':
                return file_get_contents($file->getRealPath());

            case 'pdf':
                // For PDF, we'll need a library. For now, return empty and suggest text extraction
                // In production, use: smalot/pdfparser or similar
                return $this->extractTextFromPDF($file->getRealPath());

            case 'doc':
            case 'docx':
                // For Word documents, would need phpword library
                // For now, return empty
                return '';

            default:
                return '';
        }
    }

    /**
     * Extract text from PDF (basic implementation)
     * Note: For production, install and use smalot/pdfparser
     */
    private function extractTextFromPDF(string $filePath): string
    {
        // Basic implementation - in production, use a proper PDF parser
        // For now, return empty and require text input
        // You can install: composer require smalot/pdfparser
        return '';
    }
}
