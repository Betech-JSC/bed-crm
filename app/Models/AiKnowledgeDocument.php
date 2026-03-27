<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiKnowledgeDocument extends Model
{
    protected $fillable = [
        'knowledge_base_id', 'title', 'source_type', 'source_ref',
        'content', 'content_hash', 'chunks', 'metadata', 'status',
        'last_synced_at', 'error_message',
    ];

    protected $casts = [
        'chunks' => 'array',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public const SOURCE_TYPES = [
        'upload' => ['label' => 'Upload', 'icon' => 'pi pi-upload'],
        'crm_sync' => ['label' => 'CRM Sync', 'icon' => 'pi pi-sync'],
        'url' => ['label' => 'URL', 'icon' => 'pi pi-globe'],
        'text' => ['label' => 'Text', 'icon' => 'pi pi-pencil'],
        'api' => ['label' => 'API', 'icon' => 'pi pi-code'],
    ];

    public const CRM_SYNC_SOURCES = [
        'leads' => ['label' => 'Leads', 'table' => 'leads', 'icon' => 'pi pi-bullseye'],
        'deals' => ['label' => 'Deals', 'table' => 'deals', 'icon' => 'pi pi-briefcase'],
        'contacts' => ['label' => 'Contacts', 'table' => 'contacts', 'icon' => 'pi pi-id-card'],
        'products' => ['label' => 'Products', 'table' => 'products', 'icon' => 'pi pi-box'],
        'tickets' => ['label' => 'Tickets', 'table' => 'tickets', 'icon' => 'pi pi-ticket'],
        'wiki' => ['label' => 'Wiki', 'table' => 'wiki_pages', 'icon' => 'pi pi-book'],
        'content_items' => ['label' => 'Content', 'table' => 'content_items', 'icon' => 'pi pi-pencil'],
        'email_templates' => ['label' => 'Email Templates', 'table' => 'email_templates', 'icon' => 'pi pi-envelope'],
        'crm_guides' => ['label' => 'CRM Guides', 'table' => 'crm_guides', 'icon' => 'pi pi-question-circle'],
        'case_studies' => ['label' => 'Case Studies', 'table' => 'case_studies', 'icon' => 'pi pi-trophy'],
        'seo_keywords' => ['label' => 'SEO Keywords', 'table' => 'seo_keywords', 'icon' => 'pi pi-search'],
    ];

    public function knowledgeBase(): BelongsTo { return $this->belongsTo(AiKnowledgeBase::class, 'knowledge_base_id'); }

    public function getWordCountAttribute(): int
    {
        return str_word_count(strip_tags($this->content ?? ''));
    }

    public function computeHash(): string
    {
        return hash('sha256', $this->content ?? '');
    }
}
