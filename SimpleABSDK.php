<?php

namespace SimpleAB\SDK;

class BaseAPIUrls {
    const CAPTCHIFY_NA = 'https://api.captchify.com';
}

class FlushIntervals {
    const ONE_MINUTE = 60000;

    public static function isValid($type) {
        return in_array($type, [self::ONE_MINUTE]);
    }
}

class AggregationTypes {
    const SUM = 'sum';
    const AVERAGE = 'average';
    const PERCENTILE = 'percentile';

    public static function isValid($type) {
        return in_array($type, [self::SUM, self::AVERAGE, self::PERCENTILE]);
    }
}

class Treatments {
    const NONE = '';
    const CONTROL = 'C';
    public static $TREATMENTS;

    public static function init() {
        self::$TREATMENTS = array_map(function($i) { return "T" . ($i + 1); }, range(0, 254));
    }

    public static function isValid($type) {
        return in_array($type, array_merge([self::NONE, self::CONTROL], self::$TREATMENTS));
    }
}

Treatments::init();

class Stages {
    const BETA = 'Beta';
    const PROD = 'Prod';

    public static function isValid($type) {
        return in_array($type, [self::BETA, self::PROD]);
    }
}

class Segment {
    public $countryCode;
    public $region;
    public $deviceType;

    public function __construct($countryCode = '', $region = '', $deviceType = '') {
        $this->countryCode = $countryCode;
        $this->region = $region;
        $this->deviceType = $deviceType;
    }

    public static function fromJSON($json) {
        return new Segment($json['countryCode'], $json['region'], $json['deviceType']);
    }

    public function toJSON() {
        return [
            'countryCode' => $this->countryCode,
            'region' => $this->region,
            'deviceType' => $this->deviceType
        ];
    }
}

class SimpleABSDK {
    private $apiURL;
    private $apiKey;
    private $experiments;
    private $cache;
    private $buffer;
    private $flushInterval;
    private $cacheRefreshInterval;
    private $bufferFlushInterval;

    public function __construct($apiURL, $apiKey, $experiments = []) {
        $this->apiURL = $apiURL;
        $this->apiKey = $apiKey;
        $this->experiments = $experiments;
        $this->cache = [];
        $this->buffer = [];
        $this->flushInterval = FlushIntervals::ONE_MINUTE;

        if (!empty($experiments)) {
            $this->loadExperiments($experiments);
        }
    }

    public function getTreatment($experimentID, $stage, $dimension, $allocationKey) {
        try {
            $exp = $this->getExperiment($experimentID);

            // Check for overrides
            $override = $this->checkForOverride($exp, $stage, $dimension, $allocationKey);
            if ($override !== null) {
                return $override;
            }

            $stageData = $this->findStage($exp, $stage);
            $stageDimension = $this->findStageDimension($stageData, $dimension);

            if (!$stageDimension['enabled']) {
                return '';
            }

            $allocationHash = $this->calculateHash($allocationKey . $exp['allocationRandomizationToken']);
            $exposureHash = $this->calculateHash($allocationKey . $exp['exposureRandomizationToken']);

            if (!$this->isInExposureBucket($exposureHash, $stageDimension['exposure'])) {
                return '';
            }

            $treatment = $this->determineTreatment($allocationHash, $stageDimension['treatmentAllocations']);
            return $treatment ?? '';
        } catch (Exception $e) {
            return '';
        }
    }

    private function checkForOverride($experiment, $stage, $dimension, $allocationKey) {
        if (!isset($experiment['overrides'])) {
            return null;
        }

        foreach ($experiment['overrides'] as $override) {
            if ($override['allocationKey'] === $allocationKey) {
                foreach ($override['stageOverrides'] as $stageOverride) {
                    if ($stageOverride['stage'] === $stage &&
                        $stageOverride['enabled'] &&
                        in_array($dimension, $stageOverride['dimensions'])) {
                        return $stageOverride['treatment'];
                    }
                }
            }
        }

        return null;
    }

    private function getExperiment($experimentID) {
        if (isset($this->cache[$experimentID])) {
            return $this->cache[$experimentID];
        }

        $this->loadExperiments([$experimentID]);

        if (!isset($this->cache[$experimentID])) {
            throw new \Exception(esc_html("Experiment {$experimentID} not found"));
        }

        return $this->cache[$experimentID];
    }

    private function loadExperiments($experimentIDs) {
        $batchSize = 50;
        for ($i = 0; $i < count($experimentIDs); $i += $batchSize) {
            $batch = array_slice($experimentIDs, $i, $batchSize);
            $response = $this->makeApiRequest('POST', '/experiments/batch/list', ['ids' => $batch]);

            foreach ($response['success'] as $exp) {
                $this->cache[$exp['id']] = $exp;
            }
        }
    }

    private function startCacheRefresh() {
        $this->cacheRefreshInterval = 600; // 10 minutes
        register_shutdown_function([$this, 'close']);
    }

    public function close() {
        // Add any cleanup logic here
    }

    private function refreshCache() {
        try {
            $cacheKeys = array_keys($this->cache);
            $this->loadExperiments($cacheKeys);
        } catch (Exception $e) {
            error_log('Error refreshing experiments: ' . $e->getMessage());
        }
    }

    private function calculateHash($input) {
        return hash('md5', $input);
    }

    private function isInExposureBucket($hash, $exposure) {
        if (!isset($exposure)) {
            return false;
        }
        $hashInt = hexdec(substr($hash, 0, 8));
        if ($hashInt === 0xffffffff && $exposure === 100) {
            return true;
        }
        if ($hashInt === 0x00000000 && $exposure === 0) {
            return false;
        }
        return ($hashInt / 0xffffffff) < ($exposure / 100);
    }

    private function determineTreatment($hash, $treatmentAllocations) {
        if (!is_array($treatmentAllocations) || empty($treatmentAllocations)) {
            return '';
        }

        $hashFloat = hexdec(substr($hash, 0, 8)) / 0xffffffff;
        $cumulativeProbability = 0;

        foreach ($treatmentAllocations as $allocation) {
            if (!isset($allocation['allocation']) || !isset($allocation['id'])) {
                continue;
            }
            $cumulativeProbability += $allocation['allocation'] / 100;
            if ($hashFloat < $cumulativeProbability) {
                return $allocation['id'];
            }
        }

        return '';
    }

    public function trackMetric($params) {
        $experimentID = $params['experimentID'];
        $stage = $params['stage'];
        $dimension = $params['dimension'];
        $treatment = $params['treatment'];
        $metricName = $params['metricName'];
        $metricValue = $params['metricValue'];
        $aggregationType = $params['aggregationType'] ?? AggregationTypes::SUM;

        if (!Treatments::isValid($treatment)) {
            throw new \Exception('Invalid treatment string');
        }

        if (!Stages::isValid($stage)) {
            throw new \Exception('Invalid stage string');
        }

        if (!AggregationTypes::isValid($aggregationType)) {
            throw new \Exception(esc_html("Invalid aggregation type: {$aggregationType}"));
        }

        if ($treatment === '') {
            return;
        }

        $experiment = $this->getExperiment($experimentID);
        $stageData = $this->findStage($experiment, $stage);
        $stageDimension = $this->findStageDimension($stageData, $dimension);
        $treatmentData = $this->findTreatment($experiment, $treatment);

        $key = "{$experimentID}|{$stage}|{$dimension}|{$treatment}|{$metricName}|{$aggregationType}";

        if (!isset($this->buffer[$key])) {
            $this->buffer[$key] = ['sum' => 0, 'count' => 0, 'values' => []];
        }

        if ($aggregationType === AggregationTypes::SUM && $metricValue < 0) {
            throw new \Exception(esc_html("Metric {$metricName} cannot be negative for AggregationTypes::SUM"));
        }

        $this->buffer[$key]['sum'] += $metricValue;
        $this->buffer[$key]['count'] += 1;

        if ($aggregationType === AggregationTypes::PERCENTILE) {
            $this->buffer[$key]['values'][] = $metricValue;
        }
    }

    private function startBufferFlush() {
        $this->bufferFlushInterval = $this->flushInterval / 1000; // Convert to seconds
        register_shutdown_function([$this, 'flushMetrics']);
    }

    private function calculatePercentiles($values, $percentiles) {
        if (empty($values)) {
            return [];
        }

        sort($values);
        $results = [];

        foreach ($percentiles as $percentile) {
            $idx = ceil(($percentile / 100) * count($values)) - 1;
            $results[$percentile] = $values[$idx];
        }

        return $results;
    }

    public function flushMetrics() {
        $metricsBatch = [];

        foreach ($this->buffer as $key => $value) {
            list($experimentID, $stage, $dimension, $treatment, $metricName, $aggregationType) = explode('|', $key);

            if ($aggregationType === AggregationTypes::AVERAGE) {
                $metricValue = $value['sum'] / $value['count'];
            } elseif ($aggregationType === AggregationTypes::PERCENTILE) {
                $percentiles = $this->calculatePercentiles($value['values'], [50, 90, 99]);
                $metricsBatch[] = [
                    'experimentID' => $experimentID,
                    'stage' => $stage,
                    'dimension' => $dimension,
                    'treatment' => $treatment,
                    'metricName' => $metricName,
                    'aggregationType' => $aggregationType,
                    'p50' => $percentiles[50],
                    'p90' => $percentiles[90],
                    'p99' => $percentiles[99],
                    'count' => $value['count']
                ];
                continue;
            } else {
                $metricValue = $value['sum'];
            }

            $metricsBatch[] = [
                'experimentID' => $experimentID,
                'stage' => $stage,
                'dimension' => $dimension,
                'treatment' => $treatment,
                'metricName' => $metricName,
                'aggregationType' => $aggregationType,
                'value' => $metricValue,
                'count' => $value['count']
            ];
        }

        $batchSize = 150;
        $batches = array_chunk($metricsBatch, $batchSize);

        foreach ($batches as $batch) {
            try {
                $this->makeApiRequest('POST', '/metrics/track/batch', ['metrics' => $batch]);
            } catch (Exception $e) {
                error_log('Error sending metrics batch: ' . $e->getMessage());
            }
        }

        $this->buffer = [];
    }

    public function flush() {
        $this->flushMetrics();
    }

    protected function makeApiRequest($method, $endpoint, $data = null) {
        $url = $this->apiURL . $endpoint;
        $args = [
            'method' => $method,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->apiKey
            ],
            'timeout' => 30
        ];

        if ($data !== null) {
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            throw new \Exception("API request failed: " . esc_html($response->get_error_message()));
        }

        $body = wp_remote_retrieve_body($response);
        $decoded_response = json_decode($body, true);

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            throw new \Exception(esc_html("API request failed with status code: {$status_code}"));
        }

        return $decoded_response;
    }

    private function findStage($experiment, $stage) {
        foreach ($experiment['stages'] as $stageData) {
            if ($stageData['stage'] === $stage) {
                return $stageData;
            }
        }
        throw new \Exception(esc_html("Stage {$stage} not found for experiment {$experiment['id']}"));
    }

    private function findStageDimension($stageData, $dimension) {
        foreach ($stageData['stageDimensions'] as $stageDimension) {
            if ($stageDimension['dimension'] === $dimension) {
                return $stageDimension;
            }
        }
        throw new \Exception(esc_html("Dimension {$dimension} not found for stage {$stageData['stage']}"));
    }

    private function findTreatment($experiment, $treatment) {
        foreach ($experiment['treatments'] as $treatmentData) {
            if ($treatmentData['id'] === $treatment) {
                return $treatmentData;
            }
        }
        throw new \Exception(esc_html("Treatment {$treatment} not found in experiment {$experiment['id']}"));
    }

    public function getCache() {
        return $this->cache;
    }

    public function getBuffer() {
        return $this->buffer;
    }

    public function getSegment($options = []) {
        try {
            $response = $this->makeApiRequest('POST', '/segment', [
                'ip' => $options['ip'] ?? null,
                'userAgent' => $options['userAgent'] ?? null
            ]);

            return Segment::fromJSON($response);
        } catch (Exception $e) {
            error_log('Error getting segment: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getTreatmentWithSegment($experimentID, $stage, $segment, $allocationKey) {
        $exp = $this->getExperiment($experimentID);
        $dimension = $this->_getDimensionFromSegment($exp, $stage, $segment);
        if ($dimension === '') {
            return '';
        }
        return $this->getTreatment($experimentID, $stage, $dimension, $allocationKey);
    }

    public function trackMetricWithSegment($params) {
        $experimentID = $params['experimentID'];
        $stage = $params['stage'];
        $segment = $params['segment'];
        $treatment = $params['treatment'];
        $metricName = $params['metricName'];
        $metricValue = $params['metricValue'];
        $aggregationType = $params['aggregationType'] ?? AggregationTypes::SUM;

        $exp = $this->getExperiment($experimentID);
        $dimension = $this->_getDimensionFromSegment($exp, $stage, $segment);
        if ($dimension === '') {
            return;
        }

        return $this->trackMetric([
            'experimentID' => $experimentID,
            'stage' => $stage,
            'dimension' => $dimension,
            'treatment' => $treatment,
            'metricName' => $metricName,
            'metricValue' => $metricValue,
            'aggregationType' => $aggregationType
        ]);
    }

    private function _getDimensionFromSegment($experiment, $stage, $segment) {
        $stageData = $this->findStage($experiment, $stage);
        $dimensions = array_filter(array_map(function($sd) {
            return $sd['enabled'] ? $sd['dimension'] : null;
        }, $stageData['stageDimensions']));

        $exactMatches = [
            "{$segment->countryCode}-{$segment->deviceType}",
            "{$segment->countryCode}-all",
            "{$segment->region}-{$segment->deviceType}",
            "{$segment->region}-all",
            "GLO-{$segment->deviceType}",
            'GLO-all'
        ];

        foreach ($exactMatches as $match) {
            if (in_array($match, $dimensions)) {
                return $match;
            }
        }

        return '';
    }
}