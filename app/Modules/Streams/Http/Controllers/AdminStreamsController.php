<?php namespace App\Modules\Streams\Http\Controllers;

use ModelHandlerTrait;
use App\Modules\Streams\Stream;
use Hover, HTML, BackController;

class AdminStreamsController extends BackController {

    use ModelHandlerTrait;

    protected $icon = 'video-camera';

    public function __construct()
    {
        $this->modelName = 'Stream';

        parent::__construct();
    }

    public function index()
    {
        $this->indexPage([
            'tableHead' => [
                trans('app.id')         => 'id', 
                trans('app.title')      => 'title',
                trans('app.provider')   => 'provider',
            ],
            'tableRow' => function($stream)
            {
                Hover::modelAttributes($stream, ['creator']);

                return [
                    $stream->id,
                    raw(Hover::pull(), $stream->title),
                    Stream::$providers[$stream->provider],
                ];             
            }
        ]);
    }

}