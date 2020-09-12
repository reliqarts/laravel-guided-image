<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Helper;

use ReliqArts\GuidedImage\Contract\FileHelper as FileHelperContract;

/**
 * @codeCoverageIgnore
 */
final class FileHelper implements FileHelperContract
{
    public function hashFile(string $filePath): string
    {
        return md5_file($filePath);
    }

    /**
     * @return array|false an array with 7 elements, false on failure
     */
    public function getImageSize(string $filename, array &$imageInfo = [])
    {
        return getimagesize($filename, $imageInfo);
    }
}
