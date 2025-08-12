<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\traits\FileTrait;
use oihana\files\exceptions\FileException;
use PHPUnit\Framework\TestCase;

use oihana\commands\enums\ExitCode;
use oihana\commands\options\CommandOptions;
use RuntimeException;

/**
 * Unit tests for FileTrait
 */
class FileTraitTest extends TestCase
{
    private $trait;

    protected function setUp(): void
    {
        $this->trait = new class
        {
            use FileTrait;

            public array $systemCalledWith = [];

            // On mock la méthode system
            public function system
            (
                null|array|string         $command ,
                null|array|string         $args     = null  ,
                null|array|CommandOptions $options  = null  ,
                bool                      $silent   = false ,
                bool                      $verbose  = false ,
                ?string                   $previous = null  ,
                ?string                   $post     = null  ,
                bool                      $sudo     = false ,
                bool                      $dryRun   = false ,
            ): int {
                $this->systemCalledWith[] = func_get_args();

                if ( str_contains( $command, 'fail' ) )
                {
                    return ExitCode::FAILURE;
                }

                return ExitCode::SUCCESS;
            }
        };
    }

    public function testDeleteFileDryRunReturnsSuccessWithoutCallingSystem()
    {
        $result = $this->trait->deleteFile
        (
            '/tmp/testfile.txt',
            null,
            false,
            false,
            false,
            true // dryRun
        );

        $this->assertSame(ExitCode::SUCCESS, $result);
        $this->assertCount(1, $this->trait->systemCalledWith);

        $calledArgs = $this->trait->systemCalledWith[0];

        $this->assertStringContainsString('rm -f', $calledArgs[0]);
        $this->assertTrue($calledArgs[8]); // dryRun param true
    }

    public function testDeleteFileThrowsWhenAssertableAndStatusNotSuccess()
    {
        $this->expectException( FileException::class ) ;
        $this->trait->deleteFile('/tmp/testfile.txt', null, false, true);
    }

    public function testMakeFileThrowsWhenFilePathEmpty()
    {
        $this->expectException( RuntimeException::class ) ;
        $this->expectExceptionMessage('Failed to write an empty or null file path');
        $this->trait->makeFile(null);
    }

    public function testMakeFileCreatesDirectoryAndFileSuccessfully()
    {
        $result = $this->trait->makeFile
        (
            '/tmp/testdir/testfile.txt',
            'Hello World'
        );

        $this->assertSame(ExitCode::SUCCESS, $result);

        // Vérifie que mkdir et tee ont été appelés
        $commands = array_column($this->trait->systemCalledWith, 0);
        $this->assertStringContainsString('mkdir -p', $commands[0]);
        $this->assertStringContainsString('tee', $commands[1]);
    }

    public function testMakeFileThrowsIfDirectoryCreationFails()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to create directory/');

        // On modifie la méthode system pour simuler échec mkdir
        $this->trait = new class {
            use FileTrait;
            public int $callCount = 0;
            public function system
            (
                null|array|string         $command ,
                null|array|string         $args     = null  ,
                null|array|CommandOptions $options  = null  ,
                bool                      $silent   = false ,
                bool                      $verbose  = false ,
                ?string                   $previous = null  ,
                ?string                   $post     = null  ,
                bool                      $sudo     = false ,
                bool                      $dryRun   = false ,
            ): int
            {
                $this->callCount++;
                if ($this->callCount === 1 && str_starts_with($command, 'mkdir'))
                {
                    return ExitCode::FAILURE ;
                }
                return ExitCode::SUCCESS;
            }
        };

        $this->trait->makeFile('/tmp/faildir/testfile.txt', 'content');
    }


    public function testMakeFileThrowsIfFileWriteFails()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to write the file/');

        $this->trait = new class
        {
            use FileTrait ;
            public int $callCount = 0;
            public function system
            (
                null|array|string         $command ,
                null|array|string         $args     = null  ,
                null|array|CommandOptions $options  = null  ,
                bool                      $silent   = false ,
                bool                      $verbose  = false ,
                ?string                   $previous = null  ,
                ?string                   $post     = null  ,
                bool                      $sudo     = false ,
                bool                      $dryRun   = false ,
            ): int {
                $this->callCount++ ;

                if ( $this->callCount === 1 && str_starts_with($command, 'mkdir') )
                {
                    return ExitCode::SUCCESS;
                }
                // tee fail
                if ( $this->callCount === 2 && str_starts_with($command, 'tee'))
                {
                    return ExitCode::FAILURE ;
                }
                return ExitCode::SUCCESS;
            }
        };

        $this->trait->makeFile('/tmp/dir/testfile.txt', 'content');
    }
}