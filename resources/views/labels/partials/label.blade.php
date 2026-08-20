@php
    $contentResolver = app(\App\Services\LabelContentResolver::class);
    $labelVariables = $contentResolver->variables($product, $company);
    $fontFamilies = [
        'inherit' => 'inherit',
        'tajawal' => '"Tajawal", Tahoma, Arial, sans-serif',
        'arial' => 'Arial, sans-serif',
        'helvetica' => 'Helvetica, Arial, sans-serif',
        'times' => '"Times New Roman", serif',
        'courier' => '"Courier New", monospace',
        'georgia' => 'Georgia, serif',
        'verdana' => 'Verdana, sans-serif',
    ];
@endphp

<div
    class="label"
    data-label-width-mm="{{ (float) $template->width_mm }}"
    data-label-height-mm="{{ (float) $template->height_mm }}"
>
    @foreach($template->layout as $element)
        @if($element['visible'] ?? true)
            @php
                $type = $element['type'];
                $isText = in_array($type, ['name', 'sku', 'price', 'code', 'text'], true);
                $rotation = (float) ($element['rotation'] ?? 0);
                $vertical = ['top' => 'flex-start', 'middle' => 'center', 'bottom' => 'flex-end'][$element['verticalAlign'] ?? 'middle'] ?? 'center';
                $styles = [
                    'left:'.((float) $element['x']).'mm',
                    'top:'.((float) $element['y']).'mm',
                    'width:'.((float) $element['width']).'mm',
                    'height:'.((float) $element['height']).'mm',
                ];

                if ($isText) {
                    $styles[] = 'font-size:'.((float) ($element['fontSize'] ?? 10)).'pt';
                    $styles[] = 'font-family:'.($fontFamilies[$element['fontFamily'] ?? 'inherit'] ?? 'inherit');
                    $styles[] = 'font-weight:'.(($element['bold'] ?? in_array($type, ['name', 'price'], true)) ? '700' : '400');
                    $styles[] = 'font-style:'.(($element['italic'] ?? false) ? 'italic' : 'normal');
                    $styles[] = 'text-decoration:'.(($element['underline'] ?? false) ? 'underline' : 'none');
                    $styles[] = 'text-align:'.($element['textAlign'] ?? 'center');
                    $styles[] = 'line-height:'.((float) ($element['lineHeight'] ?? 1.05));
                    $styles[] = 'justify-content:'.$vertical;
                }

                $maxLines = (int) ($element['maxLines'] ?? 0);
                $lineClampStyle = $maxLines > 0
                    ? 'display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:'.$maxLines.';overflow:hidden'
                    : '';
            @endphp
            <div
                class="element {{ ($element['inverted'] ?? false) ? 'inverted' : '' }}"
                data-element-id="{{ $element['id'] }}"
                data-element-type="{{ $type }}"
                style="{{ implode(';', $styles) }}"
            >
                <div class="element-content {{ $type === 'barcode' ? 'element-content-fill' : '' }}" style="transform:rotate({{ $rotation }}deg)">
                    @switch($type)
                        @case('name')
                            <span style="{{ $lineClampStyle }}">{{ $labelVariables['display_name'] }}</span>
                            @break
                        @case('sku')
                            <span style="{{ $lineClampStyle }}">{{ $labelVariables['sku'] }}</span>
                            @break
                        @case('price')
                            <x-currency-text :value="$labelVariables['price'].' '.$company->currency" :currency="$company->currency" style="{{ $lineClampStyle }}" />
                            @break
                        @case('barcode')
                            @php
                                $barcodeFormat = $element['barcodeFormat'] ?? 'code128';
                                $barcodeValue = $contentResolver->resolve($element['content'] ?? null, $product, $company, '{{barcode}}');
                                $isTwoDimensional = in_array($barcodeFormat, ['qr', 'datamatrix'], true);
                            @endphp
                            <div class="barcode barcode-{{ $barcodeFormat }}">
                                <div class="barcode-graphic" style="padding:{{ (float) ($element['quietZone'] ?? 0.5) }}mm">
                                    @if(! empty($barcodeSvgs[$element['id']]))
                                        {!! $barcodeSvgs[$element['id']] !!}
                                    @else
                                        <span class="barcode-error">{{ __('Invalid value for :format', ['format' => strtoupper($barcodeFormat)]) }}</span>
                                    @endif
                                </div>
                                @if(($element['showText'] ?? false) && ! $isTwoDimensional)
                                    <div class="barcode-human-text" style="font-size:{{ (float) ($element['textSize'] ?? 8) }}pt">{{ $barcodeValue }}</div>
                                @endif
                            </div>
                            @break
                        @case('code')
                            <span style="{{ $lineClampStyle }}">{{ $labelVariables['barcode'] }}</span>
                            @break
                        @case('text')
                            <x-currency-text :value="$contentResolver->resolve($element['content'] ?? $element['text'] ?? '', $product, $company)" :currency="$company->currency" style="white-space:pre-wrap;overflow-wrap:anywhere;{{ $lineClampStyle }}" />
                            @break
                        @case('line')
                            <div class="shape line" style="border-top-width:{{ (float) ($element['borderWidth'] ?? 1) }}pt"></div>
                            @break
                        @case('box')
                            <div class="shape box" style="border-width:{{ (float) ($element['borderWidth'] ?? 1) }}pt"></div>
                            @break
                    @endswitch
                </div>
            </div>
        @endif
    @endforeach
</div>
