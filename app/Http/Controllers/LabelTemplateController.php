<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\LabelTemplate;
use App\Services\BarcodeSvgRenderer;
use App\Services\LabelContentResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LabelTemplateController extends Controller
{
    public function index(Request $request, Company $company, LabelContentResolver $contentResolver): View
    {
        $templates = $company->labelTemplates()->orderByDesc('is_default')->orderBy('name')->get();
        $selected = $templates->firstWhere('id', (int) $request->query('template')) ?? $templates->first();
        $previewProducts = $company->products()
            ->where('is_active', true)
            ->with(['barcodes', 'batches', 'category'])
            ->orderBy('name')
            ->limit(1000)
            ->get()
            ->map(fn ($product): array => [
                'id' => $product->id,
                'label' => app()->isLocale('ar') && $product->name_ar ? $product->name_ar : $product->name,
                'variables' => $contentResolver->variables($product, $company),
            ])
            ->values();

        return view('label-templates.index', compact('company', 'templates', 'selected', 'previewProducts'));
    }

    public function barcodePreview(
        Request $request,
        Company $company,
        BarcodeSvgRenderer $renderer,
        LabelContentResolver $contentResolver,
    ): Response {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'content' => ['nullable', 'string', 'max:300'],
            'format' => ['required', Rule::in(BarcodeSvgRenderer::FORMATS)],
            'errorCorrection' => ['nullable', Rule::in(['L', 'M', 'Q', 'H'])],
        ]);
        $product = $company->products()
            ->with(['barcodes', 'batches', 'category'])
            ->findOrFail($data['product_id']);
        $value = $contentResolver->resolve($data['content'] ?? null, $product, $company, '{{barcode}}');
        $svg = $renderer->renderValue($value, $data['format'], $data);

        abort_if($svg === null, 422, __('The selected value is not valid for this barcode type.'));

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function store(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validated($request);
        $data['company_id'] = $company->id;
        $template = $company->labelTemplates()->create($data);

        return redirect()->route('companies.label-templates.index', [$company, 'template' => $template->id])->with('success', __('Label template created.'));
    }

    public function update(Request $request, Company $company, LabelTemplate $labelTemplate): RedirectResponse
    {
        abort_unless($labelTemplate->company_id === $company->id, 404);
        $data = $this->validated($request);

        DB::transaction(function () use ($company, $labelTemplate, $data) {
            if ($data['is_default']) {
                $company->labelTemplates()->where('id', '!=', $labelTemplate->id)->update(['is_default' => false]);
            }
            $labelTemplate->update($data);
        });

        return back()->with('success', __('Label design saved.'));
    }

    public function duplicate(Company $company, LabelTemplate $labelTemplate): RedirectResponse
    {
        abort_unless($labelTemplate->company_id === $company->id, 404);

        $copy = $labelTemplate->replicate();
        $copy->name = $this->duplicateName($company, $labelTemplate->name);
        $copy->is_default = false;
        $copy->save();

        return back()->with('success', __('Label design duplicated.'));
    }

    public function destroy(Company $company, LabelTemplate $labelTemplate): RedirectResponse
    {
        abort_unless($labelTemplate->company_id === $company->id, 404);

        if ($company->labelTemplates()->count() <= 1) {
            return back()->withErrors(['template' => __('The final label design cannot be deleted.')]);
        }

        DB::transaction(function () use ($company, $labelTemplate) {
            $wasDefault = $labelTemplate->is_default;
            $labelTemplate->delete();

            if ($wasDefault) {
                $company->labelTemplates()->orderBy('name')->first()?->update(['is_default' => true]);
            }
        });

        return back()->with('success', __('Label design deleted.'));
    }

    private function duplicateName(Company $company, string $name): string
    {
        $base = mb_substr($name, 0, 90).' copy';
        $candidate = $base;
        $suffix = 2;

        while ($company->labelTemplates()->where('name', $candidate)->exists()) {
            $suffixText = ' '.$suffix++;
            $candidate = mb_substr($base, 0, 100 - mb_strlen($suffixText)).$suffixText;
        }

        return $candidate;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'width_mm' => ['required', 'numeric', 'min:20', 'max:300'],
            'height_mm' => ['required', 'numeric', 'min:10', 'max:300'],
            'layout' => ['required', 'json'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $layout = json_decode($data['layout'], true);
        if (! is_array($layout) || count($layout) > 30) {
            throw ValidationException::withMessages(['layout' => 'The label layout is invalid.']);
        }

        $allowedTypes = ['name', 'sku', 'price', 'barcode', 'code', 'text', 'line', 'box'];
        $textTypes = ['name', 'sku', 'price', 'code', 'text'];
        $fontFamilies = ['inherit', 'tajawal', 'arial', 'helvetica', 'times', 'courier', 'georgia', 'verdana'];
        $textAlignments = ['left', 'center', 'right'];
        $verticalAlignments = ['top', 'middle', 'bottom'];
        $templateWidth = (float) $data['width_mm'];
        $templateHeight = (float) $data['height_mm'];
        $ids = [];
        $data['layout'] = collect($layout)->map(function ($element, int $index) use (
            $allowedTypes,
            $fontFamilies,
            &$ids,
            $templateHeight,
            $templateWidth,
            $textAlignments,
            $textTypes,
            $verticalAlignments,
        ) {
            if (! is_array($element) || ! in_array($element['type'] ?? null, $allowedTypes, true)) {
                throw ValidationException::withMessages(['layout' => 'The label contains an unsupported field.']);
            }

            $id = preg_replace('/[^a-z0-9_-]/i', '', (string) ($element['id'] ?? '')) ?: $element['type'].'-'.($index + 1);
            if (in_array($id, $ids, true)) {
                $id .= '-'.($index + 1);
            }
            $ids[] = $id;
            $x = min(max(0, round((float) ($element['x'] ?? 0), 2)), max(0, $templateWidth - 1));
            $y = min(max(0, round((float) ($element['y'] ?? 0), 2)), max(0, $templateHeight - 1));

            $normalized = [
                'id' => $id,
                'type' => $element['type'],
                'x' => $x,
                'y' => $y,
                'width' => min(max(1, round((float) ($element['width'] ?? 10), 2)), $templateWidth - $x),
                'height' => min(max(1, round((float) ($element['height'] ?? 5), 2)), $templateHeight - $y),
                'visible' => (bool) ($element['visible'] ?? true),
            ];

            if (in_array($element['type'], $textTypes, true)) {
                $normalized += [
                    'fontSize' => min(72, max(4, round((float) ($element['fontSize'] ?? 10), 1))),
                    'fontFamily' => in_array($element['fontFamily'] ?? null, $fontFamilies, true) ? $element['fontFamily'] : 'inherit',
                    'bold' => (bool) ($element['bold'] ?? in_array($element['type'], ['name', 'price'], true)),
                    'italic' => (bool) ($element['italic'] ?? false),
                    'underline' => (bool) ($element['underline'] ?? false),
                    'textAlign' => in_array($element['textAlign'] ?? null, $textAlignments, true) ? $element['textAlign'] : 'center',
                    'verticalAlign' => in_array($element['verticalAlign'] ?? null, $verticalAlignments, true) ? $element['verticalAlign'] : 'middle',
                    'lineHeight' => min(3, max(0.7, round((float) ($element['lineHeight'] ?? 1.05), 2))),
                    'maxLines' => min(10, max(0, (int) ($element['maxLines'] ?? 0))),
                    'rotation' => min(360, max(-360, round((float) ($element['rotation'] ?? 0), 1))),
                    'inverted' => (bool) ($element['inverted'] ?? false),
                ];
            }

            if ($element['type'] === 'text') {
                $normalized['content'] = mb_substr((string) ($element['content'] ?? $element['text'] ?? ''), 0, 300);
            }

            if ($element['type'] === 'barcode') {
                $normalized += [
                    'content' => mb_substr((string) ($element['content'] ?? '{{barcode}}'), 0, 300),
                    'showText' => (bool) ($element['showText'] ?? false),
                    'textSize' => min(36, max(4, round((float) ($element['textSize'] ?? 8), 1))),
                    'quietZone' => min(10, max(0, round((float) ($element['quietZone'] ?? 0.5), 2))),
                    'errorCorrection' => in_array($element['errorCorrection'] ?? null, ['L', 'M', 'Q', 'H'], true) ? $element['errorCorrection'] : 'M',
                    'rotation' => min(360, max(-360, round((float) ($element['rotation'] ?? 0), 1))),
                    'inverted' => (bool) ($element['inverted'] ?? false),
                ];
                $normalized['barcodeFormat'] = in_array($element['barcodeFormat'] ?? null, BarcodeSvgRenderer::FORMATS, true)
                    ? $element['barcodeFormat']
                    : 'code128';
            }

            if (in_array($element['type'], ['line', 'box'], true)) {
                $normalized['borderWidth'] = min(10, max(0.5, round((float) ($element['borderWidth'] ?? 1), 1)));
            }

            return $normalized;
        })->values()->all();
        $data['is_default'] = $request->boolean('is_default');

        return $data;
    }
}
