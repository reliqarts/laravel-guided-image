# Laravel Guided Image

Guided Image is an image utility package for Laravel 5.x based on Intervention Image.

![Build Status](https://img.shields.io/circleci/project/reliqarts/laravel-guided-image.svg?style=flat-square)
[![StyleCI](https://styleci.io/repos/71434979/shield?branch=master)](https://styleci.io/repos/71434979)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/reliqarts/laravel-guided-image.svg?style=flat-square)](https://scrutinizer-ci.com/g/reliqarts/laravel-guided-image/)
[![Built For Laravel](https://img.shields.io/badge/built%20for-laravel-red.svg?style=flat-square)](http://laravel.com)
[![License](https://poser.pugx.org/reliqarts/guided-image/license?format=flat-square)](https://packagist.org/packages/reliqarts/guided-image)
[![Total Downloads](https://poser.pugx.org/reliqarts/guided-image/downloads?format=flat-square)](https://packagist.org/packages/reliqarts/guided-image)
[![Latest Stable Version](https://poser.pugx.org/reliqarts/guided-image/version?format=flat-square)](https://packagist.org/packages/reliqarts/guided-image)
[![Latest Unstable Version](https://poser.pugx.org/reliqarts/guided-image/v/unstable?format=flat-square)](//packagist.org/packages/reliqarts/guided-image)

&nbsp;

[![Guided Image for Laravel](https://raw.githubusercontent.com/reliqarts/laravel-guided-image/master/docs/images/logo.png)](#)

## Key Features

Guided Image can be integrated seemlessly with your existing image model.

### Guided Routes

The package provides routes for generating resized/cropped/dummy images. 
- Routes are configurable you you may set any middleware and prefix you want.
- Generated images are *cached to disk* to avoid regenerating frequently accessed images and reduce overhead.

### Image file reuse

For situations where different instances of models use the same image.
- The package provides a safe removal feature which allows images to be detached and only deleted from disk if not being used elsewhere.
- An overridable method used to determine when an image should be considered *safe* to delete. 

## Installation & Usage

### Installation

Install via composer; in console: 
```
composer require reliqarts/guided-image
``` 
or require in *composer.json*:
```js
{
    "require": {
        "reliqarts/guided-image": "^1.0"
    }
}
```
then run `composer update` in your terminal to pull it in.

Once this has finished, you will need to add the service provider to the providers array in your app.php config as follows:

```php
ReliQArts\GuidedImage\GuidedImageServiceProvider::class,
```

Finally, publish package resources and configuration:

```
php artisan vendor:publish --provider="ReliQArts\GuidedImage\GuidedImageServiceProvider"
``` 

You may opt to publish only configuration by using the `config` tag:

```
php artisan vendor:publish --provider="ReliQArts\GuidedImage\GuidedImageServiceProvider" --tag="config"
``` 
You may publish migrations in a similar manner using the tag `migrations`.

### Setup

Set the desired environment variables so the package knows your image model, controller(s), etc. 

Example environment config:
```
GUIDED_IMAGE_MODEL=Image
GUIDED_IMAGE_CONTROLLER=ImageController
GUIDED_IMAGE_ROUTE_PREFIX=image
GUIDED_IMAGE_SKIM_DIR=images
```

These variables, and more are explained within the [config](https://github.com/ReliQArts/laravel-guided-image/blob/master/src/config/config.php) file.

And... it's ready! :ok_hand:

### Usage

To *use* Guided Image you must do just that from your *Image* model. :smirk:

Implement the `ReliQArts\GuidedImage\Contracts\Guided` contract and use the `ReliQArts\GuidedImage\Traits\Guided` trait, e.g:

```php
use Illuminate\Database\Eloquent\Model;
use ReliQArts\GuidedImage\Traits\Guided as GuidedTrait;
use ReliQArts\GuidedImage\Contracts\Guided as GuidedContract;

class Image extends Model implements GuidedContract
{
    use GuidedTrait;

    // ... properties and methods
}
```
See example [here](https://github.com/ReliQArts/laravel-guided-image/blob/master/docs/examples/Image.php).

Implement the `ReliQArts\GuidedImage\Contracts\ImageGuider` contract and use the `ReliQArts\GuidedImage\Traits\ImageGuider` trait from your *ImageController*, e.g:

```php
use ReliQArts\GuidedImage\Contracts\ImageGuider as ImageGuiderContract;
use ReliQArts\GuidedImage\Traits\ImageGuider as ImageGuiderTrait;

class ImageController extends Controller implements ImageGuiderContract
{
    use ImageGuiderTrait;
}
```
See example [here](https://github.com/ReliQArts/laravel-guided-image/blob/master/docs/examples/ImageController.php).

#### Features

##### Safely Remove Image (dissociate & conditionally delete the image)

An guided image instance is removed by calling the *remove* method. e.g:

```php
$oldImage->remove($force);
```
`$force` is optional and is `false` by default.

##### Link Generation

You may retrieve guided links to resized or cropped images like so:

```php
// resized image:
$linkToImage = $image->routeResized([
    '550',      // width
    '_',        // height, 'null' is OK 
    '_',        // keep aspect ratio? true by default, 'null' is OK
    '_',        // allow upsize? false by default, 'null' is OK
]);

// thumbnail:
$linkToImage = $image->routeResized([
    'crop',     // method: crop|fit
    '550',      // width
    '_',        // height, 'null' is OK 
], 'thumb');
```
**NB:** In the above example "_" is treated as *null*. You may specify which strings should be treated as *null* by the routes in `config/guidedimage.php`. 

Have a look at the [Guided contract](https://github.com/ReliQArts/laravel-guided-image/blob/master/src/ReliQArts/GuidedImage/Contracts/Guided.php) for more info on model functions.

For more info on controller functions see the [ImageGuider contract](https://github.com/ReliQArts/laravel-guided-image/blob/master/src/ReliQArts/GuidedImage/Contracts/ImageGuider.php).

##### Routes

Your actually routes will depend heavily on your custom configuration. Here is an example of what the routes may look like:

```
|        | GET|HEAD | image/.dummy\{width}-{height}/{color?}/{fill?}/{object?}            | image.dummy           | App\Http\Controllers\ImageController@dummy                             | web          |
|        | GET|HEAD | image/.image\{image}\{width}-{height}\{aspect?}\{upsize?}\{object?} | image.resize          | App\Http\Controllers\ImageController@resized                           | web          |
|        | GET|HEAD | image/.thumb\{image}\m.{method}\{width}-{height}\{object?}          | image.thumb           | App\Http\Controllers\ImageController@thumb                             | web          |
|        | GET|HEAD | image/empty-cache                                                   | image.empty-cache     | App\Http\Controllers\ImageController@emptyCache                        | web          |

```
