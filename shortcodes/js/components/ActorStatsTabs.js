import React, { Component } from 'react';
import { Tab, Tabs } from 'react-bootstrap';

import ActivityTable from './ActivityTable'
import LoadingIndicator from './LoadingIndicator';

class ActorStatsTabs extends Component {
	render() {
		const dataSets = this.props.dataSets
		const tabs = []
		if (! dataSets) return <LoadingIndicator/>

		Object.entries(dataSets).forEach( (dataSet, idx) => {
			tabs.push(
				<Tab eventKey={idx} key={dataSet[0]} title={dataSet[0]} >
					<ActivityTable key={dataSet[0]} title={dataSet[0]} values={dataSet[1]} />
				</Tab>
			)
		})

		return(
		<Tabs id="actor-stats-tabs" defaultActiveKey="0">
			{tabs}
		</Tabs>
		)
	}
}

export default ActorStatsTabs;
