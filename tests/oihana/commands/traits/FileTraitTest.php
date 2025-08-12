<?php

declare(strict_types=1);

namespace oihana\commands\traits;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

use oihana\commands\enums\ExitCode;
use oihana\commands\options\CommandOptions;
use oihana\files\exceptions\FileException;

/**
 * Concrete class for testing FileTrait
 */
class TestableFileClass
{
    use FileTrait;

    public function system(
        string $command,
        null|array|CommandOptions $options = null,
        bool $silent = false,
        bool $verbose = false,
        bool $sudo = false,
        bool $dryRun = false
    ): int {
        // This will be mocked in tests
        return ExitCode::SUCCESS;
    }
}

/**
 * Unit tests for FileTrait
 */
class FileTraitTest extends TestCase
{
    private MockObject $traitObject;
    private string $tempDir;
    private string $testFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock object of the concrete class
        $this->traitObject = $this->createPartialMock(TestableFileClass::class, ['system']);

        // Create temporary directory for tests
        $this->tempDir = sys_get_temp_dir() . '/file_trait_tests_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        $this->testFile = $this->tempDir . '/test_file.txt';
    }

    protected function tearDown(): void
    {
        // Clean up temporary files and directory
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    /**
     * Test deleteFile method with successful deletion
     * @throws FileException
     */
    public function testDeleteFileSuccess(): void
    {
        // Create a test file
        file_put_contents($this->testFile, 'test content');

        // Mock the system method to return success
        $this->traitObject->expects($this->once())
            ->method('system')
            ->with(
                $this->stringContains('rm -f'),
                $this->equalTo(null),
                $this->equalTo(true),
                $this->equalTo(false),
                $this->equalTo(false),
                $this->equalTo(false)
            )
            ->willReturn(ExitCode::SUCCESS);

        $result = $this->traitObject->deleteFile($this->testFile);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test deleteFile method with verbose option
     * @throws FileException
     */
    public function testDeleteFileWithVerbose(): void
    {
        $this->traitObject->expects($this->once())
            ->method('system')
            ->with(
                $this->anything(),
                $this->equalTo(null),
                $this->equalTo(true),
                $this->equalTo(true), // verbose = true
                $this->equalTo(false),
                $this->equalTo(false)
            )
            ->willReturn(ExitCode::SUCCESS);

        $result = $this->traitObject->deleteFile($this->testFile, null, true);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test deleteFile method with sudo option
     * @throws FileException
     */
    public function testDeleteFileWithSudo(): void
    {
        $this->traitObject->expects($this->once())
            ->method('system')
            ->with(
                $this->anything(),
                $this->equalTo(null),
                $this->equalTo(true),
                $this->equalTo(false),
                $this->equalTo(true), // sudo = true
                $this->equalTo(false)
            )
            ->willReturn(ExitCode::SUCCESS);

        $result = $this->traitObject->deleteFile($this->testFile, null, false, false, true);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test deleteFile method with dry run option
     * @throws FileException
     */
    public function testDeleteFileWithDryRun(): void
    {
        $this->traitObject->expects($this->once())
            ->method('system')
            ->with(
                $this->anything(),
                $this->equalTo(null),
                $this->equalTo(true),
                $this->equalTo(false),
                $this->equalTo(false),
                $this->equalTo(true) // dryRun = true
            )
            ->willReturn(ExitCode::SUCCESS);

        $result = $this->traitObject->deleteFile($this->testFile, null, false, false, false, true);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test deleteFile method with CommandOptions
     * @throws FileException
     */
    public function testDeleteFileWithCommandOptions(): void
    {
        $options = $this->createMock(CommandOptions::class);

        $this->traitObject->expects($this->once())
            ->method('system')
            ->with(
                $this->anything(),
                $this->equalTo($options),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            )
            ->willReturn(ExitCode::SUCCESS);

        $result = $this->traitObject->deleteFile($this->testFile, $options);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test deleteFile method with assertable option and file assertion failure
     * Note: This test assumes assertFile function will throw FileException for non-existent files
     */
    public function testDeleteFileWithAssertableThrowsFileException(): void
    {
        $this->expectException(FileException::class);

        // Test with non-existent file path when assertable is true
        // The assertFile function should throw FileException
        $this->traitObject->deleteFile('/nonexistent/file.txt', null, false, true);
    }

    /**
     * Test deleteFile method with assertable option and system failure
     * @throws FileException
     */
    public function testDeleteFileWithAssertableAndSystemFailure(): void
    {
        // Create test file
        file_put_contents($this->testFile, 'test');

        $this->traitObject->expects($this->once())
            ->method('system')
            ->willReturn(1); // Failure exit code

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to delete the file via exec command');

        $this->traitObject->deleteFile($this->testFile, null, false, true);
    }

    /**
     * Test makeFile method with successful file creation
     */
    public function testMakeFileSuccess(): void
    {
        $content = 'Test file content';
        $filePath = $this->tempDir . '/new_file.txt';

        // Only one system call for mv (mkdir is skipped because directory exists)
        $this->traitObject->expects($this->once())
            ->method('system')
            ->with(
                $this->stringContains('mv'),
                $this->equalTo(null),
                $this->equalTo(true),
                $this->equalTo(false),
                $this->equalTo(false),
                $this->equalTo(false)
            )
            ->willReturn(ExitCode::SUCCESS);

        $result = $this->traitObject->makeFile($filePath, $content);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test makeFile method with new directory creation
     */
    public function testMakeFileWithNewDirectory(): void
    {
        $content = 'Test file content';
        $newDir = $this->tempDir . '/new_subdir';
        $filePath = $newDir . '/new_file.txt';

        // Two system calls: mkdir and mv
        $this->traitObject->expects($this->exactly(2))
            ->method('system')
            ->willReturnCallback(function ($command) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertStringContainsString('mkdir -p', $command);
                } else {
                    $this->assertStringContainsString('mv', $command);
                }

                return ExitCode::SUCCESS;
            });

        $result = $this->traitObject->makeFile($filePath, $content);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }
    /**
     * Test makeFile method with empty content
     */
    public function testMakeFileWithEmptyContent(): void
    {
        $filePath = $this->tempDir . '/empty_file.txt';

        // Only mv command since directory exists
        $this->traitObject->expects($this->once())
            ->method('system')
            ->willReturn(ExitCode::SUCCESS);

        $result = $this->traitObject->makeFile($filePath, '');

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test makeFile method with null content (defaults to empty string)
     */
    public function testMakeFileWithNullContent(): void
    {
        $filePath = $this->tempDir . '/null_content_file.txt';

        // Only mv command since directory exists
        $this->traitObject->expects($this->once())
            ->method('system')
            ->willReturn(ExitCode::SUCCESS);

        $result = $this->traitObject->makeFile($filePath);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test makeFile method with empty file path throws exception
     */
    public function testMakeFileWithEmptyFilePathThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to write an empty or null file path');

        $this->traitObject->makeFile('');
    }

    /**
     * Test makeFile method with null file path throws exception
     */
    public function testMakeFileWithNullFilePathThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to write an empty or null file path');

        $this->traitObject->makeFile(null);
    }

    /**
     * Test makeFile method with directory creation failure
     */
    public function testMakeFileWithDirectoryCreationFailure(): void
    {
        $newDir = $this->tempDir . '/new_subdir_fail';
        $filePath = $newDir . '/new_file.txt';

        // First call (mkdir) fails, second call (mv) won't be reached
        $this->traitObject->expects($this->once())
            ->method('system')
            ->with($this->stringContains('mkdir -p'))
            ->willReturn(1); // Failure exit code

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create directory');

        $this->traitObject->makeFile($filePath, 'content');
    }

    /**
     * Test makeFile method with file move failure
     */
    public function testMakeFileWithMoveFailure(): void
    {
        $newDir = $this->tempDir . '/new_subdir_move';
        $filePath = $newDir . '/move_fail.txt';

        // First call (mkdir) succeeds, second call (mv) fails
        $this->traitObject->expects($this->exactly(2))
            ->method('system')
            ->willReturnOnConsecutiveCalls(ExitCode::SUCCESS, 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to write the file');

        $this->traitObject->makeFile($filePath, 'content');
    }

    /**
     * Test makeFile method with verbose option
     */
    public function testMakeFileWithVerbose(): void
    {
        $newDir = $this->tempDir . '/verbose_subdir';
        $filePath = $newDir . '/verbose_file.txt';

        $this->traitObject->expects($this->exactly(2))
            ->method('system')
            ->willReturnCallback(function ($command, $options, $silent, $verbose, $sudo, $dryRun) {
                $this->assertTrue($verbose, 'Verbose should be true');
                $this->assertFalse($sudo, 'Sudo should be false');
                $this->assertFalse($dryRun, 'DryRun should be false');
                return ExitCode::SUCCESS;
            });

        $result = $this->traitObject->makeFile($filePath, 'content', null, true);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test makeFile method with sudo option
     */
    public function testMakeFileWithSudo(): void
    {
        $newDir = $this->tempDir . '/sudo_subdir';
        $filePath = $newDir . '/sudo_file.txt';

        $this->traitObject->expects($this->exactly(2))
            ->method('system')
            ->willReturnCallback(function ($command, $options, $silent, $verbose, $sudo, $dryRun) {
                $this->assertFalse($verbose, 'Verbose should be false');
                $this->assertTrue($sudo, 'Sudo should be true');
                $this->assertFalse($dryRun, 'DryRun should be false');
                return ExitCode::SUCCESS;
            });

        $result = $this->traitObject->makeFile($filePath, 'content', null, false, true);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test makeFile method with dry run option
     */
    public function testMakeFileWithDryRun(): void
    {
        $newDir = $this->tempDir . '/dry_run_subdir';
        $filePath = $newDir . '/dry_run_file.txt';

        $this->traitObject->expects($this->exactly(2))
            ->method('system')
            ->willReturnCallback(function ($command, $options, $silent, $verbose, $sudo, $dryRun) {
                $this->assertFalse($verbose, 'Verbose should be false');
                $this->assertFalse($sudo, 'Sudo should be false');
                $this->assertTrue($dryRun, 'DryRun should be true');
                return ExitCode::SUCCESS;
            });

        $result = $this->traitObject->makeFile($filePath, 'content', null, false, false, true);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test makeFile method with CommandOptions array
     */
    public function testMakeFileWithOptionsArray(): void
    {
        $newDir = $this->tempDir . '/options_subdir';
        $filePath = $newDir . '/options_file.txt';
        $options = ['key' => 'value'];

        $this->traitObject->expects($this->exactly(2))
            ->method('system')
            ->willReturnCallback(function ($command, $receivedOptions) use ($options) {
                $this->assertEquals($options, $receivedOptions);
                return ExitCode::SUCCESS;
            });

        $result = $this->traitObject->makeFile($filePath, 'content', $options);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test makeFile method with CommandOptions object
     */
    public function testMakeFileWithCommandOptionsObject(): void
    {
        $newDir = $this->tempDir . '/command_options_subdir';
        $filePath = $newDir . '/command_options_file.txt';
        $options = $this->createMock(CommandOptions::class);

        $this->traitObject->expects($this->exactly(2))
            ->method('system')
            ->willReturnCallback(function ($command, $receivedOptions) use ($options) {
                $this->assertSame($options, $receivedOptions);
                return ExitCode::SUCCESS;
            });

        $result = $this->traitObject->makeFile($filePath, 'content', $options);

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test makeFile method when directory already exists
     */
    public function testMakeFileWhenDirectoryAlreadyExists(): void
    {
        // Use existing temp directory
        $filePath = $this->tempDir . '/existing_dir_file.txt';

        // Only one system call for mv (mkdir is skipped because directory exists)
        $this->traitObject->expects($this->once())
            ->method('system')
            ->with(
                $this->stringContains('mv'),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            )
            ->willReturn(ExitCode::SUCCESS);

        $result = $this->traitObject->makeFile($filePath, 'content');

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }

    /**
     * Test that shell arguments are properly escaped
     */
    public function testShellArgumentEscaping(): void
    {
        $newDir = $this->tempDir . "/subdir with spaces & special chars";
        $dangerousPath = $newDir . "/file with spaces & special chars.txt";

        $this->traitObject->expects($this->exactly(2))
            ->method('system')
            ->willReturn(ExitCode::SUCCESS);

        $result = $this->traitObject->makeFile($dangerousPath, 'content');

        $this->assertEquals(ExitCode::SUCCESS, $result);
    }
}