/**
 * External dependencies
 */
import React from 'react';
import { render } from 'react-dom';

/**
 * Internal dependencies
 */
import AverageLabelTime from './components/AverageLabelTime';
const { repo, label, id } = ghactivity_avg_label_time;
const className = `${repo}#${label}${id}`.toLowerCase().replace(/\W/gi,'-');

render((
	<AverageLabelTime
		repo={ repo }
		label={ label }
		id={ id }
	/>
), document.querySelector( `#avg-label-time.${className}` ) );
