<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStudyMedia extends Model
{
    protected $table = 'case_study_media';

    protected $fillable = [
        'case_study_id', 'type', 'title', 'url', 'thumbnail_url',
        'mime_type', 'file_size', 'caption', 'section', 'sort_order',
    ];

    public const TYPES = [
        'image' => 'Image',
        'video' => 'Video',
        'document' => 'Document',
        'link' => 'Link',
    ];

    public const SECTIONS = [
        'gallery' => 'Gallery',
        'before_after' => 'Before & After',
        'process' => 'Process',
        'result' => 'Result',
    ];

    public function caseStudy(): BelongsTo
    {
        return $this->belongsTo(CaseStudy::class);
    }

    public function getFormattedFileSize(): string
    {
        if (!$this->file_size) return '';
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 1) . ' ' . $units[$i];
    }
}
