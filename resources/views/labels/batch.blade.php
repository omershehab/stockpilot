<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('Print labels') }}</title>
    @vite(['resources/css/tajawal.css'])
    <style>
        *{box-sizing:border-box}html,body{margin:0;min-height:100%;color:#0f172a}body{background:#dfe3e8;font-family:Arial,Tahoma,sans-serif}.toolbar{position:sticky;top:0;z-index:20;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 18px;background:#0f172a;color:#fff;box-shadow:0 2px 10px #0f172a33}.toolbar small{display:block;margin-top:2px;color:#94a3b8}.toolbar button{border:0;border-radius:9px;background:#10b981;padding:10px 18px;color:#052e16;font-weight:700;cursor:pointer}.preview{display:flex;min-height:100vh;flex-direction:column;align-items:center;gap:18px;padding:28px}.label{position:relative;flex:none;overflow:hidden;width:{{ (float)$template->width_mm }}mm;height:{{ (float)$template->height_mm }}mm;background:#fff;box-shadow:0 6px 18px #0f172a26;break-inside:avoid;print-color-adjust:exact;-webkit-print-color-adjust:exact}.element{position:absolute;display:flex;flex-direction:column;overflow:hidden}.element.inverted{background:#0f172a;color:#fff}.element.inverted .barcode svg{filter:invert(1)}.currency-symbol{height:1em;width:auto;display:inline-block;vertical-align:-.12em}.element-content{max-width:100%;transform-origin:center}.element-content-fill{width:100%;height:100%;min-height:0}.barcode{display:flex;width:100%;height:100%;min-height:0;flex-direction:column}.barcode-graphic{display:flex;min-height:0;flex:1;align-items:center;justify-content:center}.barcode svg,.barcode-graphic svg{display:block;width:100%;height:100%}.barcode-human-text{flex:none;overflow:hidden;text-align:center;line-height:1.05;white-space:nowrap}.barcode-error{margin:auto;font-size:7pt;color:#dc2626;text-align:center}.shape{width:100%;border-color:#0f172a}.shape.line{height:0;border-top-style:solid}.shape.box{height:100%;border-style:solid}.empty{margin:auto;padding:50px 24px;text-align:center;color:#64748b}.empty strong{display:block;margin-bottom:7px;color:#334155}
        @media print{html,body{width:{{ (float)$template->width_mm }}mm;background:#fff}.toolbar{display:none}.preview{display:block;min-height:0;padding:0}.label{box-shadow:none;break-after:page;page-break-after:always}.label:last-child{break-after:auto;page-break-after:auto}@page{size:{{ (float)$template->width_mm }}mm {{ (float)$template->height_mm }}mm;margin:0}}
    </style>
</head>
<body>
    @unless($embedded)
        <div class="toolbar"><div><strong>{{ __(':count label(s)', ['count' => $totalLabels]) }} · {{ $template->name }}</strong><small>{{ (float)$template->width_mm }} × {{ (float)$template->height_mm }} mm</small></div><button type="button" onclick="window.print()">{{ __('Print') }}</button></div>
    @endunless
    @if($entries->isEmpty())
        <div class="empty"><strong>{{ __('Add products to preview labels') }}</strong><span>{{ __('Use search to add products and select copy quantities') }}</span></div>
    @else
        <div class="preview">@foreach($entries as $entry)@for($copy=0;$copy<$entry['copies'];$copy++)@include('labels.partials.label', ['product' => $entry['product'], 'barcodeSvgs' => $entry['barcodeSvgs']])@endfor @endforeach</div>
    @endif
</body>
</html>
