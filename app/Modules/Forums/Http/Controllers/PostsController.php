<?php namespace App\Modules\Forums\Http\Controllers;

use App\Modules\Forums\ForumPost;
use App\Modules\Forums\ForumReport;
use App\Modules\Forums\ForumThread;
use Response, Input, Redirect, FrontController;

class PostsController extends FrontController {

    /**
     * Show a single post
     *
     * @param int The id of the post
     */
    public function show($id)
    {
        $forumPost = ForumPost::isAccessible()->findOrFail($id);

        $this->pageView('forums::show_single_post', compact('forumPost'));
    }

    /**
     * Returns a forum post as JSON (as response to an AJAX call)
     * 
     * @param  int $id The ID of the post
     * @return mixed
     */
    public function get($id)
    {
        $forumPost = ForumPost::isAccessible()->findOrFail($id);

        return Response::json($forumPost);
    }

    /**
     * Stores a post
     *
     * @param int The id of the thread
     */
    public function store($id)
    {
        $forumPost = new ForumPost(Input::all());
        $forumPost->creator_id = user()->id;
        $forumPost->thread_id = $id;

        $forumThread = ForumThread::isAccessible()->findOrFail($id);

        if ($forumThread->closed) {
            $this->alertError(trans('forums::closed_info'));
            return;
        }

        $valid = $forumPost->save();

        if (! $valid) {
            return Redirect::to('forums/threads/create')
                ->withInput()->withErrors($forumPost->getErrors()); // TODO: Keep this redirect??
        }  

        $forumThread->posts_count++;
        $forumThread->forceSave();

        $user = user();
        $user->posts_count++;
        $user->save();

        $this->alertFlash(trans('app.created', ['Post']));
        return Redirect::to('forums/threads/'.$forumPost->thread->id.'/'.$forumPost->thread->slug);
    }

    /**
     * Edits a post
     * 
     * @param int The id of the post
     */
    public function edit($id)
    {
        $forumPost = ForumPost::isAccessible()->findOrFail($id);

        if (! ($this->hasAccessUpdate() or $forumPost->creator_id == user()->id)) {
            return $this->alertError(trans('app.access_denied'));
        }

        /*
         * If the post is a root post, edit the thread instead of the post.
         */
        if ($forumPost->root) {
            return Redirect::to('forums/threads/edit/'.$forumPost->thread_id);
        }

        $this->pageView('forums::post_form', compact('forumPost'));
    }

    /**
     * Updates a post
     * 
     * @param int The id of the post
     */
    public function update($id)
    {
        $forumPost = ForumPost::isAccessible()->findOrFail($id);

        if (! ($this->hasAccessUpdate() or $forumPost->creator_id == user()->id)) {
            return $this->alertError(trans('app.access_denied'));
        }

        /*
         * If the post is a root post, delete the thread instead of the post.
         */
        if ($forumPost->root) {
            // TODO: What to do? Redirect to the following?
            //return Redirect::action('forums/thread/update/'.$forumPost->forum_thread_id);
        }

        $forumPost->fill(Input::all());
        $forumPost->updater_id = user()->id;
        $valid = $forumPost->save();
        
        if (! $valid) {
            return Redirect::to('forums/threads/edit/'.$forumPost->id)
                ->withInput()->withErrors($forumPost->getErrors()); // TODO: Keep this redirect??
        }

        $this->alertFlash(trans('app.updated', ['Post']));

        return Redirect::to('forums/threads/'.$forumPost->thread->id.'/'.$forumPost->thread->slug);
    }

    /**
     * Deletes a post
     * 
     * @param int The id of the post
     */
    public function delete($id)
    {
        if (! $this->checkAccessDelete()) return;

        $forumPost = ForumPost::isAccessible()->findOrFail($id);       

        /*
         * If the post is a root post, delete the thread instead of the post.
         */
        if ($forumPost->root) {
            return Redirect::action('forums/threads/delete/'.$forumPost->thread_id);
        }

        $user = user();
        $user->posts_count--;
        $user->save();

        $thread = $forumPost->thread;

        $forumPost->delete();

        $thread->refresh();
    }

    /**
     * Reports a post
     * 
     * @param int The id of the post
     */
    public function report($id)
    {
        $forumPost = ForumPost::isAccessible()->findOrFail($id); 

        $forumReport = ForumReport::whereCreatorId(user()->id)->wherePostId($id)->first();   

        if ($forumReport) {
            $this->alertFlash(trans('forums::already_reported'));
        } else {
            $forumReport = new ForumReport(['forum_post_id' => $id]);
            $forumReport->creator_id = user()->id;
            $forumReport->save();

            $this->alertFlash(trans('forums::reported'));
        }

        return Redirect::to('forums/threads/'.$forumPost->thread->id.'/'.$forumPost->thread->slug);
    }

}