<?php
/*
 * This file is part of the SemanticVersion package.
 *
 * (c) Jason Schilling <jason@sourecode.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SoureCode\SemanticVersion\Tests;

use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\TestCase;
use SoureCode\SemanticVersion\Exception\InvalidArgumentException;
use SoureCode\SemanticVersion\Version;

/**
 * Class VersionTest.
 *
 * @author Jason Schilling <jason@sourecode.dev>
 */
class VersionTest extends TestCase
{
    public function testGetMajor(): void
    {
        static::assertSame(1, (new Version(1, 0, 0))->getMajor());
        static::assertSame(2, (new Version(2, 0, 0))->getMajor());
    }

    public function testGetMinor(): void
    {
        static::assertSame(1, (new Version(0, 1, 0))->getMinor());
        static::assertSame(2, (new Version(0, 2, 0))->getMinor());
    }

    public function testGetPatch(): void
    {
        static::assertSame(1, (new Version(0, 0, 1))->getPatch());
        static::assertSame(2, (new Version(0, 0, 2))->getPatch());
    }

    public function testGetPreRelease(): void
    {
        static::assertEquals(['foo'], (new Version(0, 0, 0, ['foo']))->getPreRelease());
        static::assertEquals(['foo', 'bar'], (new Version(0, 0, 0, ['foo', 'bar']))->getPreRelease());
    }

    public function testGetBuildMetadata(): void
    {
        static::assertEquals(['foo'], (new Version(0, 0, 0, [], ['foo']))->getBuildMetadata());
        static::assertEquals(['foo', 'bar'], (new Version(0, 0, 0, [], ['foo', 'bar']))->getBuildMetadata());
    }

    public function testFromString(): void
    {
        static::assertSameVersion(new Version(1, 2, 3), Version::fromString('1.2.3'));
        static::assertSameVersion(new Version(1, 2, 3, ['foo', 'bar']), Version::fromString('1.2.3-foo.bar'));
        static::assertSameVersion(
            new Version(1, 2, 3, ['foo', 'bar'], ['baz', 'boz']),
            Version::fromString('1.2.3-foo.bar+baz.boz')
        );
    }

    public static function assertSameVersion(Version $expected, Version $actual, string $message = ''): void
    {
        static::assertThat($actual->getMajor(), new IsIdentical($expected->getMajor()), $message);
        static::assertThat($actual->getMinor(), new IsIdentical($expected->getMinor()), $message);
        static::assertThat($actual->getPatch(), new IsIdentical($expected->getPatch()), $message);
        static::assertThat($actual->getPreRelease(), new IsIdentical($expected->getPreRelease()), $message);
        static::assertThat($actual->getBuildMetadata(), new IsIdentical($expected->getBuildMetadata()), $message);
    }

    /**
     * @dataProvider invalidVersions
     */
    public function testInvalidVersion(string $invalidVersion): void
    {
        $this->expectException(InvalidArgumentException::class);

        Version::fromString($invalidVersion);
    }

    public function testSetMajor(): void
    {
        $version = new Version(1, 2, 3);
        $modifiedVersion = $version->setMajor(2);

        static::assertNotSame($version, $modifiedVersion);
        static::assertSameVersion(new Version(1, 2, 3), $version);
        static::assertSameVersion(new Version(2, 2, 3), $modifiedVersion);
    }

    public function testSetMinor(): void
    {
        $version = new Version(1, 2, 3);
        $modifiedVersion = $version->setMinor(3);

        static::assertNotSame($version, $modifiedVersion);
        static::assertSameVersion(new Version(1, 2, 3), $version);
        static::assertSameVersion(new Version(1, 3, 3), $modifiedVersion);
    }

    public function testSetPatch(): void
    {
        $version = new Version(1, 2, 3);
        $modifiedVersion = $version->setPatch(4);

        static::assertNotSame($version, $modifiedVersion);
        static::assertSameVersion(new Version(1, 2, 3), $version);
        static::assertSameVersion(new Version(1, 2, 4), $modifiedVersion);
    }

    public function testSetPreRelease(): void
    {
        $version = new Version(1, 2, 3, ['foo', 'bar'], ['baz', 'boz']);
        $modifiedVersion = $version->setPreRelease(['foot', 'bart']);

        static::assertNotSame($version, $modifiedVersion);
        static::assertSameVersion(new Version(1, 2, 3, ['foo', 'bar'], ['baz', 'boz']), $version);
        static::assertSameVersion(new Version(1, 2, 3, ['foot', 'bart'], ['baz', 'boz']), $modifiedVersion);
    }

    public function testSetBuildMetadata(): void
    {
        $version = new Version(1, 2, 3, ['foo', 'bar'], ['baz', 'boz']);
        $modifiedVersion = $version->setBuildMetadata(['baze', 'boze']);

        static::assertNotSame($version, $modifiedVersion);
        static::assertSameVersion(new Version(1, 2, 3, ['foo', 'bar'], ['baz', 'boz']), $version);
        static::assertSameVersion(new Version(1, 2, 3, ['foo', 'bar'], ['baze', 'boze']), $modifiedVersion);
    }

    /**
     * @dataProvider toStringData
     */
    public function testToString($value): void
    {
        static::assertSame($value, (string) Version::fromString($value));
    }

    public function toStringData(): array
    {
        return [
            ['1.2.3'],
            ['1.2.3-foo.bar'],
            ['1.2.3+baz-boz'],
            ['1.2.3-foo.bar+baz-boz'],
        ];
    }

    /**
     * @dataProvider compareData
     */
    public function testCompare($result, $versionA, $versionB)
    {
        static::assertSame($result, Version::fromString($versionA)->compare(Version::fromString($versionB)));
    }

    public function compareData()
    {
        return [
            [0, '1.2.3', '1.2.3'],
            [-1, '1.2.3', '2.2.3'],
            [-1, '1.2.3', '1.3.3'],
            [-1, '1.2.3', '1.2.4'],
            [1, '1.2.3', '0.2.3'],
            [1, '1.2.3', '1.1.3'],
            [1, '1.2.3', '1.2.2'],
            [-1, '1.2.3-a', '1.2.3-b'],
            [1, '1.2.3-b', '1.2.3-a'],
            [1, '1.2.3', '1.2.3-a'],
            [-1, '1.0.0-alpha', '1.0.0-alpha.1'],
            [-1, '1.0.0-alpha.1', '1.0.0-alpha.beta'],
            [-1, '1.0.0-alpha.beta', '1.0.0-beta'],
            [-1, '1.0.0-beta', '1.0.0-beta.2'],
            [-1, '1.0.0-beta.2', '1.0.0-beta.11'],
            [-1, '1.0.0-beta.11', '1.0.0-rc.1'],
            [-1, '1.0.0-rc.1', '1.0.0'],
        ];
    }

    public function invalidVersions()
    {
        return [
            ['1'],
            ['1.2'],
            ['1.2.b'],
        ];
    }
}
