<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketingPageController extends Controller
{
    private const PAGES = [
        'features' => [
            'meta_title' => 'Inventory Management Features | StockPilot',
            'meta_description' => 'Explore StockPilot features for products, barcodes, labels, batches, stock movements, warehouse counts, teams, and bilingual inventory management.',
            'eyebrow' => 'Complete inventory toolkit',
            'title' => 'Everything your team needs to control stock',
            'description' => 'Replace disconnected spreadsheets and manual barcode tools with one workspace for products, stock, labels, warehouses, and people.',
            'highlights' => [
                ['Product catalog', 'Manage SKUs, Arabic and English names, categories, units, images, prices, batches, and expiry dates.'],
                ['Barcode labels', 'Generate ten barcode formats—including Code 128, EAN, UPC, QR, and Data Matrix—with a visual drag-and-resize designer.'],
                ['Stock movements', 'Receive, issue, transfer, and adjust inventory with a permanent movement history.'],
                ['Physical counts', 'Start warehouse counts, enter actual quantities, review variances, and finalize adjustments.'],
                ['Multiple locations', 'Track stock across warehouses and see on-hand quantities by product and location.'],
                ['Users and teams', 'Give owners, managers, staff, and viewers the access they need—without sharing accounts.'],
            ],
            'sections' => [
                ['title' => 'Built around real inventory workflows', 'body' => 'StockPilot connects the product catalog to every stock event, so your totals, movement history, and labels stay aligned.', 'bullets' => ['Low-stock visibility and reorder levels', 'Batch-level expiry tracking', 'Search by product, SKU, or barcode']],
                ['title' => 'Bilingual from the start', 'body' => 'Use English and Arabic product names and switch the workspace language to suit your team.', 'bullets' => ['Arabic-friendly interface', 'Saudi Riyal support', 'Timezone-aware activity history']],
            ],
            'faq' => [
                ['question' => 'Can I print barcode labels?', 'answer' => 'Yes. You can design reusable labels, choose supported barcode types, add text, lines, and boxes, then print one or many copies.'],
                ['question' => 'Does StockPilot support product batches?', 'answer' => 'Yes. A product can have multiple batches, and every batch can have its own expiry date.'],
                ['question' => 'Can multiple employees use the same workspace?', 'answer' => 'Yes. Add users, organize teams, and control access with roles and permissions.'],
            ],
            'related' => ['barcode-inventory-system', 'barcode-label-printing', 'stock-counting-software'],
        ],
        'pricing' => [
            'meta_title' => 'Free Inventory Software Pricing | StockPilot Starter',
            'meta_description' => 'StockPilot Starter is free forever. Start managing products, barcodes, stock movements, labels, and inventory counts without a credit card or trial expiry.',
            'eyebrow' => 'Simple, honest pricing',
            'title' => 'Starter is free forever',
            'description' => 'Open your inventory workspace and keep using the Starter package. There is no trial countdown and no credit card is required.',
            'highlights' => [
                ['SAR 0 forever', 'The Starter package has no monthly subscription fee and does not expire.'],
                ['No credit card', 'Create a workspace without entering payment information.'],
                ['Core inventory included', 'Products, barcodes, stock movements, physical counts, and labels are available from day one.'],
                ['Bilingual workspace', 'Work with English and Arabic product information in the same system.'],
                ['Generous Starter capacity', 'Manage up to 1,000 products, 10 users, and 3 warehouses on the free Starter package.'],
                ['Start in minutes', 'Register, complete the guided setup, add your first product, and print a barcode label.'],
            ],
            'sections' => [
                ['title' => 'What “free forever” means', 'body' => 'Starter is not a time-limited trial. Your active Starter workspace remains available without a subscription charge.', 'bullets' => ['No trial expiry', 'No automatic billing', 'No card required']],
                ['title' => 'A practical starting point', 'body' => 'Use the same connected product, barcode, and stock workflows that make inventory easier to audit and understand.', 'bullets' => ['Up to 1,000 products', 'Up to 10 users', 'Up to 3 warehouses']],
            ],
            'faq' => [
                ['question' => 'Is StockPilot Starter really free forever?', 'answer' => 'Yes. The Starter package is free with no trial expiry and no monthly subscription charge.'],
                ['question' => 'Do I need a credit card to register?', 'answer' => 'No. You can create a Starter workspace without entering card details.'],
                ['question' => 'Will my Starter workspace expire?', 'answer' => 'No. An active Starter workspace does not expire because of a trial deadline.'],
            ],
            'related' => ['features', 'inventory-management-small-business', 'barcode-label-printing'],
        ],
        'barcode-inventory-system' => [
            'meta_title' => 'Barcode Inventory System for Small Teams | StockPilot',
            'meta_description' => 'Create barcodes, find products quickly, record stock movements, and maintain an auditable inventory history with StockPilot’s free Starter package.',
            'eyebrow' => 'Barcode inventory system',
            'title' => 'Turn every scan into clearer inventory',
            'description' => 'Connect each product barcode to stock receiving, issuing, transfers, counts, and printable labels in one organized workspace.',
            'highlights' => [
                ['Fast product lookup', 'Search products by name, SKU, or barcode instead of browsing long spreadsheets.'],
                ['Multiple barcode formats', 'Choose from ten formats including Code 128, EAN, UPC, ITF-14, QR, and Data Matrix.'],
                ['Movement history', 'Keep a dated record of inventory changes, locations, references, and responsible users.'],
                ['Accurate stock snapshots', 'See current on-hand quantities while retaining the ledger behind each total.'],
                ['Batch visibility', 'Store multiple batch numbers and their individual expiry dates for the same product.'],
                ['Printable labels', 'Create reusable label templates and generate the exact number of copies you need.'],
            ],
            'sections' => [
                ['title' => 'From product setup to first scan', 'body' => 'Create a product, assign or generate its barcode, print the label, and use the code as the product’s consistent identity.', 'bullets' => ['Create or generate a barcode', 'Choose a label design', 'Print individual or batch labels']],
                ['title' => 'A complete record behind the barcode', 'body' => 'A barcode should do more than identify a product. StockPilot connects it to pricing, stock locations, batches, and movement history.', 'bullets' => ['Product and pricing details', 'Warehouse quantities', 'Expiry and batch records']],
            ],
            'faq' => [
                ['question' => 'Can StockPilot generate product barcodes?', 'answer' => 'Yes. You can enter an existing code or generate a new primary barcode when creating a product.'],
                ['question' => 'Which barcode types can I print?', 'answer' => 'The label designer supports Code 128, Code 39, Code 93, EAN-13, EAN-8, UPC-A, ITF-14, Codabar, QR, and Data Matrix.'],
                ['question' => 'Can I search inventory by barcode?', 'answer' => 'Yes. The product catalog search includes product names, SKUs, and barcode values.'],
            ],
            'related' => ['barcode-label-printing', 'features', 'warehouse-inventory-management'],
        ],
        'barcode-label-printing' => [
            'meta_title' => 'Barcode Label Printing and Designer | StockPilot',
            'meta_description' => 'Design and print barcode or QR labels with custom text, boxes, lines, inverted colors, zoom, mouse resizing, and multi-copy print previews.',
            'eyebrow' => 'Barcode label printing',
            'title' => 'Design labels that fit your products and printer',
            'description' => 'Build reusable thermal label templates visually, preview real product data, and print the right number of copies.',
            'highlights' => [
                ['Visual designer', 'Drag elements to position them and resize them with the mouse.'],
                ['Text and shapes', 'Add custom text fields, separator lines, and bordered boxes.'],
                ['Inverted colors', 'Create black highlights with white text for important label information.'],
                ['Linear and 2D barcodes', 'Choose from ten formats, including Code 128, retail EAN/UPC codes, QR, and Data Matrix.'],
                ['Zoom controls', 'Zoom the design canvas while keeping real millimeter dimensions.'],
                ['Multi-copy printing', 'Select products, set copies, and review the complete print output before printing.'],
            ],
            'sections' => [
                ['title' => 'A label designer made for thermal printing', 'body' => 'Work in millimeters, set label width and height, and arrange product fields precisely for compact labels.', 'bullets' => ['Real-size dimensions', 'Mouse drag and resize', 'Reusable design templates']],
                ['title' => 'Print one product or a complete batch', 'body' => 'Use the single-product label view for a quick print or the print page to combine products and copy quantities.', 'bullets' => ['Live print preview', 'Per-product copy counts', 'Browser print support']],
            ],
            'faq' => [
                ['question' => 'Can I create QR code labels?', 'answer' => 'Yes. Select QR as the barcode format in the label designer.'],
                ['question' => 'Can I resize label elements with the mouse?', 'answer' => 'Yes. Select an element and use its resize handle, or enter exact dimensions.'],
                ['question' => 'Can I duplicate a label design?', 'answer' => 'Yes. Label designs can be duplicated, edited, and deleted from the print page.'],
            ],
            'related' => ['barcode-inventory-system', 'features', 'pricing'],
        ],
        'inventory-management-small-business' => [
            'meta_title' => 'Free Inventory Management for Small Business | StockPilot',
            'meta_description' => 'A practical free inventory system for small businesses: manage products, barcodes, locations, stock movements, batches, expiry dates, and counts.',
            'eyebrow' => 'For small businesses',
            'title' => 'Inventory control without enterprise complexity',
            'description' => 'Give your team a clear, repeatable way to receive stock, issue items, print labels, and know what is available.',
            'highlights' => [
                ['Quick setup', 'Create your company, warehouse, categories, units, and first product through a guided flow.'],
                ['Clear product records', 'Keep names, SKUs, barcodes, prices, images, batches, and expiry dates together.'],
                ['Simple daily movements', 'Receive, issue, transfer, or adjust stock with references and notes.'],
                ['Low-stock awareness', 'Set minimum quantities and identify products that need attention.'],
                ['Team access', 'Give each employee an account and use permissions appropriate to their work.'],
                ['Free Starter', 'Build your core inventory process on a package that does not expire.'],
            ],
            'sections' => [
                ['title' => 'Move beyond spreadsheet uncertainty', 'body' => 'Spreadsheets are easy to start but difficult to audit when several people edit quantities. A movement ledger keeps the reason behind each change.', 'bullets' => ['One product catalog', 'User-attributed changes', 'Current stock by location']],
                ['title' => 'Useful for many product-based businesses', 'body' => 'StockPilot fits retailers, workshops, offices, distributors, spare-parts stores, supply rooms, and growing online sellers.', 'bullets' => ['Retail and wholesale', 'Office and warehouse supplies', 'Parts and consumables']],
            ],
            'faq' => [
                ['question' => 'Is StockPilot suitable for a new small business?', 'answer' => 'Yes. Starter is free forever and the guided setup helps you create your first warehouse and product catalog.'],
                ['question' => 'Can I use Arabic product names?', 'answer' => 'Yes. Products can store both English and Arabic names.'],
                ['question' => 'Can my employees have different access?', 'answer' => 'Yes. Roles and permissions control who can view products, move inventory, count stock, print labels, or manage users.'],
            ],
            'related' => ['pricing', 'features', 'inventory-management-saudi-arabia'],
        ],
        'warehouse-inventory-management' => [
            'meta_title' => 'Warehouse Inventory Management Software | StockPilot',
            'meta_description' => 'Track stock by warehouse, receive and transfer inventory, review movement history, run physical counts, and print barcode labels with StockPilot.',
            'eyebrow' => 'Warehouse stock control',
            'title' => 'Know what is in each location—and how it got there',
            'description' => 'Maintain current warehouse quantities while preserving a traceable history of receipts, issues, transfers, adjustments, and counts.',
            'highlights' => [
                ['Location-level stock', 'See the quantity of each product in every active warehouse or storage location.'],
                ['Transfers', 'Move stock between locations with balanced source and destination records.'],
                ['Receiving and issuing', 'Record incoming and outgoing quantities with references, notes, date, and user.'],
                ['Controlled adjustments', 'Correct discrepancies without hiding the adjustment from history.'],
                ['Physical inventory', 'Count products by location and turn approved variances into ledger movements.'],
                ['Low-stock monitoring', 'Compare on-hand quantity with the minimum stock set for each product.'],
            ],
            'sections' => [
                ['title' => 'A ledger behind every stock total', 'body' => 'Current quantity is useful; the movement history explains it. StockPilot keeps both the snapshot and the transactions that produced it.', 'bullets' => ['Dated movement records', 'Source and destination locations', 'References and operator names']],
                ['title' => 'Count with a controlled workflow', 'body' => 'Create a stock count, capture expected quantities, enter counted amounts, review variance, and finalize once approved.', 'bullets' => ['Location-specific counts', 'Expected versus counted', 'Automatic adjustment on finalization']],
            ],
            'faq' => [
                ['question' => 'Can I manage more than one warehouse?', 'answer' => 'Yes. Products can have stock in multiple locations, and transfers move quantities between them.'],
                ['question' => 'Does StockPilot keep movement history?', 'answer' => 'Yes. Each movement retains its type, date, reference, notes, user, product, location, and quantity change.'],
                ['question' => 'Can stock become negative?', 'answer' => 'Stock validation prevents issuing or transferring more than the available quantity.'],
            ],
            'related' => ['stock-counting-software', 'barcode-inventory-system', 'features'],
        ],
        'stock-counting-software' => [
            'meta_title' => 'Stock Counting and Inventory Count Software | StockPilot',
            'meta_description' => 'Run controlled physical inventory counts by warehouse, compare expected and counted quantities, review variances, and finalize stock adjustments.',
            'eyebrow' => 'Physical inventory counts',
            'title' => 'Make stock counts easier to review and trust',
            'description' => 'Replace handwritten count sheets with a structured workflow that captures expected stock, actual stock, and the resulting variance.',
            'highlights' => [
                ['Count by location', 'Start a focused count for the warehouse or storage location you are checking.'],
                ['Expected snapshot', 'Capture expected quantities when the count starts for a consistent comparison.'],
                ['Counted quantities', 'Enter actual quantities product by product and immediately see the difference.'],
                ['Variance review', 'Identify shortages and overages before making any inventory adjustment.'],
                ['Controlled finalization', 'Finalize the count to generate the necessary stock adjustments.'],
                ['Permanent history', 'Retain count references, dates, users, statuses, notes, and line-level results.'],
            ],
            'sections' => [
                ['title' => 'A repeatable count process', 'body' => 'Use the same steps for weekly cycle counts, monthly checks, or a full year-end physical inventory.', 'bullets' => ['Start and snapshot', 'Enter and review', 'Finalize and adjust']],
                ['title' => 'Variance you can investigate', 'body' => 'Because movements and counts share the same product and location records, your team can trace discrepancies more effectively.', 'bullets' => ['Expected quantity', 'Counted quantity', 'Calculated variance']],
            ],
            'faq' => [
                ['question' => 'What happens when I finalize a stock count?', 'answer' => 'StockPilot records adjustment movements for the approved differences so on-hand quantities match the count.'],
                ['question' => 'Can I count one warehouse at a time?', 'answer' => 'Yes. Each stock count is associated with a specific inventory location.'],
                ['question' => 'Can I review variances before adjustment?', 'answer' => 'Yes. Counts remain in progress while quantities are entered and reviewed, then an authorized user finalizes them.'],
            ],
            'related' => ['warehouse-inventory-management', 'inventory-management-small-business', 'features'],
        ],
        'inventory-management-saudi-arabia' => [
            'meta_title' => 'Inventory Management Software Saudi Arabia | StockPilot',
            'meta_description' => 'Bilingual inventory and barcode software for businesses in Saudi Arabia, with Arabic product names, SAR display, warehouse tracking, labels, and free Starter access.',
            'eyebrow' => 'Built for Saudi businesses',
            'title' => 'Bilingual inventory management for Saudi teams',
            'description' => 'Manage products, warehouses, barcodes, and stock movements in a workspace designed for English and Arabic operations.',
            'highlights' => [
                ['Arabic and English', 'Store bilingual product names and switch the workspace language for different team members.'],
                ['Saudi Riyal', 'Display costs, selling prices, and inventory values with the Saudi Riyal symbol.'],
                ['Riyadh timezone ready', 'Use Asia/Riyadh as the workspace timezone for relevant activity dates.'],
                ['Warehouse workflows', 'Receive, issue, transfer, adjust, and count stock across Saudi branches or storage locations.'],
                ['Barcode printing', 'Design thermal barcode and QR labels using sizes measured in millimeters.'],
                ['Free forever Starter', 'Start without a credit card, subscription charge, or trial expiry.'],
            ],
            'sections' => [
                ['title' => 'إدارة المخزون بالعربي والإنجليزي', 'body' => 'Keep bilingual product information in one catalog so office, warehouse, sales, and management teams can work from the same records.', 'bullets' => ['أسماء أصناف بالعربية والإنجليزية', 'الأسعار والتكلفة بالريال السعودي', 'باركود وملصقات حرارية']],
                ['title' => 'Practical for local operations', 'body' => 'Use StockPilot for a single shop, a supply room, multiple warehouses, or a growing distribution operation.', 'bullets' => ['Retail and wholesale stock', 'Spare parts and consumables', 'Office and warehouse supplies']],
            ],
            'faq' => [
                ['question' => 'هل يدعم StockPilot اللغة العربية؟', 'answer' => 'نعم. يدعم النظام واجهة عربية وأسماء أصناف بالعربية والإنجليزية.'],
                ['question' => 'Does StockPilot support the Saudi Riyal?', 'answer' => 'Yes. Product costs, prices, and inventory values can use SAR with the Saudi Riyal symbol.'],
                ['question' => 'Is the Starter package free in Saudi Arabia?', 'answer' => 'Yes. Starter is free forever, with no credit card and no trial expiry.'],
            ],
            'related' => ['inventory-management-small-business', 'pricing', 'barcode-label-printing'],
        ],
    ];

    public function __invoke(Request $request): View
    {
        $key = Str::afterLast((string) $request->route()->getName(), '.');
        abort_unless(isset(self::PAGES[$key]), 404);

        $page = $this->page($key);
        $page['key'] = $key;
        $page['related_pages'] = collect($page['related'])->map(function (string $relatedKey): array {
            $relatedPage = $this->page($relatedKey);

            return [
                'title' => $relatedPage['title'],
                'description' => $relatedPage['meta_description'],
                'route' => route('marketing.'.$relatedKey),
            ];
        })->all();

        return view('marketing.page', compact('page'));
    }

    /**
     * @return array<string, mixed>
     */
    private function page(string $key): array
    {
        if (app()->isLocale('ar')) {
            return trans('marketing.pages.'.$key);
        }

        return self::PAGES[$key];
    }
}
