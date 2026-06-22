<?php

declare(strict_types=1);

namespace tests\oihana\commands\traits;

use oihana\commands\options\CommandOption;
use oihana\commands\traits\LockCommandTrait;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A plain consumer of LockCommandTrait: neither a Command nor carrying an AsCommand attribute.
 * The Symfony LockableTrait lock()/release() methods are overridden (class methods take
 * precedence over trait methods) so the tests never touch the real filesystem lock store.
 */
class PlainLockFixture
{
    use LockCommandTrait;

    public ?array $lockCalledWith = null ;
    public bool   $lockResult     = true ;
    public bool   $released       = false ;

    public function lock( ?string $name = null , bool $blocking = false ): bool
    {
        $this->lockCalledWith = [ $name , $blocking ] ;
        return $this->lockResult ;
    }

    public function release(): void
    {
        $this->released = true ;
    }

    public function doAcquire( InputInterface $input , OutputInterface $output , ?string $name = null , bool $blocking = false , ?string $env = null ): bool
    {
        return $this->acquireLock( $input , $output , $name , $blocking , $env ) ;
    }

    public function doUnlock(): void
    {
        $this->unlock() ;
    }
}

/**
 * A consumer carrying an AsCommand attribute but not extending Command.
 */
#[AsCommand( name: 'attr:command' )]
class AttributeLockFixture
{
    use LockCommandTrait;

    public ?array $lockCalledWith = null ;
    public bool   $lockResult     = true ;

    public function lock( ?string $name = null , bool $blocking = false ): bool
    {
        $this->lockCalledWith = [ $name , $blocking ] ;
        return $this->lockResult ;
    }

    public function release(): void {}

    public function doAcquire( InputInterface $input , OutputInterface $output , ?string $name = null , bool $blocking = false , ?string $env = null ): bool
    {
        return $this->acquireLock( $input , $output , $name , $blocking , $env ) ;
    }
}

/**
 * A consumer extending Symfony Command.
 */
class CommandLockFixture extends Command
{
    use LockCommandTrait;

    public ?array $lockCalledWith = null ;
    public bool   $lockResult     = true ;

    public function lock( ?string $name = null , bool $blocking = false ): bool
    {
        $this->lockCalledWith = [ $name , $blocking ] ;
        return $this->lockResult ;
    }

    public function release(): void {}

    public function doAcquire( InputInterface $input , OutputInterface $output , ?string $name = null , bool $blocking = false , ?string $env = null ): bool
    {
        return $this->acquireLock( $input , $output , $name , $blocking , $env ) ;
    }
}

/**
 * Unit tests for LockCommandTrait
 */
class LockCommandTraitTest extends TestCase
{
    /**
     * Builds an Input stub whose ENV/FORCE options are configured.
     */
    private function makeInput( bool $hasEnv = false , ?string $envValue = null , bool $hasForce = false , bool $forceValue = false ): InputInterface
    {
        $input = $this->createStub( InputInterface::class ) ;

        $input->method( 'hasOption' )->willReturnMap
        ([
            [ CommandOption::ENV   , $hasEnv   ] ,
            [ CommandOption::FORCE , $hasForce ] ,
        ]);

        $input->method( 'getOption' )->willReturnMap
        ([
            [ CommandOption::ENV   , $envValue   ] ,
            [ CommandOption::FORCE , $forceValue ] ,
        ]);

        return $input ;
    }

    public function testAcquireLockReturnsTrueWhenLockSucceeds(): void
    {
        $fixture = new PlainLockFixture() ;
        $fixture->lockResult = true ;

        $result = $fixture->doAcquire( $this->makeInput() , $this->createStub( OutputInterface::class ) , 'task' ) ;

        $this->assertTrue( $result ) ;
        $this->assertSame( [ 'task' , false ] , $fixture->lockCalledWith ) ;
    }

    public function testAcquireLockReturnsFalseAndWarnsWhenLockFails(): void
    {
        $fixture = new PlainLockFixture() ;
        $fixture->lockResult = false ;

        $output = $this->createMock( OutputInterface::class ) ;
        $output->expects( $this->once() )
               ->method( 'writeln' )
               ->with( $this->stringContains( 'already running' ) ) ;

        $result = $fixture->doAcquire( $this->makeInput() , $output , 'task' ) ;

        $this->assertFalse( $result ) ;
    }

    public function testForceOptionBypassesLock(): void
    {
        $fixture = new PlainLockFixture() ;
        $fixture->lockResult = false ; // would fail, but force bypasses it

        $result = $fixture->doAcquire( $this->makeInput( hasForce: true , forceValue: true ) , $this->createStub( OutputInterface::class ) , 'task' ) ;

        $this->assertTrue( $result ) ;
        $this->assertNull( $fixture->lockCalledWith , 'lock() must not be called when --force is set' ) ;
    }

    public function testBlockingFlagIsForwardedToLock(): void
    {
        $fixture = new PlainLockFixture() ;

        $fixture->doAcquire( $this->makeInput() , $this->createStub( OutputInterface::class ) , 'task' , true ) ;

        $this->assertSame( [ 'task' , true ] , $fixture->lockCalledWith ) ;
    }

    public function testUsesCommandNameWhenInstanceOfCommand(): void
    {
        $fixture = new CommandLockFixture() ;
        $fixture->setName( 'my:cmd' ) ;

        $fixture->doAcquire( $this->makeInput() , $this->createStub( OutputInterface::class ) ) ;

        $this->assertSame( 'my:cmd' , $fixture->lockCalledWith[ 0 ] ) ;
    }

    public function testUsesAsCommandAttributeNameWhenNotACommand(): void
    {
        $fixture = new AttributeLockFixture() ;

        $fixture->doAcquire( $this->makeInput() , $this->createStub( OutputInterface::class ) ) ;

        $this->assertSame( 'attr:command' , $fixture->lockCalledWith[ 0 ] ) ;
    }

    public function testNoNameResolvedWhenNeitherCommandNorAttribute(): void
    {
        $fixture = new PlainLockFixture() ;

        $fixture->doAcquire( $this->makeInput() , $this->createStub( OutputInterface::class ) ) ;

        $this->assertNull( $fixture->lockCalledWith[ 0 ] ) ;
    }

    public function testEnvPrefixFromParameter(): void
    {
        $fixture = new PlainLockFixture() ;

        $fixture->doAcquire( $this->makeInput() , $this->createStub( OutputInterface::class ) , 'job' , false , 'prod' ) ;

        $this->assertSame( 'prod:job' , $fixture->lockCalledWith[ 0 ] ) ;
    }

    public function testEnvFromInputOptionOverridesParameter(): void
    {
        $fixture = new PlainLockFixture() ;

        $input = $this->makeInput( hasEnv: true , envValue: 'staging' ) ;

        $fixture->doAcquire( $input , $this->createStub( OutputInterface::class ) , 'job' , false , 'prod' ) ;

        $this->assertSame( 'staging:job' , $fixture->lockCalledWith[ 0 ] ) ;
    }

    public function testUnlockCallsRelease(): void
    {
        $fixture = new PlainLockFixture() ;

        $fixture->doUnlock() ;

        $this->assertTrue( $fixture->released ) ;
    }
}
