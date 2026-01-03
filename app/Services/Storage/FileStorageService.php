<?php

namespace App\Services\Storage;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class FileStorageService
{
    private array $allowedMimeTypes = [
        // Images
        'image/jpeg',
        'image/jpg', // Some systems return jpg instead of jpeg
        'image/pjpeg', // Alternative JPEG MIME type
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',

        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',

        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-tar',
        'application/gzip',

        // Video
        'video/mp4',
        'video/quicktime',
        'video/x-msvideo',

        // Audio
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
    ];

    private int $maxFileSize = 10485760; // 10MB default

    /**
     * Upload a file.
     */
    public function upload(
        UploadedFile $uploadedFile,
        User $user,
        array $options = []
    ): File {
        // Validate file
        $this->validateFile($uploadedFile, $options);

        // Generate unique filename
        $extension = $uploadedFile->getClientOriginalExtension();
        $originalName = $uploadedFile->getClientOriginalName();
        $filename = $this->generateFilename($originalName, $extension);

        // Determine storage path
        $path = $this->getStoragePath($user->account_id, $options['type'] ?? null);
        $fullPath = $path . '/' . $filename;

        // Store file
        $disk = $options['disk'] ?? config('filesystems.default');
        $storedPath = $uploadedFile->storeAs($path, $filename, $disk);

        // Calculate checksum
        $content = Storage::disk($disk)->get($storedPath);
        $checksum = hash('sha256', $content);

        // Get file metadata
        $mimeType = $uploadedFile->getMimeType();
        $size = $uploadedFile->getSize();
        $category = File::getCategoryFromMimeType($mimeType);

        $metadata = $this->extractMetadata($uploadedFile, $content);

        // Create file record
        $file = File::create([
            'account_id' => $user->account_id,
            'uploaded_by' => $user->id,
            'name' => $originalName,
            'filename' => $filename,
            'path' => $storedPath,
            'disk' => $disk,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $size,
            'category' => $category,
            'type' => $options['type'] ?? File::TYPE_ATTACHMENT,
            'related_type' => $options['related_type'] ?? null,
            'related_id' => $options['related_id'] ?? null,
            'description' => $options['description'] ?? null,
            'metadata' => $metadata,
            'is_public' => $options['is_public'] ?? false,
            'access_level' => $options['access_level'] ?? File::ACCESS_PRIVATE,
            'checksum' => $checksum,
            'is_virus_scanned' => false, // Can be implemented with ClamAV or similar
            'is_safe' => true,
        ]);

        return $file;
    }

    /**
     * Delete a file.
     */
    public function delete(File $file): bool
    {
        // Delete from storage
        $deleted = $file->deleteFromStorage();

        // Delete record
        $file->delete();

        return $deleted;
    }

    /**
     * Move file to different location.
     */
    public function move(File $file, string $newPath): bool
    {
        $disk = $file->disk;

        if (!Storage::disk($disk)->exists($file->path)) {
            return false;
        }

        $newFullPath = $newPath . '/' . $file->filename;

        // Move file
        $moved = Storage::disk($disk)->move($file->path, $newFullPath);

        if ($moved) {
            $file->update(['path' => $newFullPath]);
        }

        return $moved;
    }

    /**
     * Copy file.
     */
    public function copy(File $file, string $newPath, ?User $user = null): ?File
    {
        $disk = $file->disk;

        if (!Storage::disk($disk)->exists($file->path)) {
            return null;
        }

        $newFilename = $this->generateFilename($file->name, $file->extension);
        $newFullPath = $newPath . '/' . $newFilename;

        // Copy file
        $copied = Storage::disk($disk)->copy($file->path, $newFullPath);

        if (!$copied) {
            return null;
        }

        // Create new file record
        $newFile = File::create([
            'account_id' => $file->account_id,
            'uploaded_by' => $user?->id ?? $file->uploaded_by,
            'name' => $file->name,
            'filename' => $newFilename,
            'path' => $newFullPath,
            'disk' => $disk,
            'mime_type' => $file->mime_type,
            'extension' => $file->extension,
            'size' => $file->size,
            'category' => $file->category,
            'type' => $file->type,
            'related_type' => $file->related_type,
            'related_id' => $file->related_id,
            'description' => $file->description,
            'metadata' => $file->metadata,
            'is_public' => $file->is_public,
            'access_level' => $file->access_level,
            'checksum' => $file->checksum,
            'is_virus_scanned' => $file->is_virus_scanned,
            'is_safe' => $file->is_safe,
        ]);

        return $newFile;
    }

    /**
     * Validate uploaded file.
     */
    private function validateFile(UploadedFile $file, array $options): void
    {
        // Check MIME type
        $allowedTypes = $options['allowed_mime_types'] ?? $this->allowedMimeTypes;
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Normalize MIME type for JPEG variants
        if (in_array($mimeType, ['image/jpg', 'image/pjpeg'])) {
            $mimeType = 'image/jpeg';
        }

        // Check MIME type
        if (!in_array($mimeType, $allowedTypes)) {
            // Fallback: check by extension for common image types
            $extensionMimeMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
            ];

            if (isset($extensionMimeMap[$extension]) && in_array($extensionMimeMap[$extension], $allowedTypes)) {
                // Allow if extension matches allowed type
                return;
            }

            $allowedExts = $this->getAllowedExtensions();
            throw new \Exception('Invalid file type. Allowed file types: ' . implode(', ', $allowedExts) . '. Your file: ' . $extension . ' (' . $mimeType . ')');
        }

        // Check file size
        $maxSize = $options['max_size'] ?? $this->maxFileSize;

        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed size: ' . $this->formatBytes($maxSize));
        }

        // Additional validation can be added here (virus scanning, etc.)
    }

    /**
     * Get allowed file extensions for error messages.
     */
    private function getAllowedExtensions(): array
    {
        $mimeToExt = [
            'image/jpeg' => 'jpg, jpeg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'application/zip' => 'zip',
            'application/x-rar-compressed' => 'rar',
            'application/x-tar' => 'tar',
            'application/gzip' => 'gz',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
            'audio/ogg' => 'ogg',
        ];

        $extensions = [];
        foreach ($this->allowedMimeTypes as $mime) {
            if (isset($mimeToExt[$mime])) {
                $extensions[] = $mimeToExt[$mime];
            }
        }

        return array_unique($extensions);
    }

    /**
     * Generate unique filename.
     */
    private function generateFilename(string $originalName, string $extension): string
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $name = Str::slug($name);
        $hash = Str::random(8);

        return $name . '_' . $hash . '.' . $extension;
    }

    /**
     * Get storage path.
     */
    private function getStoragePath(int $accountId, ?string $type = null): string
    {
        $basePath = 'accounts/' . $accountId;

        if ($type) {
            $basePath .= '/' . $type;
        }

        $year = date('Y');
        $month = date('m');

        return $basePath . '/' . $year . '/' . $month;
    }

    /**
     * Extract metadata from file.
     */
    private function extractMetadata(UploadedFile $file, string $content): array
    {
        $metadata = [];

        // Image metadata
        if (str_starts_with($file->getMimeType(), 'image/')) {
            try {
                $imageInfo = getimagesizefromstring($content);
                if ($imageInfo) {
                    $metadata['width'] = $imageInfo[0];
                    $metadata['height'] = $imageInfo[1];
                }
            } catch (\Exception $e) {
                // Ignore errors
            }
        }

        return $metadata;
    }

    /**
     * Format bytes to human readable.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Set allowed MIME types.
     */
    public function setAllowedMimeTypes(array $types): self
    {
        $this->allowedMimeTypes = $types;
        return $this;
    }

    /**
     * Set max file size.
     */
    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }
}
