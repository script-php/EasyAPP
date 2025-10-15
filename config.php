<?php

/**
 * Load environment variables from .env file
 * This framework uses a custom EnvReader class to load environment variables.
 * Make sure to create a .env file in the root directory of your project.
 * The .env file should contain key-value pairs in the format KEY=VALUE.
 * Example:
 *   DEBUG=true
 *   APP_ENV=dev
 * We can use arrays by separating values with commas.
 * Example:
 *   SERVICES=service1,service2,service3
 *   or
 *   SERVICES=["service1","service2","service3"]
 *   or
 *   SERVICES={"service1","service2","service3"}
 * 
 * 
 * This config can be overridden in app/config.php file.
 * For having different settings for app and framework.
 */


