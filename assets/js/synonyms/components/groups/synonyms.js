/**
 * WordPress dependencies.
 */
import { safeHTML } from '@wordpress/dom';
import { RawHTML, WPElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { useSynonymsSettings } from '../../provider';
import VisualEditor from '../editors/visual-editor';

/**
 * Synonyms group component.
 *
 * @returns {WPElement}
 */
export default () => {
	const { synonyms } = useSynonymsSettings();

	return (
		<>
			<RawHTML>
				{safeHTML(
					__(
						'<p><strong>Synonyms</strong> are terms with similar meanings. For example, <em>sneaker</em>, <em>tennis shoe</em>, <em>trainer</em>, and <em>running shoe</em> could all refer to a particular type of shoe.</p>',
						'wpprobe',
					),
				)}
				{safeHTML(
					__(
						'<p>Use synonyms when you want queries for a specific term to also return results relevant to any of its synonyms. This can be useful for supporting products and services whose names have changed over time or regional variations in terminology. For example, when a search for "sneaker" should return sneakers, tennis shoes, trainers and running shoes.</p>',
						'wpprobe',
					),
				)}
			</RawHTML>
			<VisualEditor
				labels={{
					add: __('Add synonyms', 'wpprobe'),
					edit: __('Edit Synonyms', 'wpprobe'),
					new: __('Add Synonyms', 'wpprobe'),
					synonyms: __('Synonyms', 'wpprobe'),
				}}
				messages={{
					added: __('Added synonyms.', 'wpprobe'),
					deleted: __('Deleted synonyms.', 'wpprobe'),
					invalid: __('Synonym sets require at least two synonyms.', 'wpprobe'),
					updated: __('Updated synonyms.', 'wpprobe'),
				}}
				mode="synonyms"
				rules={synonyms}
			/>
		</>
	);
};
