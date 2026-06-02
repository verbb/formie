import { Component } from 'react';

import { LargeErrorState } from './LargeErrorState.jsx';

class AppErrorBoundary extends Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError() {
        return { hasError: true };
    }

    componentDidCatch(error, info) {
        this.setState({ error });
        console.error(this.props.consoleLabel || 'React app crashed:', error, info);
    }

    render() {
        if (this.state.hasError) {
            const {
                title,
                message,
                detailsLabel,
                reloadLabel,
                containerClassName = 'flex flex-1 items-center justify-center py-12',
                contentClassName = 'flex flex-col items-center justify-center text-center',
            } = this.props;

            const { error } = this.state;

            return (
                <LargeErrorState
                    error={error}
                    title={title}
                    message={message}
                    detailsLabel={detailsLabel}
                    actionLabel={reloadLabel}
                    onAction={() => { return window.location.reload(); }}
                    containerClassName={containerClassName}
                    contentClassName={contentClassName}
                />
            );
        }

        return this.props.children;
    }
}

export { AppErrorBoundary };
