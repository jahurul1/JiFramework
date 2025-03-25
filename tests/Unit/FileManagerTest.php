<?php
/**
 * Test case for the FileManager class in the Unit directory
 */

class UnitFileManagerTest extends TestCase
{
    private $testDir;
    private $testFile;
    
    /**
     * Set up the test environment
     */
    public function setUp()
    {
        parent::setUp();
        
        // Create a test directory within the uploads test directory
        $this->testDir = \JIFramework\Config\Config::STORAGE_PATH . 'Uploads/test/file_manager_test/';
        $this->app->fileManager->ensureDirectoryExists($this->testDir);
        
        // Create a test file
        $this->testFile = $this->testDir . 'test_file.txt';
        file_put_contents($this->testFile, 'This is a test file content.');
    }
    
    /**
     * Clean up the test environment
     */
    public function tearDown()
    {
        // Delete the test file if it exists
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
        
        // Delete the test directory
        if (is_dir($this->testDir)) {
            rmdir($this->testDir);
        }
        
        parent::tearDown();
    }
    
    /**
     * Test ensureDirectoryExists method
     */
    public function testEnsureDirectoryExists()
    {
        $nestedDir = $this->testDir . 'nested/path/';
        
        // Test creating a nested directory
        $result = $this->app->fileManager->ensureDirectoryExists($nestedDir);
        
        $this->assertTrue($result, 'Directory creation should return true');
        $this->assertTrue(is_dir($nestedDir), 'Nested directory should be created');
        
        // Clean up
        rmdir($nestedDir);
        rmdir($this->testDir . 'nested/');
    }
    
    /**
     * Test deleteFile method
     */
    public function testDeleteFile()
    {
        // Create another test file
        $fileToDelete = $this->testDir . 'to_delete.txt';
        file_put_contents($fileToDelete, 'This file will be deleted.');
        
        $this->assertTrue(file_exists($fileToDelete), 'File should exist before deletion');
        
        // Delete the file
        $result = $this->app->fileManager->deleteFile($fileToDelete);
        
        $this->assertTrue($result, 'File deletion should return true');
        $this->assertFalse(file_exists($fileToDelete), 'File should not exist after deletion');
    }
    
    /**
     * Test listFiles method
     */
    public function testListFiles()
    {
        // Create additional test files
        file_put_contents($this->testDir . 'file1.txt', 'File 1');
        file_put_contents($this->testDir . 'file2.txt', 'File 2');
        
        // Create a subdirectory with a file
        $subDir = $this->testDir . 'subdir/';
        mkdir($subDir);
        file_put_contents($subDir . 'subfile.txt', 'Subdir file');
        
        // Test non-recursive listing
        $nonRecursiveFiles = $this->app->fileManager->listFiles($this->testDir, false);
        
        $this->assertTrue(is_array($nonRecursiveFiles), 'listFiles should return an array');
        $this->assertTrue(count($nonRecursiveFiles) >= 3, 'Should find at least 3 files in the directory');
        $this->assertTrue(in_array('file1.txt', array_map('basename', $nonRecursiveFiles)), 'Should find file1.txt');
        $this->assertTrue(in_array('file2.txt', array_map('basename', $nonRecursiveFiles)), 'Should find file2.txt');
        $this->assertFalse(in_array('subfile.txt', array_map('basename', $nonRecursiveFiles)), 'Should not find subfile.txt in non-recursive mode');
        
        // Test recursive listing
        $recursiveFiles = $this->app->fileManager->listFiles($this->testDir, true);
        
        $this->assertTrue(is_array($recursiveFiles), 'listFiles should return an array');
        $this->assertTrue(count($recursiveFiles) >= 4, 'Should find at least 4 files in the directory tree');
        $this->assertTrue(in_array('file1.txt', array_map('basename', $recursiveFiles)), 'Should find file1.txt');
        $this->assertTrue(in_array('file2.txt', array_map('basename', $recursiveFiles)), 'Should find file2.txt');
        $this->assertTrue(in_array('subfile.txt', array_map('basename', $recursiveFiles)), 'Should find subfile.txt in recursive mode');
        
        // Clean up
        unlink($this->testDir . 'file1.txt');
        unlink($this->testDir . 'file2.txt');
        unlink($subDir . 'subfile.txt');
        rmdir($subDir);
    }
    
    /**
     * Test handling of non-existent files
     */
    public function testNonExistentFile()
    {
        $nonExistentFile = $this->testDir . 'does_not_exist.txt';
        
        // Test deleting a non-existent file
        $deleteResult = $this->app->fileManager->deleteFile($nonExistentFile);
        $this->assertFalse($deleteResult, 'Deleting a non-existent file should return false');
    }
    
    /**
     * Test ensuring directory exists when it already exists
     */
    public function testExistingDirectory()
    {
        // Directory already exists from setUp
        $result = $this->app->fileManager->ensureDirectoryExists($this->testDir);
        
        $this->assertTrue($result, 'Ensuring an existing directory exists should return true');
        $this->assertTrue(is_dir($this->testDir), 'Directory should still exist');
    }
} 


