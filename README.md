![Guided Image for Laravel](https://raw.githubusercontent.com/reliqarts/laravel-guided-image/master/docs/images/logo.png)

Guided Image is an image utility package for Laravel 5.x. It can be integrated seemlessly with your existing image model.

![Build Status](https://img.shields.io/circleci/project/ReliQArts/laravel-guided-image.svg?style=flat-square)

## Key Features

### Guided Routes

The package provides routes for generating resized/cropped/dummy images. 
- Routes are configurable you you may set any middleware and prefix you want.
- Generated images are *cached to disk* to avoid regenerating frequently accessed images and reduce overhead.

### Image file reuse

For situations where different instances of models use the same image.
- Image files are not actually deleted until they are no longer needed.  
- The package provides an overridable method used to determine when an image should be considered *safe* to delete. 

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

### Usage

To *use* Guided Image you must do just that from your *Image* model. :smirk:

Implement the *ReliQArts\GuidedImage\Contracts\Guided* contract and use the *ReliQArts\GuidedImage\Traits\Guided* trait, e.g:

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
See full example [here](https://github.com/ReliQArts/laravel-guided-image/blob/master/docs/examples/Image.php).

Use the *ReliQArts\GuidedImage\Traits\ImageGuider* trait from your *ImageController*, e.g:

```php
use ReliQArts\GuidedImage\Traits\ImageGuider;

class ImageController extends Controller
{
    use ImageGuider;
}

```

---
And... you're done! :ok_hand: