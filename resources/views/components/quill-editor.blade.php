@props([
    'editorId' => 'editor',
    'height' => '100px',
    'maxHeight' => '500px',
    'type' => 'full', // Options: 'full', 'basic', 'minimal'
    'customToolbar' => null, // For custom toolbar configuration
])

@once
<link rel="stylesheet" href="{{ asset('vendor/quill/quill.min.css') }}" onerror="this.onerror=null;this.href='https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css';" />
<style>
    .ql-editor {
        min-height: {{ $height }};
        @if($maxHeight !== '-1')
        max-height: {{ $maxHeight }};
        @endif
        overflow-y: auto;
    }
    .ql-toolbar.ql-snow {
        border-radius: 10px 10px 0px 0px;
        margin-bottom: 0px;
    }
    /* Create a container for Quill to target */
    .quill-container {
        border: 1px solid #ccc;
        border-radius: 0 0 10px 10px;
        background: transparent;
    }
    .dark .quill-container {
        border-color: #4b5563;
        color: #e5e7eb;
    }
    .dark .ql-snow {
        border-color: #4b5563;
    }
    .dark .ql-toolbar.ql-snow .ql-picker-label,
    .dark .ql-toolbar.ql-snow .ql-picker-options,
    .dark .ql-toolbar.ql-snow button,
    .dark .ql-toolbar.ql-snow span {
        color: #e5e7eb;
    }
    .dark .ql-snow .ql-stroke {
        stroke: #e5e7eb;
    }
    .dark .ql-snow .ql-fill {
        fill: #e5e7eb;
    }
    .dark .ql-editor.ql-blank::before {
        color: rgba(255, 255, 255, 0.6);
    }

    /* Alternative using iconify icon */
    .ql-toolbar .ql-media-modal {
        width: 28px;
        height: 28px;
    }
    
    .ql-toolbar .ql-media-modal:after {
        content: '';
        background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>');
        background-size: 18px;
        background-repeat: no-repeat;
        background-position: center;
        width: 100%;
        height: 100%;
        display: block;
    }
</style>

<script src="{{ asset('vendor/quill/quill.min.js') }}" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';"></script>
@endonce

<div class="mb-2 flex items-center gap-2">
    <button
        type="button"
        id="quill-toggle-source-{{ $editorId }}"
        class="btn-default !px-3 !py-1 text-xs"
    >
        {{ __('Source HTML') }}
    </button>
    <span id="quill-mode-label-{{ $editorId }}" class="text-xs text-gray-500 dark:text-gray-400">
        {{ __('Visual mode') }}
    </span>
</div>

<script>
    async function ensureQuillLoaded() {
        if (typeof window.Quill !== 'undefined') {
            return true;
        }

        await new Promise((resolve) => setTimeout(resolve, 50));
        if (typeof window.Quill !== 'undefined') {
            return true;
        }

        await new Promise((resolve, reject) => {
            const existingScript = Array.from(document.scripts).find((script) =>
                script.src.includes('/vendor/quill/quill.min.js') ||
                script.src.includes('cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js')
            );

            if (existingScript) {
                existingScript.addEventListener('load', () => resolve(), { once: true });
                existingScript.addEventListener('error', () => reject(new Error('Failed to load Quill script.')), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js';
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Failed to load Quill from CDN.'));
            document.head.appendChild(script);
        });

        return typeof window.Quill !== 'undefined';
    }

    document.addEventListener('DOMContentLoaded', async function() {
        const editorId = '{{ $editorId }}';
        const editorType = '{{ $type }}';
        const textareaElement = document.getElementById(editorId);
        const customToolbar = @json($customToolbar);

        if (!textareaElement) {
            console.error(`Textarea with ID "${editorId}" not found`);
            return;
        }

        // Create a div after the textarea to host Quill
        const quillContainer = document.createElement('div');
        quillContainer.id = `quill-${editorId}`;
        quillContainer.className = 'quill-container';
        textareaElement.insertAdjacentElement('afterend', quillContainer);
        const toggleButton = document.getElementById(`quill-toggle-source-${editorId}`);
        const modeLabel = document.getElementById(`quill-mode-label-${editorId}`);

        // Store original textarea content
        const initialContent = textareaElement.value || '';

        // Define toolbar configurations based on type (removed default image handler)
        const toolbarConfigs = {
            full: [
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['blockquote'],
                [{ 'align': [] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'indent': '-1' }, { 'indent': '+1' }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'font': [] }],
                ['link', 'media-modal', 'video', 'code-block']
            ],
            basic: [
                ['bold', 'italic', 'underline'],
                [{ 'header': [1, 2, 3, false] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['link', 'media-modal']
            ],
            minimal: [
                ['bold', 'italic'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }]
            ]
        };

        // Select toolbar configuration based on type or use custom if provided
        const toolbarConfig = customToolbar ? JSON.parse(customToolbar) :
                             (toolbarConfigs[editorType] || toolbarConfigs.basic);

        // Custom media modal handler
        const mediaModalHandler = function() {
            const modalId = `quillMediaModal_${editorId}`;
            
            // Open media modal using the existing component (single selection for editor)
            openMediaModal(modalId, false, 'all', `handleQuillMediaSelect_${editorId}`);
        };

        try {
            await ensureQuillLoaded();
        } catch (error) {
            console.error('Quill could not be loaded:', error);
            return;
        }

        if (typeof window.Quill === 'undefined') {
            console.error('Quill is not available on window after loading attempts.');
            return;
        }

        const formatHtmlForSource = function(html) {
            if (!html) {
                return '';
            }

            return html
                .replace(/&nbsp;/g, ' ')
                .replace(/></g, '>\n<')
                .replace(/\n{3,}/g, '\n\n')
                .trim();
        };

        const normalizeSourceForVisual = function(html) {
            if (!html) {
                return '';
            }

            return html.replace(/\u00A0/g, ' ');
        };

        // Initialize Quill on the container div
        const quill = new window.Quill(`#quill-${editorId}`, {
            theme: "snow",
            placeholder: '{{ __('Type here...') }}',
            modules: {
                toolbar: {
                    container: toolbarConfig,
                    handlers: {
                        'media-modal': mediaModalHandler
                    }
                }
            }
        });

        window['quill_' + editorId] = quill;

        // Create media selection handler function for this specific editor
        window[`handleQuillMediaSelect_${editorId}`] = function(files) {
            if (files.length > 0) {
                const file = files[0];
                const range = quill.getSelection(true);
                
                if (file.mime_type && file.mime_type.startsWith('image/')) {
                    // Insert image
                    quill.insertEmbed(range.index, 'image', file.url, 'user');
                } else {
                    // Insert as link for non-image files
                    quill.insertText(range.index, file.name, 'link', file.url, 'user');
                }
                
                // Move cursor after inserted content
                quill.setSelection(range.index + 1);
            }
        };

        // Set initial content from textarea
        if (initialContent) {
            quill.clipboard.dangerouslyPasteHTML(initialContent);
        }

        // Hide textarea visually but keep it in the DOM for form submission
        textareaElement.style.display = 'none';
        textareaElement.classList.remove('sr-only');

        // Update textarea on editor change for form submission
        quill.on('text-change', function() {
            textareaElement.value = quill.root.innerHTML;
            
            // Trigger form change detection for the unsaved changes warning
            const event = new Event('input', { bubbles: true });
            textareaElement.dispatchEvent(event);
        });

        // Also update on form submit to ensure the latest content is captured
        const form = textareaElement.closest('form');
        let isSourceMode = false;

        const toolbarElement = quillContainer.previousElementSibling;
        const showVisualMode = function() {
            if (toolbarElement) {
                toolbarElement.style.display = '';
            }
            quillContainer.style.display = '';
            textareaElement.style.display = 'none';
            if (toggleButton) {
                toggleButton.textContent = @json(__('Source HTML'));
            }
            if (modeLabel) {
                modeLabel.textContent = @json(__('Visual mode'));
            }
            isSourceMode = false;
        };

        const showSourceMode = function() {
            textareaElement.value = formatHtmlForSource(quill.root.innerHTML);
            if (toolbarElement) {
                toolbarElement.style.display = 'none';
            }
            quillContainer.style.display = 'none';
            textareaElement.style.display = 'block';
            textareaElement.classList.add('form-control');
            textareaElement.style.minHeight = '{{ $height }}';
            if (toggleButton) {
                toggleButton.textContent = @json(__('Visual editor'));
            }
            if (modeLabel) {
                modeLabel.textContent = @json(__('Source mode'));
            }
            isSourceMode = true;
        };

        if (toggleButton) {
            toggleButton.addEventListener('click', function() {
                if (isSourceMode) {
                    quill.clipboard.dangerouslyPasteHTML(normalizeSourceForVisual(textareaElement.value || ''));
                    showVisualMode();
                } else {
                    showSourceMode();
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function() {
                if (!isSourceMode) {
                    textareaElement.value = quill.root.innerHTML;
                }
            });
        }

    });
</script>

<!-- Include the media modal component for Quill editor -->
<x-media-modal 
    :id="'quillMediaModal_' . $editorId" 
    title="Select Media for Editor"
    :multiple="false"
    allowedTypes="all"
    buttonText="Select Media"
    buttonClass="hidden"
/>
