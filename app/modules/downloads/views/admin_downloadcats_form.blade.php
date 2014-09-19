{{-- Form Template generated by FormGenerator --}}

{{ Form::errors($errors) }}

@if (isset($model))
{{ Form::model($model, ['route' => ['admin.downloadcats.update', $model->id], 'files' => true, 'method' => 'PUT']) }}
@else
{{ Form::open(['url' => 'admin/downloadcats']) }}
@endif
        {{ Form::smartText('title', trans('app.title')) }}
        <!-- {{ Form::smartImageFile('image', trans('app.image')) }} -->
        
    {{ Form::actions() }}
{{ Form::close() }}