import { reactive, toRef } from 'vue';

export default function() {
    const errorMap = reactive({});

    const errorPlugin = (node) => {
        if (node.props.type == 'form' || node.props.type == 'group') {
            return;
        }

        // builds an object of the top-level groups
        errorMap[node.name] = errorMap[node.name] || {};

        node.on('created', () => {
        // use 'on created' to ensure context object is available
            errorMap[node.name].valid = toRef(node.context.state, 'valid');
        });

        // Store or update the count of blocking validation messages.
        node.on('count:blocking', ({ payload: count }) => {
            errorMap[node.name].blockingCount = count;
        });

        // Store or update the count of backend error messages.
        node.on('count:errors', ({ payload: count }) => {
            errorMap[node.name].errorCount = count;
        });
    };

    return { errorMap, errorPlugin };
}
