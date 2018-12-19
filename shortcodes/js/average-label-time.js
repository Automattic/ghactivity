/**
 * External dependencies
 */
import React from 'react';
import { render } from 'react-dom';

/**
 * Internal dependencies
 */
import AverageLabelTime from './components/AverageLabelTime';
const { id } = ghactivity_avg_label_time;
const className = `avg-label-time-${id}`;

render((
	<AverageLabelTime
		id={ id }
	/>
), document.querySelector( `#avg-label-time.${className}` ) );
