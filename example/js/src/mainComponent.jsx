//import React from 'react';
//import ChildComponent from './childComponent';

class MainComponent extends React.Component {

	static defaultProps = {
		message: 'Hello, world!'
	};

	constructor(props) {
		super(props);

		this.state = {
			message: this.props.message
		};
	}

	_update = (message) => {
		//this.state.message = message;
		this.setState({ message });
	};


	/*ChildComponent message={this.state.message} onUpdate={this._update}/*/
	render() {
		return (
			<div>
				<h1>{this.props.title}</h1>
			</div>
		);
	}

}


export default MainComponent;