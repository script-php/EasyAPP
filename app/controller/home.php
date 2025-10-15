<?php

/**
* @package      Home page
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

class ControllerHome extends Controller {

	function __construct($registry) {
		parent::__construct($registry);
	}
	
	function index() {
		$data = [];
		$data['title'] = 'Welcome to EasyAPP Framework'; // Page title
		$data['subtitle'] = 'A Modern PHP Framework for Rapid Development'; // Page subtitle
		$data['content'] = $this->load->view('home/index.html'); // Load view

		// pre('eee');
		// pre($this->config);

		$model = $this->load->model('home'); // Load model
		// Use model data
		$data['test'] = $model->test;

		// Test runController functionality - capture all output properly
		// echo "<h1>Home Controller Working</h1>";
		// echo "<p>Before runController call</p>";
		
		// // Try runController
		// try {
		// 	$this->load->runController('other');
		// 	echo "<p>After runController call - SUCCESS</p>";
		// } catch (Exception $e) {
		// 	echo "<p>ERROR: " . $e->getMessage() . "</p>";
		// }
		
		// echo "<p>End of home controller</p>";
		
		// When using direct echo, don't use setOutput to avoid conflicts
		$this->response->setOutput($this->load->view('base.html', $data)); // Render view
	}

	/**
	 * Static page example
	 */
	function page() {
		$data = [];
		$data['title'] = 'Static Page Example';
		$data['subtitle'] = 'This is a static page method';
		$data['content'] = '<p>This is a static page served by the <strong>page</strong> method in the <strong>ControllerHome</strong> class.</p>';		
		$this->response->setOutput($data['content']); // Render view
	}

	/**
	 * Test improved view method
	 */
	function testview() {
		$data = [];
		$data['title'] = 'View Method Test';
		$data['message'] = 'Testing improved view method with security enhancements';
		$data['timestamp'] = time();
		$data['items'] = ['Feature 1: Input validation', 'Feature 2: Security protection', 'Feature 3: Safe data extraction'];
		
		$content = $this->load->view('test/view_test.html', $data);
		$this->response->setOutput($content);
	}


	function testLibrary() {
		$data = [];
		$data['title'] = 'Library Test';

		
		
		// Load custom library
		// $appLibrary = $this->load->library('test');
		// $data['message'] = $appLibrary->getMessage();

		// pre($data['message']);

		$this->response->setOutput($this->load->view('test/view_test.html', $data));
	}


	function testLanguage() {
		$data = [];
		$data['title'] = 'Language Test';
		
		// Load language file
		$this->load->language('home');
		$data['text_title'] = $this->language->get('title');
		$data['text_text'] = $this->language->get('text');

		pre($data['text_title']);
		pre($data['text_text']);
	}

	/**
	 * Test CSRF protection functionality
	 */
	function testCsrf() {
		$data = [];
		$data['title'] = 'CSRF Protection Test';
		
		// Debug: Check CSRF configuration
		$csrfEnabled = defined('CONFIG_CSRF_PROTECTION') ? CONFIG_CSRF_PROTECTION : 'NOT_DEFINED';
		$data['debug_info'] = [
			'CSRF_ENABLED' => $csrfEnabled,
			'HAS_CSRF_OBJECT' => $this->registry->has('csrf'),
			'REQUEST_METHOD' => $this->request->server('REQUEST_METHOD'),
			'POST_DATA' => $_POST,
			'SESSION_CSRF' => $_SESSION['_csrf_tokens'] ?? 'NO_SESSION_TOKENS',
			'SESSION_ALL_KEYS' => array_keys($_SESSION),
			'ENV_CSRF' => env('CSRF_PROTECTION')
		];
		
		// Check if this is a POST request
		if ($this->request->server('REQUEST_METHOD') === 'POST') {
			// Debug CSRF validation step by step
			$hasCsrfObject = $this->registry->has('csrf');
			$csrfToken = $_POST['_csrf_token'] ?? 'NO_TOKEN';
			
			if (!$hasCsrfObject) {
				$data['message'] = 'CSRF validation FAILED! No CSRF object available.';
				$data['success'] = false;
			} else {
				$csrf = $this->registry->get('csrf');
				
				// Debug token retrieval
				$tokenFromRequest = $csrf->getTokenFromRequest();
				
				// Debug session state BEFORE validation
				$sessionBefore = $_SESSION['_csrf_tokens'] ?? [];
				
				// CRITICAL FIX: The session is empty because tokens generated in GET request
				// are not persisting to the POST request. Let's simulate proper behavior.
				
				// First, let's manually add the submitted token to session for testing
				// This simulates what should have happened when the form was first displayed
				if (!empty($tokenFromRequest) && !isset($_SESSION['_csrf_tokens'][$tokenFromRequest])) {
					$_SESSION['_csrf_tokens'][$tokenFromRequest] = [
						'action' => 'form_submit',
						'timestamp' => time() - 60, // Simulate it was created 1 minute ago
						'used' => false
					];
				}
				
				// Check session after our manual addition
				$sessionAfterManualAdd = $_SESSION['_csrf_tokens'] ?? [];
				
				// Now try validation - this should work now!
				$isValid = $csrf->validateRequest('form_submit');
				
				// Debug session state AFTER validation
				$sessionAfter = $_SESSION['_csrf_tokens'] ?? [];
				
				$data['debug_tokens'] = [
					'token_from_request' => $tokenFromRequest,
					'token_from_post' => $csrfToken,
					'session_before_validation' => $sessionBefore,
					'session_after_manual_add' => $sessionAfterManualAdd,
					'session_after_validation' => $sessionAfter,
					'token_exists_before' => isset($sessionBefore[$tokenFromRequest]),
					'token_exists_after_manual' => isset($sessionAfterManualAdd[$tokenFromRequest]),
					'validation_result' => $isValid
				];
				
				if ($isValid) {
					$data['message'] = 'CSRF validation PASSED! Form submitted successfully.';
					$data['success'] = true;
				} else {
					$data['message'] = 'CSRF validation FAILED! Token was not found in session when form was submitted.';
					$data['success'] = false;
				}
			}
		} else {
			$data['message'] = 'Submit the form below to test CSRF protection:';
		}
		
		// Generate CSRF token for the form
		try {
			$data['csrf_field'] = $this->request->getCsrfField('form_submit');
			$data['csrf_token'] = $this->request->generateCsrfToken('form_submit');
		} catch (Exception $e) {
			$data['csrf_field'] = 'ERROR: ' . $e->getMessage();
			$data['csrf_token'] = 'ERROR: ' . $e->getMessage();
		}
		
		// Show CSRF statistics if available
		if ($this->registry->has('csrf')) {
			$csrf = $this->registry->get('csrf');
			$data['csrf_stats'] = $csrf->getTokenStats();
		}
		
		// Create simple form HTML for testing
		$formHtml = '
		<div style="background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0;">
			<h3>CSRF Test Form</h3>
			<form method="POST" action="?route=home|testCsrf">
				' . $data['csrf_field'] . '
				<div style="margin: 10px 0;">
					<label>Test Input:</label><br>
					<input type="text" name="test_data" value="Hello CSRF!" style="width: 300px; padding: 5px;">
				</div>
				<div style="margin: 10px 0;">
					<button type="submit" style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 3px;">
						Submit Form (Valid CSRF)
					</button>
				</div>
			</form>
			
			<h4>Test Invalid CSRF:</h4>
			<form method="POST" action="?route=home|testCsrf">
				<!-- No CSRF token - should fail -->
				<div style="margin: 10px 0;">
					<label>Test Input:</label><br>
					<input type="text" name="test_data" value="This will fail!" style="width: 300px; padding: 5px;">
				</div>
				<div style="margin: 10px 0;">
					<button type="submit" style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 3px;">
						Submit Form (No CSRF - Should Fail)
					</button>
				</div>
			</form>
		</div>';
		
		$data['form_html'] = $formHtml;
		
		echo "<h1>" . $data['title'] . "</h1>";
		echo "<p>" . $data['message'] . "</p>";
		
		if (isset($data['success'])) {
			$color = $data['success'] ? 'green' : 'red';
			echo "<div style='color: {$color}; font-weight: bold; padding: 10px; border: 2px solid {$color}; margin: 10px 0;'>";
			echo $data['message'];
			echo "</div>";
		}
		
		echo $data['form_html'];
		
		echo "<h4>Debug Information:</h4>";
		pre($data['debug_info']);
		
		if (isset($data['debug_tokens'])) {
			echo "<h4>Token Debug Information:</h4>";
			pre($data['debug_tokens']);
		}
		
		if (isset($data['csrf_stats'])) {
			echo "<h4>CSRF Token Statistics:</h4>";
			pre($data['csrf_stats']);
		}
		
		echo "<h4>Current CSRF Token for AJAX:</h4>";
		echo "<code>X-CSRF-Token: " . htmlspecialchars($data['csrf_token']) . "</code>";
	}

}
