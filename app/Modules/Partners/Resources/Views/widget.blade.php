<div class="widget widget-partners"
    <ul class="list-unstyled widget-partners">
        @foreach ($partners as $partner)
            @if ($partner->image)
                <li>
                    <a href="{!! url('partners/'.$partner->id) !!}" title="{{ $partner->title }}" target="_blank">
                        <img class="filter" src="{!! $partner->uploadPath().$partner->image !!}" alt="{{ $partner->title }}">
                    </a>
                </li>
            @endif
        @endforeach
    </ul>
</div>