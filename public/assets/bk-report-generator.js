/**
 * BK Report Generator — client-side PDF using jsPDF + autoTable
 * Adapted from recway-old report-sv.php
 */
(function (window) {
    'use strict';

    window.BkReportGenerator = {

        /**
         * @param {object} templateData  – Livewire templates[lang] (sections array)
         * @param {object} candidate     – { id, orderId, name, email, phone, customerName, company, serviceName, ssn, staffName }
         * @param {string} action        – 'preview' | 'download' | 'upload'
         * @param {string} uploadUrl     – CSRF-protected POST URL (upload action only)
         * @param {string} csrfToken     – Laravel CSRF token
         */
        generate: function (templateData, candidate, action, uploadUrl, csrfToken) {
            window.jsPDF = window.jspdf.jsPDF;
            const doc = new jsPDF();

            const LEFT   = 15;
            const RIGHT  = 15;
            const FOOTER = 7;
            const PRIMARY_RGB   = [172, 2, 6];
            const SECONDARY_RGB = [127, 126, 126];
            const STATUS_COLORS = {
                'Godkänd':  [60, 179, 113],
                'Approved': [60, 179, 113],
                'Avvikelse':[255, 165, 0],
                'Nekad':    [255, 0, 0],
                'Denied':   [255, 0, 0],
                '-':        [255, 255, 255],
            };

            let y = 35;
            const toc_headings = [];
            const toc_numbers  = {};
            const summary_items = {};

            // ── Header / Footer ─────────────────────────────────────────────

            function addHeader() {
                doc.setDrawColor(...PRIMARY_RGB);
                doc.setFillColor(...PRIMARY_RGB);
                doc.triangle(0, 0, 0, 20, 200, 0, 'F');
                doc.setDrawColor(...SECONDARY_RGB);
                doc.setFillColor(...SECONDARY_RGB);
                doc.triangle(doc.internal.pageSize.width, 0, doc.internal.pageSize.width, 30, doc.internal.pageSize.width - 60, 0, 'F');
                doc.setFontSize(8);
                doc.setFont('Helvetica', 'Bold');
                doc.setTextColor('#ffffff');
                doc.text('Recway AB', 5, 5);
                doc.setTextColor('#D3D3D3');
                doc.text('info@recway.se', 5, 8);
                doc.setTextColor('#ffffff');
                const now = new Date();
                doc.text(now.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' }),
                    doc.internal.pageSize.width - 20, 5);
            }

            function addFooter() {
                doc.setDrawColor(...PRIMARY_RGB);
                doc.setFillColor(...PRIMARY_RGB);
                doc.rect(0, doc.internal.pageSize.height - 15, doc.internal.pageSize.width, 15, 'F');
            }

            // ── Font helpers ─────────────────────────────────────────────────

            function setFont(style) {
                switch (style) {
                    case 'title':       doc.setFontSize(18); doc.setFont('Helvetica', 'Bold');   break;
                    case 'mainHeading': doc.setFontSize(16); doc.setFont('Helvetica', 'Bold');   break;
                    case 'subHeading':  doc.setFontSize(12); doc.setFont('Helvetica', 'Bold');   break;
                    default:            doc.setFontSize(12); doc.setFont('Helvetica', 'normal'); break;
                }
            }

            function getTextWidth(text, size) {
                return (doc.getStringUnitWidth(text) * size) / (72 / 25.6);
            }

            function drawSectionHeading(text, addToToc) {
                const maxW = doc.internal.pageSize.width - LEFT - RIGHT;
                setFont('mainHeading');
                const lines = doc.splitTextToSize(text.toUpperCase(), maxW).filter(l => l.trim());
                const spacing = 7;

                if (y + lines.length * spacing + 20 > doc.internal.pageSize.height - FOOTER) {
                    doc.addPage(); addHeader(); addFooter(); y = 35;
                }

                doc.setTextColor('#000000');
                lines.forEach((l, i) => doc.text(l, LEFT, y + i * spacing));
                const lineY = y + (lines.length - 1) * spacing + 2;
                doc.setLineWidth(0.6);
                doc.setDrawColor(...PRIMARY_RGB);
                doc.line(LEFT, lineY, LEFT + 10, lineY);
                y = lineY + 10;

                if (addToToc) {
                    toc_headings.push(text);
                    toc_numbers[text] = doc.getCurrentPageInfo().pageNumber + 3;
                }
                return lineY;
            }

            // ── Table generators ─────────────────────────────────────────────

            function generate3ColTable(section) {
                drawSectionHeading(section.caption, true);
                summary_items[section.caption] = 'None';

                const data = (section.rows || [])
                    .filter(r => (r.c1 || '') !== '' || (r.c2 || '') !== '')
                    .map(r => ({ key: r.c1 || '', value: r.c2 || '', result: r.c3 || '-' }));

                doc.autoTable({
                    startY: y,
                    margin: { top: 30, bottom: 20 },
                    head: [{ key: 'Key', value: 'Value', result: 'Result' }],
                    body: data,
                    showHead: false,
                    theme: 'plain',
                    tableLineWidth: 0.1,
                    columnStyles: {
                        key:    { textColor: 0, fontStyle: 'bold', cellWidth: 81.5 },
                        value:  { cellWidth: 80 },
                        result: { textColor: '#ffffff', cellWidth: 20 },
                    },
                    didParseCell: function (d) {
                        if (d.column.index === d.table.columns.length - 1) {
                            const raw = d.cell.raw;
                            if (STATUS_COLORS[raw]) {
                                d.cell.styles.fillColor = STATUS_COLORS[raw];
                                if (raw !== '-') d.cell.text = '';
                            }
                            if (raw === '-' || !STATUS_COLORS[raw]) {
                                d.cell.styles.textColor = [0, 0, 0];
                                if (d.row.index % 2 === 0) d.cell.styles.fillColor = [240, 240, 240];
                            }
                        } else if (d.row.index % 2 === 0) {
                            d.cell.styles.fillColor = [240, 240, 240];
                        }
                    },
                    didDrawPage: function () { addHeader(); addFooter(); },
                });
                y = doc.lastAutoTable.finalY + 10;
            }

            function generateMultiColTable(section) {
                drawSectionHeading(section.caption, true);
                summary_items[section.caption] = 'None';

                const colCount = Math.min(section.columns || 3, 5);
                const headers = section.headers || [];
                const data = (section.rows || []).map(r => {
                    const row = {};
                    for (let i = 1; i <= colCount; i++) row['c' + i] = r['c' + i] || '';
                    return row;
                });

                const headObj = {};
                for (let i = 1; i <= colCount; i++) headObj['c' + i] = headers[i - 1] || 'Col ' + i;

                doc.autoTable({
                    startY: y,
                    margin: { top: 30, bottom: 20 },
                    head: [headObj],
                    body: data,
                    showHead: true,
                    theme: 'plain',
                    tableLineWidth: 0.1,
                    didParseCell: function (d) {
                        if (d.row.index === -1) {
                            d.cell.styles.fillColor = PRIMARY_RGB;
                            d.cell.styles.textColor = 255;
                            d.cell.styles.fontStyle = 'bold';
                        } else if (d.column.index === d.table.columns.length - 1 && d.row.index >= 0) {
                            const raw = d.cell.raw;
                            if (STATUS_COLORS[raw]) {
                                d.cell.styles.fillColor = STATUS_COLORS[raw];
                                d.cell.styles.textColor = 255;
                                if (raw !== '-') d.cell.text = '';
                            }
                        } else if (d.row.index % 2 === 0) {
                            d.cell.styles.fillColor = [240, 240, 240];
                        }
                    },
                    didDrawPage: function () { addHeader(); addFooter(); },
                });
                y = doc.lastAutoTable.finalY + 10;
            }

            // ── Text section ─────────────────────────────────────────────────

            function generateTextSection(section) {
                const heading = (section.heading || '').trim();
                const content = (section.content || '').trim();
                const align   = section.align || 'left';

                if (!heading && !content) return;

                setFont('normalText');
                const lines      = doc.splitTextToSize(content, doc.internal.pageSize.width - LEFT * 2);
                const lineH      = doc.internal.getFontSize() / doc.internal.scaleFactor + 2;
                const totalH     = lines.length * lineH + 20;

                if (totalH > doc.internal.pageSize.height - FOOTER - y) {
                    doc.addPage(); addHeader(); addFooter(); y = 35;
                }

                if (heading) {
                    drawSectionHeading(heading, true);
                    summary_items[heading] = 'None';
                }

                if (content) {
                    setFont('normalText');
                    doc.setTextColor('#000000');
                    doc.text(content, LEFT, y, {
                        maxWidth: doc.internal.pageSize.width - LEFT * 2,
                        align: align === 'justify' ? 'justify' : 'left',
                    });
                    y += totalH - 5;
                }
            }

            // ── Profile table ─────────────────────────────────────────────────

            function addProfile() {
                y = 35;
                const heading = 'PERSONLIG INFORMATION';
                const maxW    = doc.internal.pageSize.width - LEFT - RIGHT;
                setFont('mainHeading');
                const lines   = doc.splitTextToSize(heading, maxW).filter(l => l.trim());
                doc.setTextColor('#000000');
                lines.forEach((l, i) => doc.text(l, LEFT, y + i * 7));
                const lineY = y + (lines.length - 1) * 7 + 2;
                doc.setLineWidth(0.6);
                doc.setDrawColor(...PRIMARY_RGB);
                doc.line(LEFT, lineY, LEFT + 10, lineY);
                toc_headings.push('Personlig information');
                toc_numbers['Personlig information'] = doc.getCurrentPageInfo().pageNumber + 3;
                summary_items['Personlig information'] = 'None';

                y = lineY + 10;
                const data = [
                    { key: 'Namn',       value: candidate.name       || '' },
                    { key: 'E-post',     value: candidate.email      || '' },
                    { key: 'Telefon',    value: candidate.phone      || '' },
                    { key: 'Beställare', value: candidate.customerName || '' },
                    { key: 'Företag',    value: candidate.company    || '' },
                    { key: 'Tjänst',     value: candidate.serviceName || '' },
                    { key: 'SSN',        value: candidate.ssn        || '' },
                    { key: 'Handläggare',value: candidate.staffName  || '' },
                ];

                doc.autoTable({
                    startY: y,
                    head: [{ key: 'Key', value: 'Value' }],
                    body: data,
                    showHead: false,
                    theme: 'grid',
                    columnStyles: { key: { textColor: 0, fontStyle: 'bold' } },
                    didParseCell: function (d) {
                        if (d.row.index % 2 === 0) d.cell.styles.fillColor = [240, 240, 240];
                    },
                    didDrawPage: function () { addHeader(); addFooter(); },
                });
                y = doc.lastAutoTable.finalY + 10;
            }

            // ── Information header (logo + order ref) ─────────────────────────

            function addInformation() {
                setFont('subHeading');
                doc.setTextColor(...PRIMARY_RGB);
                doc.setFontSize(10);
                doc.setFont('Helvetica', 'normal');
                doc.setTextColor(SECONDARY_RGB[0], SECONDARY_RGB[1], SECONDARY_RGB[2]);
                const orderText = 'Order# ' + (candidate.orderId || '');
                doc.text(orderText, doc.internal.pageSize.width - getTextWidth(orderText, doc.getFontSize()) - RIGHT, y + 3);
                y += 30;
                setFont('title');
                doc.setTextColor('#000000');
                doc.text(candidate.serviceName || 'Bakgrundskontroll', LEFT, y);
                y += 5;
                doc.setLineWidth(1);
                doc.setDrawColor(...PRIMARY_RGB);
                doc.line(LEFT, y, LEFT + 45, y);
                y += 10;
            }

            // ── TOC ───────────────────────────────────────────────────────────

            function addTOC() {
                let ty = 30;
                doc.setTextColor('#000000');
                setFont('mainHeading');
                doc.text('INNEHÅLL', (doc.internal.pageSize.width / 2) - 20, 30);
                for (let i = 0; i < toc_headings.length; i++) {
                    setFont('subHeading');
                    doc.setFontSize(10);
                    ty = i === 0 ? ty + 5 : ty;
                    const h = toc_headings[i].toUpperCase();
                    const pg = toc_numbers[toc_headings[i]];
                    doc.textWithLink(h, LEFT + 10, ty + 5, { pageNumber: pg });
                    doc.setLineWidth(0.2);
                    doc.setLineDash([1, 1]);
                    doc.setDrawColor(0, 0, 0);
                    doc.line(
                        getTextWidth(h, doc.getFontSize()) + LEFT + 10,
                        ty + 5,
                        doc.internal.pageSize.width - RIGHT - 10,
                        ty + 5
                    );
                    doc.setLineDash([]);
                    doc.textWithLink(String(pg), doc.internal.pageSize.width - RIGHT - 10, ty + 5, { pageNumber: pg });
                    ty += 5;
                }
            }

            // ── Summary page ──────────────────────────────────────────────────

            function addSummaryPage() {
                doc.insertPage(1);
                addHeader(); addFooter();

                // Background image
                try {
                    const imgW = doc.internal.pageSize.getWidth();
                    const imgH = doc.internal.pageSize.getHeight();
                    doc.addImage(window.BkReportGenerator._bg3, 'WEBP', 0, 0, imgW, imgH);
                } catch (e) {
                    doc.setFillColor(30, 30, 50);
                    doc.rect(0, 0, doc.internal.pageSize.width, doc.internal.pageSize.height, 'F');
                }

                let sy = 30;
                doc.setTextColor('#ffffff');
                setFont('mainHeading');
                doc.text('SAMMANFATTNING', LEFT, sy);
                setFont('subHeading');
                doc.text('Översikt per delområde', LEFT, sy + 7);
                toc_headings.unshift('Sammanfattning');
                toc_numbers['Sammanfattning'] = doc.getCurrentPageInfo().pageNumber + 2;
                sy += 17;

                toc_headings.forEach(function (h) {
                    if (h === 'Sammanfattning') return;
                    const val = summary_items[h];
                    if (!val || val === 'None') return;
                    const color = STATUS_COLORS[val] || [100, 100, 100];
                    doc.setFontSize(10);
                    doc.setLineWidth(5);
                    doc.setDrawColor(...color);
                    doc.line(LEFT, sy, LEFT + 50, sy);
                    doc.setTextColor('#ffffff');
                    doc.text(h.toUpperCase(), LEFT + 55, sy + 1);
                    sy += 10;
                });

                // Definitions legend
                sy += 10;
                doc.setTextColor('#ffffff');
                setFont('mainHeading');
                doc.text('DEFINITIONER', LEFT, sy);
                sy += 7;
                setFont('subHeading');
                doc.text('DEFINITION', LEFT, sy);
                doc.text('FÄRGKOD', doc.internal.pageSize.width - RIGHT - 22, sy);
                sy += 5;
                doc.setLineWidth(0.6);
                doc.setDrawColor(255, 255, 255);
                doc.line(LEFT, sy, doc.internal.pageSize.width - RIGHT, sy);
                sy += 10;
                doc.setTextColor('#ffffff');
                [
                    ['INGA AVVIKELSER',            [61, 179, 112]],
                    ['NOTERBARA AVVIKELSER',        [255, 166, 0]],
                    ['ANMÄRKNINGSVÄRDA AVVIKELSER', [172, 2, 6]],
                ].forEach(function ([label, color]) {
                    doc.text(label, LEFT, sy + 2);
                    doc.setLineWidth(10);
                    doc.setDrawColor(...color);
                    doc.line(doc.internal.pageSize.width - RIGHT - 10, sy, doc.internal.pageSize.width - RIGHT, sy);
                    sy += 15;
                });
            }

            // ── Cover page ────────────────────────────────────────────────────

            function addCoverPage() {
                doc.insertPage(1);
                const imgW = doc.internal.pageSize.getWidth();
                const imgH = doc.internal.pageSize.getHeight();
                try {
                    doc.addImage(window.BkReportGenerator._bg2, 'WEBP', 0, 0, imgW, imgH);
                } catch (e) {
                    doc.setFillColor(20, 20, 40);
                    doc.rect(0, 0, imgW, imgH, 'F');
                }
                doc.setTextColor('#ffffff');
                doc.setFontSize(20);
                doc.text('BAKGRUNDSKONTROLL', LEFT, 80);
                doc.setFontSize(32);
                const serviceLines = doc.splitTextToSize(candidate.serviceName || 'BAS', imgW - LEFT * 2);
                doc.text(serviceLines, LEFT, 95);
                doc.setDrawColor(255, 255, 255);
                doc.setLineWidth(1);
                doc.line(LEFT, 100 + serviceLines.length * 5, LEFT + 20, 100 + serviceLines.length * 5);
            }

            // ── Page numbers ──────────────────────────────────────────────────

            function addPageNumbers() {
                const total = doc.internal.getNumberOfPages();
                for (let i = 1; i <= total; i++) {
                    doc.setPage(i);
                    doc.setFontSize(10);
                    doc.setFont('Helvetica', 'Bold');
                    doc.setTextColor('#ffffff');
                    doc.text(i + ' | ' + total, doc.internal.pageSize.width - 18, doc.internal.pageSize.height - 6);
                }
            }

            // ── Main generation ───────────────────────────────────────────────

            addHeader();
            addFooter();
            addInformation();

            const sections = templateData.sections || [];
            let profileAdded = false;

            sections.forEach(function (section) {
                if (section.type === 'page_break') {
                    doc.addPage(); addHeader(); addFooter(); y = 35;
                } else if (section.type === 'table') {
                    const caption = (section.caption || '').toLowerCase();
                    if (!profileAdded && (caption.includes('profile') || caption.includes('personlig') || caption.includes('profil'))) {
                        addProfile();
                        profileAdded = true;
                        doc.addPage(); addHeader(); addFooter(); y = 35;
                    } else if (section.columns && section.columns > 3) {
                        generateMultiColTable(section);
                    } else {
                        generate3ColTable(section);
                    }
                } else if (section.type === 'text') {
                    generateTextSection(section);
                }
            });

            addSummaryPage();

            doc.insertPage(1);
            addHeader();
            addFooter();
            addTOC();

            addCoverPage();
            addPageNumbers();

            // ── Output ────────────────────────────────────────────────────────

            const blobPDF = new Blob([doc.output('blob')], { type: 'application/pdf' });
            const blobURL = URL.createObjectURL(blobPDF);
            const orderRef = candidate.orderId || 'report';

            if (action === 'preview') {
                document.getElementById('bkReportFrame').src = blobURL;
                document.getElementById('bkPreviewModal').classList.remove('hidden');
            } else if (action === 'download') {
                doc.save(orderRef + '.pdf');
            } else if (action === 'upload') {
                const fd = new FormData();
                fd.append('file', blobPDF, orderRef + '.pdf');
                fd.append('_token', csrfToken);
                document.getElementById('bkUploadMsg').textContent = 'Uploading…';
                fetch(uploadUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('bkUploadMsg').textContent =
                            data.success ? '✓ Report submitted successfully!' : '✗ Upload failed.';
                    })
                    .catch(() => {
                        document.getElementById('bkUploadMsg').textContent = '✗ Upload failed.';
                    });
            }
        },

        /**
         * Pre-load both background images as base64 so jsPDF can use them
         * without cross-origin issues. Call once on page load.
         */
        preloadImages: function (bg2Url, bg3Url) {
            const self = this;
            [{ url: bg2Url, key: '_bg2' }, { url: bg3Url, key: '_bg3' }].forEach(function (img) {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const image = new Image();
                image.crossOrigin = 'anonymous';
                image.onload = function () {
                    canvas.width  = image.width;
                    canvas.height = image.height;
                    ctx.drawImage(image, 0, 0);
                    self[img.key] = canvas.toDataURL('image/webp');
                };
                image.onerror = function () { self[img.key] = null; };
                image.src = img.url;
            });
        },
    };
}(window));
