<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait FileUploadTrait
{
    /**
     * Upload file to storage
     *
     * @param UploadedFile $file
     * @param string $path
     * @param string|null $filename
     * @return string
     */
    public function uploadFile(UploadedFile $file, string $path, ?string $filename = null): string
    {
        $filename = $filename ?: Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        $filePath = $file->storeAs($path, $filename, 'public');
        
        return $filePath;
    }

    /**
     * Delete file from storage
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }

    /**
     * Get file URL
     *
     * @param string $path
     * @return string
     */
    public function getFileUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    /**
     * Validate file upload
     *
     * @param UploadedFile $file
     * @param array $allowedTypes
     * @param int $maxSize
     * @return array
     */
    public function validateFileUpload(UploadedFile $file, array $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], int $maxSize = 2048): array
    {
        $errors = [];

        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
        }

        // Check file size (in KB)
        $sizeKB = $file->getSize() / 1024;
        if ($sizeKB > $maxSize) {
            $errors[] = "File size too large. Maximum size is {$maxSize}KB.";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'size' => $sizeKB,
            'extension' => $extension,
        ];
    }

    /**
     * Get image dimensions
     *
     * @param string $path
     * @return array|null
     */
    public function getImageDimensions(string $path): ?array
    {
        try {
            $imagePath = storage_path('app/public/' . $path);
            
            if (!file_exists($imagePath)) {
                return null;
            }

            $imageInfo = getimagesize($imagePath);
            
            return [
                'width' => $imageInfo[0] ?? null,
                'height' => $imageInfo[1] ?? null,
                'type' => $imageInfo['mime'] ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create thumbnail for image
     *
     * @param string $path
     * @param int $width
     * @param int $height
     * @return string|null
     */
    public function createThumbnail(string $path, int $width = 150, int $height = 150): ?string
    {
        try {
            $imagePath = storage_path('app/public/' . $path);
            
            if (!file_exists($imagePath)) {
                return null;
            }

            $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
            $thumbnailPath = 'thumbnails/' . Str::uuid() . '.' . $extension;
            
            // Use intervention/image if available, otherwise basic GD
            if (class_exists('\Intervention\Image\ImageManager')) {
                $manager = new \Intervention\Image\ImageManager();
                $image = $manager->make($imagePath);
                $image->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                
                $thumbnailFullPath = storage_path('app/public/' . $thumbnailPath);
                $image->save($thumbnailFullPath);
                
                return $thumbnailPath;
            } else {
                // Basic GD implementation
                $sourceImage = imagecreatefromstring(file_get_contents($imagePath));
                $thumbnail = imagecreatetruecolor($width, $height);
                
                $sourceWidth = imagesx($sourceImage);
                $sourceHeight = imagesy($sourceImage);
                
                // Calculate aspect ratio
                $ratio = min($width / $sourceWidth, $height / $sourceHeight);
                $newWidth = $sourceWidth * $ratio;
                $newHeight = $sourceHeight * $ratio;
                
                // Center the thumbnail
                $x = ($width - $newWidth) / 2;
                $y = ($height - $newHeight) / 2;
                
                imagecopyresampled($thumbnail, $sourceImage, $x, $y, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
                
                $thumbnailFullPath = storage_path('app/public/' . $thumbnailPath);
                
                switch ($extension) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg($thumbnail, $thumbnailFullPath, 90);
                        break;
                    case 'png':
                        imagepng($thumbnail, $thumbnailFullPath, 9);
                        break;
                    case 'gif':
                        imagegif($thumbnail, $thumbnailFullPath);
                        break;
                    default:
                        return null;
                }
                
                imagedestroy($sourceImage);
                imagedestroy($thumbnail);
                
                return $thumbnailPath;
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}
