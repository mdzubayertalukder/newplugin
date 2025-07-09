<?php

namespace Plugin\Dropshipping\Services;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageImportService
{
    /**
     * Download and import a single image from URL
     * 
     * @param string $imageUrl
     * @param string $productName
     * @return int|null File ID if successful, null if failed
     */
    public function importImageFromUrl($imageUrl, $productName = 'product')
    {
        try {
            // Validate URL
            if (empty($imageUrl) || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                Log::warning('Invalid image URL provided: ' . $imageUrl);
                return null;
            }

            // Download image
            $response = Http::timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                Log::warning('Failed to download image: ' . $imageUrl);
                return null;
            }

            // Get image content
            $imageContent = $response->body();

            // Get original filename and extension
            $pathInfo = pathinfo(parse_url($imageUrl, PHP_URL_PATH));
            $originalExtension = $pathInfo['extension'] ?? 'jpg';
            $originalName = $pathInfo['filename'] ?? Str::slug($productName);

            // Generate unique filename
            $fileName = $originalName . '_' . time() . '_' . Str::random(8) . '.' . $originalExtension;

            // Create temporary file
            $tempPath = sys_get_temp_dir() . '/' . $fileName;
            file_put_contents($tempPath, $imageContent);

            // Create an UploadedFile object for saveFileInStorage
            $uploadedFile = new UploadedFile(
                $tempPath,
                $fileName,
                mime_content_type($tempPath),
                null,
                true // Mark as test file to avoid validation errors
            );

            // Save using the system's file storage function
            $fileId = saveFileInStorage($uploadedFile, false);

            // Clean up temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            if ($fileId) {
                Log::info('Successfully imported image: ' . $imageUrl . ' -> File ID: ' . $fileId);
                return $fileId;
            } else {
                Log::error('Failed to save image to storage: ' . $imageUrl);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Image import failed for URL: ' . $imageUrl . ' - Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Import multiple images from an array of URLs
     * 
     * @param array $imageUrls
     * @param string $productName
     * @param int $maxImages Maximum number of images to import
     * @return array Array of file IDs
     */
    public function importMultipleImages($imageUrls, $productName = 'product', $maxImages = 10)
    {
        $fileIds = [];

        if (!is_array($imageUrls)) {
            return $fileIds;
        }

        $count = 0;
        foreach ($imageUrls as $imageUrl) {
            if ($count >= $maxImages) {
                break;
            }

            // Extract URL from image object if needed
            if (is_array($imageUrl)) {
                $url = $imageUrl['src'] ?? $imageUrl['url'] ?? null;
            } else {
                $url = $imageUrl;
            }

            if ($url) {
                $fileId = $this->importImageFromUrl($url, $productName . '_' . $count);
                if ($fileId) {
                    $fileIds[] = $fileId;
                }
            }

            $count++;
        }

        return $fileIds;
    }

    /**
     * Get the main thumbnail image from WooCommerce product images
     * 
     * @param mixed $imagesData JSON string or array of images
     * @param string $productName
     * @return int|null File ID if successful, null if failed
     */
    public function importThumbnailImage($imagesData, $productName = 'product')
    {
        try {
            // Parse images data
            if (is_string($imagesData)) {
                $images = json_decode($imagesData, true);
            } else {
                $images = $imagesData;
            }

            if (!is_array($images) || empty($images)) {
                return null;
            }

            // Get the first image as thumbnail
            $firstImage = $images[0];

            // Extract URL
            if (is_array($firstImage)) {
                $imageUrl = $firstImage['src'] ?? $firstImage['url'] ?? null;
            } else {
                $imageUrl = $firstImage;
            }

            if ($imageUrl) {
                return $this->importImageFromUrl($imageUrl, $productName . '_thumbnail');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Thumbnail import failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate image URL and check if it's accessible
     * 
     * @param string $imageUrl
     * @return bool
     */
    public function validateImageUrl($imageUrl)
    {
        if (empty($imageUrl) || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        try {
            $response = Http::timeout(10)->head($imageUrl);
            return $response->successful() &&
                str_contains($response->header('Content-Type', ''), 'image');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get image info without downloading
     * 
     * @param string $imageUrl
     * @return array|null
     */
    public function getImageInfo($imageUrl)
    {
        try {
            $response = Http::timeout(10)->head($imageUrl);

            if ($response->successful()) {
                return [
                    'url' => $imageUrl,
                    'content_type' => $response->header('Content-Type'),
                    'content_length' => $response->header('Content-Length'),
                    'is_valid' => str_contains($response->header('Content-Type', ''), 'image')
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
