<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

interface FileHelper
{
    public function hashFile(string $filePath): string;

    /**
     * @return array|false an array with 7 elements, false on failure
     */
    public function getImageSize(string $filename, array &$imageInfo = []);

    /**
     * @param mixed $data
     *
     * @see file_put_contents()
     */
    public function putContents(string $filename, $data): ?int;

    /**
     * @see file_get_contents()
     */
    public function getContents(string $filename, bool $useIncludePath = false): ?string;

    /**
     * @param resource|null $context
     *
     * @see unlink()
     */
    public function unlink(string $filename, $context = null): bool;

    /**
     * Returns directory path used for temporary files
     *
     * @see sys_get_temp_dir()
     */
    public function getSystemTempDirectory(): string;

    /**
     * @return string|null the new temporary file name or null on failure
     * @see tmpnam()
     */
    public function tempName(string $directory, string $prefix): ?string;

    /**
     * @see mime_content_type()
     */
    public function getMimeType(string $filename): ?string;

    /**
     * Get associated extension for particular mime type.
     */
    public function mime2Ext(string $mime): ?string;

    /**
     * @throws FileException
     * @throws FileNotFoundException
     */
    public function createUploadedFile(string $tempFile, string $originalName, string $mimeType): UploadedFile;
}
