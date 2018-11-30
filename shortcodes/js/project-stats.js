/**
 * External dependencies
 */
import React from 'react';
import { render } from 'react-dom';

/**
 * Internal dependencies
 */
import ProjectStats from './components/ProjectStats';
const { org, project_name, columns, api_url, api_nonce } = ghactivity_project_stats;
const className = `${org}#${project_name}`.toLowerCase().replace(/\W/gi,'-');

render((
	<ProjectStats
		api_url={ api_url }
		api_nonce={ api_nonce }
		org={ org }
		project_name={ project_name }
		columns={ columns }
	/>
), document.querySelector( `#project-stats.${className}` ) );
