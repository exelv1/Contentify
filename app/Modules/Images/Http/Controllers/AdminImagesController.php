<?php namespace App\Modules\Images\Http\Controllers;

use App\Modules\Images\Image;
use ModelHandlerTrait;
use HTML, BackController;

class AdminImagesController extends BackController {

    use ModelHandlerTrait;

    protected $icon = 'image';

    public function __construct()
    {
        $this->modelName = 'Image';

        parent::__construct();
    }

    public function index()
    {
        $this->indexPage([
            'tableHead' => [
                trans('app.id')         => 'id', 
                trans('app.image')      => null,
                trans('app.created_at') => 'created_at'
            ],
            'tableRow' => function($image)
            {
                $imgCode = HTML::image(
                    $image->uploadPath().'100/'.$image->image, 
                    'Image-Preview', ['class' => 'image']
                );
                $preview = '<a href="'.$image->uploadPath().$image->image.'" target="_blank">'.$imgCode.'</a>'
                            .'<br>'.e($image->tags);
                if ($image->gallery) {
                    $preview .= '<br>'.link_to('galleries/'.$image->gallery->id, 'Gallery: '.e($image->gallery->title));
                }

                return [
                    $image->id,
                    raw($preview),
                    $image->created_at
                ];
            },
            'searchFor' => 'tags'
        ]);
    }

}