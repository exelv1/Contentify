<?php namespace App\Modules\Messages\Http\Controllers;

use App\Modules\Messages\Message;
use HTML, FrontController;

class OutboxController extends FrontController {

    public function __construct()
    {
        $this->modelName = 'Message';

        parent::__construct();
    }

    public function index()
    {
        $this->pageView('messages::page_navigation', ['active' => 'outbox']);

        $this->indexPage([
            'buttons'       => null,
            'tableHead'     => [
                trans('app.new')            => 'new', 
                trans('app.title')          => 'title', 
                trans('messages::receiver') => 'receiver_id',
                trans('app.date')           => 'created_at',
            ],
            'tableRow'      => function($message)
            {
                $icon = HTML::fontIcon('close');
                if ($message->new) {
                    $icon = HTML::fontIcon('check');
                }

                $receiver = $message->receiver;

                return [
                    raw($icon),
                    raw(link_to('messages/'.$message->id.'/'.$message->slug, $message->title)),
                    raw(link_to('users/'.$receiver->id.'/'.$receiver->slug, $receiver->username)),
                    $message->created_at->dateTime(),
                ];
            },
            'actions'       => null,
            'permaFilter'   => function($query)
            {
                 return $query->whereCreatorId(user()->id)->whereCreatorVisible(true);
            },
            'brightenFirst' => false,
        ], 'front');
    }

}