<?php

namespace oihana\commands\enums;

use oihana\reflect\traits\ConstantsTrait;

/**
 * Enumeration of common systemctl commands.
 *
 * This class provides constants representing frequently used
 * `systemctl` commands for managing the systemd system and service
 * manager on Linux. It can be used to standardize and centralize
 * command usage in scripts and applications.
 *
 * Example:
 * ```php
 * use oihana\commands\enums\SystemCTLCommands;
 *
 * // Start a service
 * shell_exec(SystemCTLCommands::SYSTEM_CTL_START . ' nginx');
 *
 * // Check if a service is active
 * $isActive = trim(shell_exec(SystemCTLCommands::SYSTEM_CTL_IS_ACTIVE . ' nginx')) === 'active';
 * var_dump($isActive);
 *
 * // List all running units
 * echo shell_exec(SystemCTLCommands::SYSTEM_CTL_LIST_UNITS);
 * ```
 *
 * @package oihana\commands\enums
 * @author  Marc Alcaraz (ekameleon)
 * @since   1.0.0
 *
 * @see https://www.commandlinux.com/man-page/man1/systemctl.1.html
 */
class SystemCTLCommands
{
    use ConstantsTrait ;

    /**
     * The 'systemctl' cli command.
     */
    public const string SYSTEM_CTL = 'systemctl' ;

    /**
     * List known units (subject to limitations specified with -t).
     * If one or more PATTERNs are specified, only units matching one of them are shown.
     * This is the default command.
     */
    public const string SYSTEM_CTL_LIST_UNITS = 'systemctl list-units' ;

    /**
     * List socket units ordered by listening address.
     */
    public const string SYSTEM_CTL_LIST_SOCKETS = 'systemctl list-sockets' ;

    /**
     * Shows required and wanted units of the specified unit.
     *
     * If no unit is specified, default.target is implied.
     * Target units are recursively expanded.
     * When --all is passed, all other units are recursively expanded as well.
     */
    public const string SYSTEM_CTL_LIST_DEPENDENCIES = 'systemctl list-dependencies' ;

    /**
     * List timer units ordered by the time they elapse next.
     *
     * If one or more PATTERNs are specified, only units matching one of them are shown.
     */
    public const string SYSTEM_CTL_LIST_TIMERS = 'systemctl list-timers' ;

    /**
     * Show backing files of one or more units.
     *
     * Prints the "fragment" and "drop-ins" (source files) of units.
     * Each file is preceded by a comment which includes the file name.
     */
    public const string SYSTEM_CTL_CAT = 'systemctl cat' ;

    /**
     * Disables autostart at boot.
     */
    public const string SYSTEM_CTL_DISABLE = 'systemctl disable' ;

    /**
     * Reloads systemd binary (rarely needed).
     */
    public const string SYSTEM_CTL_DAEMON_RE_EXEC = 'systemctl daemon-reexec' ;

    /**
     * Reloads unit files after changes (e.g., .service files).
     */
    public const string SYSTEM_CTL_DAEMON_RELOAD = 'systemctl daemon-reload' ;

    /**
     * Enables the service to start on boot.
     */
    public const string SYSTEM_CTL_ENABLE = 'systemctl enable' ;

    /**
     * Show manual pages for one or more units, if available.
     * If a PID is given, the manual pages for the unit the process belongs to are shown.
     */
    public const string SYSTEM_CTL_HELP = 'systemctl help' ;

    /**
     * Start the unit specified on the command line and its dependencies and stop all others.
     *
     * This is similar to changing the runlevel in a traditional init system.
     * The isolate command will immediately stop processes that are not enabled in the new unit,
     * possibly including the graphical environment or terminal you are currently using.
     *
     * Note that this is allowed only on units where AllowIsolate= is enabled. See systemd.unit(5) for details.
     */
    public const string SYSTEM_CTL_ISOLATE = 'systemctl isolate' ;

    /**
     * Checks if the service is running.
     */
    public const string SYSTEM_CTL_IS_ACTIVE = 'systemctl is-active' ;

    /**
     * Checks if the service is enabled to start on boot.
     */
    public const string SYSTEM_CTL_IS_ENABLED = 'systemctl is-enabled' ;

    /**
     * Send a signal to one or more processes of the unit.
     *
     * Use --kill-who= to select which process to kill.
     * Use --signal= to select the signal to send.
     */
    public const string SYSTEM_CTL_KILL = 'systemctl kill' ;

    /**
     * Completely disables a service (cannot start it).
     */
    public const string SYSTEM_CTL_MASK = 'systemctl mask' ;

    /**
     * Rewrites symlinks for autostart.
     */
    public const string SYSTEM_CTL_RE_ENABLE = 'systemctl reenable' ;

    /**
     * Show properties of one or more units, jobs, or the manager itself.
     *
     * If no argument is specified, properties of the manager will be shown.
     * If a unit name is specified, properties of the unit is shown, and if a job id is specified,
     * properties of the job is shown. By default, empty properties are suppressed.
     *
     * Use --all to show those too. To select specific properties to show, use --property=.
     *
     * This command is intended to be used whenever computer-parsable output is required.
     *
     * Use status if you are looking for formatted human-readable output.
     */
    public const string SYSTEM_CTL_SHOW = 'systemctl show' ;

    /**
     * Start (activate) one or more units specified on the command line.
     */
    public const string SYSTEM_CTL_START = 'systemctl start' ;

    /**
     * Stop (deactivate) one or more units specified on the command line.
     */
    public const string SYSTEM_CTL_STOP = 'systemctl stop' ;

    /**
     * Asks all units listed on the command line to reload their configuration.
     *
     * Note that this will reload the service-specific configuration,
     * not the unit configuration file of systemd.
     *
     * If you want systemd to reload the configuration file of a unit,
     * use the daemon-reload command. In other words: for the example case of Apache,
     * this will reload Apache's httpd.conf in the web server, not the apache.service systemd unit file.
     */
    public const string SYSTEM_CTL_RELOAD = 'systemctl reload' ;

    /**
     * Restart one or more units specified on the command line.
     * If the units are not running yet, they will be started.
     */
    public const string SYSTEM_CTL_RESTART = 'systemctl restart' ;

    /**
     * Show terse runtime status information about one or more units, followed by most recent log data from the journal.
     * If no units are specified, show system status.
     * If combined with --all, also show the status of all units (subject to limitations specified with -t).
     * If a PID is passed, show information about the unit the process belongs to.
     */
    public const string SYSTEM_CTL_STATUS = 'systemctl status' ;

    /**
     * Reverses mask (restores ability to start).
     */
    public const string SYSTEM_CTL_UNMASK = 'systemctl unmask' ;
}