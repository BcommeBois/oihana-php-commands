<?php

namespace oihana\commands\traits;

use RuntimeException;

use oihana\commands\enums\ExitCode;
use oihana\commands\options\CommandOptions;
use oihana\files\exceptions\FileException;

use function oihana\files\assertFile;

/**
 * The trait to manage files.
 *
 * @package oihana\commands\traits
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
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

        $tmpFile = tempnam( sys_get_temp_dir() , 'oihana_php_command_make_file' ) ;

        if ( $tmpFile === false )
        {
            throw new RuntimeException("Failed to create temporary file" ) ;
        }

        if ( file_put_contents( $tmpFile , $content ) === false )
        {
            throw new RuntimeException("Failed to write content to temporary file $tmpFile" ) ;
        }

        chmod( $tmpFile , 0644 ) ;

        $tmpFileEscaped  = escapeshellarg($tmpFile);
        $filePathEscaped = escapeshellarg($filePath);

        $status = $this->system
        (
            command  : "mv $tmpFileEscaped $filePathEscaped" ,
            options  : $options ,
            silent   : true ,
            verbose  : $verbose ,
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