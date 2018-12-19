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
		const { id } = this.props;
		const { api_url, api_nonce } = ghactivity_avg_label_time;
		return fetch(
			`${ api_url }ghactivity/v1/queries/average-label-time/?id=${ encodeURI( id ) }`,
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
		const { records } = this.state;
		const labels = [];
		const avgTimeData = [];
		const issuesNumData = [];
		const issues = [];

		if ( !records || records.length === 0 ) {
			return (
				<div>
					Loading...
				</div>
			)
		}

		const issueSlugs = Object.entries(records[ records.length - 1	][2]);
		issueSlugs.sort( (a, b) => b[1] - a[1] ) // Sort from older to newer
		issueSlugs.forEach( ( [issueSlug, time], id  ) => {
			const [ repoName, issueNumber ] = issueSlug.split('#')
			const href = `https://github.com/${repoName}/pull/${issueNumber}`
			const timeString = Math.round(time / 60 / 60 / 24) + ' days'
			issues.push(
				<li key={id} >
					<a href={href} >#{issueNumber}</a>  <span>{timeString}</span>
				</li>
			)
		});
		records.forEach( ( [avgTime, recordDate, recordedIssues] ) => {
			avgTimeData.push( Math.round( avgTime / 60 / 60 / 24 ) ); // Convert seconds into days
			labels.push( new Date( recordDate * 1000 ).toDateString() );
			issuesNumData.push( Object.values( recordedIssues ).length );
		} );
		const chartArgs = {
			labels,
			datasets: [
				{
					data: avgTimeData,
					yAxisID: 'A',
					label: 'Average time',
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
				{
					data: issuesNumData,
					yAxisID: 'B',
					label: 'Number of issues',
					fill: false,
					lineTension: 0.1,
					backgroundColor: 'rgba(75,43,192,0.4)',
					borderColor: 'rgba(75,42,192,1)',
					borderCapStyle: 'butt',
					borderDash: [],
					borderDashOffset: 0.0,
					borderJoinStyle: 'miter',
					pointBorderColor: 'rgba(75,43,192,1)',
					pointBackgroundColor: '#fff',
					pointBorderWidth: 1,
					pointHoverRadius: 5,
					pointHoverBackgroundColor: 'rgba(75,43,192,1)',
					pointHoverBorderColor: 'rgba(220,220,220,1)',
					pointHoverBorderWidth: 2,
					pointRadius: 1,
					pointHitRadius: 10,
				},
			],
		};

		const chartOpts = {
			scales: {
				yAxes: [{
					id: 'A',
					type: 'linear',
					position: 'left',
				}, {
					id: 'B',
					type: 'linear',
					position: 'right',
				}]
			}
		}

		return (
			<div>
				<Line data={ chartArgs } options={ chartOpts} />
				<ul>
					{issues}
				</ul>
			</div>

		);
	}
}

AverageLabelTime.propTypes = {
	id: PropTypes.string.isRequired,
};

export default AverageLabelTime;
