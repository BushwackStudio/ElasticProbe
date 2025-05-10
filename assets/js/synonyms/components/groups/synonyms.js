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
						'elasticprobe',
					),
				)}
				{safeHTML(
					__(
						'<p>Use synonyms when you want queries for a specific term to also return results relevant to any of its synonyms. This can be useful for supporting products and services whose names have changed over time or regional variations in terminology. For example, when a search for "sneaker" should return sneakers, tennis shoes, trainers and running shoes.</p>',
						'elasticprobe',
					),
				)}
			</RawHTML>
			<VisualEditor
				labels={{
					add: __('Add synonyms', 'elasticprobe'),
					edit: __('Edit Synonyms', 'elasticprobe'),
					new: __('Add Synonyms', 'elasticprobe'),
					synonyms: __('Synonyms', 'elasticprobe'),
				}}
				messages={{
					added: __('Added synonyms.', 'elasticprobe'),
					deleted: __('Deleted synonyms.', 'elasticprobe'),
					invalid: __('Synonym sets require at least two synonyms.', 'elasticprobe'),
					updated: __('Updated synonyms.', 'elasticprobe'),
				}}
				mode="synonyms"
				rules={synonyms}
			/>
		</>
	);
};
