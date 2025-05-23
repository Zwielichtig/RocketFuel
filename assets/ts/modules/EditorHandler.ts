import { EditorView } from '@codemirror/view';
import { javascript } from '@codemirror/lang-javascript';
import { basicSetup } from 'codemirror';
import { autocompletion } from "@codemirror/autocomplete";
import { dracula } from 'thememirror';
import { AjaxHelper } from '../helper/AjaxHelper';
import { EditorState } from '@codemirror/state';

export class EditorHandler
{
    private static instance: EditorHandler;
    private editor?: EditorView;
    private static readonly BLOCK_DELIMITER = '\u200C\u200D\u200C'; // Zero-width non-joiner + zero-width joiner + zero-width non-joiner

    public static getInstance(): EditorHandler {
        if (!EditorHandler.instance) {
            EditorHandler.instance = new EditorHandler();
        }
        return EditorHandler.instance;
    }

    public init(): void
    {
        this.addCodeEditor();
        this.searchLogics();
        this.populateEditor();
        this.initCustomBlockButton();
    }

    private addCodeEditor(): void
    {
        const editorElement = document.querySelector('.codemirror-editor-container');
        const saveButton = document.querySelector('.ts-button-save') as HTMLButtonElement | null;
        const ajaxHelper = new AjaxHelper();

        // Create initial state
        const startState = EditorState.create({
            doc: '',
            extensions: [
                basicSetup,
                javascript(),
                autocompletion(),
                dracula,
                EditorView.lineWrapping,
                EditorView.theme({
                    '&': {
                        height: '100%',
                    },
                    '.cm-scroller': {
                        overflow: 'auto',
                    },
                }),
                // Add change listener to track content modifications
                EditorView.updateListener.of((update) => {
                    // Only handle content changes that aren't from our own updates
                    if (update.docChanged &&
                        !update.transactions.some(t =>
                            t.isUserEvent("reorder") ||
                            t.isUserEvent("load") ||
                            t.isUserEvent("block-update")
                        )) {
                        this.handleEditorContentChange();
                    }
                }),
            ],
        });

        // Create editor with initial state
        this.editor = new EditorView({
            state: startState,
            parent: editorElement,
        });

        saveButton.addEventListener('click', async () => {
            const inputs = document.querySelectorAll('[class^="editor-input-"]');
            const values: Record<string, string> = {};

            // Get the editor type from the URL
            const isScriptType = window.location.pathname.includes('/script/');

            let saveData: any = {
                values: {}
            };

            // Get all input values
            inputs.forEach(input => {
                const el = input as HTMLInputElement;
                if (el.name) {
                    values[el.name] = el.value;
                }
            });
            saveData.values = values;

            if (isScriptType) {
                console.log('Preparing script save data');
                // For scripts, use the block-based format
                // First, ensure we have the latest content from the editor
                this.handleEditorContentChange();

                // Get blocks in order
                const blocks = Array.from(document.querySelectorAll('.logic-block'))
                    .sort((a, b) => {
                        const posA = parseInt(a.getAttribute('data-position') || '0', 10);
                        const posB = parseInt(b.getAttribute('data-position') || '0', 10);
                        return posA - posB;
                    })
                    .map((block, index) => {
                        // Get the current content from the block's data attribute
                        const content = block.getAttribute('data-current-content') || '';
                        const blockId = block.getAttribute('data-logic-id');
                        const name = block.querySelector('.block-name')?.textContent?.trim() || '';
                        const isCustom = blockId?.toString().startsWith('custom_') || false;

                        return {
                            id: isCustom ? null : blockId,
                            name: name,
                            content: content.trim(), // Ensure content is trimmed
                            position: index + 1,
                            isCustom: isCustom
                        };
                    });

                console.log('Blocks to save:', blocks);

                // Set the blocks in the save data
                saveData.blocks = blocks;

                // Also include the current editor content as script
                // This ensures we have the complete, up-to-date content
                saveData.script = this.getEditorContent() || '';
                console.log('Full script content:', saveData.script);
            } else {
                // For logics, send the raw editor content as a string
                saveData.script = this.getEditorContent() || '';
            }

            console.log('Sending save data:', saveData);

                try {
                    const response = await ajaxHelper.request('/ajax/editor/save', {
                        method: 'POST',
                    body: saveData,
                    });

                console.log('Save response:', response);

                    if (response.redirect) {
                        window.location.href = response.redirect;
                    }
                } catch (error) {
                    console.error('Error saving script:', error);
            }
        });
    }

    public getEditorContent(): string | undefined
    {
        return this.editor?.state.doc.toString();
    }

    public searchLogics(): void
    {
        // Only initialize search functionality in script mode
        if (!window.location.pathname.includes('/script/')) {
            return;
        }

        const searchInput = document.querySelector('.editor-search-input') as HTMLInputElement;
        const searchButton = document.querySelector('.editor-search-button') as HTMLButtonElement;
        const clearButton = document.querySelector('.editor-search-clear') as HTMLButtonElement;
        const resultsContainer = document.querySelector('.editor-search-results') as HTMLDivElement;

        // If any of the required elements are missing, don't initialize search
        if (!searchInput || !searchButton || !clearButton || !resultsContainer) {
            console.warn('Search elements not found, skipping search initialization');
            return;
        }

        const ajaxHelper = new AjaxHelper();

        // Function to update clear button visibility
        const updateClearButton = () => {
            clearButton.style.display = searchInput.value ? 'flex' : 'none';
        };

        // Clear button click handler
        clearButton.addEventListener('click', () => {
            searchInput.value = '';
            updateClearButton();
            resultsContainer.style.display = 'none';
            searchInput.focus();
        });

        // Update clear button on input
        searchInput.addEventListener('input', () => {
            updateClearButton();
            clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(() => {
                if (searchInput.value) {
                    searchButton.click();
                } else {
                    resultsContainer.style.display = 'none';
                }
            }, 300);
        });

        // Initial clear button state
        updateClearButton();

        // Function to display results
        const displayResults = (results: any[]) => {
            console.log('Displaying search results:', results);
            if (results.length === 0) {
                resultsContainer.innerHTML = '<div class="no-results">Keine Ergebnisse gefunden</div>';
            } else {
                resultsContainer.innerHTML = results.map(result => `
                    <div class="search-result-item" data-id="${result.id}">
                        <span class="result-name">${result.name}</span>
                        ${result.packagemanager ? `<span class="result-pm">${result.packagemanager}</span>` : ''}
                        <span class="result-tag">${result.category}</span>
                        <span class="result-creator">${result.creator}</span>
                    </div>
                `).join('');

                console.log('Search results HTML added to container');

                // Add click event to each result item
                const resultItems = resultsContainer.querySelectorAll('.search-result-item');
                console.log('Found result items:', resultItems.length);

                resultItems.forEach(item => {
                    console.log('Adding click handler to item:', item.getAttribute('data-id'));
                    item.addEventListener('click', (event) => {
                        const id = item.getAttribute('data-id');
                        if (id) {
                            const result = results.find(r => r.id.toString() === id);
                            console.log('Found matching result:', result);
                            if (result) {
                                this.addLogicBlockFromSearch(result);
                                resultsContainer.style.display = 'none';
                            }
                        }
                    });
                });
            }
            resultsContainer.style.display = 'block';
        };

        // Search on button click
        searchButton.addEventListener('click', async(event) => {
            event.preventDefault(); // Prevent form submission
            const query = searchInput.value;
            console.log('Search button clicked with query:', query);
            if (query) {
                try {
                    const response = await ajaxHelper.request('/ajax/editor/searchLogics', {
                        method: 'POST',
                        body: { query: query },
                    });
                    console.log('Search response:', response);
                    displayResults(response);
                } catch (error) {
                    console.error('Error searching logics:', error);
                    resultsContainer.innerHTML = '<div class="no-results">Fehler bei der Suche</div>';
                    resultsContainer.style.display = 'block';
                }
            } else {
                resultsContainer.style.display = 'none';
            }
        });

        // Hide results when clicking outside
        document.addEventListener('click', (event) => {
            if (!resultsContainer.contains(event.target as Node) &&
                !searchInput.contains(event.target as Node) &&
                !searchButton.contains(event.target as Node)) {
                resultsContainer.style.display = 'none';
            }
        });

        let debounceTimer: number;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(() => {
                if (searchInput.value) {
                    searchButton.click();
                } else {
                    resultsContainer.style.display = 'none';
                }
            }, 300);
        });
    }

    public async populateEditor()
    {
        const ajaxHelper = new AjaxHelper();
        const currentUrl = window.location.href;
        const isScriptType = window.location.pathname.includes('/script/');
        const isNewScript = currentUrl.endsWith('/0');

        console.log('Starting populateEditor:', { isScriptType, currentUrl, isNewScript });

        if (!isNewScript) {
            try {
                const response = await ajaxHelper.request('/ajax/editor/getData', {
                    method: 'GET',
                });

                if (response && response.data) {
                    const data = response.data;
                    console.log('Received data from server:', {
                        scriptLength: data.script?.length,
                        dependenciesCount: data.dependencies?.length,
                        dependencies: data.dependencies
                    });

                    // Update editor content based on type
                    if (isScriptType) {
                        console.log('Handling script type editor');

                        // First, clear any existing blocks and content
                        const blocksContainer = document.querySelector('.editor-logic-blocks');
                        const existingBlocks = blocksContainer?.querySelectorAll('.logic-block');
                        console.log('Clearing existing blocks:', {
                            containerExists: !!blocksContainer,
                            existingBlocksCount: existingBlocks?.length,
                            existingBlocks: Array.from(existingBlocks || []).map(b => ({
                                id: b.getAttribute('data-logic-id'),
                                name: b.querySelector('.block-name')?.textContent,
                                isCustom: b.getAttribute('data-logic-id')?.startsWith('custom_')
                            }))
                        });

                        if (blocksContainer) {
                            blocksContainer.innerHTML = '';
                        }

                        // Clear the editor content first
                        console.log('Clearing editor content');
                        this.updateEditorContent('');

                        // Handle dependencies/blocks for scripts
                        if (data.dependencies && Array.isArray(data.dependencies)) {
                            // Sort dependencies by position
                            const sortedDependencies = [...data.dependencies].sort((a, b) =>
                                (a.position || 0) - (b.position || 0)
                            );

                            console.log('Sorted dependencies:', sortedDependencies.map(d => ({
                                id: d.id,
                                name: d.name,
                                isCustom: d.isCustom,
                                position: d.position,
                                contentLength: d.content?.length
                            })));

                            // First, add all blocks without updating content
                            sortedDependencies.forEach((block: any, index: number) => {
                                console.log(`Adding block ${index + 1}:`, {
                                    id: block.id,
                                    name: block.name,
                                    isCustom: block.isCustom,
                                    contentLength: block.content?.length
                                });

                                const blockData = {
                                    id: block.id || `custom_${Date.now()}`,
                                    name: block.name,
                                    description: block.description || 'Custom Block',
                                    category: block.category || 'Custom',
                                    creator: block.creator || 'User',
                                    content: block.content,
                                    packagemanager: block.packagemanager || null,
                                    isCustom: block.isCustom || false
                                };
                                this.addLogicBlock(blockData);
                            });

                            // After all blocks are added, update the editor content
                            if (data.script) {
                                console.log('Setting initial editor content');
                                this.updateEditorContent(data.script, 'load');
                            }

                            // Now manually set each block's content
                            const blocks = Array.from(document.querySelectorAll('.logic-block'))
                                .sort((a, b) => {
                                    const posA = parseInt(a.getAttribute('data-position') || '0', 10);
                                    const posB = parseInt(b.getAttribute('data-position') || '0', 10);
                                    return posA - posB;
                                });

                            blocks.forEach((block, index) => {
                                const blockId = block.getAttribute('data-logic-id');
                                const isCustom = blockId?.startsWith('custom_') || false;
                                const blockData = sortedDependencies[index];

                                if (blockData) {
                                    console.log(`Setting block ${index + 1} content:`, {
                                        id: blockId,
                                        name: blockData.name,
                                        isCustom,
                                        contentLength: blockData.content?.length
                                    });

                                    // Store both original and current content
                                    block.setAttribute('data-logic-content', blockData.content || '');
                                    block.setAttribute('data-current-content', blockData.content || '');
                                }
                            });

                            // After setting all block content, update the editor one final time
                            this.updateEditorContentFromBlocks();
                        }
                    } else {
                        console.log('Handling logic type editor');
                        // For logics, just set the content directly
                        this.updateEditorContent(data.script || '');
                    }

                    // Map of data keys to their corresponding class names
                    const fieldMappings = {
                        name: 'editor-input-name',
                        description: 'editor-input-description',
                        packageManager: 'editor-input-packageManager',
                        category: 'editor-input-category',
                        isPublic: 'editor-input-isPublic'
                    };

                    // Populate input fields with values
                    for (const [key, value] of Object.entries(data)) {
                        if (key in fieldMappings) {
                            const className = fieldMappings[key as keyof typeof fieldMappings];
                            const input = document.querySelector(`.${className}`) as HTMLInputElement | HTMLSelectElement;

                            if (input) {
                                if (input.type === 'checkbox') {
                                    (input as HTMLInputElement).checked = Boolean(value);
                                } else if (input instanceof HTMLSelectElement && key === 'packageManager' && value === null) {
                                    // Special handling for packageManager when value is null
                                    input.value = 'null';
                                } else {
                                    input.value = value === null ? '' : String(value);
                                }
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Error populating editor:', error);
            }
        } else if (isScriptType) {
            // For new scripts, create a "Start" block with the shebang line
            console.log('Initializing new script with Start block');
            const startBlock = {
                id: `custom_${Date.now()}`,
                name: 'Start',
                content: '#!/bin/bash',
                description: 'Skript Initialisierung',
                category: 'System',
                creator: 'System',
                isCustom: true
            };
            this.addLogicBlock(startBlock);
            this.updateEditorContentFromBlocks();
        }
    }

    private addLogicBlock(logic: any): void {
        const blocksContainer = document.querySelector('.editor-logic-blocks');
        if (!blocksContainer) return;

        // Check if block already exists
        const existingBlock = blocksContainer.querySelector(`[data-logic-id="${logic.id}"]`);
        if (existingBlock) return;

        const blockElement = document.createElement('div');
        blockElement.className = 'logic-block';
        blockElement.setAttribute('data-logic-id', logic.id);
        blockElement.setAttribute('draggable', 'true');

        // Store both the original and current content, ensuring proper trimming
        const originalContent = (logic.content || '').trim();
        blockElement.setAttribute('data-logic-content', originalContent);
        blockElement.setAttribute('data-current-content', originalContent);

        // Get the current position number (number of existing blocks + 1)
        const position = blocksContainer.children.length + 1;
        blockElement.setAttribute('data-position', position.toString());

        blockElement.innerHTML = `
            <div class="block-header">
                <span class="block-position">${position}</span>
                <span class="block-drag-handle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="12" r="1"></circle>
                        <circle cx="9" cy="5" r="1"></circle>
                        <circle cx="9" cy="19" r="1"></circle>
                        <circle cx="15" cy="12" r="1"></circle>
                        <circle cx="15" cy="5" r="1"></circle>
                        <circle cx="15" cy="19" r="1"></circle>
                    </svg>
                </span>
                <span class="block-name" ${logic.id.toString().startsWith('custom_') ? 'contenteditable="true" spellcheck="false"' : ''}>${logic.name}</span>
                ${logic.packagemanager ? `<span class="block-pm">${logic.packagemanager}</span>` : ''}
                <button type="button" class="block-remove" title="Remove logic block">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18"></path>
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
            <div class="block-details">
                <div class="block-description">${logic.description || ''}</div>
                <div class="block-meta">
                    <span class="block-category">${logic.category}</span>
                    <span class="block-creator">by ${logic.creator}</span>
                </div>
            </div>
        `;

        // Add name editing functionality only for custom blocks
        const nameElement = blockElement.querySelector('.block-name');
        if (nameElement && logic.id.toString().startsWith('custom_')) {
            nameElement.addEventListener('blur', () => {
                const newName = nameElement.textContent?.trim() || 'Unbenannter Block';
                nameElement.textContent = newName;

                // Update the content to reflect the new name
                const currentContent = blockElement.getAttribute('data-current-content') || '';
                const lines = currentContent.split('\n');
                if (lines.length > 0) {
                    lines[0] = '# ' + newName;
                    const newContent = lines.join('\n');
                    blockElement.setAttribute('data-current-content', newContent);
                    this.updateEditorContentFromBlocks();
                }
            });

            nameElement.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    (nameElement as HTMLElement).blur();
                }
            });
        }

        // Add drag and drop event listeners
        blockElement.addEventListener('dragstart', (e) => {
            e.dataTransfer?.setData('text/plain', logic.id.toString());
            // Only add dragging class to the current block
            blockElement.classList.add('dragging');

            // Ensure we have the latest content before drag starts
            this.handleEditorContentChange();
        });

        blockElement.addEventListener('dragend', () => {
            // Remove dragging class from all blocks
            document.querySelectorAll('.logic-block').forEach(block => {
                block.classList.remove('dragging');
            });

            // Update positions and content after drag ends
            requestAnimationFrame(() => {
                this.updateBlockPositions();
                this.updateEditorContentFromBlocks();
            });
        });

        blockElement.addEventListener('dragover', (e) => {
            e.preventDefault();
            const draggingElement = document.querySelector('.dragging');
            if (draggingElement && draggingElement !== blockElement) {
                const rect = blockElement.getBoundingClientRect();
                const midY = rect.top + rect.height / 2;

                if (e.clientY < midY) {
                    blockElement.parentNode?.insertBefore(draggingElement, blockElement);
                } else {
                    blockElement.parentNode?.insertBefore(draggingElement, blockElement.nextSibling);
                }
            }
        });

        // Add remove button click handler
        const removeButton = blockElement.querySelector('.block-remove');
        if (removeButton) {
            removeButton.addEventListener('click', (event) => {
                event.stopPropagation();
                blockElement.remove();
                // Ensure we update both positions and content after removal
                requestAnimationFrame(() => {
                    this.updateBlockPositions();
                    this.updateEditorContentFromBlocks();
                });
            });
        }

        blocksContainer.appendChild(blockElement);

        // Add drop event listener to the container
        blocksContainer.addEventListener('drop', (e) => {
            e.preventDefault();
            // Ensure we update both positions and content after drop
            requestAnimationFrame(() => {
                this.updateBlockPositions();
                this.updateEditorContentFromBlocks();
            });
        });
    }

    private updateBlockPositions(): void {
        const blocks = document.querySelectorAll('.logic-block');
        blocks.forEach((block, index) => {
            const position = index + 1;
            block.setAttribute('data-position', position.toString());
            const positionElement = block.querySelector('.block-position');
            if (positionElement) {
                positionElement.textContent = position.toString();
            }
        });
    }

    private updateEditorContent(content: string, eventType: 'load' | 'reorder' | 'block-update' = 'reorder'): void {
        if (!this.editor) return;

        console.log('Updating editor content:', {
            contentLength: content.length,
            currentLength: this.editor.state.doc.length,
            eventType
        });

        // Create a transaction with the specified event type
        const transaction = this.editor.state.update({
            changes: {
                from: 0,
                to: this.editor.state.doc.length,
                insert: content,
            },
            userEvent: eventType
        });

        this.editor.dispatch(transaction);
        this.editor.requestMeasure();
    }

    private updateEditorContentFromBlocks(): void {
        if (!this.editor) return;

        const blocks = Array.from(document.querySelectorAll('.logic-block'))
            .sort((a, b) => {
                const posA = parseInt(a.getAttribute('data-position') || '0', 10);
                const posB = parseInt(b.getAttribute('data-position') || '0', 10);
                return posA - posB;
            });

        console.log('Updating editor content from blocks:', {
            blocksCount: blocks.length,
            blocks: blocks.map(b => ({
                id: b.getAttribute('data-logic-id'),
                name: b.querySelector('.block-name')?.textContent,
                isCustom: b.getAttribute('data-logic-id')?.startsWith('custom_'),
                position: b.getAttribute('data-position'),
                contentLength: (b.getAttribute('data-current-content') || '').length
            }))
        });

        const contents: string[] = [];
        blocks.forEach(block => {
            const content = block.getAttribute('data-current-content') || '';
            if (content) {
                contents.push(content.trim());
            }
        });

        // Combine all contents with our strict delimiter and ensure proper spacing
        const newContent = contents.join(EditorHandler.BLOCK_DELIMITER + '\n\n');

        // Only update if content has actually changed
        const currentContent = this.editor.state.doc.toString();
        if (currentContent !== newContent) {
            console.log('Content changed, updating editor:', {
                oldLength: currentContent.length,
                newLength: newContent.length
            });

            // Use block-update event type for block-based updates
            this.updateEditorContent(newContent, 'block-update');
        } else {
            console.log('Content unchanged, skipping update');
        }
    }

    private addLogicBlockFromSearch(result: any): void {
        // Ensure we have the latest content before adding a new block
        this.handleEditorContentChange();

        // Add the new block
        this.addLogicBlock(result);

        // Update the editor content with proper spacing
        this.updateEditorContentFromBlocks();
    }

    private handleEditorContentChange(): void {
        if (!this.editor) return;

        const currentContent = this.editor.state.doc.toString();
        const blocks = Array.from(document.querySelectorAll('.logic-block'))
            .sort((a, b) => {
                const posA = parseInt(a.getAttribute('data-position') || '0', 10);
                const posB = parseInt(b.getAttribute('data-position') || '0', 10);
                return posA - posB;
            });

        // For script type, check if we're just editing the shebang line
        if (window.location.pathname.includes('/script/')) {
            const firstLineEnd = currentContent.indexOf('\n');
            if (firstLineEnd !== -1) {
                const firstLine = currentContent.substring(0, firstLineEnd);
                const restOfContent = currentContent.substring(firstLineEnd + 1);

                // If we're only editing the first line, don't process the rest
                if (this.editor.state.selection.main.from <= firstLineEnd) {
                    console.log('Editing shebang line, skipping block update');
                    return;
                }
            }
        }

        console.log('Handling editor content change:', {
            contentLength: currentContent.length,
            blocksCount: blocks.length,
            blocks: blocks.map(b => ({
                id: b.getAttribute('data-logic-id'),
                name: b.querySelector('.block-name')?.textContent,
                isCustom: b.getAttribute('data-logic-id')?.startsWith('custom_'),
                position: b.getAttribute('data-position')
            }))
        });

        // Split the content using our strict delimiter and remove any extra newlines
        const contentParts = currentContent
            .split(EditorHandler.BLOCK_DELIMITER)
            .map((part: string) => part.trim())
            .filter(part => part.length > 0);

        console.log('Content parts:', {
            partsCount: contentParts.length,
            partsLengths: contentParts.map(p => p.length)
        });

        let contentChanged = false;

        // Update each block's content based on its position
        blocks.forEach((block, index) => {
            const blockId = block.getAttribute('data-logic-id');
            const isCustom = blockId?.startsWith('custom_') || false;
            const currentBlockContent = block.getAttribute('data-current-content') || '';

            // For all blocks, use the content from the editor
            if (contentParts[index]) {
                const newContent = contentParts[index];
                if (currentBlockContent !== newContent) {
                    console.log(`Updating block ${index + 1} content:`, {
                        blockId,
                        contentLength: newContent.length,
                        isCustom
                    });
                    block.setAttribute('data-current-content', newContent);
                    contentChanged = true;
                }
            }
        });

        // Only update editor content if something changed
        if (contentChanged) {
            this.updateEditorContentFromBlocks();
        }
    }

    private initCustomBlockButton(): void {
        const customBlockButton = document.querySelector('.editor-add-custom-block');
        if (customBlockButton) {
            customBlockButton.addEventListener('click', () => {
                const customBlock = {
                    id: 'custom_' + Date.now(),
                    name: 'Neuer Block',
                    content: '# Neuer Block',
                    description: 'Benutzerdefinierter Block',
                    category: 'Custom',
                    creator: 'User'
                };
                this.addLogicBlock(customBlock);
                this.updateEditorContentFromBlocks();

                // Focus the name element of the newly created block
                const newBlock = document.querySelector(`[data-logic-id="${customBlock.id}"] .block-name`);
                if (newBlock) {
                    (newBlock as HTMLElement).focus();
                    // Select all text
                    const range = document.createRange();
                    range.selectNodeContents(newBlock);
                    const selection = window.getSelection();
                    if (selection) {
                        selection.removeAllRanges();
                        selection.addRange(range);
                    }
                }
            });
        }
    }
}
