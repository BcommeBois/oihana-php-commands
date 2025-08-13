<?php

namespace oihana\commands\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Standard UNIX exit codes
 *
 * The exit status of an executed command is the value returned by the waitpid system call or equivalent function.
 *
 * Exit statuses fall between 0 and 255, though, as explained below, the shell may use values above 125 specially.
 * Exit statuses from shell builtins and compound commands are also limited to this range.
 * Under certain circumstances, the shell will use special values to indicate specific failure modes.
 *
 * @see https://tldp.org/LDP/abs/html/exitcodes.html
 * @see https://www.gnu.org/software/bash/manual/html_node/Exit-Status.html
 * @see https://man7.org/linux/man-pages/man7/signal.7.html
 *
 * @package oihana\commands\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 */
class ExitCode
{
    use ConstantsTrait ;

    // ─────────────────────────────
    // Success & General Errors
    // ─────────────────────────────

    /**
     * Command succeed.
     */
    public const int SUCCESS = 0 ;

    /**
     * Catchall for general errors.
     * Miscellaneous errors, such as "divide by zero" and other impermissible operations
     */
    public const int FAILURE = 1 ;

    /**
     * Misuse of shell builtins.
     * Missing keyword or command, or permission problem (and diff return code on a failed binary file comparison).
     */
    public const int INVALID = 2 ;

    // ─────────────────────────────
    // Command Execution Errors
    // ─────────────────────────────

    /**
     * Command invoked cannot execute.
     * Permission problem or command is not an executable.
     */
    public const int CANNOT_EXECUTE = 126;

    /**
     * Command not found.
     * Possible problem with $PATH or a typo
     */
    public const int COMMAND_NOT_FOUND = 127 ;

    /**
     * Invalid argument to exit
     * Exit takes only integer args in the range 0 - 255 (see first footnote)
     */
    public const int INVALID_ARGUMENT_TO_EXIT = 128 ;

    /**
     * Fatal error signal "n".
     * Signal numbers start at 1; 128 + n indicates termination by signal.
     * For example, 130 = script terminated by Control-C (SIGINT).
     */
    public const int TERMINATED_BY_SIGNAL = 128 ; // Base value, add signal number

    /**
     * Exit status out of range.
     * Exit status codes above 255 are usually truncated modulo 256.
     */
    public const int EXIT_STATUS_OUT_OF_RANGE = 255;

    // ─────────────────────────────
    // Signals (128 + signal number)
    // ─────────────────────────────
    // See `man 7 signal` for details.

    /**
     * Hangup detected on controlling terminal or death of controlling process.
     */
    public const int SIGHUP = 129;

    /**
     * Interrupt from keyboard (Control-C).
     */
    public const int SIGINT = 130;

    /**
     * Quit from keyboard (often Control-\).
     */
    public const int SIGQUIT = 131;

    /**
     * Illegal instruction.
     */
    public const int SIGILL = 132;

    /**
     * Trace/breakpoint trap.
     */
    public const int SIGTRAP = 133;

    /**
     * Abort signal from abort(3).
     */
    public const int SIGABRT = 134;

    /**
     * Bus error (bad memory access).
     */
    public const int SIGBUS = 135;

    /**
     * Floating-point exception.
     */
    public const int SIGFPE = 136;

    /**
     * Kill signal (cannot be caught or ignored).
     */
    public const int SIGKILL = 137;

    /**
     * User-defined signal 1.
     */
    public const int SIGUSR1 = 138;

    /**
     * Invalid memory reference (segmentation fault).
     */
    public const int SIGSEGV = 139;

    /**
     * User-defined signal 2.
     */
    public const int SIGUSR2 = 140;

    /**
     * Broken pipe: write to pipe with no readers.
     */
    public const int SIGPIPE = 141;

    /**
     * Alarm clock (timer signal from alarm(2)).
     */
    public const int SIGALRM = 142;

    /**
     * Termination signal.
     */
    public const int SIGTERM = 143;

    /**
     * Stack fault on coprocessor (unused on most systems).
     */
    public const int SIGSTKFLT = 144;

    /**
     * Child stopped or terminated.
     */
    public const int SIGCHLD = 145;

    /**
     * Continue if stopped.
     */
    public const int SIGCONT = 146;

    /**
     * Stop process (cannot be caught or ignored).
     */
    public const int SIGSTOP = 147;

    /**
     * Stop typed at terminal.
     */
    public const int SIGTSTP = 148;

    /**
     * Terminal input for background process.
     */
    public const int SIGTTIN = 149;

    /**
     * Terminal output for background process.
     */
    public const int SIGTTOU = 150;

    /**
     * Urgent condition on socket.
     */
    public const int SIGURG = 151;

    /**
     * CPU time limit exceeded.
     */
    public const int SIGXCPU = 152;

    /**
     * File size limit exceeded.
     */
    public const int SIGXFSZ = 153;

    /**
     * Virtual alarm clock.
     */
    public const int SIGVTALRM = 154;

    /**
     * Profiling timer expired.
     */
    public const int SIGPROF = 155;

    /**
     * Window resize signal.
     */
    public const int SIGWINCH = 156;

    /**
     * I/O now possible.
     */
    public const int SIGIO = 157;

    /**
     * Power failure (not all systems).
     */
    public const int SIGPWR = 158;

    /**
     * Bad system call (sometimes SIGSYS).
     */
    public const int SIGSYS = 159;

    // ─────────────────────────────
    // Helpers
    // ─────────────────────────────

    /**
     * Returns the exit code for a given signal name.
     *
     * @param string $signalName Signal name (case-insensitive, e.g. "sigterm").
     * @return int|null Exit code (e.g. 143) or null if not found.
     *
     * @example
     * ```php
     * echo ExitCode::getCodeForSignal(' sigterm' ) ; // 143
     * ```
     */
    public static function getCodeForSignal( string $signalName ): ?int
    {
        $signalName = strtoupper($signalName);
        $flipped    = array_change_key_case( array_flip( self::$signalMap ) , CASE_UPPER ) ;
        return $flipped[ $signalName ] ?? null ;
    }

    /**
     * Returns the signal name for a given exit code if applicable.
     *
     * @param int $code Exit code to check.
     * @return string|null The signal name (e.g. "SIGKILL") or null if not a signal.
     *
     * @example
     * ```php
     * echo ExitCode::getSignalName( 137 ) ; // SIGKILL
     *
     * var_dump( ExitCode::getSignalName( 0 ) ) ; // null
     *
     * $code   = 137;
     * $signal = ExitCode::getSignalName( $code ) ;
     * if ( $signal )
     * {
     *     echo "Process terminated by signal: {$signal}";
     * }
     * else
     * {
     *     echo "Exit code: {$code}";
     * }
     * ```
     */
    public static function getSignalName( int $code ): ?string
    {
        return self::$signalMap[$code] ?? null;
    }

    /**
     * Signal code-to-name mapping.
     */
    private static array $signalMap =
    [
        self::SIGHUP    => 'SIGHUP'    ,
        self::SIGINT    => 'SIGINT'    ,
        self::SIGQUIT   => 'SIGQUIT'   ,
        self::SIGILL    => 'SIGILL'    ,
        self::SIGTRAP   => 'SIGTRAP'   ,
        self::SIGABRT   => 'SIGABRT'   ,
        self::SIGBUS    => 'SIGBUS'    ,
        self::SIGFPE    => 'SIGFPE'    ,
        self::SIGKILL   => 'SIGKILL'   ,
        self::SIGUSR1   => 'SIGUSR1'   ,
        self::SIGSEGV   => 'SIGSEGV'   ,
        self::SIGUSR2   => 'SIGUSR2'   ,
        self::SIGPIPE   => 'SIGPIPE'   ,
        self::SIGALRM   => 'SIGALRM'   ,
        self::SIGTERM   => 'SIGTERM'   ,
        self::SIGSTKFLT => 'SIGSTKFLT' ,
        self::SIGCHLD   => 'SIGCHLD'   ,
        self::SIGCONT   => 'SIGCONT'   ,
        self::SIGSTOP   => 'SIGSTOP'   ,
        self::SIGTSTP   => 'SIGTSTP'   ,
        self::SIGTTIN   => 'SIGTTIN'   ,
        self::SIGTTOU   => 'SIGTTOU'   ,
        self::SIGURG    => 'SIGURG'    ,
        self::SIGXCPU   => 'SIGXCPU'   ,
        self::SIGXFSZ   => 'SIGXFSZ'   ,
        self::SIGVTALRM => 'SIGVTALRM' ,
        self::SIGPROF   => 'SIGPROF'   ,
        self::SIGWINCH  => 'SIGWINCH'  ,
        self::SIGIO     => 'SIGIO'     ,
        self::SIGPWR    => 'SIGPWR'    ,
        self::SIGSYS    => 'SIGSYS'    ,
    ];
}