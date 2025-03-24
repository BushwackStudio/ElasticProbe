/**
 * WordPress dependencies.
 */
import { safeHTML } from '@wordpress/dom';
import { createInterpolateElement, RawHTML, WPElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { useSynonymsSettings } from '../../provider';
import VisualEditor from '../editors/visual-editor';

/**
 * Replacements group component.
 *
 * @returns {WPElement}
 */
export default () => {
	const { replacements } = useSynonymsSettings();

	return (
		<>
			<RawHTML>
				{safeHTML(
					__(
						'<p><strong>Replacements</strong> are terms that replace other incorrect or obsolete terms.</p>',
						'wpprobe',
					),
				)}
				{safeHTML(
					__(
						'<p>Use replacements when you want search queries for certain terms to return results that are only relevant to another term, or set of terms. This can be useful for supporting specific typos or incorrect phrasing. For example, when a search for the phrase "intensive purposes" should only return results including the phrase "intents and purposes".</p>',
						'wpprobe',
					),
				)}
			</RawHTML>
			<p>
				{createInterpolateElement(
					__(
						'You may need to <a>disable fuzziness</a> to have it working properly.',
						'wpprobe',
					),
					{
						a: (
							// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
							<a
								target="_blank"
								href="https://www.elasticpress.io/documentation/article/how-to-disable-fuzziness/"
								rel="noreferrer"
							/>
						),
					},
				)}
			</p>
			<VisualEditor
				labels={{
					add: __('Add replacements', 'wpprobe'),
					edit: __('Edit Replacements', 'wpprobe'),
					new: __('Add Replacements', 'wpprobe'),
					primary: __('Terms', 'replacements'),
					synonyms: __('Replacements', 'wpprobe'),
				}}
				messages={{
					added: __('Added replacements.', 'wpprobe'),
					deleted: __('Deleted replacements.', 'wpprobe'),
					invalid: __(
						'Replacement sets require at least one term and one replacement.',
						'wpprobe',
					),
					updated: __('Updated replacements.', 'wpprobe'),
				}}
				mode="replacements"
				rules={replacements}
			/>
		</>
	);
};
