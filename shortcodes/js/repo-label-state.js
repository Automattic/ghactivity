/**
 * External dependencies
 */
import React from 'react';
import { render } from 'react-dom';

/**
 * Internal dependencies
 */
import RepoLabelState from './components/RepoLabelState';
const { repo, api_url, api_nonce } = ghactivity_repo_label_state;
const className = `${repo}`.toLowerCase().replace( /\W/gi,'-' );

render(  (
	<RepoLabelState
		repo={ repo }
		api_url={ api_url }
		api_nonce={ api_nonce }
	/>
), document.querySelector( `#repo-label-state.${className}` ) );
