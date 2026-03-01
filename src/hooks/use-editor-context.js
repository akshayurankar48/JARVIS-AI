/**
 * Editor context hook.
 *
 * Reads WordPress editor state and derives a context object
 * used to surface relevant suggested prompts.
 *
 * @package
 * @since 1.1.0
 */

import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Context categories derived from editor state.
 *
 * @typedef {'blank'|'has-content'|'published'} EditorContextType
 */

/**
 * Hook that reads the current editor state and returns
 * a structured context object for prompt generation.
 *
 * @return {Object} Editor context.
 * @return {string} return.type       - 'blank' | 'has-content' | 'published'
 * @return {string} return.postType   - WordPress post type slug (e.g. 'post', 'page')
 * @return {string} return.postStatus - Post status slug (e.g. 'draft', 'publish')
 * @return {number} return.blockCount - Number of top-level blocks in the editor
 * @return {string} return.title      - Current post title
 */
export function useEditorContext() {
	return useSelect( ( select ) => {
		const editor = select( editorStore );
		const postType = editor?.getCurrentPostType() ?? 'post';
		const postStatus = editor?.getEditedPostAttribute( 'status' ) ?? 'draft';
		const title = editor?.getEditedPostAttribute( 'title' ) ?? '';

		const blocks = select( blockEditorStore );
		const blockCount = blocks?.getBlockCount() ?? 0;

		// Derive context type.
		let type = 'blank';
		if ( postStatus === 'publish' ) {
			type = 'published';
		} else if ( blockCount > 0 || title.trim().length > 0 ) {
			type = 'has-content';
		}

		return { type, postType, postStatus, blockCount, title };
	}, [] );
}
