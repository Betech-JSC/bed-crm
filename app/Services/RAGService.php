<?php

namespace App\Services;

use App\Models\ChatWidget;
use App\Models\ChatWidgetDocument;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class RAGService
{
    /**
     * Chunk size for documents (in tokens, approximately)
     */
    private const CHUNK_SIZE = 500; // ~2000 characters
    private const CHUNK_OVERLAP = 50; // Overlap between chunks (in tokens)

    /**
     * Process and store document with embeddings
     */
    public function processDocument(
        ChatWidget $widget,
        string $name,
        string $content,
        ?string $filePath = null,
        ?string $fileType = null
    ): array {
        // Split content into chunks
        $chunks = $this->chunkText($content);

        $processedChunks = [];

        foreach ($chunks as $index => $chunk) {
            // Generate embedding for chunk
            $embedding = $this->generateEmbedding($chunk);

            if (!$embedding) {
                Log::warning('Failed to generate embedding for chunk', [
                    'widget_id' => $widget->id,
                    'chunk_index' => $index,
                ]);
                continue;
            }

            // Estimate token count
            $tokenCount = $this->estimateTokenCount($chunk);

            // Create document chunk
            $document = ChatWidgetDocument::create([
                'account_id' => $widget->account_id,
                'widget_id' => $widget->id,
                'name' => $name . (count($chunks) > 1 ? " (Part " . ($index + 1) . ")" : ''),
                'content' => $chunk,
                'file_path' => $filePath,
                'file_type' => $fileType,
                'chunk_index' => $index,
                'embedding' => json_encode($embedding),
                'token_count' => $tokenCount,
                'metadata' => [
                    'total_chunks' => count($chunks),
                    'chunk_size' => strlen($chunk),
                ],
                'is_active' => true,
            ]);

            $processedChunks[] = $document;
        }

        return $processedChunks;
    }

    /**
     * Search for relevant documents based on query
     */
    public function searchRelevantDocuments(ChatWidget $widget, string $query, int $limit = 3): array
    {
        // Generate embedding for query
        $queryEmbedding = $this->generateEmbedding($query);

        if (!$queryEmbedding) {
            return [];
        }

        // Get all active documents for widget
        $documents = $widget->documents()
            ->where('is_active', true)
            ->get();

        if ($documents->isEmpty()) {
            return [];
        }

        // Calculate similarity scores
        $scoredDocuments = [];
        foreach ($documents as $document) {
            $docEmbedding = $document->getEmbeddingArray();
            if (!$docEmbedding) {
                continue;
            }

            $similarity = $this->cosineSimilarity($queryEmbedding, $docEmbedding);
            
            $scoredDocuments[] = [
                'document' => $document,
                'similarity' => $similarity,
            ];
        }

        // Sort by similarity (descending)
        usort($scoredDocuments, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        // Return top N documents with similarity > threshold
        $threshold = 0.7; // Minimum similarity score
        $relevant = array_slice($scoredDocuments, 0, $limit);
        
        return array_filter($relevant, fn($item) => $item['similarity'] >= $threshold);
    }

    /**
     * Get context from relevant documents for RAG
     */
    public function getRAGContext(ChatWidget $widget, string $query, int $maxChunks = 3): string
    {
        $relevantDocs = $this->searchRelevantDocuments($widget, $query, $maxChunks);

        if (empty($relevantDocs)) {
            return '';
        }

        $contexts = [];
        foreach ($relevantDocs as $item) {
            $contexts[] = $item['document']->content;
        }

        return implode("\n\n", $contexts);
    }

    /**
     * Chunk text into smaller pieces
     */
    private function chunkText(string $text): array
    {
        $chunks = [];
        $text = trim($text);
        
        if (empty($text)) {
            return [];
        }

        $textLength = strlen($text);
        $chunkSize = self::CHUNK_SIZE * 4; // Approximate: 1 token ≈ 4 characters
        $overlap = self::CHUNK_OVERLAP * 4;

        if ($textLength <= $chunkSize) {
            return [$text];
        }

        $start = 0;
        while ($start < $textLength) {
            $end = min($start + $chunkSize, $textLength);
            
            // Try to break at sentence boundary
            if ($end < $textLength) {
                $chunkText = substr($text, $start, $chunkSize);
                $lastPeriod = strrpos($chunkText, '.');
                $lastNewline = strrpos($chunkText, "\n");
                $lastQuestion = strrpos($chunkText, '?');
                $lastExclamation = strrpos($chunkText, '!');
                
                $breakPoint = max($lastPeriod, $lastNewline, $lastQuestion, $lastExclamation);
                if ($breakPoint !== false && $breakPoint > $chunkSize * 0.5) {
                    $end = $start + $breakPoint + 1;
                }
            }

            $chunk = substr($text, $start, $end - $start);
            $trimmedChunk = trim($chunk);
            
            if (!empty($trimmedChunk)) {
                $chunks[] = $trimmedChunk;
            }

            // Move start position with overlap
            $start = max($start + 1, $end - $overlap);
            
            // Prevent infinite loop
            if ($start >= $end) {
                $start = $end;
            }
        }

        return array_filter($chunks, fn($chunk) => !empty(trim($chunk)));
    }

    /**
     * Generate embedding using OpenAI
     */
    private function generateEmbedding(string $text): ?array
    {
        try {
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-small', // Cost-effective embedding model
                'input' => $text,
            ]);

            return $response->embeddings[0]->embedding;
        } catch (\Exception $e) {
            Log::error('Failed to generate embedding', [
                'error' => $e->getMessage(),
                'text_length' => strlen($text),
            ]);
            return null;
        }
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    private function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        if (count($vectorA) !== count($vectorB)) {
            return 0.0;
        }

        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($vectorA); $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $normA += $vectorA[$i] * $vectorA[$i];
            $normB += $vectorB[$i] * $vectorB[$i];
        }

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Estimate token count (rough approximation)
     */
    private function estimateTokenCount(string $text): int
    {
        // Rough estimate: ~4 characters per token
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Delete all documents for a widget
     */
    public function deleteWidgetDocuments(ChatWidget $widget): void
    {
        $widget->documents()->delete();
    }
}

