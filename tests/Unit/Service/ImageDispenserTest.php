<?php
/**
 * @noinspection PhpParamsInspection
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpStrictTypeCheckingInspection
 * @noinspection PhpVoidFunctionResultUsedInspection
 */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Service;

use Exception;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Origin;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\Contract\Logger;
use ReliqArts\GuidedImage\Contract\ConfigProvider;
use ReliqArts\GuidedImage\Contract\FileHelper;
use ReliqArts\GuidedImage\Contract\ImageDispenser as ImageDispenserContract;
use ReliqArts\GuidedImage\Contract\ImageManager;
use ReliqArts\GuidedImage\Demand\Dummy;
use ReliqArts\GuidedImage\Demand\Resize;
use ReliqArts\GuidedImage\Demand\Thumbnail;
use ReliqArts\GuidedImage\Service\ImageDispenser;
use ReliqArts\GuidedImage\Tests\Fixtures\Model\GuidedImage;
use ReliqArts\GuidedImage\Tests\Unit\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 */
#[CoversClass(ImageDispenser::class)]
final class ImageDispenserTest extends TestCase
{
    private const CACHE_DISK_NAME = 'local';

    private const CACHE_RESIZED_SUB_DIRECTORY = 'RESIZED';

    private const CACHE_THUMBS_SUB_DIRECTORY = 'THUMBS';

    private const RESPONSE_HTTP_OK = Response::HTTP_OK;

    private const LAST_MODIFIED = 21343;

    private const FILE_HASH = '4387904830a4245a8ab767e5937d722c';

    private const CACHE_FILE_NAME_FORMAT_RESIZED = '%s/%d-%d-_-%d_%d_%s';

    private const CACHE_FILE_FORMAT_THUMBNAIL = '%s/%d-%d-_-%s_%s';

    private const IMAGE_NAME = 'my-image';

    private const IMAGE_URL = '//image_url';

    private const IMAGE_WIDTH = 100;

    private const IMAGE_HEIGHT = 200;

    private const IMAGE_ENCODING_FORMAT = 'png';

    private const IMAGE_ENCODING_QUALITY = 90;

    private const IMAGE_MEDIA_TYPE = 'image/png';

    private const IMAGE_FILE_PATH = 'image.png';

    private const IMAGE_CONTENT_RAW = 'RAW';

    private const THUMBNAIL_METHOD_CROP = 'crop';

    private const THUMBNAIL_METHOD_COVER = 'cover';

    private const UPLOAD_DISK_NAME = 'public';

    private const FOO_RESOURCE = 'resource';

    private ObjectProphecy|Filesystem|FilesystemAdapter $cacheDisk;

    private ObjectProphecy|ImageManager $imageManager;

    private ObjectProphecy|Logger $logger;

    private ObjectProphecy|Request $request;

    private ObjectProphecy|GuidedImage $guidedImage;

    private string $cacheThumbs;

    private string $cacheResized;

    private ImageDispenserContract $subject;

    /**
     * @throws RuntimeException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDisk = $this->prophesize(FilesystemAdapter::class);
        $this->imageManager = $this->prophesize(ImageManager::class);
        $this->logger = $this->prophesize(Logger::class);
        $this->request = $this->prophesize(Request::class);
        $this->guidedImage = $this->prophesize(GuidedImage::class);
        $this->cacheResized = self::CACHE_RESIZED_SUB_DIRECTORY;
        $this->cacheThumbs = self::CACHE_THUMBS_SUB_DIRECTORY;

        $configProvider = $this->prophesize(ConfigProvider::class);
        $fileHelper = $this->prophesize(FileHelper::class);
        $filesystemManager = $this->prophesize(FilesystemManager::class);
        $uploadDisk = $this->prophesize(FilesystemAdapter::class);

        $configProvider
            ->getCacheDiskName()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::CACHE_DISK_NAME);
        $configProvider
            ->getUploadDiskName()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::UPLOAD_DISK_NAME);
        $configProvider
            ->getResizedCachePath()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::CACHE_RESIZED_SUB_DIRECTORY);
        $configProvider
            ->getThumbsCachePath()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::CACHE_THUMBS_SUB_DIRECTORY);
        $configProvider
            ->getCacheDaysHeader()
            ->willReturn(2);
        $configProvider
            ->getAdditionalHeaders()
            ->willReturn([]);
        $configProvider
            ->getImageEncodingMimeType()
            ->willReturn(self::IMAGE_ENCODING_FORMAT);
        $configProvider
            ->getImageEncodingQuality()
            ->willReturn(self::IMAGE_ENCODING_QUALITY);
        $configProvider
            ->isRawImageFallbackEnabled()
            ->willReturn(false);

        $filesystemManager
            ->disk(self::CACHE_DISK_NAME)
            ->shouldBeCalledTimes(1)
            ->willReturn($this->cacheDisk);
        $filesystemManager
            ->disk(self::UPLOAD_DISK_NAME)
            ->shouldBeCalledTimes(1)
            ->willReturn($uploadDisk);

        $this->cacheDisk
            ->exists($this->cacheResized)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->cacheDisk
            ->makeDirectory($this->cacheResized)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->cacheDisk
            ->exists($this->cacheThumbs)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->cacheDisk
            ->makeDirectory($this->cacheThumbs)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->cacheDisk
            ->lastModified(Argument::type('string'))
            ->willReturn(self::LAST_MODIFIED);

        $uploadDisk
            ->url(self::IMAGE_URL)
            ->willReturn(self::IMAGE_URL);

        $fileHelper
            ->hashFile(Argument::type('string'))
            ->willReturn(self::FILE_HASH);

        $this->request
            ->header(Argument::cetera())
            ->willReturn('');

        $this->guidedImage
            ->getName()
            ->willReturn(self::IMAGE_NAME);
        $this->guidedImage
            ->getUrl(true)
            ->willReturn(self::IMAGE_URL);

        $this->subject = new ImageDispenser(
            $configProvider->reveal(),
            $filesystemManager->reveal(),
            $this->imageManager->reveal(),
            $this->logger->reveal(),
            $fileHelper->reveal()
        );
    }

    /**
     * @throws RuntimeException
     */
    public function testEmptyCache(): void
    {
        $this->cacheDisk
            ->deleteDirectory($this->cacheResized)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->cacheDisk
            ->deleteDirectory($this->cacheThumbs)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);

        $result = $this->subject->emptyCache();

        self::assertTrue($result);
    }

    /**
     * @throws RuntimeException
     */
    public function testGetDummyImage(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $color = 'fee';
        $image = $this->getImageMock();

        $this->imageManager
            ->create($width, $height)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getDummyImage(
            new Dummy($width, $height, $color)
        );

        self::assertSame($image, $result);
    }

    /**
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function testGetResizedImage(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $image = $this->getImageMock();
        $demand = new Resize(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            $width,
            $height
        );
        $cacheFile = $this->getCacheFilename($width, $height);
        $imageContent = self::IMAGE_CONTENT_RAW;

        $this->cacheDisk
            ->exists($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->cacheDisk
            ->path($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($cacheFile);
        $this->cacheDisk
            ->get($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($imageContent);

        $this->imageManager
            ->read($cacheFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->read(Argument::in([self::IMAGE_URL, self::FOO_RESOURCE]))
            ->shouldBeCalledTimes(2)
            ->willReturn($image);

        $result = $this->subject->getResizedImage($demand);

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(self::RESPONSE_HTTP_OK, $result->getStatusCode());
        self::assertSame($imageContent, $result->getOriginalContent());
    }

    /**
     * @throws RuntimeException|InvalidArgumentException
     */
    public function testGetResizedImageWhenImageInstanceIsExpected(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $image = $this->getImageMock();
        $demand = new Resize(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            $width,
            $height,
            returnObject: true
        );
        $cacheFile = $this->getCacheFilename($width, $height);

        $this->cacheDisk
            ->exists($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->cacheDisk
            ->path($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($cacheFile);
        $this->cacheDisk
            ->get($cacheFile)
            ->shouldNotBeCalled();

        $this->imageManager
            ->read($cacheFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->read(Argument::in([self::IMAGE_URL, self::FOO_RESOURCE]))
            ->shouldBeCalledTimes(2)
            ->willReturn($image);

        $result = $this->subject->getResizedImage($demand);

        self::assertSame($image, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetResizedImageWhenCacheFileExists(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $image = $this->getImageMock();
        $cacheFile = $this->getCacheFilename($width, $height);

        $this->cacheDisk
            ->exists($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->cacheDisk
            ->path($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($cacheFile);
        $this->cacheDisk
            ->get($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(self::IMAGE_CONTENT_RAW);

        $this->imageManager
            ->read(Argument::in([$cacheFile, self::FOO_RESOURCE]))
            ->shouldBeCalledTimes(2)
            ->willReturn($image);
        $this->imageManager
            ->read(self::IMAGE_URL)
            ->shouldNotBeCalled();

        $result = $this->subject->getResizedImage(
            new Resize(
                $this->request->reveal(),
                $this->guidedImage->reveal(),
                $width,
                $height
            )
        );

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(self::RESPONSE_HTTP_OK, $result->getStatusCode());
        self::assertSame(self::IMAGE_CONTENT_RAW, $result->getOriginalContent());
    }

    /**
     * @throws Exception
     */
    public function testGetResizedWhenImageRetrievalFails(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $image = $this->getImageMock();
        $demand = new Resize(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            $width,
            $height
        );
        $cacheFile = $this->getCacheFilename($width, $height);

        $this->cacheDisk
            ->exists($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->cacheDisk
            ->path($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($cacheFile);
        $this->cacheDisk
            ->get($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willThrow(FileNotFoundException::class);

        $this->imageManager
            ->read($cacheFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->read(Argument::in([self::IMAGE_URL, self::FOO_RESOURCE]))
            ->shouldBeCalledTimes(2)
            ->willReturn($image);

        $this->guidedImage
            ->getUrl()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::IMAGE_URL);

        $this->logger
            ->error(
                Argument::containingString('Exception'),
                Argument::type('array')
            )
            ->shouldBeCalledTimes(1);

        $this->expectException(NotFoundHttpException::class);

        $this->subject->getResizedImage($demand);
    }

    /**
     * @throws Exception
     */
    public function testGetImageThumbnail(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $image = $this->getImageMock();
        $demand = new Thumbnail(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            self::THUMBNAIL_METHOD_CROP,
            $width,
            $height
        );
        $cacheFile = sprintf(
            self::CACHE_FILE_FORMAT_THUMBNAIL,
            $this->cacheThumbs,
            $width,
            $height,
            $demand->getMethod(),
            self::IMAGE_NAME
        );
        $imageContent = self::IMAGE_CONTENT_RAW;

        $this->cacheDisk
            ->exists($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->cacheDisk
            ->get($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($imageContent);
        $this->cacheDisk
            ->path($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($cacheFile);

        $this->imageManager
            ->read($cacheFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->read(self::IMAGE_URL)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getImageThumbnail($demand);

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(self::RESPONSE_HTTP_OK, $result->getStatusCode());
        self::assertSame($imageContent, $result->getOriginalContent());
    }

    /**
     * @throws Exception
     */
    public function testGetImageThumbnailWhenImageInstanceIsExpected(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $image = $this->getImageMock();
        $cacheFile = sprintf(
            self::CACHE_FILE_FORMAT_THUMBNAIL,
            $this->cacheThumbs,
            $width,
            $height,
            self::THUMBNAIL_METHOD_CROP,
            self::IMAGE_NAME
        );

        $this->cacheDisk
            ->exists($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->cacheDisk
            ->path($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($cacheFile);
        $this->cacheDisk
            ->get($cacheFile)
            ->shouldNotBeCalled();

        $this->imageManager
            ->read($cacheFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->read(Argument::in([self::IMAGE_URL, self::FOO_RESOURCE]))
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getImageThumbnail(
            new Thumbnail(
                $this->request->reveal(),
                $this->guidedImage->reveal(),
                self::THUMBNAIL_METHOD_CROP,
                $width,
                $height,
                true
            )
        );

        self::assertSame($image, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetImageThumbnailWhenCacheFileExists(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $image = $this->getImageMock();
        $cacheFile = sprintf(
            self::CACHE_FILE_FORMAT_THUMBNAIL,
            $this->cacheThumbs,
            $width,
            $height,
            self::THUMBNAIL_METHOD_CROP,
            self::IMAGE_NAME
        );
        $imageContent = self::IMAGE_CONTENT_RAW;

        $this->cacheDisk
            ->exists($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->cacheDisk
            ->path($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($cacheFile);
        $this->cacheDisk
            ->get($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($imageContent);

        $this->imageManager
            ->read(Argument::in([$cacheFile, self::FOO_RESOURCE]))
            ->shouldBeCalledTimes(2)
            ->willReturn($image);
        $this->imageManager
            ->read(self::IMAGE_URL)
            ->shouldNotBeCalled();

        $result = $this->subject->getImageThumbnail(
            new Thumbnail(
                $this->request->reveal(),
                $this->guidedImage->reveal(),
                self::THUMBNAIL_METHOD_CROP,
                $width,
                $height
            )
        );

        self::assertInstanceOf(Response::class, $result);
        self::assertSame(self::RESPONSE_HTTP_OK, $result->getStatusCode());
        self::assertSame($imageContent, $result->getOriginalContent());
    }

    /**
     * @throws Exception
     */
    public function testGetImageThumbnailWhenDemandIsInvalid(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $demand = new Thumbnail(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            'invalid',
            $width,
            $height
        );
        $cacheFile = sprintf(
            self::CACHE_FILE_FORMAT_THUMBNAIL,
            $this->cacheThumbs,
            $width,
            $height,
            $demand->getMethod(),
            self::IMAGE_NAME
        );

        $this->cacheDisk
            ->exists($cacheFile)
            ->shouldNotBeCalled();
        $this->cacheDisk
            ->path($cacheFile)
            ->shouldNotBeCalled();
        $this->cacheDisk
            ->get($cacheFile)
            ->shouldNotBeCalled();

        $this->imageManager
            ->read($cacheFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->read(self::IMAGE_URL)
            ->shouldNotBeCalled();

        $this->logger
            ->warning(
                Argument::containingString('Invalid'),
                [
                    'method' => $demand->getMethod(),
                ]
            )
            ->shouldBeCalledTimes(1);

        $this->expectException(NotFoundHttpException::class);

        $this->subject->getImageThumbnail($demand);
    }

    /**
     * @throws Exception
     */
    public function testGetImageThumbnailWhenImageRetrievalFails(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $demand = new Thumbnail(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            self::THUMBNAIL_METHOD_COVER,
            $width,
            $height
        );
        $cacheFile = sprintf(
            self::CACHE_FILE_FORMAT_THUMBNAIL,
            $this->cacheThumbs,
            $width,
            $height,
            $demand->getMethod(),
            self::IMAGE_NAME
        );

        $this->cacheDisk
            ->exists($cacheFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->cacheDisk
            ->path($cacheFile)
            ->shouldNotBeCalled();
        $this->cacheDisk
            ->get($cacheFile)
            ->shouldNotBeCalled();

        $this->imageManager
            ->read($cacheFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->read(self::IMAGE_URL)
            ->shouldBeCalledTimes(1)
            ->willThrow(RuntimeException::class);

        $this->logger
            ->error(
                Argument::containingString('Exception'),
                Argument::type('array')
            )
            ->shouldBeCalledTimes(1);

        $this->expectException(NotFoundHttpException::class);

        $this->subject->getImageThumbnail($demand);
    }

    private function getImageMock(): ImageInterface|MockInterface
    {
        /** @var Origin|MockInterface $imageOrigin */
        $imageOrigin = Mockery::mock(Origin::class);
        $imageOrigin->shouldReceive('mediaType')
            ->andReturn(self::IMAGE_MEDIA_TYPE);
        $imageOrigin->shouldReceive('filePath')
            ->andReturn(self::IMAGE_FILE_PATH);

        $imageMethods = [
            'fill',
            'save',
            'resize',
            'resizeDown',
            'scale',
            'scaleDown',
            self::THUMBNAIL_METHOD_CROP,
            self::THUMBNAIL_METHOD_COVER,
        ];
        /** @var ImageInterface|MockInterface $image */
        $image = Mockery::mock(ImageInterface::class);
        $image->dirname = 'directory';
        $image->basename = 'basename';
        $image->shouldReceive(...$imageMethods)
            ->andReturn($image);
        $image->shouldReceive('origin')
            ->andReturn($imageOrigin);

        /** @var EncodedImageInterface|MockInterface $encodedImage */
        $encodedImage = Mockery::mock(EncodedImageInterface::class);
        $encodedImage->shouldReceive('toFilePointer')
            ->andReturn(self::FOO_RESOURCE);
        $image->shouldReceive('encode')
            ->andReturn($encodedImage);

        return $image;
    }

    private function getCacheFilename(int $width, int $height): string
    {
        return sprintf(
            self::CACHE_FILE_NAME_FORMAT_RESIZED,
            $this->cacheResized,
            $width,
            $height,
            1,
            0,
            self::IMAGE_NAME
        );
    }
}
