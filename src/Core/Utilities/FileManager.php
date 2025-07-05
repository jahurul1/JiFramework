<?php
namespace JiFramework\Core\Utilities;

use Exception;
use finfo;
use JiFramework\Config\Config;

class FileManager
{
    /**
     * Extract detailed information from a single file.
     *
     * @param array $file The file array from $_FILES.
     * @return array      An array containing enhanced file information.
     * @throws Exception If the file is not valid.
     */
    public function extractFileInfo(array $file)
    {
        // Check if the file is uploaded via HTTP POST
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('File upload error: Possible file upload attack.');
        }

        // Basic file information
        $fileName = $file['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileSize = $file['size'];
        $fileTempName = $file['tmp_name'];
        $fileTypeProvided = $file['type']; // The MIME type provided by $_FILES

        // Get actual MIME type for security
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileActualMimeType = $finfo->file($fileTempName);

        // Get file's last modification time
        $fileModificationTime = filemtime($fileTempName);

        // Generate a SHA-256 hash of the file content
        $fileHash = hash_file('sha256', $fileTempName);

        // Package enhanced file information
        $fileInfoEnhanced = [
            'name'             => $fileName,
            'extension'        => $fileExtension,
            'size'             => $fileSize,
            'tmpName'          => $fileTempName,
            'typeProvided'     => $fileTypeProvided,
            'actualType'       => $fileActualMimeType,
            'modificationTime' => $fileModificationTime,
            'hash'             => $fileHash,
        ];

        return $fileInfoEnhanced;
    }

    /**
     * Extract detailed information from multiple files.
     *
     * @param array $files The files array from $_FILES.
     * @return array       An array of arrays containing enhanced file information.
     * @throws Exception If any file is not valid.
     */
    public function extractMultipleFileInfo(array $files)
    {
        $fileInfos = [];
        $fileCount = count($files['name']);

        for ($index = 0; $index < $fileCount; $index++) {
            // Check if the file is uploaded via HTTP POST
            if (!is_uploaded_file($files['tmp_name'][$index])) {
                throw new Exception('File upload error: Possible file upload attack.');
            }

            // Extract details for the specified file at the given index
            $fileName = $files['name'][$index];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $fileMimeType = $files['type'][$index];
            $fileSize = $files['size'][$index];
            $fileTempName = $files['tmp_name'][$index];

            // Actual MIME type verification using PHP's File Information functions
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $fileActualMimeType = $finfo->file($fileTempName);

            // Get file's last modification time
            $fileModificationTime = filemtime($fileTempName);

            // Generate a SHA-256 hash of the file content
            $fileHash = hash_file('sha256', $fileTempName);

            // Package file information
            $fileInfo = [
                'name'             => $fileName,
                'extension'        => $fileExtension,
                'typeProvided'     => $fileMimeType,
                'actualType'       => $fileActualMimeType,
                'modificationTime' => $fileModificationTime,
                'size'             => $fileSize,
                'tmpName'          => $fileTempName,
                'hash'             => $fileHash,
            ];

            $fileInfos[] = $fileInfo;
        }

        return $fileInfos;
    }

    /**
     * Ensure that a directory exists and is writable.
     *
     * @param string $path The directory path.
     * @return string      The directory path.
     * @throws Exception If the directory cannot be created or is not writable.
     */
    public function ensureDirectoryExists(string $path)
    {
        // Check if the directory exists
        if (!is_dir($path)) {
            // Attempt to create the directory with safe permissions
            if (!mkdir($path, 0755, true)) {
                throw new Exception("Failed to create directory: $path");
            }
        }

        // Ensure the directory is writable
        if (!is_writable($path)) {
            // Attempt to set the permissions if the directory is not writable
            if (!chmod($path, 0755)) {
                throw new Exception("Directory is not writable and cannot set permissions: $path");
            }
        }

        return $path;
    }

    /**
     * Resize and save an image to a specified path.
     *
     * @param string $photoPath The path to the original photo.
     * @param int    $maxDim    The maximum dimension (width or height).
     * @param string $savePath  The path to save the resized image (optional).
     * @return bool             True on success, false on failure.
     */
    public function resizeAndSaveImage(string $photoPath, int $maxDim = null, string $savePath = null)
    {
        // Determine the save path
        $savePath = $savePath ?: $photoPath;
        $maxDim = $maxDim ?: Config::IMAGE_MAX_DIMENSION;

        // Get original image dimensions and type
        list($width, $height, $type) = getimagesize($photoPath);
        if (!$width || !$height) {
            throw new Exception("Failed to get image dimensions: $photoPath");
        }

        // Calculate new dimensions while maintaining aspect ratio
        $ratio = $width / $height;

        if ($width > $height) {
            $newWidth = $maxDim;
            $newHeight = $maxDim / $ratio;
        } else {
            $newWidth = $maxDim * $ratio;
            $newHeight = $maxDim;
        }

        // Create a new image based on the type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $srcImg = imagecreatefromjpeg($photoPath);
                break;
            case IMAGETYPE_PNG:
                $srcImg = imagecreatefrompng($photoPath);
                break;
            case IMAGETYPE_GIF:
                $srcImg = imagecreatefromgif($photoPath);
                break;
            default:
                return false;
        }

        if (!$srcImg) {
            throw new Exception("Failed to create image: $photoPath");
        }

        // Create a new true color image with the new dimensions
        $dstImg = imagecreatetruecolor((int) round($newWidth), (int) round($newHeight));

        // Preserve transparency for PNG and GIF images
        if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
            imagecolortransparent($dstImg, imagecolorallocatealpha($dstImg, 0, 0, 0, 127));
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
        }

        // Copy and resize part of the image with resampling
        imagecopyresampled(
            $dstImg,
            $srcImg,
            0,
            0,
            0,
            0,
            (int) round($newWidth),
            (int) round($newHeight),
            $width,
            $height
        );

        // Save the resized image based on the original type
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($dstImg, $savePath, 90); // Adjust quality as needed
                break;
            case IMAGETYPE_PNG:
                $result = imagepng($dstImg, $savePath, 6); // Compression level: 0 (no compression) to 9
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($dstImg, $savePath);
                break;
            default:
                $result = false;
        }

        if (!$result) {

        }

        // Free up memory
        imagedestroy($srcImg);
        imagedestroy($dstImg);

        return $result;
    }

    /**
     * Upload an image to a specified directory with optional resizing.
     *
     * @param array  $file         The file array from $_FILES.
     * @param string $destination  The directory to save the uploaded file.
     * @param int    $maxSize      The maximum allowed file size in bytes (optional).
     * @param int    $maxDim       The maximum dimension for resizing (optional).
     * @param array  $allowedTypes An array of allowed MIME types.
     * @return array               An array containing 'success' and 'data' or 'error'.
     */
    public function uploadFile(array $file, string $destination = null, int $maxSize = null, array $allowedTypes = null)
    {
        // Use defaults from Config if parameters are not provided
        $destination = $destination ?: Config::UPLOAD_DIRECTORY;
        $maxSize = $maxSize ?: Config::MAX_FILE_SIZE;
        $allowedTypes = $allowedTypes ?: Config::ALLOWED_IMAGE_TYPES;

        // Ensure the directory exists
        try {
            $this->ensureDirectoryExists($destination);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create or access the destination directory.'
            ];
        }

        // Extract file info
        try {
            $fileInfo = $this->extractFileInfo($file);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid file upload.'
            ];
        }

        // Validate the file size
        if ($fileInfo['size'] > $maxSize) {
            return [
                'success' => false,
                'error' => 'File size exceeds the maximum allowed limit.'
            ];
        }

        // Validate the file type
        if (!in_array($fileInfo['actualType'], $allowedTypes)) {
            return [
                'success' => false,
                'error' => 'Invalid file type.'
            ];
        }

        // Generate a unique file name
        $uniqueName = uniqid('img_', true) . '.' . $fileInfo['extension'];
        $savePath = rtrim($destination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uniqueName;

        // Move the uploaded file to the destination
        if (!move_uploaded_file($fileInfo['tmpName'], $savePath)) {
            return [
                'success' => false,
                'error' => 'Failed to save the uploaded file.'
            ];
        }

        // Update file info with the new save path and unique name
        $fileInfo['savedPath'] = $savePath;
        $fileInfo['uniqueName'] = $uniqueName;

        // Return success and file info
        return [
            'success' => true,
            'data' => $fileInfo
        ];
    }

    /**
     * Delete a file.
     *
     * @param string $filePath The path to the file to delete.
     * @return bool            True on success, false on failure.
     */
    public function deleteFile(string $filePath)
    {
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * Get a list of files in a directory.
     *
     * @param string $directory The directory path.
     * @param bool   $recursive Whether to include files in subdirectories.
     * @return array            An array of file paths.
     */
    public function listFiles(string $directory, bool $recursive = false)
    {
        $files = [];

        if (!is_dir($directory)) {
            return $files;
        }

        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        } else {
            foreach (new \DirectoryIterator($directory) as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $files[] = $fileInfo->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Download a file to the browser.
     *
     * @param string $filePath The path to the file to download.
     * @param string $filename The name to give the downloaded file.
     * @return void
     * @throws Exception If the file cannot be read.
     */
    public function downloadFile(string $filePath, string $filename = null)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception('File not found or cannot be read.');
        }

        $filename = $filename ?: basename($filePath);
        $mimeType = mime_content_type($filePath);
        $fileSize = filesize($filePath);

        // Set headers
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $fileSize);

        // Read the file
        readfile($filePath);
        exit();
    }

}