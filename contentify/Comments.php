<?php namespace Contentify;

use View, Response, Comment, Input, Request;

class Comments {

    /**
     * Directly outputs comments and the comment form.
     * 
     * @param  string $foreignType Identifier for the content the comments are related to (usually a model class)
     * @param  int    $foreignId   ID, if the comments are related to a certain model instance
     * @return string
     */
    public function show($foreignType, $foreignId)
    {
        $perPage = Config::get('app.frontItemsPerPage');

        $comments = Comment::where('foreign_type', '=', $foreignType)->
            where('foreign_id', '=', $foreignId)->paginate($perPage)->setPath(Request::url());

        echo View::make('comments.show', compact('comments', 'foreignType', 'foreignId'))->render();

        if (user() and user()->hasAccess('comments', PERM_CREATE)) {
            echo View::make('comments.form', compact('foreignType', 'foreignId'))->render();
        }
    }

    /**
     * Returns comments and respects pagination
     * 
     * @param  string $foreignType Identifier for the content the comments are related to (usually a model class)
     * @param  int    $foreignId   ID, if the comments are related to a certain model instance
     * @return string
     */
    public function paginate($foreignType, $foreignId)
    {
        $perPage = Config::get('app.frontItemsPerPage');

        $comments = Comment::where('foreign_type', '=', $foreignType)
            ->where('foreign_id', '=', $foreignId)->paginate($perPage)->setPath(Request::url());

        return View::make('comments.paginate', compact('comments'))->render();
    }

    /**
     * Stores a comment
     * 
     * @param  string   $foreignType The foreign type identifier
     * @param  int      $foreignId   The foreign id (can be 0)
     * @return \Illuminate\Http\Response
     */
    public function store($foreignType, $foreignId)
    {
        if (! user()->hasAccess('comments', PERM_CREATE)) {
            return;
        }

        $comment = new Comment(Input::all());
        $comment->foreign_type  = $foreignType;
        $comment->foreign_id    = $foreignId;
        $comment->creator_id    = user()->id;
        $comment->updater_id    = user()->id;

        $okay = $comment->save();

        if (! $okay) {
            return Response::make(trans('app.comment_create_fail', $comment->getErrors()->all()), 500);
        } else {
            return View::make('comments.comment', compact('comment', 'foreignType', 'foreignId'));
        }
    }

    /**
     * Returns a comment as JSON (as response to an AJAX call)
     * 
     * @param  int $id The ID of the comment
     * @return mixed
     */
    public function get($id)
    {
        $comment = Comment::findOrFail($id);

        return Response::json($comment);
    }

    /**
     * Edits a comment
     * 
     * @param  int $id The ID of the comment
     * @return void
     */
    public function edit($id)
    {
        $comment = Comment::findOrFail($id);

        if (! user() or (! user()->hasAccess('comments', PERM_UPDATE) and $comment->creator->id != user()->id)) {
            return Response::make(trans('app.access_denied'), 500);
        }

        echo View::make('comments.form', compact('comment'))->render();
    }

    /**
     * Updates a comment
     * 
     * @param  int $id The ID of the comment
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $comment = Comment::findOrFail($id);

        if (! user() or (! user()->hasAccess('comments', PERM_UPDATE) and $comment->creator->id != user()->id)) {
            return Response::make(trans('app.access_denied'), 500);
        }

        $comment->fill(Input::all());
        $comment->updater_id = user()->id;

        $okay = $comment->save();

        if (! $okay) {
            return Response::make(trans('app.comment_create_fail', $comment->getErrors()->all()), 500);
        } else {
            return View::make('comments.comment', compact('comment', 'foreignType', 'foreignId'));
        }
    }

    /**
     * Deletes a comment
     * 
     * @param  int $id The ID of the comment
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $comment = Comment::findOrFail($id);

        if (! user() or (! user()->hasAccess('comments', PERM_DELETE) and $comment->creator->id != user()->id)) {
            return Response::make(trans('app.access_denied'), 500);
        }

        $comment->delete();

        return Response::make('1', 200);
    }
    
}