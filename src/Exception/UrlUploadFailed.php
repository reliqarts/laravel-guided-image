<?php

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Exception;

use RuntimeException;
use Throwable;

final class UrlUploadFailed extends RuntimeException
{
    private const ERROR_TEMPLATE = 'Failed to upload image from url: %s. %s';

    private string $url;

    public static function forUrl(string $url, Throwable $previousException): self
    {
        $instance = new self(
            sprintf(self::ERROR_TEMPLATE, $url, $previousException->getMessage()),
            $previousException->getCode(),
            $previousException
        );
        $instance->url = $url;

        return $instance;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
