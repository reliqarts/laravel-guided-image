<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Contract;

interface FileHelper
{
    public function hashFile(string $filePath): string;

    /**
     * @return array|false an array with 7 elements, false on failure
     */
    public function getImageSize(string $filename, array &$imageInfo = []);
}
