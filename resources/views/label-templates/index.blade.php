<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold">{{ __('Barcode label designer') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('Design at true physical size, add product variables, and preview the result with a specific product.') }}</p>
        </div>
    </x-slot>

    <style>
        .designer-barcode-svg svg{display:block;width:100%;height:100%}.designer-currency-symbol{display:inline-block;height:1em;width:auto;vertical-align:-.12em}.designer-content{max-width:100%;transform-origin:center}.designer-content-fill{width:100%;height:100%;min-height:0}.designer-barcode{display:flex;width:100%;height:100%;min-height:0;flex-direction:column}.designer-barcode-graphic{display:flex;min-height:0;flex:1;align-items:center;justify-content:center}.designer-barcode-text{flex:none;overflow:hidden;text-align:center;line-height:1.05;white-space:nowrap}
    </style>

    <div class="grid gap-6 xl:grid-cols-[230px_minmax(0,1fr)]">
        <aside class="space-y-5">
            <section class="card p-5">
                <h2 class="font-bold">{{ __('Templates') }}</h2>
                <div class="mt-4 space-y-2">
                    @foreach($templates as $template)
                        <a class="block rounded-xl border p-3 text-sm {{ $selected?->id===$template->id ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 hover:bg-slate-50' }}" href="{{ route('companies.label-templates.index', [$company,'template'=>$template->id]) }}">
                            <strong class="block">{{ $template->name }}</strong>
                            <small class="text-slate-500">{{ (float)$template->width_mm }} × {{ (float)$template->height_mm }} mm {{ $template->is_default ? '· '.__('Default') : '' }}</small>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="card p-5" x-data="{ open:false }">
                <button type="button" class="btn-secondary w-full" @click="open=!open">+ {{ __('New custom size') }}</button>
                <form x-show="open" x-cloak method="POST" action="{{ route('companies.label-templates.store',$company) }}" class="mt-4 space-y-3">
                    @csrf
                    <input class="field" name="name" placeholder="{{ __('Template name') }}" required>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="label">{{ __('Width mm') }}</label><input class="field" type="number" min="20" max="300" step="0.1" name="width_mm" value="60" required></div>
                        <div><label class="label">{{ __('Height mm') }}</label><input class="field" type="number" min="10" max="300" step="0.1" name="height_mm" value="40" required></div>
                    </div>
                    <input type="hidden" name="is_default" value="0">
                    <input type="hidden" name="layout" value='[{"id":"name","type":"name","x":3,"y":3,"width":35,"height":6,"fontSize":11,"visible":true},{"id":"price","type":"price","x":42,"y":3,"width":15,"height":6,"fontSize":10,"visible":true},{"id":"barcode","type":"barcode","content":"@{{barcode}}","barcodeFormat":"code128","x":3,"y":11,"width":54,"height":20,"visible":true},{"id":"code","type":"code","x":3,"y":33,"width":54,"height":4,"fontSize":9,"visible":true}]'>
                    <button class="btn-primary w-full">{{ __('Create 60×40 template') }}</button>
                </form>
            </section>
        </aside>

        @if($selected)
            @php
                $designerConfig = [
                    'elements' => $selected->layout,
                    'width' => (float) $selected->width_mm,
                    'height' => (float) $selected->height_mm,
                    'name' => $selected->name,
                    'isDefault' => $selected->is_default,
                    'products' => $previewProducts,
                    'barcodePreviewUrl' => route('companies.label-templates.barcode-preview', $company),
                    'elementLabels' => [
                        'text' => __('Text'),
                        'barcode' => __('Barcode'),
                        'line' => __('Line'),
                        'box' => __('Box'),
                        'name' => __('Name'),
                        'sku' => 'SKU',
                        'price' => __('Price'),
                        'code' => __('Code'),
                        'customText' => __('Custom text'),
                    ],
                    'variableLabels' => [
                        'display_name' => __('Product display name'),
                        'name' => __('Product name (English)'),
                        'name_ar' => __('Product name (Arabic)'),
                        'sku' => 'SKU',
                        'barcode' => __('Primary barcode'),
                        'price' => __('Price'),
                        'price_with_currency' => __('Price with currency'),
                        'cost' => __('Cost'),
                        'currency' => __('Currency'),
                        'description' => __('Description'),
                        'unit' => __('Unit'),
                        'category' => __('Category'),
                        'batch' => __('First batch number'),
                        'expiry' => __('First batch expiry'),
                        'company' => __('Company name'),
                    ],
                    'barcodeFormats' => [
                        ['value' => 'code128', 'label' => 'Code 128'],
                        ['value' => 'code39', 'label' => 'Code 39'],
                        ['value' => 'code93', 'label' => 'Code 93'],
                        ['value' => 'ean13', 'label' => 'EAN-13'],
                        ['value' => 'ean8', 'label' => 'EAN-8'],
                        ['value' => 'upca', 'label' => 'UPC-A'],
                        ['value' => 'itf14', 'label' => 'ITF-14'],
                        ['value' => 'codabar', 'label' => 'Codabar'],
                        ['value' => 'qr', 'label' => 'QR Code'],
                        ['value' => 'datamatrix', 'label' => 'Data Matrix'],
                    ],
                    'fontLabels' => [
                        ['value' => 'inherit', 'label' => __('Interface font')],
                        ['value' => 'tajawal', 'label' => 'Tajawal'],
                        ['value' => 'arial', 'label' => 'Arial'],
                        ['value' => 'helvetica', 'label' => 'Helvetica'],
                        ['value' => 'times', 'label' => 'Times New Roman'],
                        ['value' => 'courier', 'label' => 'Courier New'],
                        ['value' => 'georgia', 'label' => 'Georgia'],
                        ['value' => 'verdana', 'label' => 'Verdana'],
                    ],
                ];
            @endphp
            <section x-data="labelDesigner({{ Js::from($designerConfig) }})">
                <form method="POST" action="{{ route('companies.label-templates.update',[$company,$selected]) }}" @submit="syncLayout">
                    @csrf
                    @method('PUT')
                    <div class="card mb-5 grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-5">
                        <div class="lg:col-span-2"><label class="label">{{ __('Template name') }}</label><input class="field" name="name" x-model="name" required></div>
                        <div><label class="label">{{ __('Width (mm)') }}</label><input class="field" type="number" min="20" max="300" step="0.1" name="width_mm" x-model.number="width" @change="clampElements" required></div>
                        <div><label class="label">{{ __('Height (mm)') }}</label><input class="field" type="number" min="10" max="300" step="0.1" name="height_mm" x-model.number="height" @change="clampElements" required></div>
                        <div class="flex items-end"><label class="flex items-center gap-2 pb-3 text-sm"><input type="hidden" name="is_default" value="0"><input type="checkbox" name="is_default" value="1" x-model="isDefault" class="rounded border-slate-300 text-emerald-600"> {{ __('Default template') }}</label></div>
                        <input type="hidden" name="layout" x-ref="layoutInput">
                    </div>

                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_310px]">
                        <div class="card min-h-[620px] overflow-auto bg-slate-100 p-5">
                            <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
                                <div>
                                    <strong>{{ __('Canvas preview') }}</strong>
                                    <small class="ms-2 text-slate-500" x-text="`${width} × ${height} mm`"></small>
                                    <span class="ms-2 rounded-full bg-blue-100 px-2 py-1 text-[11px] font-semibold text-blue-700">{{ __('100% equals browser print size') }}</span>
                                </div>
                                <div class="flex flex-wrap items-end gap-3">
                                    <div class="min-w-52">
                                        <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Preview product') }}</label>
                                        <select class="field !py-1.5" x-model.number="previewProductId" @change="previewProductChanged">
                                            <template x-for="product in products" :key="product.id"><option :value="product.id" x-text="`${product.label} · ${product.variables.sku}`"></option></template>
                                        </select>
                                    </div>
                                    <div class="flex items-center rounded-lg border border-slate-300 bg-white shadow-sm">
                                        <button type="button" class="px-2.5 py-1.5 text-sm font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-40" @click="zoomOut" :disabled="zoom <= 25" aria-label="{{ __('Zoom out') }}">−</button>
                                        <button type="button" class="min-w-16 border-x border-slate-200 px-2 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="resetZoom" title="{{ __('Reset zoom') }}" x-text="`${zoom}%`"></button>
                                        <button type="button" class="px-2.5 py-1.5 text-sm font-bold text-slate-600 hover:bg-slate-50 disabled:opacity-40" @click="zoomIn" :disabled="zoom >= 400" aria-label="{{ __('Zoom in') }}">+</button>
                                    </div>
                                </div>
                            </div>

                            <div x-show="products.length === 0" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ __('Add a product to enable variable and barcode preview.') }}</div>
                            <div class="inline-block rounded-xl border border-slate-300 bg-slate-200 p-6 shadow-inner">
                                <div
                                    class="relative overflow-hidden bg-white shadow-xl"
                                    data-designer-canvas
                                    :data-label-width-mm="width"
                                    :data-label-height-mm="height"
                                    :data-zoom="zoom"
                                    :style="`width:${width*scale}px;height:${height*scale}px`"
                                    @click.self="selectedIndex=null"
                                >
                                    <template x-for="(element,index) in elements" :key="element.id">
                                        <div x-show="element.visible" class="absolute flex select-none flex-col overflow-hidden" :style="elementBoxStyle(element)" :data-designer-element="element.id">
                                            <div
                                                @pointerdown.prevent="startDrag($event,index)"
                                                @click.stop="selectedIndex=index"
                                                class="absolute inset-0 flex cursor-move flex-col overflow-hidden border"
                                                :class="selectedIndex===index ? 'border-emerald-500 ring-1 ring-emerald-500':'border-transparent hover:border-slate-300'"
                                                :style="`background-color:${element.inverted?'#0f172a':'transparent'};color:${element.inverted?'#fff':'#0f172a'};justify-content:${isTextElement(element)?({top:'flex-start',middle:'center',bottom:'flex-end'}[element.verticalAlign]??'center'):'center'}`"
                                            >
                                                <template x-if="isTextElement(element)">
                                                    <div class="designer-content w-full overflow-hidden px-px" :style="`${contentStyle(element)};${lineClampStyle(element)}`" x-html="displayTextHtml(element)"></div>
                                                </template>
                                                <template x-if="element.type==='barcode'">
                                                    <div class="designer-content designer-content-fill" :style="contentStyle(element)">
                                                        <div class="designer-barcode">
                                                            <div class="designer-barcode-graphic" :style="`padding:${quietZonePx(element)}px`">
                                                                <div x-show="barcodeSvgs[element.id]" class="designer-barcode-svg h-full w-full" :class="element.inverted ? 'invert' : ''" x-html="barcodeSvgs[element.id]"></div>
                                                                <div x-show="!barcodeSvgs[element.id]" class="h-full w-full" :class="isTwoDimensional(element) ? 'bg-[repeating-conic-gradient(#0f172a_0_25%,transparent_0_50%)] bg-[length:8px_8px]' : 'bg-[repeating-linear-gradient(90deg,#0f172a_0_2px,transparent_2px_4px,#0f172a_4px_5px,transparent_5px_8px)]'"></div>
                                                            </div>
                                                            <div x-show="element.showText && !isTwoDimensional(element)" class="designer-barcode-text" :style="`font-size:${barcodeTextSizePx(element)}px`" x-text="resolveContent(element.content)"></div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template x-if="element.type==='line'"><div class="w-full border-solid border-slate-900" :style="`border-top-width:${borderWidthPx(element)}px`"></div></template>
                                                <template x-if="element.type==='box'"><div class="h-full w-full border-solid border-slate-900" :style="`border-width:${borderWidthPx(element)}px`"></div></template>
                                            </div>
                                            <button x-show="selectedIndex===index" type="button" class="absolute -bottom-1.5 -right-1.5 z-20 h-3.5 w-3.5 cursor-se-resize rounded-sm border-2 border-white bg-emerald-600 shadow" @pointerdown.stop.prevent="startResize($event,index)" @click.stop aria-label="{{ __('Resize element') }}" title="{{ __('Drag to resize') }}"></button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <aside class="card p-5">
                            <h3 class="font-bold">{{ __('Elements') }}</h3>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <template x-for="type in ['text','barcode','line','box']" :key="type"><button type="button" class="btn-secondary !px-2 !py-1.5" @click="addElement(type)" :disabled="elements.length >= 30" :class="elements.length >= 30 ? 'cursor-not-allowed opacity-50' : ''" x-text="`+ ${elementLabels[type]}`"></button></template>
                            </div>
                            <div class="mt-4 max-h-52 space-y-2 overflow-y-auto pe-1">
                                <template x-for="(element,index) in elements" :key="element.id">
                                    <button type="button" @click="selectedIndex=index" class="flex w-full items-center justify-between rounded-xl border px-3 py-2 text-sm" :class="selectedIndex===index?'border-emerald-500 bg-emerald-50':'border-slate-200'">
                                        <span class="min-w-0 truncate" x-text="element.type === 'text' ? (resolveContent(element.content) || elementLabels.text) : elementLabels[element.type]"></span>
                                        <input type="checkbox" x-model="element.visible" @click.stop class="rounded border-slate-300 text-emerald-600">
                                    </button>
                                </template>
                            </div>

                            <template x-if="selected">
                                <div class="mt-5 space-y-4 border-t border-slate-200 pt-5">
                                    <div class="flex items-center justify-between gap-2">
                                        <h4 class="text-sm font-bold" x-text="elementLabels[selected.type]"></h4>
                                        <button x-show="isRemovable(selected)" type="button" class="text-xs font-semibold text-red-600 hover:text-red-700" @click="removeSelected">{{ __('Remove') }}</button>
                                    </div>

                                    <div x-show="selected.type === 'text'" class="space-y-2">
                                        <div class="flex items-center justify-between"><label class="label !mb-0">{{ __('Content') }}</label><select class="rounded-lg border-slate-300 py-1 text-xs text-emerald-700" @change="insertVariable($event.target.value);$event.target.value=''" value=""><option value="">+ {{ __('Add variable') }}</option><template x-for="(label,key) in variableLabels" :key="key"><option :value="key" x-text="label"></option></template></select></div>
                                        <textarea class="field !py-1.5" rows="3" maxlength="300" x-model="selected.content" placeholder="{{ __('Enter text or add product variables') }}"></textarea>
                                    </div>

                                    <div x-show="selected.type === 'barcode'" class="space-y-3">
                                        <div><div class="flex items-center justify-between"><label class="label !mb-0">{{ __('Content') }}</label><select class="rounded-lg border-slate-300 py-1 text-xs text-emerald-700" @change="insertVariable($event.target.value);$event.target.value=''" value=""><option value="">+ {{ __('Add variable') }}</option><template x-for="(label,key) in variableLabels" :key="key"><option :value="key" x-text="label"></option></template></select></div><input class="field mt-2 !py-1.5" maxlength="300" x-model="selected.content" @input.debounce.350ms="refreshBarcode(selected)" placeholder="@{{barcode}}"></div>
                                        <div><label class="label">{{ __('Barcode type') }}</label><select class="field !py-1.5" x-model="selected.barcodeFormat" @change="refreshBarcode(selected)"><template x-for="format in barcodeFormats" :key="format.value"><option :value="format.value" x-text="format.label"></option></template></select></div>
                                        <div x-show="selected.barcodeFormat === 'qr'"><label class="label">{{ __('QR error correction') }}</label><select class="field !py-1.5" x-model="selected.errorCorrection" @change="refreshBarcode(selected)"><option value="L">L · 7%</option><option value="M">M · 15%</option><option value="Q">Q · 25%</option><option value="H">H · 30%</option></select></div>
                                        <label x-show="!isTwoDimensional(selected)" class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="selected.showText" class="rounded border-slate-300 text-emerald-600"> {{ __('Show value below barcode') }}</label>
                                        <div x-show="selected.showText && !isTwoDimensional(selected)"><label class="label">{{ __('Value text size (pt)') }}</label><input class="field !py-1.5" type="number" min="4" max="36" step="0.5" x-model.number="selected.textSize"></div>
                                        <div><label class="label">{{ __('Quiet zone (mm)') }}</label><input class="field !py-1.5" type="number" min="0" max="10" step="0.1" x-model.number="selected.quietZone"></div>
                                        <p x-show="barcodeErrors[selected.id]" class="rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700">{{ __('The selected value is not valid for this barcode type.') }}</p>
                                    </div>

                                    <div x-show="isTextElement(selected)" class="space-y-3 border-t border-slate-100 pt-3">
                                        <div><label class="label">{{ __('Font') }}</label><select class="field !py-1.5" x-model="selected.fontFamily"><template x-for="font in fontLabels" :key="font.value"><option :value="font.value" x-text="font.label"></option></template></select></div>
                                        <div class="grid grid-cols-[1fr_auto] gap-2"><div><label class="label">{{ __('Size (pt)') }}</label><input class="field !py-1.5" type="number" min="4" max="72" step="0.5" x-model.number="selected.fontSize"></div><div class="flex items-end"><div class="flex overflow-hidden rounded-xl border border-slate-300"><button type="button" class="px-3 py-2 font-bold" :class="selected.bold?'bg-slate-900 text-white':'bg-white'" @click="selected.bold=!selected.bold">B</button><button type="button" class="border-s border-slate-300 px-3 py-2 italic" :class="selected.italic?'bg-slate-900 text-white':'bg-white'" @click="selected.italic=!selected.italic">I</button><button type="button" class="border-s border-slate-300 px-3 py-2 underline" :class="selected.underline?'bg-slate-900 text-white':'bg-white'" @click="selected.underline=!selected.underline">U</button></div></div></div>
                                        <div><label class="label">{{ __('Horizontal alignment') }}</label><div class="grid grid-cols-3 overflow-hidden rounded-xl border border-slate-300"><button type="button" class="px-2 py-2 text-xs" :class="selected.textAlign==='left'?'bg-emerald-100 text-emerald-800':'bg-white'" @click="selected.textAlign='left'">{{ __('Left') }}</button><button type="button" class="border-x border-slate-300 px-2 py-2 text-xs" :class="selected.textAlign==='center'?'bg-emerald-100 text-emerald-800':'bg-white'" @click="selected.textAlign='center'">{{ __('Center') }}</button><button type="button" class="px-2 py-2 text-xs" :class="selected.textAlign==='right'?'bg-emerald-100 text-emerald-800':'bg-white'" @click="selected.textAlign='right'">{{ __('Right') }}</button></div></div>
                                        <div><label class="label">{{ __('Vertical alignment') }}</label><div class="grid grid-cols-3 overflow-hidden rounded-xl border border-slate-300"><button type="button" class="px-2 py-2 text-xs" :class="selected.verticalAlign==='top'?'bg-emerald-100 text-emerald-800':'bg-white'" @click="selected.verticalAlign='top'">{{ __('Top') }}</button><button type="button" class="border-x border-slate-300 px-2 py-2 text-xs" :class="selected.verticalAlign==='middle'?'bg-emerald-100 text-emerald-800':'bg-white'" @click="selected.verticalAlign='middle'">{{ __('Middle') }}</button><button type="button" class="px-2 py-2 text-xs" :class="selected.verticalAlign==='bottom'?'bg-emerald-100 text-emerald-800':'bg-white'" @click="selected.verticalAlign='bottom'">{{ __('Bottom') }}</button></div></div>
                                        <div class="grid grid-cols-2 gap-2"><div><label class="label">{{ __('Line height') }}</label><input class="field !py-1.5" type="number" min="0.7" max="3" step="0.05" x-model.number="selected.lineHeight"></div><div><label class="label">{{ __('Max lines') }}</label><input class="field !py-1.5" type="number" min="0" max="10" step="1" x-model.number="selected.maxLines"><small class="text-[10px] text-slate-400">{{ __('0 means unlimited') }}</small></div></div>
                                    </div>

                                    <div x-show="isTextElement(selected) || selected.type === 'barcode'" class="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
                                        <div><label class="label">{{ __('Rotation (degrees)') }}</label><input class="field !py-1.5" type="number" min="-360" max="360" step="1" x-model.number="selected.rotation"></div>
                                        <label class="flex items-end gap-2 pb-2 text-sm"><input type="checkbox" x-model="selected.inverted" class="rounded border-slate-300 text-emerald-600"> {{ __('Inverted colors') }}</label>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
                                        <div><label class="label">X mm</label><input class="field !py-1.5" type="number" step="0.1" min="0" x-model.number="selected.x" @change="clampElements"></div>
                                        <div><label class="label">Y mm</label><input class="field !py-1.5" type="number" step="0.1" min="0" x-model.number="selected.y" @change="clampElements"></div>
                                        <div><label class="label">{{ __('Width') }}</label><input class="field !py-1.5" type="number" step="0.1" min="1" x-model.number="selected.width" @change="clampElements"></div>
                                        <div><label class="label">{{ __('Height') }}</label><input class="field !py-1.5" type="number" step="0.1" min="1" x-model.number="selected.height" @change="clampElements"></div>
                                        <div x-show="['line','box'].includes(selected.type)" class="col-span-2"><label class="label">{{ __('Border thickness (pt)') }}</label><input class="field !py-1.5" type="number" min="0.5" max="10" step="0.5" x-model.number="selected.borderWidth"></div>
                                    </div>
                                </div>
                            </template>

                            <div x-show="!selected" class="mt-5 rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-400">{{ __('Select an element to edit its properties.') }}</div>
                            <button class="btn-primary mt-6 w-full">{{ __('Save label design') }}</button>
                            <p class="mt-3 text-center text-xs text-slate-500">{{ __('All geometry is stored in millimetres and type is stored in print points.') }}</p>
                        </aside>
                    </div>
                </form>
            </section>
        @endif
    </div>
</x-app-layout>
