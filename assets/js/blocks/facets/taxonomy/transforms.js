/**
 * WordPress dependencies.
 */
import { createBlock } from '@wordpress/blocks';

/**
 * Filter by Taxonomy block transforms.
 */
export default {
	from: [
		{
			type: 'block',
			blocks: ['core/legacy-widget'],
			isMatch: ({ idBase }) => idBase === 'ep-facet',
			transform: ({ instance }) => {
				const { title = null, ...attributes } = instance.raw;

				if (!title) {
					return createBlock('elasticprobe/facet', attributes);
				}

				return [
					createBlock('core/heading', { content: title }),
					createBlock('elasticprobe/facet', attributes),
				];
			},
		},
	],
};
