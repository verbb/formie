import { useState, useEffect } from 'react';

import { Spinner } from '@verbb/plugin-kit-react/components';
import { takeAtLeast } from '@verbb/plugin-kit-react/utils';
import { useFormValues } from '@form-builder/hooks/useFormTools';
import { LargeErrorState, StatePanel } from '@utils';
import { QuestionnaireResultsBar } from '@form-builder/components/QuestionnaireResultsBar';

const QuestionCard = ({ question }) => {
    const hasResponses = (question.totalResponses ?? 0) > 0;
    const scoring = question.scoring;

    return (
        <section className="flex flex-col gap-2.5 rounded border border-gray-200 bg-white p-3">
            <div className="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
                <h4 className="text-sm font-semibold text-gray-900">{question.question?.label}</h4>
                <div className="flex flex-wrap items-baseline gap-x-3 gap-y-0.5 text-xs text-muted">
                    <p>
                        {Craft.t('formie', '{count} responses', {
                            count: question.totalResponses ?? 0,
                        })}
                    </p>
                    {scoring?.enabled ? (
                        <p>
                            {Craft.t('formie', 'Average score: {score} / {maxScore}', {
                                score: scoring.averageScore,
                                maxScore: scoring.maxScore,
                            })}
                        </p>
                    ) : null}
                </div>
            </div>

            {!hasResponses ? (
                <p className="text-xs text-muted">{Craft.t('formie', 'No responses for this question yet.')}</p>
            ) : (
                <div className="flex flex-col gap-2">
                    {(question.options ?? []).map((option) => {
                        return (
                            <QuestionnaireResultsBar
                                key={option.value}
                                option={option}
                            />
                        );
                    })}
                </div>
            )}
        </section>
    );
};

const QuizSummaryCard = ({ summary }) => {
    return (
        <section className="flex flex-col gap-2 rounded border border-gray-200 bg-white p-3">
            <h3 className="text-sm font-semibold text-gray-900">{Craft.t('formie', 'Quiz summary')}</h3>
            <dl className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                <div>
                    <dt className="text-xs text-muted">{Craft.t('formie', 'Attempts')}</dt>
                    <dd className="font-medium text-gray-900">{summary.attemptCount}</dd>
                </div>
                <div>
                    <dt className="text-xs text-muted">{Craft.t('formie', 'Average score')}</dt>
                    <dd className="font-medium text-gray-900">{summary.averagePercentage}%</dd>
                </div>
                <div>
                    <dt className="text-xs text-muted">{Craft.t('formie', 'Pass rate')}</dt>
                    <dd className="font-medium text-gray-900">{summary.passRate}%</dd>
                </div>
                <div>
                    <dt className="text-xs text-muted">{Craft.t('formie', 'Pass threshold')}</dt>
                    <dd className="font-medium text-gray-900">{summary.passPercentage}%</dd>
                </div>
            </dl>
        </section>
    );
};

const QuestionnaireResults = () => {
    const formValues = useFormValues();

    const [resultsData, setResultsData] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const loadResults = async () => {
        if (!formValues.id) {
            setResultsData(null);
            setLoading(false);
            setError(null);
            return;
        }

        setLoading(true);
        setError(null);

        try {
            const response = await takeAtLeast(300)(
                Craft.sendActionRequest('POST', 'formie/forms/get-questionnaire-results', {
                    data: {
                        formId: formValues.id,
                    },
                }),
            );

            if (response.data.error) {
                throw response.data.error;
            }

            setResultsData(response.data);
        } catch (loadError) {
            setError(loadError);
        }

        setLoading(false);
    };

    useEffect(() => {
        loadResults();
    }, [formValues.id]);

    if (!formValues.id) {
        return (
            <StatePanel
                variant="empty"
                title={Craft.t('formie', 'Save this form first')}
                message={Craft.t('formie', 'Response results will appear here once this form has been saved and submissions are received.')}
                containerClassName="p-8 text-center"
                contentClassName="flex w-[90%] max-w-[560px] flex-col items-center text-center mx-auto"
            />
        );
    }

    if (loading) {
        return (
            <div className="py-10 text-center">
                <Spinner size="lg" />
            </div>
        );
    }

    if (error) {
        return (
            <LargeErrorState
                error={error}
                message={Craft.t('formie', 'Unable to load questionnaire results.')}
                detailsLabel={Craft.t('formie', 'Show error details')}
                actionLabel={Craft.t('formie', 'Try again')}
                onAction={loadResults}
            />
        );
    }

    const questions = resultsData?.questions ?? [];

    if (questions.length === 0) {
        return (
            <StatePanel
                variant="empty"
                title={Craft.t('formie', 'No questions configured')}
                message={Craft.t('formie', 'Add Quiz or Survey fields on the Fields tab to start collecting responses.')}
                containerClassName="py-8 text-center"
                contentClassName="flex w-[90%] max-w-[560px] flex-col items-center text-center mx-auto"
            />
        );
    }

    const hasResponses = (resultsData?.totalResponses ?? 0) > 0;

    if (!hasResponses) {
        return (
            <StatePanel
                variant="empty"
                title={Craft.t('formie', 'No responses yet')}
                message={Craft.t('formie', 'Results will appear here once people submit this form.')}
                containerClassName="p-8 text-center"
                contentClassName="flex w-[90%] max-w-[560px] flex-col items-center text-center mx-auto"
            />
        );
    }

    return (
        <div className="questionnaire-results flex flex-col gap-2.5">
            {resultsData?.quizSummary ? (
                <QuizSummaryCard summary={resultsData.quizSummary} />
            ) : null}

            {questions.map((question) => {
                return (
                    <QuestionCard
                        key={question.question?.handle}
                        question={question}
                    />
                );
            })}
        </div>
    );
};

export { QuestionnaireResults };
