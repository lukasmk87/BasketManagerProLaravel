<template>
    <div class="rich-text-editor">
        <!-- Toolbar -->
        <div v-if="editor" class="border border-gray-300 dark:border-gray-600 rounded-t-md bg-gray-50 dark:bg-gray-800 p-2 flex flex-wrap gap-1">
            <!-- Text Formatting -->
            <div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2">
                <button
                    type="button"
                    @click="editor.chain().focus().toggleBold().run()"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive('bold') }"
                    class="toolbar-button"
                    title="Fett (Ctrl+B)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleItalic().run()"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive('italic') }"
                    class="toolbar-button"
                    title="Kursiv (Ctrl+I)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4M14 4L10 20M6 20h4"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleUnderline().run()"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive('underline') }"
                    class="toolbar-button"
                    title="Unterstrichen (Ctrl+U)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3v9a5 5 0 0 0 10 0V3M5 21h14"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleStrike().run()"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive('strike') }"
                    class="toolbar-button"
                    title="Durchgestrichen"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12h18M9 5C7.5 6.5 7 8 7 10h10c0-2-.5-3.5-2-5M9 19c1.5-1.5 2-3 2-5h2c0 2 .5 3.5 2 5"/>
                    </svg>
                </button>
            </div>

            <!-- Headings -->
            <div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2">
                <button
                    type="button"
                    @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive('heading', { level: 2 }) }"
                    class="toolbar-button"
                    title="Überschrift 2"
                >
                    <span class="font-bold">H2</span>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive('heading', { level: 3 }) }"
                    class="toolbar-button"
                    title="Überschrift 3"
                >
                    <span class="font-bold">H3</span>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().setParagraph().run()"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive('paragraph') }"
                    class="toolbar-button"
                    title="Absatz"
                >
                    <span class="font-normal">P</span>
                </button>
            </div>

            <!-- Lists -->
            <div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2">
                <button
                    type="button"
                    @click="editor.chain().focus().toggleBulletList().run()"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive('bulletList') }"
                    class="toolbar-button"
                    title="Aufzählung"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().toggleOrderedList().run()"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive('orderedList') }"
                    class="toolbar-button"
                    title="Nummerierte Liste"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h1M3 8h1M3 12h1M3 16h1M3 20h1M9 4h12M9 8h12M9 12h12M9 16h12M9 20h12"/>
                    </svg>
                </button>
            </div>

            <!-- Text Alignment -->
            <div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2">
                <button
                    type="button"
                    @click="editor.chain().focus().setTextAlign('left').run()"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive({ textAlign: 'left' }) }"
                    class="toolbar-button"
                    title="Linksbündig"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M3 12h12M3 18h18"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().setTextAlign('center').run()"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive({ textAlign: 'center' }) }"
                    class="toolbar-button"
                    title="Zentriert"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M6 12h12M3 18h18"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().setTextAlign('right').run()"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive({ textAlign: 'right' }) }"
                    class="toolbar-button"
                    title="Rechtsbündig"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6h18M9 12h12M3 18h18"/>
                    </svg>
                </button>
            </div>

            <!-- Link -->
            <div class="flex gap-1 border-r border-gray-300 dark:border-gray-600 pr-2">
                <button
                    type="button"
                    @click="setLink"
                    :class="{ 'bg-gray-300 dark:bg-gray-600': editor.isActive('link') }"
                    class="toolbar-button"
                    title="Link einfügen"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </button>
                <button
                    v-if="editor.isActive('link')"
                    type="button"
                    @click="editor.chain().focus().unsetLink().run()"
                    class="toolbar-button"
                    title="Link entfernen"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636"/>
                    </svg>
                </button>
            </div>

            <!-- Undo/Redo -->
            <div class="flex gap-1">
                <button
                    type="button"
                    @click="editor.chain().focus().undo().run()"
                    :disabled="!editor.can().undo()"
                    class="toolbar-button"
                    title="Rückgängig (Ctrl+Z)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="editor.chain().focus().redo().run()"
                    :disabled="!editor.can().redo()"
                    class="toolbar-button"
                    title="Wiederholen (Ctrl+Y)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a8 8 0 00-8 8v2m18-10l-6 6m6-6l-6-6"/>
                    </svg>
                </button>
            </div>

            <!-- Clear Formatting -->
            <div class="flex gap-1 ml-auto">
                <button
                    type="button"
                    @click="editor.chain().focus().clearNodes().unsetAllMarks().run()"
                    class="toolbar-button"
                    title="Formatierung entfernen"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Editor Content -->
        <editor-content
            :editor="editor"
            class="prose prose-sm max-w-none border border-gray-300 dark:border-gray-600 rounded-b-md p-3 min-h-[200px] bg-white dark:bg-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
            :class="{ 'border-red-500 dark:border-red-500': error }"
        />

        <!-- Character Counter & Error -->
        <div class="flex justify-between items-center mt-2">
            <div v-if="error" class="text-sm text-red-600 dark:text-red-400">
                {{ error }}
            </div>
            <div v-else class="text-sm text-gray-500"></div>

            <div v-if="maxLength" class="text-sm text-gray-500 dark:text-gray-400">
                {{ characterCount }} / {{ maxLength }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link'
import TextAlign from '@tiptap/extension-text-align'
import Placeholder from '@tiptap/extension-placeholder'
import CharacterCount from '@tiptap/extension-character-count'
import { watch, computed } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Geben Sie hier Ihren Text ein...',
    },
    maxLength: {
        type: Number,
        default: null,
    },
    error: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [2, 3],
            },
        }),
        Underline,
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-blue-600 dark:text-blue-400 underline hover:text-blue-800 dark:hover:text-blue-300',
            },
        }),
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
        ...(props.maxLength ? [CharacterCount.configure({ limit: props.maxLength })] : []),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none focus:outline-none dark:prose-invert',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML())
    },
})

// Watch for external changes to modelValue
watch(() => props.modelValue, (value) => {
    if (editor.value && value !== editor.value.getHTML()) {
        editor.value.commands.setContent(value, false)
    }
})

// Character count
const characterCount = computed(() => {
    return editor.value ? editor.value.storage.characterCount?.characters() || 0 : 0
})

// Set link function
const setLink = () => {
    const previousUrl = editor.value.getAttributes('link').href
    const url = window.prompt('URL:', previousUrl)

    // cancelled
    if (url === null) {
        return
    }

    // empty
    if (url === '') {
        editor.value.chain().focus().extendMarkRange('link').unsetLink().run()
        return
    }

    // update link
    editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}
</script>

<style scoped>
.toolbar-button {
    @apply p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Tiptap Editor Styling */
:deep(.ProseMirror) {
    min-height: 200px;
    outline: none;
}

:deep(.ProseMirror p.is-editor-empty:first-child::before) {
    color: #adb5bd;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

:deep(.ProseMirror h2) {
    @apply text-2xl font-bold mt-4 mb-2;
}

:deep(.ProseMirror h3) {
    @apply text-xl font-bold mt-3 mb-2;
}

:deep(.ProseMirror ul) {
    @apply list-disc pl-6 my-2;
}

:deep(.ProseMirror ol) {
    @apply list-decimal pl-6 my-2;
}

:deep(.ProseMirror a) {
    @apply text-blue-600 dark:text-blue-400 underline hover:text-blue-800 dark:hover:text-blue-300;
}

:deep(.ProseMirror blockquote) {
    @apply border-l-4 border-gray-300 dark:border-gray-600 pl-4 italic my-2;
}

:deep(.ProseMirror code) {
    @apply bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded text-sm;
}

:deep(.ProseMirror pre) {
    @apply bg-gray-100 dark:bg-gray-800 p-3 rounded my-2 overflow-x-auto;
}
</style>
