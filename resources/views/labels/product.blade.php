<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('Print barcode') }} — {{ $product->name }}</title>
    @vite(['resources/css/tajawal.css'])
    <style>
        *{box-sizing:border-box}html,body{margin:0;min-height:100%;color:#0f172a}body{background:#e2e8f0;font-family:Arial,Tahoma,sans-serif}.controls{position:sticky;top:0;z-index:10;display:flex;flex-wrap:wrap;align-items:center;gap:12px;padding:14px 18px;background:#0f172a;color:#fff}.controls input,.controls select{padding:8px;border:0;border-radius:7px}.controls button{padding:9px 16px;border:0;border-radius:8px;background:#10b981;color:#052e16;font-weight:700;cursor:pointer}.sheet{display:flex;flex-wrap:wrap;align-content:flex-start;gap:2mm;padding:10mm}.label{position:relative;flex:none;overflow:hidden;width:{{ (float)$template->width_mm }}mm;height:{{ (float)$template->height_mm }}mm;background:#fff;break-inside:avoid;page-break-inside:avoid;print-color-adjust:exact;-webkit-print-color-adjust:exact}.element{position:absolute;display:flex;flex-direction:column;overflow:hidden}.element.inverted{background:#0f172a;color:#fff}.element.inverted .barcode svg{filter:invert(1)}.currency-symbol{height:1em;width:auto;display:inline-block;vertical-align:-.12em}.element-content{max-width:100%;transform-origin:center}.element-content-fill{width:100%;height:100%;min-height:0}.barcode{display:flex;width:100%;height:100%;min-height:0;flex-direction:column}.barcode-graphic{display:flex;min-height:0;flex:1;align-items:center;justify-content:center}.barcode svg,.barcode-graphic svg{display:block;width:100%;height:100%}.barcode-human-text{flex:none;overflow:hidden;text-align:center;line-height:1.05;white-space:nowrap}.barcode-error{margin:auto;font-size:7pt;color:#dc2626;text-align:center}.shape{width:100%;border-color:#0f172a}.shape.line{height:0;border-top-style:solid}.shape.box{height:100%;border-style:solid}
        @media print{html,body{width:{{ (float)$template->width_mm }}mm;background:#fff}.controls{display:none}.sheet{display:block;min-height:0;padding:0}.label{break-after:page;page-break-after:always}.label:last-child{break-after:auto;page-break-after:auto}@page{size:{{ (float)$template->width_mm }}mm {{ (float)$template->height_mm }}mm;margin:0}}
    </style>
</head>
<body>
    <form class="controls" method="GET">
        <strong>{{ app()->isLocale('ar') && $product->name_ar ? $product->name_ar : $product->name }}</strong>
        <label>{{ __('Template') }} <select name="template" onchange="this.form.submit()">@foreach($company->labelTemplates as $option)<option value="{{ $option->id }}" @selected($template->id===$option->id)>{{ $option->name }} ({{ (float)$option->width_mm }}×{{ (float)$option->height_mm }}mm)</option>@endforeach</select></label>
        <label>{{ __('Copies') }} <input type="number" name="copies" min="1" max="100" value="{{ $copies }}" onchange="this.form.submit()"></label>
        <button type="button" onclick="window.print()">{{ __('Print') }}</button>
        <a href="{{ route('companies.label-templates.index',$company) }}" style="color:#6ee7b7">{{ __('Edit templates') }}</a>
    </form>
    <div class="sheet">@for($copy=0;$copy<$copies;$copy++)@include('labels.partials.label')@endfor</div>
</body>
</html>
