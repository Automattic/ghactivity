import React, { Component } from 'react';

class DataSetSection extends Component {
	render() {
		const {dataSet, name} = this.props
		let rows = [];

		Object.entries(dataSet).forEach( (set, idx) => {
			rows.push(
				<tr key={`th ${idx}`}>
					<th colSpan="1">
						{set[0]}
					</th>
				</tr>
			)
			Object.entries(set[1]).forEach( (stats, id) => {
				rows.push(
					<tr key={`${idx} ${id}`}>
						<td>{stats[0]}</td>
						<td>{stats[1]}</td>
					</tr>
				)
			})
		})

		return rows
	}
}

export default DataSetSection;
