@if (! isset($forumThread))
    {!! Form::model($forumPost, ['url' => ['forums/posts/'.$forumPost->id], 'method' => 'PUT']) !!}
@else
    {!! Form::open(['url' => 'forums/posts/'.$forumThread->id]) !!}
@endif
    {!! Form::smartTextarea('text', trans('app.text'), false) !!}

    {!! Form::actions(['submit'], false) !!}    
{!! Form::close() !!}