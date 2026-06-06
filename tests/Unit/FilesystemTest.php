<?php

declare(strict_types=1);

namespace Switon\Core\Tests\Unit;

use Switon\Core\Exception\FileNotFoundException;
use Switon\Core\Exception\RuntimeException;
use Switon\Core\Filesystem;
use Switon\Core\PathAliasInterface;
use Switon\Core\Tests\TestCase;

/**
 * Filesystem behaviour (real temp dirs + {@see PathAliasInterface}); runs under unit suite for coverage.
 */
class FilesystemTest extends TestCase
{
    protected Filesystem $filesystem;
    protected PathAliasInterface $pathAlias;
    protected string $testDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->container->get(Filesystem::class);
        $this->pathAlias = $this->container->get(PathAliasInterface::class);

        $this->testDir = sys_get_temp_dir() . '/switon_test_' . bin2hex(random_bytes(8));
        $this->pathAlias->set('@test', $this->testDir);
        @mkdir($this->testDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            $this->removeDirectory($this->testDir);
        }
        parent::tearDown();
    }

    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testExistsChecksFileExistence(): void
    {
        $testFile = $this->testDir . '/test.txt';
        file_put_contents($testFile, 'test');

        $this->assertTrue($this->filesystem->exists('@test/test.txt'));
        $this->assertFalse($this->filesystem->exists('@test/nonexistent.txt'));
    }

    public function testExistsChecksDirectoryExistence(): void
    {
        $this->assertTrue($this->filesystem->exists('@test'));
        $this->assertFalse($this->filesystem->exists('@test/nonexistent'));
    }

    public function testSizeReturnsFileSize(): void
    {
        $testFile = $this->testDir . '/test.txt';
        $content = 'test content';
        file_put_contents($testFile, $content);

        $this->assertSame(strlen($content), $this->filesystem->size('@test/test.txt'));
    }

    public function testSizeReturnsNullForNonExistentFile(): void
    {
        $this->assertNull($this->filesystem->size('@test/nonexistent.txt'));
    }

    public function testWriteCreatesFile(): void
    {
        $this->filesystem->write('@test/test.txt', 'test content');

        $this->assertTrue($this->filesystem->exists('@test/test.txt'));
        $this->assertSame('test content', file_get_contents($this->testDir . '/test.txt'));
    }

    public function testReadReadsFileContent(): void
    {
        $content = 'test content';
        file_put_contents($this->testDir . '/test.txt', $content);

        $this->assertSame($content, $this->filesystem->read('@test/test.txt'));
    }

    public function testMkdirCreatesDirectory(): void
    {
        $this->filesystem->mkdir('@test/newdir');

        $this->assertTrue($this->filesystem->exists('@test/newdir'));
        $this->assertDirectoryExists($this->testDir . '/newdir');
    }

    public function testDeleteRemovesFile(): void
    {
        $testFile = $this->testDir . '/test.txt';
        file_put_contents($testFile, 'test');

        $this->filesystem->delete('@test/test.txt');
        $this->assertFalse($this->filesystem->exists('@test/test.txt'));
    }

    public function testAppendAppendsDataToFile(): void
    {
        $this->filesystem->write('@test/test.txt', 'initial');
        $this->filesystem->append('@test/test.txt', ' appended');

        $this->assertSame('initial appended', $this->filesystem->read('@test/test.txt'));
    }

    public function testAppendCreatesFileIfNotExists(): void
    {
        $this->filesystem->append('@test/new.txt', 'content');

        $this->assertSame('content', $this->filesystem->read('@test/new.txt'));
    }

    public function testMoveMovesFile(): void
    {
        $this->filesystem->write('@test/source.txt', 'content');
        $this->filesystem->move('@test/source.txt', '@test/dest.txt');

        $this->assertFalse($this->filesystem->exists('@test/source.txt'));
        $this->assertTrue($this->filesystem->exists('@test/dest.txt'));
        $this->assertSame('content', $this->filesystem->read('@test/dest.txt'));
    }

    public function testMoveOverwritesWhenEnabled(): void
    {
        $this->filesystem->write('@test/source.txt', 'new content');
        $this->filesystem->write('@test/dest.txt', 'old content');
        $this->filesystem->move('@test/source.txt', '@test/dest.txt', true);

        $this->assertSame('new content', $this->filesystem->read('@test/dest.txt'));
    }

    public function testIsDirChecksIfPathIsDirectory(): void
    {
        $this->filesystem->mkdir('@test/subdir');
        $this->filesystem->write('@test/file.txt', 'content');

        $this->assertTrue($this->filesystem->isDir('@test/subdir'));
        $this->assertFalse($this->filesystem->isDir('@test/file.txt'));
    }

    public function testRmdirRemovesDirectory(): void
    {
        $this->filesystem->mkdir('@test/subdir');
        $this->filesystem->write('@test/subdir/file.txt', 'content');

        $this->filesystem->rmdir('@test/subdir');

        $this->assertFalse($this->filesystem->exists('@test/subdir'));
    }

    public function testRmdirSilentlyReturnsIfNotExists(): void
    {
        $this->assertFalse($this->filesystem->exists('@test/nonexistent'));
        $this->filesystem->rmdir('@test/nonexistent');
        $this->assertFalse($this->filesystem->exists('@test/nonexistent'));
    }

    public function testCopyCopiesFile(): void
    {
        $this->filesystem->write('@test/source.txt', 'content');
        $this->filesystem->copy('@test/source.txt', '@test/dest.txt');

        $this->assertTrue($this->filesystem->exists('@test/source.txt'));
        $this->assertTrue($this->filesystem->exists('@test/dest.txt'));
        $this->assertSame('content', $this->filesystem->read('@test/dest.txt'));
    }

    public function testCopyCopiesDirectoryRecursively(): void
    {
        $this->filesystem->mkdir('@test/source');
        $this->filesystem->write('@test/source/file1.txt', 'content1');
        $this->filesystem->write('@test/source/file2.txt', 'content2');
        $this->filesystem->mkdir('@test/source/subdir');
        $this->filesystem->write('@test/source/subdir/file3.txt', 'content3');

        $this->filesystem->copy('@test/source', '@test/dest');

        $this->assertTrue($this->filesystem->exists('@test/dest/file1.txt'));
        $this->assertTrue($this->filesystem->exists('@test/dest/file2.txt'));
        $this->assertTrue($this->filesystem->exists('@test/dest/subdir/file3.txt'));
        $this->assertSame('content1', $this->filesystem->read('@test/dest/file1.txt'));
        $this->assertSame('content3', $this->filesystem->read('@test/dest/subdir/file3.txt'));
    }

    public function testCopyOverwritesWhenEnabled(): void
    {
        $this->filesystem->write('@test/source.txt', 'new content');
        $this->filesystem->write('@test/dest.txt', 'old content');
        $this->filesystem->copy('@test/source.txt', '@test/dest.txt', true);

        $this->assertSame('new content', $this->filesystem->read('@test/dest.txt'));
    }

    public function testGlobFindsMatchingFiles(): void
    {
        $this->filesystem->write('@test/file1.txt', 'content');
        $this->filesystem->write('@test/file2.txt', 'content');
        $this->filesystem->write('@test/other.log', 'content');

        $results = $this->filesystem->glob('@test/*.txt');
        $this->assertCount(2, $results);
    }

    public function testFilesReturnsOnlyFiles(): void
    {
        $this->filesystem->write('@test/file1.txt', 'content');
        $this->filesystem->write('@test/file2.txt', 'content');
        $this->filesystem->mkdir('@test/subdir');

        $files = $this->filesystem->files('@test');
        $this->assertCount(2, $files);
        foreach ($files as $file) {
            $this->assertFileExists($file);
        }
    }

    public function testDirectoriesReturnsOnlyDirectories(): void
    {
        $this->filesystem->mkdir('@test/dir1');
        $this->filesystem->mkdir('@test/dir2');
        $this->filesystem->write('@test/file.txt', 'content');

        $dirs = $this->filesystem->directories('@test');
        $this->assertGreaterThanOrEqual(2, count($dirs));
        foreach ($dirs as $dir) {
            $this->assertDirectoryExists($dir);
        }
    }

    public function testListReturnsDirectoryContents(): void
    {
        $this->filesystem->write('@test/file1.txt', 'content');
        $this->filesystem->write('@test/file2.txt', 'content');
        $this->filesystem->mkdir('@test/subdir');

        $items = $this->filesystem->list('@test');
        $this->assertCount(3, $items);
        $this->assertContains('file1.txt', $items);
        $this->assertContains('file2.txt', $items);
        $this->assertContains('subdir', $items);
    }

    public function testMtimeReturnsModificationTime(): void
    {
        $this->filesystem->write('@test/file.txt', 'content');
        $mtime = $this->filesystem->mtime('@test/file.txt');

        $this->assertIsInt($mtime);
        $this->assertGreaterThan(0, $mtime);
    }

    public function testMtimeReturnsNullForNonExistentFile(): void
    {
        $this->assertNull($this->filesystem->mtime('@test/nonexistent.txt'));
    }

    public function testChmodChangesFilePermissions(): void
    {
        $this->filesystem->write('@test/file.txt', 'content');
        $this->filesystem->chmod('@test/file.txt', 0644);

        $perms = fileperms($this->testDir . '/file.txt') & 0777;
        $this->assertSame(0644, $perms);
    }

    public function testDeleteHandlesWildcardPatterns(): void
    {
        $this->filesystem->write('@test/file1.txt', 'content');
        $this->filesystem->write('@test/file2.txt', 'content');
        $this->filesystem->write('@test/other.log', 'content');

        $this->filesystem->delete('@test/*.txt');

        $this->assertFalse($this->filesystem->exists('@test/file1.txt'));
        $this->assertFalse($this->filesystem->exists('@test/file2.txt'));
        $this->assertTrue($this->filesystem->exists('@test/other.log'));
    }

    public function testReadThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->filesystem->read('@test/nonexistent.txt');
    }

    public function testMoveThrowsExceptionWhenDestinationExists(): void
    {
        $this->filesystem->write('@test/source.txt', 'content');
        $this->filesystem->write('@test/dest.txt', 'existing');

        $this->expectException(RuntimeException::class);
        $this->filesystem->move('@test/source.txt', '@test/dest.txt');
    }

    public function testCopyThrowsExceptionWhenDestinationExists(): void
    {
        $this->filesystem->write('@test/source.txt', 'content');
        $this->filesystem->write('@test/dest.txt', 'existing');

        $this->expectException(RuntimeException::class);
        $this->filesystem->copy('@test/source.txt', '@test/dest.txt');
    }

    public function testCopyThrowsExceptionWhenSourceDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->filesystem->copy('@test/nonexistent.txt', '@test/dest.txt');
    }

    public function testListThrowsExceptionForNonExistentDirectory(): void
    {
        $this->expectException(RuntimeException::class);
        $this->filesystem->list('@test/nonexistent');
    }

    public function testListThrowsWhenPathIsAFile(): void
    {
        $this->filesystem->write('@test/not-a-dir.txt', 'x');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to list directory');

        $this->filesystem->list('@test/not-a-dir.txt');
    }

    public function testChmodThrowsWhenPathDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to chmod');

        $this->filesystem->chmod('@test/definitely-missing.bin', 0644);
    }

    public function testMkdirHandlesExistingDirectory(): void
    {
        $this->filesystem->mkdir('@test/existing');
        $this->filesystem->mkdir('@test/existing');
        $this->assertTrue($this->filesystem->exists('@test/existing'));
    }

    public function testMkdirCreatesNestedDirectories(): void
    {
        $this->filesystem->mkdir('@test/level1/level2/level3');

        $this->assertTrue($this->filesystem->exists('@test/level1'));
        $this->assertTrue($this->filesystem->exists('@test/level1/level2'));
        $this->assertTrue($this->filesystem->exists('@test/level1/level2/level3'));
    }

    public function testMoveAppendsBasenameWhenDestinationEndsWithSlash(): void
    {
        $this->filesystem->write('@test/source.txt', 'content');
        $this->filesystem->mkdir('@test/destdir');
        $this->filesystem->move('@test/source.txt', '@test/destdir/');

        $this->assertTrue($this->filesystem->exists('@test/destdir/source.txt'));
        $this->assertFalse($this->filesystem->exists('@test/source.txt'));
    }

    public function testGlobHandlesOnlyDirFlag(): void
    {
        $this->filesystem->write('@test/file.txt', 'content');
        $this->filesystem->mkdir('@test/subdir');

        $results = $this->filesystem->glob('@test/*', GLOB_ONLYDIR);
        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $result) {
            $this->assertDirectoryExists($result);
        }
    }

    public function testFilesHandlesWildcardPatterns(): void
    {
        $this->filesystem->write('@test/file1.txt', 'content');
        $this->filesystem->write('@test/file2.txt', 'content');
        $this->filesystem->write('@test/other.log', 'content');

        $files = $this->filesystem->files('@test/*.txt');
        $this->assertCount(2, $files);
    }

    public function testDirectoriesHandlesWildcardPatterns(): void
    {
        $this->filesystem->mkdir('@test/dir1');
        $this->filesystem->mkdir('@test/dir2');
        $this->filesystem->write('@test/file.txt', 'content');

        $dirs = $this->filesystem->directories('@test/dir*');
        $this->assertGreaterThanOrEqual(2, count($dirs));
    }

    public function testCopyCreatesParentDirectories(): void
    {
        $this->filesystem->write('@test/source.txt', 'content');
        $this->filesystem->copy('@test/source.txt', '@test/nested/path/dest.txt');

        $this->assertTrue($this->filesystem->exists('@test/nested/path/dest.txt'));
        $this->assertSame('content', $this->filesystem->read('@test/nested/path/dest.txt'));
    }

    public function testCopyHandlesNestedSubdirectories(): void
    {
        $this->filesystem->mkdir('@test/source/level1/level2');
        $this->filesystem->write('@test/source/file1.txt', 'content1');
        $this->filesystem->write('@test/source/level1/file2.txt', 'content2');
        $this->filesystem->write('@test/source/level1/level2/file3.txt', 'content3');

        $this->filesystem->copy('@test/source', '@test/dest');

        $this->assertTrue($this->filesystem->exists('@test/dest/file1.txt'));
        $this->assertTrue($this->filesystem->exists('@test/dest/level1/file2.txt'));
        $this->assertTrue($this->filesystem->exists('@test/dest/level1/level2/file3.txt'));
    }

    public function testCopyHandlesOverwriteForDirectories(): void
    {
        $this->filesystem->mkdir('@test/source/subdir');
        $this->filesystem->write('@test/source/subdir/file.txt', 'new content');
        $this->filesystem->mkdir('@test/dest/subdir');
        $this->filesystem->write('@test/dest/subdir/file.txt', 'old content');

        $this->filesystem->copy('@test/source', '@test/dest', true);

        $this->assertSame('new content', $this->filesystem->read('@test/dest/subdir/file.txt'));
    }

    public function testRmdirHandlesNestedDirectories(): void
    {
        $this->filesystem->mkdir('@test/level1/level2/level3');
        $this->filesystem->write('@test/level1/file1.txt', 'content');
        $this->filesystem->write('@test/level1/level2/file2.txt', 'content');
        $this->filesystem->write('@test/level1/level2/level3/file3.txt', 'content');

        $this->filesystem->rmdir('@test/level1');

        $this->assertFalse($this->filesystem->exists('@test/level1'));
    }

    public function testListHandlesDifferentSortingOrders(): void
    {
        $this->filesystem->write('@test/a.txt', 'content');
        $this->filesystem->write('@test/b.txt', 'content');
        $this->filesystem->write('@test/c.txt', 'content');

        $asc = $this->filesystem->list('@test');
        $desc = $this->filesystem->list('@test', SCANDIR_SORT_DESCENDING);
        $none = $this->filesystem->list('@test', SCANDIR_SORT_NONE);

        $this->assertCount(3, $asc);
        $this->assertCount(3, $desc);
        $this->assertCount(3, $none);
    }

    public function testGlobReturnsEmptyForNonMatchingPattern(): void
    {
        $this->filesystem->write('@test/file.txt', 'content');

        $results = $this->filesystem->glob('@test/*.log');
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testSizeReturnsCorrectSizeForFiles(): void
    {
        $content1 = str_repeat('a', 100);
        $content2 = str_repeat('b', 1000);

        $this->filesystem->write('@test/file1.txt', $content1);
        $this->filesystem->write('@test/file2.txt', $content2);

        $this->assertSame(100, $this->filesystem->size('@test/file1.txt'));
        $this->assertSame(1000, $this->filesystem->size('@test/file2.txt'));
    }

    public function testWriteCreatesParentDirectories(): void
    {
        $this->filesystem->write('@test/nested/path/file.txt', 'content');

        $this->assertTrue($this->filesystem->exists('@test/nested/path/file.txt'));
        $this->assertSame('content', $this->filesystem->read('@test/nested/path/file.txt'));
    }

    public function testAppendCreatesParentDirectories(): void
    {
        $this->filesystem->append('@test/nested/path/file.txt', 'content');

        $this->assertTrue($this->filesystem->exists('@test/nested/path/file.txt'));
        $this->assertSame('content', $this->filesystem->read('@test/nested/path/file.txt'));
    }
}
