<?php

namespace oihana\commands\traits;

use RuntimeException;

use oihana\commands\enums\ExitCode;
use oihana\commands\options\CommandOptions;
use oihana\files\exceptions\FileException;

use function oihana\commands\helpers\escapeForPrintf;
use function oihana\files\assertFile;

/**
 * Manages file system operations using shell commands.
 *
 * This trait encapsulates common file operations, such as creating and deleting files,
 * by executing underlying system commands (e.g., `rm`, `mkdir`, `tee`). It is designed
 * to be used in contexts where command-line interaction is preferred or necessary,
 * for instance, to handle complex permissions or to execute commands with elevated
 * privileges (`sudo`).
 *
 * It relies on the `CommandTrait` for command execution, thereby inheriting features
 * like `sudo` support, `dryRun` mode for testing, and verbosity control.
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 *
 * @see CommandTrait
 */
trait FileTrait
{
    use CommandTrait ;

    /**
     * Deletes a file.
     *
     * @param string|null               $filePath    The absolute path to the file to create.
     * @param null|array|CommandOptions $options     Optional command options, such as sudo or user context.
     * @param bool                      $verbose     Whether to output verbose logs.
     * @param bool                      $assertable  If true, asserts file existence and permissions before deletion.
     * @param bool                      $sudo        If true, the command is automatically prefixed with `sudo` to run with elevated privileges.
     * @param bool                      $dryRun      If true, simulates the execution without actually running the command. Always returns 0.
     *
     * @throws FileException It the file path is not valid or the file not exist.
     */
    public function deleteFile
    (
       ?string                    $filePath           ,
        null|array|CommandOptions $options    = null  ,
        bool                      $verbose    = false ,
        bool                      $assertable = false ,
        bool                      $sudo       = false ,
        bool                      $dryRun     = false ,
    )
    :int
    {
        if ( $assertable )
        {
            assertFile( $filePath ) ;
        }

        $status = $this->system
        (
            command : "rm -f " . escapeshellarg( $filePath ) ,
            options : $options ,
            silent  : true ,
            verbose : $verbose ,
            sudo    : $sudo ,
            dryRun  : $dryRun ,
        ) ;

        if ( $assertable && $status !== ExitCode::SUCCESS )
        {
            throw new RuntimeException("Failed to delete the file via exec command : $filePath" );
        }

        return ExitCode::SUCCESS ;
    }

    /**
     * Creates a file at the specified path with the given contents.
     * If the parent directory does not exist, it is created.
     *
     * @param string|null               $filePath The absolute path to the file to create.
     * @param string|null               $content  The content to write; if null, an empty file is created@param null|array|CommandOptions $options  Optional command options, such as sudo or user context..
     * @param null|array|CommandOptions $options Optional command options, such as sudo or user context.
     * @param bool                      $verbose  Whether to output verbose logs.
     * @param bool                      $sudo If true, the command is automatically prefixed with `sudo` to run with elevated privileges.
     * @param bool                      $dryRun If true, simulates the execution without actually running the command. Always returns 0.
 *
     * @return int `ExitCode::SUCCESS` (0) if the file creation succeeds.
     *
     * @throws RuntimeException If the directory or file cannot be created.
     *@example
     * ```php
     * $this->createFile
     * (
     *     '/var/www/html/robots.txt',
     *     'User-agent: *' . PHP_EOL . 'Disallow: /private/'
     * );
     * ```
     *
     */
    public function makeFile
    (
        ?string                   $filePath ,
        ?string                   $content  = '' ,
        null|array|CommandOptions $options  = null  ,
        bool                      $verbose  = false ,
        bool                      $sudo     = false ,
        bool                      $dryRun   = false ,
    )
    : int
    {
        if( empty( $filePath ) )
        {
            throw new RuntimeException("Failed to write an empty or null file path" ) ;
        }

        $dir = dirname( $filePath ) ;
        if ( !is_dir( $dir ) )
        {
            $status = $this->system
            (
                command  : "mkdir -p " . escapeshellarg( $dir ) ,
                options  : $options ,
                silent   : true ,
                verbose  : $verbose ,
                sudo     : $sudo ,
                dryRun   : $dryRun
            ) ;

            if ( $status !== ExitCode::SUCCESS )
            {
                throw new RuntimeException("Failed to create directory $dir" );
            }
        }

        $escapedContent  = escapeForPrintf( $content ) ;
        $filePathEscaped = escapeshellarg($filePath);

        $status = $this->system
        (
            command  : "tee $filePathEscaped" ,
            options  : $options ,
            silent   : true ,
            verbose  : $verbose ,
            previous : "printf '%s' $escapedContent" ,
            sudo     : $sudo ,
            dryRun   : $dryRun
        ) ;

        if ( $status !== ExitCode::SUCCESS )
        {
            throw new RuntimeException("Failed to write the file: $filePath" ) ;
        }

        return ExitCode::SUCCESS;
    }
}