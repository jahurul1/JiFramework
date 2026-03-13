<?php

namespace JiFramework\Tests\Feature;

use JiFramework\Core\Utilities\FileManager;
use JiFramework\Tests\TestCase;

class FileManagerTest extends TestCase
{
    private FileManager $fm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fm = new FileManager();
    }

    // ── writeFile() / readFile() ──────────────────────────────────────────────

    public function testWriteAndReadFile(): void
    {
        $path = $this->tempDir . 'test.txt';
        $this->fm->writeFile($path, 'hello world');
        $this->assertSame('hello world', $this->fm->readFile($path));
    }

    public function testWriteFileAppend(): void
    {
        $path = $this->tempDir . 'append.txt';
        $this->fm->writeFile($path, 'line1');
        $this->fm->writeFile($path, 'line2', true);
        $this->assertSame('line1line2', $this->fm->readFile($path));
    }

    public function testReadFileThrowsForMissingFile(): void
    {
        $this->expectException(\Exception::class);
        $this->fm->readFile($this->tempDir . 'nonexistent.txt');
    }

    // ── copyFile() ───────────────────────────────────────────────────────────

    public function testCopyFile(): void
    {
        $src  = $this->tempDir . 'src.txt';
        $dest = $this->tempDir . 'dest.txt';
        $this->fm->writeFile($src, 'copy me');
        $this->assertTrue($this->fm->copyFile($src, $dest));
        $this->assertSame('copy me', $this->fm->readFile($dest));
        $this->assertFileExists($src); // original still exists
    }

    // ── moveFile() ───────────────────────────────────────────────────────────

    public function testMoveFile(): void
    {
        $src  = $this->tempDir . 'move_src.txt';
        $dest = $this->tempDir . 'move_dest.txt';
        $this->fm->writeFile($src, 'move me');
        $this->assertTrue($this->fm->moveFile($src, $dest));
        $this->assertFileExists($dest);
        $this->assertFileDoesNotExist($src);
    }

    // ── deleteFile() ─────────────────────────────────────────────────────────

    public function testDeleteFile(): void
    {
        $path = $this->tempDir . 'delete_me.txt';
        $this->fm->writeFile($path, 'bye');
        $this->assertTrue($this->fm->deleteFile($path));
        $this->assertFileDoesNotExist($path);
    }

    public function testDeleteFileReturnsTrueForMissing(): void
    {
        // deleteFile() is idempotent — returns true when file already gone
        $this->assertTrue($this->fm->deleteFile($this->tempDir . 'ghost.txt'));
    }

    // ── ensureDirectoryExists() ───────────────────────────────────────────────

    public function testEnsureDirectoryExists(): void
    {
        $dir = $this->tempDir . 'new/nested/dir/';
        $returned = $this->fm->ensureDirectoryExists($dir);
        $this->assertDirectoryExists($dir);
        $this->assertSame($dir, $returned);
    }

    public function testEnsureDirectoryExistsIdempotent(): void
    {
        $dir = $this->tempDir . 'already/';
        mkdir($dir, 0755, true);
        $this->fm->ensureDirectoryExists($dir); // should not throw
        $this->assertDirectoryExists($dir);
    }

    // ── listFiles() ──────────────────────────────────────────────────────────

    public function testListFiles(): void
    {
        $this->fm->writeFile($this->tempDir . 'a.txt', 'a');
        $this->fm->writeFile($this->tempDir . 'b.txt', 'b');
        $this->fm->writeFile($this->tempDir . 'c.log', 'c');

        $all = $this->fm->listFiles($this->tempDir);
        $this->assertCount(3, $all);
    }

    public function testListFilesFilteredByExtension(): void
    {
        $this->fm->writeFile($this->tempDir . 'a.txt', 'a');
        $this->fm->writeFile($this->tempDir . 'b.php', 'b');

        $txtFiles = $this->fm->listFiles($this->tempDir, false, 'txt');
        $this->assertCount(1, $txtFiles);
        $this->assertStringEndsWith('.txt', $txtFiles[0]);
    }

    public function testListFilesRecursive(): void
    {
        $sub = $this->tempDir . 'sub/';
        mkdir($sub);
        $this->fm->writeFile($this->tempDir . 'root.txt', 'r');
        $this->fm->writeFile($sub . 'child.txt', 'c');

        $all = $this->fm->listFiles($this->tempDir, true);
        $this->assertCount(2, $all);
    }

    // ── getFileInfo() ─────────────────────────────────────────────────────────

    public function testGetFileInfo(): void
    {
        $path = $this->tempDir . 'info.txt';
        $this->fm->writeFile($path, 'some content');

        $info = $this->fm->getFileInfo($path);
        $this->assertIsArray($info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('size', $info);
        $this->assertArrayHasKey('extension', $info);
        $this->assertSame('txt', $info['extension']);
        $this->assertSame(12, $info['size']);
    }

    // ── getMimeType() ─────────────────────────────────────────────────────────

    public function testGetMimeType(): void
    {
        $path = $this->tempDir . 'mime.txt';
        $this->fm->writeFile($path, 'plain text content');
        $mime = $this->fm->getMimeType($path);
        $this->assertStringContainsString('text/', $mime);
    }

    // ── humanFileSize() ──────────────────────────────────────────────────────

    public function testHumanFileSizeBytes(): void
    {
        $this->assertStringContainsString('B', $this->fm->humanFileSize(500));
    }

    public function testHumanFileSizeKilobytes(): void
    {
        $this->assertStringContainsString('KB', $this->fm->humanFileSize(2048));
    }

    public function testHumanFileSizeMegabytes(): void
    {
        $this->assertStringContainsString('MB', $this->fm->humanFileSize(1048576));
    }

    // ── generateSafeFilename() ────────────────────────────────────────────────

    public function testGenerateSafeFilename(): void
    {
        $safe = $this->fm->generateSafeFilename('My File Name (2024).txt');
        // keeps alphanumeric, underscore, hyphen, dot (including uppercase)
        $this->assertMatchesRegularExpression('/^[\w\-\.]+$/', $safe);
        $this->assertStringNotContainsString(' ', $safe);
        $this->assertStringNotContainsString('(', $safe);
    }

    public function testGenerateSafeFilenameNoPathTraversal(): void
    {
        $safe = $this->fm->generateSafeFilename('../../../etc/passwd');
        $this->assertStringNotContainsString('/', $safe);
        $this->assertStringNotContainsString('..', $safe);
    }

    // ── cleanDirectory() ─────────────────────────────────────────────────────

    public function testCleanDirectory(): void
    {
        $dir = $this->tempDir . 'to_clean/';
        mkdir($dir);
        $this->fm->writeFile($dir . 'file1.txt', 'x');
        $this->fm->writeFile($dir . 'file2.txt', 'y');

        $this->assertTrue($this->fm->cleanDirectory($dir));
        $this->assertCount(0, $this->fm->listFiles($dir));
    }
}
