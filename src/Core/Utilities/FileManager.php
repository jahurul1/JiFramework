<?php
namespace JiFramework\Core\Utilities;

use Exception;
use finfo;
use JiFramework\Config\Config;

class FileManager
{
    // =========================================================================
    // Upload Info
    // =========================================================================

    /**
     * Extract detailed information from a single uploaded file ($_FILES entry).
     * Validates that the file was actually uploaded via HTTP POST.
     *
     * @param array $file  Single file array from $_FILES.
     * @return array       Enhanced file information.
     * @throws Exception   If the file was not uploaded via HTTP POST.
     */
    public function getUploadInfo(array $file): array
    {
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('File upload error: possible file upload attack.');
        }

        $name    = $file['name'];
        $tmpName = $file['tmp_name'];
        $finfo   = new finfo(FILEINFO_MIME_TYPE);

        return [
            'name'             => $this->generateSafeFilename($name),
            'originalName'     => $name,
            'extension'        => strtolower(pathinfo($name, PATHINFO_EXTENSION)),
            'size'             => $file['size'],
            'humanSize'        => $this->humanFileSize($file['size']),
            'tmpName'          => $tmpName,
            'typeProvided'     => $file['type'],
            'actualType'       => $finfo->file($tmpName),
            'modificationTime' => filemtime($tmpName),
            'hash'             => hash_file('sha256', $tmpName),
        ];
    }

    /**
     * Extract detailed information from multiple uploaded files.
     * Accepts the multi-file $_FILES structure (where each key holds an array).
     *
     * @param array $files  Multi-file array from $_FILES.
     * @return array        Array of file info arrays.
     * @throws Exception    If any file was not uploaded via HTTP POST.
     */
    public function getMultipleUploadInfo(array $files): array
    {
        $results = [];
        $count   = count($files['name']);

        for ($i = 0; $i < $count; $i++) {
            $results[] = $this->getUploadInfo([
                'name'     => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'type'     => $files['type'][$i],
                'size'     => $files['size'][$i],
                'error'    => $files['error'][$i],
            ]);
        }

        return $results;
    }

    // =========================================================================
    // Upload
    // =========================================================================

    /**
     * Validate and save a single uploaded file.
     * Any parameter left null falls back to the matching Config value.
     *
     * @param array       $file          Single file array from $_FILES.
     * @param string|null $destination   Directory to save the file. Default: Config::$uploadDirectory.
     * @param int|null    $maxSize       Max allowed file size in bytes. Default: Config::$maxFileSize.
     * @param array|null  $allowedTypes  Allowed MIME types. Default: Config::$allowedImageTypes.
     * @param int|null    $maxDim        Max image dimension (px) for automatic resize. null = no resize.
     * @return array                     ['success' => true, 'data' => array]
     *                                   or ['success' => false, 'error' => string].
     */
    public function uploadFile(
        array $file,
        ?string $destination = null,
        ?int $maxSize = null,
        ?array $allowedTypes = null,
        ?int $maxDim = null
    ): array {
        $destination  = $destination  ?? Config::$uploadDirectory;
        $maxSize      = $maxSize      ?? Config::$maxFileSize;
        $allowedTypes = $allowedTypes ?? Config::$allowedImageTypes;

        // Ensure destination directory is accessible
        try {
            $this->ensureDirectoryExists($destination);
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to create or access the destination directory.'];
        }

        // Extract and validate upload info
        try {
            $info = $this->getUploadInfo($file);
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Invalid file upload.'];
        }

        // Validate file size
        if ($info['size'] > $maxSize) {
            return [
                'success' => false,
                'error'   => 'File size exceeds the maximum allowed limit of ' . $this->humanFileSize($maxSize) . '.',
            ];
        }

        // Validate MIME type
        if (!in_array($info['actualType'], $allowedTypes, true)) {
            return [
                'success' => false,
                'error'   => 'File type "' . $info['actualType'] . '" is not allowed.',
            ];
        }

        // Generate a collision-proof unique filename
        $uniqueName = bin2hex(random_bytes(8)) . '.' . $info['extension'];
        $savePath   = rtrim($destination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uniqueName;

        // Move the file from the temp location
        if (!move_uploaded_file($info['tmpName'], $savePath)) {
            return ['success' => false, 'error' => 'Failed to save the uploaded file.'];
        }

        $info['savedPath']  = $savePath;
        $info['uniqueName'] = $uniqueName;

        // Resize if it is an image and a dimension limit was requested
        if ($maxDim !== null && $this->isImageMimeType($info['actualType'])) {
            $this->resizeImage($savePath, $maxDim);
        }

        return ['success' => true, 'data' => $info];
    }

    /**
     * Validate and save multiple uploaded files in one call.
     * Any parameter left null falls back to the matching Config value.
     *
     * @param array       $files         Multi-file array from $_FILES.
     * @param string|null $destination   Directory to save files.
     * @param int|null    $maxSize       Max file size per file in bytes.
     * @param array|null  $allowedTypes  Allowed MIME types.
     * @param int|null    $maxDim        Max image dimension for automatic resize.
     * @return array                     Array of results, one per file (same structure as uploadFile()).
     */
    public function uploadMultipleFiles(
        array $files,
        ?string $destination = null,
        ?int $maxSize = null,
        ?array $allowedTypes = null,
        ?int $maxDim = null
    ): array {
        $results = [];
        $count   = count($files['name']);

        for ($i = 0; $i < $count; $i++) {
            $results[] = $this->uploadFile(
                [
                    'name'     => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'type'     => $files['type'][$i],
                    'size'     => $files['size'][$i],
                    'error'    => $files['error'][$i],
                ],
                $destination,
                $maxSize,
                $allowedTypes,
                $maxDim
            );
        }

        return $results;
    }

    // =========================================================================
    // Image Processing
    // =========================================================================

    /**
     * Resize an image maintaining aspect ratio.
     * Supports JPEG, PNG, GIF, and WebP. Preserves alpha/transparency.
     * If the image is already within bounds it is copied (or left in place) unchanged.
     *
     * @param string      $photoPath  Path to the source image.
     * @param int|null    $maxDim     Max width or height in pixels. Default: Config::$imageMaxDimension.
     * @param string|null $savePath   Output path. Default: overwrites the source file.
     * @return bool                   True on success, false if the image type is unsupported.
     * @throws Exception              If the image cannot be read or the destination cannot be written.
     */
    public function resizeImage(string $photoPath, ?int $maxDim = null, ?string $savePath = null): bool
    {
        $savePath = $savePath ?? $photoPath;
        $maxDim   = $maxDim   ?? Config::$imageMaxDimension;

        [$width, $height, $type] = getimagesize($photoPath);
        if (!$width || !$height) {
            throw new Exception("Failed to get image dimensions: $photoPath");
        }

        // Already within bounds — just copy if saving to a different path
        if ($width <= $maxDim && $height <= $maxDim) {
            if ($savePath !== $photoPath) {
                copy($photoPath, $savePath);
            }
            return true;
        }

        $ratio     = $width / $height;
        $newWidth  = $width > $height ? $maxDim                       : (int) round($maxDim * $ratio);
        $newHeight = $width > $height ? (int) round($maxDim / $ratio) : $maxDim;

        $srcImg = $this->createImageResource($photoPath, $type);
        if ($srcImg === null) {
            return false; // Unsupported type
        }

        $dstImg = imagecreatetruecolor($newWidth, $newHeight);
        $this->preserveTransparency($dstImg, $type);

        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $result = $this->saveImageResource($dstImg, $savePath, $type);

        imagedestroy($srcImg);
        imagedestroy($dstImg);

        return $result;
    }

    /**
     * Convert an image to WebP format.
     * Supports JPEG, PNG, GIF, and WebP sources. Preserves PNG/GIF transparency.
     *
     * @param string      $imagePath   Path to the source image.
     * @param string|null $outputPath  Output file path.
     *                                 Default: same directory and name as source, with .webp extension.
     * @param int         $quality     WebP quality 0–100. Default: 80.
     * @return string|false            Absolute path to the created WebP file, or false on failure.
     * @throws Exception               If the source image does not exist or cannot be read.
     */
    public function convertToWebp(string $imagePath, ?string $outputPath = null, int $quality = 80)
    {
        if (!file_exists($imagePath) || !is_readable($imagePath)) {
            throw new Exception("Image not found or not readable: $imagePath");
        }

        [$width, $height, $type] = getimagesize($imagePath);
        if (!$width || !$height) {
            throw new Exception("Not a valid image: $imagePath");
        }

        $srcImg = $this->createImageResource($imagePath, $type);
        if ($srcImg === null) {
            return false; // Unsupported image type
        }

        // For PNG/GIF, composite onto a transparent canvas to preserve alpha
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF], true)) {
            $canvas = imagecreatetruecolor($width, $height);
            imagecolortransparent($canvas, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagecopy($canvas, $srcImg, 0, 0, 0, 0, $width, $height);
            imagedestroy($srcImg);
            $srcImg = $canvas;
        }

        $outputPath = $outputPath ?? preg_replace('/\.[^.]+$/', '.webp', $imagePath);

        $result = imagewebp($srcImg, $outputPath, $quality);
        imagedestroy($srcImg);

        return $result ? $outputPath : false;
    }

    // =========================================================================
    // Filesystem — Read / Write / Copy / Move / Delete
    // =========================================================================

    /**
     * Read a file's contents into a string.
     *
     * @param string $path  Path to the file.
     * @return string       File contents.
     * @throws Exception    If the file does not exist or cannot be read.
     */
    public function readFile(string $path): string
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw new Exception("File not found or not readable: $path");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new Exception("Failed to read file: $path");
        }

        return $contents;
    }

    /**
     * Write (or append) content to a file.
     * Creates parent directories if they do not exist.
     *
     * @param string $path     Destination file path.
     * @param string $content  Content to write.
     * @param bool   $append   Append instead of overwriting. Default: false.
     * @return bool            True on success.
     * @throws Exception       If the directory cannot be created.
     */
    public function writeFile(string $path, string $content, bool $append = false): bool
    {
        $this->ensureDirectoryExists(dirname($path));
        $flags = $append ? FILE_APPEND | LOCK_EX : LOCK_EX;
        return file_put_contents($path, $content, $flags) !== false;
    }

    /**
     * Copy a file to a new location.
     * Creates the destination directory if it does not exist.
     *
     * @param string $source       Source file path.
     * @param string $destination  Destination file path.
     * @return bool                True on success.
     * @throws Exception           If the source does not exist or the directory cannot be created.
     */
    public function copyFile(string $source, string $destination): bool
    {
        if (!file_exists($source)) {
            throw new Exception("Source file not found: $source");
        }

        $this->ensureDirectoryExists(dirname($destination));
        return copy($source, $destination);
    }

    /**
     * Move (rename) a file.
     * Creates the destination directory if it does not exist.
     *
     * @param string $source       Source file path.
     * @param string $destination  Destination file path.
     * @return bool                True on success.
     * @throws Exception           If the source does not exist.
     */
    public function moveFile(string $source, string $destination): bool
    {
        if (!file_exists($source)) {
            throw new Exception("Source file not found: $source");
        }

        $this->ensureDirectoryExists(dirname($destination));
        return rename($source, $destination);
    }

    /**
     * Delete a file. Returns true if the file is gone regardless of whether it existed.
     *
     * @param string $filePath  Path to the file.
     * @return bool             True if the file no longer exists, false on unlink failure.
     */
    public function deleteFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return true; // Already gone — idempotent success
        }

        return unlink($filePath);
    }

    // =========================================================================
    // Filesystem — Directories
    // =========================================================================

    /**
     * Ensure a directory exists and is writable. Creates missing parent directories.
     *
     * @param string $path  Directory path.
     * @return string       The resolved directory path.
     * @throws Exception    If the directory cannot be created or made writable.
     */
    public function ensureDirectoryExists(string $path): string
    {
        if (!is_dir($path)) {
            // Suppress warning — race condition between is_dir() and mkdir() on concurrent requests
            if (!@mkdir($path, 0755, true) && !is_dir($path)) {
                throw new Exception("Failed to create directory: $path");
            }
        }

        if (!is_writable($path)) {
            if (!chmod($path, 0755) || !is_writable($path)) {
                throw new Exception("Directory is not writable: $path");
            }
        }

        return $path;
    }

    /**
     * List files in a directory, sorted alphabetically.
     *
     * @param string $directory  Directory path.
     * @param bool   $recursive  Include files in subdirectories. Default: false.
     * @param string $extension  Filter by extension, e.g. 'jpg'. Empty string = all files.
     * @return array             Sorted array of absolute file paths.
     */
    public function listFiles(string $directory, bool $recursive = false, string $extension = ''): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];

        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $files[] = $item->getPathname();
                }
            }
        } else {
            foreach (new \DirectoryIterator($directory) as $item) {
                if ($item->isFile()) {
                    $files[] = $item->getPathname();
                }
            }
        }

        // Optional extension filter
        if ($extension !== '') {
            $ext   = strtolower(ltrim($extension, '.'));
            $files = array_filter($files, function ($f) use ($ext) {
                return strtolower(pathinfo($f, PATHINFO_EXTENSION)) === $ext;
            });
        }

        sort($files);
        return array_values($files);
    }

    /**
     * Delete all files inside a directory without deleting the directory itself.
     * Subdirectories are left untouched.
     *
     * @param string $path  Directory path.
     * @return bool         True if every file was deleted, false if any deletion failed.
     */
    public function cleanDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $success = true;
        foreach (new \DirectoryIterator($path) as $item) {
            if ($item->isFile() && !unlink($item->getPathname())) {
                $success = false;
            }
        }

        return $success;
    }

    // =========================================================================
    // File Info & Utilities
    // =========================================================================

    /**
     * Get detailed information about an existing file on disk (not an upload).
     *
     * @param string $path  Path to the file.
     * @return array        Associative array with name, path, extension, size, humanSize,
     *                      mimeType, modificationTime, hash.
     * @throws Exception    If the file does not exist or is not readable.
     */
    public function getFileInfo(string $path): array
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw new Exception("File not found or not readable: $path");
        }

        $size = filesize($path);

        return [
            'name'             => basename($path),
            'path'             => realpath($path),
            'extension'        => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            'size'             => $size,
            'humanSize'        => $this->humanFileSize($size),
            'mimeType'         => $this->getMimeType($path),
            'modificationTime' => filemtime($path),
            'hash'             => hash_file('sha256', $path),
        ];
    }

    /**
     * Get the MIME type of a file on disk.
     *
     * @param string $path  Path to the file.
     * @return string       MIME type string, e.g. 'image/jpeg'.
     * @throws Exception    If the file does not exist.
     */
    public function getMimeType(string $path): string
    {
        if (!file_exists($path)) {
            throw new Exception("File not found: $path");
        }

        return (new finfo(FILEINFO_MIME_TYPE))->file($path);
    }

    /**
     * Convert a byte count to a human-readable size string.
     *
     * @param int $bytes     File size in bytes.
     * @param int $decimals  Number of decimal places. Default: 2.
     * @return string        e.g. '1.50 MB', '820 B', '2.34 GB'.
     */
    public function humanFileSize(int $bytes, int $decimals = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i     = 0;
        $size  = (float) max(0, $bytes);

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, $i === 0 ? 0 : $decimals) . ' ' . $units[$i];
    }

    /**
     * Generate a safe filename by stripping path traversal components and
     * removing characters that are unsafe in filenames.
     *
     * @param string $name  Original filename (may include path or extension).
     * @return string       Sanitized filename safe to use on any OS.
     */
    public function generateSafeFilename(string $name): string
    {
        // Strip any directory component — prevents traversal
        $name = basename($name);

        // Collapse whitespace to underscores
        $name = preg_replace('/\s+/', '_', $name);

        // Collapse consecutive dots (prevents tricks like "file..php")
        $name = preg_replace('/\.{2,}/', '.', $name);

        // Keep only safe characters: alphanumeric, underscore, hyphen, dot
        $name = preg_replace('/[^\w\-.]/', '', $name);

        // Strip leading dots and dashes
        $name = ltrim($name, '.-');

        return $name === '' ? 'file' : $name;
    }

    // =========================================================================
    // Download
    // =========================================================================

    /**
     * Stream a file to the browser as a forced download.
     * Clears any prior output buffer and sets appropriate cache-control headers.
     *
     * @param string      $filePath  Absolute path to the file.
     * @param string|null $filename  Filename shown in the browser's save dialog.
     *                               Default: basename of $filePath.
     * @return void
     * @throws Exception             If the file does not exist or cannot be read.
     */
    public function downloadFile(string $filePath, ?string $filename = null): void
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception('File not found or cannot be read.');
        }

        $filename = $filename ?? basename($filePath);
        $mimeType = mime_content_type($filePath);
        $fileSize = filesize($filePath);

        // Flush any previously buffered output
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($filePath);
        exit();
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Create a GD image resource from a file, based on the IMAGETYPE_* constant.
     * Returns null for unsupported types, throws on read failure.
     *
     * @param string $path  Path to the image file.
     * @param int    $type  IMAGETYPE_* constant from getimagesize().
     * @return resource|null
     * @throws Exception
     */
    private function createImageResource(string $path, int $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                $img = imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                $img = imagecreatefrompng($path);
                break;
            case IMAGETYPE_GIF:
                $img = imagecreatefromgif($path);
                break;
            case IMAGETYPE_WEBP:
                $img = imagecreatefromwebp($path);
                break;
            default:
                return null;
        }

        if ($img === false) {
            throw new Exception("Failed to load image: $path");
        }

        return $img;
    }

    /**
     * Save a GD image resource to disk in the format matching the IMAGETYPE_* constant.
     *
     * @param resource $img      GD image resource.
     * @param string   $path     Output file path.
     * @param int      $type     IMAGETYPE_* constant.
     * @return bool
     */
    private function saveImageResource($img, string $path, int $type): bool
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagejpeg($img, $path, 90);
            case IMAGETYPE_PNG:
                return imagepng($img, $path, 6);
            case IMAGETYPE_GIF:
                return imagegif($img, $path);
            case IMAGETYPE_WEBP:
                return imagewebp($img, $path, 90);
            default:
                return false;
        }
    }

    /**
     * Configure a GD image resource to preserve transparency for PNG, GIF, and WebP.
     *
     * @param resource $img   GD image resource.
     * @param int      $type  IMAGETYPE_* constant.
     */
    private function preserveTransparency($img, int $type): void
    {
        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagecolortransparent($img, imagecolorallocatealpha($img, 0, 0, 0, 127));
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }
    }

    /**
     * Check whether a MIME type string represents an image.
     *
     * @param string $mimeType  MIME type, e.g. 'image/jpeg'.
     * @return bool
     */
    private function isImageMimeType(string $mimeType): bool
    {
        return strpos($mimeType, 'image/') === 0;
    }
}
