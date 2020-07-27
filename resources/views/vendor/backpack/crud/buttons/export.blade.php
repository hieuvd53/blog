@if($crud->hasAccess('export'))
    <a href="{{ url($crud->route.'/export') }}" class="btn btn-primary"><i class="la la-download"></i> Export {{ $crud->entity_name }}</a>
@endif
