{{-- loom:meta
{
    "name": "meta",
    "slug": "meta",
    "enabled": true,
    "parameters": [
        {
            "name": "author",
            "label": "Author",
            "type": "text",
            "default": "Sarab"
        },
        {
            "name": "description",
            "label": "Description",
            "type": "text",
            "default": "Sarab - Fast Food & Restaurant HTML Template",
            "row": 2,
            "colClass": "col-12"
        }
    ],
    "updated_at": "2026-06-30T16:28:25+00:00"
}
--}}

@verbatim
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="author" content="{{ $segmentData['author'] }}">
<meta name="description" content="{{ $segmentData['description'] }}">
@endverbatim
