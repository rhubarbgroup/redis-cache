<?php

declare(strict_types=1);

const HHVM_VERSION = '0.0.0';
const WPINC = 'wp-includes';
const WP_CONTENT_DIR = './';
const WP_DEBUG_DISPLAY = true;
const WP_REDIS_PLUGIN_PATH = './';
const WP_REDIS_PLUGIN_DIR = './';
const WP_REDIS_FILE = 'plugin/plugin.php';
const WP_REDIS_BASENAME = 'plugin.php';
const WP_REDIS_DIR = './';
const WP_REDIS_VERSION = '0.0.0';
const WP_REDIS_PASSWORD = '';
const WP_REDIS_SERVERS = [
    'tcp://127.0.0.1:6379?database=5&alias=master',
    'tcp://127.0.0.2:6379?database=5&alias=replica-01',
];
const WP_REDIS_CLUSTER = [
    'tcp://127.0.0.1:6379?alias=node-01',
    'tcp://127.0.0.2:6379?alias=node-02',
    'tcp://127.0.0.3:6379?alias=node-03',
];
