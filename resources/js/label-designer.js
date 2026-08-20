const TEXT_TYPES = ['name', 'sku', 'price', 'code', 'text'];
const REMOVABLE_TYPES = ['text', 'barcode', 'line', 'box'];
const TWO_DIMENSIONAL_FORMATS = ['qr', 'datamatrix'];
const PX_PER_MM = 96 / 25.4;
const PX_PER_PT = 96 / 72;
const SAUDI_RIYAL_SVG = '<svg class="designer-currency-symbol" style="height:1em;width:auto;display:inline-block;vertical-align:-0.12em" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 549 613" role="img" aria-label="Saudi Riyal" fill="currentColor"><path fill-rule="evenodd" d="M 0 560 L 6 530 L 18 497 L 193 460 L 193 377 L 32 412 L 30 412 L 30 408 L 36 376 L 49 341 L 193 311 L 193 54 L 221 25 L 257 0 L 258 297 L 323 283 L 322 87 L 354 54 L 387 32 L 388 269 L 549 235 L 543 271 L 530 306 L 388 336 L 388 401 L 547 367 L 549 367 L 549 372 L 542 407 L 530 438 L 323 482 L 323 350 L 258 363 L 258 444 L 254 454 L 225 497 L 207 518 L 193 526 L 179 530 L 14 565 L 0 567 Z M 323 605 L 329 576 L 341 543 L 548 499 L 549 504 L 542 539 L 530 570 L 329 613 L 323 613 Z" /></svg>';

const fontStacks = {
    inherit: 'inherit',
    tajawal: '"Tajawal", Tahoma, Arial, sans-serif',
    arial: 'Arial, sans-serif',
    helvetica: 'Helvetica, Arial, sans-serif',
    times: '"Times New Roman", serif',
    courier: '"Courier New", monospace',
    georgia: 'Georgia, serif',
    verdana: 'Verdana, sans-serif',
};

function normaliseElement(element) {
    const item = {
        ...element,
        visible: element.visible ?? true,
    };

    if (TEXT_TYPES.includes(item.type)) {
        Object.assign(item, {
            fontSize: item.fontSize ?? 10,
            fontFamily: item.fontFamily ?? 'inherit',
            bold: item.bold ?? ['name', 'price'].includes(item.type),
            italic: item.italic ?? false,
            underline: item.underline ?? false,
            textAlign: item.textAlign ?? 'center',
            verticalAlign: item.verticalAlign ?? 'middle',
            lineHeight: item.lineHeight ?? 1.05,
            maxLines: item.maxLines ?? 0,
            rotation: item.rotation ?? 0,
            inverted: item.inverted ?? false,
        });
    }

    if (item.type === 'text') {
        item.content = item.content ?? item.text ?? '';
        delete item.text;
    }

    if (item.type === 'barcode') {
        Object.assign(item, {
            content: item.content ?? '{{barcode}}',
            barcodeFormat: item.barcodeFormat ?? 'code128',
            showText: item.showText ?? false,
            textSize: item.textSize ?? 8,
            quietZone: item.quietZone ?? 0.5,
            errorCorrection: item.errorCorrection ?? 'M',
            rotation: item.rotation ?? 0,
            inverted: item.inverted ?? false,
        });
    }

    if (['line', 'box'].includes(item.type)) {
        item.borderWidth = item.borderWidth ?? 1;
    }

    return item;
}

export default function labelDesigner(config) {
    return {
        elements: config.elements.map(normaliseElement),
        elementLabels: config.elementLabels,
        variableLabels: config.variableLabels,
        barcodeFormats: config.barcodeFormats,
        fontLabels: config.fontLabels,
        width: config.width,
        height: config.height,
        name: config.name,
        isDefault: config.isDefault,
        products: config.products,
        barcodePreviewUrl: config.barcodePreviewUrl,
        previewProductId: config.products[0]?.id ?? null,
        zoom: 100,
        selectedIndex: null,
        drag: null,
        resizing: null,
        barcodeSvgs: {},
        barcodeErrors: {},
        barcodeRequestKeys: {},

        init() {
            this.$nextTick(() => this.refreshBarcodes());
        },

        get selected() {
            return this.selectedIndex === null ? null : this.elements[this.selectedIndex];
        },

        get previewProduct() {
            return this.products.find(product => Number(product.id) === Number(this.previewProductId)) ?? null;
        },

        get scale() {
            return PX_PER_MM * this.zoom / 100;
        },

        isTextElement(element) {
            return Boolean(element && TEXT_TYPES.includes(element.type));
        },

        isRemovable(element) {
            return Boolean(element && REMOVABLE_TYPES.includes(element.type));
        },

        isTwoDimensional(element) {
            return Boolean(element && TWO_DIMENSIONAL_FORMATS.includes(element.barcodeFormat));
        },

        zoomIn() {
            this.zoom = Math.min(400, this.zoom + 25);
        },

        zoomOut() {
            this.zoom = Math.max(25, this.zoom - 25);
        },

        resetZoom() {
            this.zoom = 100;
        },

        elementBoxStyle(element) {
            const styles = [
                `left:${element.x * this.scale}px`,
                `top:${element.y * this.scale}px`,
                `width:${element.width * this.scale}px`,
                `height:${element.height * this.scale}px`,
            ];

            if (this.isTextElement(element)) {
                const vertical = { top: 'flex-start', middle: 'center', bottom: 'flex-end' }[element.verticalAlign] ?? 'center';
                styles.push(
                    `font-size:${element.fontSize * PX_PER_PT * this.zoom / 100}px`,
                    `font-family:${fontStacks[element.fontFamily] ?? 'inherit'}`,
                    `font-weight:${element.bold ? 700 : 400}`,
                    `font-style:${element.italic ? 'italic' : 'normal'}`,
                    `text-decoration:${element.underline ? 'underline' : 'none'}`,
                    `text-align:${element.textAlign}`,
                    `line-height:${element.lineHeight}`,
                    `justify-content:${vertical}`,
                );
            }

            return styles.join(';');
        },

        contentStyle(element) {
            return `transform:rotate(${Number(element.rotation ?? 0)}deg)`;
        },

        borderWidthPx(element) {
            return Number(element.borderWidth ?? 1) * PX_PER_PT * this.zoom / 100;
        },

        quietZonePx(element) {
            return Number(element.quietZone ?? 0.5) * this.scale;
        },

        barcodeTextSizePx(element) {
            return Number(element.textSize ?? 8) * PX_PER_PT * this.zoom / 100;
        },

        lineClampStyle(element) {
            const lines = Number(element.maxLines ?? 0);
            return lines > 0
                ? `display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:${lines};overflow:hidden`
                : '';
        },

        resolveContent(content) {
            const variables = this.previewProduct?.variables ?? {};
            return String(content ?? '').replace(/\{\{\s*([a-z_]+)\s*\}\}/gi, (match, key) => variables[key.toLowerCase()] ?? match);
        },

        displayText(element) {
            const variables = this.previewProduct?.variables ?? {};
            switch (element.type) {
                case 'name': return variables.display_name ?? this.elementLabels.name;
                case 'sku': return variables.sku ?? 'SKU-1001';
                case 'price': return variables.price_with_currency ?? '25.00 SAR';
                case 'code': return variables.barcode ?? '6281000012345';
                case 'text': return this.resolveContent(element.content);
                default: return '';
            }
        },

        displayTextHtml(element) {
            const escaped = this.displayText(element)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            return escaped.replace(/\bSAR\b/g, SAUDI_RIYAL_SVG);
        },

        insertVariable(variable) {
            if (!variable || !this.selected || !['text', 'barcode'].includes(this.selected.type)) return;
            const token = `{{${variable}}}`;
            const content = String(this.selected.content ?? '');
            this.selected.content = content === '' || /\s$/.test(content) ? `${content}${token}` : `${content} ${token}`;
            if (this.selected.type === 'barcode') this.refreshBarcode(this.selected);
        },

        addElement(type) {
            if (this.elements.length >= 30) return;
            let sequence = 1;
            while (this.elements.some(element => element.id === `${type}-${sequence}`)) sequence += 1;
            const width = Math.max(1, Math.min(type === 'barcode' ? 35 : 40, this.width - 4));
            const element = normaliseElement({
                id: `${type}-${sequence}`,
                type,
                x: 2,
                y: 2,
                width,
                height: type === 'barcode' ? 12 : 5,
                visible: true,
            });
            if (type === 'text') element.content = this.elementLabels.customText;
            if (type === 'line') Object.assign(element, { height: 1, borderWidth: 1 });
            if (type === 'box') Object.assign(element, { width: Math.max(1, Math.min(30, this.width - 4)), height: Math.max(1, Math.min(15, this.height - 4)), borderWidth: 1 });
            this.elements.push(element);
            this.selectedIndex = this.elements.length - 1;
            if (type === 'barcode') this.$nextTick(() => this.refreshBarcode(element));
        },

        removeSelected() {
            if (!this.isRemovable(this.selected)) return;
            const id = this.selected.id;
            this.elements.splice(this.selectedIndex, 1);
            delete this.barcodeSvgs[id];
            delete this.barcodeErrors[id];
            this.selectedIndex = null;
        },

        previewProductChanged() {
            this.refreshBarcodes();
        },

        async refreshBarcodes() {
            await Promise.all(this.elements.filter(element => element.type === 'barcode').map(element => this.refreshBarcode(element)));
        },

        async refreshBarcode(element) {
            if (!element || element.type !== 'barcode' || !this.previewProductId) return;
            const params = new URLSearchParams({
                product_id: this.previewProductId,
                content: element.content ?? '{{barcode}}',
                format: element.barcodeFormat,
                errorCorrection: element.errorCorrection ?? 'M',
            });
            const requestKey = params.toString();
            this.barcodeRequestKeys[element.id] = requestKey;
            this.barcodeErrors = { ...this.barcodeErrors, [element.id]: false };

            try {
                const response = await fetch(`${this.barcodePreviewUrl}?${requestKey}`, {
                    headers: { Accept: 'image/svg+xml' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error('Invalid barcode');
                const svg = await response.text();
                if (this.barcodeRequestKeys[element.id] === requestKey) {
                    this.barcodeSvgs = { ...this.barcodeSvgs, [element.id]: svg };
                }
            } catch {
                if (this.barcodeRequestKeys[element.id] === requestKey) {
                    this.barcodeSvgs = { ...this.barcodeSvgs, [element.id]: '' };
                    this.barcodeErrors = { ...this.barcodeErrors, [element.id]: true };
                }
            }
        },

        startDrag(event, index) {
            this.selectedIndex = index;
            const item = this.elements[index];
            this.drag = { index, startX: event.clientX, startY: event.clientY, x: item.x, y: item.y };
            this.moveHandler = pointerEvent => this.move(pointerEvent);
            this.upHandler = () => this.stopDrag();
            window.addEventListener('pointermove', this.moveHandler);
            window.addEventListener('pointerup', this.upHandler, { once: true });
        },

        move(event) {
            if (!this.drag) return;
            const item = this.elements[this.drag.index];
            item.x = Math.max(0, Math.min(this.width - item.width, this.drag.x + (event.clientX - this.drag.startX) / this.scale));
            item.y = Math.max(0, Math.min(this.height - item.height, this.drag.y + (event.clientY - this.drag.startY) / this.scale));
            item.x = Math.round(item.x * 10) / 10;
            item.y = Math.round(item.y * 10) / 10;
        },

        stopDrag() {
            window.removeEventListener('pointermove', this.moveHandler);
            this.drag = null;
        },

        startResize(event, index) {
            this.selectedIndex = index;
            const item = this.elements[index];
            this.resizing = { index, startX: event.clientX, startY: event.clientY, width: item.width, height: item.height };
            this.resizeMoveHandler = pointerEvent => this.resizeElement(pointerEvent);
            this.resizeUpHandler = () => this.stopResize();
            window.addEventListener('pointermove', this.resizeMoveHandler);
            window.addEventListener('pointerup', this.resizeUpHandler, { once: true });
        },

        resizeElement(event) {
            if (!this.resizing) return;
            const item = this.elements[this.resizing.index];
            item.width = Math.max(1, Math.min(this.width - item.x, this.resizing.width + (event.clientX - this.resizing.startX) / this.scale));
            item.height = Math.max(1, Math.min(this.height - item.y, this.resizing.height + (event.clientY - this.resizing.startY) / this.scale));
            item.width = Math.round(item.width * 10) / 10;
            item.height = Math.round(item.height * 10) / 10;
        },

        stopResize() {
            window.removeEventListener('pointermove', this.resizeMoveHandler);
            this.resizing = null;
        },

        clampElements() {
            this.elements.forEach(item => {
                item.width = Math.max(1, Math.min(item.width, this.width));
                item.height = Math.max(1, Math.min(item.height, this.height));
                item.x = Math.max(0, Math.min(item.x, this.width - item.width));
                item.y = Math.max(0, Math.min(item.y, this.height - item.height));
            });
        },

        syncLayout() {
            this.clampElements();
            this.$refs.layoutInput.value = JSON.stringify(this.elements);
        },
    };
}
