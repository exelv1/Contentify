<?php namespace App\Modules\Galleries\Http\Controllers;

use App\Modules\Images\Image;
use App\Modules\Galleries\Gallery;
use Request, Config, URL, FrontController;

class GalleriesController extends FrontController {

    public function __construct()
    {
        $this->modelName = 'Gallery';

        parent::__construct();
    }

    public function index()
    {
        $perPage = Config::get('app.frontItemsPerPage');

        $galleries = Gallery::paginate($perPage)->setPath(Request::url());

        $this->pageView('galleries::index', compact('galleries'));
    }

    /**
     * Show a gallery
     * 
     * @param  int $id The id of the gallery
     * @return void
     */
    public function show($galleryId, $imageId = null)
    {
        $gallery = Gallery::findOrFail($galleryId);

        if ($imageId) {
            $image = Image::findOrFail($imageId);
        } else {
            if ($gallery->images) {
                $image = $gallery->images[0];
            } else {
                $this->alertError(trans('app.not_found'));
                return;
            }
        }

        $gallery->access_counter++;
        $gallery->save();

        $image->access_counter++;
        $image->save();

        $this->title($gallery->title);

        $this->pageView('galleries::show', compact('gallery', 'image'));
    }

}