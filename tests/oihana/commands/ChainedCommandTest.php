<?php

declare(strict_types=1);

namespace tests\oihana\commands;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;

use oihana\commands\ChainedCommand;
use oihana\commands\enums\ExitCode;
use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;

final class ChainedCommandTest extends TestCase
{
    private ChainedCommand $command;
    private ArrayInput $input;
    private BufferedOutput $output;

    /**
     * @throws NotFoundExceptionInterface
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     * @throws DependencyException
     */
    protected function setUp(): void
    {
        $this->command = new ChainedCommand('test:chained', new Container() ,
        [
            'before' => [
                fn($i, $o, $cmd) => $o->writeln('before 1'),
                fn($i, $o, $cmd) => $o->writeln('before 2')
            ],
            'run' => [
                fn($i, $o, $cmd) => $o->writeln('run 1')
            ],
            'after' => [
                fn($i, $o, $cmd) => $o->writeln('after 1')
            ]
        ]);

        $this->input  = new ArrayInput([]);
        $this->output = new BufferedOutput();
    }

    /**
     * @throws ExceptionInterface
     */
    public function testTriggerExecutesHooksInOrder(): void
    {
        $status = $this->command->trigger($this->input, $this->output);
        $this->assertSame(ExitCode::SUCCESS, $status);

        $contents = $this->output->fetch();
        $this->assertStringContainsString('before 1', $contents);
        $this->assertStringContainsString('before 2', $contents);
        $this->assertStringContainsString('run 1', $contents);
        $this->assertStringContainsString('after 1', $contents);

        // Vérifie l'ordre
        $posBefore1 = strpos($contents, 'before 1');
        $posBefore2 = strpos($contents, 'before 2');
        $posRun1    = strpos($contents, 'run 1');
        $posAfter1  = strpos($contents, 'after 1');

        $this->assertTrue($posBefore1 < $posBefore2);
        $this->assertTrue($posBefore2 < $posRun1);
        $this->assertTrue($posRun1 < $posAfter1);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testHookCanStopChain(): void
    {
        $this->command->initializeChain([
            'before' => [
                fn($i, $o, $cmd) => ExitCode::FAILURE,
                fn($i, $o, $cmd) => $o->writeln('should not run')
            ]
        ]);

        $status = $this->command->trigger($this->input, $this->output);
        $this->assertSame(ExitCode::FAILURE, $status);

        $contents = $this->output->fetch();
        $this->assertStringNotContainsString('should not run', $contents);
    }

    /**
     * @throws ExceptionInterface
     */
    /**
     * @throws ExceptionInterface
     */
    public function testExecuteRunsClearLifecycleAndChain(): void
    {
        // 'clear' option is defined but left unset, so clearConsole(false) runs (no system('clear')).
        $definition = new InputDefinition( [ new InputOption( 'clear' , null , InputOption::VALUE_OPTIONAL ) ] ) ;
        $input      = new ArrayInput( [] , $definition ) ;

        $invoke = fn( $i , $o ) => $this->execute( $i , $o ) ;
        $status = $invoke->call( $this->command , $input , $this->output ) ;

        $this->assertSame( ExitCode::SUCCESS , $status ) ;

        $contents = $this->output->fetch() ;
        $this->assertStringContainsString( 'before 1' , $contents ) ; // the chain ran through execute()
        $this->assertStringContainsString( 'run 1' , $contents ) ;
        $this->assertStringContainsString( 'after 1' , $contents ) ;
    }

    public function testRunCallableReturningNullCountsAsSuccess(): void
    {
        $this->command->initializeChain([
            'run' => [
                fn($i, $o, $cmd) => null
            ]
        ]);

        $status = $this->command->trigger($this->input, $this->output);
        $this->assertSame(ExitCode::SUCCESS, $status);
    }
}
