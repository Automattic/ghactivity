/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { Line } from 'react-chartjs-2';

class AverageLabelTime extends Component {
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
		const { repo, label } = this.props;
		const { api_url, api_nonce } = ghactivity_avg_label_time;
		return fetch(
			`${ api_url }ghactivity/v1/queries/average-label-time/repo/${ repo }/?label=${ encodeURI( label ) }`,
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

	render() {
		const { repo, label } = this.props;
		const { records } = this.state;
		const labels = [];
		const data = [];
		const issues = [];

		if ( !records || records.length === 0 ) {
			return (
				<div>
					Loading...
				</div>
			)
		}

		records[ records.length - 1	][2].forEach(issueSlug => {
			const [ repoName, issueNumber ] = issueSlug.split('#')
			const href = `https://github.com/${repoName}/pull/${issueNumber}`
			issues.push(
				<li>
					<a href={href} >#{issueNumber}</a>
				</li>
			)
		});
		records.forEach( r => {
			data.push( r[ 0 ] / 60 / 60 / 24 ); // Convert seconds into days
			labels.push( new Date( r[ 1 ] * 1000 ).toDateString() );
		} );
		const chartArgs = {
			labels,
			datasets: [
				{
					data,
					label: `${ repo } :: ${ label }`,
					fill: false,
					lineTension: 0.1,
					backgroundColor: 'rgba(75,192,192,0.4)',
					borderColor: 'rgba(75,192,192,1)',
					borderCapStyle: 'butt',
					borderDash: [],
					borderDashOffset: 0.0,
					borderJoinStyle: 'miter',
					pointBorderColor: 'rgba(75,192,192,1)',
					pointBackgroundColor: '#fff',
					pointBorderWidth: 1,
					pointHoverRadius: 5,
					pointHoverBackgroundColor: 'rgba(75,192,192,1)',
					pointHoverBorderColor: 'rgba(220,220,220,1)',
					pointHoverBorderWidth: 2,
					pointRadius: 1,
					pointHitRadius: 10,
				},
			],
		};
		return (
			<div>
				<Line data={ chartArgs } />
				<ul>
					{issues}
				</ul>
			</div>

		);
	}
}

AverageLabelTime.propTypes = {
	repo: PropTypes.string.isRequired,
	label: PropTypes.string.isRequired,
};

export default AverageLabelTime;
