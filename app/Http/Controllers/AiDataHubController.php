<?php

namespace App\Http\Controllers;

use App\Models\AiKnowledgeBase;
use App\Models\AiKnowledgeDocument;
use App\Models\AiTrainingSet;
use App\Models\AiDataSyncLog;
use App\Models\AiAgent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiDataHubController extends Controller
{
    /**
     * Dashboard — Knowledge Bases, documents overview, sync logs.
     */
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;

        // Knowledge bases with document counts
        $knowledgeBases = AiKnowledgeBase::where('account_id', $accountId)
            ->withCount('documents')
            ->orderBy('name')
            ->get()
            ->map(fn ($kb) => [
                'id' => $kb->id,
                'name' => $kb->name,
                'description' => $kb->description,
                'type' => $kb->type,
                'type_meta' => AiKnowledgeBase::TYPES[$kb->type] ?? AiKnowledgeBase::TYPES['general'],
                'status' => $kb->status,
                'status_meta' => AiKnowledgeBase::STATUSES[$kb->status] ?? null,
                'stats' => $kb->stats ?? ['documents' => 0, 'chunks' => 0],
                'documents_count' => $kb->documents_count,
                'created_at' => $kb->created_at->format('d/m/Y'),
            ]);

        // Training sets
        $trainingSets = AiTrainingSet::where('account_id', $accountId)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($ts) => [
                'id' => $ts->id,
                'name' => $ts->name,
                'agent_type' => $ts->agent_type,
                'description' => $ts->description,
                'format' => $ts->format,
                'format_meta' => AiTrainingSet::FORMATS[$ts->format] ?? null,
                'item_count' => $ts->item_count,
                'quality_score' => $ts->quality_score,
                'status' => $ts->status,
                'updated_at' => $ts->updated_at->format('d/m/Y'),
            ]);

        // Recent sync logs
        $syncLogs = AiDataSyncLog::where('account_id', $accountId)
            ->with('knowledgeBase:id,name')
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'kb_name' => $log->knowledgeBase->name ?? '—',
                'source_type' => $log->source_type,
                'source_ref' => $log->source_ref,
                'action' => $log->action,
                'records_processed' => $log->records_processed,
                'records_failed' => $log->records_failed,
                'duration' => $log->duration_format,
                'status' => $log->status,
                'error_message' => $log->error_message,
                'created_at' => $log->created_at->diffForHumans(),
            ]);

        // Aggregate stats
        $totalDocs = AiKnowledgeDocument::whereIn('knowledge_base_id',
            AiKnowledgeBase::where('account_id', $accountId)->pluck('id')
        )->count();
        $totalAgents = AiAgent::where('account_id', $accountId)->count();
        $totalConversations = AiAgent::where('account_id', $accountId)->sum('total_conversations');

        return Inertia::render('AiDataHub/Index', [
            'knowledgeBases' => $knowledgeBases,
            'trainingSets' => $trainingSets,
            'syncLogs' => $syncLogs,
            'stats' => [
                'knowledge_bases' => $knowledgeBases->count(),
                'documents' => $totalDocs,
                'agents' => $totalAgents,
                'conversations' => $totalConversations,
            ],
            'kbTypes' => AiKnowledgeBase::TYPES,
            'syncSources' => AiKnowledgeDocument::CRM_SYNC_SOURCES,
            'trainingFormats' => AiTrainingSet::FORMATS,
        ]);
    }

    /**
     * Create a knowledge base.
     */
    public function storeKnowledgeBase(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:' . implode(',', array_keys(AiKnowledgeBase::TYPES)),
        ]);

        $validated['account_id'] = auth()->user()->account_id;
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'building';
        $validated['stats'] = ['documents' => 0, 'chunks' => 0, 'indexed' => 0];

        AiKnowledgeBase::create($validated);

        return back()->with('success', 'Đã tạo Knowledge Base.');
    }

    /**
     * Add document to knowledge base.
     */
    public function storeDocument(Request $request)
    {
        $validated = $request->validate([
            'knowledge_base_id' => 'required|exists:ai_knowledge_bases,id',
            'title' => 'required|string|max:255',
            'source_type' => 'required|string|in:upload,crm_sync,url,text,api',
            'source_ref' => 'nullable|string|max:500',
            'content' => 'nullable|string',
        ]);

        $validated['content_hash'] = hash('sha256', $validated['content'] ?? '');
        $validated['status'] = 'indexed';
        $validated['last_synced_at'] = now();

        AiKnowledgeDocument::create($validated);

        // Update KB stats
        $kb = AiKnowledgeBase::find($validated['knowledge_base_id']);
        $kb->refreshStats();
        if ($kb->status === 'building') {
            $kb->update(['status' => 'ready']);
        }

        // Log sync
        AiDataSyncLog::create([
            'account_id' => auth()->user()->account_id,
            'knowledge_base_id' => $validated['knowledge_base_id'],
            'source_type' => $validated['source_type'],
            'source_ref' => $validated['source_ref'] ?? $validated['title'],
            'action' => 'index',
            'records_processed' => 1,
            'records_failed' => 0,
            'duration_ms' => rand(100, 2000),
            'status' => 'completed',
        ]);

        return back()->with('success', 'Đã thêm tài liệu.');
    }

    /**
     * Simulate CRM data sync to knowledge base.
     */
    public function syncCrmData(Request $request)
    {
        $validated = $request->validate([
            'knowledge_base_id' => 'required|exists:ai_knowledge_bases,id',
            'source' => 'required|string',
        ]);

        $accountId = auth()->user()->account_id;
        $kb = AiKnowledgeBase::findOrFail($validated['knowledge_base_id']);
        $source = $validated['source'];
        $sourceMeta = AiKnowledgeDocument::CRM_SYNC_SOURCES[$source] ?? null;

        if (!$sourceMeta) {
            return back()->with('error', 'Nguồn dữ liệu không hợp lệ.');
        }

        // Simulate syncing records from CRM table
        $recordCount = rand(10, 200);
        $startTime = microtime(true);

        // Create sync document
        AiKnowledgeDocument::create([
            'knowledge_base_id' => $kb->id,
            'title' => "CRM Sync: {$sourceMeta['label']} (" . now()->format('d/m/Y H:i') . ")",
            'source_type' => 'crm_sync',
            'source_ref' => $sourceMeta['table'],
            'content' => "Synced {$recordCount} records from {$sourceMeta['table']} table. Data includes structured fields and text content for AI training.",
            'content_hash' => hash('sha256', $source . now()->timestamp),
            'metadata' => [
                'table' => $sourceMeta['table'],
                'records_synced' => $recordCount,
                'sync_type' => 'full',
            ],
            'status' => 'indexed',
            'last_synced_at' => now(),
        ]);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000) + rand(500, 3000);

        // Log
        AiDataSyncLog::create([
            'account_id' => $accountId,
            'knowledge_base_id' => $kb->id,
            'source_type' => 'crm_sync',
            'source_ref' => $sourceMeta['table'],
            'action' => 'sync',
            'records_processed' => $recordCount,
            'records_failed' => rand(0, 3),
            'duration_ms' => $durationMs,
            'status' => 'completed',
        ]);

        $kb->refreshStats();

        return back()->with('success', "Đã đồng bộ {$recordCount} records từ {$sourceMeta['label']}.");
    }

    /**
     * Store training set.
     */
    public function storeTrainingSet(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'agent_type' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
            'format' => 'required|string|in:' . implode(',', array_keys(AiTrainingSet::FORMATS)),
            'data' => 'nullable|array',
        ]);

        $validated['account_id'] = auth()->user()->account_id;
        $validated['created_by'] = auth()->id();
        $validated['item_count'] = count($validated['data'] ?? []);
        $validated['status'] = 'draft';

        AiTrainingSet::create($validated);

        return back()->with('success', 'Đã tạo Training Set.');
    }

    /**
     * Delete knowledge base.
     */
    public function destroyKnowledgeBase(AiKnowledgeBase $aiKnowledgeBase)
    {
        $aiKnowledgeBase->delete();
        return back()->with('success', 'Đã xóa Knowledge Base.');
    }

    /**
     * Delete document.
     */
    public function destroyDocument(AiKnowledgeDocument $aiKnowledgeDocument)
    {
        $kbId = $aiKnowledgeDocument->knowledge_base_id;
        $aiKnowledgeDocument->delete();

        $kb = AiKnowledgeBase::find($kbId);
        if ($kb) $kb->refreshStats();

        return back()->with('success', 'Đã xóa tài liệu.');
    }
}
