<?php

declare(strict_types=1);

namespace tests\oihana\commands\helpers;

use InvalidArgumentException;
use oihana\commands\enums\outputs\ColorParam;
use oihana\commands\enums\outputs\Palette;
use oihana\commands\enums\outputs\StyleOption;
use PHPUnit\Framework\TestCase;

use function oihana\commands\helpers\format;

final class FormatHelperTest extends TestCase
{
    /**
     * Test simple message sans aucune couleur ni style.
     */
    public function testReturnsRawMessageWhenNoOptions(): void
    {
        $result = format('Hello World');
        $this->assertSame('Hello World', $result);
    }

    /**
     * Test simple couleur de premier plan.
     */
    public function testFormatsWithForegroundColor(): void
    {
        $result = format('Success', [ColorParam::FG => Palette::GREEN]);
        $this->assertSame('<fg=green>Success</>', $result);
    }

    /**
     * Test simple couleur d'arrière-plan.
     */
    public function testFormatsWithBackgroundColor(): void
    {
        $result = format('Alert', [ColorParam::BG => Palette::RED]);
        $this->assertSame('<bg=red>Alert</>', $result);
    }

    /**
     * Test foreground + background en même temps.
     */
    public function testFormatsWithForegroundAndBackground(): void
    {
        $result = format('Error', [
            ColorParam::FG => Palette::WHITE,
            ColorParam::BG => Palette::RED
        ]);
        $this->assertSame('<fg=white;bg=red>Error</>', $result);
    }

    /**
     * Test utilisation d'une seule option de style.
     */
    public function testFormatsWithSingleStyleOption(): void
    {
        $result = format('Warning',
        [
            ColorParam::FG => Palette::YELLOW,
            ColorParam::OPTIONS => StyleOption::BOLD
        ]);
        $this->assertSame('<fg=yellow;options=bold>Warning</>', $result);
    }

    /**
     * Test plusieurs styles passés sous forme de tableau.
     */
    public function testFormatsWithMultipleStyleOptions(): void
    {
        $result = format('Important', [
            ColorParam::FG => Palette::WHITE,
            ColorParam::BG => Palette::MAGENTA,
            ColorParam::OPTIONS => [StyleOption::BOLD, StyleOption::UNDERSCORE]
        ]);

        $this->assertSame('<fg=white;bg=magenta;options=bold,underscore>Important</>', $result);
    }

    /**
     * Test plusieurs styles passés sous forme de chaîne.
     */
    public function testFormatsWithMultipleStyleOptionsAsString(): void
    {
        $result = format('Info', [
            ColorParam::FG => Palette::CYAN,
            ColorParam::OPTIONS => 'bold,underscore'
        ]);

        $this->assertSame('<fg=cyan;options=bold,underscore>Info</>', $result);
    }

    /**
     * Test exception si couleur de foreground invalide.
     */
    public function testThrowsExceptionForInvalidForegroundColor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid foreground color 'pink'");

        format('Invalid', [ColorParam::FG => 'pink']);
    }

    /**
     * Test exception si couleur de background invalide.
     */
    public function testThrowsExceptionForInvalidBackgroundColor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid foreground color 'orange'");

        format('Invalid', [ColorParam::BG => 'orange']);
    }

    /**
     * Test exception si options a un type non valide.
     */
    public function testThrowsExceptionForInvalidOptionsType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid 'options' format. Expected string or array.");

        format('Oops', [ColorParam::OPTIONS => 123]);
    }

    /**
     * Test exception si option de style non supportée.
     */
    public function testThrowsExceptionForInvalidStyle(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid style option 'italic'");

        format('Oops', [
            ColorParam::FG => Palette::WHITE,
            ColorParam::OPTIONS => 'italic'
        ]);
    }

    /**
     * Test que les alias foreground/background fonctionnent.
     */
    public function testSupportsAliasForegroundAndBackground(): void
    {
        $result = format('Alias Test',
        [
            ColorParam::FOREGROUND => Palette::BLUE,
            ColorParam::BACKGROUND => Palette::WHITE,
            ColorParam::OPTIONS => StyleOption::BOLD
        ]);

        $this->assertSame('<fg=blue;bg=white;options=bold>Alias Test</>', $result);
    }
}
