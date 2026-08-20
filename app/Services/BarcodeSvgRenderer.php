<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use Com\Tecnick\Barcode\Barcode as TecnickBarcode;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Throwable;

class BarcodeSvgRenderer
{
    public const FORMATS = [
        'code128',
        'code39',
        'code93',
        'ean13',
        'ean8',
        'upca',
        'itf14',
        'codabar',
        'qr',
        'datamatrix',
    ];

    public function __construct(private readonly LabelContentResolver $contentResolver) {}

    /**
     * @return array<string, string|null>
     */
    public function renderForProduct(Product $product, Company $company, array $layout): array
    {
        return collect($layout)
            ->where('type', 'barcode')
            ->filter(fn (array $element): bool => (bool) ($element['visible'] ?? true))
            ->mapWithKeys(function (array $element) use ($company, $product): array {
                $id = (string) ($element['id'] ?? 'barcode');
                $content = $this->contentResolver->resolve(
                    $element['content'] ?? null,
                    $product,
                    $company,
                    '{{barcode}}',
                );

                return [$id => $this->renderValue($content, (string) ($element['barcodeFormat'] ?? 'code128'), $element)];
            })
            ->all();
    }

    public function renderValue(string $value, string $format, array $options = []): ?string
    {
        if ($value === '' || ! in_array($format, self::FORMATS, true)) {
            return null;
        }

        $oneDimensionalTypes = [
            'code128' => BarcodeGeneratorSVG::TYPE_CODE_128,
            'code39' => BarcodeGeneratorSVG::TYPE_CODE_39,
            'code93' => BarcodeGeneratorSVG::TYPE_CODE_93,
            'ean13' => BarcodeGeneratorSVG::TYPE_EAN_13,
            'ean8' => BarcodeGeneratorSVG::TYPE_EAN_8,
            'upca' => BarcodeGeneratorSVG::TYPE_UPC_A,
            'itf14' => BarcodeGeneratorSVG::TYPE_ITF_14,
            'codabar' => BarcodeGeneratorSVG::TYPE_CODABAR,
        ];

        try {
            if ($format === 'qr') {
                $errorCorrection = match ($options['errorCorrection'] ?? 'M') {
                    'L' => ErrorCorrectionLevel::Low,
                    'Q' => ErrorCorrectionLevel::Quartile,
                    'H' => ErrorCorrectionLevel::High,
                    default => ErrorCorrectionLevel::Medium,
                };
                $qrCode = new QrCode(
                    data: $value,
                    errorCorrectionLevel: $errorCorrection,
                    size: 300,
                    margin: 0,
                );

                return (new SvgWriter)->write($qrCode, options: [
                    SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true,
                ])->getString();
            }

            if ($format === 'datamatrix') {
                return (new TecnickBarcode)
                    ->getBarcodeObj('DATAMATRIX', $value, -4, -4, 'black', [0, 0, 0, 0])
                    ->getInlineSvgCode();
            }

            return (new BarcodeGeneratorSVG)->getBarcode($value, $oneDimensionalTypes[$format], 2, 54);
        } catch (Throwable) {
            return null;
        }
    }
}
