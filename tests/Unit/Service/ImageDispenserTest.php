<?php

/** @noinspection PhpParamsInspection */

declare(strict_types=1);

namespace ReliqArts\GuidedImage\Tests\Unit\Service;

use AspectMock\Proxy\FuncProxy;
use AspectMock\Test;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Mockery;
use Mockery\MockInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use ReliqArts\GuidedImage\Contract\ConfigProvider;
use ReliqArts\GuidedImage\Contract\ImageDispenser as ImageDispenserContract;
use ReliqArts\GuidedImage\Contract\Logger;
use ReliqArts\GuidedImage\Demand\Dummy;
use ReliqArts\GuidedImage\Demand\Resize;
use ReliqArts\GuidedImage\Demand\Thumbnail;
use ReliqArts\GuidedImage\Service\ImageDispenser;
use ReliqArts\GuidedImage\Tests\Fixtures\Model\GuidedImage;
use ReliqArts\GuidedImage\Tests\Unit\AspectMockedTestCase;
use ReliqArts\Services\Filesystem;

/**
 * Class ImageDispenserTest.
 *
 * @coversDefaultClass  \ReliqArts\GuidedImage\Service\ImageDispenser
 *
 * @internal
 */
final class ImageDispenserTest extends AspectMockedTestCase
{
    private const SKIM_RESIZED_SUB_DIRECTORY = 'RESIZED';
    private const SKIM_THUMBS_SUB_DIRECTORY = 'THUMBS';
    private const RESPONSE_HTTP_OK = Response::HTTP_OK;
    private const RESPONSE_HTTP_NOT_FOUND = Response::HTTP_NOT_FOUND;
    private const LAST_MODIFIED = 21343;
    private const IMAGE_NAME = 'my-image';
    private const IMAGE_URL = '//image_url';
    private const SKIM_FILE_NAME_FORMAT_RESIZED = '%s/%d-%d-_-%d_%d_%s';
    private const SKIM_FILE_FORMAT_THUMBNAIL = '%s/%d-%d-_-%s_%s';
    private const IMAGE_WIDTH = 100;
    private const IMAGE_HEIGHT = 200;
    private const THUMBNAIL_METHOD_CROP = 'crop';
    private const THUMBNAIL_METHOD_FIT = 'fit';
    private const IMAGE_ENCODING_FORMAT = 'png';
    private const IMAGE_ENCODING_QUALITY = 90;

    /**
     * @var ConfigProvider|ObjectProphecy
     */
    private $configProvider;

    /**
     * @var Filesystem|ObjectProphecy
     */
    private $filesystem;

    /**
     * @var ImageManager|ObjectProphecy
     */
    private $imageManager;

    /**
     * @var Logger|ObjectProphecy
     */
    private $logger;

    /**
     * @var ObjectProphecy|Request
     */
    private $request;

    /**
     * @var GuidedImage|ObjectProphecy
     */
    private $guidedImage;

    /**
     * @var ImageDispenserContract
     */
    private $subject;

    /**
     * @var string
     */
    private $skimThumbs;

    /**
     * @var string
     */
    private $skimResized;

    /**
     * @var FuncProxy
     */
    private $storagePathFunc;

    /**
     * @var FuncProxy
     */
    private $md5FileFunc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider = $this->prophesize(ConfigProvider::class);
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->imageManager = $this->prophesize(ImageManager::class);
        $this->logger = $this->prophesize(Logger::class);
        $this->request = $this->prophesize(Request::class);
        $this->guidedImage = $this->prophesize(GuidedImage::class);
        $this->skimResized = self::SKIM_RESIZED_SUB_DIRECTORY;
        $this->skimThumbs = self::SKIM_THUMBS_SUB_DIRECTORY;
        $this->namespace = 'ReliqArts\\GuidedImage\\Services';
        $this->storagePathFunc = Test::func(
            $this->namespace,
            'storage_path',
            function ($path) {
                return $path;
            }
        );
        $this->md5FileFunc = Test::func(
            $this->namespace,
            'md5_file',
            function ($path) {
                return $path;
            }
        );

        $this->configProvider
            ->getSkimResizedDirectory()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::SKIM_RESIZED_SUB_DIRECTORY);
        $this->configProvider
            ->getSkimThumbsDirectory()
            ->shouldBeCalledTimes(1)
            ->willReturn(self::SKIM_THUMBS_SUB_DIRECTORY);
        $this->configProvider
            ->getCacheDaysHeader()
            ->willReturn(2);
        $this->configProvider
            ->getAdditionalHeaders()
            ->willReturn([]);
        $this->configProvider
            ->getImageEncodingFormat()
            ->willReturn(self::IMAGE_ENCODING_FORMAT);
        $this->configProvider
            ->getImageEncodingQuality()
            ->willReturn(self::IMAGE_ENCODING_QUALITY);

        $this->filesystem
            ->isDirectory($this->skimResized)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->filesystem
            ->makeDirectory($this->skimResized, Argument::cetera())
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem
            ->isDirectory($this->skimThumbs)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);
        $this->filesystem
            ->makeDirectory($this->skimThumbs, Argument::cetera())
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem
            ->lastModified(Argument::type('string'))
            ->willReturn(self::LAST_MODIFIED);

        $this->request
            ->header(Argument::cetera())
            ->willReturn('');

        $this->guidedImage
            ->getName()
            ->willReturn(self::IMAGE_NAME);
        $this->guidedImage
            ->getUrl()
            ->willReturn(self::IMAGE_URL);

        $this->subject = new ImageDispenser(
            $this->configProvider->reveal(),
            $this->filesystem->reveal(),
            $this->imageManager->reveal(),
            $this->logger->reveal()
        );
    }

    /**
     * @covers ::__construct
     * @covers ::emptySkimDirectories
     */
    public function testEmptyCache(): void
    {
        $this->filesystem
            ->deleteDirectory($this->skimResized)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);
        $this->filesystem
            ->deleteDirectory($this->skimThumbs)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);

        $result = $this->subject->emptySkimDirectories();

        $this->assertTrue($result);
    }

    /**
     * @covers ::__construct
     * @covers ::getDummyImage
     * @covers ::prepSkimDirectories
     */
    public function testGetDummyImage(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $color = 'fee';
        $fill = 'f00';
        $imageResponse = new Response();
        $image = $this->getImageMock($imageResponse);

        $this->imageManager
            ->canvas($width, $height, $color)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getDummyImage(
            new Dummy($width, $height, $color, $fill)
        );

        $this->assertSame($imageResponse, $result);

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
    }

    /**
     * @covers ::__construct
     * @covers ::getDummyImage
     * @covers ::prepSkimDirectories
     */
    public function testGetDummyImageWhenImageInstanceIsExpected(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $color = 'fee';
        $fill = 'f00';
        $image = $this->getImageMock();

        $this->imageManager
            ->canvas($width, $height, $color)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getDummyImage(
            new Dummy($width, $height, $color, $fill, true)
        );

        $this->assertSame($image, $result);

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
    }

    /**
     * @covers ::__construct
     * @covers ::getDefaultHeaders
     * @covers ::getImageHeaders
     * @covers ::getResizedImage
     * @covers ::makeImageWithEncoding
     * @covers ::prepSkimDirectories
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
        $skimFile = sprintf(
            self::SKIM_FILE_NAME_FORMAT_RESIZED,
            $this->skimResized,
            $width,
            $height,
            1,
            0,
            self::IMAGE_NAME
        );
        $imageContent = 'RAW';

        $this->filesystem
            ->exists($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);

        $this->filesystem
            ->get($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($imageContent);

        $this->imageManager
            ->make($skimFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->make(self::IMAGE_URL)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getResizedImage($demand);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(self::RESPONSE_HTTP_OK, $result->getStatusCode());
        $this->assertSame($imageContent, $result->getOriginalContent());

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
        $this->md5FileFunc->verifyInvokedOnce();
    }

    /**
     * @covers ::__construct
     * @covers ::getResizedImage
     * @covers ::makeImageWithEncoding
     * @covers ::prepSkimDirectories
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
            true,
            false,
            true
        );
        $skimFile = sprintf(
            self::SKIM_FILE_NAME_FORMAT_RESIZED,
            $this->skimResized,
            $width,
            $height,
            1,
            0,
            self::IMAGE_NAME
        );

        $this->filesystem
            ->exists($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);

        $this->filesystem
            ->get($skimFile)
            ->shouldNotBeCalled();

        $this->imageManager
            ->make($skimFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->make(self::IMAGE_URL)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getResizedImage($demand);

        $this->assertSame($image, $result);

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
        $this->md5FileFunc->verifyNeverInvoked();
    }

    /**
     * @covers ::__construct
     * @covers ::getDefaultHeaders
     * @covers ::getImageHeaders
     * @covers ::getResizedImage
     * @covers ::makeImageWithEncoding
     * @covers ::prepSkimDirectories
     */
    public function testGetResizedImageWhenSkimFileExists(): void
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
        $skimFile = sprintf(
            self::SKIM_FILE_NAME_FORMAT_RESIZED,
            $this->skimResized,
            $width,
            $height,
            1,
            0,
            self::IMAGE_NAME
        );
        $imageContent = 'RAW';

        $this->filesystem
            ->exists($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);

        $this->filesystem
            ->get($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($imageContent);

        $this->imageManager
            ->make($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);
        $this->imageManager
            ->make(self::IMAGE_URL)
            ->shouldNotBeCalled();

        $result = $this->subject->getResizedImage($demand);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(self::RESPONSE_HTTP_OK, $result->getStatusCode());
        $this->assertSame($imageContent, $result->getOriginalContent());

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
        $this->md5FileFunc->verifyInvokedOnce();
    }

    /**
     * @covers ::__construct
     * @covers ::getDefaultHeaders
     * @covers ::getImageHeaders
     * @covers ::getResizedImage
     * @covers ::makeImageWithEncoding
     * @covers ::prepSkimDirectories
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
        $skimFile = sprintf(
            self::SKIM_FILE_NAME_FORMAT_RESIZED,
            $this->skimResized,
            $width,
            $height,
            1,
            0,
            self::IMAGE_NAME
        );

        $this->filesystem
            ->exists($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);

        $this->filesystem
            ->get($skimFile)
            ->shouldBeCalledTimes(1)
            ->willThrow(FileNotFoundException::class);

        $this->imageManager
            ->make($skimFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->make(self::IMAGE_URL)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $this->logger
            ->error(
                Argument::containingString('Exception'),
                Argument::that(function (array $argument) use ($skimFile) {
                    return in_array($skimFile, $argument, true) && in_array(self::IMAGE_URL, $argument, true);
                })
            )
            ->shouldBeCalledTimes(1);

        $result = $this->subject->getResizedImage($demand);

        $this->assertEmpty($result);

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
        $this->md5FileFunc->verifyNeverInvoked();
        $this->abortFunc->verifyInvokedOnce();
    }

    /**
     * @covers ::__construct
     * @covers ::getDefaultHeaders
     * @covers ::getImageHeaders
     * @covers ::getImageThumbnail
     * @covers ::makeImageWithEncoding
     * @covers ::prepSkimDirectories
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
        $skimFile = sprintf(
            self::SKIM_FILE_FORMAT_THUMBNAIL,
            $this->skimThumbs,
            $width,
            $height,
            $demand->getMethod(),
            self::IMAGE_NAME
        );
        $imageContent = 'RAW';

        $this->filesystem
            ->exists($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);

        $this->filesystem
            ->get($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($imageContent);

        $this->imageManager
            ->make($skimFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->make(self::IMAGE_URL)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getImageThumbnail($demand);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(self::RESPONSE_HTTP_OK, $result->getStatusCode());
        $this->assertSame($imageContent, $result->getOriginalContent());

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
        $this->md5FileFunc->verifyInvokedOnce();
    }

    /**
     * @covers ::__construct
     * @covers ::getImageThumbnail
     * @covers ::makeImageWithEncoding
     * @covers ::prepSkimDirectories
     */
    public function testGetImageThumbnailWhenImageInstanceIsExpected(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $image = $this->getImageMock();
        $demand = new Thumbnail(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            self::THUMBNAIL_METHOD_CROP,
            $width,
            $height,
            true
        );
        $skimFile = sprintf(
            self::SKIM_FILE_FORMAT_THUMBNAIL,
            $this->skimThumbs,
            $width,
            $height,
            $demand->getMethod(),
            self::IMAGE_NAME
        );

        $this->filesystem
            ->exists($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);

        $this->filesystem
            ->get($skimFile)
            ->shouldNotBeCalled();

        $this->imageManager
            ->make($skimFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->make(self::IMAGE_URL)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);

        $result = $this->subject->getImageThumbnail($demand);

        $this->assertSame($image, $result);

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
        $this->md5FileFunc->verifyNeverInvoked();
    }

    /**
     * @covers ::__construct
     * @covers ::getDefaultHeaders
     * @covers ::getImageHeaders
     * @covers ::getImageThumbnail
     * @covers ::makeImageWithEncoding
     * @covers ::prepSkimDirectories
     */
    public function testGetImageThumbnailWhenSkimFileExists(): void
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
        $skimFile = sprintf(
            self::SKIM_FILE_FORMAT_THUMBNAIL,
            $this->skimThumbs,
            $width,
            $height,
            $demand->getMethod(),
            self::IMAGE_NAME
        );
        $imageContent = 'RAW';

        $this->filesystem
            ->exists($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(true);

        $this->filesystem
            ->get($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($imageContent);

        $this->imageManager
            ->make($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn($image);
        $this->imageManager
            ->make(self::IMAGE_URL)
            ->shouldNotBeCalled();

        $result = $this->subject->getImageThumbnail($demand);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(self::RESPONSE_HTTP_OK, $result->getStatusCode());
        $this->assertSame($imageContent, $result->getOriginalContent());

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
        $this->md5FileFunc->verifyInvokedOnce();
    }

    /**
     * @covers ::__construct
     * @covers ::getImageThumbnail
     * @covers ::makeImageWithEncoding
     * @covers ::prepSkimDirectories
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
        $skimFile = sprintf(
            self::SKIM_FILE_FORMAT_THUMBNAIL,
            $this->skimThumbs,
            $width,
            $height,
            $demand->getMethod(),
            self::IMAGE_NAME
        );

        $this->filesystem
            ->exists($skimFile)
            ->shouldNotBeCalled();

        $this->filesystem
            ->get($skimFile)
            ->shouldNotBeCalled();

        $this->imageManager
            ->make($skimFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->make(self::IMAGE_URL)
            ->shouldNotBeCalled();

        $this->logger
            ->warning(
                Argument::containingString('Invalid'),
                [
                    'method' => $demand->getMethod(),
                ]
            )
            ->shouldBeCalledTimes(1);

        $result = $this->subject->getImageThumbnail($demand);

        $this->assertSame(self::RESPONSE_HTTP_NOT_FOUND, $result);

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
        $this->md5FileFunc->verifyNeverInvoked();
        $this->abortFunc->verifyInvokedOnce();
    }

    /**
     * @covers ::__construct
     * @covers ::getDefaultHeaders
     * @covers ::getImageHeaders
     * @covers ::getImageThumbnail
     * @covers ::makeImageWithEncoding
     * @covers ::prepSkimDirectories
     */
    public function testGetImageThumbnailWhenImageRetrievalFails(): void
    {
        $width = self::IMAGE_WIDTH;
        $height = self::IMAGE_HEIGHT;
        $demand = new Thumbnail(
            $this->request->reveal(),
            $this->guidedImage->reveal(),
            self::THUMBNAIL_METHOD_FIT,
            $width,
            $height
        );
        $skimFile = sprintf(
            self::SKIM_FILE_FORMAT_THUMBNAIL,
            $this->skimThumbs,
            $width,
            $height,
            $demand->getMethod(),
            self::IMAGE_NAME
        );

        $this->filesystem
            ->exists($skimFile)
            ->shouldBeCalledTimes(1)
            ->willReturn(false);

        $this->filesystem
            ->get($skimFile)
            ->shouldNotBeCalled();

        $this->imageManager
            ->make($skimFile)
            ->shouldNotBeCalled();
        $this->imageManager
            ->make(self::IMAGE_URL)
            ->shouldBeCalledTimes(1)
            ->willThrow(NotReadableException::class);

        $this->logger
            ->error(
                Argument::containingString('Exception'),
                Argument::that(function (array $argument) use ($skimFile) {
                    return in_array($skimFile, $argument, true) && in_array(self::IMAGE_URL, $argument, true);
                })
            )
            ->shouldBeCalledTimes(1);

        $result = $this->subject->getImageThumbnail($demand);

        $this->assertEmpty($result);

        $this->storagePathFunc->verifyInvokedMultipleTimes(2);
        $this->md5FileFunc->verifyNeverInvoked();
        $this->abortFunc->verifyInvokedOnce();
    }

    /**
     * @param Response $imageResponse
     *
     * @return Image|MockInterface
     */
    private function getImageMock(Response $imageResponse = null): MockInterface
    {
        $imageMethodNames = [
            'fill',
            'resize',
            'save',
            'encode',
            self::THUMBNAIL_METHOD_CROP,
            self::THUMBNAIL_METHOD_FIT,
        ];
        /** @var Image|MockInterface $image */
        $image = Mockery::mock(
            Image::class,
            [
                'response' => $imageResponse ?? new Response(),
            ]
        );
        $image->dirname = 'directory';
        $image->basename = 'basename';
        $image
            ->shouldReceive(...$imageMethodNames)
            ->andReturn($image);

        return $image;
    }
}
