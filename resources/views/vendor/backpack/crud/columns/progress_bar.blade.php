@php
$percent = intval($entry->getAttribute($column["totalAttribute"])) === 0 ? 0 : floor(intval($entry->getAttribute($column["meterAttribute"])) / intval($entry->getAttribute($column["totalAttribute"])) * 100);
$displayPercentage = $column["percent"] ?? false;
@endphp
<div class="progress-group">
    <div class="progress-group-header align-items-end">
        <div class="ml-auto mr-2">
        @if($displayPercentage)
            {{$percent}}%
        @else
            {{$entry->getAttribute($column["meterAttribute"])}} / {{$entry->getAttribute($column["totalAttribute"])}}
        @endif
        </div>
    </div>
    <div class="progress-group-bars">
        <div class="progress">
            <div class="progress-bar {{$column["class"] ?? ''}}" role="progressbar" aria-valuenow="{{$percent}}" aria-valuemin="0" aria-valuemax="100" style="width: {{$percent}}%">
        </div>
    </div>
</div>
