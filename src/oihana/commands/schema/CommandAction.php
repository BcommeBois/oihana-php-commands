<?php

namespace oihana\commands\schema;

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use ReflectionException;

use org\schema\Action;

use Symfony\Component\Uid\Uuid;
use function oihana\core\date\now;

/**
 * Represents a command action extending Schema.org's `Action`.
 *
 * This class is a specialized `Action` for CLI or system commands.
 * It automatically sets default values such as:
 * - `agent`      → the current system user,
 * - `identifier` → a UUID v4 unique identifier,
 * - `location`   → the hostname of the machine,
 * - `startTime`  → current timestamp in ISO 8601 UTC format.
 *
 * It can be initialized with an array or object, which is passed to the parent constructor
 * for additional hydration of Schema.org properties.
 *
 * JSON-LD @context is defined in `CommandAction::CONTEXT`.
 *
 * @see https://schema.org/Action
 *
 * @package oihana\commands\schema
 */
class CommandAction extends Action
{
    /**
     * Creates a new Command Action instance.
     * @param array|object|null $init
     * @throws ReflectionException
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     */
    public function __construct( array|object|null $init = null )
    {
        $this->agent      = $agent ?? get_current_user();
        $this->identifier = Uuid::v4() ;
        $this->location   = gethostname();
        $this->startTime  = now();
        parent::__construct( $init ) ;
    }

    /**
     * The @context of the json-ld representation of the thing.
     */
    public const string CONTEXT = 'https://schema.oihana.xyz';
}