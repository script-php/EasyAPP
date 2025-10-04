<?php

/**
 * Test Helper - Backward Compatibility Include
 * 
 * This file provides backward compatibility for existing test files.
 * The actual test bootstrap is now part of the framework in system/TestBootstrap.php
 * 
 * @package      EasyAPP Framework Tests
 * @author       EasyAPP Framework
 * @copyright    Copyright (c) 2022, script-php.ro
 * @deprecated   Use system/TestBootstrap.php directly for new tests
 */

// Include the framework test bootstrap
require_once __DIR__ . '/../system/TestBootstrap.php';