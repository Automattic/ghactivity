/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { Line } from 'react-chartjs-2';

class ProjectStats extends Component {
	constructor() {
		super();
		this.state = {
			records: [],
		};
	}

	componentDidMount() {
		this.fetchActivity();
	}

	fetchActivity() {
		const { org, project_name, api_url, api_nonce } = this.props;
		return fetch(
			`${ api_url }ghactivity/v1/queries/project-stats/org/${ org }/?project_name=${ encodeURI( project_name ) }`,
			{
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': api_nonce,
					'Content-type': 'application/json' },
			}
		)
			.then( data => data.json() )
			.then( data => {
				this.setState( { records: data.records } );
			} )
			.catch( err => {
				console.log( err );
				this.setState( { err } );
			} );
	}

	createChartData() {
		const { records } = this.state;
		const { columns } = this.props;
		const labels = [];
		const issues = [];
		const cardsList = {};
		let project_url = '';

		records.forEach( ( record, id ) => {
			labels.push( new Date( record.post_date * 1000 ).toDateString() );
			let recordColumns = Object.entries( record.columns );
			// if columns arg is passed in shortcode - use that array as a filter.
			if ( columns && columns.length > 0 ) {
				recordColumns = recordColumns.filter( column => columns.includes( column[0] ) );
			}
			recordColumns.forEach( ( [ columnName, cards ], idx ) => {
				if ( ! cardsList[ columnName ] ) {
					cardsList[ columnName ] = []
				}
				cardsList[ columnName ].push( cards.length );

				// Create list items for issues for latest record
				if ( id === records.length - 1 ) {
					project_url = record.project_url;
					cards.forEach( ( card, idxx ) => {
						if ( card.html_url ) {
							const issueNumber = card.html_url.split('/issues/')[1];

							issues.push(
								<li key={ id.toString() + idx.toString() + idxx.toString() } >
									<span>{columnName}</span> <a href={card.html_url} >#{issueNumber}</a>
								</li>
							);
						}
					} )
				}
			} )
		} )

		// Internal functions to create graph dataset object with random colors.
		const randomRGBColor = opacity => 'rgba(' + Math.floor(Math.random() * 255) + ',' + Math.floor(Math.random() * 255)+ ',' + Math.floor(Math.random() * 255) + ',' + opacity + ')'
		const createDatasetObject = (data, label) => ( {
			data,
			label,
			fill: false,
			backgroundColor: randomRGBColor(0.4),
			borderColor: randomRGBColor(1),
		} );

		const datasets = Object.entries( cardsList ).map( ( [ label, data ] ) => createDatasetObject( data, label ) );
		return { project_url, issues, labels, datasets };
	}

	render() {
		const { records } = this.state;
		const { org, project_name, api_url, api_nonce } = this.props;


		if ( !records || records.length === 0 ) {
			return (
				<div>
					Loading...
				</div>
			)
		}

		const { project_url, issues, labels, datasets } = this.createChartData()

		const chartArgs = {
			labels,
			datasets,
		};

		return (
			<div>
				<Line data={ chartArgs } />
				<br />
				<p>
					Project: <a href={ project_url }> { org + ' / ' + project_name }</a>
				</p>
				<p>List of open issues of specific project column</p>
				<ul>
					{issues}
				</ul>
			</div>

		);
	}
}

ProjectStats.propTypes = {
	org: PropTypes.string.isRequired,
	project_name: PropTypes.string.isRequired,
};

export default ProjectStats;
