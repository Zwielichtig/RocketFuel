import { EditorView } from '@codemirror/view';
import { javascript } from '@codemirror/lang-javascript';
import { basicSetup } from 'codemirror';
import { autocompletion } from "@codemirror/autocomplete";
import { dracula } from 'thememirror';

export class ScriptEditorHandler {
    private static instance: ScriptEditorHandler;
    private editor?: EditorView;

    public static getInstance(): ScriptEditorHandler {
        if (!ScriptEditorHandler.instance) {
            ScriptEditorHandler.instance = new ScriptEditorHandler();
        }
        return ScriptEditorHandler.instance;
    }

    public init(): void {
        this.addCodeEditor();
    }

    private addCodeEditor(): void {
        const editorElement = document.querySelector('.codemirror-editor-container');

        if (!editorElement) {
            console.warn('[ScriptEditorHandler] Editor container not found.');
            return;
        }

        this.editor = new EditorView({
            doc: '#!/bin/bash',
            extensions: [basicSetup, javascript(), autocompletion(), dracula],
            parent: editorElement,
        });
    }

    public getEditorContent(): string | undefined {
        return this.editor?.state.doc.toString();
    }

    public setEditorContent(content: string): void {
        if (this.editor) {
            const transaction = this.editor.state.update({
                changes: {
                    from: 0,
                    to: this.editor.state.doc.length,
                    insert: content,
                },
            });
            this.editor.dispatch(transaction);
        }
    }
}
