<?php

declare(strict_types=1);

namespace Psl\Tests\Unit\Filesystem;

use Psl\Env;
use Psl\Exception\InvariantViolationException;
use Psl\Filesystem;
use Psl\Str;

use function time;

use const PHP_OS_FAMILY;

final class FileTest extends AbstractFilesystemTest
{
    protected string $function = 'file';

    public function testTemporaryFile(): void
    {
        $file = Filesystem\create_temporary_file($this->directory);

        static::assertTrue(Filesystem\is_file($file));
        static::assertSame($this->directory, Filesystem\get_directory($file));
    }

    public function testTemporaryFileWithPrefix(): void
    {
        $file = Filesystem\create_temporary_file($this->directory, 'foo');

        static::assertTrue(Filesystem\is_file($file));
        static::assertSame($this->directory, Filesystem\get_directory($file));
        static::assertStringContainsString('foo', Filesystem\get_filename($file));
    }

    public function testTemporaryFileIsCreateInTempDirectoryByDefault(): void
    {
        $file = Filesystem\create_temporary_file();

        static::assertSame(Env\temp_dir(), Filesystem\get_directory($file));
    }

    public function testTemporaryFileThrowsForNonExistingDirectory(): void
    {
        $this->expectException(InvariantViolationException::class);
        $this->expectExceptionMessage('"' . __FILE__ . '" is not a directory.');

        Filesystem\create_temporary_file(__FILE__);
    }

    public function testTemporaryFileThrowsForPrefixWithSeparator(): void
    {
        $prefix = Str\join(['a', 'b'], Filesystem\SEPARATOR);

        $this->expectException(InvariantViolationException::class);
        $this->expectExceptionMessage(
            '$prefix should not contain a directory separator ( "' . Filesystem\SEPARATOR . '" ).'
        );

        Filesystem\create_temporary_file($this->directory, $prefix);
    }

    public function testCreateFileAndParentDirectory(): void
    {
        $directory = Str\join([$this->directory, 'foo'], Filesystem\SEPARATOR);
        $file = Str\join([$directory, 'write.txt'], Filesystem\SEPARATOR);

        static::assertFalse(Filesystem\is_directory($directory));

        Filesystem\create_file($file);

        static::assertTrue(Filesystem\is_directory($directory));
        static::assertTrue(Filesystem\is_file($file));
    }

    public function testFileModificationAndAccessTime(): void
    {
        $file = Str\join([$this->directory, 'write.txt'], Filesystem\SEPARATOR);

        $modification_time = time() - 3600;
        $access_time = time() - 1800;

        Filesystem\create_file($file, $modification_time, $access_time);

        static::assertSame($modification_time, Filesystem\get_modification_time($file));
        static::assertSame($access_time, Filesystem\get_access_time($file));
    }

    public function testGetModificationTimeOfNonExistingFile(): void
    {
        $filename = Str\join([$this->directory, 'non-existing'], Filesystem\SEPARATOR);

        $this->expectException(InvariantViolationException::class);
        $this->expectExceptionMessage('File "' . $filename . '" does not exist.');

        Filesystem\get_modification_time($filename);
    }

    public function testGetAccessTimeOfNonExistingFile(): void
    {
        $filename = Str\join([$this->directory, 'non-existing'], Filesystem\SEPARATOR);

        $this->expectException(InvariantViolationException::class);
        $this->expectExceptionMessage('File "' . $filename . '" does not exist.');

        Filesystem\get_access_time($filename);
    }

    public function testGetChangeTimeOfNonExistingFile(): void
    {
        $filename = Str\join([$this->directory, 'non-existing'], Filesystem\SEPARATOR);

        $this->expectException(InvariantViolationException::class);
        $this->expectExceptionMessage('File "' . $filename . '" does not exist.');

        Filesystem\get_change_time($filename);
    }

    public function testGetInodeOfNonExistingFile(): void
    {
        $filename = Str\join([$this->directory, 'non-existing'], Filesystem\SEPARATOR);

        $this->expectException(InvariantViolationException::class);
        $this->expectExceptionMessage('File "' . $filename . '" does not exist.');

        Filesystem\get_inode($filename);
    }

    public function testFileSizeThrowsForNonExistingFile(): void
    {
        $filename = Str\join([$this->directory, 'non-existing'], Filesystem\SEPARATOR);

        $this->expectException(InvariantViolationException::class);
        $this->expectExceptionMessage('File "' . $filename . '" does not exist, or is not readable.');

        Filesystem\file_size($filename);
    }

    public function testCopyThrowsForNonExistingFile(): void
    {
        $filename = Str\join([$this->directory, 'non-existing'], Filesystem\SEPARATOR);

        $this->expectException(InvariantViolationException::class);
        $this->expectExceptionMessage('Source "' . $filename . '" does not exist, or is not readable.');

        Filesystem\copy($filename, '/foo/bar');
    }

    public function testCreateSymbolicLinkThrowsForNonExistingFile(): void
    {
        $filename = Str\join([$this->directory, 'non-existing'], Filesystem\SEPARATOR);

        $this->expectException(InvariantViolationException::class);
        $this->expectExceptionMessage('Source file "' . $filename . '" does not exist.');

        Filesystem\create_symbolic_link($filename, '/foo/bar');
    }

    public function testFileSizeThrowsForNonReadableFile(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // executable bit on windows.
            static::markTestSkipped('Test can only be executed under *nix OS.');
        }

        $filename = Str\join([$this->directory, 'non-readable.txt'], Filesystem\SEPARATOR);
        Filesystem\create_file($filename);
        $permissions = Filesystem\get_permissions($filename) & 0777;
        Filesystem\change_permissions($filename, 0111);

        static::assertFalse(Filesystem\is_readable($filename));

        $this->expectException(InvariantViolationException::class);
        $this->expectExceptionMessage('File "' . $filename . '" does not exist, or is not readable.');

        try {
            Filesystem\file_size($filename);
        } finally {
            Filesystem\change_permissions($filename, $permissions);
        }
    }

    public function testCopyThrowsForNonReadableFile(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // executable bit on windows.
            static::markTestSkipped('Test can only be executed under *nix OS.');
        }

        $filename = Str\join([$this->directory, 'non-readable.txt'], Filesystem\SEPARATOR);
        Filesystem\create_file($filename);
        $permissions = Filesystem\get_permissions($filename) & 0777;
        Filesystem\change_permissions($filename, 0111);

        static::assertFalse(Filesystem\is_readable($filename));

        $this->expectException(InvariantViolationException::class);
        $this->expectExceptionMessage('Source "' . $filename . '" does not exist, or is not readable.');

        try {
            Filesystem\copy($filename, '/foo/bar');
        } finally {
            Filesystem\change_permissions($filename, $permissions);
        }
    }

    public function testFileAccessTime(): void
    {
        $file = Str\join([$this->directory, 'write.txt'], Filesystem\SEPARATOR);

        $access_time = time() - 1800;

        Filesystem\create_file($file, null, $access_time);

        static::assertSame($access_time, Filesystem\get_modification_time($file));
        static::assertSame($access_time, Filesystem\get_access_time($file));
    }

    public function testFileModificationTime(): void
    {
        $file = Str\join([$this->directory, 'write.txt'], Filesystem\SEPARATOR);

        $modification_time = time() - 3600;

        Filesystem\create_file($file, $modification_time);

        static::assertSame($modification_time, Filesystem\get_modification_time($file));
        static::assertSame($modification_time, Filesystem\get_access_time($file));
    }

    public function testFileChangeTime(): void
    {
        $file = Str\join([$this->directory, 'write.txt'], Filesystem\SEPARATOR);

        Filesystem\create_file($file);

        static::assertEqualsWithDelta(time(), Filesystem\get_change_time($file), 1.0);
    }
}
