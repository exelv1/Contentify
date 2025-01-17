<?php namespace App\Modules\Contact\Http\Controllers;

use App\Modules\Contact\ContactMessage;
use Input, Redirect, FrontController;

class ContactController extends FrontController {

    public function index()
    {
        $this->pageView('contact::form');
    }

    public function store()
    {
        $msg = new ContactMessage(Input::all());
        $msg->ip = getenv('REMOTE_ADDR');

        $okay = $msg->save();
        
        if ($okay) {
            $this->alertSuccess(trans('contact::message_sent'));
        } else {
            return Redirect::to('contact')->withInput()->withErrors($msg->getErrors());
        }
    }
    
}