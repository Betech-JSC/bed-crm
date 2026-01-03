<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORY_DOCUMENT = 'document';
    public const CATEGORY_IMAGE = 'image';
    public const CATEGORY_VIDEO = 'video';
    public const CATEGORY_AUDIO = 'audio';
    public const CATEGORY_ARCHIVE = 'archive';
    public const CATEGORY_OTHER = 'other';

    public const TYPE_PROPOSAL = 'proposal';
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_AVATAR = 'avatar';
    public const TYPE_ATTACHMENT = 'attachment';
    public const TYPE_LOGO = 'logo';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_OTHER = 'other';

    public const ACCESS_PRIVATE = 'private';
    public const ACCESS_ACCOUNT = 'account';
    public const ACCESS_PUBLIC = 'public';

    protected $fillable = [
        'account_id',
        'uploaded_by',
        'name',
        'filename',
        'path',
        'disk',
        'mime_type',
        'extension',
        'size',
        'category',
        'type',
        'related_type',
        'related_id',
        'description',
        'metadata',
        'is_public',
        'access_level',
        'download_count',
        'last_downloaded_at',
        'checksum',
        'is_virus_scanned',
        'is_safe',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_public' => 'boolean',
        'is_virus_scanned' => 'boolean',
        'is_safe' => 'boolean',
        'last_downloaded_at' => 'datetime',
        'size' => 'integer',
        'download_count' => 'integer',
    ];

    protected $appends = [
        'url',
        'human_readable_size',
        'icon',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get file URL for download/preview.
     */
    public function getUrlAttribute(): string
    {
        if ($this->is_public && $this->access_level === self::ACCESS_PUBLIC) {
            return Storage::disk($this->disk)->url($this->path);
        }

        return route('files.download', $this->id);
    }

    /**
     * Get human readable file size.
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get icon based on file type.
     */
    public function getIconAttribute(): string
    {
        if ($this->category === self::CATEGORY_IMAGE) {
            return 'pi-image';
        }
        
        if ($this->category === self::CATEGORY_VIDEO) {
            return 'pi-video';
        }
        
        if ($this->category === self::CATEGORY_AUDIO) {
            return 'pi-volume-up';
        }
        
        $extension = strtolower($this->extension);
        
        $iconMap = [
            'pdf' => 'pi-file-pdf',
            'doc' => 'pi-file-word',
            'docx' => 'pi-file-word',
            'xls' => 'pi-file-excel',
            'xlsx' => 'pi-file-excel',
            'ppt' => 'pi-file-powerpoint',
            'pptx' => 'pi-file-powerpoint',
            'zip' => 'pi-file-archive',
            'rar' => 'pi-file-archive',
            'txt' => 'pi-file',
            'csv' => 'pi-file',
        ];
        
        return $iconMap[$extension] ?? 'pi-file';
    }

    /**
     * Check if file exists in storage.
     */
    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /**
     * Get file content.
     */
    public function getContent(): string
    {
        return Storage::disk($this->disk)->get($this->path);
    }

    /**
     * Delete file from storage.
     */
    public function deleteFromStorage(): bool
    {
        if ($this->exists()) {
            return Storage::disk($this->disk)->delete($this->path);
        }
        
        return false;
    }

    /**
     * Increment download count.
     */
    public function incrementDownload(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    /**
     * Scope for account files.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope for category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for accessible files.
     */
    public function scopeAccessible($query, ?User $user = null)
    {
        if (!$user) {
            return $query->where('is_public', true)
                ->where('access_level', self::ACCESS_PUBLIC);
        }

        return $query->where(function ($q) use ($user) {
            $q->where('account_id', $user->account_id)
                ->orWhere(function ($subQ) {
                    $subQ->where('is_public', true)
                        ->whereIn('access_level', [self::ACCESS_ACCOUNT, self::ACCESS_PUBLIC]);
                });
        });
    }

    /**
     * Get available categories.
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_DOCUMENT => 'Document',
            self::CATEGORY_IMAGE => 'Image',
            self::CATEGORY_VIDEO => 'Video',
            self::CATEGORY_AUDIO => 'Audio',
            self::CATEGORY_ARCHIVE => 'Archive',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    /**
     * Get available types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_PROPOSAL => 'Proposal',
            self::TYPE_CONTRACT => 'Contract',
            self::TYPE_INVOICE => 'Invoice',
            self::TYPE_AVATAR => 'Avatar',
            self::TYPE_ATTACHMENT => 'Attachment',
            self::TYPE_LOGO => 'Logo',
            self::TYPE_DOCUMENT => 'Document',
            self::TYPE_OTHER => 'Other',
        ];
    }

    /**
     * Get available access levels.
     */
    public static function getAccessLevels(): array
    {
        return [
            self::ACCESS_PRIVATE => 'Private',
            self::ACCESS_ACCOUNT => 'Account',
            self::ACCESS_PUBLIC => 'Public',
        ];
    }

    /**
     * Determine category from MIME type.
     */
    public static function getCategoryFromMimeType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return self::CATEGORY_IMAGE;
        }
        
        if (str_starts_with($mimeType, 'video/')) {
            return self::CATEGORY_VIDEO;
        }
        
        if (str_starts_with($mimeType, 'audio/')) {
            return self::CATEGORY_AUDIO;
        }
        
        if (in_array($mimeType, [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-tar',
            'application/gzip',
        ])) {
            return self::CATEGORY_ARCHIVE;
        }
        
        if (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])) {
            return self::CATEGORY_DOCUMENT;
        }
        
        return self::CATEGORY_OTHER;
    }
}
