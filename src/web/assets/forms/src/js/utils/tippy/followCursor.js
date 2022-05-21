export default {
    name: 'followCursor',
    defaultValue: false,
    fn(instance) {
        const { reference } = instance;

        return {
            onMount() {
                const event = instance.props.mouseEvent;
                const isCursorOverReference = event.target ? reference.contains(event.target) : true;
                const { clientX, clientY } = event;

                const rect = reference.getBoundingClientRect();
                const relativeX = clientX - rect.left;
                const relativeY = clientY - rect.top;

                if (isCursorOverReference || !instance.props.interactive) {
                    instance.setProps({
                        getReferenceClientRect() {
                            const rect = reference.getBoundingClientRect();

                            const x = rect.left + relativeX;
                            const y = rect.top + relativeY;

                            return {
                                width: 0,
                                height: 0,
                                top: y,
                                right: x,
                                bottom: y,
                                left: x,
                            };
                        },
                    });
                }
            },
        };
    },
};
