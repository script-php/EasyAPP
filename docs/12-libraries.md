# Libraries

Libraries are reusable components that provide utility functions and third-party integrations. Unlike services which contain business logic, libraries are general-purpose tools.

---

## Table of Contents

1. [Introduction](#introduction)
2. [Creating Libraries](#creating-libraries)
3. [Loading Libraries](#loading-libraries)
4. [Built-in Libraries](#built-in-libraries)
5. [Custom Libraries](#custom-libraries)
6. [Best Practices](#best-practices)

---

## Introduction

Libraries in EasyAPP are utility classes that can be loaded on-demand throughout your application. They differ from services:

- **Services**: Business logic specific to your application
- **Libraries**: General-purpose utilities and third-party wrappers

### Library Location

Libraries can be stored in two locations:

```
system/Library/          # Framework libraries (don't modify)
app/library/            # Your custom libraries
```

---

## Creating Libraries

### Basic Library Structure

All libraries extend the `Library` base class:

**File:** `app/library/Pagination.php`

```php
<?php

class LibraryPagination extends Library {
    
    private $total;
    private $page;
    private $limit;
    private $url;
    
    /**
     * Initialize pagination
     */
    public function initialize($total, $page, $limit, $url) {
        $this->total = (int)$total;
        $this->page = max(1, (int)$page);
        $this->limit = max(1, (int)$limit);
        $this->url = $url;
    }
    
    /**
     * Get total pages
     */
    public function getTotalPages() {
        return ceil($this->total / $this->limit);
    }
    
    /**
     * Get offset for SQL query
     */
    public function getOffset() {
        return ($this->page - 1) * $this->limit;
    }
    
    /**
     * Get limit for SQL query
     */
    public function getLimit() {
        return $this->limit;
    }
    
    /**
     * Render pagination HTML
     */
    public function render() {
        $totalPages = $this->getTotalPages();
        
        if ($totalPages <= 1) {
            return '';
        }
        
        $html = '<div class="pagination">';
        
        // Previous button
        if ($this->page > 1) {
            $html .= '<a href="' . $this->getUrl($this->page - 1) . '" class="prev">&laquo; Previous</a>';
        }
        
        // Page numbers
        $start = max(1, $this->page - 2);
        $end = min($totalPages, $this->page + 2);
        
        if ($start > 1) {
            $html .= '<a href="' . $this->getUrl(1) . '">1</a>';
            if ($start > 2) {
                $html .= '<span class="dots">...</span>';
            }
        }
        
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $this->page) {
                $html .= '<span class="current">' . $i . '</span>';
            } else {
                $html .= '<a href="' . $this->getUrl($i) . '">' . $i . '</a>';
            }
        }
        
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $html .= '<span class="dots">...</span>';
            }
            $html .= '<a href="' . $this->getUrl($totalPages) . '">' . $totalPages . '</a>';
        }
        
        // Next button
        if ($this->page < $totalPages) {
            $html .= '<a href="' . $this->getUrl($this->page + 1) . '" class="next">Next &raquo;</a>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate URL for page
     */
    private function getUrl($page) {
        $separator = strpos($this->url, '?') !== false ? '&' : '?';
        return $this->url . $separator . 'page=' . $page;
    }
}
```

### Naming Convention

Library class names follow the pattern: `Library[Name]`

```php
LibraryPagination    // app/library/Pagination.php
LibraryUpload        // app/library/Upload.php
LibraryPdf          // app/library/Pdf.php
```

When loading: Use filename without extension

```php
$this->load->library('Pagination');  // Loads LibraryPagination
$this->load->library('Upload');       // Loads LibraryUpload
```

---

## Loading Libraries

### From Controllers

```php
class ControllerProduct extends Controller {
    
    public function index() {
        // Get page from query string
        $page = $this->request->get['page'] ?? 1;
        $limit = 20;
        
        // Get total products
        $total = $this->load->model('product')->getTotal();
        
        // Load pagination library
        $this->load->library('Pagination');
        $this->Pagination->initialize($total, $page, $limit, '/product/index');
        
        // Get products for current page
        $data['products'] = $this->load->model('product')->getAll(
            $this->Pagination->getLimit(),
            $this->Pagination->getOffset()
        );
        
        // Render pagination
        $data['pagination'] = $this->Pagination->render();
        
        $this->response->setOutput($this->load->view('product/list.html', $data));
    }
}
```

### From Models

```php
class ModelProduct extends Model {
    
    public function exportToCsv() {
        // Load CSV library
        $this->load->library('Csv');
        
        // Get data
        $products = $this->getAll();
        
        // Generate CSV
        $this->Csv->setHeaders(['ID', 'Name', 'Price', 'Stock']);
        
        foreach ($products as $product) {
            $this->Csv->addRow([
                $product['id'],
                $product['name'],
                $product['price'],
                $product['stock']
            ]);
        }
        
        return $this->Csv->generate();
    }
}
```

### From Services

```php
class ServiceReportService extends Service {
    
    public function generatePdfReport($data) {
        // Load PDF library
        $this->load->library('Pdf');
        
        // Generate HTML
        $html = $this->load->view('reports/template.html', $data);
        
        // Convert to PDF
        $this->Pdf->loadHtml($html);
        return $this->Pdf->output();
    }
}
```

---

## Built-in Libraries

### Cache

Access via `$this->cache`:

```php
// Set cache
$this->cache->set('key', $value, 3600);

// Get cache
$value = $this->cache->get('key');

// Delete cache
$this->cache->delete('key');

// Clear all cache
$this->cache->clear();
```

### Database (Db)

Access via `$this->db`:

```php
// Execute query
$this->db->query("SELECT * FROM users WHERE id = ?", [1]);

// Get single row
$user = $this->db->row;

// Get all rows
$users = $this->db->rows;

// Get affected rows
$affected = $this->db->countAffected();
```

### Request

Access via `$this->request`:

```php
// GET data
$id = $this->request->get['id'];

// POST data
$name = $this->request->post['name'];

// Server info
$method = $this->request->server['REQUEST_METHOD'];

// Cookies
$token = $this->request->cookie['auth_token'];

// Session
$userId = $this->request->session['user_id'];
```

### Response

Access via `$this->response`:

```php
// Set output
$this->response->setOutput($html);

// Redirect
$this->response->redirect('/path');

// Set header
$this->response->addHeader('Content-Type: application/json');

// JSON response
$this->response->json(['status' => 'success']);
```

### Logger

Access via `$this->logger`:

```php
// Log levels
$this->logger->emergency($message);
$this->logger->alert($message);
$this->logger->critical($message);
$this->logger->error($message);
$this->logger->warning($message);
$this->logger->notice($message);
$this->logger->info($message);
$this->logger->debug($message);

// Log with context
$this->logger->error('User not found', ['user_id' => 123]);
```

### Mail

Access via `$this->mail`:

```php
// Send email
$this->mail->send($to, $subject, $body);

// Send with additional options
$this->mail->setFrom('noreply@example.com', 'My App');
$this->mail->setReplyTo('support@example.com');
$this->mail->send($to, $subject, $body);
```

---

## Custom Libraries

### File Upload Library

**File:** `app/library/Upload.php`

```php
<?php

class LibraryUpload extends Library {
    
    private $uploadPath = 'storage/uploads/';
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    private $maxSize = 5242880; // 5MB
    private $errors = [];
    
    /**
     * Set upload path
     */
    public function setUploadPath($path) {
        $this->uploadPath = rtrim($path, '/') . '/';
        return $this;
    }
    
    /**
     * Set allowed extensions
     */
    public function setAllowedExtensions($extensions) {
        $this->allowedExtensions = $extensions;
        return $this;
    }
    
    /**
     * Set max file size
     */
    public function setMaxSize($bytes) {
        $this->maxSize = $bytes;
        return $this;
    }
    
    /**
     * Upload file
     */
    public function upload($file, $newName = null) {
        $this->errors = [];
        
        // Validate file
        if (!$this->validate($file)) {
            return false;
        }
        
        // Generate filename
        if ($newName === null) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newName = uniqid() . '_' . time() . '.' . $extension;
        }
        
        $destination = $this->uploadPath . $newName;
        
        // Create directory if it doesn't exist
        $directory = dirname($destination);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $newName;
        } else {
            $this->errors[] = 'Failed to move uploaded file';
            return false;
        }
    }
    
    /**
     * Validate file
     */
    private function validate($file) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $this->errors[] = 'No file uploaded';
            return false;
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = 'Upload error: ' . $this->getUploadError($file['error']);
            return false;
        }
        
        // Check file size
        if ($file['size'] > $this->maxSize) {
            $this->errors[] = 'File too large. Maximum size: ' . $this->formatBytes($this->maxSize);
            return false;
        }
        
        // Check extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->errors[] = 'Invalid file type. Allowed: ' . implode(', ', $this->allowedExtensions);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get upload errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get upload error message
     */
    private function getUploadError($code) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        return $errors[$code] ?? 'Unknown upload error';
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

**Usage:**

```php
public function upload() {
    if (!empty($_FILES['file'])) {
        // Load upload library
        $this->load->library('Upload');
        
        // Configure
        $this->Upload
            ->setUploadPath('storage/uploads/images/')
            ->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif'])
            ->setMaxSize(2097152); // 2MB
        
        // Upload
        $filename = $this->Upload->upload($_FILES['file']);
        
        if ($filename) {
            // Success
            $this->response->json([
                'status' => 'success',
                'filename' => $filename
            ]);
        } else {
            // Error
            $this->response->json([
                'status' => 'error',
                'errors' => $this->Upload->getErrors()
            ]);
        }
    }
}
```

### CSV Export Library

**File:** `app/library/Csv.php`

```php
<?php

class LibraryCsv extends Library {
    
    private $headers = [];
    private $rows = [];
    private $delimiter = ',';
    private $enclosure = '"';
    
    /**
     * Set CSV headers
     */
    public function setHeaders($headers) {
        $this->headers = $headers;
        return $this;
    }
    
    /**
     * Add a row
     */
    public function addRow($row) {
        $this->rows[] = $row;
        return $this;
    }
    
    /**
     * Add multiple rows
     */
    public function addRows($rows) {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
        return $this;
    }
    
    /**
     * Set delimiter
     */
    public function setDelimiter($delimiter) {
        $this->delimiter = $delimiter;
        return $this;
    }
    
    /**
     * Generate CSV content
     */
    public function generate() {
        $output = '';
        
        // Add headers
        if (!empty($this->headers)) {
            $output .= $this->formatRow($this->headers);
        }
        
        // Add rows
        foreach ($this->rows as $row) {
            $output .= $this->formatRow($row);
        }
        
        return $output;
    }
    
    /**
     * Download CSV file
     */
    public function download($filename = 'export.csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $this->generate();
        exit;
    }
    
    /**
     * Save CSV to file
     */
    public function save($filepath) {
        return file_put_contents($filepath, $this->generate());
    }
    
    /**
     * Format row for CSV
     */
    private function formatRow($row) {
        $escaped = array_map(function($field) {
            return $this->enclosure . str_replace($this->enclosure, $this->enclosure . $this->enclosure, $field) . $this->enclosure;
        }, $row);
        
        return implode($this->delimiter, $escaped) . "\n";
    }
    
    /**
     * Clear data
     */
    public function clear() {
        $this->headers = [];
        $this->rows = [];
        return $this;
    }
}
```

**Usage:**

```php
public function export() {
    // Load library
    $this->load->library('Csv');
    
    // Get data
    $users = $this->load->model('user')->getAll();
    
    // Configure CSV
    $this->Csv->setHeaders(['ID', 'Name', 'Email', 'Created']);
    
    // Add rows
    foreach ($users as $user) {
        $this->Csv->addRow([
            $user['id'],
            $user['name'],
            $user['email'],
            $user['created_at']
        ]);
    }
    
    // Download
    $this->Csv->download('users_' . date('Y-m-d') . '.csv');
}
```

### PDF Generator Library

**File:** `app/library/Pdf.php`

```php
<?php

// Requires: composer require dompdf/dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

class LibraryPdf extends Library {
    
    private $dompdf;
    private $html = '';
    
    public function __construct($registry) {
        parent::__construct($registry);
        
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $this->dompdf = new Dompdf($options);
    }
    
    /**
     * Load HTML content
     */
    public function loadHtml($html) {
        $this->html = $html;
        return $this;
    }
    
    /**
     * Load HTML from view
     */
    public function loadView($view, $data = []) {
        $this->html = $this->load->view($view, $data);
        return $this;
    }
    
    /**
     * Set paper size
     */
    public function setPaper($size = 'A4', $orientation = 'portrait') {
        $this->dompdf->setPaper($size, $orientation);
        return $this;
    }
    
    /**
     * Generate PDF
     */
    public function render() {
        $this->dompdf->loadHtml($this->html);
        $this->dompdf->render();
        return $this;
    }
    
    /**
     * Output PDF
     */
    public function output() {
        return $this->dompdf->output();
    }
    
    /**
     * Stream PDF to browser
     */
    public function stream($filename = 'document.pdf') {
        $this->render();
        $this->dompdf->stream($filename);
    }
    
    /**
     * Download PDF
     */
    public function download($filename = 'document.pdf') {
        $this->render();
        $this->dompdf->stream($filename, ['Attachment' => true]);
    }
    
    /**
     * Save PDF to file
     */
    public function save($filepath) {
        $this->render();
        return file_put_contents($filepath, $this->output());
    }
}
```

**Usage:**

```php
public function invoice() {
    $orderId = $this->request->get['order_id'];
    
    // Get order data
    $data['order'] = $this->load->model('order')->getById($orderId);
    $data['items'] = $this->load->model('order')->getItems($orderId);
    
    // Load PDF library
    $this->load->library('Pdf');
    
    // Generate PDF
    $this->Pdf
        ->loadView('invoice/template.html', $data)
        ->setPaper('A4', 'portrait')
        ->download('invoice_' . $orderId . '.pdf');
}
```

### Image Manipulation Library

**File:** `app/library/ImageProcessor.php`

```php
<?php

class LibraryImageProcessor extends Library {
    
    private $image;
    private $width;
    private $height;
    private $type;
    
    /**
     * Load image from file
     */
    public function load($filepath) {
        $info = getimagesize($filepath);
        $this->width = $info[0];
        $this->height = $info[1];
        $this->type = $info[2];
        
        switch ($this->type) {
            case IMAGETYPE_JPEG:
                $this->image = imagecreatefromjpeg($filepath);
                break;
            case IMAGETYPE_PNG:
                $this->image = imagecreatefrompng($filepath);
                break;
            case IMAGETYPE_GIF:
                $this->image = imagecreatefromgif($filepath);
                break;
            default:
                throw new Exception('Unsupported image type');
        }
        
        return $this;
    }
    
    /**
     * Resize image
     */
    public function resize($width, $height = null, $keepAspectRatio = true) {
        if ($keepAspectRatio && $height === null) {
            $height = ($this->height / $this->width) * $width;
        }
        
        $newImage = imagecreatetruecolor($width, $height);
        
        // Preserve transparency
        if ($this->type == IMAGETYPE_PNG || $this->type == IMAGETYPE_GIF) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }
        
        imagecopyresampled(
            $newImage, $this->image,
            0, 0, 0, 0,
            $width, $height,
            $this->width, $this->height
        );
        
        imagedestroy($this->image);
        $this->image = $newImage;
        $this->width = $width;
        $this->height = $height;
        
        return $this;
    }
    
    /**
     * Crop image
     */
    public function crop($x, $y, $width, $height) {
        $newImage = imagecreatetruecolor($width, $height);
        
        imagecopy($newImage, $this->image, 0, 0, $x, $y, $width, $height);
        
        imagedestroy($this->image);
        $this->image = $newImage;
        $this->width = $width;
        $this->height = $height;
        
        return $this;
    }
    
    /**
     * Save image to file
     */
    public function save($filepath, $quality = 90) {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($this->image, $filepath, $quality);
                break;
            case 'png':
                imagepng($this->image, $filepath, 9 - round(($quality / 100) * 9));
                break;
            case 'gif':
                imagegif($this->image, $filepath);
                break;
            default:
                throw new Exception('Unsupported save format');
        }
        
        return $this;
    }
    
    /**
     * Output image to browser
     */
    public function output($type = 'jpeg', $quality = 90) {
        header('Content-Type: image/' . $type);
        
        switch ($type) {
            case 'jpeg':
                imagejpeg($this->image, null, $quality);
                break;
            case 'png':
                imagepng($this->image, null, 9 - round(($quality / 100) * 9));
                break;
            case 'gif':
                imagegif($this->image);
                break;
        }
        
        return $this;
    }
    
    /**
     * Destroy image resource
     */
    public function __destruct() {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }
}
```

**Usage:**

```php
public function thumbnail() {
    $image = $this->request->get['image'];
    $imagePath = 'storage/uploads/' . $image;
    
    // Load image processor
    $this->load->library('ImageProcessor');
    
    // Process image
    $this->ImageProcessor
        ->load($imagePath)
        ->resize(200, 200)
        ->output('jpeg', 85);
}
```

---

## Best Practices

### 1. Make Libraries Stateless When Possible

```php
// Good: Methods don't depend on internal state
class LibraryStringHelper extends Library {
    public function slugify($string) {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $string));
    }
}

// Use: Can call multiple times without side effects
$slug1 = $this->StringHelper->slugify('Hello World');
$slug2 = $this->StringHelper->slugify('Another String');
```

### 2. Use Method Chaining

```php
// Good: Fluent interface
$this->Upload
    ->setUploadPath('storage/uploads/')
    ->setAllowedExtensions(['jpg', 'png'])
    ->setMaxSize(5242880)
    ->upload($_FILES['file']);
```

### 3. Provide Clear Error Messages

```php
public function upload($file) {
    if (!$this->validate($file)) {
        // Store detailed errors
        return false;
    }
    // ...
}

public function getErrors() {
    return $this->errors;
}
```

### 4. Document Your Libraries

```php
/**
 * Pagination Library
 * 
 * Generates pagination links for database queries
 * 
 * @example
 * $this->load->library('Pagination');
 * $this->Pagination->initialize($total, $page, $limit, $url);
 * $html = $this->Pagination->render();
 */
class LibraryPagination extends Library {
    // ...
}
```

### 5. Handle Dependencies Properly

```php
public function __construct($registry) {
    parent::__construct($registry);
    
    // Check for required extensions
    if (!extension_loaded('gd')) {
        throw new Exception('GD extension is required');
    }
}
```

---

## Related Documentation

- **[Controllers](07-controllers.md)** - Loading libraries in controllers
- **[Services](11-services.md)** - When to use services vs libraries
- **[Helpers](14-helpers.md)** - Simple functions vs library classes

---

**Previous:** [Services](11-services.md)  
**Next:** [Language Files](13-language.md)
