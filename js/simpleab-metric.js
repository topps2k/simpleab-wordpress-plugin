document.addEventListener('DOMContentLoaded', function ()
{
    document.querySelectorAll('.simpleab-experiment [data-simpleab-metric]').forEach(function (element)
    {
        var metricName = element.dataset.simpleabMetric;
        var aggregationType = element.dataset.simpleabAggregation || 'sum';
        var metricValue = parseFloat(element.dataset.simpleabValue) || 1;
        var events = element.dataset.simpleabEvents ? element.dataset.simpleabEvents.split(',') : ['click'];

        // Find the nearest .simpleab-experiment parent
        var parentExperiment = element.closest('.simpleab-experiment');
        if (!parentExperiment)
        {
            console.warn('No parent .simpleab-experiment found for element:', element);
            return;
        }

        // Retrieve data attributes from the parent element
        var experimentId = parentExperiment.dataset.experimentId;
        var stage = parentExperiment.dataset.stage;
        var dimension = parentExperiment.dataset.dimension;
        var treatment = parentExperiment.dataset.treatment;

        // Set up event listeners for specified events
        events.forEach(function (eventType)
        {
            element.addEventListener(eventType.trim(), function ()
            {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', simpleab_data.ajax_url, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                // Send the metric tracking data along with the experiment data
                xhr.send('action=simpleab_track_metric' +
                    '&_wpnonce=' + simpleab_data.nonce +
                    '&experiment_id=' + encodeURIComponent(experimentId) +
                    '&stage=' + encodeURIComponent(stage) +
                    '&dimension=' + encodeURIComponent(dimension) +
                    '&treatment=' + encodeURIComponent(treatment) +
                    '&metric_name=' + encodeURIComponent(metricName) +
                    '&metric_value=' + metricValue +
                    '&aggregation_type=' + encodeURIComponent(aggregationType));
            });
        });
    });
});
